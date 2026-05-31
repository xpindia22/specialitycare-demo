<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class EcgRecord extends Model {
    protected $fillable = ['record_no','patient_id','encounter_id','doctor_id','record_date','heart_rate','rhythm','pr_interval','qrs_duration','qt_qtc','axis','interpretation','ecg_file','price','performed_by'];
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
