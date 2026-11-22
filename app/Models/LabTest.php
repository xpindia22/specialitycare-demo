<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class LabTest extends Model {
    protected $fillable = ['lab_category_id','name','short_code','description','sample_type','normal_range','normal_value','unit','price','profit','turnaround_hours','instructions','is_active','is_pinned'];
    protected $casts = ['is_active'=>'boolean','is_pinned'=>'boolean','price'=>'decimal:2','profit'=>'decimal:2'];
    public function category() { return $this->belongsTo(LabCategory::class,'lab_category_id'); }
    public function panels() { return $this->belongsToMany(LabPanel::class,'lab_panel_tests','lab_test_id','lab_panel_id'); }
}
