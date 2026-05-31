<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Forum extends Model
{
    protected $fillable = [
        'forum_category_id', 'name', 'slug', 'description', 'icon', 'sort_order',
        'allowed_roles', 'is_announcements', 'is_private', 'private_owner_id',
        'topics_count', 'posts_count', 'last_post_id', 'last_post_at',
    ];

    protected $casts = [
        'allowed_roles'    => 'array',
        'is_announcements' => 'boolean',
        'is_private'       => 'boolean',
        'last_post_at'     => 'datetime',
    ];

    public function category(): BelongsTo  { return $this->belongsTo(ForumCategory::class, 'forum_category_id'); }
    public function topics(): HasMany       { return $this->hasMany(ForumTopic::class); }
    public function lastPost(): BelongsTo   { return $this->belongsTo(ForumPost::class, 'last_post_id'); }
    public function privateOwner(): BelongsTo { return $this->belongsTo(\App\Models\User::class, 'private_owner_id'); }

    /** A private/personal forum bound to a single owner. */
    public function isPrivate(): bool
    {
        return $this->is_private && $this->private_owner_id !== null;
    }

    /** True only for the owner of a private forum. */
    public function isOwnedBy(?\App\Models\User $user): bool
    {
        return $user !== null && $this->private_owner_id !== null
            && (int) $this->private_owner_id === (int) $user->id;
    }

    /**
     * Whether this forum should even be visible to the user (index /
     * admin list / direct URL). Private forums are visible to their owner
     * only; everyone else is treated as if the forum doesn't exist.
     */
    public function isVisibleTo(?\App\Models\User $user): bool
    {
        if ($this->isPrivate()) return $this->isOwnedBy($user);
        return true;
    }

    /**
     * True when the given user's role list includes any role allowed for
     * the requested action ('view'|'post'|'reply'|'moderate'). Admin always
     * gets moderate.
     *
     * Private forums short-circuit role lists entirely: the owner may do
     * anything, and no one else may do anything. The session-level password
     * unlock is enforced separately in the controller — userCan() only
     * answers "is this person allowed at all", not "have they unlocked".
     */
    public function userCan(\App\Models\User $user, string $action): bool
    {
        if ($this->isPrivate()) {
            return $this->isOwnedBy($user);
        }
        if ($action === 'moderate' && $user->can('forum.moderate')) return true;
        $allowed = (array) ($this->allowed_roles[$action] ?? []);
        if (empty($allowed)) return false;
        foreach ($allowed as $roleName) {
            if ($user->hasRole($roleName)) return true;
        }
        return false;
    }

    /**
     * Bump cached counters + last-post pointers after a new post.
     */
    public function touchLastPost(ForumPost $post): void
    {
        $this->forceFill([
            'last_post_id'  => $post->id,
            'last_post_at'  => $post->created_at,
            'posts_count'   => $this->posts_count + 1,
        ])->save();
    }
}
