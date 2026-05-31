<?php
// FILE: app/Http/Controllers/PatientAllergyController.php
namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\PatientAllergy;
use Illuminate\Http\Request;

class PatientAllergyController extends Controller
{
    public function store(Request $request, Patient $patient)
    {
        $validated = $request->validate([
            'allergen' => 'required|string|max:100',
            'severity' => 'nullable|in:mild,moderate,severe,unknown',
        ]);

        $allergy = PatientAllergy::create([
            'patient_id' => $patient->id,
            'allergen'   => $validated['allergen'],
            'severity'   => $validated['severity'] ?? 'unknown',
        ]);

        $this->syncPatientAllergiesText($patient);

        return response()->json([
            'success' => true,
            'allergy' => [
                'id'       => $allergy->id,
                'allergen' => $allergy->allergen,
                'severity' => $allergy->severity,
            ],
        ]);
    }

    public function destroy(Patient $patient, PatientAllergy $allergy)
    {
        $allergy->delete();

        $this->syncPatientAllergiesText($patient);

        return response()->json(['success' => true]);
    }

    private function syncPatientAllergiesText(Patient $patient): void
    {
        $patient->refresh();
        $names = $patient->patientAllergies()->orderBy('allergen')->pluck('allergen')->toArray();
        $patient->allergies = count($names) ? implode(', ', $names) : null;
        $patient->save();
    }
}
