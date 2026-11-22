<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PendingLabReport extends Model
{
    protected $fillable = [
        'sender', 'channel', 'report_path', 'source', 'report_name', 'reg_id',
        'sample_date', 'parsed', 'analyte_count', 'status',
        'patient_id', 'lab_order_id', 'handled_by', 'handled_at',
    ];

    protected $casts = [
        'parsed'      => 'array',
        'sample_date' => 'date',
        'handled_at'  => 'datetime',
    ];

    public function patient()  { return $this->belongsTo(Patient::class); }
    public function labOrder()  { return $this->belongsTo(LabOrder::class); }
    public function handledBy() { return $this->belongsTo(User::class, 'handled_by'); }
}
