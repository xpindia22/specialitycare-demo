<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * A single numeric health measurement (weight, heart rate, SpO2, stress, …)
 * for a patient. Patient-scoped time-series — no encounter required.
 */
class HealthMetric extends Model
{
    protected $fillable = ['patient_id', 'logged_at', 'metric', 'value', 'notes', 'recorded_by'];

    protected $casts = [
        'logged_at' => 'datetime',
        'value'     => 'decimal:2',
    ];

    /** Supported metrics with display config + normal ranges. */
    public const METRICS = [
        'weight'           => ['label' => 'Weight',     'unit' => 'kg',  'icon' => 'fa-weight-scale',     'min' => 20, 'max' => 400, 'normal' => null,        'step' => '0.1'],
        'heart_rate'       => ['label' => 'Heart rate', 'unit' => 'bpm', 'icon' => 'fa-heart-pulse',      'min' => 30, 'max' => 250, 'normal' => [60, 100],   'step' => '1'],
        'spo2'             => ['label' => 'SpO₂',        'unit' => '%',   'icon' => 'fa-lungs',            'min' => 50, 'max' => 100, 'normal' => [95, 100],   'step' => '1'],
        'stress'           => ['label' => 'Stress',     'unit' => '/100','icon' => 'fa-brain',            'min' => 0,  'max' => 100, 'normal' => [0, 50],     'step' => '1'],
        'temperature'      => ['label' => 'Temperature','unit' => '°F',  'icon' => 'fa-temperature-half', 'min' => 90, 'max' => 110, 'normal' => [97, 99.5],  'step' => '0.1'],
        'respiratory_rate' => ['label' => 'Resp. rate', 'unit' => '/min','icon' => 'fa-wind',             'min' => 5,  'max' => 60,  'normal' => [12, 20],    'step' => '1'],
    ];

    public function patient()    { return $this->belongsTo(Patient::class); }
    public function recordedBy() { return $this->belongsTo(User::class, 'recorded_by'); }

    public function config(): array { return self::METRICS[$this->metric] ?? ['label' => $this->metric, 'unit' => '']; }
    public function label(): string { return $this->config()['label'] ?? $this->metric; }
    public function unit(): string  { return $this->config()['unit'] ?? ''; }

    /** Classify a value against the metric's normal band. */
    public static function classify(string $metric, $value): array
    {
        $green = '#16a34a'; $amber = '#d97706'; $red = '#dc2626'; $grey = '#94a3b8';
        $cfg = self::METRICS[$metric] ?? null;
        if (!$cfg || $value === null || ($cfg['normal'] ?? null) === null) {
            return ['status' => 'none', 'label' => '', 'color' => $grey];
        }
        $v = (float) $value;
        [$lo, $hi] = $cfg['normal'];
        if ($v < $lo) {
            // For SpO2 a low value is the concern; for stress low is good.
            return $metric === 'stress'
                ? ['status' => 'good', 'label' => 'Calm', 'color' => $green]
                : ['status' => 'low', 'label' => 'Low', 'color' => $red];
        }
        if ($v > $hi) return ['status' => 'high', 'label' => 'High', 'color' => $red];
        return ['status' => 'normal', 'label' => $metric === 'stress' ? 'Moderate' : 'Normal', 'color' => $green];
    }

    public function classification(): array
    {
        return self::classify($this->metric, $this->value);
    }

    /**
     * Weight trend: first vs latest within the window (or all-time), plus the
     * change (negative = lost). $weightLogs are HealthMetric rows of metric=weight.
     */
    public static function weightTrend(Collection $weightLogs): ?array
    {
        if ($weightLogs->isEmpty()) return null;
        $sorted = $weightLogs->sortBy('logged_at')->values();
        $first  = $sorted->first();
        $latest = $sorted->last();
        $change = round((float) $latest->value - (float) $first->value, 1);
        $days   = $first->logged_at->diffInDays($latest->logged_at);
        return [
            'first'   => (float) $first->value,
            'latest'  => (float) $latest->value,
            'change'  => $change,                 // negative = lost
            'days'    => $days,
            'count'   => $sorted->count(),
            'firstAt' => $first->logged_at,
            'latestAt'=> $latest->logged_at,
        ];
    }
}
