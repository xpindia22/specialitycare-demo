<?php

namespace App\Http\Controllers\Forum;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\MessageAttachment;
use App\Models\MessageThread;
use App\Models\User;
use App\Services\Forum\ForumNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Private messaging — 1-to-1 threads and group threads.
 *
 * - inbox()    : list of threads the viewer participates in (with unread)
 * - thread()   : messages in one thread + reply form
 * - send()     : append a message to an existing thread
 * - compose()  : start a new 1-to-1 thread (or reuse existing one)
 * - createGroup(): start a multi-participant group thread
 */
class MessageController extends Controller
{
    public function inbox(Request $request)
    {
        $u = auth()->user();
        $q = trim((string) $request->input('q', ''));
        // Scope: 'mine' (my non-archived threads, default), 'archived' (my
        // archived threads), or 'all' (owner-only — every conversation).
        $scopeIn = $request->input('scope');
        if ($scopeIn === 'all' && $u->isOwner()) {
            $scope = 'all';
        } elseif ($scopeIn === 'archived') {
            $scope = 'archived';
        } else {
            $scope = 'mine';
        }

        $base = MessageThread::query()
            ->with(['activeParticipants', 'lastMessage.author', 'patient']);

        if ($scope === 'mine') {
            $base->whereHas('participants', fn ($w) => $w->where('users.id', $u->id)
                ->whereNull('message_thread_user.left_at')->whereNull('message_thread_user.archived_at'));
        } elseif ($scope === 'archived') {
            $base->whereHas('participants', fn ($w) => $w->where('users.id', $u->id)
                ->whereNull('message_thread_user.left_at')->whereNotNull('message_thread_user.archived_at'));
        }
        // 'all' (owner): no participant filter — every thread.

        // Global search: match message text, a participant's name, the linked
        // patient (name / JHC id), or a group subject.
        if ($q !== '') {
            $like = '%' . $q . '%';
            $base->where(function ($w) use ($like) {
                $w->whereHas('messages', fn ($m) => $m->whereNull('deleted_at')->where('body', 'like', $like))
                  ->orWhereHas('activeParticipants', fn ($p) => $p->where('users.name', 'like', $like))
                  ->orWhereHas('patient', fn ($p) => $p->where('first_name', 'like', $like)
                        ->orWhere('last_name', 'like', $like)->orWhere('jhc_id', 'like', $like))
                  ->orWhere('subject', 'like', $like);
            });
        }

        $threads = $base->orderByDesc('last_message_at')->orderByDesc('id')
            ->paginate(25)->withQueryString();

        // Viewer-side unread map (0 for threads the viewer isn't a member of).
        $unreadByThread = [];
        foreach ($threads as $t) {
            $me = $t->activeParticipants->firstWhere('id', $u->id);
            if (!$me) { $unreadByThread[$t->id] = 0; continue; }
            $lastRead = $me->pivot?->last_read_at;
            $unreadByThread[$t->id] = $lastRead
                ? Message::where('message_thread_id', $t->id)->where('created_at', '>', $lastRead)->where('user_id', '!=', $u->id)->count()
                : Message::where('message_thread_id', $t->id)->where('user_id', '!=', $u->id)->count();
        }

        return view('forum.messages.inbox', compact('threads', 'unreadByThread', 'q', 'scope'));
    }

    public function thread(MessageThread $thread)
    {
        $u = auth()->user();

        // Participants get the full interactive view. Seniors/admins
        // (forum.moderate) may READ a conversation they're not part of, but
        // only when it's linked to a patient (care oversight). The clinic
        // OWNER may open ANY conversation — including private DMs — to
        // moderate (edit/delete any message). Read-only viewers can't
        // send/react/unsend/mute/leave, but the owner can still moderate.
        $isParticipant = $this->isParticipant($thread, $u);
        $canModerate   = $u->isOwner();   // owner = edit/delete ANY message
        $readOnly = false;
        if (!$isParticipant) {
            if ($canModerate) {
                $readOnly = true;         // owner can moderate but not post as a non-member
            } elseif ($u->can('forum.moderate') && $thread->patientLinks()->exists()) {
                $readOnly = true;
            } else {
                abort(403, 'You are not a participant in this conversation.');
            }
        }

        $thread->load('patientLinks.patient', 'patient');

        $messages = $thread->messages()
            ->with(['author', 'attachments', 'reactions', 'replyTo.author'])
            ->paginate(50);

        // Which of these messages has the viewer starred?
        $starredIds = \App\Models\MessageStar::where('user_id', $u->id)
            ->whereIn('message_id', $messages->pluck('id'))
            ->pluck('message_id')->all();

        // "Seen by everyone" threshold: the earliest last_read_at among the
        // OTHER active participants. If anyone hasn't read yet (null), nothing
        // counts as fully seen. My own messages with created_at <= this are
        // shown with the blue double-tick.
        $others = $thread->activeParticipants->where('id', '!=', $u->id);
        $reads  = $others->map(fn ($p) => $p->pivot->last_read_at);
        $allSeenUpTo = ($others->isEmpty() || $reads->contains(null))
            ? null
            : \Illuminate\Support\Carbon::parse($reads->map(fn ($v) => (string) $v)->min());

        // Mark read — only for actual participants (read-only viewers have no
        // pivot row and shouldn't affect read state).
        if ($isParticipant) {
            DB::table('message_thread_user')
                ->where('message_thread_id', $thread->id)
                ->where('user_id', $u->id)
                ->update(['last_read_at' => now(), 'updated_at' => now()]);
        }

        return view('forum.messages.thread', compact('thread', 'messages', 'allSeenUpTo', 'readOnly', 'canModerate', 'starredIds'));
    }

    /**
     * Lightweight polling endpoint for live updates: returns the latest
     * message id and a signature of the other participants' read state, so
     * the client can refresh only when something actually changed (new
     * message or a seen-tick flip).
     */
    public function poll(MessageThread $thread)
    {
        $u = auth()->user();
        if (!$this->isParticipant($thread, $u) && !$u->isOwner()
            && !($u->can('forum.moderate') && $thread->patientLinks()->exists())) {
            abort(403);
        }
        $lastId = (int) Message::where('message_thread_id', $thread->id)->max('id');
        $reads  = $thread->activeParticipants()->where('users.id', '!=', $u->id)
            ->pluck('message_thread_user.last_read_at')->implode('|');

        return response()->json(['lastId' => $lastId, 'seenSig' => md5($reads)]);
    }

    public function send(MessageThread $thread, Request $request)
    {
        $u = auth()->user();
        $this->assertParticipant($thread, $u);

        $data = $request->validate([
            'body'                => 'nullable|string|max:10000|required_without:attachments',
            'attachments'         => 'nullable|array|max:10',
            'attachments.*'       => 'file|max:10240|mimes:jpg,jpeg,png,gif,webp,heic,pdf,doc,docx,xls,xlsx,csv,txt',
            'reply_to_message_id' => 'nullable|integer',
        ], [
            'body.required_without' => 'Type a message or attach a file.',
            'attachments.*.max'     => 'Each file must be 10 MB or smaller.',
            'attachments.*.mimes'   => 'Allowed: images, PDF, Word, Excel, CSV, text.',
        ]);

        // A reply target must belong to THIS thread and not be unsent.
        $replyToId = null;
        if (!empty($data['reply_to_message_id'])) {
            $replyToId = Message::where('id', $data['reply_to_message_id'])
                ->where('message_thread_id', $thread->id)
                ->whereNull('deleted_at')
                ->value('id');
        }

        $msg = Message::create([
            'message_thread_id'   => $thread->id,
            'user_id'             => $u->id,
            'reply_to_message_id' => $replyToId,
            'body'                => $data['body'] ?? '',
        ]);

        // Store any uploaded files privately (NOT under public/), one row each.
        foreach ($request->file('attachments', []) as $file) {
            $path = $file->store('message-attachments/' . $thread->id, 'local');
            MessageAttachment::create([
                'message_id'    => $msg->id,
                'path'          => $path,
                'original_name' => mb_substr($file->getClientOriginalName(), 0, 255),
                'mime_type'     => $file->getClientMimeType(),
                'size'          => $file->getSize(),
            ]);
        }

        $thread->forceFill([
            'last_message_id' => $msg->id,
            'last_message_at' => $msg->created_at,
        ])->save();

        // Bump sender's read pointer to "now" so their own message doesn't
        // count as unread for them.
        DB::table('message_thread_user')
            ->where('message_thread_id', $thread->id)
            ->where('user_id', $u->id)
            ->update(['last_read_at' => now(), 'updated_at' => now()]);

        app(ForumNotifier::class)->onDirectMessage($thread, $msg);

        return redirect()->route('messages.thread', $thread)
            ->withFragment('msg-' . $msg->id);
    }

    /**
     * Stream a message attachment to a participant of its thread.
     * Files live on the private 'local' disk, so this auth-gated route is the
     * only way to reach them — never exposed via the public storage symlink.
     */
    public function downloadAttachment(MessageAttachment $attachment)
    {
        $thread = $attachment->message->thread;
        $this->assertParticipant($thread, auth()->user());

        abort_unless(Storage::disk('local')->exists($attachment->path), 404);

        $abs = Storage::disk('local')->path($attachment->path);

        // Show images/PDFs inline (so they preview in the browser); force a
        // download for everything else.
        if ($attachment->isImage() || $attachment->isPdf()) {
            return response()->file($abs, ['Content-Type' => $attachment->mime_type]);
        }
        return response()->download($abs, $attachment->original_name);
    }

    /** Toggle one of the allowed emoji reactions on a message. */
    public function react(MessageThread $thread, Message $message, Request $request)
    {
        $u = auth()->user();
        $this->assertParticipant($thread, $u);
        abort_unless($message->message_thread_id === $thread->id, 404);

        $emoji = (string) $request->input('emoji');
        abort_unless(in_array($emoji, \App\Models\MessageReaction::ALLOWED, true), 422);

        $existing = $message->reactions()->where('user_id', $u->id)->where('emoji', $emoji)->first();
        if ($existing) {
            $existing->delete();          // tapping the same emoji again removes it
        } else {
            $message->reactions()->create(['user_id' => $u->id, 'emoji' => $emoji]);
        }

        return redirect()->route('messages.thread', $thread)->withFragment('msg-' . $message->id);
    }

    /**
     * Remove a message.
     *  - Your OWN message → "unsend": content+files cleared but a
     *    "This message was deleted" placeholder remains (WhatsApp-style).
     *  - Owner deleting SOMEONE ELSE'S message → moderation: the message is
     *    hard-deleted so NO trace/placeholder is left for participants.
     *    Attachment/reaction rows cascade; replies that quoted it have their
     *    reference nulled. Audited in the activity log.
     */
    public function unsend(MessageThread $thread, Message $message)
    {
        $u = auth()->user();
        $isOwner = $u->isOwner();
        if (!$isOwner) {
            $this->assertParticipant($thread, $u);
        }
        abort_unless($message->message_thread_id === $thread->id, 404);
        abort_unless($message->user_id === $u->id || $isOwner, 403, 'You can only unsend your own messages.');

        // Attachment files always come off disk first (DB rows go via the
        // unsend clear below, or via cascade on a hard delete).
        foreach ($message->attachments as $att) {
            Storage::disk('local')->delete($att->path);
        }

        if ($isOwner && $message->user_id !== $u->id) {
            // Owner moderation — leave no tombstone.
            \App\Models\ActivityLog::log('deleted', "Owner removed message #{$message->id} (by user #{$message->user_id}) in conversation #{$thread->id}");
            $wasLast = (int) $thread->last_message_id === (int) $message->id;
            $message->delete();   // hard delete; attachments + reactions cascade
            if ($wasLast) {
                $prev = Message::where('message_thread_id', $thread->id)->orderByDesc('created_at')->first();
                $thread->forceFill([
                    'last_message_id' => $prev?->id,
                    'last_message_at' => $prev?->created_at,
                ])->save();
            }
            return redirect()->route('messages.thread', $thread);
        }

        // Self-unsend — keep the placeholder.
        $message->attachments()->delete();
        $message->reactions()->delete();
        $message->forceFill(['body' => '', 'deleted_at' => now()])->save();

        return redirect()->route('messages.thread', $thread)->withFragment('msg-' . $message->id);
    }

    /**
     * Edit a message's text. Authors may edit their OWN message; the owner
     * may edit ANY message (moderation). Stamps edited_at so the UI shows an
     * "(edited)" marker. Deleted (unsent) messages can't be edited.
     */
    public function editMessage(MessageThread $thread, Message $message, Request $request)
    {
        $u = auth()->user();
        $isOwner = $u->isOwner();
        abort_unless($message->message_thread_id === $thread->id, 404);
        abort_unless($isOwner || $message->user_id === $u->id, 403, 'You can only edit your own messages.');
        if (!$isOwner) {
            $this->assertParticipant($thread, $u);
        }
        abort_if($message->isDeleted(), 422, 'A deleted message cannot be edited.');

        $data = $request->validate(['body' => 'required|string|min:1|max:10000']);
        $message->forceFill(['body' => $data['body'], 'edited_at' => now()])->save();

        if ($isOwner && $message->user_id !== $u->id) {
            \App\Models\ActivityLog::log('updated', "Owner edited message #{$message->id} (by user #{$message->user_id}) in conversation #{$thread->id}");
        }

        return redirect()->route('messages.thread', $thread)->withFragment('msg-' . $message->id);
    }

    /**
     * Owner-only: permanently delete a whole conversation for everyone —
     * messages, attachments, reactions, participants and patient links all
     * cascade. Other staff use leave() to hide a thread from their own inbox.
     */
    public function destroyThread(MessageThread $thread)
    {
        abort_unless(auth()->user()->isOwner(), 403, 'Only the clinic owner can delete conversations.');
        $this->purgeThread($thread);
        return redirect()->route('messages.inbox')->with('success', 'Conversation deleted.');
    }

    /** Owner-only: delete several conversations at once (select-all/delete-all). */
    public function bulkDestroy(Request $request)
    {
        abort_unless(auth()->user()->isOwner(), 403, 'Only the clinic owner can delete conversations.');
        $ids = array_filter(array_map('intval', (array) $request->input('thread_ids', [])));
        abort_if(empty($ids), 422, 'No conversations selected.');

        $threads = MessageThread::whereIn('id', $ids)->get();
        foreach ($threads as $t) {
            $this->purgeThread($t);
        }
        return redirect()->route('messages.inbox')->with('success', count($threads) . ' conversation(s) deleted.');
    }

    /** Hard-delete a thread + its on-disk attachment files. DB rows cascade. */
    private function purgeThread(MessageThread $thread): void
    {
        \App\Models\ActivityLog::log('deleted', "Owner deleted conversation #{$thread->id} ({$thread->messages()->count()} messages)");
        Storage::disk('local')->deleteDirectory('message-attachments/' . $thread->id);
        $thread->delete();   // cascades messages, attachments, reactions, participants, patient links
    }

    /** Toggle the viewer's star/bookmark on a message. */
    public function toggleStar(MessageThread $thread, Message $message)
    {
        $u = auth()->user();
        if (!$u->isOwner()) {
            $this->assertParticipant($thread, $u);
        }
        abort_unless($message->message_thread_id === $thread->id, 404);

        $star = \App\Models\MessageStar::where('user_id', $u->id)->where('message_id', $message->id)->first();
        if ($star) {
            $star->delete();
        } else {
            \App\Models\MessageStar::create(['user_id' => $u->id, 'message_id' => $message->id, 'created_at' => now()]);
        }
        return redirect()->route('messages.thread', $thread)->withFragment('msg-' . $message->id);
    }

    /** List the viewer's starred messages across all conversations. */
    public function starred()
    {
        $stars = \App\Models\MessageStar::where('user_id', auth()->id())
            ->with(['message.author', 'message.thread.activeParticipants', 'message.thread.patient'])
            ->orderByDesc('created_at')
            ->paginate(30);
        return view('forum.messages.starred', compact('stars'));
    }

    /** Toggle archive of a thread for the current user (per-user, reversible). */
    public function toggleArchive(MessageThread $thread)
    {
        $u = auth()->user();
        $this->assertParticipant($thread, $u);
        $cur = DB::table('message_thread_user')
            ->where('message_thread_id', $thread->id)->where('user_id', $u->id)->value('archived_at');
        DB::table('message_thread_user')
            ->where('message_thread_id', $thread->id)->where('user_id', $u->id)
            ->update(['archived_at' => $cur ? null : now(), 'updated_at' => now()]);
        return back()->with('success', $cur ? 'Conversation unarchived.' : 'Conversation archived.');
    }

    /** Owner-only: compose a broadcast to all active staff. */
    public function broadcastForm()
    {
        abort_unless(auth()->user()->isOwner(), 403);
        $count = User::where('is_active', true)->where('has_system_access', true)->where('id', '!=', auth()->id())->count();
        return view('forum.messages.broadcast', compact('count'));
    }

    /** Owner-only: send a broadcast — a group thread with every active staff member. */
    public function storeBroadcast(Request $request)
    {
        $u = auth()->user();
        abort_unless($u->isOwner(), 403);
        $data = $request->validate([
            'subject' => 'nullable|string|max:200',
            'body'    => 'required|string|min:1|max:10000',
        ]);

        $recipients = User::where('is_active', true)->where('has_system_access', true)
            ->where('id', '!=', $u->id)->pluck('id')->all();
        abort_if(empty($recipients), 422, 'There are no other staff to broadcast to.');

        $thread = DB::transaction(function () use ($u, $data, $recipients) {
            $t = MessageThread::create([
                'subject'    => $data['subject'] ?: ('📢 Broadcast — ' . now()->format('d M Y, H:i')),
                'is_group'   => true,
                'created_by' => $u->id,
            ]);
            $now = now();
            $attach = [$u->id => ['created_at' => $now, 'updated_at' => $now, 'last_read_at' => $now]];
            foreach ($recipients as $pid) {
                $attach[(int) $pid] = ['created_at' => $now, 'updated_at' => $now, 'last_read_at' => null];
            }
            $t->participants()->attach($attach);
            return $t;
        });

        $msg = Message::create(['message_thread_id' => $thread->id, 'user_id' => $u->id, 'body' => $data['body']]);
        $thread->forceFill(['last_message_id' => $msg->id, 'last_message_at' => $msg->created_at])->save();
        DB::table('message_thread_user')->where('message_thread_id', $thread->id)->where('user_id', $u->id)
            ->update(['last_read_at' => now(), 'updated_at' => now()]);

        \App\Models\ActivityLog::log('created', 'Owner broadcast to ' . count($recipients) . " staff (conversation #{$thread->id})");
        app(ForumNotifier::class)->onDirectMessage($thread, $msg);

        return redirect()->route('messages.thread', $thread)->with('success', 'Broadcast sent to ' . count($recipients) . ' staff.');
    }

    // ── Patient ↔ clinic messaging (shared staff inbox) ───────────────────

    /** Shared inbox of patient conversations (any staff with messaging). */
    public function patientInbox()
    {
        abort_unless(auth()->user()->can('messages.send'), 403);
        $conversations = \App\Models\PatientConversation::with('patient')
            ->whereNotNull('last_message_at')
            ->orderByDesc('last_message_at')
            ->paginate(25);
        return view('forum.messages.patient-inbox', compact('conversations'));
    }

    /** Staff view of one patient's conversation; marks it read for staff. */
    public function patientThread(\App\Models\Patient $patient)
    {
        abort_unless(auth()->user()->can('messages.send'), 403);
        $conversation = \App\Models\PatientConversation::forPatient($patient);
        $messages = $conversation->messages()->with('user')->get();
        $conversation->forceFill(['staff_read_at' => now()])->save();
        return view('forum.messages.patient-thread', compact('patient', 'conversation', 'messages'));
    }

    /** Staff reply to a patient; notifies the patient by email (async). */
    public function patientReply(\App\Models\Patient $patient, Request $request)
    {
        $u = auth()->user();
        abort_unless($u->can('messages.send'), 403);
        $data = $request->validate(['body' => 'required|string|min:1|max:10000']);

        $conversation = \App\Models\PatientConversation::forPatient($patient);
        \App\Models\PatientMessage::create([
            'patient_conversation_id' => $conversation->id,
            'sender'  => 'staff',
            'user_id' => $u->id,
            'body'    => $data['body'],
        ]);
        $conversation->forceFill(['last_message_at' => now(), 'staff_read_at' => now()])->save();

        if ($patient->email) {
            $clinic = setting('clinic_name', config('app.name'));
            $this->notifyEmailAsync(
                $patient->email,
                "[{$clinic}] New message from the clinic",
                "Hello {$patient->first_name},\n\nYou have a new message from {$clinic}:\n\n"
                . \Illuminate\Support\Str::limit($data['body'], 500)
                . "\n\nLog in to the patient portal to read and reply: " . url('/portal')
            );
        }
        return redirect()->route('messages.patients.thread', $patient)->withFragment('pm-bottom');
    }

    /** Fire-and-forget email (mirrors ForumNotifier::sendEmailAsync). */
    private function notifyEmailAsync(string $to, string $subject, string $body): void
    {
        try {
            dispatch(function () use ($to, $subject, $body) {
                try {
                    \Illuminate\Support\Facades\Mail::raw($body, fn ($m) => $m->to($to)->subject($subject));
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('patient-msg mail failed: ' . $e->getMessage());
                }
            })->afterResponse();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('patient-msg dispatch failed: ' . $e->getMessage());
        }
    }

    /** Link a patient to this conversation (additive — never unlinks others). */
    public function linkPatient(MessageThread $thread, Request $request)
    {
        $u = auth()->user();
        $this->assertParticipant($thread, $u);
        $data = $request->validate(['patient_id' => 'required|integer|exists:patients,id']);

        if (!$thread->patientLinks()->where('patient_id', $data['patient_id'])->exists()) {
            $other = $thread->is_group ? null : $thread->activeParticipants->firstWhere('id', '!=', $u->id);
            \App\Models\PatientCommunication::create([
                'patient_id'        => $data['patient_id'],
                'mode'              => 'dm',
                'with_user_id'      => $other?->id,
                'message_thread_id' => $thread->id,
                'occurred_at'       => now(),
                'logged_by'         => $u->id,
            ]);
        }

        return redirect()->route('messages.thread', $thread);
    }

    /** Remove one patient's link from this conversation. */
    public function unlinkPatient(MessageThread $thread, \App\Models\Patient $patient)
    {
        $this->assertParticipant($thread, auth()->user());
        $thread->patientLinks()->where('patient_id', $patient->id)->delete();

        return redirect()->route('messages.thread', $thread);
    }

    /** Compose form — pick a recipient (any active user). */
    public function compose(Request $request)
    {
        $u = auth()->user();
        $recipients = User::where('is_active', true)
            ->where('has_system_access', true)
            ->where('id', '!=', $u->id)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
        $preselected = $request->integer('to');
        $patientId   = $request->integer('patient') ?: null;
        return view('forum.messages.compose', compact('recipients', 'preselected', 'patientId'));
    }

    public function storeCompose(Request $request)
    {
        $u = auth()->user();
        $data = $request->validate([
            'recipient_id' => 'required|integer|exists:users,id',
            'body'         => 'required|string|min:1|max:10000',
            'patient_id'   => 'nullable|integer|exists:patients,id',
        ]);
        if ((int) $data['recipient_id'] === $u->id) {
            return back()->withErrors(['recipient_id' => 'Pick someone other than yourself.'])->withInput();
        }

        // A patient-linked DM is keyed to (the two participants + that one
        // patient). Starting a DM from a patient's profile reuses a thread
        // only when it's already scoped to THAT patient; otherwise a fresh
        // dedicated thread is created — so each patient's discussion stays
        // isolated instead of piling into one shared thread. General
        // (no-patient) DMs reuse the single patient-less thread between the
        // two. Group threads are always new.
        $patientId = !empty($data['patient_id']) ? (int) $data['patient_id'] : null;

        $existing = MessageThread::where('is_group', false)
            ->whereHas('participants', fn ($q) => $q->where('users.id', $u->id))
            ->whereHas('participants', fn ($q) => $q->where('users.id', $data['recipient_id']))
            ->when($patientId === null,
                fn ($q) => $q->whereNull('patient_id'),
                fn ($q) => $q->where('patient_id', $patientId))
            ->first();

        $thread = $existing ?: DB::transaction(function () use ($u, $data, $patientId) {
            $t = MessageThread::create([
                'is_group'   => false,
                'created_by' => $u->id,
                'patient_id' => $patientId,
            ]);
            // Same pivot-column shape on every row — Eloquent's attach()
            // builds its INSERT from the keys of the first row, so a
            // recipient row missing last_read_at trips a column-count error.
            $now = now();
            $t->participants()->attach([
                $u->id                       => ['created_at' => $now, 'updated_at' => $now, 'last_read_at' => $now],
                (int) $data['recipient_id']  => ['created_at' => $now, 'updated_at' => $now, 'last_read_at' => null],
            ]);
            return $t;
        });

        // Link the patient this conversation is about (from the patient profile's
        // "New" button) as a dm-mode communication row, if not already linked.
        if (!empty($data['patient_id'])) {
            $alreadyLinked = \App\Models\PatientCommunication::where('message_thread_id', $thread->id)
                ->where('mode', 'dm')->where('patient_id', $data['patient_id'])->exists();
            if (!$alreadyLinked) {
                \App\Models\PatientCommunication::create([
                    'patient_id'        => (int) $data['patient_id'],
                    'mode'              => 'dm',
                    'with_user_id'      => (int) $data['recipient_id'],
                    'message_thread_id' => $thread->id,
                    'occurred_at'       => now(),
                    'logged_by'         => $u->id,
                ]);
            }
        }

        $msg = Message::create([
            'message_thread_id' => $thread->id,
            'user_id'           => $u->id,
            'body'              => $data['body'],
        ]);
        $thread->forceFill([
            'last_message_id' => $msg->id,
            'last_message_at' => $msg->created_at,
        ])->save();

        // Sender's read pointer up-to-date.
        DB::table('message_thread_user')
            ->where('message_thread_id', $thread->id)->where('user_id', $u->id)
            ->update(['last_read_at' => now(), 'updated_at' => now()]);

        app(ForumNotifier::class)->onDirectMessage($thread, $msg);

        return redirect()->route('messages.thread', $thread);
    }

    public function createGroupForm()
    {
        $u = auth()->user();
        abort_unless($u->can('messages.group'), 403);
        $users = User::where('is_active', true)
            ->where('has_system_access', true)
            ->where('id', '!=', $u->id)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
        return view('forum.messages.group-create', compact('users'));
    }

    public function storeGroup(Request $request)
    {
        $u = auth()->user();
        abort_unless($u->can('messages.group'), 403);

        $data = $request->validate([
            'subject'         => 'required|string|max:200',
            'participant_ids' => 'required|array|min:1',
            'participant_ids.*' => 'integer|exists:users,id',
            'body'            => 'required|string|min:1|max:10000',
        ]);

        $thread = DB::transaction(function () use ($u, $data) {
            $t = MessageThread::create([
                'subject'    => $data['subject'],
                'is_group'   => true,
                'created_by' => $u->id,
            ]);
            $now = now();
            $attach = [$u->id => ['created_at' => $now, 'updated_at' => $now, 'last_read_at' => $now]];
            foreach (array_unique($data['participant_ids']) as $pid) {
                if ((int) $pid === $u->id) continue;
                $attach[(int) $pid] = ['created_at' => $now, 'updated_at' => $now, 'last_read_at' => null];
            }
            $t->participants()->attach($attach);
            return $t;
        });

        $msg = Message::create([
            'message_thread_id' => $thread->id,
            'user_id'           => $u->id,
            'body'              => $data['body'],
        ]);
        $thread->forceFill([
            'last_message_id' => $msg->id,
            'last_message_at' => $msg->created_at,
        ])->save();
        DB::table('message_thread_user')
            ->where('message_thread_id', $thread->id)->where('user_id', $u->id)
            ->update(['last_read_at' => now(), 'updated_at' => now()]);

        app(ForumNotifier::class)->onDirectMessage($thread, $msg);

        return redirect()->route('messages.thread', $thread);
    }

    /** Leave a thread — stops further mail/wa, hides from inbox. */
    public function leave(MessageThread $thread)
    {
        $u = auth()->user();
        $this->assertParticipant($thread, $u);
        DB::table('message_thread_user')
            ->where('message_thread_id', $thread->id)->where('user_id', $u->id)
            ->update(['left_at' => now(), 'updated_at' => now()]);
        return redirect()->route('messages.inbox')->with('success', 'Left the conversation.');
    }

    public function toggleMute(MessageThread $thread)
    {
        $u = auth()->user();
        $this->assertParticipant($thread, $u);
        $cur = (bool) DB::table('message_thread_user')
            ->where('message_thread_id', $thread->id)->where('user_id', $u->id)->value('is_muted');
        DB::table('message_thread_user')
            ->where('message_thread_id', $thread->id)->where('user_id', $u->id)
            ->update(['is_muted' => !$cur, 'updated_at' => now()]);
        return back()->with('success', $cur ? 'Notifications unmuted.' : 'Notifications muted for this thread.');
    }

    /** Live unread count for the topbar bell badge. */
    public function unreadCount()
    {
        $u = auth()->user();
        $count = DB::table('messages')
            ->join('message_thread_user as p', function ($j) use ($u) {
                $j->on('p.message_thread_id', '=', 'messages.message_thread_id')
                  ->where('p.user_id', $u->id)
                  ->whereNull('p.left_at');
            })
            ->where('messages.user_id', '!=', $u->id)
            ->where(function ($q) {
                $q->whereColumn('messages.created_at', '>', 'p.last_read_at')
                  ->orWhereNull('p.last_read_at');
            })
            ->count();
        return response()->json(['count' => $count]);
    }

    private function assertParticipant(MessageThread $thread, User $user): void
    {
        if (!$this->isParticipant($thread, $user)) {
            abort(403, 'You are not a participant in this conversation.');
        }
    }

    /** True when the user is an active (non-left) participant of the thread. */
    private function isParticipant(MessageThread $thread, User $user): bool
    {
        $row = DB::table('message_thread_user')
            ->where('message_thread_id', $thread->id)
            ->where('user_id', $user->id)
            ->first();
        return $row && !$row->left_at;
    }
}
