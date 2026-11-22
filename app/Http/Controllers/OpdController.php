<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\OpdToken;
use App\Models\Appointment;
use App\Models\Encounter;
use App\Models\Patient;
use App\Models\Doctor;
use App\Models\ActivityLog;
use App\Models\Department;

class OpdController extends Controller
{
    /** Active departments that have at least one active doctor (for speciality filters) */
    public static function opdDepartments()
    {
        $doctors = Doctor::where('is_active', true)->with('departments')->get();
        return Department::where('is_active', true)->ordered()->get()
            ->filter(fn ($d) => $doctors->contains(fn ($doc) => in_array($d->id, $doc->departmentIds())))->values();
    }

    /** Limit a token/appointment query to a department: its own department, or (if none recorded) the doctor's */
    private function inDepartment($q, ?int $deptId)
    {
        if (!$deptId) return $q;
        $doctorIds = Doctor::where('department_id', $deptId)
            ->orWhereHas('departments', fn ($d) => $d->where('departments.id', $deptId))->pluck('id');
        return $q->where(fn ($w) => $w->where('department_id', $deptId)
            ->orWhere(fn ($n) => $n->whereNull('department_id')->whereIn('doctor_id', $doctorIds)));
    }

    // Reception desk — issue tokens
    public function index(Request $request)
    {
        $doctors = Doctor::where('is_active', true)->orderBy('first_name')->get();
        $doctorId = $request->doctor_id;
        $deptId   = $request->integer('department_id') ?: null;
        $departments = self::opdDepartments();

        $queue = $this->inDepartment(OpdToken::with('patient','doctor','department'), $deptId)
            ->whereDate('token_date', today())
            ->when($doctorId, fn($q) => $q->where('doctor_id', $doctorId))
            ->orderBy('priority','desc')
            ->orderBy('token_no')
            ->get();

        $stats = OpdToken::todayStats();

        $appointments = $this->inDepartment(Appointment::with('patient','doctor','department'), $deptId)
            ->whereDate('appointment_date', today())
            ->whereIn('status', ['Scheduled', 'Confirmed'])
            ->when($doctorId, fn($q) => $q->where('doctor_id', $doctorId))
            ->orderBy('appointment_time')
            ->get();

        return view('opd.index', compact('queue','doctors','stats','doctorId','appointments','departments','deptId'));
    }

    // Issue a new token
    public function store(Request $request)
    {
        $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'doctor_id'  => 'nullable|exists:doctors,id',
            'department_id' => 'nullable|exists:departments,id',
            'priority'   => 'required|in:normal,urgent',
            'purpose'    => 'nullable|string|max:200',
        ]);

        $activeToday = OpdToken::where('patient_id', $request->patient_id)
            ->whereDate('token_date', today())
            ->whereNotIn('status', ['done', 'skipped'])
            ->first();
        if ($activeToday) {
            return back()->with('error', "This patient already has an active token today ({$activeToday->display}). Complete or skip it before issuing a new one.")->withInput();
        }

        $token = OpdToken::issue([
            'patient_id' => $request->patient_id,
            'doctor_id'  => $request->doctor_id,
            'department_id' => $request->department_id,
            'status'     => 'waiting',
            'priority'   => $request->priority,
            'purpose'    => $request->purpose,
            'created_by' => auth()->id(),
        ]);

        $token->load('patient','doctor');
        ActivityLog::log('created', "Issued OPD token {$token->display} to {$token->patient->first_name} {$token->patient->last_name}" . ($token->doctor ? " — Dr. {$token->doctor->first_name} {$token->doctor->last_name}" : ''));

        return redirect()->route('opd.index')->with('success', "Token {$token->display} issued successfully.");
    }

    // Update token status (called, in_consultation, done, skipped)
    public function updateStatus(Request $request, OpdToken $token)
    {
        $request->validate(['status' => 'required|in:called,in_consultation,done,skipped']);

        $updates = ['status' => $request->status];

        if ($request->status === 'called')           $updates['called_at'] = now();
        if ($request->status === 'in_consultation')  $updates['consultation_started_at'] = now();
        if (in_array($request->status, ['done','skipped'])) $updates['completed_at'] = now();

        $token->update($updates);
        $token->load('patient');

        ActivityLog::log('updated', "OPD token {$token->display} ({$token->patient->first_name} {$token->patient->last_name}) marked as {$request->status}");

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'status' => $token->status]);
        }
        return back()->with('success', "Token {$token->display} updated.");
    }

    // Doctor's queue view
    public function doctorQueue(Request $request)
    {
        $doctors  = Doctor::where('is_active', true)->orderBy('first_name')->get();
        $doctorId = $request->doctor_id ?? $doctors->firstWhere('id', 1)?->id ?? $doctors->first()?->id;

        $waiting = OpdToken::with('patient', 'department')
            ->whereDate('token_date', today())
            ->where('doctor_id', $doctorId)
            ->whereIn('status', ['waiting','called'])
            ->orderBy('priority','desc')
            ->orderBy('token_no')
            ->get();

        $current = OpdToken::with(['patient','encounter','department'])
            ->whereDate('token_date', today())
            ->where('doctor_id', $doctorId)
            ->where('status', 'in_consultation')
            ->first();

        $done = OpdToken::with(['patient','encounter','department'])
            ->whereDate('token_date', today())
            ->where('doctor_id', $doctorId)
            ->whereIn('status', ['done','skipped'])
            ->orderByDesc('completed_at')
            ->get();

        return view('opd.doctor', compact('waiting','current','done','doctors','doctorId'));
    }

    // Mark current token done and start the next waiting token for the same doctor
    public function nextPatient(Request $request, OpdToken $token)
    {
        // Mark current as done
        $token->update(['status' => 'done', 'completed_at' => now()]);
        $token->load('patient');
        ActivityLog::log('updated', "OPD token {$token->display} ({$token->patient->full_name}) marked done via Next Patient");

        // Find next waiting/called token for the same doctor
        $next = OpdToken::with('patient')
            ->whereDate('token_date', today())
            ->where('doctor_id', $token->doctor_id)
            ->whereIn('status', ['waiting', 'called'])
            ->orderBy('priority', 'desc')
            ->orderBy('token_no')
            ->first();

        if (!$next) {
            return redirect()->route('opd.doctor', ['doctor_id' => $token->doctor_id])
                ->with('success', "Done with {$token->display}. No more patients waiting.");
        }

        // Start encounter for next patient
        if ($next->encounter) {
            $next->update(['status' => 'in_consultation', 'consultation_started_at' => now()]);
            return redirect()->route('encounters.show', $next->encounter);
        }

        $encounter = Encounter::create([
            'encounter_no'   => Encounter::generateNo(),
            'patient_id'     => $next->patient_id,
            'doctor_id'      => $next->doctor_id,
            'opd_token_id'   => $next->id,
            'encounter_date' => today(),
            'encounter_time' => now()->format('H:i'),
            'visit_type'     => 'New Visit',
            'status'         => 'Open',
            'created_by'     => auth()->id(),
        ]);

        $next->update(['status' => 'in_consultation', 'consultation_started_at' => now()]);
        ActivityLog::log('created', "Started encounter {$encounter->encounter_no} for {$next->display} ({$next->patient->full_name}) via Next Patient");

        return redirect()->route('encounters.show', $encounter);
    }

    // Live queue board (public display / nurse station)
    public function board(Request $request)
    {
        $doctors  = Doctor::where('is_active', true)->orderBy('first_name')->get();
        $doctorId = $request->doctor_id;
        $deptId   = $request->integer('department_id') ?: null;
        $departments = self::opdDepartments();

        $queue = $this->inDepartment(OpdToken::with('patient','doctor','department'), $deptId)
            ->whereDate('token_date', today())
            ->when($doctorId, fn($q) => $q->where('doctor_id', $doctorId))
            ->whereIn('status', ['waiting','called','in_consultation'])
            ->orderBy('priority','desc')
            ->orderBy('token_no')
            ->get();

        $current = $queue->firstWhere('status', 'in_consultation')
                ?? $queue->firstWhere('status', 'called');

        $stats = OpdToken::todayStats();

        return view('opd.board', compact('queue','current','doctors','doctorId','stats','departments','deptId'));
    }

    // Create (or reopen) an encounter for this token and go straight to it
    public function startEncounter(OpdToken $token)
    {
        // If an encounter already exists for this token, just redirect to it
        if ($token->encounter) {
            if ($token->status === 'waiting' || $token->status === 'called') {
                $token->update(['status' => 'in_consultation', 'consultation_started_at' => now()]);
            }
            return redirect()->route('encounters.show', $token->encounter);
        }

        // Create a new encounter
        $encounter = Encounter::create([
            'encounter_no'   => Encounter::generateNo(),
            'patient_id'     => $token->patient_id,
            'doctor_id'      => $token->doctor_id,
            'opd_token_id'   => $token->id,
            'encounter_date' => today(),
            'encounter_time' => now()->format('H:i'),
            'visit_type'     => 'New Visit',
            'status'         => 'Open',
            'created_by'     => auth()->id(),
        ]);

        $token->update(['status' => 'in_consultation', 'consultation_started_at' => now()]);

        $token->load('patient');
        ActivityLog::log('created', "Started encounter {$encounter->encounter_no} for OPD token {$token->display} ({$token->patient->full_name})");

        return redirect()->route('encounters.show', $encounter);
    }

    // Find a Doctor: department-wise directory with each doctor's sitting, leave and free slots on a date
    public function directory(Request $request, \App\Services\DoctorAvailability $avail)
    {
        $date = $request->filled('date') ? \Illuminate\Support\Carbon::parse($request->date)->startOfDay() : today();
        $doctors = Doctor::where('is_active', true)->with('schedules', 'departments', 'department')->orderBy('first_name')->get();
        $waiting = OpdToken::whereDate('token_date', today())->whereIn('status', ['waiting', 'called'])
            ->selectRaw('doctor_id, count(*) n')->groupBy('doctor_id')->pluck('n', 'doctor_id');

        // per department + doctor: that department's sessions only (e.g. Cardiology Tue/Thu)
        $info = [];
        $departments = self::opdDepartments()->map(function ($d) use ($doctors, $avail, $date, $waiting, &$info) {
            $docs = $doctors->filter(fn ($doc) => in_array($d->id, $doc->departmentIds()))->values();
            foreach ($docs as $doc) {
                $slots = $avail->slots($doc, $date, $d->id);
                $free  = collect($slots)->where('available', true);
                $info[$d->id][$doc->id] = [
                    'leave'     => $avail->leaveOn($doc, $date),
                    'sessions'  => $avail->sessionsOn($doc, $date, $d->id),
                    'timetable' => $avail->timetableLabel($doc, $d->id),
                    'free'      => $free->count(),
                    'next'      => $free->first()['time'] ?? null,
                    'full'      => collect($slots)->isNotEmpty() && $free->isEmpty(),
                    'waiting'   => $waiting[$doc->id] ?? 0,
                ];
            }
            $d->setRelation('opdDoctors', $docs);
            return $d;
        });

        return view('opd.directory', compact('departments', 'info', 'date'));
    }

    // Printable daily OPD register
    public function register(Request $request)
    {
        // Date range (from/to). Falls back to the legacy single ?date= param,
        // then to today, so existing links keep working. Swap if reversed.
        $from = $request->get('from') ?: $request->get('date', today()->toDateString());
        $to   = $request->get('to')   ?: $request->get('date', $from);
        if ($to < $from) { [$from, $to] = [$to, $from]; }
        $date     = $from; // kept for backward-compatible references
        $doctorId = $request->get('doctor_id');

        $tokens = OpdToken::with(['patient', 'doctor', 'encounter', 'triage', 'department'])
            ->whereDate('token_date', '>=', $from)
            ->whereDate('token_date', '<=', $to)
            ->when($doctorId, fn($q) => $q->where('doctor_id', $doctorId))
            ->orderBy('token_date')
            ->orderBy('priority', 'desc')
            ->orderBy('token_no')
            ->get();

        $doctors = Doctor::where('is_active', true)->orderBy('first_name')->get();
        $stats   = [
            'total'    => $tokens->count(),
            'done'     => $tokens->whereIn('status', ['done'])->count(),
            'waiting'  => $tokens->where('status', 'waiting')->count(),
            'skipped'  => $tokens->where('status', 'skipped')->count(),
        ];

        return view('opd.register', compact('tokens', 'doctors', 'date', 'from', 'to', 'doctorId', 'stats'));
    }

    // Print token slip
    public function slip(OpdToken $token)
    {
        $token->load(['patient', 'doctor']);
        $position = OpdToken::whereDate('token_date', $token->token_date)
            ->where('queue', $token->queue)
            ->where('token_no', '<=', $token->token_no)
            ->whereIn('status', ['waiting', 'called', 'in_consultation'])
            ->count();

        return view('opd.slip', compact('token', 'position'));
    }

    // Public TV Queue Display Board (no auth required)
    public function publicBoard(Request $request)
    {
        // ?dept=CARD (code) shows one department's waiting area; no parameter = all
        $dept = $request->filled('dept')
            ? Department::where('code', strtoupper($request->dept))->orWhere('id', (int) $request->dept)->first()
            : null;
        $doctors = Doctor::where('is_active', true)->with('department')->orderBy('first_name')->get();

        // For each doctor, build their queue grouped
        $doctorQueues = $doctors->map(function ($doctor) use ($dept) {
            $active = $this->inDepartment(OpdToken::with('patient', 'department'), $dept?->id)
                ->whereDate('token_date', today())
                ->where('doctor_id', $doctor->id)
                ->whereIn('status', ['waiting', 'called', 'in_consultation'])
                ->orderBy('priority', 'desc')
                ->orderBy('token_no')
                ->get();

            $current = $active->firstWhere('status', 'in_consultation')
                    ?? $active->firstWhere('status', 'called');

            $waiting = $active->whereIn('status', ['waiting', 'called'])->values();

            return [
                'doctor'  => $doctor,
                'current' => $current,
                'waiting' => $waiting->take(5),
            ];
        })->filter(fn($d) => $d['current'] || $d['waiting']->isNotEmpty())->values();

        $stats = OpdToken::todayStats();

        return view('opd.public-board', compact('doctorQueues', 'stats', 'dept'));
    }

    // JSON endpoint for live polling
    public function queueData(Request $request)
    {
        $doctorId = $request->doctor_id;
        $deptId   = $request->integer('department_id') ?: null;

        $tokens = $this->inDepartment(OpdToken::with('patient:id,first_name,last_name','doctor:id,first_name,last_name','department:id,name,room'), $deptId)
            ->whereDate('token_date', today())
            ->when($doctorId, fn($q) => $q->where('doctor_id', $doctorId))
            ->orderBy('priority','desc')
            ->orderBy('token_no')
            ->get()
            ->map(fn($t) => [
                'id'       => $t->id,
                'display'  => $t->display,
                'status'   => $t->status,
                'priority' => $t->priority,
                'patient'  => $t->patient->first_name . ' ' . $t->patient->last_name,
                'doctor'   => $t->doctor ? 'Dr. ' . $t->doctor->first_name . ' ' . $t->doctor->last_name : '—',
                'purpose'  => $t->purpose,
                'department' => $t->department?->name,
                'room'     => $t->department?->room,
            ]);

        return response()->json([
            'tokens' => $tokens,
            'stats'  => OpdToken::todayStats(),
        ]);
    }
}
