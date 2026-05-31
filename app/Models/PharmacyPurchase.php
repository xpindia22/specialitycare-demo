<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PharmacyPurchase extends Model
{
    protected $fillable = [
        'supplier_ledger_id', 'invoice_no', 'invoice_date', 'payment_mode',
        'subtotal', 'cgst_amount', 'sgst_amount', 'igst_amount',
        'round_off', 'total_amount', 'narration', 'voucher_id', 'created_by',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'subtotal'     => 'decimal:2',
        'cgst_amount'  => 'decimal:2',
        'sgst_amount'  => 'decimal:2',
        'igst_amount'  => 'decimal:2',
        'round_off'    => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function supplier()    { return $this->belongsTo(AccountLedger::class, 'supplier_ledger_id'); }
    public function items()       { return $this->hasMany(PharmacyPurchaseItem::class, 'purchase_id'); }
    public function voucher()     { return $this->belongsTo(Voucher::class, 'voucher_id'); }
    public function createdBy()   { return $this->belongsTo(User::class, 'created_by'); }
}
