<?php

namespace App\Services;

use App\Models\ClinicalRule;
use App\Models\Encounter;
use App\Models\Patient;
use Illuminate\Support\Collection;

/**
 * Evaluates the active clinical_rules against a patient's demographics + the
 * current encounter signals (vitals + free-text complaints / history / exam)
 * and returns advisory suggestions — the SpecialityCare equivalent of
 * OpenEMR's Clinical Reminders. Advisory only; never auto-acts.
 */
class ClinicalRuleEngine
{
    /**
     * Build the evaluation context from a saved Encounter (+ its patient).
     * @return array{age:?int,gender:?string,vitals:array,text:array}
     */
    public function contextFromEncounter(Encounter $encounter): array
    {
        $p = $encounter->patient;
        $vitals = [];
        foreach (ClinicalRule::VITAL_METRICS as $m) {
            $vitals[$m] = is_numeric($encounter->$m ?? null) ? (float) $encounter->$m : null;
        }
        $text = [];
        foreach (ClinicalRule::DEFAULT_KEYWORD_FIELDS as $f) {
            $text[$f] = self::plain($encounter->$f ?? '');
        }
        // a couple of extra text fields some rules may name explicitly
        foreach (['family_history', 'impression', 'plan', 'review_of_systems'] as $f) {
            $text[$f] = self::plain($encounter->$f ?? '');
        }

        return [
            'age'    => $p ? (int) $p->age_years : null,
            'gender' => $p ? self::sex($p->gender) : null,
            'vitals' => $vitals,
            'text'   => $text,
        ];
    }

    /**
     * Merge live (unsaved) values posted from the encounter screen over the
     * patient's demographics. Used by the live reminders endpoint.
     */
    public function contextFromInput(?Patient $patient, array $input): array
    {
        $vitals = [];
        foreach (ClinicalRule::VITAL_METRICS as $m) {
            $vitals[$m] = isset($input[$m]) && is_numeric($input[$m]) ? (float) $input[$m] : null;
        }
        // derive BMI from weight/height if not sent directly
        if (($vitals['bmi'] ?? null) === null && !empty($vitals['weight']) && !empty($vitals['height'])) {
            $h = $vitals['height'] / 100;
            if ($h > 0) $vitals['bmi'] = round($vitals['weight'] / ($h * $h), 1);
        }

        $text = [];
        $fields = array_unique(array_merge(
            ClinicalRule::DEFAULT_KEYWORD_FIELDS,
            ['family_history', 'impression', 'plan', 'review_of_systems']
        ));
        foreach ($fields as $f) {
            $text[$f] = self::plain($input[$f] ?? '');
        }

        $age = isset($input['age']) && is_numeric($input['age'])
            ? (int) $input['age']
            : ($patient ? (int) $patient->age_years : null);

        return [
            'age'    => $age,
            'gender' => self::sex($input['gender'] ?? ($patient?->gender)),
            'vitals' => $vitals,
            'text'   => $text,
        ];
    }

    /**
     * Run every active rule against the context.
     * @return array<int,array{name:string,category:string,severity:string,suggestion:string,icon:string,color:string,category_label:string}>
     */
    public function evaluate(array $ctx): array
    {
        $rules = ClinicalRule::active()->orderBy('sort_order')->orderBy('id')->get();
        $out = [];
        foreach ($rules as $rule) {
            if (!$this->scopeMatches($rule, $ctx)) continue;
            if (!$this->conditionMatches($rule, $ctx)) continue;

            $meta  = $rule->categoryMeta();
            $out[] = [
                'name'           => $rule->name,
                'category'       => $rule->category,
                'category_label' => $meta['label'],
                'icon'           => $meta['icon'],
                'color'          => $meta['color'],
                'severity'       => $rule->severity,
                'suggestion'     => $rule->suggestion,
            ];
        }
        return $this->sortBySeverity($out);
    }

    /** Convenience: evaluate directly from an encounter. */
    public function forEncounter(Encounter $encounter): array
    {
        return $this->evaluate($this->contextFromEncounter($encounter));
    }

    // ---- matching helpers ----

    /** Optional extra scoping filters (gender / age range) that must all hold. */
    private function scopeMatches(ClinicalRule $rule, array $ctx): bool
    {
        if ($rule->gender && $ctx['gender'] && $rule->gender !== $ctx['gender']) return false;
        $age = $ctx['age'];
        if ($rule->age_min !== null && ($age === null || $age < $rule->age_min)) return false;
        if ($rule->age_max !== null && ($age === null || $age > $rule->age_max)) return false;
        return true;
    }

    private function conditionMatches(ClinicalRule $rule, array $ctx): bool
    {
        switch ($rule->match_type) {
            case 'vital':
                $val = $ctx['vitals'][$rule->metric] ?? null;
                return $val !== null && $this->compare((float) $val, $rule->comparator, (float) $rule->value_num);

            case 'demographic':
                if ($rule->metric === 'age') {
                    $age = $ctx['age'];
                    return $age !== null && $this->compare((float) $age, $rule->comparator, (float) $rule->value_num);
                }
                return false; // gender-only rules are expressed through scope filters

            case 'keyword':
                $blob = '';
                foreach ($rule->keywordFieldList() as $f) {
                    $blob .= ' ' . ($ctx['text'][$f] ?? '');
                }
                $blob = mb_strtolower($blob);
                foreach ($rule->keywordList() as $kw) {
                    if ($kw !== '' && mb_strpos($blob, $kw) !== false) return true;
                }
                return false;
        }
        return false;
    }

    private function compare(float $a, ?string $op, float $b): bool
    {
        return match ($op) {
            'gte'   => $a >= $b,
            'lte'   => $a <= $b,
            'gt'    => $a >  $b,
            'lt'    => $a <  $b,
            'eq'    => $a == $b,
            default => false,
        };
    }

    private function sortBySeverity(array $out): array
    {
        $rank = ['urgent' => 0, 'warning' => 1, 'info' => 2];
        usort($out, fn ($x, $y) => ($rank[$x['severity']] ?? 3) <=> ($rank[$y['severity']] ?? 3));
        return $out;
    }

    /** Strip HTML (Quill content) and collapse whitespace to plain text. */
    private static function plain($html): string
    {
        $t = strip_tags((string) $html);
        $t = html_entity_decode($t, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return trim(preg_replace('/\s+/', ' ', $t));
    }

    /** Normalise gender strings to male|female|null. */
    private static function sex($g): ?string
    {
        $g = mb_strtolower(trim((string) $g));
        if ($g === '') return null;
        if (str_starts_with($g, 'm')) return 'male';
        if (str_starts_with($g, 'f')) return 'female';
        return $g;
    }
}
