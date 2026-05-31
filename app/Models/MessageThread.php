<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MessageThread extends Model
{
    protected $fillable = [
        'subject', 'is_group', 'created_by', 'patient_id',
        'last_message_id', 'last_message_at',
    ];

    protected $casts = [
        'is_group'        => 'boolean',
        'last_message_at' => 'datetime',
    ];

    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }

    /**
     * The single patient this thread is "about". Set when a DM is started
     * from a patient's profile, and used as the thread's identity so each
     * patient gets a dedicated thread per colleague (see MessageController::
     * storeCompose). Null for general DMs and group threads.
     */
    public function patient(): BelongsTo { return $this->belongsTo(Patient::class); }

    /** dm-mode communication rows = the patients this conversation is linked to. */
    public function patientLinks(): HasMany
    {
        return $this->hasMany(PatientCommunication::class)->where('mode', 'dm')->latest('occurred_at');
    }

    /** Distinct linked Patient models (loaded via patientLinks). */
    public function linkedPatients()
    {
        return $this->patientLinks->pluck('patient')->filter()->unique('id')->values();
    }
    public function messages(): HasMany  { return $this->hasMany(Message::class)->orderBy('created_at'); }
    public function lastMessage(): BelongsTo { return $this->belongsTo(Message::class, 'last_message_id'); }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'message_thread_user')
            ->withPivot(['last_read_at', 'is_muted', 'left_at', 'archived_at'])
            ->withTimestamps();
    }

    /** Active (non-left) participants, used for fan-out + display. */
    public function activeParticipants(): BelongsToMany
    {
        return $this->participants()->wherePivotNull('left_at');
    }

    /**
     * Display name from the POV of $viewer. For 1-to-1 threads, the
     * other participant's name; for groups, the subject (falling back
     * to a comma-separated list of names).
     */
    public function titleFor(User $viewer): string
    {
        if ($this->is_group) {
            if ($this->subject) return $this->subject;
            $names = $this->activeParticipants->where('id', '!=', $viewer->id)->pluck('name')->take(3)->implode(', ');
            return $names ?: 'Group';
        }
        // Normally the single other participant; when the viewer isn't a
        // member (owner browsing all conversations) show every participant.
        $others = $this->activeParticipants->where('id', '!=', $viewer->id);
        $name = $others->count() > 1
            ? $others->pluck('name')->take(3)->implode(' & ')
            : ($others->first()?->name ?? 'Conversation');
        // A patient-scoped DM appends the patient so several threads with the
        // same colleague stay distinguishable in the inbox/header.
        if ($this->patient) {
            $name .= ' · ' . $this->patient->full_name . ' (' . $this->patient->jhc_id . ')';
        }
        return $name;
    }
}
