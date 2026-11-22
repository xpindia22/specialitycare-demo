<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Appointment extends Model {
    protected $fillable = [
        'appointment_no','patient_id','doctor_id','department_id','appointment_date','appointment_time',
        'duration','type','status','chief_complaint','notes','meeting_url',
        'rescheduled_from','reschedule_reason','created_by','checked_in_at','completed_at',
        'booking_source','public_name','public_phone',
    ];
    protected $casts = [
        'appointment_date'=>'date','checked_in_at'=>'datetime','completed_at'=>'datetime'
    ];
    public function patient() { return $this->belongsTo(Patient::class); }
    public function doctor() { return $this->belongsTo(Doctor::class); }
    public function department() { return $this->belongsTo(Department::class); }
    public function createdBy() { return $this->belongsTo(User::class,'created_by'); }
    public function rescheduledFrom() { return $this->belongsTo(Appointment::class,'rescheduled_from'); }
    public function encounter() { return $this->hasOne(Encounter::class); }

    /** Statuses that mean the patient has arrived / been seen for this visit. */
    public const SEEN_STATUSES = ['Checked-In', 'In-Progress', 'Completed'];

    /**
     * Has this visit already happened (patient arrived / encounter under way or
     * done)? Such an appointment is a historical record: it must NOT be dragged
     * to a future date on the calendar — book a follow-up instead. Uses the
     * eager-loaded encounter relation when present to avoid an extra query.
     */
    public function isSeen(): bool {
        if ($this->checked_in_at || $this->completed_at) return true;
        if (in_array($this->status, self::SEEN_STATUSES, true)) return true;
        $enc = $this->relationLoaded('encounter') ? $this->encounter : $this->encounter()->first();
        return $enc !== null;
    }

    public static function generateNo(): string {
        $prefix = 'APT/'.date('Ym').'/';
        $last = static::where('appointment_no','like',$prefix.'%')->orderBy('id','desc')->first();
        $num = $last ? (int) substr($last->appointment_no,-5)+1 : 1;
        return $prefix.str_pad($num,5,'0',STR_PAD_LEFT);
    }

    /** Leave out appointments cancelled because the visit started from them was deleted. */
    public function scopeWithoutDeletedVisits($query)
    {
        return $query->where(fn ($q) => $q->where('status', '!=', 'Cancelled')
            ->orWhereNull('notes')
            ->orWhere('notes', 'not like', '%' . \App\Http\Controllers\EncounterController::VISIT_DELETED_NOTE . '%'));
    }
}
