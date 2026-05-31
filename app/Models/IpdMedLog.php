<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IpdMedLog extends Model
{
    protected $table    = 'ipd_med_logs';
    protected $fillable = [
        'prescription_id','admission_id','log_date','scheduled_time',
        'status','actual_time','remarks','recorded_by',
    ];
    protected $casts = ['log_date' => 'date'];

    const STATUS_COLORS = [
        'given'   => 'green',
        'missed'  => 'red',
        'held'    => 'amber',
        'refused' => 'navy',
    ];
    const STATUS_ICONS = [
        'given'   => 'fa-circle-check',
        'missed'  => 'fa-circle-xmark',
        'held'    => 'fa-circle-pause',
        'refused' => 'fa-circle-minus',
    ];

    public function prescription() { return $this->belongsTo(IpdPrescription::class, 'prescription_id'); }
    public function recordedBy()   { return $this->belongsTo(User::class, 'recorded_by'); }
}
