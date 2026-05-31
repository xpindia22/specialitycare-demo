<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EncounterNote extends Model
{
    protected $fillable = ['encounter_id', 'patient_id', 'body', 'created_by'];

    public function encounter()
    {
        return $this->belongsTo(Encounter::class);
    }
}
