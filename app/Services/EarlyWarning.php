<?php

namespace App\Services;

use App\Models\IpdVitals;

/**
 * NEWS2 (Royal College of Physicians, 2017).
 *
 * Score each parameter 0–3 and add up. Missing parameters score 0 and the
 * result is marked partial. Temperature is stored in °F and converted here.
 * Not validated for patients under 16 or in pregnancy.
 */
class EarlyWarning
{
    const LEVELS = [
        'low'    => ['label' => 'Low',          'color' => '#15803d', 'bg' => '#dcfce7', 'action' => 'Continue routine monitoring (at least every 12 h; 4–6 h if score 1–4).'],
        'single' => ['label' => 'Low–medium',   'color' => '#a16207', 'bg' => '#fef9c3', 'action' => 'A single parameter scores 3: urgent ward-based review by a doctor. Monitor at least hourly.'],
        'medium' => ['label' => 'Medium',       'color' => '#c2410c', 'bg' => '#ffedd5', 'action' => 'Urgent review by a doctor competent in acute illness. Monitor at least hourly.'],
        'high'   => ['label' => 'High',         'color' => '#b91c1c', 'bg' => '#fee2e2', 'action' => 'Emergency response: critical-care team now. Continuous monitoring.'],
    ];

    /** @return array{score:int, parts:array<string,int>, partial:bool, level:string} */
    public static function news2(IpdVitals|array $v): array
    {
        $g = fn ($k) => is_array($v) ? ($v[$k] ?? null) : $v->{$k};
        $parts = [];
        $missing = 0;

        $rr = $g('respiration_rate');
        if ($rr === null || $rr === '') $missing++;
        else $parts['RR'] = $rr <= 8 ? 3 : ($rr <= 11 ? 1 : ($rr <= 20 ? 0 : ($rr <= 24 ? 2 : 3)));

        $device = $g('o2_device');
        $onO2 = $device && $device !== 'air';
        $parts['O₂'] = $onO2 ? 2 : 0;

        $spo2 = $g('spo2');
        if ($spo2 === null || $spo2 === '') $missing++;
        elseif ($g('spo2_scale2')) {
            // Scale 2 — hypercapnic respiratory failure, target 88–92 %
            if ($spo2 <= 83) $parts['SpO₂'] = 3;
            elseif ($spo2 <= 85) $parts['SpO₂'] = 2;
            elseif ($spo2 <= 87) $parts['SpO₂'] = 1;
            elseif ($spo2 <= 92 || !$onO2) $parts['SpO₂'] = 0;
            elseif ($spo2 <= 94) $parts['SpO₂'] = 1;
            elseif ($spo2 <= 96) $parts['SpO₂'] = 2;
            else $parts['SpO₂'] = 3;
        } else {
            $parts['SpO₂'] = $spo2 <= 91 ? 3 : ($spo2 <= 93 ? 2 : ($spo2 <= 95 ? 1 : 0));
        }

        $sbp = $g('bp_systolic');
        if ($sbp === null || $sbp === '') $missing++;
        else $parts['SBP'] = $sbp <= 90 ? 3 : ($sbp <= 100 ? 2 : ($sbp <= 110 ? 1 : ($sbp <= 219 ? 0 : 3)));

        $hr = $g('pulse');
        if ($hr === null || $hr === '') $missing++;
        else $parts['Pulse'] = $hr <= 40 ? 3 : ($hr <= 50 ? 1 : ($hr <= 90 ? 0 : ($hr <= 110 ? 1 : ($hr <= 130 ? 2 : 3))));

        $avpu = $g('avpu');
        if (!$avpu) $missing++;
        else $parts['ACVPU'] = $avpu === 'A' ? 0 : 3;

        $tf = $g('temperature');
        if ($tf === null || $tf === '') $missing++;
        else {
            $c = round(((float) $tf - 32) * 5 / 9, 1);
            $parts['Temp'] = $c <= 35.0 ? 3 : ($c <= 36.0 ? 1 : ($c <= 38.0 ? 0 : ($c <= 39.0 ? 1 : 2)));
        }

        $score = array_sum($parts);
        return ['score' => $score, 'parts' => $parts, 'partial' => $missing > 0, 'level' => self::level($score, $parts)];
    }

    public static function level(?int $score, array $parts = []): string
    {
        if ($score === null) return 'low';
        if ($score >= 7) return 'high';
        if ($score >= 5) return 'medium';
        if (in_array(3, $parts, true)) return 'single';
        return 'low';
    }

    /** Level from a stored vitals row (parts are recomputed for the "single 3" rule). */
    public static function levelOf(?IpdVitals $v): ?string
    {
        if (!$v || $v->news2 === null) return null;
        return self::news2($v)['level'];
    }
}
