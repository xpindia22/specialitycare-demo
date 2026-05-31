<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class VoucherEntry extends Model {
    protected $fillable = [
        'voucher_id','ledger_id','dr_amount','cr_amount','narration',
        'hsn_sac','taxable_amount','gst_rate','cgst_amount','sgst_amount','igst_amount','cess_amount',
        'line_no',
    ];
    protected $casts = [
        'dr_amount'=>'decimal:2','cr_amount'=>'decimal:2',
        'taxable_amount'=>'decimal:2','gst_rate'=>'decimal:2',
        'cgst_amount'=>'decimal:2','sgst_amount'=>'decimal:2',
        'igst_amount'=>'decimal:2','cess_amount'=>'decimal:2',
    ];

    public function voucher() { return $this->belongsTo(Voucher::class); }
    public function ledger()  { return $this->belongsTo(AccountLedger::class, 'ledger_id'); }
}
