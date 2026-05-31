<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EncounterTemplate extends Model
{
    protected $fillable = ['name', 'specialization', 'chief_complaint', 'clinical_notes', 'diagnoses', 'prescription_template', 'lab_orders', 'is_active', 'created_by'];
    protected $casts    = ['diagnoses' => 'array', 'prescription_template' => 'array', 'lab_orders' => 'array', 'is_active' => 'boolean'];

    public function createdBy() { return $this->belongsTo(User::class, 'created_by'); }
}
