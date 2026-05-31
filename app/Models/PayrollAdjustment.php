<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollAdjustment extends Model
{
    protected $fillable = ['staff_id','doctor_id','date','type','amount','notes','created_by'];
    protected $casts = ['date' => 'date', 'amount' => 'decimal:2'];

    const TYPES = [
        'overtime'  => ['label' => 'Overtime / Extra Duty', 'sign' => +1, 'color' => '#0891b2', 'badge' => 'teal'],
        'bonus'     => ['label' => 'Bonus',                 'sign' => +1, 'color' => '#16a34a', 'badge' => 'green'],
        'allowance' => ['label' => 'Allowance',             'sign' => +1, 'color' => '#7c3aed', 'badge' => 'purple'],
        'advance'   => ['label' => 'Advance Taken',         'sign' => -1, 'color' => '#b45309', 'badge' => 'amber'],
        'deduction' => ['label' => 'Deduction',             'sign' => -1, 'color' => '#dc2626', 'badge' => 'red'],
    ];

    public function staff()     { return $this->belongsTo(Staff::class); }
    public function doctor()    { return $this->belongsTo(Doctor::class); }
    public function createdBy() { return $this->belongsTo(User::class, 'created_by'); }

    public function subject()
    {
        return $this->doctor_id ? $this->doctor : $this->staff;
    }

    public function getSignedAmountAttribute(): float
    {
        return ((float) $this->amount) * (self::TYPES[$this->type]['sign'] ?? 1);
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type]['label'] ?? ucfirst($this->type);
    }
}
