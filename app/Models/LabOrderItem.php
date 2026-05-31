<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class LabOrderItem extends Model {
    protected $fillable = ['lab_order_id','lab_test_id','lab_panel_id','price','status','result_value','result_status','abnormal_flag','remarks','result_entered_at','result_entered_by'];
    protected $casts = ['price'=>'decimal:2','result_entered_at'=>'datetime'];
    public function order() { return $this->belongsTo(LabOrder::class,'lab_order_id'); }
    public function test() { return $this->belongsTo(LabTest::class,'lab_test_id'); }
    public function panel() { return $this->belongsTo(LabPanel::class,'lab_panel_id'); }
    public function results() { return $this->hasMany(LabResult::class,'lab_order_item_id'); }
    public function resultEnteredBy() { return $this->belongsTo(User::class,'result_entered_by'); }
}
