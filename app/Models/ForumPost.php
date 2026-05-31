<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ForumPost extends Model
{
    protected $fillable = [
        'forum_topic_id', 'user_id', 'body', 'is_first',
        'edited_at', 'edited_by',
    ];

    protected $casts = [
        'is_first'  => 'boolean',
        'edited_at' => 'datetime',
    ];

    public function topic(): BelongsTo  { return $this->belongsTo(ForumTopic::class, 'forum_topic_id'); }
    public function author(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }
    public function editor(): BelongsTo { return $this->belongsTo(User::class, 'edited_by'); }
}
