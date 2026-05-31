<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PharmacyPurchaseItem extends Model
{
    protected $fillable = [
        'purchase_id', 'medicine_id', 'batch_number', 'expiry_date',
        'quantity', 'unit_cost', 'hsn_code', 'gst_rate',
        'taxable_amount', 'cgst_amount', 'sgst_amount', 'igst_amount',
        'line_total', 'stock_id',
    ];

    protected $casts = [
        'expiry_date'    => 'date',
        'quantity'       => 'decimal:2',
        'unit_cost'      => 'decimal:2',
        'gst_rate'       => 'decimal:2',
        'taxable_amount' => 'decimal:2',
        'cgst_amount'    => 'decimal:2',
        'sgst_amount'    => 'decimal:2',
        'igst_amount'    => 'decimal:2',
        'line_total'     => 'decimal:2',
    ];

    public function purchase() { return $this->belongsTo(PharmacyPurchase::class, 'purchase_id'); }
    public function medicine() { return $this->belongsTo(Medicine::class); }
    public function stock()    { return $this->belongsTo(PharmacyStock::class, 'stock_id'); }
}
