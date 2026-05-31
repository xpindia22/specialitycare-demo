<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    protected $fillable = ['key', 'label', 'enabled', 'enabled_at'];
    protected $casts = ['enabled' => 'bool', 'enabled_at' => 'datetime'];
}
