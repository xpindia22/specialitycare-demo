<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class AccountGroup extends Model {
    protected $fillable = ['name','code','parent_id','nature','affects_gross_profit','is_system','sort_order'];
    protected $casts = ['affects_gross_profit'=>'bool','is_system'=>'bool'];

    public function parent()   { return $this->belongsTo(self::class, 'parent_id'); }
    public function children() { return $this->hasMany(self::class, 'parent_id'); }
    public function ledgers()  { return $this->hasMany(AccountLedger::class, 'group_id'); }
}
