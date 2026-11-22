<?php

namespace App\Http\Controllers;

use App\Models\BpLog;
use App\Models\Diagnosis;
use App\Models\DietLog;
use App\Models\ExerciseLog;
use App\Models\GlucoseLog;
use App\Models\HealthMetric;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * AI-generated, advisory medicine/dose suggestions for the diabetes and BP
 * diaries. Pulls the patient's full context (comorbidities from diagnoses,
 * current medicines, allergies, recent readings) and asks Claude for
 * guideline-based options. Output is decision support for the treating
 * doctor — never an auto-prescription. Gated to admin/doctor.
 */
class AiSuggestionController extends Controller
{
    public function suggest(Request $request, Patient $patient)
    {
        $focus = in_array($request->input('focus'), ['diabetes', 'bp', 'health'], true)
            ? $request->input('focus') : 'diabetes';

        $apiKey = env('ANTHROPIC_API_KEY') ?: $this->readEnvKey('ANTHROPIC_API_KEY');
        if (!$apiKey) {
            return response()->json(['error' => 'AI is not configured. Add ANTHROPIC_API_KEY to .env.'], 503);
        }

        $context = $this->buildContext($patient);

        $system = implode("\n", [
            "You are a clinical decision-support assistant inside SpecialityCare HMS, advising a qualified doctor.",
            "Give evidence-based, guideline-aligned medication suggestions (ADA/ICMR for diabetes; ACC/AHA/standard for hypertension).",
            "ALWAYS account for the patient's comorbidities, allergies, current medicines and possible drug interactions, plus age and likely renal/hepatic caution.",
            "Suggest concrete OPTIONS with typical starting doses and titration steps where relevant — but frame everything as options for the doctor to decide, never as a prescription.",
            "Be concise. Flag any urgent/red-flag findings first. If the data is insufficient for a suggestion, say so plainly. Do not invent values that are not provided.",
            "Respond in Markdown with these short sections: **Snapshot**, **Suggested options** (bullet list, include typical doses), **Cautions & interactions**, **Monitoring / follow-up**, **When to escalate**. Keep under ~400 words.",
            "End with one line: _Advisory only — the treating doctor must confirm all decisions._",
        ]);

        $userMsg = match ($focus) {
            'bp'     => "Focus on BLOOD-PRESSURE management. Based on the data below, suggest antihypertensive options / dose adjustments, considering the comorbidities and current medicines.\n\n" . $context,
            'health' => "Focus on DIET, EXERCISE & LIFESTYLE. Based on the data below: (1) comment on the daily calorie intake vs burn and weight trend; (2) for the foods logged, suggest specific HEALTHIER ALTERNATIVES with their approx calories; (3) give an exercise and lifestyle plan; all tailored to the comorbidities (e.g. diabetes/BP) and current medicines. Add **Suggested daily calorie target** if weight management is relevant.\n\n" . $context,
            default  => "Focus on DIABETES management. Based on the blood-sugar reports and current medicines below, suggest medication options and dose adjustments, considering the comorbidities.\n\n" . $context,
        };

        $response = Http::withHeaders([
            'x-api-key'         => $apiKey,
            'anthropic-version' => '2023-06-01',
            'content-type'      => 'application/json',
        ])->timeout(120)->post('https://api.anthropic.com/v1/messages', [
            'model'      => 'claude-sonnet-4-6',
            'max_tokens' => 1500,
            'system'     => $system,
            'messages'   => [['role' => 'user', 'content' => $userMsg]],
        ]);

        if (!$response->successful()) {
            return response()->json(['error' => $response->json('error.message', 'AI request failed.')], 502);
        }

        $text = collect($response->json('content', []))
            ->where('type', 'text')->pluck('text')->implode('');
        if (trim($text) === '') {
            return response()->json(['error' => 'No suggestion returned.'], 502);
        }

        try {
            $html = Str::markdown($text, ['html_input' => 'strip', 'allow_unsafe_links' => false]);
        } catch (\Throwable $e) {
            $html = nl2br(e($text));
        }

        return response()->json(['html' => $html]);
    }

    /** Assemble a compact text snapshot of the patient for the model. */
    private function buildContext(Patient $patient): string
    {
        $L = [];
        $L[] = "PATIENT: {$patient->full_name}, {$patient->age}, {$patient->gender}"
             . ($patient->blood_group ? ", blood group {$patient->blood_group}" : '');

        $allergies = $patient->patientAllergies;
        $L[] = 'ALLERGIES: ' . ($allergies->count()
            ? $allergies->map(fn ($a) => $a->allergen . ($a->severity && $a->severity !== 'unknown' ? " ({$a->severity})" : ''))->implode(', ')
            : 'none recorded');

        // Comorbidities from recorded diagnoses across encounters.
        $encounterIds = $patient->encounters()->pluck('id');
        $dx = Diagnosis::whereIn('encounter_id', $encounterIds)
            ->orderByDesc('id')->get()
            ->unique(fn ($d) => strtolower((string) $d->diagnosis_name))
            ->take(20);
        $L[] = 'COMORBIDITIES / DIAGNOSES: ' . ($dx->count()
            ? $dx->map(fn ($d) => $d->diagnosis_name . ($d->icd_code ? " [{$d->icd_code}]" : '') . ($d->status ? " ({$d->status})" : ''))->implode('; ')
            : 'none recorded');

        // Current medicines: recent prescription items + diary-recorded meds.
        $rxMeds = $patient->prescriptions()->with('items')->latest('id')->take(5)->get()
            ->flatMap(fn ($p) => $p->items)
            ->map(fn ($i) => trim($i->generic_name . ' ' . ($i->dose ?? '') . ' ' . ($i->frequency ?? '')))
            ->filter()->unique()->take(20);
        $diaryMeds = collect()
            ->merge(GlucoseLog::where('patient_id', $patient->id)->whereNotNull('medications')->latest('logged_at')->take(20)->pluck('medications'))
            ->merge(BpLog::where('patient_id', $patient->id)->whereNotNull('medications')->latest('logged_at')->take(20)->pluck('medications'))
            ->flatMap(fn ($m) => explode(',', $m))->map(fn ($m) => trim($m))->filter()->unique()->take(20);
        $meds = $rxMeds->merge($diaryMeds)->unique()->values();
        $L[] = 'CURRENT MEDICINES: ' . ($meds->count() ? $meds->implode('; ') : 'none recorded');

        // Diabetes data
        $glog = GlucoseLog::where('patient_id', $patient->id)->orderByDesc('logged_at')->get();
        $ga = GlucoseLog::analyze($glog);
        if ($ga['stats']) {
            $s = $ga['stats'];
            $L[] = "GLUCOSE SUMMARY (last {$s['windowDays']}d): avg {$s['avg']} mg/dL, {$s['inPct']}% in target, lows {$s['low']}, highs {$s['high']}, n={$s['count']}"
                 . ($s['estA1c'] ? ", est HbA1c {$s['estA1c']}%" : '');
        }
        $recentG = $glog->take(14)->map(fn ($l) => $l->typeShort() . ' ' . rtrim(rtrim(number_format((float) $l->value, 1), '0'), '.') . ($l->reading_type === 'hba1c' ? '%' : '') . ' (' . $l->logged_at->format('d/m H:i') . ')');
        if ($recentG->isNotEmpty()) $L[] = 'RECENT SUGARS: ' . $recentG->implode('; ');

        // BP data
        $blog = BpLog::where('patient_id', $patient->id)->orderByDesc('logged_at')->get();
        $ba = BpLog::analyze($blog);
        if ($ba['stats']) {
            $s = $ba['stats'];
            $L[] = "BP SUMMARY (last {$s['windowDays']}d): avg {$s['avgSys']}/{$s['avgDia']} mmHg, {$s['goalPct']}% under 140/90, stage-2 {$s['stage2']}, low {$s['low']}, n={$s['count']}";
        }
        $recentB = $blog->take(10)->map(fn ($l) => $l->reading() . ($l->pulse ? " (HR {$l->pulse})" : '') . ' [' . $l->logged_at->format('d/m H:i') . ']');
        if ($recentB->isNotEmpty()) $L[] = 'RECENT BP: ' . $recentB->implode('; ');

        // Weight trend
        $weight = HealthMetric::where('patient_id', $patient->id)->where('metric', 'weight')->orderBy('logged_at')->get();
        if ($wt = HealthMetric::weightTrend($weight)) {
            $dir = $wt['change'] < 0 ? 'lost ' . abs($wt['change']) : ($wt['change'] > 0 ? 'gained ' . $wt['change'] : 'no change');
            $L[] = "WEIGHT: now {$wt['latest']} kg ({$dir} kg over {$wt['days']}d, n={$wt['count']})";
        }

        // Latest vitals
        foreach (['heart_rate' => 'HEART RATE', 'spo2' => 'SpO2', 'stress' => 'STRESS'] as $m => $tag) {
            $row = HealthMetric::where('patient_id', $patient->id)->where('metric', $m)->orderByDesc('logged_at')->first();
            if ($row) $L[] = "{$tag}: {$row->value} " . $row->unit() . ' (' . $row->logged_at->format('d/m') . ')';
        }

        // Diet: recent foods + average daily calories (last 7 days)
        $diet = DietLog::where('patient_id', $patient->id)->where('logged_at', '>=', now()->subDays(7))->orderByDesc('logged_at')->get();
        if ($diet->isNotEmpty()) {
            $days = max(1, $diet->groupBy(fn ($d) => $d->logged_at->toDateString())->count());
            $avgKcal = (int) round($diet->sum('calories') / $days);
            $L[] = "DIET (last 7d): ~{$avgKcal} kcal/day avg over {$days} day(s)";
            $foods = $diet->take(20)->map(fn ($d) => trim(($d->meal ? ucfirst($d->meal) . ': ' : '') . $d->food . ($d->calories ? " ({$d->calories} kcal)" : '')));
            $L[] = 'RECENT FOODS: ' . $foods->implode('; ');
        }

        // Exercise: recent + average daily burn (last 7 days)
        $ex = ExerciseLog::where('patient_id', $patient->id)->where('logged_at', '>=', now()->subDays(7))->orderByDesc('logged_at')->get();
        if ($ex->isNotEmpty()) {
            $days = max(1, $ex->groupBy(fn ($e) => $e->logged_at->toDateString())->count());
            $avgBurn = (int) round($ex->sum('calories_burned') / $days);
            $acts = $ex->take(12)->map(fn ($e) => $e->activity . ($e->minutes ? " {$e->minutes}min" : '') . ($e->calories_burned ? " ({$e->calories_burned} kcal)" : ''));
            $L[] = "EXERCISE (last 7d): ~{$avgBurn} kcal/day avg; " . $acts->implode('; ');
        }

        return implode("\n", $L);
    }

    private function readEnvKey(string $key): ?string
    {
        $envFile = base_path('.env');
        if (!file_exists($envFile)) return null;
        foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            if (str_starts_with(trim($line), '#')) continue;
            if (str_contains($line, '=')) {
                [$k, $v] = explode('=', $line, 2);
                if (trim($k) === $key) return trim($v, " \t\n\r\0\x0B\"'");
            }
        }
        return null;
    }
}
