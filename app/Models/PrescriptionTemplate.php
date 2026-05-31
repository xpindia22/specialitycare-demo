<?php
// FILE: app/Models/PrescriptionTemplate.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrescriptionTemplate extends Model
{
    protected $fillable = ['name', 'doctor_id', 'items', 'created_by'];

    protected $casts = [
        'items' => 'array',
    ];

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
