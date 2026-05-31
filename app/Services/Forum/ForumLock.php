<?php

namespace App\Services\Forum;

use App\Models\Forum;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * Session-scoped unlock state for private/personal forums.
 *
 * A private forum has no stored password — the "forum password" IS the
 * owner's account password. verify() runs Hash::check() against the
 * owner's users.password, so rotating the account password rotates the
 * forum password automatically.
 *
 * The unlock flag lives in the session and therefore clears on logout.
 */
class ForumLock
{
    private const SESSION_KEY = 'forum_unlocked';

    /** Is this private forum currently unlocked in the session? */
    public function isUnlocked(Forum $forum): bool
    {
        if (!$forum->isPrivate()) return true; // non-private = always open
        $set = (array) session(self::SESSION_KEY, []);
        return !empty($set[$forum->id]);
    }

    /**
     * Verify the supplied password against the forum owner's account
     * password and, on success, record the unlock in the session.
     */
    public function attempt(Forum $forum, string $password): bool
    {
        if (!$forum->isPrivate()) return true;

        $owner = $forum->private_owner_id ? User::find($forum->private_owner_id) : null;
        if (!$owner || !$owner->password) return false;

        if (!Hash::check($password, $owner->password)) return false;

        $set = (array) session(self::SESSION_KEY, []);
        $set[$forum->id] = true;
        session([self::SESSION_KEY => $set]);
        return true;
    }

    /** Drop the unlock for one forum (manual re-lock). */
    public function lock(Forum $forum): void
    {
        $set = (array) session(self::SESSION_KEY, []);
        unset($set[$forum->id]);
        session([self::SESSION_KEY => $set]);
    }
}
