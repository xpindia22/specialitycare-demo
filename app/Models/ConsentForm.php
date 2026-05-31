<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConsentForm extends Model
{
    protected $fillable = [
        'admission_id', 'consent_type', 'procedure_name', 'is_obtained',
        'patient_or_guardian', 'relationship', 'witness_name',
        'obtained_at', 'obtained_by', 'notes',
    ];

    protected $casts = ['is_obtained' => 'boolean', 'obtained_at' => 'datetime'];

    const TYPES = [
        'admission'         => ['label' => 'General Admission',     'icon' => 'fa-hospital',          'color' => 'teal'],
        'surgical'          => ['label' => 'Surgical / Procedure',  'icon' => 'fa-scalpel',           'color' => 'navy'],
        'anaesthesia'       => ['label' => 'Anaesthesia',           'icon' => 'fa-syringe',           'color' => 'purple'],
        'blood_transfusion' => ['label' => 'Blood Transfusion',     'icon' => 'fa-droplet',           'color' => 'red'],
        'procedure'         => ['label' => 'Procedure / Other',     'icon' => 'fa-notes-medical',     'color' => 'amber'],
    ];

    public function getLabelAttribute(): string { return self::TYPES[$this->consent_type]['label'] ?? ucfirst($this->consent_type); }
    public function getIconAttribute(): string  { return self::TYPES[$this->consent_type]['icon']  ?? 'fa-file-signature'; }
    public function getColorAttribute(): string { return self::TYPES[$this->consent_type]['color'] ?? 'gray'; }

    public function admission()   { return $this->belongsTo(Admission::class); }
    public function obtainedBy()  { return $this->belongsTo(User::class, 'obtained_by'); }
}
