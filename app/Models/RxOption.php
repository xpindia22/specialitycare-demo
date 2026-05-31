<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Editable prescription dropdown option (dose/frequency/route/duration/instruction). */
class RxOption extends Model
{
    protected $fillable = ['field', 'value', 'label', 'sort'];

    public const FIELDS = ['dose', 'frequency', 'route', 'duration', 'instruction'];

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
