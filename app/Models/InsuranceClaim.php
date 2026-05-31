<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InsuranceClaim extends Model
{
    protected $fillable = [
        'admission_id', 'patient_id', 'insurer_id',
        'claim_no', 'policy_no', 'member_id', 'policy_holder_name', 'policy_expiry',
        'sum_insured', 'approved_amount', 'settled_amount', 'co_pay_amount',
        'status', 'submitted_date', 'approved_date', 'settled_date',
        'diagnosis_icd', 'remarks', 'rejection_reason', 'created_by',
    ];

    protected $casts = [
        'policy_expiry'   => 'date',
        'submitted_date'  => 'date',
        'approved_date'   => 'date',
        'settled_date'    => 'date',
        'sum_insured'     => 'decimal:2',
        'approved_amount' => 'decimal:2',
        'settled_amount'  => 'decimal:2',
        'co_pay_amount'   => 'decimal:2',
    ];

    const STATUSES = [
        'draft'               => ['label' => 'Draft',               'color' => 'gray'],
        'submitted'           => ['label' => 'Submitted',           'color' => 'blue'],
        'query_raised'        => ['label' => 'Query Raised',        'color' => 'amber'],
        'approved'            => ['label' => 'Approved',            'color' => 'teal'],
        'partially_approved'  => ['label' => 'Partially Approved',  'color' => 'indigo'],
        'settled'             => ['label' => 'Settled',             'color' => 'green'],
        'rejected'            => ['label' => 'Rejected',            'color' => 'red'],
        'cancelled'           => ['label' => 'Cancelled',           'color' => 'gray'],
    ];

    public function admission()  { return $this->belongsTo(Admission::class); }
    public function patient()    { return $this->belongsTo(Patient::class); }
    public function insurer()    { return $this->belongsTo(Insurer::class); }
    public function createdBy()  { return $this->belongsTo(User::class, 'created_by'); }
    public function documents()  { return $this->hasMany(InsuranceDocument::class, 'claim_id'); }

    public function getStatusLabelAttribute(): string {
        return self::STATUSES[$this->status]['label'] ?? ucfirst($this->status);
    }
    public function getStatusColorAttribute(): string {
        return self::STATUSES[$this->status]['color'] ?? 'gray';
    }
    public function getPendingAmountAttribute(): float {
        return max(0, (float)$this->approved_amount - (float)$this->settled_amount);
    }
}
