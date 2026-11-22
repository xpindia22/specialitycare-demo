<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClinicalRule extends Model
{
    protected $fillable = [
        'name', 'category', 'severity', 'match_type',
        'metric', 'comparator', 'value_num',
        'keywords', 'keyword_fields',
        'gender', 'age_min', 'age_max',
        'suggestion', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'value_num'  => 'float',
        'age_min'    => 'integer',
        'age_max'    => 'integer',
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
    ];

    /** Category => [label, icon, color] — used for grouping + popup styling. */
    public const CATEGORIES = [
        'symptom'    => ['label' => 'Symptom / Work-up', 'icon' => 'fa-notes-medical',        'color' => '#0ab4b4'],
        'vitals'     => ['label' => 'Vitals',            'icon' => 'fa-heart-pulse',          'color' => '#ef4444'],
        'lab'        => ['label' => 'Lab',               'icon' => 'fa-flask',                'color' => '#8b5cf6'],
        'screening'  => ['label' => 'Screening',         'icon' => 'fa-magnifying-glass-chart','color' => '#3b82f6'],
        'vaccine'    => ['label' => 'Vaccine',           'icon' => 'fa-syringe',              'color' => '#22c55e'],
        'counseling' => ['label' => 'Counseling',        'icon' => 'fa-comments',             'color' => '#f59e0b'],
    ];

    public const SEVERITIES  = ['info', 'warning', 'urgent'];
    public const MATCH_TYPES  = ['vital', 'demographic', 'keyword'];
    public const COMPARATORS  = ['gte', 'lte', 'gt', 'lt', 'eq'];

    /** Numeric metrics that can be read from an encounter's vitals. */
    public const VITAL_METRICS = [
        'bmi', 'bp_systolic', 'bp_diastolic', 'blood_glucose',
        'temperature', 'spo2', 'pulse', 'respiratory_rate', 'weight', 'height',
    ];

    public const DEMOGRAPHIC_METRICS = ['age'];

    /** Text fields scanned for keywords when a rule leaves keyword_fields blank. */
    public const DEFAULT_KEYWORD_FIELDS = [
        'chief_complaint', 'history_of_present_illness',
        'past_medical_history', 'social_history', 'examination_findings',
    ];

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }

    public function categoryMeta(): array
    {
        return self::CATEGORIES[$this->category] ?? self::CATEGORIES['symptom'];
    }

    public function keywordFieldList(): array
    {
        $raw = trim((string) $this->keyword_fields);
        if ($raw === '') return self::DEFAULT_KEYWORD_FIELDS;
        return array_values(array_filter(array_map('trim', explode(',', $raw))));
    }

    public function keywordList(): array
    {
        return array_values(array_filter(array_map(
            fn ($k) => mb_strtolower(trim($k)),
            explode(',', (string) $this->keywords)
        )));
    }
}
