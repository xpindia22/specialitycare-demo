<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConsentTemplate extends Model
{
    protected $fillable = ['name', 'category', 'body_html', 'is_active', 'created_by'];
    protected $casts    = ['is_active' => 'boolean'];

    public const CATEGORIES = [
        'surgical'        => 'Surgical / Procedure',
        'anaesthesia'     => 'Anaesthesia',
        'blood'           => 'Blood Transfusion',
        'investigation'   => 'Investigation / Imaging',
        'clinical_trial'  => 'Clinical Trial / Research',
        'general'         => 'General / Other',
    ];

    public function createdBy() { return $this->belongsTo(User::class, 'created_by'); }
}
