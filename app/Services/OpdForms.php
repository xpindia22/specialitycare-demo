<?php

namespace App\Services;

use App\Models\Encounter;
use App\Models\EncounterForm;

/**
 * Speciality visit forms defined in config/opd_forms.php.
 *
 * A field gives one stored key, or key_r / key_l when 'eyes' is set; a
 * refraction field gives key_{r,l}_{sph,cyl,axis,add,va}.
 */
class OpdForms
{
    const REFRACTION_PARTS = ['sph' => 'Sph', 'cyl' => 'Cyl', 'axis' => 'Axis', 'add' => 'Add', 'va' => 'VA'];
    const EYES = ['r' => 'Right (OD)', 'l' => 'Left (OS)'];

    public static function all(): array
    {
        return config('opd_forms', []);
    }

    public static function get(string $form): ?array
    {
        return self::all()[$form] ?? null;
    }

    /** Stored keys of one field. */
    public static function keysOf(string $key, array $f): array
    {
        if ($f['type'] === 'refraction') {
            $parts = array_keys(self::REFRACTION_PARTS);
            if (empty($f['add'])) $parts = array_diff($parts, ['add']);
            $out = [];
            foreach (array_keys(self::EYES) as $eye) foreach ($parts as $p) $out[] = "{$key}_{$eye}_{$p}";
            return $out;
        }
        return !empty($f['eyes']) ? ["{$key}_r", "{$key}_l"] : [$key];
    }

    /** Every stored key of a form => its field definition. */
    public static function fields(string $form): array
    {
        $out = [];
        foreach (self::get($form)['sections'] ?? [] as $sec) {
            foreach ($sec['fields'] as $key => $f) {
                foreach (self::keysOf($key, $f) as $k) $out[$k] = $f;
            }
        }
        return $out;
    }

    /** Keep only known keys; checks become arrays of allowed options, everything else trimmed strings. */
    public static function clean(string $form, array $input): array
    {
        $data = [];
        foreach (self::fields($form) as $k => $f) {
            $v = $input[$k] ?? null;
            if ($f['type'] === 'checks') {
                $v = array_values(array_intersect((array) $v, $f['options']));
                if ($v) $data[$k] = $v;
                continue;
            }
            if (is_array($v)) continue;
            $v = trim((string) $v);
            if ($v === '') continue;
            $data[$k] = mb_substr($v, 0, $f['type'] === 'textarea' ? 2000 : 120);
        }
        return $data;
    }

    /** Department codes this visit belongs to: token, appointment, then the doctor's departments. */
    public static function departmentCodes(Encounter $e): array
    {
        $e->loadMissing(['opdToken.department', 'appointment.department', 'doctor.department', 'doctor.departments']);
        $codes = [
            $e->opdToken?->department?->code,
            $e->appointment?->department?->code,
            $e->doctor?->department?->code,
        ];
        foreach ($e->doctor?->departments ?? [] as $d) $codes[] = $d->code;
        return array_values(array_unique(array_filter($codes)));
    }

    /**
     * Forms shown as tabs on this visit: the visit's own department first,
     * then the doctor's other departments, then any form that has data.
     */
    public static function forEncounter(Encounter $e): array
    {
        $saved = $e->relationLoaded('forms') ? $e->forms : $e->forms()->get();
        $codes = array_merge(self::departmentCodes($e), $saved->pluck('form')->all());
        return array_values(array_unique(array_filter($codes, fn ($c) => self::get($c))));
    }

    /** The patient's last saved form of this kind before this visit (for "Copy last visit"). */
    public static function previous(Encounter $e, string $form): ?EncounterForm
    {
        return EncounterForm::with('encounter')
            ->where('patient_id', $e->patient_id)->where('form', $form)
            ->where('encounter_id', '!=', $e->id)
            ->whereHas('encounter', fn ($q) => $q->where('encounter_date', '<=', $e->encounter_date))
            ->latest('updated_at')->first();
    }

    /** Filled fields for print / summary: [section title => [[label, value-string], …]]. */
    public static function summary(string $form, array $data): array
    {
        $out = [];
        foreach (self::get($form)['sections'] ?? [] as $sec) {
            $rows = [];
            foreach ($sec['fields'] as $key => $f) {
                if ($f['type'] === 'refraction') continue;   // printed as a grid
                $unit = isset($f['unit']) ? ' ' . $f['unit'] : '';
                $fmt = fn ($v) => is_array($v) ? implode(', ', $v) : $v . $unit;
                if (!empty($f['eyes'])) {
                    $r = $data["{$key}_r"] ?? null; $l = $data["{$key}_l"] ?? null;
                    if ($r === null && $l === null) continue;
                    $rows[] = [$f['label'], 'R: ' . ($r !== null ? $fmt($r) : '—') . '   L: ' . ($l !== null ? $fmt($l) : '—')];
                } elseif (isset($data[$key])) {
                    $rows[] = [$f['label'], $fmt($data[$key])];
                }
            }
            if ($rows) $out[$sec['title']] = $rows;
        }
        return $out;
    }

    /** Refraction grids that have any value: [label => ['r' => [part => v], 'l' => …, 'parts' => […]]]. */
    public static function refractions(string $form, array $data): array
    {
        $out = [];
        foreach (self::get($form)['sections'] ?? [] as $sec) {
            foreach ($sec['fields'] as $key => $f) {
                if ($f['type'] !== 'refraction') continue;
                $parts = self::REFRACTION_PARTS;
                if (empty($f['add'])) unset($parts['add']);
                $grid = ['parts' => $parts, 'key' => $key];
                $any = false;
                foreach (array_keys(self::EYES) as $eye) {
                    foreach (array_keys($parts) as $p) {
                        $grid[$eye][$p] = $data["{$key}_{$eye}_{$p}"] ?? null;
                        $any = $any || $grid[$eye][$p] !== null;
                    }
                }
                if ($any) $out[$f['label']] = $grid;
            }
        }
        return $out;
    }
}
