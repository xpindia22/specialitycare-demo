<?php
namespace App\Http\Controllers;

use App\Models\DietPrescription;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DietPrescriptionController extends Controller
{
    public function create(Request $request)
    {
        $patient = $request->patient_id ? Patient::findOrFail($request->patient_id) : null;
        $conditions = DietPrescription::CONDITIONS;
        $activityLevels = DietPrescription::ACTIVITY_LEVELS;
        return view('diet-prescriptions.create', compact('patient', 'conditions', 'activityLevels'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'patient_id'       => 'required|exists:patients,id',
            'height_cm'        => 'required|numeric|min:50|max:250',
            'weight_kg'        => 'required|numeric|min:10|max:300',
            'target_weight_kg' => 'nullable|numeric|min:10|max:300',
            'activity_level'   => 'required|in:sedentary,light,moderate,active',
            'conditions'       => 'required|array|min:1',
            'conditions.*'     => 'in:dm,htn,cad,cholesterol,uric_acid,hypothyroid,ckd,obesity',
            'diet_notes'       => 'nullable|string|max:2000',
            'admission_id'     => 'nullable|exists:admissions,id',
            'encounter_id'     => 'nullable|exists:encounters,id',
        ]);

        $patient = Patient::findOrFail($data['patient_id']);
        $bmi = round($data['weight_kg'] / (($data['height_cm'] / 100) ** 2), 1);
        $calories = DietPrescription::calculateCalories(
            $data['height_cm'], $data['weight_kg'],
            $patient->age_years ?? 40,
            $patient->gender ?? 'male',
            $data['activity_level'], $bmi
        );

        $rx = DietPrescription::create([
            'patient_id'       => $data['patient_id'],
            'admission_id'     => $data['admission_id'] ?? null,
            'encounter_id'     => $data['encounter_id'] ?? null,
            'height_cm'        => $data['height_cm'],
            'weight_kg'        => $data['weight_kg'],
            'bmi'              => $bmi,
            'target_weight_kg' => $data['target_weight_kg'] ?? null,
            'activity_level'   => $data['activity_level'],
            'conditions'       => $data['conditions'],
            'calorie_target'   => $calories,
            'diet_notes'       => $data['diet_notes'] ?? null,
            'prescribed_by'    => Auth::id(),
            'prescribed_at'    => now(),
        ]);

        return redirect()->route('diet-prescriptions.show', $rx)
                         ->with('success', 'Diet prescription saved.');
    }

    public function show(DietPrescription $dietPrescription)
    {
        $dietPrescription->load('patient', 'prescribedBy', 'admission', 'encounter');
        $dietPlans = DietPrescription::DIET_PLANS;
        $conditions = DietPrescription::CONDITIONS;
        return view('diet-prescriptions.show', compact('dietPrescription', 'dietPlans', 'conditions'));
    }

    public function index(Request $request)
    {
        $query = DietPrescription::with('patient', 'prescribedBy')->latest();
        if ($request->patient_id) $query->where('patient_id', $request->patient_id);
        if ($q = trim((string) $request->q)) {
            $query->whereHas('patient', function ($pq) use ($q) {
                $pq->where('first_name', 'like', "%{$q}%")
                   ->orWhere('last_name', 'like', "%{$q}%")
                   ->orWhere('jhc_id', 'like', "%{$q}%")
                   ->orWhere('phone', 'like', "%{$q}%");
            });
        }
        $prescriptions = $query->paginate(20)->withQueryString();
        return view('diet-prescriptions.index', compact('prescriptions'));
    }
}
