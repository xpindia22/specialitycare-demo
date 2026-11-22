<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Bill extends Model {
    protected $fillable = ['bill_no','patient_id','encounter_id','billing_package_id','bill_date','subtotal','discount_percent','discount_amount','tax_amount','total_amount','paid_amount','due_amount','status','payment_method','notes','created_by','insurance_amount','insurance_company','tpa_ref','last_overdue_reminded_at'];
    protected $casts = ['bill_date'=>'date','subtotal'=>'decimal:2','total_amount'=>'decimal:2','paid_amount'=>'decimal:2','due_amount'=>'decimal:2','last_overdue_reminded_at'=>'datetime'];
    public function patient() { return $this->belongsTo(Patient::class); }
    public function encounter() { return $this->belongsTo(Encounter::class); }
    public function package() { return $this->belongsTo(BillingPackage::class, 'billing_package_id'); }
    public function items() { return $this->hasMany(BillItem::class); }
    public function payments() { return $this->hasMany(Payment::class); }
    public function createdBy() { return $this->belongsTo(User::class,'created_by'); }
    public static function generateNo(): string {
        $prefix = 'BILL/'.date('Ym').'/';
        $last = static::where('bill_no','like',$prefix.'%')->orderBy('id','desc')->first();
        $num = $last ? (int) substr($last->bill_no,-5)+1 : 1;
        return $prefix.str_pad($num,5,'0',STR_PAD_LEFT);
    }
}
