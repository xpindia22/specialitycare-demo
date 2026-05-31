<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BillingPackageItem extends Model
{
    public $timestamps = false;
    protected $fillable = ['package_id', 'item_name', 'item_type', 'unit_price', 'quantity', 'total_price', 'is_included'];

    public function package() { return $this->belongsTo(BillingPackage::class, 'package_id'); }
}
