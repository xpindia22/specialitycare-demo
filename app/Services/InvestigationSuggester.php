<?php

namespace App\Services;

use App\Models\Encounter;
use App\Models\LabPanel;
use App\Models\LabTest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Suggests blood tests / investigations for an encounter once a diagnosis is
 * saved. Two layers:
 *  1. Rules (config/investigation_suggestions.php) — fast, free, deterministic
 *     diagnosis -> panels/tests matching, filtered through LabPanel's own
 *     age/gender scoping (matchesPatient()).
 *  2. AI (Claude, same integration as AiSuggestionController) — given the
 *     rule-based baseline plus the patient's age, chief complaint and CURRENT
 *     MEDICINES, adds any extra tests specifically warranted by drug
 *     side-effect/monitoring needs or nuanced clinical reasoning the rules
 *     don't cover. Constrained to only pick from the real lab catalog so
 *     results always resolve via LabController's existing name-based lookup.
 *
 * Never auto-places an order — this only returns suggested names; the doctor
 * places (or edits/dismisses) the order from the encounter's Labs tab exactly
 * like the existing template-driven "Suggested Lab Order" card.
 */
class InvestigationSuggester
{
    /**
     * The only procedure names the suggester (rules or AI) is allowed to
     * return, mapped to the route that creates that record for a patient —
     * these aren't lab tests, so they render as links to the relevant
     * module's create form instead of an item on the lab order form.
     */
    public const PROCEDURE_ROUTES = [
        'ECG' => 'ecg.create',
        'Echo' => 'echo.create',
        'Chest X-ray' => 'radiology.create',
    ];

    /** Fast/free rule-based baseline. Always available, no external calls. */
    public function suggestFromRules(Encounter $encounter): array
    {
        $patient = $encounter->patient;
        $diagnoses = $encounter->diagnoses;

        $panelNames = [];
        $testNames = [];
        $procedureNames = [];

        foreach (config('investigation_suggestions', []) as $rule) {
            $matched = $diagnoses->contains(function ($d) use ($rule) {
                $name = strtolower((string) $d->diagnosis_name);
                $code = strtolower((string) $d->icd_code);
                foreach ($rule['keywords'] as $kw) {
                    // Word-boundary match (not plain substring) so short keywords like
                    // "dm", "ra", "uti" don't false-positive inside unrelated words, and
                    // phrases match as a whole unit regardless of leading/trailing text.
                    if ($name !== '' && preg_match('/\b' . preg_quote($kw, '/') . '\b/i', $name)) return true;
                    if ($code !== '' && str_starts_with($code, strtolower($kw))) return true;
                }
                return false;
            });
            if (!$matched) continue;
            $panelNames = array_merge($panelNames, $rule['panels'] ?? []);
            $testNames = array_merge($testNames, $rule['tests'] ?? []);
            $procedureNames = array_merge($procedureNames, $rule['procedures'] ?? []);
        }

        $panelNames = array_values(array_unique($panelNames));
        $testNames = array_values(array_unique($testNames));
        $procedureNames = array_values(array_intersect(array_unique($procedureNames), array_keys(self::PROCEDURE_ROUTES)));

        // Drop panels that don't fit this patient's age/gender (e.g. Antenatal
        // Profile for a male patient) using the model's own scoping logic.
        if ($panelNames && $patient) {
            $panelNames = LabPanel::whereIn('name', $panelNames)->get()
                ->filter(fn ($p) => $p->matchesPatient($patient))
                ->pluck('name')->values()->all();
        }

        return ['panels' => $panelNames, 'tests' => $testNames, 'procedures' => $procedureNames];
    }

    /**
     * AI layer — best-effort. Returns null (not an empty array) if the AI is
     * unavailable or the call fails, so the caller can distinguish "AI added
     * nothing" from "AI wasn't consulted" and degrade gracefully to rules-only.
     */
    public function suggestFromAi(Encounter $encounter, array $ruleTests, array $rulePanels, array $ruleProcedures = []): ?array
    {
        $apiKey = env('ANTHROPIC_API_KEY') ?: $this->readEnvKey('ANTHROPIC_API_KEY');
        if (!$apiKey) return null;

        $patient = $encounter->patient;
        if (!$patient) return null;

        $context = $this->buildContext($encounter, $ruleTests, $rulePanels, $ruleProcedures);
        $catalog = $this->catalogNames();
        $procedureCatalog = array_keys(self::PROCEDURE_ROUTES);

        $system = implode("\n", [
            'You are a clinical decision-support assistant inside SpecialityCare HMS, advising a qualified doctor which additional investigations (beyond what a rule-based diagnosis matcher already suggested) are warranted.',
            'The most common reason to add something here is MONITORING FOR A CURRENT MEDICINE\'S SIDE EFFECTS, e.g.: statins -> Liver Function Test; ACE inhibitors/ARBs -> Renal Function Test + Electrolyte Panel; diuretics -> Electrolyte Panel + Renal Function Test; metformin -> Renal Function Test + Vitamin B12; amiodarone -> Thyroid Profile Total + Liver Function Test; warfarin -> Coagulation Profile; long-term NSAIDs -> Renal Function Test; methotrexate/DMARDs -> CBC + Liver Function Test + Renal Function Test; digoxin -> Electrolyte Panel + Renal Function Test.',
            'You may also add tests justified by the chief complaint or diagnosis nuance the rules missed, and may consider the patient\'s age where relevant.',
            'Besides lab panels/tests, you may also suggest PROCEDURES from this fixed list only: ' . implode(', ', $procedureCatalog) . '. These are essential first-line investigations for cardiac/vascular presentations — e.g. any suspected or confirmed myocardial infarction, acute coronary syndrome, chest pain, arrhythmia (including atrial fibrillation), heart failure, or stroke/TIA should normally include ECG and Echo unless the rule-based baseline already covers them. Chest X-ray is appropriate for heart failure or respiratory presentations.',
            'Base every reason strictly on the data given below — do not infer or invent clinical details (findings, severity, prior events) that are not explicitly stated.',
            'Do NOT repeat anything already in the rule-based baseline given below.',
            'You MUST only choose panel/test names that appear EXACTLY (character-for-character) in the CATALOG list provided, and procedures only from the fixed procedures list — never invent a name.',
            'If nothing further is warranted, return empty arrays. Be conservative on labs (only add what has real clinical justification, not a generic screen) — but do not be conservative about ECG/Echo for cardiac diagnoses; those are standard of care, not optional extras.',
            'Respond with ONLY a JSON object, no prose, no markdown fences: {"panels": ["..."], "tests": ["..."], "procedures": ["..."], "reasons": {"<name>": "short reason"}}',
        ]);

        $catalogText = "PANELS CATALOG:\n" . implode('; ', $catalog['panels']) . "\n\nTESTS CATALOG:\n" . implode('; ', $catalog['tests']);
        $userMsg = $context . "\n\n" . $catalogText;

        try {
            $response = Http::withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])->timeout(30)->post('https://api.anthropic.com/v1/messages', [
                'model' => 'claude-sonnet-4-6',
                'max_tokens' => 600,
                'system' => $system,
                'messages' => [['role' => 'user', 'content' => $userMsg]],
            ]);
        } catch (\Throwable $e) {
            return null;
        }

        if (!$response->successful()) return null;

        $text = collect($response->json('content', []))->where('type', 'text')->pluck('text')->implode('');
        $text = trim($text);
        // Strip accidental markdown fences even though the prompt asks against them.
        $text = preg_replace('/^```(?:json)?|```$/m', '', $text);
        $data = json_decode(trim($text), true);
        if (!is_array($data)) return null;

        $panels = array_values(array_intersect((array) ($data['panels'] ?? []), $catalog['panels']));
        $tests = array_values(array_intersect((array) ($data['tests'] ?? []), $catalog['tests']));
        $procedures = array_values(array_intersect((array) ($data['procedures'] ?? []), $procedureCatalog));
        $reasons = is_array($data['reasons'] ?? null) ? $data['reasons'] : [];

        // Age/gender re-check for AI-picked panels too.
        if ($panels) {
            $panels = LabPanel::whereIn('name', $panels)->get()
                ->filter(fn ($p) => $p->matchesPatient($patient))
                ->pluck('name')->values()->all();
        }

        return ['panels' => $panels, 'tests' => $tests, 'procedures' => $procedures, 'reasons' => $reasons];
    }

    private function buildContext(Encounter $encounter, array $ruleTests, array $rulePanels, array $ruleProcedures = []): string
    {
        $patient = $encounter->patient;
        $L = [];
        $L[] = "PATIENT: {$patient->age}, {$patient->gender}";

        $dx = $encounter->diagnoses;
        $L[] = 'DIAGNOSES (this encounter): ' . ($dx->count()
            ? $dx->map(fn ($d) => $d->diagnosis_name . ($d->icd_code ? " [{$d->icd_code}]" : ''))->implode('; ')
            : 'none');

        // Quill stores each line/paragraph as its own <p>/<div> — strip_tags() alone
        // would run them together with no separator (e.g. "HypertensionSTEMI EF 40%").
        // Turn block/line breaks into a space first, then collapse whitespace.
        $ccHtml = (string) $encounter->chief_complaint;
        $ccHtml = preg_replace('/<\/(p|div|li|br)\s*\/?>/i', ' ', $ccHtml);
        $cc = trim(preg_replace('/\s+/', ' ', strip_tags($ccHtml)));
        $L[] = 'CHIEF COMPLAINT: ' . (Str::limit($cc, 300) ?: 'none recorded');

        $meds = $patient->prescriptions()->with('items')->latest('id')->take(5)->get()
            ->flatMap(fn ($p) => $p->items)
            ->map(fn ($i) => trim($i->generic_name . ' ' . ($i->dose ?? '') . ' ' . ($i->frequency ?? '')))
            ->filter()->unique()->take(20);
        $L[] = 'CURRENT MEDICINES: ' . ($meds->count() ? $meds->implode('; ') : 'none recorded');

        $L[] = 'RULE-BASED BASELINE ALREADY SUGGESTED — panels: ' . (implode(', ', $rulePanels) ?: 'none')
            . '; tests: ' . (implode(', ', $ruleTests) ?: 'none')
            . '; procedures: ' . (implode(', ', $ruleProcedures) ?: 'none');

        return implode("\n", $L);
    }

    private function catalogNames(): array
    {
        return [
            'panels' => LabPanel::where('is_active', true)->pluck('name')->all(),
            'tests' => LabTest::where('is_active', true)->pluck('name')->all(),
        ];
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
