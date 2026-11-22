<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OpdFeeRule extends Model
{
    protected $fillable = ['department_id', 'doctor_id', 'new_fee', 'followup_fee', 'followup_days', 'free_days', 'free_visits', 'notes', 'is_active'];
    protected $casts = ['new_fee' => 'decimal:2', 'followup_fee' => 'decimal:2', 'is_active' => 'boolean'];

    public function department() { return $this->belongsTo(Department::class); }
    public function doctor()     { return $this->belongsTo(Doctor::class); }

    /** "Cardiology · Dr. Robert James", "All departments" … */
    public function getScopeLabelAttribute(): string
    {
        $parts = array_filter([$this->department?->name, $this->doctor?->full_name]);
        return $parts ? implode(' · ', $parts) : 'Default (all departments and doctors)';
    }
}
