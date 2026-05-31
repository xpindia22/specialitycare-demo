<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HandoverNote extends Model
{
    protected $fillable = ['admission_id', 'doctor_id', 'shift', 'notes'];

    public function admission()
    {
        return $this->belongsTo(Admission::class);
    }

    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }
}
