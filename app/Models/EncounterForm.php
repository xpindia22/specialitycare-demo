<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EncounterForm extends Model
{
    protected $fillable = ['encounter_id', 'patient_id', 'form', 'data', 'updated_by'];
    protected $casts = ['data' => 'array'];

    public function encounter() { return $this->belongsTo(Encounter::class); }
    public function patient()   { return $this->belongsTo(Patient::class); }
    public function updatedBy() { return $this->belongsTo(User::class, 'updated_by'); }

    public function get(string $key, $default = null) { return $this->data[$key] ?? $default; }
}
