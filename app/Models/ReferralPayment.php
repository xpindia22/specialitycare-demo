<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReferralPayment extends Model
{
    protected $fillable = [
        'referral_source_id', 'patient_id', 'admission_id', 'bill_id',
        'calculated_amount', 'paid_amount', 'status', 'payment_date',
        'payment_method', 'transaction_ref', 'notes', 'created_by',
    ];

    protected $casts = [
        'calculated_amount' => 'decimal:2',
        'paid_amount'       => 'decimal:2',
        'payment_date'      => 'date',
    ];

    public function referralSource() { return $this->belongsTo(ReferralSource::class); }
    public function patient()        { return $this->belongsTo(Patient::class); }
    public function admission()      { return $this->belongsTo(Admission::class); }
    public function bill()           { return $this->belongsTo(Bill::class); }
    public function createdBy()      { return $this->belongsTo(User::class, 'created_by'); }
}
