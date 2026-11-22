<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IcuInfusion extends Model
{
    protected $fillable = [
        'admission_id', 'drug_key', 'drug_name', 'amount', 'amount_unit', 'volume_ml', 'diluent',
        'dose_unit', 'weight_kg', 'dose', 'rate_ml_h', 'started_at', 'stopped_at', 'notes', 'started_by', 'stopped_by',
    ];
    protected $casts = [
        'started_at' => 'datetime', 'stopped_at' => 'datetime',
        'amount' => 'float', 'volume_ml' => 'float', 'weight_kg' => 'float', 'dose' => 'float', 'rate_ml_h' => 'float',
    ];

    const VASOACTIVE = ['noradrenaline', 'adrenaline', 'dopamine', 'dobutamine', 'vasopressin', 'phenylephrine', 'milrinone', 'levosimendan'];

    public function admission() { return $this->belongsTo(Admission::class); }
    public function logs()      { return $this->hasMany(IcuInfusionLog::class)->orderBy('logged_at')->orderBy('id'); }
    public function startedBy() { return $this->belongsTo(User::class, 'started_by'); }
    public function stoppedBy() { return $this->belongsTo(User::class, 'stopped_by'); }

    public function scopeRunning($q) { return $q->whereNull('stopped_at'); }

    public function getDrugAttribute(): ?array {
        return $this->drug_key ? config("icu_drugs.infusions.{$this->drug_key}") : null;
    }

    public function getIsVasoactiveAttribute(): bool {
        return in_array($this->drug_key, self::VASOACTIVE, true);
    }

    /** Concentration in the dose unit's numerator per ml (e.g. mcg/ml for mcg/kg/min). */
    public function getConcentrationAttribute(): float {
        return self::concentration($this->amount, $this->amount_unit, $this->volume_ml, $this->dose_unit);
    }

    public function getConcentrationLabelAttribute(): string {
        $num = explode('/', $this->dose_unit)[0];
        $c = $this->concentration;
        return rtrim(rtrim(number_format($c, $c < 1 ? 3 : 1, '.', ''), '0'), '.') . " {$num}/ml";
    }

    public function getDoseLabelAttribute(): string {
        return self::fmt($this->dose) . ' ' . $this->dose_unit;
    }

    /** ml left in the current syringe / bag (since the last start or new syringe), and hours until empty. */
    public function getSyringeAttribute(): array
    {
        $logs = $this->logs->values();
        $from = $logs->keys()->filter(fn ($i) => in_array($logs[$i]->action, ['start', 'new_syringe'], true))->last() ?? 0;
        $used = 0;
        for ($i = $from; $i < $logs->count(); $i++) {
            $end = $logs->get($i + 1)?->logged_at ?? $this->stopped_at ?? now();
            $used += $logs[$i]->rate_ml_h * $logs[$i]->logged_at->diffInMinutes($end) / 60;
        }
        $left = max(0, $this->volume_ml - $used);
        return ['left' => round($left, 1), 'hours' => $this->rate_ml_h > 0 ? round($left / $this->rate_ml_h, 1) : null];
    }

    // ── Dose ↔ rate maths (mirrors resources/views/icu/_calc-js) ──────────

    const FACTOR = ['mg' => ['mg' => 1, 'mcg' => 1000], 'mcg' => ['mcg' => 1, 'mg' => 0.001], 'U' => ['U' => 1]];

    public static function concentration(float $amount, string $amountUnit, float $volume, string $doseUnit): float
    {
        $num = explode('/', $doseUnit)[0];
        $f = self::FACTOR[$amountUnit][$num] ?? null;
        if ($f === null || $volume <= 0) throw new \InvalidArgumentException("Cannot express {$amountUnit} as {$num}");
        return $amount * $f / $volume;
    }

    /** Multiplier from the dose to "numerator per hour". */
    public static function perHour(string $doseUnit, ?float $weight): float
    {
        $parts = explode('/', $doseUnit);
        $perKg = in_array('kg', $parts, true);
        $time = end($parts);
        if ($perKg && !$weight) throw new \InvalidArgumentException('Weight is needed for a per-kg dose');
        return ($perKg ? $weight : 1) * ($time === 'min' ? 60 : 1);
    }

    public static function rateFor(float $dose, float $conc, string $doseUnit, ?float $weight): float
    {
        return $dose * self::perHour($doseUnit, $weight) / $conc;
    }

    public static function doseFor(float $rate, float $conc, string $doseUnit, ?float $weight): float
    {
        return $rate * $conc / self::perHour($doseUnit, $weight);
    }

    public static function fmt(?float $n): string
    {
        if ($n === null) return '—';
        $d = $n >= 100 ? 0 : ($n >= 10 ? 1 : ($n >= 1 ? 2 : 3));
        return rtrim(rtrim(number_format($n, $d, '.', ''), '0'), '.');
    }
}
