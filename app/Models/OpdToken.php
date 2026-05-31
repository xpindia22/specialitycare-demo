<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OpdToken extends Model
{
    protected $fillable = [
        'token_no','token_date','patient_id','doctor_id',
        'status','priority','purpose','notes',
        'called_at','consultation_started_at','completed_at','created_by',
    ];

    protected $casts = [
        'token_date'             => 'date',
        'called_at'              => 'datetime',
        'consultation_started_at'=> 'datetime',
        'completed_at'           => 'datetime',
    ];

    public function patient()  { return $this->belongsTo(Patient::class); }
    public function doctor()   { return $this->belongsTo(Doctor::class); }
    public function createdBy(){ return $this->belongsTo(User::class, 'created_by'); }
    public function triage()     { return $this->hasOne(TriageRecord::class, 'opd_token_id'); }
    public function encounter()  { return $this->hasOne(Encounter::class, 'opd_token_id'); }

    public function getDisplayAttribute(): string {
        return 'T-' . str_pad($this->token_no, 3, '0', STR_PAD_LEFT);
    }

    public static function nextTokenNo(): int {
        $last = static::whereDate('token_date', today())->max('token_no');
        return ($last ?? 0) + 1;
    }

    public static function todayStats(): array {
        $q = static::whereDate('token_date', today());
        return [
            'total'           => (clone $q)->count(),
            'waiting'         => (clone $q)->where('status','waiting')->count(),
            'in_consultation' => (clone $q)->where('status','in_consultation')->count(),
            'done'            => (clone $q)->where('status','done')->count(),
            'skipped'         => (clone $q)->where('status','skipped')->count(),
        ];
    }
}
