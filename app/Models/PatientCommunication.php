<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientCommunication extends Model
{
    protected $fillable = [
        'patient_id', 'mode', 'with_user_id', 'message_thread_id', 'note', 'occurred_at', 'logged_by',
    ];

    protected $casts = ['occurred_at' => 'datetime'];

    /** All modes (dm also comes from the in-app linker; the rest are logged manually). */
    public const MODES = [
        'dm'        => 'Direct Message',
        'whatsapp'  => 'WhatsApp',
        'email'     => 'Email',
        'call'      => 'Call',
        'in_person' => 'In person',
    ];

    /** Modes a user can pick when manually logging a communication (DM default). */
    public const MANUAL_MODES = [
        'dm'        => 'Direct Message',
        'whatsapp'  => 'WhatsApp',
        'email'     => 'Email',
        'call'      => 'Call',
        'in_person' => 'In person',
    ];

    public function patient(): BelongsTo { return $this->belongsTo(Patient::class); }
    public function withUser(): BelongsTo { return $this->belongsTo(User::class, 'with_user_id'); }
    public function thread(): BelongsTo { return $this->belongsTo(MessageThread::class, 'message_thread_id'); }
    public function logger(): BelongsTo { return $this->belongsTo(User::class, 'logged_by'); }

    public function modeLabel(): string { return self::MODES[$this->mode] ?? ucfirst($this->mode); }

    public function modeIcon(): string
    {
        return match ($this->mode) {
            'whatsapp'  => 'fa-brands fa-whatsapp',
            'email'     => 'fa-solid fa-envelope',
            'call'      => 'fa-solid fa-phone',
            'dm'        => 'fa-solid fa-comments',
            'in_person' => 'fa-solid fa-user-group',
            default     => 'fa-solid fa-comment',
        };
    }
}
