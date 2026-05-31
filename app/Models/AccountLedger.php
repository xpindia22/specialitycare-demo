<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountLedger extends Model {
    protected $fillable = [
        'name','code','group_id','opening_balance','opening_balance_dr_cr',
        'gstin','state_code','party_type','contact_phone','contact_email','address',
        'is_system','is_active','notes',
    ];
    protected $casts = [
        'opening_balance'=>'decimal:2','is_system'=>'bool','is_active'=>'bool',
    ];

    public function group(): BelongsTo { return $this->belongsTo(AccountGroup::class, 'group_id'); }
    public function entries() { return $this->hasMany(VoucherEntry::class, 'ledger_id'); }

    /**
     * The "default" supplier to pre-select in supplier dropdowns. Picks the
     * supplier most recently used in a pharmacy_purchase; falls back to the
     * most recently created active supplier. Used by Stock Receipt, Add
     * Stock, Edit Stock, and Add Medicine forms to save users a click.
     */
    public static function defaultSupplier(): ?self {
        $latest = \DB::table('pharmacy_purchases')->orderByDesc('id')->value('supplier_ledger_id');
        if ($latest) return static::find($latest);
        return static::where('party_type', 'Supplier')->where('is_active', true)->orderByDesc('id')->first();
    }

    /** Find a system ledger by its seeded code; throws if missing (would mean seeder didn't run). */
    public static function byCode(string $code): self {
        $l = static::where('code', $code)->first();
        if (!$l) {
            throw new \RuntimeException("Required system ledger missing: {$code}. Run AccountsModuleSeeder.");
        }
        return $l;
    }

    /** Closing balance = opening + Σdr − Σcr (signed by nature). Use sparingly — for reports. */
    public function closingBalance(?string $upto = null): float {
        $q = $this->entries();
        if ($upto) {
            $q->whereHas('voucher', fn($v) => $v->where('voucher_date','<=',$upto)->where('status','Posted'));
        } else {
            $q->whereHas('voucher', fn($v) => $v->where('status','Posted'));
        }
        $dr = (float) $q->sum('dr_amount');
        $cr = (float) (clone $q)->sum('cr_amount');
        $opening = (float) $this->opening_balance * ($this->opening_balance_dr_cr === 'Dr' ? 1 : -1);
        return round($opening + $dr - $cr, 2);
    }
}
