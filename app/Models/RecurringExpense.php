<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class RecurringExpense extends Model {
    protected $fillable = [
        'name','expense_ledger_id','counter_ledger_id','amount',
        'day_of_month','narration','is_active','last_posted_on','starts_on','ends_on',
    ];
    protected $casts = [
        'amount'=>'decimal:2','is_active'=>'bool',
        'last_posted_on'=>'date','starts_on'=>'date','ends_on'=>'date',
    ];

    public function expenseLedger() { return $this->belongsTo(AccountLedger::class, 'expense_ledger_id'); }
    public function counterLedger() { return $this->belongsTo(AccountLedger::class, 'counter_ledger_id'); }
}
