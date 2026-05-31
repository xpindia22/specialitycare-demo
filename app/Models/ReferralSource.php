<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReferralSource extends Model
{
    protected $fillable = [
        'name', 'type', 'type_other', 'contact_person', 'phone', 'email', 'address',
        'rate_type', 'rate_value', 'notes', 'is_active',
    ];

    public function getTypeLabelAttribute(): string {
        return match($this->type) {
            'doctor'   => 'Doctor',
            'hospital' => 'Hospital',
            'clinic'   => 'Clinic',
            'nurse'    => 'Nurse',
            'other'    => $this->type_other ?: 'Other',
            default    => ucfirst($this->type),
        };
    }

    protected $casts = ['is_active' => 'boolean', 'rate_value' => 'decimal:2'];

    public function payments()  { return $this->hasMany(ReferralPayment::class); }
    public function patients()  { return $this->hasMany(Patient::class, 'referral_source_id'); }

    public function getPendingAmountAttribute(): float
    {
        return (float) $this->payments()->whereIn('status', ['pending', 'partial'])->sum(\DB::raw('calculated_amount - paid_amount'));
    }
}
