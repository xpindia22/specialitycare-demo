<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PharmacyStock extends Model {
    protected $table = 'pharmacy_stock';
    protected $fillable = ['medicine_id','batch_number','expiry_date','quantity','min_stock_level','unit_cost','supplier','notes','added_by','is_active','purchase_item_id','destroy_method','destroyed_on','destroy_witness'];
    protected $casts = ['expiry_date'=>'date','destroyed_on'=>'date','is_active'=>'boolean'];
    public function medicine() { return $this->belongsTo(Medicine::class); }
    public function addedBy() { return $this->belongsTo(User::class,'added_by'); }
    public function receivedBy() { return $this->belongsTo(User::class,'added_by'); }
    public function transactions() { return $this->hasMany(StockTransaction::class); }
    public function getBatchNoAttribute() { return $this->batch_number; }
    public function getSellingPriceAttribute() { return $this->medicine?->unit_price ?? $this->unit_cost ?? 0; }
    public function getPurchasePriceAttribute() { return $this->unit_cost ?? 0; }
    public function isExpired(): bool { return $this->expiry_date ? $this->expiry_date->isPast() : false; }
    public function isNearExpiry(): bool { return $this->expiry_date ? now()->diffInDays($this->expiry_date, false) <= 90 && !$this->isExpired() : false; }
}
