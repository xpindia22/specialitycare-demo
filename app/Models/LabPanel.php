<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class LabPanel extends Model {
    protected $fillable = ['name','short_code','description','price','turnaround_hours','is_active','is_pinned'];
    protected $casts = ['is_active'=>'boolean','is_pinned'=>'boolean','price'=>'decimal:2'];
    public function tests() { return $this->belongsToMany(LabTest::class,'lab_panel_tests','lab_panel_id','lab_test_id'); }
}
