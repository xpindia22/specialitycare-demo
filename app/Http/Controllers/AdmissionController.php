<?php
namespace App\Http\Controllers;

use App\Models\Admission;
use App\Models\ActivityLog;
use App\Models\Bed;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\ReferralSource;
use App\Models\ReferralPayment;
use App\Models\Ward;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class AdmissionController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->get('status', 'admitted');
        $q = $request->get('q');

        $admissions = Admission::with(['patient', 'ward', 'bed', 'admittingDoctor'])
            ->when($status !== 'all', fn($query) => $query->where('status', $status))
            ->when($q, function ($query) use ($q) {
                $query->where('ipd_no', 'like', "%$q%")
                    ->orWhereHas('patient', fn($p) => $p->where('first_name', 'like', "%$q%")
                        ->orWhere('last_name', 'like', "%$q%")
                        ->orWhere('jhc_id', 'like', "%$q%"));
            })
            ->orderByDesc('admission_date')
            ->paginate(25);

        if ($request->get('format') === 'json') {
            return response()->json($admissions->map(fn($a) => [
                'id'           => $a->id,
                'ipd_no'       => $a->ipd_no,
                'patient_id'   => $a->patient_id,
                'patient_name' => $a->patient->full_name,
                'ward'         => $a->ward?->name,
                'status'       => $a->status,
            ]));
        }

        $stats = [
            'admitted'    => Admission::where('status', 'admitted')->count(),
            'discharged'  => Admission::where('status', 'discharged')->whereDate('discharge_date', today())->count(),
            'available'   => Bed::where('is_active', true)->where('status', 'available')->count(),
        ];

        return view('admissions.index', compact('admissions', 'stats', 'status', 'q'));
    }

    public function create(Request $request)
    {
        $patient  = $request->patient_id ? Patient::findOrFail($request->patient_id) : null;
        $patients = Patient::where('is_active', true)->orderBy('first_name')->get();
        $doctors  = Doctor::where('is_active', true)->orderBy('first_name')->get();
        $wards    = Ward::where('is_active', true)->with('beds')->get();
        $referralSources = ReferralSource::where('is_active', true)->orderBy('name')->get();
        $defaultDoctorId = $request->doctor_id;

        return view('admissions.create', compact('patient', 'patients', 'doctors', 'wards', 'referralSources', 'defaultDoctorId'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'patient_id'           => 'required|exists:patients,id',
            'bed_id'               => 'required|exists:beds,id',
            'ward_id'              => 'required|exists:wards,id',
            'admitting_doctor_id'  => 'required|exists:doctors,id',
            'admission_type'       => 'required|in:emergency,elective,referral',
            'admission_date'       => 'required|date',
            'provisional_diagnosis'=> 'nullable|string|max:500',
            'referred_by'          => 'nullable|string|max:200',
            'referral_source_id'   => 'nullable|exists:referral_sources,id',
            'notes'                => 'nullable|string',
            'is_mlc'               => 'nullable|boolean',
            'mlc_no'               => 'nullable|string|max:100',
            'mlc_police_station'   => 'nullable|string|max:200',
            'mlc_notes'            => 'nullable|string',
        ]);
        $data['is_mlc'] = $request->boolean('is_mlc');

        $bed = Bed::findOrFail($data['bed_id']);
        if ($bed->status !== 'available') {
            return back()->withErrors(['bed_id' => 'Selected bed is not available.'])->withInput();
        }

        $existing = Admission::where('patient_id', $data['patient_id'])
            ->where('status', 'admitted')
            ->with('ward')
            ->first();
        if ($existing) {
            $ward = $existing->ward?->name ? ' — ' . $existing->ward->name : '';
            return redirect()->route('admissions.show', $existing->id)
                ->with('error', "Patient is already admitted ({$existing->ipd_no}{$ward}). Discharge them here before creating a new admission.");
        }

        try {
        DB::transaction(function () use ($data) {
            // Re-acquire bed with exclusive lock to prevent race condition
            $bed = Bed::lockForUpdate()->findOrFail($data['bed_id']);
            if ($bed->status !== 'available') {
                throw new \RuntimeException('bed_unavailable');
            }

            $data['ipd_no']     = Admission::generateIpdNo();
            $data['status']     = 'admitted';
            $data['created_by'] = auth()->id();

            $admission = Admission::create($data);

            $bed->update(['status' => 'occupied']);

            // Auto-create pending referral incentive if a referral source is selected
            if (!empty($data['referral_source_id'])) {
                $source = ReferralSource::find($data['referral_source_id']);
                if ($source) {
                    $amount = $source->rate_type === 'fixed'
                        ? $source->rate_value
                        : 0; // percentage type is calculated later when bill is settled
                    ReferralPayment::create([
                        'referral_source_id' => $source->id,
                        'patient_id'         => $admission->patient_id,
                        'admission_id'       => $admission->id,
                        'calculated_amount'  => $amount,
                        'paid_amount'        => 0,
                        'status'             => 'pending',
                        'created_by'         => auth()->id(),
                        'notes'              => "Auto-created on IPD admission {$admission->ipd_no}",
                    ]);
                }
            }

            ActivityLog::log(
                'Admission Created',
                "Patient #{$admission->patient_id} admitted as {$admission->ipd_no} in bed {$bed->bed_number}, ward #{$admission->ward_id}"
            );
        });
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'bed_unavailable') {
                return back()->withErrors(['bed_id' => 'This bed was just taken by another admission. Please select a different bed.'])->withInput();
            }
            throw $e;
        }

        return redirect()->route('admissions.index')->with('success', 'Patient admitted successfully.');
    }

    public function show(Admission $admission)
    {
        $admission->load(['patient', 'bed', 'ward', 'admittingDoctor', 'createdBy', 'deposits.receivedBy', 'referralSource']);
        $labOrders = \App\Models\LabOrder::with(['items.test', 'items.panel', 'doctor', 'createdBy'])
            ->where('admission_id', $admission->id)
            ->orderByDesc('ordered_date')
            ->orderByDesc('id')
            ->get();
        $deposits = $admission->deposits;
        $radiologyOrders = \App\Models\RadiologyOrder::with(['orderedBy'])
            ->where('admission_id', $admission->id)
            ->orderByDesc('created_at')
            ->get();
        $operations = \App\Models\Operation::with(['surgeon'])
            ->where('admission_id', $admission->id)
            ->orderByDesc('operation_date')
            ->get();
        $stockMedicines = \App\Models\Medicine::where('is_active', true)
            ->whereHas('stocks', fn($q) => $q->where('quantity', '>', 0))
            ->orderBy('name')
            ->get(['id', 'name', 'dosage_form', 'strength', 'generic_name']);

        $ioToday = $admission->ioEntries()
            ->whereDate('recorded_at', today())
            ->with('recordedBy')
            ->orderBy('recorded_at')
            ->get();
        $ioTotalIn  = $ioToday->where('io_type', 'input')->sum('amount_ml');
        $ioTotalOut = $ioToday->where('io_type', 'output')->sum('amount_ml');

        $nursingToday = \App\Models\NursingShift::where('admission_id', $admission->id)
            ->whereDate('shift_date', today())
            ->with('writtenBy')
            ->orderByRaw("FIELD(shift,'morning','evening','night')")
            ->get()
            ->keyBy('shift');

        $patientAllergies = array_values(array_filter(array_map(
            'trim', preg_split('/[\n,;]+/', $admission->patient->allergies ?? '')
        )));

        $consents = $admission->consents()->with('obtainedBy')->get()->keyBy('consent_type');

        $insuranceClaims = $admission->insuranceClaims()->with('insurer')->orderByDesc('created_at')->get();

        $admissionPackages = $admission->packages()->with('package')->orderByDesc('created_at')->get();
        $availablePackages = \App\Models\BillingPackage::where('is_active', true)->orderBy('name')->get();

        // Nursing Tasks
        $nursingTasks = $admission->nursingTasks()->with('createdBy')->get();

        // Diet Charts
        $dietCharts = \App\Models\DietChart::where('admission_id', $admission->id)
            ->with('createdBy')
            ->orderByDesc('date')
            ->orderByRaw("FIELD(meal_time,'morning','afternoon','evening','night')")
            ->get();

        $handoverNotes = $admission->handoverNotes()->with('doctor')->get();
        $preauths      = $admission->preauths()->get();

        return view('admissions.show', compact('admission', 'labOrders', 'deposits', 'radiologyOrders', 'operations', 'stockMedicines', 'ioToday', 'ioTotalIn', 'ioTotalOut', 'nursingToday', 'patientAllergies', 'consents', 'insuranceClaims', 'admissionPackages', 'availablePackages', 'nursingTasks', 'dietCharts', 'handoverNotes', 'preauths'));
    }

    public function discharge(Admission $admission)
    {
        if ($admission->status !== 'admitted') {
            return redirect()->route('admissions.show', $admission)->with('error', 'Patient is not currently admitted.');
        }
        return view('admissions.discharge', compact('admission'));
    }

    public function draftDischargeSummary(Admission $admission)
    {
        $admission->load(['patient','admittingDoctor','ward','bed',
            'charges','prescriptions.medicine','notes.writtenBy']);

        $progressNotes = \App\Models\IpdNote::where('admission_id', $admission->id)
            ->where('note_type','progress')
            ->orderByDesc('noted_at')
            ->limit(3)
            ->pluck('note')
            ->implode("\n\n");

        $medicines = $admission->prescriptions
            ->where('is_active', true)
            ->map(fn($rx) => "• {$rx->drug_name}" .
                ($rx->dosage    ? " {$rx->dosage}"    : '') .
                ($rx->frequency ? " {$rx->frequency}"  : '') .
                ($rx->duration  ? " for {$rx->duration}" : ''))
            ->implode("\n");

        // Patient allergies (latest active)
        $allergies = \App\Models\PatientAllergy::where('patient_id', $admission->patient_id)
            ->orderByDesc('id')
            ->get();
        $allergyText = $allergies->isEmpty()
            ? 'NKDA'
            : $allergies->map(fn($a) => $a->allergen . ($a->severity && $a->severity !== 'unknown' ? " ({$a->severity})" : ''))->implode(', ');

        // Vitals at admission — first encounter recorded during/around admission window with vitals
        $admStart = $admission->admission_date;
        $admEnd   = $admission->discharge_date ?: now();
        $admVitalsEnc = \App\Models\Encounter::where('patient_id', $admission->patient_id)
            ->whereDate('encounter_date', '>=', $admStart->copy()->subDay())
            ->whereDate('encounter_date', '<=', $admEnd)
            ->where(function ($q) {
                $q->whereNotNull('bp_systolic')->orWhereNotNull('pulse')
                  ->orWhereNotNull('temperature')->orWhereNotNull('spo2');
            })
            ->orderBy('encounter_date')
            ->first();
        $vitalsLine = '';
        if ($admVitalsEnc) {
            $bits = [];
            if ($admVitalsEnc->bp_systolic && $admVitalsEnc->bp_diastolic) $bits[] = "BP {$admVitalsEnc->bp_systolic}/{$admVitalsEnc->bp_diastolic} mmHg";
            if ($admVitalsEnc->pulse)        $bits[] = "HR {$admVitalsEnc->pulse}/min";
            if ($admVitalsEnc->temperature)  $bits[] = "Temp {$admVitalsEnc->temperature}°C";
            if ($admVitalsEnc->spo2)         $bits[] = "SpO₂ {$admVitalsEnc->spo2}%";
            $vitalsLine = implode(', ', $bits);
        }

        // Key lab results during the stay (top 8 by recency)
        $labOrders = \App\Models\LabOrder::with(['items.test'])
            ->where('admission_id', $admission->id)
            ->orderBy('ordered_date')
            ->get();
        $labLines = [];
        foreach ($labOrders as $lo) {
            foreach ($lo->items as $it) {
                if (!$it->result_value) continue;
                $name = $it->test?->name ?? '';
                if (!$name) continue;
                $unit = $it->test?->unit ? ' ' . $it->test->unit : '';
                $flag = in_array($it->abnormal_flag, ['H','L','HH','LL','ABN','POS'], true) ? ' (ABN)' : '';
                $labLines[] = sprintf('• %s: %s%s%s', $name, $it->result_value, $unit, $flag);
                if (count($labLines) >= 8) break 2;
            }
        }

        $summary  = "DISCHARGE SUMMARY\n";
        $summary .= str_repeat('=', 50) . "\n\n";
        $age = preg_replace('/\s*yrs?\s*$/i', '', (string) $admission->patient->age);
        $summary .= "Patient: {$admission->patient->full_name}, " . ($admission->patient->gender ?? '?') . " / Age: {$age} yrs\n";
        $summary .= "JHC ID: {$admission->patient->jhc_id} | IPD No: {$admission->ipd_no}\n";
        $summary .= "Admitted: {$admission->admission_date->format('d M Y H:i')} | Length of stay: {$admission->days_admitted} day(s)\n";
        $summary .= "Admitting Doctor: {$admission->admittingDoctor->full_name}\n";
        $summary .= "Ward / Bed: {$admission->ward->name}, Bed {$admission->bed->bed_number}\n";
        $summary .= "Allergies: {$allergyText}\n\n";

        if ($admission->provisional_diagnosis) {
            $summary .= "DIAGNOSIS:\n{$admission->provisional_diagnosis}\n\n";
        }

        if ($vitalsLine) {
            $summary .= "VITALS AT ADMISSION:\n{$vitalsLine}\n\n";
        }

        $summary .= "HOSPITAL COURSE:\n";
        $summary .= $progressNotes ?: "Patient was managed under the care of {$admission->admittingDoctor->full_name}.";
        $summary .= "\n\n";

        if (!empty($labLines)) {
            $summary .= "KEY INVESTIGATIONS:\n" . implode("\n", $labLines) . "\n\n";
        }

        if ($medicines) {
            $summary .= "DISCHARGE MEDICATIONS:\n{$medicines}\n\n";
        }

        $summary .= "DISCHARGE ADVICE:\n• Take medicines as prescribed.\n• Follow diet & lifestyle advice.\n• Return immediately if fever, severe pain, breathlessness, or bleeding.\n\n";
        $summary .= "FOLLOW-UP:\nReview in OPD after 1 week — or earlier if symptoms worsen.\n";

        return response()->json(['summary' => $summary]);
    }

    public function processDischarge(Request $request, Admission $admission)
    {
        if ($admission->status !== 'admitted') {
            return redirect()->route('admissions.show', $admission)->with('error', 'Patient is not currently admitted.');
        }

        $data = $request->validate([
            'discharge_date'      => 'required|date|after_or_equal:' . $admission->admission_date->toDateString(),
            'discharge_condition' => 'required|in:improved,recovered,lama,expired,transferred',
            'discharge_notes'     => 'nullable|string',
            'discharge_summary'   => 'nullable|string',
        ]);

        DB::transaction(function () use ($data, $admission) {
            $data['status'] = 'discharged';
            $admission->update($data);

            $admission->bed->update(['status' => 'available']);

            ActivityLog::log(
                'Patient Discharged',
                "IPD {$admission->ipd_no}: patient discharged ({$admission->discharge_condition}), bed {$admission->bed->bed_number} freed"
            );
        });

        return redirect()->route('admissions.show', $admission)->with('success', 'Patient discharged successfully.');
    }

    public function transfer(Request $request, Admission $admission)
    {
        if ($admission->status !== 'admitted') {
            return back()->with('error', 'Patient is not currently admitted.');
        }

        $data = $request->validate([
            'new_ward_id' => 'required|exists:wards,id',
            'new_bed_id'  => 'required|exists:beds,id',
            'reason'      => 'nullable|string|max:300',
        ]);

        $newBed = Bed::findOrFail($data['new_bed_id']);
        if ($newBed->status !== 'available') {
            return back()->withErrors(['new_bed_id' => 'Selected bed is not available.'])->withInput();
        }

        DB::transaction(function () use ($data, $admission, $newBed) {
            $oldBed  = $admission->bed;
            $oldWard = $admission->ward;

            $admission->update([
                'ward_id' => $data['new_ward_id'],
                'bed_id'  => $data['new_bed_id'],
            ]);

            $oldBed->update(['status' => 'available']);
            $newBed->update(['status' => 'occupied']);

            $note = "Ward transfer: {$oldWard->name} Bed {$oldBed->bed_number} → {$newBed->ward->name} Bed {$newBed->bed_number}";
            if ($data['reason']) $note .= ". Reason: {$data['reason']}";

            $admission->notes()->create([
                'type'       => 'nursing',
                'note'       => $note,
                'noted_at'   => now(),
                'written_by' => auth()->id(),
            ]);

            ActivityLog::log('updated', "IPD {$admission->ipd_no}: transferred to ward {$data['new_ward_id']} bed {$newBed->bed_number}");
        });

        return redirect()->route('admissions.show', $admission)
            ->with('success', 'Patient transferred to new ward/bed successfully.');
    }

    public function summary(Admission $admission)
    {
        $admission->load(['patient', 'bed', 'ward', 'admittingDoctor',
            'prescriptions' => fn($q) => $q->with('prescribedBy'),
            'charges',
        ]);

        $ipdNotes = $admission->notes()->where('note_type', 'doctor_round')
            ->orderBy('noted_at')->with('writtenBy')->get();

        $encEnd = $admission->discharge_date ?? now();
        $encounters = \App\Models\Encounter::where('patient_id', $admission->patient_id)
            ->whereDate('encounter_date', '>=', $admission->admission_date->toDateString())
            ->whereDate('encounter_date', '<=', $encEnd->toDateString())
            ->orderBy('encounter_date')->orderBy('encounter_time')
            ->get();

        return view('admissions.summary', compact('admission', 'ipdNotes', 'encounters'));
    }

    public function slip(Admission $admission)
    {
        $admission->load(['patient', 'bed', 'ward', 'admittingDoctor', 'createdBy']);
        return view('admissions.slip', compact('admission'));
    }

    public function summaryPdf(Admission $admission)
    {
        $admission->load(['patient', 'bed', 'ward', 'admittingDoctor', 'prescriptions', 'charges']);

        $ipdNotes = $admission->notes()->where('note_type', 'doctor_round')
            ->orderBy('noted_at')->with('writtenBy')->get();

        $encEnd = $admission->discharge_date ?? now();
        $encounters = \App\Models\Encounter::where('patient_id', $admission->patient_id)
            ->whereDate('encounter_date', '>=', $admission->admission_date->toDateString())
            ->whereDate('encounter_date', '<=', $encEnd->toDateString())
            ->orderBy('encounter_date')->orderBy('encounter_time')
            ->get();

        $pdf = Pdf::loadView('admissions.discharge-summary-print', compact('admission', 'ipdNotes', 'encounters'));
        // ipd_no contains '/' which Symfony rejects in Content-Disposition filename.
        $safe = str_replace(['/', '\\'], '-', (string) $admission->ipd_no);
        return $pdf->download("discharge-{$safe}.pdf");
    }

    public function dischargeInstructions(Admission $admission)
    {
        $admission->load([
            'patient', 'ward', 'bed', 'admittingDoctor',
            'prescriptions' => fn($q) => $q->where('status', 'active')->with('prescribedBy'),
        ]);
        return view('admissions.discharge-instructions', compact('admission'));
    }

    public function referralLetter(Admission $admission)
    {
        $admission->load(['patient', 'ward', 'bed', 'admittingDoctor']);
        return view('admissions.referral-letter', compact('admission'));
    }

    public function getBeds(Request $request)
    {
        $beds = Bed::where('ward_id', $request->ward_id)
            ->where('is_active', true)
            ->orderBy('bed_number')
            ->get(['id', 'bed_number', 'type', 'status']);

        return response()->json($beds);
    }
}
