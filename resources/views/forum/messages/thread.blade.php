@extends('layouts.app')
@section('title', $thread->titleFor(auth()->user()))
@section('page-title', $thread->titleFor(auth()->user()))

@push('styles')
<style>
    /* WhatsApp-style chat */
    .wa-card  { position:relative; border:1px solid var(--sc-border); border-radius:12px; overflow:hidden; display:flex; flex-direction:column; box-shadow:0 2px 10px rgba(15,31,61,.06); }
    .wa-list  { padding:16px 3%; max-height:62vh; overflow-y:auto; display:flex; flex-direction:column; gap:3px;
        background-color:#efeae2;
        background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='40' height='40'%3E%3Cg fill='%23d8cfc4' fill-opacity='0.25'%3E%3Ccircle cx='6' cy='6' r='1.5'/%3E%3Ccircle cx='26' cy='18' r='1.5'/%3E%3Ccircle cx='14' cy='30' r='1.5'/%3E%3C/g%3E%3C/svg%3E"); }

    /* Wide bubbles — out (right) and in (left) extend well past centre so
       they overlap horizontally, like a busy WhatsApp thread. */
    .wa-row   { display:flex; max-width:90%; gap:6px; align-items:flex-end; }
    .wa-row.out { align-self:flex-end; justify-content:flex-end; }
    .wa-row.in  { align-self:flex-start; }

    /* incoming avatar */
    .wa-avatar { width:28px; height:28px; border-radius:50%; flex-shrink:0; color:#fff; font-weight:700;
        font-size:11px; display:flex; align-items:center; justify-content:center; margin-bottom:2px; }

    .wa-bubble { position:relative; padding:6px 9px 8px; border-radius:8px; font-size:14px; line-height:1.4; color:#111b21;
        word-wrap:break-word; box-shadow:0 1px .5px rgba(11,20,26,.13); min-width:64px; }
    .wa-text { white-space:pre-wrap; word-wrap:break-word; }

    /* attachments inside a bubble */
    .wa-img { display:block; margin:2px 0; }
    .wa-img img { max-width:240px; max-height:280px; border-radius:6px; display:block; }
    .wa-file { display:flex; align-items:center; gap:9px; margin:3px 0; padding:8px 10px; background:rgba(0,0,0,.05);
        border-radius:6px; text-decoration:none; color:inherit; max-width:250px; }
    .wa-file i { font-size:20px; color:#00a884; flex-shrink:0; }
    .wa-file-name { font-size:12.5px; font-weight:600; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .wa-file-size { font-size:10.5px; color:rgba(0,0,0,.45); margin-left:auto; white-space:nowrap; }

    /* composer attach button + selected-files preview */
    .wa-attach { display:flex; align-items:center; justify-content:center; width:42px; height:46px; cursor:pointer; color:#54656f; font-size:19px; flex-shrink:0; }
    .wa-attach:hover { color:#00a884; }
    .wa-file-preview { display:flex; flex-wrap:wrap; gap:6px; }
    .wa-file-preview:empty { display:none; }
    .wa-file-preview .chip { background:#fff; border:1px solid var(--sc-border); border-radius:14px; padding:3px 10px;
        font-size:11.5px; display:flex; align-items:center; gap:6px; max-width:200px; }
    .wa-file-preview .chip span { overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .wa-file-preview .chip i { color:#00a884; }
    .wa-file-preview .chip-x { border:0; background:none; cursor:pointer; color:#94a3b8; font-size:15px; line-height:1; padding:0 0 0 2px; }
    .wa-file-preview .chip-x:hover { color:#b91c1c; }
    .wa-err { padding:8px 14px; background:#fde8e8; color:#b91c1c; font-size:12.5px; }
    .wa-row.out .wa-bubble { background:#d9fdd3; border-top-right-radius:0; }
    .wa-row.in  .wa-bubble { background:#ffffff; border-top-left-radius:0; }

    /* little tails */
    .wa-row.out .wa-bubble::before { content:''; position:absolute; top:0; right:-7px; width:0; height:0;
        border:7px solid transparent; border-top-color:#d9fdd3; border-right-width:0; }
    .wa-row.in  .wa-bubble::before  { content:''; position:absolute; top:0; left:-7px; width:0; height:0;
        border:7px solid transparent; border-top-color:#ffffff; border-left-width:0; }

    .wa-sender { font-size:12.5px; font-weight:700; margin-bottom:2px; }
    .wa-meta   { float:right; margin:6px 0 -2px 10px; font-size:10.5px; color:rgba(0,0,0,.45); white-space:nowrap; }
    .wa-meta .tick { margin-left:2px; }
    .wa-meta .tick.seen { color:#53bdeb; }
    .wa-meta .tick.sent { color:rgba(0,0,0,.4); }

    /* jump-to-bottom */
    .wa-jump { position:absolute; right:18px; bottom:86px; width:40px; height:40px; border-radius:50%;
        background:var(--sc-surface); border:1px solid var(--sc-border); box-shadow:var(--shadow-md);
        color:var(--sc-teal); cursor:pointer; display:none; align-items:center; justify-content:center; font-size:15px; z-index:5; }
    .wa-jump.show { display:flex; }

    /* empty chat */
    .wa-empty { margin:auto; text-align:center; color:#54656f; padding:40px 20px; }
    .wa-empty i { font-size:34px; opacity:.4; }

    .wa-day { align-self:center; background:#ffffff; color:#54656f; font-size:11.5px; font-weight:500;
        border-radius:8px; padding:4px 12px; margin:10px 0; box-shadow:0 1px .5px rgba(11,20,26,.13); }

    .wa-composer { position:relative; display:flex; gap:10px; padding:12px 14px; background:#f0f2f5; border-top:1px solid var(--sc-border); align-items:flex-end; }

    /* @mentions */
    .wa-mention { color:#00a884; font-weight:600; background:rgba(0,168,132,.12); border-radius:3px; padding:0 2px; }
    .wa-mention-box { position:absolute; bottom:100%; left:56px; margin-bottom:6px; background:#fff; border:1px solid var(--sc-border);
        border-radius:10px; box-shadow:var(--shadow-md); max-height:210px; overflow-y:auto; z-index:30; min-width:210px; }
    .wa-mention-box[hidden] { display:none; }
    .wa-mention-box .mi { padding:8px 12px; cursor:pointer; font-size:13px; display:flex; align-items:center; gap:8px; }
    .wa-mention-box .mi:hover, .wa-mention-box .mi.active { background:var(--sc-bg); }
    .wa-mention-box .mi .av { width:24px; height:24px; border-radius:50%; color:#fff; font-size:10px; display:flex; align-items:center; justify-content:center; font-weight:700; flex-shrink:0; }
    .wa-composer textarea { flex:1 1 auto; width:100%; padding:13px 18px; border:0; border-radius:24px; font-family:inherit; font-size:15px;
        min-height:52px; max-height:160px; resize:none; background:#fff; box-shadow:0 1px .5px rgba(11,20,26,.13); }
    .wa-composer textarea:focus { outline:none; }
    .wa-send { background:#00a884; color:#fff; border:0; border-radius:50%; width:46px; height:46px; cursor:pointer; font-size:17px; flex-shrink:0; }
    .wa-send:hover { background:#017561; }

    /* quoted reply inside a bubble */
    .wa-quote { display:block; border-left:3px solid #00a884; background:rgba(0,0,0,.05); border-radius:4px;
        padding:4px 8px; margin-bottom:4px; text-decoration:none; color:inherit; }
    .wa-quote .qauthor { display:block; font-size:11.5px; font-weight:700; color:#00a884; }
    .wa-quote .qtext { display:block; font-size:12px; color:rgba(0,0,0,.6); overflow:hidden; text-overflow:ellipsis; white-space:nowrap; max-width:230px; }

    /* deleted/unsent placeholder */
    .wa-deleted { color:rgba(0,0,0,.45); font-style:italic; font-size:13px; }
    .wa-edited { font-style:italic; opacity:.8; }
    /* Owner inline edit form */
    .wa-edit { margin-top:6px; }
    .wa-edit textarea { width:100%; min-width:220px; box-sizing:border-box; font-size:14px; line-height:1.4;
        border:1px solid var(--sc-border); border-radius:6px; padding:6px 8px; resize:vertical; }
    .wa-edit-btns { display:flex; gap:6px; margin-top:5px; }
    .wa-edit-save { background:#00a884; color:#fff; border:0; border-radius:5px; padding:4px 12px; font-size:12.5px; cursor:pointer; }
    .wa-edit-cancel { background:#fff; color:#54656f; border:1px solid var(--sc-border); border-radius:5px; padding:4px 12px; font-size:12.5px; cursor:pointer; }

    /* per-message actions (reply / react / edit / delete). Faintly visible by
       default so they're discoverable (incl. touch devices, which have no
       hover), crisp on hover. */
    .wa-actions { position:relative; display:flex; align-items:center; gap:2px; opacity:.5; transition:opacity .12s; align-self:center; }
    .wa-row:hover .wa-actions { opacity:1; }
    @media (hover: none) { .wa-actions { opacity:1; } }
    .wa-row.out .wa-actions { order:-1; }            /* sit left of my own bubble */
    .wa-act { border:0; background:rgba(255,255,255,.85); box-shadow:0 1px 3px rgba(0,0,0,.12); color:#54656f;
        width:26px; height:26px; border-radius:50%; cursor:pointer; font-size:12px; display:flex; align-items:center; justify-content:center; }
    .wa-act:hover { color:#00a884; }
    .wa-act.del:hover { color:#b91c1c; }
    .wa-act.starred { color:#f5b301; }
    .wa-star-mark { color:#f5b301; font-size:10px; margin-right:3px; }

    /* reaction chips under a bubble */
    .wa-react-form { margin:0; }
    .wa-reacts { display:flex; flex-wrap:wrap; gap:3px; margin-top:2px; }
    .wa-row.out .wa-reacts { justify-content:flex-end; }
    .wa-react-chip { border:1px solid var(--sc-border); background:#fff; border-radius:11px; padding:1px 7px; font-size:11.5px;
        cursor:pointer; line-height:1.5; }
    .wa-react-chip.mine { background:#d9fdd3; border-color:#00a884; }

    /* emoji picker popover */
    .wa-picker { position:absolute; z-index:6; background:#fff; border:1px solid var(--sc-border); border-radius:20px;
        box-shadow:var(--shadow-md); padding:4px 6px; display:flex; gap:2px; margin-top:4px; }
    .wa-picker[hidden] { display:none; }
    .wa-picker button { border:0; background:none; cursor:pointer; font-size:18px; padding:2px 4px; border-radius:50%; line-height:1; }
    .wa-picker button:hover { background:#f0f2f5; }

    /* reply-context bar above the composer */
    .wa-reply-bar { display:none; align-items:center; gap:10px; padding:8px 14px; background:#eef6f3; border-top:1px solid var(--sc-border);
        border-left:3px solid #00a884; }
    .wa-reply-bar.show { display:flex; }
    .wa-reply-bar .rb-text { flex:1 1 auto; font-size:12.5px; color:#374151; overflow:hidden; }
    .wa-reply-bar .rb-text b { color:#00a884; display:block; font-size:11.5px; }
    .wa-reply-bar .rb-x { border:0; background:none; cursor:pointer; color:#94a3b8; font-size:18px; }

    /* in-conversation search */
    .wa-search { display:flex; align-items:center; gap:8px; padding:8px 12px; background:#f0f2f5; border-bottom:1px solid var(--sc-border); }
    .wa-search[hidden] { display:none; }
    .wa-search input { flex:1 1 auto; border:1px solid var(--sc-border); border-radius:18px; padding:7px 14px; font-size:13px; background:#fff; }
    .wa-search input:focus { outline:none; border-color:#00a884; }
    .wa-search button { border:0; background:none; cursor:pointer; color:#54656f; font-size:14px; padding:4px 6px; line-height:1; }
    .wa-search button:hover { color:#00a884; }
    .wa-search-count { font-size:12px; color:#54656f; min-width:56px; text-align:center; white-space:nowrap; }
    mark.wa-hl { background:#ffe08a; color:inherit; border-radius:2px; padding:0 1px; }
    mark.wa-hl.cur { background:#f5a623; }

    /* patient-link bar */
    .pt-link-bar { display:flex; align-items:center; gap:10px; flex-wrap:wrap; background:var(--sc-surface); border:1px solid var(--sc-border);
        border-radius:10px; padding:8px 14px; margin-bottom:12px; position:relative; }
    .pt-link-bar .muted { color:var(--sc-muted); font-size:13px; display:flex; align-items:center; gap:7px; }
    .ptl-chip { display:flex; align-items:center; gap:8px; font-size:13.5px; }
    .ptl-chip > i { color:var(--sc-teal); }
    .ptl-chip a { color:var(--sc-teal); text-decoration:none; font-weight:600; }
    .ptl-chip .sub { color:var(--sc-muted); font-weight:normal; }
    .ptl-actions { margin-left:auto; display:flex; gap:6px; align-items:center; }
    .ptl-btn { border:1px solid var(--sc-border); background:var(--sc-bg); border-radius:6px; padding:4px 10px; font-size:12px; cursor:pointer; }
    .ptl-btn:hover { border-color:var(--sc-teal); color:var(--sc-teal); }
    .ptl-unlink:hover { border-color:#b91c1c; color:#b91c1c; }
    .ptl-search { position:absolute; top:100%; left:0; right:0; z-index:20; background:var(--sc-surface); border:1px solid var(--sc-border);
        border-radius:10px; box-shadow:var(--shadow-md); padding:10px; margin-top:6px; }
    .ptl-search[hidden] { display:none; }
    .ptl-search > input { width:100%; border:1px solid var(--sc-border); border-radius:8px; padding:8px 12px; font-size:13px; }
    .ptl-search > input:focus { outline:none; border-color:var(--sc-teal); }
    .ptl-results { margin-top:8px; max-height:240px; overflow-y:auto; }
    .ptl-results .res { display:flex; align-items:center; gap:10px; padding:8px 10px; border-radius:8px; cursor:pointer; }
    .ptl-results .res:hover { background:var(--sc-bg); }
    .ptl-results .res .nm { font-weight:600; font-size:13px; }
    .ptl-results .res .meta { font-size:11.5px; color:var(--sc-muted); }
    .ptl-results .empty { color:var(--sc-muted); font-size:12.5px; padding:8px; }
    .ptl-tag { display:inline-flex; align-items:center; gap:6px; background:var(--sc-teal-glow); border:1px solid var(--sc-border); border-radius:14px; padding:3px 10px; font-size:12.5px; }
    .ptl-tag a { color:var(--sc-teal); text-decoration:none; font-weight:600; }
    .ptl-tag .sub { color:var(--sc-muted); font-size:11px; }
    .ptl-tag-x { border:0; background:none; cursor:pointer; color:#94a3b8; font-size:15px; line-height:1; padding:0; }
    .ptl-tag-x:hover { color:#b91c1c; }
</style>
@endpush

@section('content')
@php $readOnly = $readOnly ?? false; @endphp
@if($readOnly)
    <div style="background:#fff4e5;border:1px solid #f5a623;border-radius:8px;padding:8px 14px;margin-bottom:12px;font-size:13px;color:#8a5a00">
        <i class="fa-solid fa-eye"></i> Oversight view — you're not a participant, so this conversation is <strong>read-only</strong>.
    </div>
@endif
<div class="page-header">
    <div class="page-header-left">
        <a href="{{ route('messages.inbox') }}" class="text-small" style="text-decoration:none"><i class="fa-solid fa-arrow-left"></i> Inbox</a>
        <h1>{{ $thread->titleFor(auth()->user()) }}</h1>
        <div class="text-small text-muted">
            @if($thread->is_group)
                <i class="fa-solid fa-people-group"></i> Group of {{ $thread->activeParticipants->count() }}:
                {{ $thread->activeParticipants->pluck('name')->implode(', ') }}
            @else
                @php $other = $thread->activeParticipants->firstWhere('id','!=',auth()->id()); @endphp
                with {{ $other?->name ?? '—' }}
            @endif
        </div>
    </div>
    <div class="page-header-right">
        <button type="button" class="btn btn-outline btn-sm" onclick="scToggleSearch()" title="Search this conversation"><i class="fa-solid fa-magnifying-glass"></i></button>
        @if(!$readOnly)
        @php $me = $thread->activeParticipants->firstWhere('id', auth()->id()); $muted = $me?->pivot?->is_muted; @endphp
        <form method="POST" action="{{ route('messages.mute', $thread) }}" style="display:inline">
            @csrf
            <button class="btn btn-outline btn-sm"><i class="fa-{{ $muted ? 'solid' : 'regular' }} fa-bell-slash"></i> {{ $muted ? 'Unmute' : 'Mute' }}</button>
        </form>
        <form method="POST" action="{{ route('messages.leave', $thread) }}" style="display:inline" onsubmit="return confirm('Leave this conversation?')">
            @csrf
            <button class="btn btn-outline btn-sm" style="color:#b91c1c"><i class="fa-solid fa-arrow-right-from-bracket"></i> Leave</button>
        </form>
        @endif
    </div>
</div>

<div class="pt-link-bar">
    @php $linked = $thread->linkedPatients(); @endphp
    <i class="fa-solid fa-user-injured" style="color:var(--sc-teal)"></i>
    @if($linked->isEmpty())
        <span class="muted">Not linked to any patient</span>
    @else
        <div style="display:flex;flex-wrap:wrap;gap:6px;align-items:center">
            @foreach($linked as $p)
                <span class="ptl-tag">
                    <a href="{{ route('patients.show', $p) }}" target="_blank" rel="noopener">{{ $p->full_name }}</a>
                    <span class="sub">{{ $p->jhc_id }}</span>
                    @if(!$readOnly)
                        <form method="POST" action="{{ route('messages.patient.unlink', [$thread, $p]) }}" style="display:inline" onsubmit="return confirm('Unlink {{ $p->full_name }} from this conversation?')">
                            @csrf
                            <button class="ptl-tag-x" type="submit" title="Unlink">&times;</button>
                        </form>
                    @endif
                </span>
            @endforeach
        </div>
    @endif

    @if(!$readOnly)
    <div class="ptl-actions"><button type="button" class="ptl-btn" onclick="ptlOpen()"><i class="fa-solid fa-link"></i> Link a patient</button></div>
    <div class="ptl-search" id="ptl-search" hidden>
        <form method="POST" action="{{ route('messages.patient', $thread) }}" id="ptl-form">@csrf<input type="hidden" name="patient_id" id="ptl-patient-id"></form>
        <input type="text" id="ptl-q" placeholder="Search patient by name, ID or phone…" autocomplete="off">
        <div class="ptl-results" id="ptl-results"></div>
    </div>
    @endif
</div>

<div class="wa-card">
    <div class="wa-search" id="wa-search" hidden>
        <i class="fa-solid fa-magnifying-glass" style="color:#54656f"></i>
        <input type="text" id="wa-search-input" placeholder="Search this conversation" autocomplete="off">
        <span class="wa-search-count" id="wa-search-count"></span>
        <button type="button" onclick="scSearchNav(-1)" title="Previous"><i class="fa-solid fa-chevron-up"></i></button>
        <button type="button" onclick="scSearchNav(1)" title="Next"><i class="fa-solid fa-chevron-down"></i></button>
        <button type="button" onclick="scToggleSearch()" title="Close" style="font-size:18px">&times;</button>
    </div>
    <div class="wa-list" id="chat-list">
        @php
            $lastDay = null;
            // Names to highlight as @mentions (longest first so "@Sample Admin"
            // wins over "@Robert"). Used to render mentions safely below.
            $mentionNames = $thread->activeParticipants->pluck('name')->filter()
                ->sortByDesc(fn ($n) => mb_strlen($n))->values();
        @endphp
        @forelse($messages as $m)
            @php
                $mine = $m->user_id === auth()->id();
                $day  = $m->created_at->format('d M Y');
                $color = substr(md5($m->user_id), 0, 6);
            @endphp
            @if($day !== $lastDay)
                <div class="wa-day">{{ $m->created_at->isToday() ? 'Today' : ($m->created_at->isYesterday() ? 'Yesterday' : $m->created_at->format('d M Y')) }}</div>
                @php $lastDay = $day; @endphp
            @endif
            @php $reacts = $m->reactions->groupBy('emoji'); @endphp
            <div class="wa-row {{ $mine ? 'out' : 'in' }}" id="msg-{{ $m->id }}">
                @if($thread->is_group && !$mine)
                    <div class="wa-avatar" style="background:#{{ $color }}" title="{{ $m->author?->name }}">{{ strtoupper(mb_substr($m->author?->name ?? '?', 0, 1)) }}</div>
                @endif
                <div style="display:flex;flex-direction:column;max-width:100%">
                    <div class="wa-bubble">
                        @if($m->isDeleted())
                            <span class="wa-deleted"><i class="fa-solid fa-ban"></i> This message was deleted</span><span class="wa-meta">{{ $m->created_at->format('H:i') }}</span>
                        @else
                            @if($thread->is_group && !$mine)
                                <div class="wa-sender" style="color:#{{ $color }}">{{ $m->author?->name ?? '—' }}</div>
                            @endif
                            @if($m->replyTo)
                                <a class="wa-quote" href="#msg-{{ $m->replyTo->id }}">
                                    <span class="qauthor">{{ $m->replyTo->user_id === auth()->id() ? 'You' : ($m->replyTo->author?->name ?? '—') }}</span>
                                    <span class="qtext">{{ $m->replyTo->isDeleted() ? 'deleted message' : \Illuminate\Support\Str::limit($m->replyTo->body !== '' ? $m->replyTo->body : '📎 attachment', 60) }}</span>
                                </a>
                            @endif
                            @foreach($m->attachments as $att)
                                @if($att->isImage())
                                    <a class="wa-img" href="{{ route('messages.attachment.show', $att) }}" target="_blank" rel="noopener">
                                        <img src="{{ route('messages.attachment.show', $att) }}" alt="{{ $att->original_name }}" loading="lazy">
                                    </a>
                                @else
                                    <a class="wa-file" href="{{ route('messages.attachment.show', $att) }}" target="_blank" rel="noopener" title="{{ $att->original_name }}">
                                        <i class="fa-solid {{ $att->icon() }}"></i>
                                        <span class="wa-file-name">{{ $att->original_name }}</span>
                                        <span class="wa-file-size">{{ $att->humanSize() }}</span>
                                    </a>
                                @endif
                            @endforeach
                            @if(trim($m->body) !== '')@php
                                $bodyHtml = e($m->body);
                                foreach ($mentionNames as $nm) {
                                    $bodyHtml = preg_replace('/@' . preg_quote(e($nm), '/') . '\b/u', '<span class="wa-mention">@' . e($nm) . '</span>', $bodyHtml);
                                }
                            @endphp<span class="wa-text">{!! $bodyHtml !!}</span>@endif<span class="wa-meta">{!! in_array($m->id, $starredIds ?? []) ? '<i class="fa-solid fa-star wa-star-mark" title="Starred"></i>' : '' !!}{!! $m->edited_at ? '<span class="wa-edited" title="Edited">edited</span> ' : '' !!}{{ $m->created_at->format('H:i') }}@if($mine)@php $seen = $allSeenUpTo && $m->created_at->lte($allSeenUpTo); @endphp@if($seen)<i class="fa-solid fa-check-double tick seen" title="Seen"></i>@else<i class="fa-solid fa-check tick sent" title="Sent"></i>@endif @endif</span>
                        @endif
                        @if(((($canModerate ?? false) || $mine)) && !$m->isDeleted())
                            <form class="wa-edit" id="edit-{{ $m->id }}" hidden method="POST" action="{{ route('messages.edit', [$thread, $m]) }}">
                                @csrf
                                <textarea name="body" rows="2" maxlength="10000" required>{{ $m->body }}</textarea>
                                <div class="wa-edit-btns">
                                    <button type="submit" class="wa-edit-save">Save edit</button>
                                    <button type="button" class="wa-edit-cancel" onclick="scEditCancel({{ $m->id }})">Cancel</button>
                                </div>
                            </form>
                        @endif
                    </div>
                    @if(!$m->isDeleted() && $reacts->isNotEmpty())
                        @if($readOnly)
                            <div class="wa-reacts">
                                @foreach($reacts as $emoji => $group)
                                    <span class="wa-react-chip {{ $group->contains('user_id', auth()->id()) ? 'mine' : '' }}">{{ $emoji }} {{ $group->count() }}</span>
                                @endforeach
                            </div>
                        @else
                            <form class="wa-react-form" method="POST" action="{{ route('messages.react', [$thread, $m]) }}">
                                @csrf
                                <div class="wa-reacts">
                                    @foreach($reacts as $emoji => $group)
                                        <button class="wa-react-chip {{ $group->contains('user_id', auth()->id()) ? 'mine' : '' }}" name="emoji" value="{{ $emoji }}">{{ $emoji }} {{ $group->count() }}</button>
                                    @endforeach
                                </div>
                            </form>
                        @endif
                    @endif
                </div>
                @php $owns = $canModerate ?? false; @endphp
                @if(!$m->isDeleted() && (!$readOnly || $owns))
                    <div class="wa-actions">
                        @if(!$readOnly)
                            <button type="button" class="wa-act" title="Reply" onclick="scReply({{ $m->id }}, this)"
                                    data-author="{{ $mine ? 'You' : ($m->author?->name ?? '—') }}"
                                    data-text="{{ \Illuminate\Support\Str::limit(trim($m->body) !== '' ? $m->body : '📎 attachment', 50) }}">
                                <i class="fa-solid fa-reply"></i>
                            </button>
                            <button type="button" class="wa-act" title="React" onclick="scPicker(this)"><i class="fa-regular fa-face-smile"></i></button>
                            <div class="wa-picker" hidden>
                                <form method="POST" action="{{ route('messages.react', [$thread, $m]) }}">@csrf
                                    @foreach(\App\Models\MessageReaction::ALLOWED as $e)
                                        <button type="submit" name="emoji" value="{{ $e }}">{{ $e }}</button>
                                    @endforeach
                                </form>
                            </div>
                        @endif
                        @php $isStarred = in_array($m->id, $starredIds ?? []); @endphp
                        <form method="POST" action="{{ route('messages.star', [$thread, $m]) }}" style="margin:0">
                            @csrf
                            <button type="submit" class="wa-act {{ $isStarred ? 'starred' : '' }}" title="{{ $isStarred ? 'Unstar' : 'Star this message' }}"><i class="fa-{{ $isStarred ? 'solid' : 'regular' }} fa-star"></i></button>
                        </form>
                        @if($owns || $mine)
                            <button type="button" class="wa-act" title="Edit message" onclick="scEdit({{ $m->id }})"><i class="fa-solid fa-pen"></i></button>
                        @endif
                        @if($owns || ($mine && !$readOnly))
                            <form method="POST" action="{{ route('messages.unsend', [$thread, $m]) }}" onsubmit="return confirm('{{ ($owns && !$mine) ? 'Delete this message for everyone? (owner moderation)' : 'Unsend this message? Its text and files will be removed for everyone.' }}')">
                                @csrf
                                <button type="submit" class="wa-act del" title="{{ ($owns && !$mine) ? 'Delete (owner)' : 'Unsend' }}"><i class="fa-solid fa-trash"></i></button>
                            </form>
                        @endif
                    </div>
                @endif
            </div>
        @empty
            <div class="wa-empty">
                <i class="fa-regular fa-comments"></i>
                <p style="margin-top:10px">No messages yet — say hi 👋</p>
            </div>
        @endforelse
    </div>
    <button type="button" class="wa-jump" id="jump-btn" title="Jump to latest"><i class="fa-solid fa-arrow-down"></i></button>

    @if($messages->hasPages())
        <div style="padding:8px 14px;background:#f0f2f5">{{ $messages->links() }}</div>
    @endif

    @if($readOnly)
        <div style="padding:14px;text-align:center;color:var(--sc-muted);font-size:13px;background:#f0f2f5;border-top:1px solid var(--sc-border)">
            <i class="fa-solid fa-eye"></i> Read-only — you're viewing this conversation for oversight and can't post in it.
        </div>
    @else
    @if($errors->any())
        <div class="wa-err">{{ $errors->first() }}</div>
    @endif

    <div class="wa-reply-bar" id="reply-bar">
        <i class="fa-solid fa-reply" style="color:#00a884"></i>
        <div class="rb-text"><b id="rb-author"></b><span id="rb-snippet"></span></div>
        <button type="button" class="rb-x" onclick="scCancelReply()" title="Cancel reply">&times;</button>
    </div>

    <form method="POST" action="{{ route('messages.send', $thread) }}" class="wa-composer" enctype="multipart/form-data" id="composer">
        @csrf
        <input type="hidden" name="reply_to_message_id" id="reply-input" value="">
        <div class="wa-mention-box" id="mention-box" hidden></div>
        <label class="wa-attach" title="Attach files (images, PDF, Word, Excel — max 10 MB each)">
            <i class="fa-solid fa-paperclip"></i>
            <input type="file" name="attachments[]" id="file-input" multiple hidden
                   accept=".jpg,.jpeg,.png,.gif,.webp,.heic,.pdf,.doc,.docx,.xls,.xlsx,.csv,.txt">
        </label>
        <div style="flex:1 1 auto;display:flex;flex-direction:column;gap:6px">
            <div class="wa-file-preview" id="file-preview"></div>
            <textarea name="body" maxlength="10000" placeholder="Type a message"
                      onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();this.form.requestSubmit();}"></textarea>
        </div>
        <button class="wa-send" type="submit" title="Send (Enter)"><i class="fa-solid fa-paper-plane"></i></button>
    </form>
    @endif
</div>

<script>
(function () {
    var el = document.getElementById('chat-list');
    var jump = document.getElementById('jump-btn');
    if (!el) return;
    var toBottom = function () { el.scrollTop = el.scrollHeight; };
    toBottom();
    el.addEventListener('scroll', function () {
        var nearBottom = el.scrollHeight - el.scrollTop - el.clientHeight < 120;
        if (jump) jump.classList.toggle('show', !nearBottom);
    });
    if (jump) jump.addEventListener('click', toBottom);

    // ── Live updates: poll for new messages / read-state, refresh on change ──
    var pollUrl = @json(route('messages.poll', $thread));
    var threadUrl = @json(route('messages.thread', $thread));
    function lastRowId() {
        var rows = el.querySelectorAll('.wa-row[id^="msg-"]');
        return rows.length ? parseInt(rows[rows.length - 1].id.slice(4), 10) : 0;
    }
    var liveLastId = lastRowId();
    var liveSig = null;
    var refreshing = false;
    function liveRefresh() {
        if (refreshing) return;
        // Don't clobber an in-progress edit or an open reaction picker.
        if (el.querySelector('.wa-edit:not([hidden])') || el.querySelector('.wa-picker:not([hidden])')) return;
        refreshing = true;
        var nearBottom = el.scrollHeight - el.scrollTop - el.clientHeight < 120;
        fetch(threadUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.ok ? r.text() : null; })
            .then(function (html) {
                if (html) {
                    var fresh = new DOMParser().parseFromString(html, 'text/html').querySelector('#chat-list');
                    if (fresh) { el.innerHTML = fresh.innerHTML; liveLastId = lastRowId(); if (nearBottom) toBottom(); }
                }
            })
            .catch(function () {})
            .finally(function () { refreshing = false; });
    }
    function livePoll() {
        if (document.hidden) return;
        fetch(pollUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (d) {
                if (!d) return;
                if (d.lastId > liveLastId || (liveSig !== null && d.seenSig !== liveSig)) liveRefresh();
                liveLastId = Math.max(liveLastId, d.lastId);
                liveSig = d.seenSig;
            })
            .catch(function () {});
    }
    setInterval(livePoll, 9000);

    // Attachments: accumulate files across clicks (the native input replaces
    // them otherwise), allow removing individual ones, and block empty sends.
    var composer = document.getElementById('composer');
    var fi = document.getElementById('file-input');
    var fp = document.getElementById('file-preview');
    var bag = new DataTransfer();   // the running set of chosen files

    function renderPreview() {
        fp.innerHTML = '';
        Array.prototype.forEach.call(bag.files, function (f, idx) {
            var c = document.createElement('span');
            c.className = 'chip';
            var icon = document.createElement('i'); icon.className = 'fa-solid fa-paperclip';
            var name = document.createElement('span'); name.textContent = f.name;
            var x = document.createElement('button');
            x.type = 'button'; x.className = 'chip-x'; x.innerHTML = '&times;'; x.title = 'Remove';
            x.addEventListener('click', function () {
                var next = new DataTransfer();
                Array.prototype.forEach.call(bag.files, function (ff, i) { if (i !== idx) next.items.add(ff); });
                bag = next; fi.files = bag.files; renderPreview();
            });
            c.appendChild(icon); c.appendChild(name); c.appendChild(x);
            fp.appendChild(c);
        });
    }

    if (fi) fi.addEventListener('change', function () {
        // Append the newly-picked files to the running bag, capped at 10.
        Array.prototype.forEach.call(fi.files, function (f) {
            if (bag.files.length < 10) bag.items.add(f);
        });
        fi.files = bag.files;
        renderPreview();
    });
    if (composer) composer.addEventListener('submit', function (e) {
        var ta = composer.querySelector('textarea[name=body]');
        var hasText = ta && ta.value.trim().length > 0;
        var hasFiles = fi && fi.files.length > 0;
        if (!hasText && !hasFiles) e.preventDefault();
    });

    // ── Reply context ──
    window.scReply = function (id, btn) {
        var input = document.getElementById('reply-input');
        if (input) input.value = id;
        document.getElementById('rb-author').textContent = btn.getAttribute('data-author');
        document.getElementById('rb-snippet').textContent = ' ' + btn.getAttribute('data-text');
        document.getElementById('reply-bar').classList.add('show');
        var ta = document.querySelector('#composer textarea');
        if (ta) ta.focus();
    };
    window.scCancelReply = function () {
        var input = document.getElementById('reply-input');
        if (input) input.value = '';
        document.getElementById('reply-bar').classList.remove('show');
    };

    // ── Emoji reaction picker ──
    window.scPicker = function (btn) {
        var p = btn.nextElementSibling;             // the .wa-picker
        var willOpen = p.hasAttribute('hidden');
        document.querySelectorAll('.wa-picker').forEach(function (x) { x.setAttribute('hidden', ''); });
        if (willOpen) p.removeAttribute('hidden');
    };
    document.addEventListener('click', function (e) {
        if (!e.target.closest('.wa-actions')) {
            document.querySelectorAll('.wa-picker').forEach(function (x) { x.setAttribute('hidden', ''); });
        }
    });

    // ── Owner message editing ──
    window.scEdit = function (id) {
        var f = document.getElementById('edit-' + id);
        if (!f) return;
        var open = f.hasAttribute('hidden');
        if (open) {
            f.removeAttribute('hidden');
            var t = f.querySelector('textarea');
            t.focus(); t.setSelectionRange(t.value.length, t.value.length);
        } else {
            f.setAttribute('hidden', '');
        }
    };
    window.scEditCancel = function (id) {
        var f = document.getElementById('edit-' + id);
        if (f) f.setAttribute('hidden', '');
    };

    // ── In-conversation search (client-side, current page) ──
    var searchBar = document.getElementById('wa-search');
    var searchInput = document.getElementById('wa-search-input');
    var searchCount = document.getElementById('wa-search-count');
    var texts = Array.prototype.slice.call(document.querySelectorAll('#chat-list .wa-text'));
    texts.forEach(function (el) { el.dataset.orig = el.innerHTML; }); // keep mention markup
    var hits = [];        // highlighted <mark> nodes, in document order
    var cur = -1;

    function esc(s) { return s.replace(/[&<>]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;' }[c]; }); }
    function rxEsc(s) { return s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'); }

    function clearHits() {
        texts.forEach(function (el) { el.innerHTML = el.dataset.orig; }); // restore mentions
        hits = []; cur = -1;
    }

    function runSearch(term) {
        clearHits();
        term = (term || '').trim();
        if (!term) { searchCount.textContent = ''; return; }
        var re = new RegExp('(' + rxEsc(term) + ')', 'gi');
        texts.forEach(function (el) {
            var plain = el.textContent;
            if (re.test(plain)) {
                re.lastIndex = 0;
                el.innerHTML = esc(plain).replace(new RegExp('(' + rxEsc(esc(term)) + ')', 'gi'), '<mark class="wa-hl">$1</mark>');
            }
        });
        hits = Array.prototype.slice.call(document.querySelectorAll('#chat-list mark.wa-hl'));
        searchCount.textContent = hits.length ? '1/' + hits.length : 'No matches';
        if (hits.length) { cur = 0; focusHit(); }
    }

    function focusHit() {
        hits.forEach(function (h) { h.classList.remove('cur'); });
        var h = hits[cur];
        if (!h) return;
        h.classList.add('cur');
        h.scrollIntoView({ block: 'center', behavior: 'smooth' });
        searchCount.textContent = (cur + 1) + '/' + hits.length;
    }

    window.scToggleSearch = function () {
        if (!searchBar) return;
        var show = searchBar.hasAttribute('hidden');
        if (show) { searchBar.removeAttribute('hidden'); searchInput.focus(); }
        else { searchBar.setAttribute('hidden', ''); searchInput.value = ''; clearHits(); searchCount.textContent = ''; }
    };
    window.scSearchNav = function (dir) {
        if (!hits.length) return;
        cur = (cur + dir + hits.length) % hits.length;
        focusHit();
    };
    if (searchInput) {
        searchInput.addEventListener('input', function () { runSearch(this.value); });
        searchInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); scSearchNav(e.shiftKey ? -1 : 1); }
            if (e.key === 'Escape') { scToggleSearch(); }
        });
    }

    // ── Patient linking ──
    var ptlBar = document.getElementById('ptl-search');
    var ptlQ = document.getElementById('ptl-q');
    var ptlResults = document.getElementById('ptl-results');
    var ptlId = document.getElementById('ptl-patient-id');
    var ptlForm = document.getElementById('ptl-form');
    window.ptlOpen = function () {
        if (!ptlBar) return;
        var show = ptlBar.hasAttribute('hidden');
        if (show) { ptlBar.removeAttribute('hidden'); if (ptlQ) ptlQ.focus(); }
        else ptlBar.setAttribute('hidden', '');
    };
    var ptlTimer;
    if (ptlQ) ptlQ.addEventListener('input', function () {
        clearTimeout(ptlTimer);
        var term = this.value.trim();
        if (term.length < 2) { ptlResults.innerHTML = ''; return; }
        ptlTimer = setTimeout(function () {
            fetch('{{ route('patients.search') }}?q=' + encodeURIComponent(term), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.ok ? r.json() : []; })
                .then(function (list) {
                    ptlResults.innerHTML = '';
                    if (!list.length) { ptlResults.innerHTML = '<div class="empty">No patients found</div>'; return; }
                    list.forEach(function (p) {
                        var d = document.createElement('div'); d.className = 'res';
                        d.innerHTML = '<i class="fa-solid fa-user-injured" style="color:var(--sc-teal)"></i><div><div class="nm"></div><div class="meta"></div></div>';
                        d.querySelector('.nm').textContent = p.full_name || p.name || '—';
                        d.querySelector('.meta').textContent = [p.jhc_id, p.phone, p.age].filter(Boolean).join(' · ');
                        d.addEventListener('click', function () { ptlId.value = p.id; ptlForm.submit(); });
                        ptlResults.appendChild(d);
                    });
                })
                .catch(function () { ptlResults.innerHTML = '<div class="empty">Search unavailable</div>'; });
        }, 250);
    });

    // ── @mention autocomplete in the composer ──
    var names = @json($mentionNames->values());
    var ta = composer ? composer.querySelector('textarea[name=body]') : null;
    var mbox = document.getElementById('mention-box');
    function colorFor(name) { // mirror the bubble avatar colour scheme loosely
        var h = 0; for (var i = 0; i < name.length; i++) h = (h * 31 + name.charCodeAt(i)) % 0xffffff;
        return '#' + ('000000' + h.toString(16)).slice(-6);
    }
    function hideMentions() { if (mbox) { mbox.setAttribute('hidden', ''); mbox.innerHTML = ''; } }
    function currentMentionQuery() {
        if (!ta) return null;
        var upto = ta.value.slice(0, ta.selectionStart);
        var m = upto.match(/(?:^|\s)@([^\s@]*)$/);   // an @token being typed at the caret
        return m ? { q: m[1], start: ta.selectionStart - m[1].length - 1 } : null;
    }
    function showMentions() {
        if (!ta || !mbox || !names.length) return;
        var ctx = currentMentionQuery();
        if (!ctx) return hideMentions();
        var q = ctx.q.toLowerCase();
        var matches = names.filter(function (n) { return n.toLowerCase().indexOf(q) !== -1; }).slice(0, 6);
        if (!matches.length) return hideMentions();
        mbox.innerHTML = '';
        matches.forEach(function (n) {
            var item = document.createElement('div'); item.className = 'mi';
            var av = document.createElement('span'); av.className = 'av'; av.style.background = colorFor(n); av.textContent = n.charAt(0).toUpperCase();
            var nm = document.createElement('span'); nm.textContent = n;
            item.appendChild(av); item.appendChild(nm);
            item.addEventListener('mousedown', function (e) {   // mousedown beats textarea blur
                e.preventDefault();
                var before = ta.value.slice(0, ctx.start);
                var after = ta.value.slice(ta.selectionStart);
                var insert = '@' + n + ' ';
                ta.value = before + insert + after;
                var pos = (before + insert).length;
                ta.setSelectionRange(pos, pos);
                ta.focus();
                hideMentions();
            });
            mbox.appendChild(item);
        });
        mbox.removeAttribute('hidden');
    }
    if (ta) {
        ta.addEventListener('input', showMentions);
        ta.addEventListener('keyup', function (e) { if (e.key === 'Escape') hideMentions(); });
        ta.addEventListener('blur', function () { setTimeout(hideMentions, 150); });
    }
})();
</script>
@endsection
