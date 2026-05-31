<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Payment extends Model {
    protected $fillable = ['bill_id','amount','payment_method','transaction_ref','payment_date','notes','received_by'];
    protected $casts = ['payment_date'=>'date','amount'=>'decimal:2'];
    public function bill() { return $this->belongsTo(Bill::class); }
    public function receivedBy() { return $this->belongsTo(User::class,'received_by'); }
}
