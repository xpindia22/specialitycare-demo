<?php
// FILE: app/Http/Controllers/PatientController.php
namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\ActivityLog;
use App\Models\Encounter;
use App\Models\Appointment;
use App\Models\Bill;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PatientController extends Controller
{
    public function index(Request $request)
    {
        $query = Patient::query();
        if ($request->search) {
            $s = $request->search;
            $query->where(function($q) use ($s) {
                $q->where('jhc_id', 'like', "%{$s}%")
                  ->orWhere('first_name', 'like', "%{$s}%")
                  ->orWhere('last_name', 'like', "%{$s}%")
                  ->orWhere('phone', 'like', "%{$s}%")
                  ->orWhere('openemr_pid', 'like', "%{$s}%");
            });
        }
        if ($request->gender) $query->where('gender', $request->gender);
        if ($request->blood_group) $query->where('blood_group', $request->blood_group);
        if ($request->status === 'active')   $query->where('is_active', true);
        if ($request->status === 'inactive') $query->where('is_active', false);

        // Sortable column whitelist. Keys are URL-friendly tokens; values are
        // the DB columns to ORDER BY in order. "patient_name" sorts by first
        // then last; "age" sorts by date_of_birth (older first when desc).
        $sortMap = [
            'jhc_id'       => ['jhc_id'],
            'patient_name' => ['first_name', 'last_name'],
            'age'          => ['date_of_birth'],
            'gender'       => ['gender'],
            'address'      => ['address'],
            'phone'        => ['phone'],
            'city'         => ['city'],
            'created_at'   => ['created_at'],
        ];
        $sort = $request->input('sort');
        $dir  = strtolower((string) $request->input('dir')) === 'asc' ? 'asc' : 'desc';
        $query->orderByDesc('is_pinned');
        if (isset($sortMap[$sort])) {
            foreach ($sortMap[$sort] as $col) {
                $query->orderBy($col, $dir);
            }
        } else {
            $query->latest();
        }

        $patients = $query->paginate(20)->withQueryString();
        return view('patients.index', compact('patients'));
    }

    public function create(Request $request)
    {
        $referralSources = \App\Models\ReferralSource::where('is_active', true)->orderBy('name')->get();
        return view('patients.create', compact('referralSources'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name'  => 'required|string|max:100',
            'middle_name'=> 'nullable|string|max:100',
            'gender'     => 'required|in:Male,Female,Other,Unknown',
            'date_of_birth' => 'required|date|before:today',
            'blood_group'   => 'nullable|string',
            'phone'      => 'required|string|max:15',
            'alt_phone'  => 'nullable|string|max:15',
            'email'      => 'nullable|email|max:100',
            'address'    => 'required|string',
            'city'       => 'nullable|string|max:100',
            'state'      => 'nullable|string|max:100',
            'pincode'    => 'nullable|string|max:10',
            'aadhar_no'  => 'nullable|string|max:20',
            'emergency_contact_name'  => 'nullable|string|max:100',
            'emergency_contact_phone' => 'nullable|string|max:15',
            'emergency_contact_relation' => 'nullable|string|max:50',
            'occupation' => 'nullable|string|max:100',
            'marital_status' => 'nullable|string|max:50',
            'referred_by'    => 'nullable|string|max:100',
            'insurance_company'     => 'nullable|string|max:200',
            'policy_no'             => 'nullable|string|max:100',
            'tpa_name'              => 'nullable|string|max:200',
            'insurance_valid_until' => 'nullable|date',
            'referral_source_id'    => 'nullable|exists:referral_sources,id',
        ]);

        // Warn if duplicate phone detected (unless user confirmed)
        if (!$request->confirm_duplicate) {
            $existing = Patient::where('phone', $validated['phone'])->first();
            if ($existing) {
                return back()->withInput()
                    ->with('duplicate_warning', $existing->only(['id', 'full_name', 'jhc_id', 'phone', 'age']))
                    ->withErrors(['phone' => "A patient with this phone number already exists: {$existing->full_name} ({$existing->jhc_id}). Check the box below to register anyway."]);
            }
        }

        $validated['jhc_id'] = Patient::generateJhcId();

        if ($request->hasFile('photo')) {
            $validated['photo'] = $request->file('photo')->store('patients/photos', 'public');
        }

        $patient = Patient::create($validated);
        ActivityLog::log('created', "Registered new patient {$patient->first_name} {$patient->last_name} (ID: {$patient->jhc_id})");

        return redirect()->route('patients.show', $patient)
            ->with('success', "Patient registered successfully. JHC ID: {$patient->jhc_id}");
    }

    public function show(Patient $patient)
    {
        $encounters = $patient->encounters()->with('doctor', 'diagnoses')->latest('encounter_date')->paginate(10)->fragment('tab-encounters');
        $appointments    = $patient->appointments()->with('doctor')->latest('appointment_date')->take(5)->get();
        $nextAppointment = $patient->appointments()->with('doctor')
            ->where('appointment_date', '>=', today())
            ->whereNotIn('status', ['Cancelled', 'No Show'])
            ->orderBy('appointment_date')->orderBy('appointment_time')
            ->first();
        $bills = $patient->bills()->latest('bill_date')->take(10)->get();
        $billSummary = [
            'total'   => $bills->sum('total_amount'),
            'paid'    => $bills->sum('paid_amount'),
            'due'     => $bills->sum('due_amount'),
            'unpaid'  => $bills->whereIn('status', ['Unpaid','Partial'])->count(),
        ];
        $labOrders = $patient->labOrders()->with('items.test')->latest('ordered_date')->take(5)->get();
        $prescriptions = $patient->prescriptions()->with('items.medicine', 'doctor')->latest('prescribed_date')->take(5)->get();

        $refillAlerts = $patient->prescriptions()->with('items.medicine')->latest('prescribed_date')->get()
            ->flatMap(function ($rx) {
                return $rx->items->map(function ($item) use ($rx) {
                    $days   = $item->duration_days > 0 ? $item->duration_days : 30;
                    $dueAt  = \Carbon\Carbon::parse($rx->prescribed_date)->addDays($days);
                    $diff   = today()->diffInDays($dueAt, false);
                    if ($diff <= 3) {
                        return [
                            'name'         => $item->medicine?->name ?? $item->generic_name ?? 'Medication',
                            'due_at'       => $dueAt,
                            'diff'         => $diff,
                            'encounter_id' => $rx->encounter_id,
                        ];
                    }
                    return null;
                });
            })
            ->filter()
            // Dedupe by medicine name + due date (same med across multiple Rx
            // rows shouldn't show up 3 times — keeps the WA reminder list in
            // sync, see SendRefillReminderWhatsApp).
            ->unique(fn ($r) => strtolower(trim($r['name'])) . '|' . $r['due_at']->toDateString())
            ->sortBy('diff')
            ->values();
        $ecgRecords = $patient->ecgRecords()->latest('record_date')->take(3)->get();
        $echoRecords = $patient->echoRecords()->latest('record_date')->take(3)->get();
        $admissions = $patient->admissions()->with(['ward', 'bed', 'admittingDoctor'])->latest('admission_date')->get();
        $currentAdmission = $admissions->firstWhere('status', 'admitted');

        // Vital sign trends — only fields with ≥2 readings.
        $vitalEncounters = $patient->encounters()
            ->orderBy('encounter_date')
            ->get(['id','encounter_date','bp_systolic','bp_diastolic','pulse','temperature','spo2','respiratory_rate','blood_glucose','weight','bmi']);
        $vitalSpecs = [
            'bp'              => ['label' => 'Blood Pressure',   'unit' => 'mmHg', 'fields' => ['bp_systolic','bp_diastolic']],
            'pulse'           => ['label' => 'Pulse',            'unit' => 'bpm',  'fields' => ['pulse']],
            'temperature'     => ['label' => 'Temperature',      'unit' => '°C',   'fields' => ['temperature']],
            'spo2'            => ['label' => 'SpO₂',             'unit' => '%',    'fields' => ['spo2']],
            'respiratory_rate'=> ['label' => 'Respiratory Rate', 'unit' => '/min', 'fields' => ['respiratory_rate']],
            'blood_glucose'   => ['label' => 'Blood Glucose',    'unit' => 'mg/dL','fields' => ['blood_glucose']],
            'weight'          => ['label' => 'Weight',           'unit' => 'kg',   'fields' => ['weight']],
            'bmi'             => ['label' => 'BMI',              'unit' => 'kg/m²','fields' => ['bmi']],
        ];
        $vitalsTrends = collect();
        foreach ($vitalSpecs as $key => $spec) {
            $points = $vitalEncounters
                ->filter(fn($e) => collect($spec['fields'])->every(fn($f) => is_numeric($e->{$f})))
                ->map(fn($e) => [
                    'date'   => \Carbon\Carbon::parse($e->encounter_date)->format('d M Y'),
                    'values' => collect($spec['fields'])->map(fn($f) => (float) $e->{$f})->all(),
                ])
                ->values();
            if ($points->count() >= 2) {
                $vitalsTrends->push([
                    'key'    => $key,
                    'label'  => $spec['label'],
                    'unit'   => $spec['unit'],
                    'fields' => $spec['fields'],
                    'points' => $points,
                ]);
            }
        }

        // Lab trends: tests done ≥2 times with numeric results
        $allLabItems = \App\Models\LabOrderItem::whereHas('order', fn($q) => $q->where('patient_id', $patient->id))
            ->with(['test','order'])
            ->whereNotNull('result_value')
            ->whereNotNull('lab_test_id')
            ->get();
        $labTrends = $allLabItems
            ->groupBy('lab_test_id')
            ->filter(fn($items) => $items->count() >= 2 && $items->every(fn($i) => is_numeric($i->result_value)))
            ->map(fn($items) => [
                'name'   => $items->first()->test->name ?? 'Test',
                'unit'   => $items->first()->test->unit ?? '',
                'points' => $items->sortBy(fn($i) => $i->order->ordered_date)->map(fn($i) => [
                    'date'  => \Carbon\Carbon::parse($i->order->ordered_date)->format('d M Y'),
                    'value' => (float) $i->result_value,
                ])->values(),
            ])->values()->take(6);

        // Patient timeline: encounters + labs + bills + admissions + prescriptions
        $patientTimeline = collect();
        $patient->encounters()->with('doctor', 'diagnoses')->get()
            ->each(fn($e) => $patientTimeline->push(['date' => $e->encounter_date, 'type' => 'encounter', 'data' => $e]));
        $patient->labOrders()->with('items.test')->get()
            ->each(fn($l) => $patientTimeline->push(['date' => $l->ordered_date, 'type' => 'lab', 'data' => $l]));
        $patient->bills()->get()
            ->each(fn($b) => $patientTimeline->push(['date' => $b->bill_date, 'type' => 'bill', 'data' => $b]));
        $patient->admissions()->with(['ward', 'admittingDoctor'])->get()->each(function($adm) use ($patientTimeline) {
            $patientTimeline->push(['date' => $adm->admission_date, 'type' => 'admission', 'data' => $adm]);
            if ($adm->discharge_date) {
                $patientTimeline->push(['date' => $adm->discharge_date, 'type' => 'discharge', 'data' => $adm]);
            }
        });
        $patient->prescriptions()->with('items.medicine', 'doctor')->get()
            ->each(fn($rx) => $patientTimeline->push(['date' => $rx->prescribed_date, 'type' => 'prescription', 'data' => $rx]));
        $patientTimeline = $patientTimeline->sortByDesc('date')->values();

        // Communication log for this patient — manual entries (WhatsApp/Email/
        // Call/In-person) are visible to all staff; in-app DM entries follow the
        // messaging privacy rule (own threads only, unless senior/admin). Newest
        // first, paginated 5/page under 'msgs' so it doesn't clash with encounters.
        // Guarded for installs that haven't run the migration.
        $patientComms = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 5);
        if (\Illuminate\Support\Facades\Schema::hasTable('patient_communications')) {
            $uid = auth()->id();
            $canSeeAll = auth()->user()->can('forum.moderate');
            $patientComms = \App\Models\PatientCommunication::where('patient_id', $patient->id)
                ->where(function ($q) use ($canSeeAll, $uid) {
                    $q->where('mode', '!=', 'dm');           // manual entries: all staff
                    if ($canSeeAll) {
                        $q->orWhere('mode', 'dm');
                    } else {
                        $q->orWhere(fn ($q2) => $q2->where('mode', 'dm')
                            ->whereHas('thread.participants', fn ($p) => $p->where('users.id', $uid)->whereNull('message_thread_user.left_at')));
                    }
                })
                ->with(['withUser', 'logger', 'thread.activeParticipants', 'thread.lastMessage.author'])
                ->orderByDesc('occurred_at')
                ->paginate(5, ['*'], 'msgs')
                ->withQueryString();
        }

        // Staff list for the "Log a communication" picker.
        $commStaff = \App\Models\User::where('is_active', true)
            ->where('has_system_access', true)
            ->orderBy('name')->get(['id', 'name']);

        return view('patients.show', compact(
            'patient', 'encounters', 'appointments', 'nextAppointment', 'bills', 'billSummary',
            'labOrders', 'prescriptions', 'ecgRecords', 'echoRecords',
            'admissions', 'currentAdmission', 'labTrends', 'vitalsTrends', 'patientTimeline',
            'refillAlerts', 'patientComms', 'commStaff'
        ));
    }

    /** Manually log a communication (WhatsApp/Email/Call/In-person) about a patient. */
    public function storeCommunication(Request $request, Patient $patient)
    {
        abort_unless(auth()->user()->can('messages.send'), 403);
        $data = $request->validate([
            'mode'         => ['required', \Illuminate\Validation\Rule::in(array_keys(\App\Models\PatientCommunication::MANUAL_MODES))],
            'with_user_id' => 'nullable|integer|exists:users,id',
            'occurred_at'  => 'nullable|date',
            'note'         => 'required|string|max:5000',
        ]);
        \App\Models\PatientCommunication::create([
            'patient_id'   => $patient->id,
            'mode'         => $data['mode'],
            'with_user_id' => $data['with_user_id'] ?? null,
            'note'         => $data['note'],
            'occurred_at'  => $data['occurred_at'] ?? now(),
            'logged_by'    => auth()->id(),
        ]);
        return back()->with('success', 'Communication logged.')->withFragment('comm-log');
    }

    /** Remove a manually-logged communication entry (author or senior/admin). */
    public function destroyCommunication(Patient $patient, \App\Models\PatientCommunication $communication)
    {
        abort_unless($communication->patient_id === $patient->id, 404);
        // Auto-linked in-app DM threads are managed from the conversation, not
        // here; a manually-logged DM note (no thread) can be removed normally.
        abort_if($communication->mode === 'dm' && $communication->message_thread_id, 422, 'Unlink in-app conversations from the chat instead.');
        abort_unless($communication->logged_by === auth()->id() || auth()->user()->can('forum.moderate'), 403);
        $communication->delete();
        return back()->with('success', 'Communication entry removed.')->withFragment('comm-log');
    }

    public function edit(Patient $patient)
    {
        $referralSources = \App\Models\ReferralSource::where('is_active', true)->orderBy('name')->get();
        return view('patients.edit', compact('patient', 'referralSources'));
    }

    public function update(Request $request, Patient $patient)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name'  => 'required|string|max:100',
            'middle_name'=> 'nullable|string|max:100',
            'gender'     => 'required|in:Male,Female,Other,Unknown',
            'date_of_birth' => 'required|date|before:today',
            'blood_group'   => 'nullable|string',
            'phone'      => 'required|string|max:15',
            'alt_phone'  => 'nullable|string|max:15',
            'email'      => 'nullable|email|max:100',
            'address'    => 'required|string',
            'city'       => 'nullable|string|max:100',
            'state'      => 'nullable|string|max:100',
            'pincode'    => 'nullable|string|max:10',
            'aadhar_no'  => 'nullable|string|max:20',
            'emergency_contact_name'  => 'nullable|string|max:100',
            'emergency_contact_phone' => 'nullable|string|max:15',
            'occupation' => 'nullable|string|max:100',
            'marital_status' => 'nullable|string|max:50',
            'insurance_company'     => 'nullable|string|max:200',
            'policy_no'             => 'nullable|string|max:100',
            'tpa_name'              => 'nullable|string|max:200',
            'insurance_valid_until' => 'nullable|date',
            'referral_source_id'    => 'nullable|exists:referral_sources,id',
            'allergies'             => 'nullable|string',
            'is_chronic'            => 'nullable|boolean',
        ]);
        $validated['is_chronic'] = $request->boolean('is_chronic');

        if ($request->hasFile('photo')) {
            if ($patient->photo) Storage::disk('public')->delete($patient->photo);
            $validated['photo'] = $request->file('photo')->store('patients/photos', 'public');
        }

        $patient->update($validated);
        ActivityLog::log('updated', "Updated patient {$patient->first_name} {$patient->last_name} (ID: {$patient->jhc_id})");
        return redirect()->route('patients.show', $patient)->with('success', 'Patient updated successfully.');
    }

    /**
     * Hard-delete a patient — but only if they have no clinical / billing
     * records attached. Otherwise refuse and tell the user to use the
     * 'Mark Inactive' toggle on the edit form (we don't auto-soft-delete
     * to avoid surprising deletions of real patients).
     */
    public function destroy(Patient $patient)
    {
        abort_unless(auth()->user()?->isOwner(), 403, 'Only the clinic owner can delete patient records.');

        $name = trim("{$patient->first_name} {$patient->last_name}");
        $jhc  = $patient->jhc_id;

        $refs = [
            'bills'         => \DB::table('bills')->where('patient_id', $patient->id)->count(),
            'encounters'    => \DB::table('encounters')->where('patient_id', $patient->id)->count(),
            'prescriptions' => \DB::table('prescriptions')->where('patient_id', $patient->id)->count(),
            'appointments'  => \Schema::hasTable('appointments') ? \DB::table('appointments')->where('patient_id', $patient->id)->count() : 0,
            'admissions'    => \Schema::hasTable('admissions')   ? \DB::table('admissions')->where('patient_id', $patient->id)->count()   : 0,
            'vaccinations'  => \Schema::hasTable('vaccinations') ? \DB::table('vaccinations')->where('patient_id', $patient->id)->count() : 0,
            'lab_orders'    => \Schema::hasTable('lab_orders')   ? \DB::table('lab_orders')->where('patient_id', $patient->id)->count()   : 0,
        ];
        $linked = array_filter($refs);
        if (!empty($linked)) {
            $detail = collect($linked)->map(fn($n, $t) => "{$n} {$t}")->implode(', ');
            return back()->withErrors(['patient' => "Cannot delete {$name} — they have linked records ({$detail}). Use the Active toggle on the edit form to mark them Inactive instead."]);
        }

        $patient->delete();
        ActivityLog::log('deleted', "Deleted patient {$name} (ID: {$jhc}) — had no linked records");
        return redirect()->route('patients.index')->with('success', "Patient {$name} ({$jhc}) deleted.");
    }

    /** One-click active/inactive toggle (for patients who can't be deleted because they have history). */
    public function toggleActive(Patient $patient)
    {
        abort_unless(auth()->user()?->can('patients.edit'), 403);
        $patient->is_active = ! $patient->is_active;
        $patient->save();
        ActivityLog::log('updated', ($patient->is_active ? 'Reactivated' : 'Marked inactive') . " patient {$patient->full_name} ({$patient->jhc_id})");
        return back()->with('success', $patient->is_active ? 'Patient reactivated.' : 'Patient marked inactive — kept in records, hidden from active lists.');
    }

    public function togglePin(Patient $patient)
    {
        abort_unless(auth()->user()?->can('patients.edit'), 403);
        $patient->is_pinned = ! $patient->is_pinned;
        $patient->save();
        return back()->with('success', $patient->is_pinned ? "{$patient->full_name} pinned to top." : "{$patient->full_name} unpinned.");
    }

    public function search(Request $request)
    {
        $term = $request->q;
        $patients = Patient::where('jhc_id', 'like', "%{$term}%")
            ->orWhere('first_name', 'like', "%{$term}%")
            ->orWhere('last_name', 'like', "%{$term}%")
            ->orWhere('phone', 'like', "%{$term}%")
            ->orWhere('openemr_pid', 'like', "%{$term}%")
            ->limit(10)
            ->get(['id', 'jhc_id', 'first_name', 'last_name', 'phone', 'gender', 'date_of_birth']);

        return response()->json($patients->map(fn($p) => [
            'id'        => $p->id,
            'jhc_id'    => $p->jhc_id,
            'full_name' => $p->full_name,
            'name'      => $p->full_name,
            'phone'     => $p->phone,
            'gender'    => $p->gender,
            'age'       => $p->age,
            'age_years' => $p->age_years,
        ]));
    }

    public function quickView(Patient $patient)
    {
        $lastEncounter = $patient->encounters()->with('doctor')->orderByDesc('encounter_date')->first();
        $currentAdmission = $patient->admissions()->with(['ward','admittingDoctor'])->where('status','admitted')->first();
        $outstandingBalance = (float) $patient->bills()->where('status','Unpaid')->sum('due_amount');
        $allergies = $patient->patientAllergies()->pluck('allergen')->toArray();

        return response()->json([
            'id'           => $patient->id,
            'name'         => $patient->full_name,
            'jhc_id'       => $patient->jhc_id,
            'age'          => $patient->age,
            'gender'       => $patient->gender,
            'blood_group'  => $patient->blood_group,
            'phone'        => $patient->phone,
            'allergies'    => $allergies,
            'profile_url'  => route('patients.show', $patient),
            'new_bill_url' => route('billing.create').'?patient_id='.$patient->id,
            'admit_url'    => route('admissions.create').'?patient_id='.$patient->id,
            'opd_url'      => route('opd.index'),
            'last_encounter' => $lastEncounter ? [
                'date'   => $lastEncounter->encounter_date->format('d M Y'),
                'doctor' => optional($lastEncounter->doctor)->full_name ?? '—',
                'reason' => $lastEncounter->chief_complaint ?? '—',
                'url'    => route('encounters.show', $lastEncounter),
            ] : null,
            'current_admission' => $currentAdmission ? [
                'ward'   => optional($currentAdmission->ward)->name ?? '—',
                'date'   => \Carbon\Carbon::parse($currentAdmission->admission_date)->format('d M Y'),
                'days'   => \Carbon\Carbon::parse($currentAdmission->admission_date)->diffInDays(now()) + 1,
                'doctor' => optional($currentAdmission->admittingDoctor)->full_name ?? '—',
                'url'    => route('admissions.show', $currentAdmission),
            ] : null,
            'outstanding_balance' => $outstandingBalance,
        ]);
    }

    public function globalSearch(Request $request)
    {
        $term = trim($request->q ?? '');
        if (strlen($term) < 2) return response()->json([]);

        $results = [];

        // Patients
        $patients = Patient::where('jhc_id', 'like', "%{$term}%")
            ->orWhere('first_name', 'like', "%{$term}%")
            ->orWhere('last_name', 'like', "%{$term}%")
            ->orWhere('phone', 'like', "%{$term}%")
            ->limit(5)->get(['id','jhc_id','first_name','last_name','phone','gender','date_of_birth']);
        foreach ($patients as $p) {
            $results[] = [
                'type'           => 'patient',
                'label'          => $p->full_name,
                'sub'            => $p->jhc_id.' · '.$p->phone,
                'url'            => route('patients.show', $p),
                'icon'           => 'fa-user',
                'patient_id'     => $p->id,
                'quick_view_url' => route('patients.quick-view', $p),
            ];
        }

        // Encounters by encounter_no
        $encounters = \App\Models\Encounter::with('patient')
            ->where('encounter_no','like',"%{$term}%")
            ->limit(3)->get();
        foreach ($encounters as $e) {
            $results[] = ['type'=>'encounter','label'=>$e->encounter_no,'sub'=>'Encounter · '.($e->patient->full_name ?? ''),'url'=>route('encounters.show',$e),'icon'=>'fa-stethoscope'];
        }

        // Bills by bill_no
        $bills = \App\Models\Bill::with('patient')
            ->where('bill_no','like',"%{$term}%")
            ->limit(3)->get();
        foreach ($bills as $b) {
            $results[] = ['type'=>'bill','label'=>$b->bill_no,'sub'=>'Bill · '.($b->patient->full_name ?? '').' · ₹'.number_format($b->total_amount,0),'url'=>route('billing.show',$b),'icon'=>'fa-file-invoice-dollar'];
        }

        return response()->json($results);
    }

    public function timeline(Patient $patient)
    {
        $timeline = collect();

        $encounters = $patient->encounters()->with('doctor', 'diagnoses', 'prescriptions.items.medicine')->get();
        foreach ($encounters as $enc) {
            $timeline->push(['date' => $enc->encounter_date, 'type' => 'encounter', 'data' => $enc]);
        }

        $prescriptions = $patient->prescriptions()->with('doctor', 'items.medicine')->get();
        foreach ($prescriptions as $rx) {
            $timeline->push(['date' => $rx->prescribed_date, 'type' => 'prescription', 'data' => $rx]);
        }

        $labs = $patient->labOrders()->with('items.test')->get();
        foreach ($labs as $lab) {
            $timeline->push(['date' => $lab->ordered_date, 'type' => 'lab', 'data' => $lab]);
        }

        foreach ($patient->ecgRecords()->get() as $ecg) {
            $timeline->push(['date' => $ecg->record_date, 'type' => 'ecg', 'data' => $ecg]);
        }

        foreach ($patient->echoRecords()->get() as $echo) {
            $timeline->push(['date' => $echo->record_date, 'type' => 'echo', 'data' => $echo]);
        }

        $bills = $patient->bills()->get();
        foreach ($bills as $bill) {
            $timeline->push(['date' => $bill->bill_date, 'type' => 'bill', 'data' => $bill]);
        }

        $appointments = $patient->appointments()->with('doctor')->get();
        foreach ($appointments as $appt) {
            $timeline->push(['date' => $appt->appointment_date, 'type' => 'appointment', 'data' => $appt]);
        }

        $admissions = $patient->admissions()->with(['ward', 'admittingDoctor'])->get();
        foreach ($admissions as $adm) {
            $timeline->push(['date' => $adm->admission_date, 'type' => 'admission', 'data' => $adm]);
            if ($adm->discharge_date) {
                $timeline->push(['date' => $adm->discharge_date, 'type' => 'discharge', 'data' => $adm]);
            }
        }

        $timeline = $timeline->sortByDesc('date')->values();

        // Summary metrics for the header strip
        $summary = [
            'visits'      => $encounters->count(),
            'last_visit'  => $encounters->max('encounter_date'),
            'labs'        => $labs->count(),
            'cardiac'     => $patient->ecgRecords()->count() + $patient->echoRecords()->count(),
            'outstanding' => $bills->where('status', '!=', 'Paid')->sum('due_amount'),
        ];

        return view('patients.timeline', compact('patient', 'timeline', 'summary'));
    }

    public function certificate(Patient $patient, Request $request)
    {
        $type = $request->get('type', 'fitness');
        return view('patients.certificate', compact('patient', 'type'));
    }

    public function growthCharts(Patient $patient)
    {
        if (!$patient->date_of_birth) {
            return redirect()->route('patients.show', $patient)->with('error', 'Patient DOB is required for growth charts.');
        }
        $dob = \Carbon\Carbon::parse($patient->date_of_birth);

        $points = $patient->encounters()
            ->orderBy('encounter_date')
            ->get(['id','encounter_date','weight','height','bmi'])
            ->map(function ($e) use ($dob) {
                $d = \Carbon\Carbon::parse($e->encounter_date);
                return (object) [
                    'date'        => $d->format('d M Y'),
                    'age_months'  => round($d->diffInDays($dob) / 30.44, 1),
                    'age_years'   => round($d->diffInDays($dob) / 365.25, 2),
                    'weight'      => is_numeric($e->weight) ? (float) $e->weight : null,
                    'height'      => is_numeric($e->height) ? (float) $e->height : null,
                    'bmi'         => is_numeric($e->bmi)    ? (float) $e->bmi    : null,
                ];
            });

        $weightPoints = $points->filter(fn ($p) => $p->weight !== null)->values();
        $heightPoints = $points->filter(fn ($p) => $p->height !== null)->values();
        $bmiPoints    = $points->filter(fn ($p) => $p->bmi !== null)->values();

        $isPaediatric = $dob->diffInYears(today()) < 18;

        return view('patients.growth', compact('patient', 'weightPoints', 'heightPoints', 'bmiPoints', 'isPaediatric'));
    }

    public function duplicates(Request $request)
    {
        // Group A: same trimmed phone (>=2 records, non-empty)
        $phoneGroups = \DB::table('patients')
            ->whereNotNull('phone')->where('phone', '!=', '')
            ->selectRaw('TRIM(phone) as key_phone, COUNT(*) as n')
            ->groupBy('key_phone')->having('n', '>', 1)
            ->orderByDesc('n')->limit(50)->get();

        $byPhone = collect();
        foreach ($phoneGroups as $g) {
            $rows = Patient::where('phone', $g->key_phone)
                ->withCount(['encounters','bills','labOrders','admissions'])
                ->orderBy('id')->get();
            if ($rows->count() >= 2) $byPhone->push(['key' => 'phone: '.$g->key_phone, 'rows' => $rows]);
        }

        // Group B: same first_name + last_name + DOB (likely true duplicates)
        $nameGroups = \DB::table('patients')
            ->whereNotNull('first_name')->whereNotNull('last_name')->whereNotNull('date_of_birth')
            ->selectRaw('LOWER(TRIM(first_name)) as fn, LOWER(TRIM(last_name)) as ln, date_of_birth as dob, COUNT(*) as n')
            ->groupBy('fn','ln','dob')->having('n', '>', 1)
            ->orderByDesc('n')->limit(50)->get();

        $byName = collect();
        foreach ($nameGroups as $g) {
            $rows = Patient::whereRaw('LOWER(TRIM(first_name)) = ?', [$g->fn])
                ->whereRaw('LOWER(TRIM(last_name)) = ?', [$g->ln])
                ->whereDate('date_of_birth', $g->dob)
                ->withCount(['encounters','bills','labOrders','admissions'])
                ->orderBy('id')->get();
            if ($rows->count() >= 2) $byName->push(['key' => 'name+DOB: '.ucfirst($g->fn).' '.ucfirst($g->ln).' / '.\Carbon\Carbon::parse($g->dob)->format('d M Y'), 'rows' => $rows]);
        }

        $groups = $byPhone->concat($byName);
        return view('patients.duplicates', compact('groups'));
    }

    public function merge(Patient $patient, Request $request)
    {
        $request->validate([
            'merge_into' => 'required|integer|exists:patients,id|different:patient_id',
        ]);
        $into = Patient::findOrFail($request->merge_into);
        if ($into->id === $patient->id) {
            return back()->with('error', 'Cannot merge a patient into itself.');
        }

        // Tables that reference patients.patient_id — explicit list to avoid
        // runaway updates against unknown foreign keys.
        $referencingTables = [
            'admissions','appointments','bills','certificates','clinical_notes',
            'diet_prescriptions','ecg_records','echo_records','encounter_notes',
            'encounter_reports','encounters','insurance_claims','lab_orders',
            'opd_tokens','operations','patient_allergies','patient_insurance',
            'patient_notes','patient_vitals','prescriptions','radiology_orders',
            'referral_payments','triage_records',
        ];

        \DB::transaction(function () use ($patient, $into, $referencingTables) {
            foreach ($referencingTables as $tbl) {
                if (!\Schema::hasTable($tbl)) continue;
                if (!\Schema::hasColumn($tbl, 'patient_id')) continue;
                \DB::table($tbl)->where('patient_id', $patient->id)->update(['patient_id' => $into->id]);
            }
            // Delete the duplicate patient row
            $patient->delete();
            if (class_exists(\App\Models\ActivityLog::class)) {
                try { \App\Models\ActivityLog::log('Patient Merge', "Merged patient #{$patient->id} ({$patient->jhc_id}) into #{$into->id} ({$into->jhc_id})"); } catch(\Throwable $e) {}
            }
        });

        return redirect()->route('tools.duplicates')
            ->with('success', "Merged into {$into->full_name} ({$into->jhc_id}). Other records re-pointed.");
    }
}
