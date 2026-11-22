<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ClinicalNote extends Model {
    protected $fillable = ['patient_id','encounter_id','doctor_id','note_type','subjective','objective','assessment','plan','note_content','is_signed','signed_at'];
    protected $casts = ['is_signed'=>'boolean','signed_at'=>'datetime'];
    public function patient() { return $this->belongsTo(Patient::class); }
    public function doctor() { return $this->belongsTo(Doctor::class); }
    public function encounter() { return $this->belongsTo(Encounter::class); }
}
