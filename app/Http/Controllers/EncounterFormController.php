<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Encounter;
use App\Models\EncounterForm;
use App\Services\OpdForms;
use Illuminate\Http\Request;

/** Speciality visit forms (eye, gynae, ortho, skin) on an encounter. */
class EncounterFormController extends Controller
{
    public function save(Request $request, Encounter $encounter, string $form)
    {
        abort_unless(OpdForms::get($form), 404);
        $data = OpdForms::clean($form, $request->input('f', []));

        $row = EncounterForm::firstOrNew(['encounter_id' => $encounter->id, 'form' => $form]);
        if (!$data && !$row->exists) return response()->json(['success' => true]);
        $row->fill(['patient_id' => $encounter->patient_id, 'data' => $data, 'updated_by' => auth()->id()])->save();

        $encounter->loadMissing('patient');
        if (EncounterController::shouldLogSave($request, "form-{$form}", $encounter->id)) {
            ActivityLog::log('updated', OpdForms::get($form)['title'] . " form saved for {$encounter->patient->first_name} {$encounter->patient->last_name} (Encounter #{$encounter->id})" . ($request->boolean('autosave') ? ' [auto-save]' : ''));
        }
        return response()->json(['success' => true, 'filled' => count($data)]);
    }

    public function print(Encounter $encounter, string $form)
    {
        $def = OpdForms::get($form);
        abort_unless($def, 404);
        $row = EncounterForm::where('encounter_id', $encounter->id)->where('form', $form)->firstOrFail();
        $encounter->loadMissing(['patient', 'doctor']);
        // ?glasses=1 prints only the spectacle prescription
        $glassesOnly = $form === 'OPTH' && request()->boolean('glasses');
        return view('encounters.form-print', [
            'encounter' => $encounter, 'def' => $def, 'form' => $form, 'data' => $row->data ?? [],
            'glassesOnly' => $glassesOnly,
        ]);
    }
}
