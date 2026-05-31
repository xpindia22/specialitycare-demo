<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NursingShift extends Model
{
    protected $fillable = [
        'admission_id', 'shift_date', 'shift', 'patient_condition',
        'assessment', 'care_given', 'medications_given',
        'iv_lines', 'pending_tasks', 'special_instructions', 'written_by',
    ];

    protected $casts = ['shift_date' => 'date'];

    const SHIFTS = [
        'morning' => ['label' => 'Morning',  'time' => '06:00 – 14:00', 'icon' => 'fa-sun',      'color' => '#f59e0b'],
        'evening' => ['label' => 'Evening',  'time' => '14:00 – 22:00', 'icon' => 'fa-cloud-sun', 'color' => '#0ab4b4'],
        'night'   => ['label' => 'Night',    'time' => '22:00 – 06:00', 'icon' => 'fa-moon',      'color' => '#3730a3'],
    ];

    const CONDITIONS = [
        'stable'        => ['label' => 'Stable',        'color' => 'badge-green'],
        'improving'     => ['label' => 'Improving',     'color' => 'badge-teal'],
        'guarded'       => ['label' => 'Guarded',       'color' => 'badge-amber'],
        'critical'      => ['label' => 'Critical',      'color' => 'badge-red'],
        'deteriorating' => ['label' => 'Deteriorating', 'color' => 'badge-red'],
    ];

    public function getShiftLabelAttribute(): string  { return self::SHIFTS[$this->shift]['label'] ?? ucfirst($this->shift); }
    public function getShiftTimeAttribute(): string   { return self::SHIFTS[$this->shift]['time']  ?? ''; }
    public function getConditionBadgeAttribute(): string { return self::CONDITIONS[$this->patient_condition]['color'] ?? 'badge-gray'; }
    public function getConditionLabelAttribute(): string { return self::CONDITIONS[$this->patient_condition]['label'] ?? ucfirst($this->patient_condition ?? '—'); }

    public function admission() { return $this->belongsTo(Admission::class); }
    public function writtenBy() { return $this->belongsTo(User::class, 'written_by'); }
}
