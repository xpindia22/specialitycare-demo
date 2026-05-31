<?php
namespace App\Http\Controllers;

use App\Models\Admission;
use App\Models\ActivityLog;
use App\Models\ConsentForm;
use Illuminate\Http\Request;

class ConsentController extends Controller
{
    public function store(Request $request, Admission $admission)
    {
        $data = $request->validate([
            'consent_type'       => 'required|in:' . implode(',', array_keys(ConsentForm::TYPES)),
            'procedure_name'     => 'nullable|string|max:200',
            'is_obtained'        => 'boolean',
            'patient_or_guardian'=> 'nullable|string|max:150',
            'relationship'       => 'nullable|string|max:80',
            'witness_name'       => 'nullable|string|max:150',
            'notes'              => 'nullable|string|max:500',
        ]);

        $data['admission_id'] = $admission->id;
        $data['obtained_by']  = auth()->id();
        $data['obtained_at']  = $data['is_obtained'] ?? false ? now() : null;

        ConsentForm::updateOrCreate(
            [
                'admission_id' => $admission->id,
                'consent_type' => $data['consent_type'],
                'procedure_name' => $data['procedure_name'] ?? null,
            ],
            $data
        );

        ActivityLog::log('Consent Recorded', "Consent '{$data['consent_type']}' for {$admission->ipd_no}");

        return back()->with('success', 'Consent form updated.')->withFragment('tab-consents');
    }

    public function destroy(Admission $admission, ConsentForm $consent)
    {
        abort_if($consent->admission_id !== $admission->id, 404);
        $consent->delete();
        return back()->with('success', 'Consent record removed.')->withFragment('tab-consents');
    }

    public function print(Admission $admission, string $type)
    {
        abort_unless(array_key_exists($type, ConsentForm::TYPES), 404);
        $admission->load(['patient', 'admittingDoctor', 'ward', 'bed']);
        $consent = ConsentForm::where('admission_id', $admission->id)
            ->where('consent_type', $type)
            ->first();
        $typeLabel = ConsentForm::TYPES[$type]['label'];

        return view('admissions.consent-print', compact('admission', 'consent', 'type', 'typeLabel'));
    }
}
