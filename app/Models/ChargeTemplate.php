<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChargeTemplate extends Model
{
    protected $fillable = ['name', 'description', 'items', 'is_active', 'created_by'];
    protected $casts    = ['items' => 'array', 'is_active' => 'boolean'];

    public function createdBy() { return $this->belongsTo(User::class, 'created_by'); }
}
