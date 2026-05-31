<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class VoucherType extends Model {
    protected $fillable = ['name','code','numbering_prefix','affects','is_system','is_active','sort_order'];
    protected $casts = ['is_system'=>'bool','is_active'=>'bool'];

    public function vouchers() { return $this->hasMany(Voucher::class); }

    public static function byCode(string $code): self {
        $vt = static::where('code', $code)->first();
        if (!$vt) {
            throw new \RuntimeException("Voucher type missing: {$code}. Run AccountsModuleSeeder.");
        }
        return $vt;
    }
}
