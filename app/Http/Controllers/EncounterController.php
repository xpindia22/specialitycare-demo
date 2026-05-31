<?php
// FILE: app/Http/Controllers/EncounterController.php
namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Encounter;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Appointment;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\Medicine;
use App\Models\Diagnosis;
use App\Models\ClinicalNote;
use App\Models\LabOrder;
use App\Models\LabTest;
use App\Models\LabPanel;
use App\Models\EcgRecord;
use App\Models\EchoRecord;
use App\Models\EncounterNote;
use App\Models\EncounterReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EncounterController extends Controller
{
    public function index(Request $request)
    {
        $q        = $request->get('q');
        $doctorId = $request->get('doctor_id');
        $status   = $request->get('status', '');
        $date     = $request->get('date', '');

        $encounters = Encounter::with(['patient', 'doctor'])
            ->when($q, fn($query) => $query->where('encounter_no', 'like', "%$q%")
                ->orWhereHas('patient', fn($p) => $p->where('first_name', 'like', "%$q%")
                    ->orWhere('last_name', 'like', "%$q%")
                    ->orWhere('jhc_id', 'like', "%$q%")))
            ->when($doctorId, fn($query) => $query->where('doctor_id', $doctorId))
            ->when($status,   fn($query) => $query->where('status', $status))
            ->when($date,     fn($query) => $query->whereDate('encounter_date', $date))
            ->orderByDesc('encounter_date')
            ->orderByDesc('id')
            ->paginate(25)->withQueryString();

        $doctors = Doctor::where('is_active', true)->orderBy('first_name')->get();

        return view('encounters.index', compact('encounters', 'doctors', 'q', 'doctorId', 'status', 'date'));
    }

    public function show(Encounter $encounter)
    {
        $encounter->load([
            'patient', 'doctor', 'appointment', 'bill',
            'diagnoses', 'prescriptions.items.medicine',
            'clinicalNotes', 'encounterNotes', 'labOrders.items',
            'reports.uploadedBy',
        ]);
        $medicines = Medicine::where('is_active', true)->get()->map(function ($m) {
            $stock = $m->total_stock;

            return [
                'id'              => $m->id,
                'name'            => $m->name,
                'generic_name'    => $m->resolved_generic,
                'strength'        => $m->strength,
                'unit'            => $m->unit ?: 'Unit',
                'dosage_form'     => $m->dosage_form,
                'stock'           => $stock,
                'quantity'        => $stock,
                'in_stock'        => $stock > 0,
                'stock_status'    => $m->stock_status,
                'side_effects'    => $m->side_effects,
                'contraindications'  => $m->contraindications,
                'drug_interactions'  => $m->drug_interactions,
            ];
        })->values();
        $labTests = LabTest::where('is_active', true)->with('category')->get();
        $labPanels = LabPanel::where('is_active', true)->with('tests')->get();
        $patientAllergyRecords = $encounter->patient->patientAllergies()->orderBy('allergen')->get();
        $patientAllergies = $patientAllergyRecords->pluck('allergen')->map(fn($a) => strtolower(trim($a)))->filter()->values()->toArray();
        $commonAllergens = \App\Models\Allergen::flatList();
        $today = now()->startOfDay();
        $activePrescribedMeds = [];
        foreach ($encounter->prescriptions as $rx) {
            foreach ($rx->items as $item) {
                $endDate = $rx->prescribed_date->copy()->addDays($item->duration_days);
                if ($endDate->gte($today)) {
                    $activePrescribedMeds[] = [
                        'name'          => $item->medicine->name,
                        'generic_name'  => $item->generic_name ?: $item->medicine?->resolved_generic,
                        'days_left'     => (int) $today->diffInDays($endDate),
                        'prescribed_on' => $rx->prescribed_date->format('d M'),
                    ];
                }
            }
        }
        $patientEcgRecords  = EcgRecord::with('doctor')->where('patient_id', $encounter->patient_id)->latest()->get();
        $patientEchoRecords = EchoRecord::with('doctor')->where('patient_id', $encounter->patient_id)->latest()->get();

        // Quick-bill data
        $services = \App\Models\ServiceCharge::where('is_active', true)
            ->orderBy('category')->orderBy('name')
            ->get(['id','name','category','price']);
        $quickBillItems = [];
        if (!$encounter->bill) {
            $visitType  = $encounter->visit_type ?? 'New Visit';
            $svcName    = str_contains(strtolower($visitType), 'follow') ? 'Follow-up Consultation' : 'General Consultation';
            $consultSvc = \App\Models\ServiceCharge::where('name', $svcName)->first()
                       ?? \App\Models\ServiceCharge::where('category', 'Consultation')->first();
            if ($consultSvc) {
                $quickBillItems[] = ['name' => $consultSvc->name, 'type' => 'Consultation', 'price' => (float)$consultSvc->price, 'qty' => 1];
            }
            foreach ($encounter->labOrders as $lo) {
                foreach ($lo->items as $item) {
                    $name  = $item->test?->name ?? $item->panel?->name ?? 'Lab Test';
                    $price = (float)($item->price ?? 0);
                    $quickBillItems[] = ['name' => $name, 'type' => 'Investigation', 'price' => $price, 'qty' => 1];
                }
            }
        }

        $clinicalChips = \App\Models\ClinicalChip::groupedForJs();

        $rxOptions = \App\Models\RxOption::grouped();

        return view('encounters.show', compact(
            'encounter', 'medicines', 'labTests', 'labPanels', 'patientAllergies', 'activePrescribedMeds',
            'patientAllergyRecords', 'commonAllergens', 'patientEcgRecords', 'patientEchoRecords',
            'services', 'quickBillItems', 'clinicalChips', 'rxOptions'
        ));
    }

    /** Persist a new prescription dropdown option (doctor "➕ Add new…"). */
    public function storeRxOption(Request $request)
    {
        $data = $request->validate([
            'field' => ['required', \Illuminate\Validation\Rule::in(\App\Models\RxOption::FIELDS)],
            'value' => 'required|string|max:191',
        ]);
        $value = trim($data['value']);
        abort_if($value === '', 422, 'Empty value.');

        $opt = \App\Models\RxOption::firstOrCreate(
            ['field' => $data['field'], 'value' => $value],
            ['label' => $value, 'sort' => 999]
        );
        return response()->json(['value' => $opt->value, 'label' => $opt->label ?: $opt->value]);
    }

    public function updateVitals(Request $request, Encounter $encounter)
    {
        $validated = $request->validate([
            'weight'           => 'nullable|numeric|min:0.1|max:500',
            'height'           => 'nullable|numeric|min:10|max:300',
            'bp_systolic'      => 'nullable|string',
            'bp_diastolic'     => 'nullable|string',
            'pulse'            => 'nullable|numeric|min:0|max:300',
            'temperature'      => 'nullable|numeric|min:30|max:115',
            'spo2'             => 'nullable|numeric|min:0|max:100',
            'respiratory_rate' => 'nullable|numeric|min:0|max:100',
            'blood_glucose'    => 'nullable|string',
        ]);

        // Auto-calculate BMI
        if (!empty($validated['weight']) && !empty($validated['height'])) {
            $heightM = $validated['height'] / 100;
            $validated['bmi'] = round($validated['weight'] / ($heightM * $heightM), 2);
        }

        $encounter->update($validated);
        $encounter->load('patient');
        ActivityLog::log('updated', "Updated vitals for patient {$encounter->patient->first_name} {$encounter->patient->last_name} (Encounter #{$encounter->id})");
        return response()->json(['success' => true, 'bmi' => $encounter->bmi]);
    }

    public function updateClinical(Request $request, Encounter $encounter)
    {
        $validated = $request->validate([
            'chief_complaint'           => 'nullable|string',
            'history_of_present_illness'=> 'nullable|string',
            'past_medical_history'      => 'nullable|string',
            'family_history'            => 'nullable|string',
            'social_history'            => 'nullable|string',
            'allergies'                 => 'nullable|string',
            'review_of_systems'         => 'nullable|string',
            'examination_findings'      => 'nullable|string',
            'impression'                => 'nullable|string',
            'plan'                      => 'nullable|string',
            'follow_up_instructions'    => 'nullable|string',
            'follow_up_days'            => 'nullable|integer|min:0',
            'follow_up_date'            => 'nullable|date',
            'is_mlc'                    => 'nullable|boolean',
            'mlc_no'                    => 'nullable|string|max:100',
        ]);
        $validated['is_mlc'] = (bool)($request->input('is_mlc', 0));

        if (!empty($validated['follow_up_days']) && empty($validated['follow_up_date'])) {
            $validated['follow_up_date'] = now()->addDays((int) $validated['follow_up_days'])->toDateString();
        }

        $encounter->update($validated);
        $encounter->load('patient');
        ActivityLog::log('updated', "Updated clinical notes for patient {$encounter->patient->first_name} {$encounter->patient->last_name} (Encounter #{$encounter->id})");
        return response()->json(['success' => true]);
    }

    public function saveDiagnoses(Request $request, Encounter $encounter)
    {
        $request->validate([
            'diagnoses'             => 'required|array',
            'diagnoses.*.name'      => 'required|string',
            'diagnoses.*.type'      => 'required|in:Primary,Secondary,Differential',
            'diagnoses.*.icd_code'  => 'nullable|string',
            'diagnoses.*.status'    => 'required|in:Active,Resolved,Chronic',
        ]);

        $encounter->diagnoses()->delete();
        foreach ($request->diagnoses as $diag) {
            $encounter->diagnoses()->create([
                'icd_code'       => $diag['icd_code'] ?? null,
                'diagnosis_name' => $diag['name'],
                'type'           => $diag['type'],
                'status'         => $diag['status'] ?? 'Active',
                'notes'          => $diag['notes'] ?? null,
            ]);
        }

        return response()->json(['success' => true, 'count' => count($request->diagnoses)]);
    }

    public function savePrescription(Request $request, Encounter $encounter)
    {
        $request->validate([
            'medicines'                  => 'required|array|min:1',
            'medicines.*.medicine_id'    => 'required|exists:medicines,id',
            'medicines.*.dose'           => 'required|string',
            'medicines.*.frequency'      => 'required|string',
            'medicines.*.duration_days'  => 'required|integer|min:1',
            'medicines.*.route'          => 'required|string',
            'medicines.*.instructions'   => 'nullable|string',
            'notes'                      => 'nullable|string',
            'advice'                     => 'nullable|string',
        ]);

        $prescription = Prescription::create([
            'prescription_no' => Prescription::generateNo(),
            'encounter_id'    => $encounter->id,
            'patient_id'      => $encounter->patient_id,
            'doctor_id'       => $encounter->doctor_id,
            'prescribed_date' => today(),
            'notes'           => $request->notes,
            'advice'          => $request->advice,
            'status'          => 'Pending',
        ]);

        $this->createRxItems($prescription, $request->medicines);

        return response()->json([
            'success'         => true,
            'prescription_id' => $prescription->id,
            'prescription_no' => $prescription->prescription_no,
        ]);
    }

    /** Create prescription_items from a medicines[] payload (qty auto-computed). */
    private function createRxItems(Prescription $prescription, array $medicines): void
    {
        $freqMap = ['OD' => 1, 'BD' => 2, 'TDS' => 3, 'QID' => 4, 'SOS' => 1, 'HS' => 1, 'STAT' => 1];
        foreach ($medicines as $med) {
            $medicine = Medicine::find($med['medicine_id']);
            if (!$medicine) continue;
            $freq = $freqMap[strtoupper($med['frequency'])] ?? 1;
            $prescription->items()->create([
                'medicine_id'   => $med['medicine_id'],
                'generic_name'  => $medicine->resolved_generic,
                'dose'          => $med['dose'],
                'frequency'     => $med['frequency'],
                'route'         => $med['route'] ?? 'Oral',
                'duration_days' => $med['duration_days'],
                'quantity'      => $freq * (int) $med['duration_days'],
                'instructions'  => $med['instructions'] ?? null,
                'is_in_stock'   => $medicine->isInStock(),
                'is_dispensed'  => false,
            ]);
        }
    }

    public function updatePrescription(Request $request, Encounter $encounter, Prescription $prescription)
    {
        abort_if($prescription->encounter_id !== $encounter->id, 404);
        abort_if($prescription->status === 'Dispensed', 403, 'Cannot edit a dispensed prescription.');
        $data = $request->validate([
            'notes'  => 'nullable|string|max:1000',
            'advice' => 'nullable|string|max:1000',
            'medicines'                  => 'sometimes|array|min:1',
            'medicines.*.medicine_id'    => 'required_with:medicines|exists:medicines,id',
            'medicines.*.dose'           => 'required_with:medicines|string',
            'medicines.*.frequency'      => 'required_with:medicines|string',
            'medicines.*.duration_days'  => 'required_with:medicines|integer|min:1',
            'medicines.*.route'          => 'required_with:medicines|string',
            'medicines.*.instructions'   => 'nullable|string',
        ]);
        $prescription->update(['notes' => $data['notes'] ?? null, 'advice' => $data['advice'] ?? null]);

        // Full edit: replace the whole medicine set when a medicines[] payload is sent.
        if (!empty($data['medicines'])) {
            $prescription->items()->delete();
            $this->createRxItems($prescription, $data['medicines']);
        }
        ActivityLog::log('Prescription Edited', "Edited {$prescription->prescription_no}");
        return response()->json(['success' => true]);
    }

    /** Permanently merge one prescription's items into another (same encounter). */
    public function mergePrescriptions(Encounter $encounter, Prescription $prescription, Prescription $target)
    {
        abort_if($prescription->encounter_id !== $encounter->id || $target->encounter_id !== $encounter->id, 404);
        abort_if($prescription->id === $target->id, 422, 'Cannot merge a prescription into itself.');
        abort_if($prescription->status === 'Dispensed' || $target->status === 'Dispensed', 403, 'Cannot merge a dispensed prescription.');

        $prescription->items()->update(['prescription_id' => $target->id]);

        // Preserve any notes/advice from the source by appending to the target.
        $notes  = trim(implode("\n", array_filter([$target->notes, $prescription->notes])));
        $advice = trim(implode("\n", array_filter([$target->advice, $prescription->advice])));
        $target->update(['notes' => $notes ?: null, 'advice' => $advice ?: null]);

        $no = $prescription->prescription_no;
        $prescription->delete();
        ActivityLog::log('Prescription Merged', "Merged {$no} into {$target->prescription_no} (encounter {$encounter->encounter_no})");
        return response()->json(['success' => true, 'target_id' => $target->id]);
    }

    public function deletePrescription(Encounter $encounter, Prescription $prescription)
    {
        abort_if($prescription->encounter_id !== $encounter->id, 404);
        abort_if($prescription->status === 'Dispensed', 403, 'Cannot delete a dispensed prescription.');
        $no = $prescription->prescription_no;
        $prescription->items()->delete();
        $prescription->delete();
        ActivityLog::log('Prescription Deleted', "Deleted {$no} from encounter {$encounter->encounter_no}");
        return response()->json(['success' => true]);
    }

    public function updatePrescriptionItem(Request $request, Encounter $encounter, PrescriptionItem $item)
    {
        abort_if($item->prescription->encounter_id !== $encounter->id, 404);
        $data = $request->validate([
            'dose'          => 'required|string|max:100',
            'frequency'     => 'required|string|max:20',
            'route'         => 'required|string|max:50',
            'duration_days' => 'required|integer|min:1',
            'instructions'  => 'nullable|string|max:300',
        ]);
        $freqMap = ['OD'=>1,'BD'=>2,'TDS'=>3,'QID'=>4,'SOS'=>1,'HS'=>1,'STAT'=>1];
        $freq = $freqMap[strtoupper($data['frequency'])] ?? 1;
        $data['quantity'] = $freq * (int)$data['duration_days'];
        $item->update($data);
        ActivityLog::log('Prescription Item Edited', "Updated {$item->medicine->name} in encounter {$encounter->encounter_no}");
        return response()->json(['success' => true]);
    }

    public function deletePrescriptionItem(Encounter $encounter, PrescriptionItem $item)
    {
        abort_if($item->prescription->encounter_id !== $encounter->id, 404);
        abort_if($item->is_dispensed, 403, 'Cannot delete a dispensed item.');
        $name = $item->medicine->name;
        $item->delete();
        ActivityLog::log('Prescription Item Deleted', "Removed {$name} from encounter {$encounter->encounter_no}");
        return response()->json(['success' => true]);
    }

    public function complete(Encounter $encounter)
    {
        $encounter->update(['status' => 'Completed']);

        if ($encounter->appointment_id) {
            $encounter->appointment->update([
                'status'       => 'Completed',
                'completed_at' => now(),
            ]);
        }

        if ($encounter->opd_token_id) {
            $encounter->opdToken->update([
                'status'       => 'done',
                'completed_at' => now(),
            ]);
        }

        // Auto-book the follow-up appointment if the doctor set follow_up_date.
        // Sunday → push to Monday (clinic closed). Time pinned to 17:00 (5 PM).
        // The existing appointments:send-whatsapp/email-reminders cron at 18:00
        // IST handles the day-before reminder automatically — no extra code.
        $followAppt = $this->autoBookFollowUp($encounter);

        $msg = 'Encounter completed.';
        if ($followAppt) {
            $msg .= " Follow-up booked for " . $followAppt->appointment_date->format('d M Y') . " at 5:00 PM (#{$followAppt->appointment_no}).";
        }

        return redirect()->route('encounters.show', $encounter)
            ->with('success', $msg)
            ->with('encounter_completed', $encounter->id);
    }

    /**
     * Auto-book follow-up when encounter completes. Sunday → Monday. 17:00 IST.
     * Idempotent (no-ops if a Scheduled/Confirmed appointment already exists
     * for that patient+doctor+date).
     */
    private function autoBookFollowUp(Encounter $encounter): ?\App\Models\Appointment
    {
        if (empty($encounter->follow_up_date)) return null;

        $date = \Carbon\Carbon::parse($encounter->follow_up_date);
        if ($date->isPast()) $date = \Carbon\Carbon::today()->addDay();
        if ($date->dayOfWeek === \Carbon\Carbon::SUNDAY) {
            $date = $date->addDay(); // → Monday
        }

        $existing = \App\Models\Appointment::where('patient_id', $encounter->patient_id)
            ->where('doctor_id', $encounter->doctor_id)
            ->whereDate('appointment_date', $date)
            ->whereNotIn('status', ['Cancelled', 'No-Show', 'Rescheduled'])
            ->first();
        if ($existing) return $existing;

        // 1-min-incrementing slot starting 17:00 — preserves visit-completion
        // order in the appointment list view. 1st completion → 17:00, 2nd → 17:01, …
        $slot = \App\Http\Controllers\AppointmentController::nextAvailableSlot(
            (int) $encounter->doctor_id,
            $date->toDateString()
        );

        $appt = \App\Models\Appointment::create([
            'appointment_no'   => \App\Models\Appointment::generateNo(),
            'patient_id'       => $encounter->patient_id,
            'doctor_id'        => $encounter->doctor_id,
            'appointment_date' => $date->toDateString(),
            'appointment_time' => $slot,
            'type'             => 'Follow-up',
            'status'           => 'Scheduled',
            'duration'         => optional(\App\Models\Doctor::find($encounter->doctor_id))->slot_duration ?? 15,
            'chief_complaint'  => \Illuminate\Support\Str::limit($encounter->follow_up_instructions ?: 'Follow-up visit', 200),
            'booking_source'   => 'auto_follow_up',
            'created_by'       => auth()->id(),
        ]);
        \App\Models\ActivityLog::log('Appointment Booked',
            "Auto follow-up {$appt->appointment_no} for {$encounter->patient->full_name} on {$date->toDateString()} 17:00 (from encounter {$encounter->encounter_no})");
        return $appt;
    }

    public function storeNote(Request $request, Encounter $encounter)
    {
        $request->validate(['body' => 'required|string|max:5000']);
        $note = $encounter->encounterNotes()->create([
            'patient_id' => $encounter->patient_id,
            'body'       => $request->body,
            'created_by' => auth()->user()?->name ?? 'Staff',
        ]);
        return response()->json([
            'success' => true,
            'note'    => [
                'id'         => $note->id,
                'body'       => $note->body,
                'created_by' => $note->created_by,
                'created_at' => $note->created_at->format('d M Y, h:i A'),
            ],
        ]);
    }

    public function updateNote(Request $request, Encounter $encounter, EncounterNote $note)
    {
        abort_if($note->encounter_id !== $encounter->id, 403);
        $request->validate(['body' => 'required|string|max:5000']);
        $note->update(['body' => $request->body]);
        return response()->json(['success' => true, 'body' => $note->body]);
    }

    public function destroyNote(Encounter $encounter, EncounterNote $note)
    {
        abort_if($note->encounter_id !== $encounter->id, 403);
        $note->delete();
        return response()->json(['success' => true]);
    }

    public function uploadReport(Request $request, Encounter $encounter)
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'report_type' => 'nullable|string|max:80',
            'notes' => 'nullable|string|max:2000',
            'report_file' => 'required|file|mimes:pdf,jpg,jpeg,png,webp,doc,docx|max:10240',
        ]);

        $file = $request->file('report_file');

        $dir = public_path("storage/encounter-reports/{$encounter->id}");
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        if (!is_writable($dir)) chmod($dir, 0777);

        $ext          = strtolower($file->getClientOriginalExtension());
        $safeName     = preg_replace('/[^A-Za-z0-9\-_\.]/', '-', pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $filename     = $safeName . '-' . now()->format('YmdHis') . '.' . $ext;
        $originalName = $file->getClientOriginalName();
        $mimeType     = $file->getClientMimeType();
        $fileSize     = $file->getSize();
        $file->move($dir, $filename);
        chmod($dir . '/' . $filename, 0644);

        $filePath = "encounter-reports/{$encounter->id}/{$filename}";

        $encounter->reports()->create([
            'patient_id'   => $encounter->patient_id,
            'title'        => $validated['title'] ?: pathinfo($originalName, PATHINFO_FILENAME),
            'report_type'  => $validated['report_type'] ?: null,
            'file_path'    => $filePath,
            'original_name'=> $originalName,
            'mime_type'    => $mimeType,
            'file_size'    => $fileSize,
            'notes'        => $validated['notes'] ?: null,
            'uploaded_by'  => auth()->id(),
        ]);

        return back()->with('success', 'Report uploaded successfully.');
    }

    public function deleteReport(Encounter $encounter, EncounterReport $report)
    {
        abort_unless($report->encounter_id === $encounter->id, 404);

        $fullPath = public_path('storage/' . $report->file_path);
        if (file_exists($fullPath)) unlink($fullPath);
        $report->delete();

        return back()->with('success', 'Report deleted.');
    }

    public function printPrescription(Prescription $prescription)
    {
        $prescription->load(['patient', 'doctor', 'items.medicine', 'encounter']);
        return view('encounters.prescription-print', compact('prescription'));
    }

    public function lastVisit(Encounter $encounter)
    {
        $prev = Encounter::with([
                'doctor',
                'diagnoses',
                'prescriptions.items.medicine',
            ])
            ->where('patient_id', $encounter->patient_id)
            ->where('id', '!=', $encounter->id)
            ->where('encounter_date', '<=', $encounter->encounter_date)
            ->orderByDesc('encounter_date')
            ->orderByDesc('id')
            ->first();

        if (!$prev) {
            return response()->json(['found' => false]);
        }

        $rxItems = [];
        foreach ($prev->prescriptions as $rx) {
            foreach ($rx->items as $item) {
                $rxItems[] = [
                    'medicine_id'   => $item->medicine_id,
                    'medicine_name' => $item->medicine->name ?? '',
                    'dose'          => $item->dose,
                    'frequency'     => $item->frequency,
                    'route'         => $item->route ?: 'Oral',
                    'duration_days' => (int) $item->duration_days,
                    'instructions'  => $item->instructions,
                ];
            }
        }

        $doctorName = trim(($prev->doctor->first_name ?? '') . ' ' . ($prev->doctor->last_name ?? ''));

        return response()->json([
            'found'                       => true,
            'encounter_id'                => $prev->id,
            'encounter_no'                => $prev->encounter_no,
            'encounter_date'              => $prev->encounter_date?->format('d M Y'),
            'days_ago'                    => $prev->encounter_date ? (int) $prev->encounter_date->diffInDays(now()) : null,
            'doctor_name'                 => $doctorName ?: null,
            'visit_type'                  => $prev->visit_type,
            'chief_complaint'             => $prev->chief_complaint,
            'history_of_present_illness'  => $prev->history_of_present_illness,
            'past_medical_history'        => $prev->past_medical_history,
            'family_history'              => $prev->family_history,
            'social_history'              => $prev->social_history,
            'examination_findings'        => $prev->examination_findings,
            'impression'                  => $prev->impression,
            'plan'                        => $prev->plan,
            'follow_up_instructions'      => $prev->follow_up_instructions,
            'diagnoses' => $prev->diagnoses->map(fn($d) => [
                'name'     => $d->diagnosis_name,
                'icd_code' => $d->icd_code,
                'type'     => $d->type,
                'status'   => $d->status,
            ])->values(),
            'rx_items' => $rxItems,
        ]);
    }

    /**
     * Live ICD-10 search for the diagnosis autocomplete on the encounter form.
     * Replaces the formerly-hardcoded 51-entry JS array with a DB-backed lookup
     * against icd_codes (seeded ~1000 codes via IcdCatalogSeeder).
     *
     * Relevance order: exact code match → code prefix → description LIKE.
     * Returns at most 15 rows shaped {c: code, d: description, cat: category}
     * to match the existing autocomplete render in encounters/show.blade.php.
     */
    public function icdSearch(\Illuminate\Http\Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }
        $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $q) . '%';
        $prefix = str_replace(['%', '_'], ['\\%', '\\_'], $q) . '%';
        $rows = \DB::table('icd_codes')
            ->select('code', 'description', 'category')
            ->where(function ($w) use ($q, $like, $prefix) {
                $w->where('code', $q)
                  ->orWhere('code', 'like', $prefix)
                  ->orWhere('description', 'like', $like);
            })
            ->orderByRaw('CASE WHEN code = ? THEN 0 WHEN code LIKE ? THEN 1 ELSE 2 END', [$q, $prefix])
            ->orderBy('code')
            ->limit(15)
            ->get()
            ->map(fn ($r) => ['c' => $r->code, 'd' => $r->description, 'cat' => $r->category]);
        return response()->json($rows);
    }
}
