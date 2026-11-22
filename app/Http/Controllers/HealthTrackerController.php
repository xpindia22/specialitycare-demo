<?php

namespace App\Http\Controllers;

use App\Models\BpLog;
use App\Models\DietLog;
use App\Models\ExerciseLog;
use App\Models\GlucoseLog;
use App\Models\HealthMetric;
use App\Models\Patient;
use App\Models\RxOption;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HealthTrackerController extends Controller
{
    public function index(Patient $patient)
    {
        $metrics  = HealthMetric::where('patient_id', $patient->id)->orderBy('logged_at')->get();
        $diet     = DietLog::with('recordedBy')->where('patient_id', $patient->id)->orderByDesc('logged_at')->get();
        $exercise = ExerciseLog::with('recordedBy')->where('patient_id', $patient->id)->orderByDesc('logged_at')->get();

        $weightLogs  = $metrics->where('metric', 'weight')->values();
        $weightTrend = HealthMetric::weightTrend($weightLogs);
        $latestWeight = $weightLogs->isNotEmpty() ? (float) $weightLogs->sortBy('logged_at')->last()->value : null;

        // Latest value per vital (for the summary cards).
        $vitalsLatest = [];
        foreach (['heart_rate', 'spo2', 'stress'] as $m) {
            $vitalsLatest[$m] = $metrics->where('metric', $m)->sortByDesc('logged_at')->first();
        }

        // Energy balance for the last 14 calendar days (summary cards only).
        $today = today();
        $todayIn  = (int) $diet->filter(fn ($x) => $x->logged_at->isToday())->sum('calories');
        $todayOut = (int) $exercise->filter(fn ($x) => $x->logged_at->isToday())->sum('calories_burned');

        $dietByDay     = $diet->groupBy(fn ($x) => $x->logged_at->toDateString());
        $exerciseByDay = $exercise->groupBy(fn ($x) => $x->logged_at->toDateString());

        $foodExtras     = RxOption::where('field', 'food_item')->orderBy('sort')->orderBy('id')->pluck('value')->all();
        $exerciseExtras = RxOption::where('field', 'exercise_item')->orderBy('sort')->orderBy('id')->pluck('value')->all();

        // Glucose (diabetes) data.
        $glucoseLogs   = GlucoseLog::where('patient_id', $patient->id)->orderBy('logged_at')->get();
        $latestGlucose = $glucoseLogs->filter(fn ($l) => $l->value !== null && $l->isGlucose())
                                     ->sortByDesc('logged_at')->first();
        $glucoseSeries = $glucoseLogs->filter(fn ($l) => $l->value !== null && $l->isGlucose())
                                     ->map(fn ($l) => [
                                         'label' => $l->logged_at->format('d M, H:i'),
                                         'y'     => (float) $l->value,
                                         'color' => $l->classification()['color'],
                                     ])->values();

        // BP data.
        $bpLogs   = BpLog::where('patient_id', $patient->id)->orderBy('logged_at')->get();
        $latestBp = $bpLogs->filter(fn ($l) => $l->systolic && $l->diastolic)->sortByDesc('logged_at')->first();
        $bpSeries = $bpLogs->filter(fn ($l) => $l->systolic && $l->diastolic)
                           ->map(fn ($l) => [
                               'label' => $l->logged_at->format('d M, H:i'),
                               'sys'   => $l->systolic,
                               'dia'   => $l->diastolic,
                           ])->values();

        // Foods entered in the diabetes diary (glucose_log.meal_notes) surfaced here
        // too, grouped by day and resolved to their time-of-day.
        $diaryFoodsByDay = [];
        foreach ($glucoseLogs->groupBy(fn ($l) => $l->logged_at->toDateString()) as $date => $dayLogs) {
            $items = [];
            foreach ($dayLogs as $l) {
                foreach (GlucoseLog::parseFoods($l->meal_notes, $date, $l->logged_at->getTimestamp()) as $f) {
                    $items[] = $f;
                }
            }
            if ($items) {
                usort($items, fn ($a, $b) => $a['sort'] <=> $b['sort']);
                $diaryFoodsByDay[$date] = $items;
            }
        }

        return view('health.index', compact(
            'patient', 'metrics', 'weightTrend', 'latestWeight', 'vitalsLatest',
            'todayIn', 'todayOut', 'diet', 'exercise', 'dietByDay', 'exerciseByDay',
            'foodExtras', 'exerciseExtras', 'diaryFoodsByDay',
            'glucoseLogs', 'latestGlucose', 'glucoseSeries',
            'bpLogs', 'latestBp', 'bpSeries'
        ));
    }

    public function storeMetric(Request $request, Patient $patient)
    {
        $data = $request->validate([
            'metric'    => ['required', Rule::in(array_keys(HealthMetric::METRICS))],
            'value'     => 'required|numeric|min:0|max:1000',
            'logged_at' => 'required|date',
            'notes'     => 'nullable|string|max:255',
        ]);

        HealthMetric::create($data + ['patient_id' => $patient->id, 'recorded_by' => auth()->id()]);

        return back()->with('success', HealthMetric::METRICS[$data['metric']]['label'] . ' recorded.');
    }

    public function storeDiet(Request $request, Patient $patient)
    {
        $data = $request->validate([
            'meal'      => ['nullable', Rule::in(array_keys(DietLog::MEALS))],
            'food'      => 'required|string|max:191',
            'quantity'  => 'nullable|numeric|min:0|max:999',
            'calories'  => 'nullable|integer|min:0|max:50000',
            'logged_at' => 'required|date',
            'notes'     => 'nullable|string|max:255',
        ]);

        DietLog::create($data + ['patient_id' => $patient->id, 'recorded_by' => auth()->id()]);

        return back()->with('success', 'Food logged' . ($data['calories'] ?? null ? " ({$data['calories']} kcal)" : '') . '.');
    }

    public function storeExercise(Request $request, Patient $patient)
    {
        $data = $request->validate([
            'activity'        => 'required|string|max:100',
            'minutes'         => 'nullable|integer|min:1|max:1000',
            'calories_burned' => 'nullable|integer|min:0|max:20000',
            'logged_at'       => 'required|date',
            'notes'           => 'nullable|string|max:255',
        ]);

        // Auto-estimate calories if not supplied and we know the body weight.
        if (($data['calories_burned'] ?? null) === null) {
            $w = HealthMetric::where('patient_id', $patient->id)->where('metric', 'weight')
                ->orderByDesc('logged_at')->value('value');
            $data['calories_burned'] = ExerciseLog::estimateCalories($data['activity'], $data['minutes'] ?? null, $w ? (float) $w : null);
        }

        ExerciseLog::create($data + ['patient_id' => $patient->id, 'recorded_by' => auth()->id()]);

        return back()->with('success', 'Activity logged' . ($data['calories_burned'] ? " ({$data['calories_burned']} kcal burned)" : '') . '.');
    }

    public function updateMetric(Request $request, Patient $patient, HealthMetric $healthMetric)
    {
        abort_if($healthMetric->patient_id !== $patient->id, 404);
        $data = $request->validate([
            'metric'    => ['required', Rule::in(array_keys(HealthMetric::METRICS))],
            'value'     => 'required|numeric|min:0|max:1000',
            'logged_at' => 'required|date',
            'notes'     => 'nullable|string|max:255',
        ]);
        $healthMetric->update($data);
        return back()->with('success', HealthMetric::METRICS[$data['metric']]['label'] . ' updated.');
    }

    public function updateDiet(Request $request, Patient $patient, DietLog $dietLog)
    {
        abort_if($dietLog->patient_id !== $patient->id, 404);
        $data = $request->validate([
            'meal'      => ['nullable', Rule::in(array_keys(DietLog::MEALS))],
            'food'      => 'required|string|max:191',
            'quantity'  => 'nullable|numeric|min:0|max:999',
            'calories'  => 'nullable|integer|min:0|max:50000',
            'logged_at' => 'required|date',
            'notes'     => 'nullable|string|max:255',
        ]);
        $dietLog->update($data);
        return back()->with('success', 'Food entry updated.');
    }

    public function destroyMetric(Patient $patient, HealthMetric $healthMetric)
    {
        abort_if($healthMetric->patient_id !== $patient->id, 404);
        $healthMetric->delete();
        return back()->with('success', 'Entry removed.');
    }

    public function destroyDiet(Patient $patient, DietLog $dietLog)
    {
        abort_if($dietLog->patient_id !== $patient->id, 404);
        $dietLog->delete();
        return back()->with('success', 'Food entry removed.');
    }

    public function destroyExercise(Patient $patient, ExerciseLog $exerciseLog)
    {
        abort_if($exerciseLog->patient_id !== $patient->id, 404);
        $exerciseLog->delete();
        return back()->with('success', 'Activity removed.');
    }
}
