<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OpdReferral extends Model
{
    protected $fillable = [
        'patient_id', 'from_encounter_id', 'from_doctor_id', 'from_department_id',
        'to_department_id', 'to_doctor_id', 'when', 'preferred_date', 'priority',
        'reason', 'notes', 'status', 'token_id', 'appointment_id', 'to_encounter_id', 'created_by',
    ];

    protected $casts = ['preferred_date' => 'date'];

    public const STATUS_LABELS = [
        'pending'   => 'Waiting for booking',
        'queued'    => 'In queue',
        'booked'    => 'Appointment booked',
        'seen'      => 'Seen',
        'cancelled' => 'Cancelled',
    ];

    public function patient()        { return $this->belongsTo(Patient::class); }
    public function fromEncounter()  { return $this->belongsTo(Encounter::class, 'from_encounter_id')->withTrashed(); }
    public function fromDoctor()     { return $this->belongsTo(Doctor::class, 'from_doctor_id'); }
    public function fromDepartment() { return $this->belongsTo(Department::class, 'from_department_id'); }
    public function toDepartment()   { return $this->belongsTo(Department::class, 'to_department_id'); }
    public function toDoctor()       { return $this->belongsTo(Doctor::class, 'to_doctor_id'); }
    public function token()          { return $this->belongsTo(OpdToken::class, 'token_id'); }
    public function appointment()    { return $this->belongsTo(Appointment::class); }
    public function toEncounter()    { return $this->belongsTo(Encounter::class, 'to_encounter_id'); }
    public function createdBy()      { return $this->belongsTo(User::class, 'created_by'); }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst($this->status);
    }

    /** "Dr. X (Cardiology)" — who referred */
    public function getFromLabelAttribute(): string
    {
        $who = $this->fromDoctor?->full_name ?? 'Doctor';
        return $this->fromDepartment ? "{$who} ({$this->fromDepartment->name})" : $who;
    }

    /** Token purpose text, shown in the queues, on the board and the slip */
    public function purposeText(): string
    {
        return \Illuminate\Support\Str::limit('Referred by ' . $this->from_label . ': ' . $this->reason, 200, '…');
    }

    /** The referral that brought this visit about (by its token, appointment or a direct link) */
    public static function forEncounter(Encounter $e): ?self
    {
        return static::with(['fromDoctor', 'fromDepartment', 'fromEncounter'])
            ->where('status', '!=', 'cancelled')
            ->where(fn ($q) => $q->where('to_encounter_id', $e->id)
                ->when($e->opd_token_id, fn ($w) => $w->orWhere('token_id', $e->opd_token_id))
                ->when($e->appointment_id, fn ($w) => $w->orWhere('appointment_id', $e->appointment_id)))
            ->latest('id')->first();
    }

    /** A visit was opened for this referral's token / appointment: mark it seen */
    public static function markSeen(Encounter $e): void
    {
        if (!$e->opd_token_id && !$e->appointment_id) return;
        static::whereIn('status', ['pending', 'queued', 'booked'])
            ->whereNull('to_encounter_id')
            ->where('patient_id', $e->patient_id)
            ->where(fn ($q) => $q->when($e->opd_token_id, fn ($w) => $w->orWhere('token_id', $e->opd_token_id))
                ->when($e->appointment_id, fn ($w) => $w->orWhere('appointment_id', $e->appointment_id)))
            ->update(['status' => 'seen', 'to_encounter_id' => $e->id]);
    }
}
