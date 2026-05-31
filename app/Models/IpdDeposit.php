<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IpdDeposit extends Model
{
    protected $fillable = [
        'admission_id', 'amount', 'payment_method', 'transaction_ref', 'notes', 'received_by',
    ];

    protected $casts = ['amount' => 'decimal:2'];

    public function admission() { return $this->belongsTo(Admission::class); }
    public function receivedBy() { return $this->belongsTo(User::class, 'received_by'); }
}
