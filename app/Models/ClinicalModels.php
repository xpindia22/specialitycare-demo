<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Diagnosis extends Model {
    protected $fillable = ['encounter_id','icd_code','diagnosis_name','type','status','notes'];
    public function encounter() { return $this->belongsTo(Encounter::class); }
}

class ClinicalNote extends Model {
    protected $fillable = [
        'patient_id','encounter_id','doctor_id','note_type',
        'subjective','objective','assessment','plan','note_content','is_signed','signed_at'
    ];
    protected $casts = ['is_signed'=>'boolean','signed_at'=>'datetime'];
    public function patient() { return $this->belongsTo(Patient::class); }
    public function doctor() { return $this->belongsTo(Doctor::class); }
    public function encounter() { return $this->belongsTo(Encounter::class); }
}

class MedicineCategory extends Model {
    protected $fillable = ['name','description'];
    public function medicines() { return $this->hasMany(Medicine::class,'category_id'); }
}

class Medicine extends Model {
    protected $fillable = [
        'name','generic_name','brand_name','category_id','drug_class',
        'formulation','strength','unit','side_effects','contraindications',
        'drug_interactions','mechanism_of_action','is_narcotic','is_active'
    ];
    protected $casts = ['is_narcotic'=>'boolean','is_active'=>'boolean'];
    public function category() { return $this->belongsTo(MedicineCategory::class,'category_id'); }
    public function stocks() { return $this->hasMany(PharmacyStock::class); }
    public function prescriptionItems() { return $this->hasMany(PrescriptionItem::class); }

    public function getTotalStockAttribute(): int {
        return $this->stocks()->where('is_active',true)
            ->where('expiry_date','>=',now()->toDateString())
            ->sum('quantity');
    }

    public function isInStock(): bool {
        return $this->total_stock > 0;
    }

    public function getStockStatusAttribute(): string {
        $stock = $this->total_stock;
        $low = (int) setting('low_stock_warning', 10);
        $critical = (int) setting('critical_stock_warning', 5);
        if ($stock == 0) return 'out_of_stock';
        if ($stock <= $critical) return 'critical';
        if ($stock <= $low) return 'low';
        return 'adequate';
    }
}

class PharmacyStock extends Model {
    protected $fillable = [
        'medicine_id','batch_no','manufacture_date','expiry_date','quantity',
        'min_stock_level','purchase_price','selling_price','mrp','supplier',
        'received_date','received_by','is_active'
    ];
    protected $casts = [
        'manufacture_date'=>'date','expiry_date'=>'date','received_date'=>'date',
        'is_active'=>'boolean','purchase_price'=>'decimal:2','selling_price'=>'decimal:2','mrp'=>'decimal:2'
    ];
    public function medicine() { return $this->belongsTo(Medicine::class); }
    public function receivedBy() { return $this->belongsTo(User::class,'received_by'); }
    public function transactions() { return $this->hasMany(StockTransaction::class); }

    public function isExpired(): bool { return $this->expiry_date->isPast(); }
    public function isNearExpiry(): bool { return $this->expiry_date->diffInDays(now()) <= 90 && !$this->isExpired(); }
}

class StockTransaction extends Model {
    protected $fillable = ['pharmacy_stock_id','medicine_id','type','quantity','reference','notes','created_by'];
    public function stock() { return $this->belongsTo(PharmacyStock::class,'pharmacy_stock_id'); }
    public function medicine() { return $this->belongsTo(Medicine::class); }
    public function createdBy() { return $this->belongsTo(User::class,'created_by'); }
}

class Prescription extends Model {
    protected $fillable = [
        'prescription_no','encounter_id','patient_id','doctor_id',
        'prescribed_date','notes','advice','status'
    ];
    protected $casts = ['prescribed_date'=>'date'];
    public function encounter() { return $this->belongsTo(Encounter::class); }
    public function patient() { return $this->belongsTo(Patient::class); }
    public function doctor() { return $this->belongsTo(Doctor::class); }
    public function items() { return $this->hasMany(PrescriptionItem::class); }

    public static function generateNo(): string {
        $prefix = 'RX/'.date('Ym').'/';
        $last = static::where('prescription_no','like',$prefix.'%')->orderBy('id','desc')->first();
        $num = $last ? (int) substr($last->prescription_no,-5)+1 : 1;
        return $prefix.str_pad($num,5,'0',STR_PAD_LEFT);
    }
}

class PrescriptionItem extends Model {
    protected $fillable = [
        'prescription_id','medicine_id','dose','frequency','route',
        'duration_days','quantity','instructions','is_in_stock','is_dispensed'
    ];
    protected $casts = ['is_in_stock'=>'boolean','is_dispensed'=>'boolean'];
    public function prescription() { return $this->belongsTo(Prescription::class); }
    public function medicine() { return $this->belongsTo(Medicine::class); }
}
