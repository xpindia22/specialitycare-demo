<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RadiologyOrder extends Model
{
    protected $fillable = [
        'order_no', 'patient_id', 'admission_id', 'encounter_id', 'ordered_by',
        'modality', 'body_part', 'clinical_indication', 'urgency', 'status',
        'scheduled_at', 'completed_at', 'findings', 'impression',
        'report_file', 'reported_by', 'created_by',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    const MODALITIES = [
        'xray'         => ['label' => 'X-Ray',       'icon' => 'fa-radiation'],
        'ct'           => ['label' => 'CT Scan',      'icon' => 'fa-circle-dot'],
        'mri'          => ['label' => 'MRI',          'icon' => 'fa-magnet'],
        'usg'          => ['label' => 'Ultrasound',   'icon' => 'fa-wave-square'],
        'mammography'  => ['label' => 'Mammography',  'icon' => 'fa-circle'],
        'dexa'         => ['label' => 'DEXA Scan',    'icon' => 'fa-bone'],
        'other'        => ['label' => 'Other',        'icon' => 'fa-microscope'],
    ];

    public function patient()    { return $this->belongsTo(Patient::class); }
    public function admission()  { return $this->belongsTo(Admission::class); }
    public function encounter()  { return $this->belongsTo(Encounter::class); }
    public function orderedBy()  { return $this->belongsTo(Doctor::class, 'ordered_by'); }
    public function createdBy()  { return $this->belongsTo(User::class, 'created_by'); }

    public function getModalityLabelAttribute(): string
    {
        return self::MODALITIES[$this->modality]['label'] ?? $this->modality;
    }

    public static function generateOrderNo(): string
    {
        $prefix = 'RAD/' . now()->format('Y') . '/';
        $last = static::where('order_no', 'like', $prefix . '%')->orderBy('id', 'desc')->first();
        if ($last && preg_match('/(\d+)$/', $last->order_no, $m)) {
            $next = (int)$m[1] + 1;
        } else {
            $next = 1;
        }
        return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
    }
}
