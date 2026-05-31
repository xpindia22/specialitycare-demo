@extends('layouts.app')
@section('title','Messages')
@section('page-title','Messages')

@push('styles')
<style>
    .msg-row { display:grid; grid-template-columns: 38px 1fr 84px 44px 40px; gap:14px; align-items:center; padding:11px 14px; border-bottom:1px solid var(--sc-border); }
    .msg-row.owner { grid-template-columns: 28px 38px 1fr 80px 40px 58px; }
    .msg-row .avatar { width:34px; height:34px; border-radius:50%; background:var(--sc-teal); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; }
    .msg-row .title  { font-weight:600; font-size:14px; }
    .msg-row .title a { text-decoration:none; color:inherit; }
    .msg-row .preview{ font-size:12px; color:var(--sc-muted); }
    .msg-row .when   { font-size:11.5px; color:var(--sc-muted); text-align:right; }
    .msg-row .unread { background:var(--sc-teal); color:#fff; font-weight:700; font-size:11px; padding:2px 7px; border-radius:10px; }
    .msg-row.unread-row { background:var(--sc-teal-glow); }
    .msg-row:hover { background:var(--sc-bg); }
    .row-actions { display:flex; align-items:center; justify-content:flex-end; gap:2px; }
    .row-arch, .row-del { border:0; background:none; cursor:pointer; font-size:14px; opacity:.6; padding:4px; }
    .row-arch { color:var(--sc-muted); } .row-arch:hover { opacity:1; color:var(--sc-teal); }
    .row-del  { color:#b91c1c; } .row-del:hover { opacity:1; }
    /* Owner bulk-delete controls */
    .bulk-toolbar { display:flex; align-items:center; gap:16px; padding:9px 14px; background:var(--sc-bg); border:1px solid var(--sc-border); border-bottom:0; border-radius:10px 10px 0 0; font-size:13px; }
    .bulk-toolbar label { display:flex; align-items:center; gap:6px; cursor:pointer; margin:0; }
    .bulk-del-btn { background:#b91c1c; color:#fff; border:0; border-radius:6px; padding:5px 13px; font-size:12.5px; cursor:pointer; }
    .bulk-del-btn:disabled { opacity:.5; cursor:not-allowed; }
    /* search + scope tools */
    .msg-tools { display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:12px; }
    .msg-search { display:flex; align-items:center; gap:8px; flex:1; min-width:240px; }
    .msg-search input[type=search] { flex:1; border:1px solid var(--sc-border); border-radius:8px; padding:7px 11px; font-size:13px; }
    .msg-scope { display:flex; border:1px solid var(--sc-border); border-radius:8px; overflow:hidden; }
    .msg-scope a { padding:7px 13px; font-size:12.5px; text-decoration:none; color:var(--sc-muted); background:var(--sc-surface); }
    .msg-scope a.active { background:var(--sc-teal); color:#fff; font-weight:600; }
</style>
@endpush

@section('content')
@php $owner = auth()->user()->isOwner(); $scope = $scope ?? 'mine'; @endphp
<div class="page-header">
    <div class="page-header-left">
        <h1>Messages</h1>
        <p class="text-small text-muted">Private 1-to-1 and group conversations with other staff.</p>
    </div>
    <div class="page-header-right">
        <a href="{{ route('messages.compose') }}" class="btn btn-primary"><i class="fa-solid fa-pen-to-square"></i> New Message</a>
        @if(auth()->user()->can('messages.group'))
            <a href="{{ route('messages.group.create') }}" class="btn btn-outline"><i class="fa-solid fa-people-group"></i> New Group</a>
        @endif
        @if($owner)
            <a href="{{ route('messages.broadcast') }}" class="btn btn-outline"><i class="fa-solid fa-bullhorn"></i> Broadcast</a>
        @endif
        <a href="{{ route('messages.starred') }}" class="btn btn-outline"><i class="fa-solid fa-star"></i> Starred</a>
        <a href="{{ route('messages.patients.inbox') }}" class="btn btn-outline"><i class="fa-solid fa-user-injured"></i> Patient messages</a>
    </div>
</div>

{{-- Reusable form for per-row archive toggle (kept outside the bulk-delete form) --}}
<form id="archiveForm" method="POST" style="display:none">@csrf</form>

<div class="msg-tools">
    <form method="GET" action="{{ route('messages.inbox') }}" class="msg-search">
        @if(in_array($scope, ['all','archived']))<input type="hidden" name="scope" value="{{ $scope }}">@endif
        <input type="search" name="q" value="{{ $q ?? '' }}" placeholder="Search messages, people, patients…" autocomplete="off">
        <button type="submit" class="btn btn-outline btn-sm"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
        @if(!empty($q))<a href="{{ route('messages.inbox', in_array($scope,['all','archived']) ? ['scope'=>$scope] : []) }}" class="btn btn-sm">Clear</a>@endif
    </form>
    <div class="msg-scope">
        <a href="{{ route('messages.inbox', array_filter(['q'=>$q])) }}" class="{{ $scope === 'mine' ? 'active' : '' }}">Inbox</a>
        <a href="{{ route('messages.inbox', array_filter(['q'=>$q, 'scope'=>'archived'])) }}" class="{{ $scope === 'archived' ? 'active' : '' }}">Archived</a>
        @if($owner)<a href="{{ route('messages.inbox', array_filter(['q'=>$q, 'scope'=>'all'])) }}" class="{{ $scope === 'all' ? 'active' : '' }}">All conversations</a>@endif
    </div>
</div>
@if(!empty($q))
    <p class="text-small text-muted" style="margin:-4px 0 10px">{{ $threads->total() }} conversation(s) matching “{{ $q }}”{{ $scope === 'all' ? ' (all conversations)' : ($scope === 'archived' ? ' (archived)' : '') }}.</p>
@endif

@if($owner && $threads->count())
<form id="bulkForm" method="POST" action="{{ route('messages.bulk-destroy') }}"
      onsubmit="return confirm('Delete the selected conversation(s) for everyone? Messages, files and links are permanently removed. This cannot be undone.');">
    @csrf
    <div class="bulk-toolbar">
        <label><input type="checkbox" id="bulkAll" onclick="scSelectAll(this)"> Select all</label>
        <button type="submit" class="bulk-del-btn" id="bulkDelBtn" disabled><i class="fa-solid fa-trash"></i> Delete selected</button>
        <span class="text-muted" style="font-size:12px">Owner only — deletes the whole conversation for everyone.</span>
    </div>
@endif

<div style="background:var(--sc-surface);border:1px solid var(--sc-border);border-radius:{{ $owner && $threads->count() ? '0 0 10px 10px' : '10px' }};overflow:hidden">
    @forelse($threads as $t)
        @php
            $other = $t->is_group ? null : $t->activeParticipants->firstWhere('id','!=',auth()->id());
            $title = $t->titleFor(auth()->user());
            $initials = strtoupper(substr($t->is_group ? ($t->subject ?: 'G') : ($other?->name ?? '?'), 0, 1));
            $unread = $unreadByThread[$t->id] ?? 0;
            $iAmIn = $t->activeParticipants->contains('id', auth()->id());
            $isArchivedView = $scope === 'archived';
        @endphp
        <div class="msg-row {{ $owner ? 'owner' : '' }} {{ $unread ? 'unread-row' : '' }}">
            @if($owner)
                <div><input type="checkbox" class="bulk-cb" name="thread_ids[]" value="{{ $t->id }}" onchange="scBulkSync()"></div>
            @endif
            <div class="avatar">
                @if($t->is_group)<i class="fa-solid fa-people-group" style="font-size:14px"></i>
                @else{{ $initials }}@endif
            </div>
            <div>
                <div class="title"><a href="{{ route('messages.thread', $t) }}">{{ $title }}</a>
                    @if($t->is_group)<span class="text-muted" style="font-size:11px;font-weight:normal"> · group of {{ $t->activeParticipants->count() }}</span>@endif
                </div>
                <div class="preview">
                    @if($t->lastMessage)
                        <strong>{{ $t->lastMessage->author?->name ?? '—' }}:</strong>
                        {{ \Illuminate\Support\Str::limit(strip_tags($t->lastMessage->body), 110) }}
                    @else
                        <em>No messages yet</em>
                    @endif
                </div>
            </div>
            <div class="when">{{ $t->last_message_at?->diffForHumans() ?? '—' }}</div>
            <div style="text-align:right">
                @if($unread > 0)<span class="unread">{{ $unread }}</span>@endif
            </div>
            <div class="row-actions">
                @if($iAmIn)
                    <button type="button" class="row-arch" title="{{ $isArchivedView ? 'Unarchive' : 'Archive' }}" onclick="scArchive('{{ route('messages.archive', $t) }}')"><i class="fa-solid {{ $isArchivedView ? 'fa-box-open' : 'fa-box-archive' }}"></i></button>
                @endif
                @if($owner)
                    <button type="button" class="row-del" title="Delete this conversation" onclick="scDeleteOne({{ $t->id }})"><i class="fa-solid fa-trash"></i></button>
                @endif
            </div>
        </div>
    @empty
        <div style="text-align:center;color:var(--sc-muted);padding:60px 20px">
            <i class="fa-solid fa-envelope-open-text" style="font-size:36px;opacity:.3"></i>
            @if(!empty($q))
                <p style="margin-top:10px">No conversations match “{{ $q }}”.</p>
            @elseif($scope === 'archived')
                <p style="margin-top:10px">No archived conversations.</p>
            @else
                <p style="margin-top:10px">No messages yet.</p>
                <a href="{{ route('messages.compose') }}" class="btn btn-primary"><i class="fa-solid fa-pen-to-square"></i> Send your first message</a>
            @endif
        </div>
    @endforelse
</div>

@if($owner && $threads->count())
</form>
@endif

<div style="margin-top:14px">{{ $threads->links() }}</div>
@endsection

@push('scripts')
<script>
    function scSelectAll(cb) {
        document.querySelectorAll('.bulk-cb').forEach(function (c) { c.checked = cb.checked; });
        scBulkSync();
    }
    function scBulkSync() {
        var boxes = document.querySelectorAll('.bulk-cb');
        var checked = document.querySelectorAll('.bulk-cb:checked').length;
        var btn = document.getElementById('bulkDelBtn');
        if (btn) btn.disabled = checked === 0;
        var all = document.getElementById('bulkAll');
        if (all) all.checked = boxes.length > 0 && checked === boxes.length;
    }
    function scDeleteOne(id) {
        if (!confirm('Delete this conversation for everyone? Messages, files and links are permanently removed. This cannot be undone.')) return;
        var f = document.getElementById('bulkForm');
        f.querySelectorAll('.bulk-cb').forEach(function (c) { c.checked = (c.value === String(id)); });
        f.onsubmit = null;  // already confirmed; skip the form-level prompt
        f.submit();
    }
    function scArchive(url) {
        var f = document.getElementById('archiveForm');
        f.action = url;
        f.submit();
    }
</script>
@endpush
