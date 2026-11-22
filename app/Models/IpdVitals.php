<?php
namespace App\Models;

use App\Services\EarlyWarning;
use Illuminate\Database\Eloquent\Model;

class IpdVitals extends Model
{
    protected $table    = 'ipd_vitals';
    protected $fillable = [
        'admission_id','recorded_at','temperature','pulse','respiration_rate',
        'bp_systolic','bp_diastolic','spo2','weight','blood_glucose','urine_output','notes','recorded_by',
        // ICU / NEWS2
        'map','cvp','rhythm','avpu','gcs_e','gcs_v','gcs_m','intubated','pupils','rass','pain_score',
        'o2_device','o2_flow','fio2','spo2_scale2','vent_mode','tidal_volume','vent_rate','peep','pip',
    ];
    protected $casts = ['recorded_at' => 'datetime', 'intubated' => 'boolean', 'spo2_scale2' => 'boolean', 'news2_partial' => 'boolean'];

    const AVPU = ['A' => 'Alert', 'C' => 'New confusion', 'V' => 'Responds to voice', 'P' => 'Responds to pain', 'U' => 'Unresponsive'];

    const O2_DEVICES = [
        'air'     => 'Room air',
        'nasal'   => 'Nasal prongs',
        'mask'    => 'Face mask',
        'venturi' => 'Venturi mask',
        'nrbm'    => 'Non-rebreather mask',
        'hfnc'    => 'High-flow nasal (HFNC)',
        'niv'     => 'NIV / BiPAP / CPAP',
        'vent'    => 'Ventilator',
        't_piece' => 'T-piece / trach mask',
    ];

    const VENT_MODES = ['VC-AC', 'PC-AC', 'SIMV', 'PSV / CPAP', 'PRVC', 'APRV', 'BiPAP (NIV)', 'CPAP (NIV)'];

    const RASS = [4 => '+4 Combative', 3 => '+3 Very agitated', 2 => '+2 Agitated', 1 => '+1 Restless', 0 => '0 Alert & calm',
                  -1 => '−1 Drowsy', -2 => '−2 Light sedation', -3 => '−3 Moderate sedation', -4 => '−4 Deep sedation', -5 => '−5 Unrousable'];

    protected static function booted(): void
    {
        // NEWS2 is stored so boards and lists can sort / alert without recomputing.
        static::saving(function (IpdVitals $v) {
            $r = EarlyWarning::news2($v);
            $v->news2 = $r['score'];
            $v->news2_partial = $r['partial'];
        });
    }

    public function admission()  { return $this->belongsTo(Admission::class); }
    public function recordedBy() { return $this->belongsTo(User::class, 'recorded_by'); }

    public function getBpAttribute(): ?string {
        if ($this->bp_systolic && $this->bp_diastolic) return "{$this->bp_systolic}/{$this->bp_diastolic} mmHg";
        return null;
    }

    /** Arterial-line MAP if entered, else (SBP + 2·DBP) / 3. */
    public function getMeanApAttribute(): ?int {
        if ($this->map) return (int) $this->map;
        if ($this->bp_systolic && $this->bp_diastolic) return (int) round(($this->bp_systolic + 2 * $this->bp_diastolic) / 3);
        return null;
    }

    public function getGcsAttribute(): ?int {
        if (!$this->gcs_e || !$this->gcs_m) return null;
        // Intubated: verbal cannot be scored, total is E + M and written with a T (e.g. 8T)
        return $this->gcs_e + ($this->intubated ? 0 : (int) $this->gcs_v) + $this->gcs_m;
    }

    /** "E3 VT M5 = 8T" style label. */
    public function getGcsLabelAttribute(): ?string {
        if ($this->gcs === null) return null;
        $v = $this->intubated ? 'T' : $this->gcs_v;
        return "E{$this->gcs_e} V{$v} M{$this->gcs_m} = {$this->gcs}" . ($this->intubated ? 'T' : '');
    }

    public function getTempCAttribute(): ?float {
        return $this->temperature ? round(((float) $this->temperature - 32) * 5 / 9, 1) : null;
    }

    public function getOnOxygenAttribute(): bool {
        return $this->o2_device && $this->o2_device !== 'air';
    }

    public function getNewsLevelAttribute(): ?string {
        return EarlyWarning::levelOf($this);
    }
}
