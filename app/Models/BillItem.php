<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class BillItem extends Model {
    protected $fillable = ['bill_id','item_type','item_name','quantity','unit_price','discount','tax','total','reference_id'];
    protected $casts = ['unit_price'=>'decimal:2','discount'=>'decimal:2','tax'=>'decimal:2','total'=>'decimal:2'];
    public function bill() { return $this->belongsTo(Bill::class); }
}
