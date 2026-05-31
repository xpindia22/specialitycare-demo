<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TriageRecord extends Model
{
    protected $fillable = [
        'opd_token_id','patient_id','triage_level','chief_complaint',
        'bp_systolic','bp_diastolic','pulse','temperature','spo2',
        'weight','height','bmi','blood_glucose','nurse_notes','assessed_by',
    ];

    protected $casts = ['bmi' => 'decimal:1'];

    public function token()      { return $this->belongsTo(OpdToken::class, 'opd_token_id'); }
    public function patient()    { return $this->belongsTo(Patient::class); }
    public function assessedBy() { return $this->belongsTo(User::class, 'assessed_by'); }

    public function getBpAttribute(): string {
        if ($this->bp_systolic && $this->bp_diastolic) {
            return "{$this->bp_systolic}/{$this->bp_diastolic} mmHg";
        }
        return '—';
    }

    public static function computeBmi(?float $weight, ?float $height): ?float {
        if (!$weight || !$height) return null;
        $hm = $height / 100;
        return round($weight / ($hm * $hm), 1);
    }
}
