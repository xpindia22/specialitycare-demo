<?php

namespace App\Services\Forum;

use App\Models\ForumPost;
use App\Models\ForumTopic;
use App\Models\Message;
use App\Models\MessageThread;
use App\Models\Staff;
use App\Models\User;
use App\Services\AdminNotifier;
use App\Services\NotificationPreferenceService;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Fan-out for the forum + messaging module:
 *   - onDirectMessage(): notify every active participant except the sender
 *   - onReply()        : notify every subscriber of the topic except the author
 *   - onAnnouncement() : notify admin via AdminNotifier ("new public announcement")
 *
 * Every channel respects:
 *   - The notification_preferences row for the event key
 *   - The per-thread mute flag (DMs only)
 *   - dispatch()->afterResponse() so the user's HTTP request returns
 *     before any SMTP / Twilio handshakes — same async pattern as the
 *     rest of the app (see [[feedback_async_notifications]]).
 *
 * In-app delivery is implicit — the messages bell counts unread rows; the
 * forum bell shows topics-with-new-replies the viewer is subscribed to.
 * So "in-app" doesn't need to write a row anywhere.
 */
class ForumNotifier
{
    public function __construct(
        private readonly NotificationPreferenceService $prefs,
        private readonly WhatsAppService $wa,
    ) {}

    public function onDirectMessage(MessageThread $thread, Message $msg): void
    {
        $thread->loadMissing('activeParticipants');
        $senderId = $msg->user_id;
        $sender   = $thread->activeParticipants->firstWhere('id', $senderId)?->name ?? 'A staff member';

        $sendEmail = $this->prefs->shouldSend('forum_dm_received', 'admin', 'email');
        $sendWa    = $this->prefs->shouldSend('forum_dm_received', 'admin', 'whatsapp');

        foreach ($thread->activeParticipants as $p) {
            if ($p->id === $senderId) continue;
            if (!empty($p->pivot?->is_muted)) continue;

            $title   = $thread->is_group ? ($thread->subject ?: 'Group conversation') : "Message from {$sender}";
            $preview = Str::limit(strip_tags((string) $msg->body), 200);

            if ($sendEmail && !empty($p->email)) {
                $this->sendEmailAsync(
                    $p->email,
                    "[SC] New message: {$title}",
                    "{$sender} sent you a message:\n\n{$preview}\n\nReply: " . url('/messages/' . $thread->id)
                );
            }
            if ($sendWa) {
                $phone = $this->phoneOfUser($p);
                if ($phone) {
                    $body = "[SC] {$sender}: " . Str::limit($preview, 140) . "\nReply: " . url('/messages/' . $thread->id);
                    $this->sendWhatsAppAsync($phone, $body, 'forum_dm');
                }
            }
        }
    }

    public function onReply(ForumTopic $topic, ForumPost $post): void
    {
        $topic->loadMissing('forum', 'subscribers', 'author');
        $authorId = $post->user_id;
        $authorName = optional(User::find($authorId))->name ?? 'A staff member';

        $sendEmail = $this->prefs->shouldSend('forum_topic_reply', 'admin', 'email');
        $sendWa    = $this->prefs->shouldSend('forum_topic_reply', 'admin', 'whatsapp');
        if (!$sendEmail && !$sendWa) return;

        $preview = Str::limit(strip_tags((string) $post->body), 200);
        $url = route('forum.topic', ['forum' => $topic->forum->slug, 'topic' => $topic->slug]);

        foreach ($topic->subscribers as $sub) {
            if ($sub->id === $authorId) continue;

            $subj = "[SC] Reply on '{$topic->title}'";
            $body = "{$authorName} replied in '{$topic->title}':\n\n{$preview}\n\nRead: {$url}";

            if ($sendEmail && !empty($sub->email)) {
                $this->sendEmailAsync($sub->email, $subj, $body);
            }
            if ($sendWa) {
                $phone = $this->phoneOfUser($sub);
                if ($phone) $this->sendWhatsAppAsync($phone, "[SC] Reply: {$topic->title} — " . Str::limit($preview, 140) . "\n{$url}", 'forum_reply');
            }
        }
    }

    public function onAnnouncement(ForumTopic $topic, ForumPost $post): void
    {
        if (!$this->prefs->shouldSend('forum_announcement', 'admin', 'email')) return;
        $topic->loadMissing('forum', 'author');
        $authorName = $topic->author?->name ?? 'Admin';
        $preview = Str::limit(strip_tags((string) $post->body), 400);
        AdminNotifier::notify(
            "New announcement published: {$topic->title}",
            AdminNotifier::fmt([
                "Forum: " . optional($topic->forum)->name,
                "Topic: " . $topic->title,
                "By: " . $authorName,
                "Public URL: " . url('/announcements/' . $topic->slug),
                "",
                $preview,
            ]),
            'forum_announce_' . $topic->id
        );
    }

    /**
     * Lookup a staff phone for a user. Reasonable best-effort:
     *   - If a `staff` row matches by email or user_id, use its phone
     *   - Else null (we don't currently store phone on users directly)
     */
    private function phoneOfUser(User $user): ?string
    {
        try {
            if (!class_exists(Staff::class)) return null;
            $q = Staff::query();
            if ($user->email) $q->orWhere('email', $user->email);
            // Some HR rows are tied to users via a `user_id` column —
            // others aren't. Try both safely.
            try { $q->orWhere('user_id', $user->id); } catch (\Throwable) {}
            $s = $q->first();
            return $s?->phone ?: null;
        } catch (\Throwable $e) {
            Log::debug('ForumNotifier phone lookup failed: ' . $e->getMessage());
            return null;
        }
    }

    private function sendEmailAsync(string $to, string $subject, string $body): void
    {
        try {
            dispatch(function () use ($to, $subject, $body) {
                try {
                    Mail::raw($body, fn ($m) => $m->to($to)->subject($subject));
                } catch (\Throwable $e) {
                    Log::warning('ForumNotifier mail failed: ' . $e->getMessage());
                }
            })->afterResponse();
        } catch (\Throwable $e) {
            Log::warning('ForumNotifier dispatch(mail) failed: ' . $e->getMessage());
        }
    }

    private function sendWhatsAppAsync(string $phone, string $body, string $kind): void
    {
        try {
            $svc = $this->wa;
            dispatch(function () use ($svc, $phone, $body, $kind) {
                try {
                    $svc->send($phone, $body, $kind);
                } catch (\Throwable $e) {
                    Log::warning('ForumNotifier WA send failed: ' . $e->getMessage());
                }
            })->afterResponse();
        } catch (\Throwable $e) {
            Log::warning('ForumNotifier dispatch(WA) failed: ' . $e->getMessage());
        }
    }
}
