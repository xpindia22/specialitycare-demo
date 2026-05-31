<?php

namespace App\Http\Controllers\Forum;

use App\Http\Controllers\Controller;
use App\Models\Forum;
use App\Models\ForumCategory;
use App\Models\ForumPost;
use App\Models\ForumTopic;
use App\Services\Forum\ForumLock;
use App\Services\Forum\ForumNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Forum browsing + topic/post lifecycle for authenticated staff.
 *
 * Per-forum permissions are enforced by Forum::userCan($user, $action).
 * Routes additionally require the broad `forum.view` / `forum.post` /
 * `forum.reply` permission keys via middleware so we have a simple master
 * switch — granular role-list checks happen here.
 */
class ForumController extends Controller
{
    /** Forum index — categories with their forums + per-forum stats. */
    public function index()
    {
        $u = auth()->user();
        $categories = ForumCategory::orderBy('sort_order')->orderBy('id')
            ->with(['forums' => function ($q) {
                $q->with(['lastPost.author', 'lastPost.topic']);
            }])
            ->get();

        // Hide forums the viewer can't view at all so the index respects
        // per-forum role lists end-to-end.
        $categories->each(function ($c) use ($u) {
            $c->setRelation('forums', $c->forums->filter(fn ($f) => $f->userCan($u, 'view'))->values());
        });
        // Drop categories that became empty after the filter.
        $categories = $categories->filter(fn ($c) => $c->forums->isNotEmpty())->values();

        return view('forum.index', compact('categories'));
    }

    /** Topic list inside one forum. */
    public function show(Forum $forum, Request $request)
    {
        $u = auth()->user();
        if ($r = $this->privateGate($forum, $u)) return $r;
        abort_unless($forum->userCan($u, 'view'), 403, 'You do not have access to this forum.');

        $topics = $forum->topics()
            ->with(['author', 'lastPost.author'])
            ->orderByDesc('is_pinned')
            ->orderByDesc('last_post_at')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('forum.show', compact('forum', 'topics'));
    }

    /** New topic form. */
    public function createTopic(Forum $forum)
    {
        $u = auth()->user();
        if ($r = $this->privateGate($forum, $u)) return $r;
        abort_unless($forum->userCan($u, 'post'), 403, 'You cannot start topics in this forum.');
        return view('forum.topic-create', compact('forum'));
    }

    public function storeTopic(Forum $forum, Request $request)
    {
        $u = auth()->user();
        if ($r = $this->privateGate($forum, $u)) return $r;
        abort_unless($forum->userCan($u, 'post'), 403);

        $data = $request->validate([
            'title'     => 'required|string|min:3|max:200',
            'body'      => 'required|string|min:2|max:20000',
            'is_public' => 'nullable|boolean',
            'subscribe' => 'nullable|boolean',
        ]);

        // Only moderators or forum-admins can flip a topic public on a
        // non-announcement forum. On announcement forums it's implicit.
        // Private forums can NEVER be published — that's the whole point.
        $isPublic = false;
        if ($forum->isPrivate()) {
            $isPublic = false;
        } elseif ($forum->is_announcements) {
            $isPublic = true;
        } elseif (!empty($data['is_public']) && ($u->can('forum.admin') || $u->can('forum.moderate'))) {
            $isPublic = true;
        }

        $slug = $this->uniqueTopicSlug($data['title']);

        $topic = ForumTopic::create([
            'forum_id'  => $forum->id,
            'user_id'   => $u->id,
            'title'     => $data['title'],
            'slug'      => $slug,
            'is_public' => $isPublic,
        ]);

        $post = ForumPost::create([
            'forum_topic_id' => $topic->id,
            'user_id'        => $u->id,
            'body'           => $data['body'],
            'is_first'       => true,
        ]);

        $topic->forceFill([
            'posts_count'   => 1,
            'last_post_id'  => $post->id,
            'last_post_at'  => $post->created_at,
        ])->save();
        $forum->forceFill(['topics_count' => $forum->topics_count + 1])->save();
        $forum->touchLastPost($post);

        // Author auto-subscribes unless they unticked.
        if ($request->boolean('subscribe', true)) {
            $topic->subscribers()->syncWithoutDetaching([$u->id => ['subscribed_at' => now()]]);
        }

        // Announcement broadcast — fan out to admin notification channels.
        if ($topic->isPublicallyVisible()) {
            app(ForumNotifier::class)->onAnnouncement($topic, $post);
        }

        return redirect()->route('forum.topic', [$forum, $topic])->with('success', 'Topic posted.');
    }

    /** Topic view + reply form. */
    public function topic(Forum $forum, ForumTopic $topic)
    {
        $u = auth()->user();
        abort_unless($topic->forum_id === $forum->id, 404);
        if ($r = $this->privateGate($forum, $u)) return $r;
        abort_unless($forum->userCan($u, 'view'), 403);

        $topic->increment('views_count');
        $posts = $topic->posts()->with('author', 'editor')->paginate(20);
        $subscribed = $topic->subscribers()->where('users.id', $u->id)->exists();

        return view('forum.topic', compact('forum', 'topic', 'posts', 'subscribed'));
    }

    public function storeReply(Forum $forum, ForumTopic $topic, Request $request)
    {
        $u = auth()->user();
        abort_unless($topic->forum_id === $forum->id, 404);
        if ($r = $this->privateGate($forum, $u)) return $r;
        abort_unless($forum->userCan($u, 'reply'), 403, 'You cannot reply in this forum.');
        abort_if($topic->is_locked && !$forum->userCan($u, 'moderate'), 423, 'This topic is locked.');

        $data = $request->validate(['body' => 'required|string|min:2|max:20000']);

        $post = ForumPost::create([
            'forum_topic_id' => $topic->id,
            'user_id'        => $u->id,
            'body'           => $data['body'],
            'is_first'       => false,
        ]);

        $topic->forceFill([
            'posts_count'  => $topic->posts_count + 1,
            'last_post_id' => $post->id,
            'last_post_at' => $post->created_at,
        ])->save();
        $forum->touchLastPost($post);

        // Replier auto-subscribes unless already.
        $topic->subscribers()->syncWithoutDetaching([$u->id => ['subscribed_at' => now()]]);

        app(ForumNotifier::class)->onReply($topic, $post);

        return redirect()->route('forum.topic', [$forum, $topic])
            ->with('success', 'Reply posted.')->withFragment('post-' . $post->id);
    }

    /** Edit own post (or any post if moderator). */
    public function editPost(Forum $forum, ForumTopic $topic, ForumPost $post)
    {
        $u = auth()->user();
        abort_unless($post->forum_topic_id === $topic->id && $topic->forum_id === $forum->id, 404);
        if ($r = $this->privateGate($forum, $u)) return $r;
        $this->authorizeEdit($u, $forum, $post);
        return view('forum.post-edit', compact('forum', 'topic', 'post'));
    }

    public function updatePost(Forum $forum, ForumTopic $topic, ForumPost $post, Request $request)
    {
        $u = auth()->user();
        abort_unless($post->forum_topic_id === $topic->id && $topic->forum_id === $forum->id, 404);
        if ($r = $this->privateGate($forum, $u)) return $r;
        $this->authorizeEdit($u, $forum, $post);

        $data = $request->validate(['body' => 'required|string|min:2|max:20000']);
        $post->update([
            'body'      => $data['body'],
            'edited_at' => now(),
            'edited_by' => $u->id,
        ]);

        return redirect()->route('forum.topic', [$forum, $topic])
            ->with('success', 'Post updated.')->withFragment('post-' . $post->id);
    }

    public function destroyPost(Forum $forum, ForumTopic $topic, ForumPost $post)
    {
        $u = auth()->user();
        abort_unless($post->forum_topic_id === $topic->id && $topic->forum_id === $forum->id, 404);
        if ($r = $this->privateGate($forum, $u)) return $r;
        // First post deletion = topic delete; only moderators can do that.
        if ($post->is_first) {
            abort_unless($forum->userCan($u, 'moderate'), 403);
            $topic->delete();
            return redirect()->route('forum.show', $forum)->with('success', 'Topic deleted.');
        }
        $this->authorizeEdit($u, $forum, $post);
        $post->delete();
        $topic->decrement('posts_count');
        return redirect()->route('forum.topic', [$forum, $topic])->with('success', 'Post deleted.');
    }

    /** Moderator toggles: pin, lock. */
    public function toggleFlag(Forum $forum, ForumTopic $topic, Request $request)
    {
        $u = auth()->user();
        abort_unless($topic->forum_id === $forum->id, 404);
        if ($r = $this->privateGate($forum, $u)) return $r;
        abort_unless($forum->userCan($u, 'moderate'), 403);

        $flag = $request->input('flag');
        abort_unless(in_array($flag, ['pinned', 'locked', 'public'], true), 422);
        // A private forum's topics can never be made public.
        abort_if($flag === 'public' && $forum->isPrivate(), 403, 'Private forums cannot be published.');

        match ($flag) {
            'pinned' => $topic->update(['is_pinned' => !$topic->is_pinned]),
            'locked' => $topic->update(['is_locked' => !$topic->is_locked]),
            'public' => $topic->update(['is_public' => !$topic->is_public]),
        };

        return back()->with('success', 'Topic updated.');
    }

    /** Subscribe / unsubscribe to topic reply notifications. */
    public function toggleSubscribe(Forum $forum, ForumTopic $topic)
    {
        $u = auth()->user();
        abort_unless($topic->forum_id === $forum->id, 404);
        if ($r = $this->privateGate($forum, $u)) return $r;
        abort_unless($forum->userCan($u, 'view'), 403);

        if ($topic->subscribers()->where('users.id', $u->id)->exists()) {
            $topic->subscribers()->detach($u->id);
            return back()->with('success', 'Unsubscribed from this topic.');
        }
        $topic->subscribers()->syncWithoutDetaching([$u->id => ['subscribed_at' => now()]]);
        return back()->with('success', 'Subscribed. You\'ll be notified on new replies.');
    }

    /** Password prompt for a private forum. */
    public function unlockForm(Forum $forum)
    {
        $u = auth()->user();
        // Non-owners must not learn the forum exists.
        abort_unless($forum->isPrivate() && $forum->isOwnedBy($u), 404);
        // Already unlocked → straight in.
        if (app(ForumLock::class)->isUnlocked($forum)) {
            return redirect()->route('forum.show', $forum);
        }
        return view('forum.unlock', compact('forum'));
    }

    public function unlock(Forum $forum, Request $request)
    {
        $u = auth()->user();
        abort_unless($forum->isPrivate() && $forum->isOwnedBy($u), 404);

        $request->validate(['password' => 'required|string']);

        if (!app(ForumLock::class)->attempt($forum, (string) $request->input('password'))) {
            return back()->withErrors(['password' => 'Incorrect password.']);
        }
        return redirect()->route('forum.show', $forum)->with('success', 'Unlocked.');
    }

    /** Manually re-lock a private forum (drops the session unlock). */
    public function lock(Forum $forum)
    {
        $u = auth()->user();
        abort_unless($forum->isPrivate() && $forum->isOwnedBy($u), 404);
        app(ForumLock::class)->lock($forum);
        return redirect()->route('forum.index')->with('success', 'Locked.');
    }

    /**
     * Guard for private forums. Returns a redirect/abort response the
     * caller should `return`, or null when access is fine.
     *   - Non-owner  → 404 (forum is invisible to everyone but its owner)
     *   - Owner, not yet unlocked this session → redirect to password prompt
     *   - Owner, unlocked → null (proceed)
     */
    private function privateGate(Forum $forum, $user)
    {
        if (!$forum->isPrivate()) return null;
        abort_unless($forum->isOwnedBy($user), 404);
        if (!app(ForumLock::class)->isUnlocked($forum)) {
            return redirect()->route('forum.unlock', $forum);
        }
        return null;
    }

    private function authorizeEdit($user, Forum $forum, ForumPost $post): void
    {
        if ($post->user_id === $user->id) return;
        if ($forum->userCan($user, 'moderate')) return;
        abort(403, 'You can only edit your own posts.');
    }

    /** Slugify + dedupe topic slug (forum-scoped uniqueness is fine). */
    private function uniqueTopicSlug(string $title): string
    {
        $base = Str::slug(Str::limit($title, 60, ''));
        if ($base === '') $base = 'topic';
        $slug = $base;
        $n = 2;
        while (ForumTopic::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $n++;
            if ($n > 100) { $slug = $base . '-' . substr(bin2hex(random_bytes(3)), 0, 6); break; }
        }
        return $slug;
    }
}
