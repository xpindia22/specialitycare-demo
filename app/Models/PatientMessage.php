<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One message in a patient↔clinic conversation. */
class PatientMessage extends Model
{
    protected $fillable = ['patient_conversation_id', 'sender', 'user_id', 'body'];

    public function conversation(): BelongsTo { return $this->belongsTo(PatientConversation::class, 'patient_conversation_id'); }

    /** The staff member who sent this (null for patient-sent messages). */
    public function user(): BelongsTo { return $this->belongsTo(User::class); }

    public function fromPatient(): bool { return $this->sender === 'patient'; }
}
