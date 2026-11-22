<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class LabCategory extends Model {
    protected $fillable = ['name','code'];
    public function tests() { return $this->hasMany(LabTest::class,'category_id'); }
}

class LabTest extends Model {
    protected $fillable = [
        'category_id','name','code','description','specimen','method',
        'normal_range','price','tat_hours','is_active'
    ];
    protected $casts = ['is_active'=>'boolean','price'=>'decimal:2'];
    public function category() { return $this->belongsTo(LabCategory::class,'category_id'); }
    public function panels() { return $this->belongsToMany(LabPanel::class,'lab_panel_tests','test_id','panel_id'); }
}

class LabPanel extends Model {
    protected $fillable = ['name','code','description','price','is_active'];
    protected $casts = ['is_active'=>'boolean','price'=>'decimal:2'];
    public function tests() { return $this->belongsToMany(LabTest::class,'lab_panel_tests','panel_id','test_id'); }
}

class LabOrder extends Model {
    protected $fillable = [
        'order_no','patient_id','encounter_id','doctor_id','ordered_date',
        'status','clinical_info','notes','total_amount','discount','net_amount','created_by'
    ];
    protected $casts = ['ordered_date'=>'date','total_amount'=>'decimal:2','discount'=>'decimal:2','net_amount'=>'decimal:2'];
    public function patient() { return $this->belongsTo(Patient::class); }
    public function doctor() { return $this->belongsTo(Doctor::class); }
    public function encounter() { return $this->belongsTo(Encounter::class); }
    public function items() { return $this->hasMany(LabOrderItem::class,'order_id'); }
    public function createdBy() { return $this->belongsTo(User::class,'created_by'); }

    public static function generateNo(): string {
        $prefix = 'LAB/'.date('Ym').'/';
        $last = static::where('order_no','like',$prefix.'%')->orderBy('id','desc')->first();
        $num = $last ? (int) substr($last->order_no,-5)+1 : 1;
        return $prefix.str_pad($num,5,'0',STR_PAD_LEFT);
    }
}

class LabOrderItem extends Model {
    protected $fillable = ['order_id','test_id','panel_id','price','status'];
    protected $casts = ['price'=>'decimal:2'];
    public function order() { return $this->belongsTo(LabOrder::class,'order_id'); }
    public function test() { return $this->belongsTo(LabTest::class); }
    public function panel() { return $this->belongsTo(LabPanel::class); }
    public function results() { return $this->hasMany(LabResult::class,'order_item_id'); }
}

class LabResult extends Model {
    protected $fillable = [
        'order_item_id','test_id','parameter_name','result_value',
        'unit','normal_range','flag','comments','reported_by','reported_at'
    ];
    protected $casts = ['reported_at'=>'datetime'];
    public function orderItem() { return $this->belongsTo(LabOrderItem::class,'order_item_id'); }
    public function test() { return $this->belongsTo(LabTest::class); }
    public function reportedBy() { return $this->belongsTo(User::class,'reported_by'); }
}

class EcgRecord extends Model {
    protected $fillable = [
        'record_no','patient_id','encounter_id','doctor_id','record_date',
        'heart_rate','rhythm','pr_interval','qrs_duration','qt_qtc','axis',
        'interpretation','ecg_file','price','performed_by'
    ];
    protected $casts = ['record_date'=>'date','price'=>'decimal:2'];
    public function patient() { return $this->belongsTo(Patient::class); }
    public function doctor() { return $this->belongsTo(Doctor::class); }
    public function performedBy() { return $this->belongsTo(User::class,'performed_by'); }

    public static function generateNo(): string {
        $prefix = 'ECG/'.date('Ym').'/';
        $last = static::where('record_no','like',$prefix.'%')->orderBy('id','desc')->first();
        $num = $last ? (int) substr($last->record_no,-4)+1 : 1;
        return $prefix.str_pad($num,4,'0',STR_PAD_LEFT);
    }
}

class EchoRecord extends Model {
    protected $fillable = [
        'record_no','patient_id','encounter_id','doctor_id','record_date',
        'lv_edd','lv_esd','ef_percent','lvpw','ivs','la_size','ao_root','rv_dimension',
        'mitral_valve','aortic_valve','tricuspid_valve','pulmonary_valve',
        'impression','recommendations','echo_file','price','performed_by'
    ];
    protected $casts = ['record_date'=>'date','price'=>'decimal:2'];
    public function patient() { return $this->belongsTo(Patient::class); }
    public function doctor() { return $this->belongsTo(Doctor::class); }

    public static function generateNo(): string {
        $prefix = 'ECHO/'.date('Ym').'/';
        $last = static::where('record_no','like',$prefix.'%')->orderBy('id','desc')->first();
        $num = $last ? (int) substr($last->record_no,-4)+1 : 1;
        return $prefix.str_pad($num,4,'0',STR_PAD_LEFT);
    }
}

class ServiceCharge extends Model {
    protected $fillable = ['name','category','price','is_taxable','tax_percent','is_active'];
    protected $casts = ['price'=>'decimal:2','is_taxable'=>'boolean','is_active'=>'boolean'];
}

class Bill extends Model {
    protected $fillable = [
        'bill_no','patient_id','encounter_id','bill_date','subtotal','discount_percent',
        'discount_amount','tax_amount','total_amount','paid_amount','due_amount',
        'payment_status','payment_method','notes','created_by'
    ];
    protected $casts = [
        'bill_date'=>'date',
        'subtotal'=>'decimal:2','discount_percent'=>'decimal:2','discount_amount'=>'decimal:2',
        'tax_amount'=>'decimal:2','total_amount'=>'decimal:2','paid_amount'=>'decimal:2','due_amount'=>'decimal:2'
    ];
    public function patient() { return $this->belongsTo(Patient::class); }
    public function encounter() { return $this->belongsTo(Encounter::class); }
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

class BillItem extends Model {
    protected $fillable = ['bill_id','item_type','item_name','quantity','unit_price','discount','tax','total','reference_id'];
    protected $casts = ['unit_price'=>'decimal:2','discount'=>'decimal:2','tax'=>'decimal:2','total'=>'decimal:2'];
    public function bill() { return $this->belongsTo(Bill::class); }
}

class Payment extends Model {
    protected $fillable = ['bill_id','amount','payment_method','transaction_ref','payment_date','notes','received_by'];
    protected $casts = ['payment_date'=>'date','amount'=>'decimal:2'];
    public function bill() { return $this->belongsTo(Bill::class); }
    public function receivedBy() { return $this->belongsTo(User::class,'received_by'); }
}

class Setting extends Model {
    protected $fillable = ['key','value','group'];
    public static function get(string $key, $default = null) {
        $setting = static::where('key',$key)->first();
        return $setting ? $setting->value : $default;
    }
}

class Department extends Model {
    protected $fillable = ['name','code','description','is_active'];
    protected $casts = ['is_active'=>'boolean'];
    public function doctors() { return $this->hasMany(Doctor::class); }
}
