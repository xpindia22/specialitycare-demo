<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Voucher extends Model {
    protected $fillable = [
        'voucher_type_id','voucher_no','fy_year','voucher_date','party_ledger_id',
        'reference_no','reference_date','narration','total_amount',
        'source_type','source_id','status','created_by',
    ];
    protected $casts = [
        'voucher_date'=>'date','reference_date'=>'date','total_amount'=>'decimal:2',
    ];

    public function type()        { return $this->belongsTo(VoucherType::class, 'voucher_type_id'); }
    public function party()       { return $this->belongsTo(AccountLedger::class, 'party_ledger_id'); }
    public function entries()     { return $this->hasMany(VoucherEntry::class)->orderBy('line_no'); }
    public function createdBy()   { return $this->belongsTo(\App\Models\User::class, 'created_by'); }
}
