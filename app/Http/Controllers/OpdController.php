<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\OpdToken;
use App\Models\Appointment;
use App\Models\Encounter;
use App\Models\Patient;
use App\Models\Doctor;
use App\Models\ActivityLog;

class OpdController extends Controller
{
    // Reception desk — issue tokens
    public function index(Request $request)
    {
        $doctors = Doctor::where('is_active', true)->orderBy('first_name')->get();
        $doctorId = $request->doctor_id;

        $queue = OpdToken::with('patient','doctor')
            ->whereDate('token_date', today())
            ->when($doctorId, fn($q) => $q->where('doctor_id', $doctorId))
            ->orderBy('priority','desc')
            ->orderBy('token_no')
            ->get();

        $stats = OpdToken::todayStats();

        $appointments = Appointment::with('patient','doctor')
            ->whereDate('appointment_date', today())
            ->whereIn('status', ['Scheduled', 'Confirmed'])
            ->when($doctorId, fn($q) => $q->where('doctor_id', $doctorId))
            ->orderBy('appointment_time')
            ->get();

        return view('opd.index', compact('queue','doctors','stats','doctorId','appointments'));
    }

    // Issue a new token
    public function store(Request $request)
    {
        $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'doctor_id'  => 'nullable|exists:doctors,id',
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

        $token = OpdToken::create([
            'token_no'   => OpdToken::nextTokenNo(),
            'token_date' => today(),
            'patient_id' => $request->patient_id,
            'doctor_id'  => $request->doctor_id,
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

        $waiting = OpdToken::with('patient')
            ->whereDate('token_date', today())
            ->where('doctor_id', $doctorId)
            ->whereIn('status', ['waiting','called'])
            ->orderBy('priority','desc')
            ->orderBy('token_no')
            ->get();

        $current = OpdToken::with(['patient','encounter'])
            ->whereDate('token_date', today())
            ->where('doctor_id', $doctorId)
            ->where('status', 'in_consultation')
            ->first();

        $done = OpdToken::with(['patient','encounter'])
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

        $queue = OpdToken::with('patient','doctor')
            ->whereDate('token_date', today())
            ->when($doctorId, fn($q) => $q->where('doctor_id', $doctorId))
            ->whereIn('status', ['waiting','called','in_consultation'])
            ->orderBy('priority','desc')
            ->orderBy('token_no')
            ->get();

        $current = $queue->firstWhere('status', 'in_consultation')
                ?? $queue->firstWhere('status', 'called');

        $stats = OpdToken::todayStats();

        return view('opd.board', compact('queue','current','doctors','doctorId','stats'));
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

    // Printable daily OPD register
    public function register(Request $request)
    {
        $date     = $request->get('date', today()->toDateString());
        $doctorId = $request->get('doctor_id');

        $tokens = OpdToken::with(['patient', 'doctor', 'encounter', 'triage'])
            ->whereDate('token_date', $date)
            ->when($doctorId, fn($q) => $q->where('doctor_id', $doctorId))
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

        return view('opd.register', compact('tokens', 'doctors', 'date', 'doctorId', 'stats'));
    }

    // Print token slip
    public function slip(OpdToken $token)
    {
        $token->load(['patient', 'doctor']);
        $position = OpdToken::whereDate('token_date', $token->token_date)
            ->where('token_no', '<=', $token->token_no)
            ->whereIn('status', ['waiting', 'called', 'in_consultation'])
            ->count();

        return view('opd.slip', compact('token', 'position'));
    }

    // Public TV Queue Display Board (no auth required)
    public function publicBoard()
    {
        $doctors = Doctor::where('is_active', true)->orderBy('first_name')->get();

        // For each doctor, build their queue grouped
        $doctorQueues = $doctors->map(function ($doctor) {
            $active = OpdToken::with('patient')
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

        return view('opd.public-board', compact('doctorQueues', 'stats'));
    }

    // JSON endpoint for live polling
    public function queueData(Request $request)
    {
        $doctorId = $request->doctor_id;

        $tokens = OpdToken::with('patient:id,first_name,last_name','doctor:id,first_name,last_name')
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
            ]);

        return response()->json([
            'tokens' => $tokens,
            'stats'  => OpdToken::todayStats(),
        ]);
    }
}
