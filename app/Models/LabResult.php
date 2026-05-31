<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class LabResult extends Model {
    protected $fillable = ['lab_order_item_id','lab_test_id','parameter_name','result_value','unit','normal_range','flag','comments','reported_by','reported_at'];
    protected $casts = ['reported_at'=>'datetime'];
    public function orderItem() { return $this->belongsTo(LabOrderItem::class,'lab_order_item_id'); }
    public function test() { return $this->belongsTo(LabTest::class,'lab_test_id'); }
    public function reportedBy() { return $this->belongsTo(User::class,'reported_by'); }
}
