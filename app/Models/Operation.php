<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Operation extends Model
{
    protected $fillable = [
        'operation_no', 'patient_id', 'admission_id', 'surgeon_id', 'anesthetist_name',
        'operation_date', 'start_time', 'end_time', 'procedure_name', 'icd_code',
        'diagnosis', 'anesthesia_type', 'pre_op_diagnosis', 'post_op_diagnosis',
        'findings', 'complications', 'outcome', 'scrub_nurse', 'circulating_nurse',
        'blood_transfused', 'blood_units', 'status', 'notes', 'created_by',
    ];

    protected $casts = [
        'operation_date'  => 'date',
        'blood_transfused' => 'boolean',
    ];

    const STATUS_COLORS = [
        'scheduled'   => 'amber',
        'in_progress' => 'teal',
        'completed'   => 'green',
        'cancelled'   => 'red',
    ];

    const OUTCOME_LABELS = [
        'successful'      => 'Successful',
        'complicated'     => 'Complicated',
        'abandoned'       => 'Abandoned',
        'patient_expired' => 'Patient Expired',
    ];

    public function patient()    { return $this->belongsTo(Patient::class); }
    public function admission()  { return $this->belongsTo(Admission::class); }
    public function surgeon()    { return $this->belongsTo(Doctor::class, 'surgeon_id'); }
    public function createdBy()  { return $this->belongsTo(User::class, 'created_by'); }

    public function getStatusColorAttribute(): string
    {
        return self::STATUS_COLORS[$this->status] ?? 'gray';
    }

    public static function generateOpNo(): string
    {
        $prefix = 'OT/' . now()->format('Y') . '/';
        $last = static::where('operation_no', 'like', $prefix . '%')->orderBy('id', 'desc')->first();
        if ($last && preg_match('/(\d+)$/', $last->operation_no, $m)) {
            $next = (int)$m[1] + 1;
        } else {
            $next = 1;
        }
        return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
    }
}
