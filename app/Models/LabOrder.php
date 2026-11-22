<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class LabOrder extends Model {
    protected $fillable = ['order_no','patient_id','encounter_id','admission_id','doctor_id','ordered_date','status','clinical_info','notes','report_path','source','bill_id','total_amount','discount','net_amount','created_by'];
    protected $casts = ['ordered_date'=>'date','total_amount'=>'decimal:2','discount'=>'decimal:2','net_amount'=>'decimal:2'];
    public function patient() { return $this->belongsTo(Patient::class); }
    public function bill() { return $this->belongsTo(Bill::class); }
    public function doctor() { return $this->belongsTo(Doctor::class); }
    public function encounter() { return $this->belongsTo(Encounter::class); }
    public function admission() { return $this->belongsTo(Admission::class); }
    public function items() { return $this->hasMany(LabOrderItem::class,'lab_order_id'); }
    public function createdBy() { return $this->belongsTo(User::class,'created_by'); }
    public static function generateNo(): string {
        $prefix = 'LAB/'.date('Ym').'/';
        $last = static::where('order_no','like',$prefix.'%')->orderBy('id','desc')->first();
        $num = $last ? (int) substr($last->order_no,-5)+1 : 1;
        return $prefix.str_pad($num,5,'0',STR_PAD_LEFT);
    }
}
