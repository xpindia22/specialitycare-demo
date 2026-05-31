<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class StockTransaction extends Model {
    protected $fillable = ['medicine_id','pharmacy_stock_id','prescription_id','type','quantity','balance_after','batch_number','reference','notes','user_id'];
    public function medicine() { return $this->belongsTo(Medicine::class); }
    public function user() { return $this->belongsTo(User::class,'user_id'); }
    public function stock() { return $this->belongsTo(PharmacyStock::class,'pharmacy_stock_id'); }
}
