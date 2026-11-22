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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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
        // A deleted visit is visible to the owner only, with its contents as they were
        abort_if($encounter->trashed() && !auth()->user()?->isOwner(), 404);
        $all = fn ($q) => $q->withoutGlobalScope('liveVisit');
        $encounter->load([
            'patient', 'doctor', 'appointment', 'bill', 'deletedBy',
            'diagnoses' => $all, 'prescriptions' => $all, 'prescriptions.items.medicine',
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
        $feeQuote = null;
        if (!$encounter->bill) {
            // Consultation fee from the OPD fee rules (free / follow-up window, discounts)
            $feeQuote = \App\Services\OpdFee::quote($encounter->patient, $encounter->doctor, \App\Services\OpdFee::departmentOf($encounter), $encounter->encounter_date, $encounter);
            $quickBillItems[] = \App\Services\OpdFee::billItem($feeQuote, $encounter->doctor);
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

        // OPD referrals: the one that brought this visit, the ones made from it, and the refer form's lists
        $referralFrom = \App\Models\OpdReferral::forEncounter($encounter);
        $referralsMade = $encounter->referralsMade()->with(['toDepartment', 'toDoctor', 'token'])->latest('id')->get();
        $referDepartments = OpdController::opdDepartments();
        $referDoctors = \App\Models\Doctor::where('is_active', true)->with('departments')->orderBy('first_name')->get();

        return view('encounters.show', compact(
            'referralFrom', 'referralsMade', 'referDepartments', 'referDoctors',
            'encounter', 'medicines', 'labTests', 'labPanels', 'patientAllergies', 'activePrescribedMeds',
            'patientAllergyRecords', 'commonAllergens', 'patientEcgRecords', 'patientEchoRecords',
            'services', 'quickBillItems', 'feeQuote', 'clinicalChips', 'rxOptions'
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

    /** Pin / unpin a diary drug to the top of its picker (diabetes_med / bp_med). */
    public function pinRxOption(Request $request)
    {
        $data = $request->validate([
            'field' => ['required', \Illuminate\Validation\Rule::in(\App\Models\RxOption::PINNABLE_FIELDS)],
            'value' => 'required|string|max:191',
        ]);
        $value = trim($data['value']);
        abort_if($value === '', 422, 'Empty value.');

        $opt = \App\Models\RxOption::firstOrCreate(
            ['field' => $data['field'], 'value' => $value],
            ['label' => $value, 'sort' => 999]
        );
        $opt->is_pinned = ! $opt->is_pinned;
        $opt->save();

        return response()->json(['value' => $opt->value, 'pinned' => (bool) $opt->is_pinned]);
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
        if (self::shouldLogSave($request, 'vitals', $encounter->id)) {
            ActivityLog::log('updated', "Updated vitals for patient {$encounter->patient->first_name} {$encounter->patient->last_name} (Encounter #{$encounter->id})" . ($request->boolean('autosave') ? ' [auto-save]' : ''));
        }
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
        if (self::shouldLogSave($request, 'clinical', $encounter->id)) {
            ActivityLog::log('updated', "Updated clinical notes for patient {$encounter->patient->first_name} {$encounter->patient->last_name} (Encounter #{$encounter->id})" . ($request->boolean('autosave') ? ' [auto-save]' : ''));
        }
        return response()->json(['success' => true]);
    }

    /** Auto-save posts every 3 s while typing: log those at most once per 10 minutes per encounter and section. */
    public static function shouldLogSave(Request $request, string $section, int $encounterId): bool
    {
        return !$request->boolean('autosave') || Cache::add("autosave-log:{$section}:{$encounterId}", 1, 600);
    }

    /** Sections of Clinical History & Examination that can be printed (code => label), in print order. */
    public const CLINICAL_PRINT_SECTIONS = [
        'chief_complaint'            => 'Chief Complaint',
        'history_of_present_illness' => 'History of Present Illness',
        'past_medical_history'       => 'Past Medical History',
        'family_history'             => 'Family History',
        'allergies'                  => 'Allergies',
        'social_history'             => 'Social History',
        'examination_findings'       => 'Examination',
        'impression'                 => 'Impression / Differential Diagnosis',
        'diagnoses'                  => 'Diagnoses',
        'plan'                       => 'Plan',
        'prescriptions'              => 'Prescriptions',
        'follow_up'                  => 'Follow-up',
    ];

    /**
     * Print all or some clinical sections: ?s=chief_complaint,social_history (none = all).
     * ?all=1 prints every visit of this patient, oldest first (?order=desc for newest first).
     */
    public function printClinical(Request $request, Encounter $encounter)
    {
        $encounter->loadMissing(['patient', 'doctor']);
        $wanted = array_filter(explode(',', (string) $request->query('s', '')));
        $codes = array_keys(self::CLINICAL_PRINT_SECTIONS);
        if ($wanted) $codes = array_values(array_intersect($codes, $wanted));

        $allVisits = $request->boolean('all');
        $desc = $request->query('order') === 'desc';
        $encounters = $allVisits
            ? Encounter::with(['doctor', 'diagnoses', 'prescriptions.items.medicine'])
                ->where('patient_id', $encounter->patient_id)
                ->orderBy('encounter_date', $desc ? 'desc' : 'asc')->orderBy('id', $desc ? 'desc' : 'asc')
                ->get()
            : collect([$encounter->load(['diagnoses', 'prescriptions.items.medicine'])]);

        // Allergies live on the patient, not the visit: same list for every visit.
        $allergyRows = $encounter->patient->patientAllergies()->orderBy('allergen')->get();
        $allergyHtml = $allergyRows->map(fn ($a) => '<li>' . e($a->allergen) . ($a->severity && $a->severity !== 'unknown' ? ' (' . e($a->severity) . ')' : '') . '</li>')->implode('');
        $allergyHtml = $allergyHtml ? "<ul>{$allergyHtml}</ul>" : '';

        $visits = $encounters->map(fn ($enc) => [
            'encounter' => $enc,
            'sections'  => $this->clinicalPrintSections($enc, $codes, $allergyHtml),
        ]);

        return view('encounters.clinical-print', compact('encounter', 'visits', 'allVisits'));
    }

    /** [['label' => .., 'html' => ..], ...] for one visit; html is sanitised. */
    private function clinicalPrintSections(Encounter $enc, array $codes, string $allergyHtml): array
    {
        // Quill HTML: keep only basic formatting tags, drop every attribute.
        $clean = function (?string $html) {
            $html = strip_tags((string) $html, '<p><br><strong><b><em><i><u><ul><ol><li>');
            $html = preg_replace('/<(\/?)(\w+)[^>]*>/', '<$1$2>', $html);
            return trim(strip_tags($html)) === '' ? '' : $html;
        };

        $sections = [];
        foreach ($codes as $code) {
            if ($code === 'allergies') {
                $html = $allergyHtml;
            } elseif ($code === 'diagnoses') {
                $html = $enc->diagnoses->map(fn ($d) => '<li>' . e($d->diagnosis_name) . ($d->icd_code ? ' <span class="muted">(' . e($d->icd_code) . ')</span>' : '') . ' — ' . e($d->type) . ', ' . e($d->status) . '</li>')->implode('');
                $html = $html ? "<ul>{$html}</ul>" : '';
            } elseif ($code === 'prescriptions') {
                $html = '';
                foreach ($enc->prescriptions as $rx) {
                    $items = $rx->items->map(fn ($it) => '<li><strong>' . e($it->medicine->name ?? '') . '</strong> ' . e(implode(' · ', array_filter([$it->dose, $it->frequency, $it->route, $it->duration_days ? $it->duration_days . ' days' : null]))) . ($it->instructions ? ' — ' . e($it->instructions) : '') . '</li>')->implode('');
                    if ($items) $html .= "<ul>{$items}</ul>";
                    if (trim((string) $rx->advice) !== '') $html .= '<p><strong>Advice:</strong> ' . e($rx->advice) . '</p>';
                }
            } elseif ($code === 'follow_up') {
                $parts = [];
                if ($enc->follow_up_date) $parts[] = '<p><strong>Next visit:</strong> ' . e($enc->follow_up_date->format('d M Y')) . '</p>';
                if (trim((string) $enc->follow_up_instructions) !== '') $parts[] = '<p>' . nl2br(e($enc->follow_up_instructions)) . '</p>';
                $html = implode('', $parts);
            } else {
                $html = $clean($enc->{$code});
            }
            $sections[] = ['label' => self::CLINICAL_PRINT_SECTIONS[$code], 'html' => $html];
        }
        return $sections;
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

        // All-or-nothing: auto-save replaces the list every few seconds while editing.
        DB::transaction(function () use ($request, $encounter) {
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
        });

        return response()->json(['success' => true, 'count' => count($request->diagnoses)]);
    }

    /**
     * Suggested blood tests / investigations for this encounter, based on its
     * (just-saved) diagnoses, the patient's age, chief complaint, and current
     * medicines. Rule-based baseline always runs; the AI layer (medication
     * side-effect / nuanced additions) is best-effort and silently skipped if
     * unavailable. Never places an order — the doctor reviews/edits/places it
     * from the Labs tab, same as the existing template-driven suggestion card.
     */
    public function suggestInvestigations(Encounter $encounter, \App\Services\InvestigationSuggester $suggester)
    {
        $encounter->load('diagnoses', 'patient');

        $rules = $suggester->suggestFromRules($encounter);
        $ai = $suggester->suggestFromAi($encounter, $rules['tests'], $rules['panels'], $rules['procedures']);

        $panels = array_values(array_unique(array_merge($rules['panels'], $ai['panels'] ?? [])));
        $tests = array_values(array_unique(array_merge($rules['tests'], $ai['tests'] ?? [])));
        $procedureNames = array_values(array_unique(array_merge($rules['procedures'], $ai['procedures'] ?? [])));

        $procedures = collect($procedureNames)->map(fn ($name) => [
            'name' => $name,
            'url' => route(\App\Services\InvestigationSuggester::PROCEDURE_ROUTES[$name], ['patient_id' => $encounter->patient_id]),
        ])->values();

        return response()->json([
            'panels' => $panels,
            'tests' => $tests,
            'procedures' => $procedures,
            'ai_used' => $ai !== null,
            'reasons' => $ai['reasons'] ?? [],
        ]);
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
     * Records that stop a visit from being deleted: they carry money or test results
     * of their own, so they must be deleted (or moved) on their own pages first.
     */
    public static function deleteBlockers(Encounter $encounter): array
    {
        $blockers = [];
        if ($bill = $encounter->bill) $blockers[] = "bill {$bill->bill_no}";
        foreach (['lab_orders' => 'lab order', 'ecg_records' => 'ECG', 'echo_records' => 'echo'] as $table => $label) {
            $n = DB::table($table)->where('encounter_id', $encounter->id)->count();
            if ($n) $blockers[] = $n . ' ' . $label . ($n > 1 ? 's' : '');
        }
        return $blockers;
    }

    /** Follow-up appointment auto-booked when this visit was completed, if still pending. */
    public static function autoFollowUpOf(Encounter $encounter): ?Appointment
    {
        return Appointment::where('patient_id', $encounter->patient_id)
            ->where('doctor_id', $encounter->doctor_id)
            ->where('booking_source', 'auto_follow_up')
            ->whereIn('status', ['Scheduled', 'Confirmed'])
            ->where('created_at', '>=', $encounter->created_at)
            ->whereDate('appointment_date', '>=', $encounter->encounter_date)
            ->orderBy('appointment_date')
            ->first();
    }

    /**
     * Delete (hide) a visit entered by mistake, e.g. a duplicate. Allowed for the owner
     * and staff given "Can delete visits". Nothing is removed: the visit and everything
     * in it vanish from lists and reports, and the owner can view, restore or
     * permanently delete it under Deleted Visits. Optionally cancels the follow-up
     * appointment this visit auto-booked.
     */
    public function destroy(Request $request, Encounter $encounter)
    {
        abort_unless(auth()->user()?->canDeleteVisits(), 403, 'You are not allowed to delete visits.');
        $request->validate(['reason' => 'required|string|max:255']);

        if ($blockers = self::deleteBlockers($encounter)) {
            return back()->with('error', "Visit {$encounter->encounter_no} still has " . implode(', ', $blockers)
                . '. Delete or move ' . (count($blockers) > 1 ? 'them' : 'it') . ' first, then delete the visit.');
        }

        $encounter->load('patient');
        $followUp = $request->boolean('cancel_follow_up') ? self::autoFollowUpOf($encounter) : null;
        $sourceAppt = $request->boolean('cancel_appointment') ? self::sourceAppointmentOf($encounter) : null;

        DB::transaction(function () use ($request, $encounter, $followUp, $sourceAppt) {
            $encounter->forceFill(['deleted_by' => auth()->id(), 'delete_reason' => $request->reason])->save();
            $encounter->delete();
            $followUp?->update(['status' => 'Cancelled']);
            // The appointment the visit was started from: cancel it so it leaves the appointments list;
            // the old status is kept in the note so restoring the visit can put it back.
            $sourceAppt?->update([
                'status' => 'Cancelled',
                'notes'  => trim($sourceAppt->notes . "\n" . self::VISIT_DELETED_NOTE . " {$encounter->encounter_no} (status was {$sourceAppt->status}): {$request->reason}"),
            ]);
        });

        $extra = ($followUp ? "; cancelled follow-up {$followUp->appointment_no} ({$followUp->appointment_date->toDateString()})" : '')
            . ($sourceAppt ? "; cancelled appointment {$sourceAppt->appointment_no}" : '');
        ActivityLog::log('deleted', "Deleted visit {$encounter->encounter_no} of {$encounter->patient->full_name} dated "
            . "{$encounter->encounter_date->toDateString()} — reason: {$request->reason}{$extra}");

        return redirect()->route('patients.show', $encounter->patient)
            ->with('success', "Visit {$encounter->encounter_no} deleted."
                . ($sourceAppt ? " Appointment {$sourceAppt->appointment_no} removed from the appointments list." : '')
                . ($followUp ? " Follow-up {$followUp->appointment_no} cancelled." : ''));
    }

    /** Marker written into an appointment's notes when deleting its visit cancels it. */
    public const VISIT_DELETED_NOTE = '[Visit deleted]';

    /** The appointment this visit was started from, if no other (live) visit uses it and it is still open. */
    public static function sourceAppointmentOf(Encounter $encounter): ?Appointment
    {
        $appt = $encounter->appointment;
        if (!$appt || in_array($appt->status, ['Cancelled', 'No-Show', 'Rescheduled'])) return null;
        $othersUseIt = Encounter::where('appointment_id', $appt->id)->where('id', '!=', $encounter->id)->exists();
        return $othersUseIt ? null : $appt;
    }

    /** Deleted Visits: owner only. */
    public function deleted(Request $request)
    {
        abort_unless(auth()->user()?->isOwner(), 403);
        $q = $request->get('q');
        $encounters = Encounter::onlyTrashed()->with('patient', 'doctor', 'deletedBy')
            ->when($q, fn($query) => $query->where(fn($w) => $w->where('encounter_no', 'like', "%$q%")
                ->orWhereHas('patient', fn($p) => $p->where('first_name', 'like', "%$q%")
                    ->orWhere('last_name', 'like', "%$q%")
                    ->orWhere('jhc_id', 'like', "%$q%"))))
            ->orderByDesc('deleted_at')->paginate(25)->withQueryString();
        return view('encounters.deleted', compact('encounters', 'q'));
    }

    /** Bring a deleted visit back exactly as it was. Owner only. */
    public function restore(Encounter $encounter)
    {
        abort_unless(auth()->user()?->isOwner(), 403);
        abort_unless($encounter->trashed(), 404);
        $encounter->restore();
        $encounter->forceFill(['deleted_by' => null, 'delete_reason' => null])->save();

        // Put back the appointment that deleting this visit cancelled.
        $appt = $encounter->appointment;
        $reopened = '';
        if ($appt && $appt->status === 'Cancelled'
            && preg_match_all('/' . preg_quote(self::VISIT_DELETED_NOTE, '/') . ' ' . preg_quote($encounter->encounter_no, '/') . ' \(status was ([A-Za-z-]+)\)/', (string) $appt->notes, $m)) {
            $appt->update(['status' => end($m[1]), 'notes' => trim($appt->notes . "\nVisit {$encounter->encounter_no} restored")]);
            $reopened = " Appointment {$appt->appointment_no} is back as " . end($m[1]) . '.';
        }

        ActivityLog::log('updated', "Restored deleted visit {$encounter->encounter_no} of {$encounter->patient->full_name}" . ($reopened ? "; reopened appointment {$appt->appointment_no}" : ''));
        return redirect()->route('encounters.show', $encounter)->with('success', "Visit {$encounter->encounter_no} restored.{$reopened}");
    }

    /**
     * Permanently remove a deleted visit. Owner only, from Deleted Visits.
     * Removed with it: diagnoses, prescriptions, notes, uploaded reports (and their files).
     * Vitals, diet and radiology entries stay in the patient's history, unlinked.
     * Optionally also removes the appointment / OPD token it came from.
     */
    public function forceDestroy(Request $request, Encounter $encounter)
    {
        abort_unless(auth()->user()?->isOwner(), 403, 'Only the clinic owner can permanently delete visits.');
        abort_unless($encounter->trashed(), 404);

        $encounter->load('patient', 'appointment', 'opdToken', 'reports');
        $removed = [];
        $files   = $encounter->reports->pluck('file_path')->filter()->all();

        DB::transaction(function () use ($request, $encounter, &$removed) {
            DB::table('clinical_notes')->where('encounter_id', $encounter->id)->delete();
            $appt  = $encounter->appointment;
            $token = $encounter->opdToken;
            $encounter->forceDelete(); // diagnoses, prescriptions (+items), notes, reports cascade

            // Only if no other visit (deleted or not) still uses them
            if ($request->boolean('delete_appointment') && $appt && !Encounter::withTrashed()->where('appointment_id', $appt->id)->exists()) {
                if (Appointment::where('rescheduled_from', $appt->id)->exists()) {
                    $appt->update(['status' => 'Cancelled']);
                    $removed[] = "cancelled appointment {$appt->appointment_no}";
                } else {
                    $appt->delete();
                    $removed[] = "deleted appointment {$appt->appointment_no}";
                }
            }
            if ($request->boolean('delete_token') && $token && !Encounter::withTrashed()->where('opd_token_id', $token->id)->exists()) {
                $token->delete(); // triage cascades
                $removed[] = "deleted OPD token #{$token->id}";
            }
        });

        foreach ($files as $f) {
            $path = public_path('storage/' . $f);
            if (is_file($path)) @unlink($path);
        }

        ActivityLog::log('deleted', "Permanently deleted visit {$encounter->encounter_no} of {$encounter->patient->full_name} dated {$encounter->encounter_date->toDateString()}"
            . ($removed ? '; ' . implode('; ', $removed) : ''));

        return redirect()->route('encounters.deleted')
            ->with('success', "Visit {$encounter->encounter_no} permanently deleted." . ($removed ? ' Also ' . implode(', ', $removed) . '.' : ''));
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
