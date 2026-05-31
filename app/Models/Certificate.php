<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Certificate extends Model
{
    use HasFactory;

    protected $fillable = [
        'certificate_no',
        'patient_id',
        'doctor_id',
        'certificate_type',
        'issued_date',
        'purpose',
        'diagnosis',
        'rest_from',
        'rest_to',
        'remarks',
        'created_by',
    ];

    protected $casts = [
        'issued_date' => 'date',
        'rest_from'   => 'date',
        'rest_to'     => 'date',
    ];

    const TYPES = [
        'medical_leave'       => 'Medical / Sick Leave Certificate',
        'fitness'             => 'Fitness Certificate',
        'accident'            => 'Accident / Injury Certificate',
        'medical_examination' => 'Medical Examination Certificate',
        'age_nativity'        => 'Age & Nativity Certificate',
        'attendant'           => 'Relative / Attendant Certificate',
        'domiciliary'         => 'Domiciliary Treatment Certificate',
    ];

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->certificate_type] ?? $this->certificate_type;
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
