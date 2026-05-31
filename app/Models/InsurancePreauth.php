<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InsurancePreauth extends Model
{
    protected $fillable = [
        'admission_id', 'insurer_name', 'auth_number',
        'requested_at', 'approved_at', 'status', 'approved_amount', 'notes',
    ];

    protected $casts = [
        'requested_at'    => 'date',
        'approved_at'     => 'date',
        'approved_amount' => 'decimal:2',
    ];

    public function admission()
    {
        return $this->belongsTo(Admission::class);
    }
}
