<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Editable prescription dropdown option (dose/frequency/route/duration/instruction). */
class RxOption extends Model
{
    protected $fillable = ['field', 'value', 'label', 'sort', 'is_pinned'];

    protected $casts = ['is_pinned' => 'boolean'];

    public const FIELDS = ['dose', 'frequency', 'route', 'duration', 'instruction', 'diabetes_med', 'glucose_symptom', 'bp_med', 'bp_symptom', 'food_item', 'exercise_item'];

    /** Drug fields whose diary picker supports pinning drugs to the top. */
    public const PINNABLE_FIELDS = ['diabetes_med', 'bp_med', 'food_item'];

    /** Pinned drug values for a field, in top-of-list order. */
    public static function pinned(string $field): array
    {
        return static::where('field', $field)->where('is_pinned', true)
            ->orderBy('sort')->orderBy('id')->pluck('value')->all();
    }

    /** All options grouped by field → [['value'=>, 'label'=>], …], for the form. */
    public static function grouped(): array
    {
        $out = array_fill_keys(self::FIELDS, []);
        foreach (static::orderBy('sort')->orderBy('id')->get() as $o) {
            $out[$o->field][] = ['value' => $o->value, 'label' => $o->label ?: $o->value];
        }
        return $out;
    }
}
