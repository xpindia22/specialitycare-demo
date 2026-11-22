<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ServiceCharge extends Model {
    protected $fillable = ['name','category','price','is_taxable','tax_percent','is_active'];
    protected $casts = ['price'=>'decimal:2','is_taxable'=>'boolean','is_active'=>'boolean'];
}
