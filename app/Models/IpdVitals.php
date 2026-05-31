<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IpdVitals extends Model
{
    protected $table    = 'ipd_vitals';
    protected $fillable = [
        'admission_id','recorded_at','temperature','pulse','respiration_rate',
        'bp_systolic','bp_diastolic','spo2','weight','blood_glucose','urine_output','notes','recorded_by',
    ];
    protected $casts = ['recorded_at' => 'datetime'];

    public function admission()  { return $this->belongsTo(Admission::class); }
    public function recordedBy() { return $this->belongsTo(User::class, 'recorded_by'); }

    public function getBpAttribute(): ?string {
        if ($this->bp_systolic && $this->bp_diastolic) return "{$this->bp_systolic}/{$this->bp_diastolic} mmHg";
        return null;
    }
}
