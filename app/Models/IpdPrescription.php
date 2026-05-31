<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IpdPrescription extends Model
{
    protected $fillable = [
        'admission_id','drug_name','generic_name','medicine_id','dose','route','frequency',
        'schedule_times','start_date','end_date','instructions','is_active','prescribed_by',
    ];
    protected $casts = [
        'schedule_times' => 'array',
        'start_date'     => 'date',
        'end_date'       => 'date',
        'is_active'      => 'boolean',
    ];

    const ROUTES = ['Oral','IV','IM','SC','Sublingual','Topical','Inhalation','Rectal','Nasal','Ophthalmic'];

    const FREQUENCIES = [
        'STAT'  => ['label' => 'STAT (Immediately)',  'times' => []],
        'OD'    => ['label' => 'OD (Once daily)',      'times' => ['08:00']],
        'BD'    => ['label' => 'BD (Twice daily)',     'times' => ['08:00','20:00']],
        'TDS'   => ['label' => 'TDS (Thrice daily)',   'times' => ['08:00','14:00','20:00']],
        'QID'   => ['label' => 'QID (Four times)',     'times' => ['06:00','12:00','18:00','22:00']],
        'Q4H'   => ['label' => 'Q4H (Every 4 hours)', 'times' => ['06:00','10:00','14:00','18:00','22:00','02:00']],
        'Q6H'   => ['label' => 'Q6H (Every 6 hours)', 'times' => ['06:00','12:00','18:00','24:00']],
        'Q8H'   => ['label' => 'Q8H (Every 8 hours)', 'times' => ['06:00','14:00','22:00']],
        'SOS'   => ['label' => 'SOS (When needed)',    'times' => []],
        'HS'    => ['label' => 'HS (At bedtime)',       'times' => ['22:00']],
        'Custom'=> ['label' => 'Custom times',          'times' => []],
    ];

    public function admission()    { return $this->belongsTo(Admission::class); }
    public function prescribedBy() { return $this->belongsTo(User::class, 'prescribed_by'); }
    public function logs()         { return $this->hasMany(IpdMedLog::class, 'prescription_id'); }
    public function todayLogs()    { return $this->hasMany(IpdMedLog::class, 'prescription_id')->whereDate('log_date', today()); }

    public function getFrequencyLabelAttribute(): string {
        return self::FREQUENCIES[$this->frequency]['label'] ?? $this->frequency;
    }
    public function getEffectiveTimesAttribute(): array {
        if ($this->schedule_times && count($this->schedule_times)) return $this->schedule_times;
        return self::FREQUENCIES[$this->frequency]['times'] ?? [];
    }
}
