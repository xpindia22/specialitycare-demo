<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdmissionPackage extends Model
{
    protected $fillable = ['admission_id', 'package_id', 'quoted_price', 'paid_amount', 'notes', 'status', 'created_by'];

    protected $casts = [
        'quoted_price' => 'decimal:2',
        'paid_amount'  => 'decimal:2',
    ];

    public function admission() { return $this->belongsTo(Admission::class); }
    public function package()   { return $this->belongsTo(BillingPackage::class, 'package_id'); }
    public function createdBy() { return $this->belongsTo(User::class, 'created_by'); }

    public function getBalanceAttribute(): float
    {
        return max(0, (float)$this->quoted_price - (float)$this->paid_amount);
    }
}
