<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class GstRate extends Model {
    protected $fillable = ['name','rate','cess_rate','is_active'];
    protected $casts = ['rate'=>'decimal:2','cess_rate'=>'decimal:2','is_active'=>'bool'];
}
