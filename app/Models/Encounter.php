<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Encounter extends Model {
    // Deleting a visit hides it; the owner can view/restore it under Deleted Visits
    use SoftDeletes;

    protected $fillable = [
        'encounter_no','patient_id','doctor_id','appointment_id','opd_token_id','encounter_date','encounter_time',
        'visit_type','status','weight','height','bmi','bp_systolic','bp_diastolic',
        'pulse','temperature','spo2','respiratory_rate','blood_glucose',
        'chief_complaint','history_of_present_illness','past_medical_history',
        'family_history','social_history','allergies','review_of_systems',
        'examination_findings','impression','plan','follow_up_instructions',
        'follow_up_days','follow_up_date','created_by',
        'is_mlc','mlc_no',
    ];
    protected $casts = [
        'encounter_date'=>'date','follow_up_date'=>'date','is_mlc'=>'boolean',
        'followup_whatsapp_sent_at'=>'datetime','followup_email_sent_at'=>'datetime',
    ];

    public function patient() { return $this->belongsTo(Patient::class); }
    public function doctor() { return $this->belongsTo(Doctor::class); }
    public function appointment() { return $this->belongsTo(Appointment::class); }
    public function opdToken()   { return $this->belongsTo(OpdToken::class); }
    public function diagnoses() { return $this->hasMany(Diagnosis::class); }
    public function prescriptions() { return $this->hasMany(Prescription::class); }
    public function clinicalNotes() { return $this->hasMany(ClinicalNote::class); }
    public function encounterNotes() { return $this->hasMany(EncounterNote::class)->latest(); }
    public function labOrders() { return $this->hasMany(LabOrder::class); }
    public function reports() { return $this->hasMany(EncounterReport::class); }
    public function forms() { return $this->hasMany(EncounterForm::class); }
    public function referralsMade() { return $this->hasMany(OpdReferral::class, 'from_encounter_id'); }
    public function bill() { return $this->hasOne(Bill::class); }
    public function createdBy() { return $this->belongsTo(User::class,'created_by'); }
    public function deletedBy() { return $this->belongsTo(User::class,'deleted_by'); }

    protected static function booted(): void {
        // A visit opened from a referral's token or appointment closes that referral as seen
        static::created(fn (Encounter $e) => OpdReferral::markSeen($e));
    }

    public static function generateNo(): string {
        $prefix = 'ENC/'.date('Y').'/';
        $last = static::withTrashed()->where('encounter_no','like',$prefix.'%')->orderBy('id','desc')->first();
        $num = $last ? (int) substr($last->encounter_no,-5)+1 : 1;
        return $prefix.str_pad($num,5,'0',STR_PAD_LEFT);
    }
}
