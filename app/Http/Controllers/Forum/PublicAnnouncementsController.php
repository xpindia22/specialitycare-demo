<?php

namespace App\Http\Controllers\Forum;

use App\Http\Controllers\Controller;
use App\Models\ForumTopic;
use App\Services\LicenseService;

/**
 * Unauthenticated read-only patient announcements feed.
 *
 * Visibility rule:
 *   - topic.is_public = true   OR
 *   - parent forum.is_announcements = true
 *
 * Posts inside an announcement topic are shown read-only with only the
 * post body + posting date (author name hidden — patients don't need
 * to see staff identities).
 *
 * The module licensing gate applies here too: if the `forum` module is
 * disabled on this install, the page 404s.
 */
class PublicAnnouncementsController extends Controller
{
    public function index()
    {
        if (!app(LicenseService::class)->isEnabled('forum')) abort(404);

        $topics = ForumTopic::query()
            ->with(['forum', 'firstPost'])
            ->where(function ($q) {
                $q->where('is_public', true)
                  ->orWhereHas('forum', fn ($f) => $f->where('is_announcements', true));
            })
            ->orderByDesc('last_post_at')
            ->orderByDesc('id')
            ->paginate(10);

        return view('forum.public.announcements', [
            'topics' => $topics,
            'clinic' => $this->clinic(),
        ]);
    }

    public function show(ForumTopic $topic)
    {
        if (!app(LicenseService::class)->isEnabled('forum')) abort(404);

        // Same visibility rule — both must be loaded to evaluate.
        $topic->load('forum', 'posts');
        if (!$topic->isPublicallyVisible()) abort(404);

        return view('forum.public.announcement-show', [
            'topic'  => $topic,
            'clinic' => $this->clinic(),
        ]);
    }

    private function clinic(): array
    {
        return [
            'name'    => setting('clinic_name', config('app.name')),
            'address' => setting('clinic_address'),
            'phone'   => setting('clinic_phone'),
            'email'   => setting('clinic_email'),
        ];
    }
}
