<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IpdNote extends Model
{
    protected $fillable = ['admission_id','note_type','noted_at','note','written_by'];
    protected $casts    = ['noted_at' => 'datetime'];

    const TYPES = [
        'doctor_round'    => ['label' => 'Doctor Round',      'icon' => 'fa-stethoscope',     'color' => 'teal'],
        'nursing'         => ['label' => 'Nursing Note',       'icon' => 'fa-notes-medical',   'color' => 'green'],
        'observation'     => ['label' => 'Observation',        'icon' => 'fa-eye',             'color' => 'navy'],
        'procedure'       => ['label' => 'Procedure',          'icon' => 'fa-syringe',         'color' => 'amber'],
        'dietary'         => ['label' => 'Dietary Note',       'icon' => 'fa-utensils',        'color' => 'purple'],
        'physiotherapy'   => ['label' => 'Physiotherapy',      'icon' => 'fa-person-walking',  'color' => 'gray'],
    ];

    public function admission()  { return $this->belongsTo(Admission::class); }
    public function writtenBy()  { return $this->belongsTo(User::class, 'written_by'); }

    public function getTypeLabelAttribute(): string  { return self::TYPES[$this->note_type]['label']  ?? ucfirst($this->note_type); }
    public function getTypeIconAttribute(): string   { return self::TYPES[$this->note_type]['icon']   ?? 'fa-note-sticky'; }
    public function getTypeColorAttribute(): string  { return self::TYPES[$this->note_type]['color']  ?? 'gray'; }
}
