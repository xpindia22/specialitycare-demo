<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ForumTopic extends Model
{
    protected $fillable = [
        'forum_id', 'user_id', 'title', 'slug',
        'is_pinned', 'is_locked', 'is_public',
        'posts_count', 'views_count', 'last_post_id', 'last_post_at',
    ];

    protected $casts = [
        'is_pinned'    => 'boolean',
        'is_locked'    => 'boolean',
        'is_public'    => 'boolean',
        'last_post_at' => 'datetime',
    ];

    public function forum(): BelongsTo    { return $this->belongsTo(Forum::class); }
    public function author(): BelongsTo   { return $this->belongsTo(User::class, 'user_id'); }
    public function posts(): HasMany      { return $this->hasMany(ForumPost::class)->orderBy('created_at'); }
    public function firstPost(): HasMany  { return $this->hasMany(ForumPost::class)->where('is_first', true); }
    public function lastPost(): BelongsTo { return $this->belongsTo(ForumPost::class, 'last_post_id'); }

    public function subscribers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'forum_subscriptions', 'forum_topic_id', 'user_id')
            ->withPivot('subscribed_at');
    }

    /** True when this topic should appear on the public /announcements page. */
    public function isPublicallyVisible(): bool
    {
        return $this->is_public || optional($this->forum)->is_announcements;
    }
}
