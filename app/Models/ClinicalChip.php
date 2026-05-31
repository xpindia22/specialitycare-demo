<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClinicalChip extends Model
{
    protected $fillable = ['field_key', 'text', 'sort_order', 'is_active'];

    const FIELDS = [
        'chief_complaint'            => ['label' => 'Chief Complaint',            'icon' => 'fa-comment-medical', 'color' => '#0ab4b4'],
        'history_of_present_illness' => ['label' => 'History of Present Illness', 'icon' => 'fa-clock-rotate-left', 'color' => '#3b82f6'],
        'examination_findings'       => ['label' => 'Examination Findings',       'icon' => 'fa-stethoscope',     'color' => '#8b5cf6'],
        'impression'                 => ['label' => 'Impression',                 'icon' => 'fa-lightbulb',       'color' => '#f59e0b'],
        'plan'                       => ['label' => 'Plan',                       'icon' => 'fa-list-check',      'color' => '#22c55e'],
    ];

    public function scopeActive($q) { return $q->where('is_active', true); }

    public static function groupedForJs(): array {
        $out = array_fill_keys(array_keys(self::FIELDS), []);
        foreach (self::active()->orderBy('field_key')->orderBy('sort_order')->orderBy('id')->get() as $chip) {
            if (isset($out[$chip->field_key])) $out[$chip->field_key][] = $chip->text;
        }
        return $out;
    }
}
