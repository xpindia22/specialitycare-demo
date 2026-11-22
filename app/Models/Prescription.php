<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Prescription extends Model {
    protected $fillable = ['prescription_no','encounter_id','patient_id','doctor_id','prescribed_date','notes','advice','status'];
    protected $casts = ['prescribed_date'=>'date'];
    public function encounter() { return $this->belongsTo(Encounter::class); }
    public function patient() { return $this->belongsTo(Patient::class); }
    public function doctor() { return $this->belongsTo(Doctor::class); }
    public function items() { return $this->hasMany(PrescriptionItem::class); }
    protected static function booted(): void {
        // Rows of a deleted (hidden) visit stay out of every list and report
        static::addGlobalScope('liveVisit', fn ($q) => $q->where(fn ($w) =>
            $w->whereNull('prescriptions.encounter_id')->orWhereHas('encounter')));
    }
    public static function generateNo(): string {
        $prefix = 'RX/'.date('Ym').'/';
        $last = static::withoutGlobalScope('liveVisit')->where('prescription_no','like',$prefix.'%')
                       ->orderBy('prescription_no','desc')
                       ->value('prescription_no');
        $num = $last ? (int) substr($last, -5) + 1 : 1;
        $candidate = $prefix.str_pad($num, 5, '0', STR_PAD_LEFT);
        // Skip any numbers that already exist (safety net for gaps/duplicates)
        while (static::withoutGlobalScope('liveVisit')->where('prescription_no', $candidate)->exists()) {
            $num++;
            $candidate = $prefix.str_pad($num, 5, '0', STR_PAD_LEFT);
        }
        return $candidate;
    }
}
