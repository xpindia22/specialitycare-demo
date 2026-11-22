<?php

namespace App\Http\Controllers;

use App\Models\ClinicalRule;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClinicalRuleController extends Controller
{
    private function rules(?ClinicalRule $rule = null): array
    {
        return [
            'name'        => ['required', 'string', 'max:140',
                               Rule::unique('clinical_rules', 'name')->ignore($rule?->id)],
            'category'    => ['required', Rule::in(array_keys(ClinicalRule::CATEGORIES))],
            'severity'    => ['required', Rule::in(ClinicalRule::SEVERITIES)],
            'match_type'  => ['required', Rule::in(ClinicalRule::MATCH_TYPES)],
            'metric'      => ['nullable', 'string', 'max:30'],
            'comparator'  => ['nullable', Rule::in(ClinicalRule::COMPARATORS)],
            'value_num'   => ['nullable', 'numeric'],
            'keywords'    => ['nullable', 'string', 'max:500'],
            'keyword_fields' => ['nullable', 'string', 'max:255'],
            'gender'      => ['nullable', Rule::in(['male', 'female'])],
            'age_min'     => ['nullable', 'integer', 'min:0', 'max:150'],
            'age_max'     => ['nullable', 'integer', 'min:0', 'max:150'],
            'suggestion'  => ['required', 'string', 'max:600'],
            'is_active'   => ['nullable', 'boolean'],
        ];
    }

    /** Enforce that the fields required by the chosen match_type are present. */
    private function validateByType(array $data): array
    {
        $errors = [];
        if ($data['match_type'] === 'vital') {
            if (empty($data['metric']) || !in_array($data['metric'], ClinicalRule::VITAL_METRICS, true))
                $errors['metric'] = 'Pick a vital for a vital rule.';
            if (empty($data['comparator'])) $errors['comparator'] = 'Comparator required.';
            if (!isset($data['value_num']) || $data['value_num'] === null) $errors['value_num'] = 'Threshold value required.';
        } elseif ($data['match_type'] === 'demographic') {
            $data['metric'] = 'age';
            if (empty($data['comparator'])) $errors['comparator'] = 'Comparator required.';
            if (!isset($data['value_num']) || $data['value_num'] === null) $errors['value_num'] = 'Age threshold required.';
        } elseif ($data['match_type'] === 'keyword') {
            if (empty(trim((string) ($data['keywords'] ?? '')))) $errors['keywords'] = 'Add at least one keyword.';
            // keyword rules don't use the numeric condition
            $data['metric'] = null; $data['comparator'] = null; $data['value_num'] = null;
        }
        return [$data, $errors];
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());
        [$data, $errors] = $this->validateByType($data);
        if ($errors) return back()->with('settings_tab', 'rules')->withErrors($errors)->withInput();

        $data['is_active']  = $request->boolean('is_active', true);
        $data['sort_order'] = (ClinicalRule::max('sort_order') ?? 0) + 1;
        ClinicalRule::create($data);

        return back()->with('success', 'Clinical rule added.')->with('settings_tab', 'rules');
    }

    public function update(Request $request, ClinicalRule $clinical_rule)
    {
        $data = $request->validate($this->rules($clinical_rule));
        [$data, $errors] = $this->validateByType($data);
        if ($errors) return back()->with('settings_tab', 'rules')->withErrors($errors)->withInput();

        $data['is_active'] = $request->boolean('is_active', true);
        $clinical_rule->update($data);

        return back()->with('success', 'Clinical rule updated.')->with('settings_tab', 'rules');
    }

    public function toggle(ClinicalRule $clinical_rule)
    {
        $clinical_rule->update(['is_active' => ! $clinical_rule->is_active]);
        return back()->with('success', 'Clinical rule ' . ($clinical_rule->is_active ? 'enabled' : 'disabled') . '.')
                     ->with('settings_tab', 'rules');
    }

    public function destroy(ClinicalRule $clinical_rule)
    {
        $clinical_rule->delete();
        return back()->with('success', 'Clinical rule removed.')->with('settings_tab', 'rules');
    }
}
