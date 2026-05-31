<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A single ongoing patient↔clinic conversation (shared staff inbox). */
class PatientConversation extends Model
{
    protected $fillable = ['patient_id', 'last_message_at', 'patient_read_at', 'staff_read_at'];

    protected $casts = [
        'last_message_at' => 'datetime',
        'patient_read_at' => 'datetime',
        'staff_read_at'   => 'datetime',
    ];

    public function patient(): BelongsTo { return $this->belongsTo(Patient::class); }
    public function messages(): HasMany { return $this->hasMany(PatientMessage::class)->orderBy('created_at'); }

    /** Get (or create) the conversation row for a patient. */
    public static function forPatient(Patient $patient): self
    {
        return static::firstOrCreate(['patient_id' => $patient->id]);
    }

    /** Messages from the patient that staff haven't read yet. */
    public function staffUnreadCount(): int
    {
        return $this->messages()
            ->where('sender', 'patient')
            ->when($this->staff_read_at, fn ($q) => $q->where('created_at', '>', $this->staff_read_at))
            ->count();
    }

    /** Messages from staff that the patient hasn't read yet. */
    public function patientUnreadCount(): int
    {
        return $this->messages()
            ->where('sender', 'staff')
            ->when($this->patient_read_at, fn ($q) => $q->where('created_at', '>', $this->patient_read_at))
            ->count();
    }
}
