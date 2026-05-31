<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class LabCategory extends Model {
    protected $fillable = ['name','code'];
    public function tests() { return $this->hasMany(LabTest::class,'lab_category_id'); }
}
