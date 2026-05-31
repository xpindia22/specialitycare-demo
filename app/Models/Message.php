<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Message extends Model
{
    protected $fillable = ['message_thread_id', 'user_id', 'reply_to_message_id', 'body', 'edited_at', 'deleted_at'];

    protected $casts = ['edited_at' => 'datetime', 'deleted_at' => 'datetime'];

    public function thread(): BelongsTo { return $this->belongsTo(MessageThread::class, 'message_thread_id'); }
    public function author(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }
    public function attachments(): HasMany { return $this->hasMany(MessageAttachment::class); }
    public function reactions(): HasMany { return $this->hasMany(MessageReaction::class); }
    public function replyTo(): BelongsTo { return $this->belongsTo(Message::class, 'reply_to_message_id'); }

    /** Whether this message was "unsent" (content cleared, placeholder shown). */
    public function isDeleted(): bool { return $this->deleted_at !== null; }
}
