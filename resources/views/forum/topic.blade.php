@extends('layouts.app')
@section('title', $topic->title)
@section('page-title', $topic->title)

@push('styles')
<style>
    .post-card { background:var(--sc-surface); border:1px solid var(--sc-border); border-radius:10px; margin-bottom:14px; overflow:hidden; }
    .post-head { display:flex; align-items:center; justify-content:space-between; padding:10px 14px; background:var(--sc-surface-2,#f7f8fb); border-bottom:1px solid var(--sc-border); font-size:12.5px; }
    .post-head .pa { display:flex; align-items:center; gap:10px; }
    .post-head .avatar { width:28px; height:28px; border-radius:50%; background:var(--sc-teal); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:12px; }
    .post-body { padding:14px; font-size:14px; white-space:pre-wrap; word-wrap:break-word; line-height:1.55; }
    .post-foot { padding:6px 14px; border-top:1px dashed var(--sc-border); font-size:11.5px; color:var(--sc-muted); display:flex; gap:10px; }
    .post-foot a, .post-foot button { color:var(--sc-muted); background:none; border:0; cursor:pointer; padding:0; }
    .post-foot a:hover, .post-foot button:hover { color:var(--sc-teal); }
    .mod-bar { background:var(--sc-surface); border:1px solid var(--sc-border); border-radius:10px; padding:10px 14px; margin-bottom:14px; display:flex; gap:8px; flex-wrap:wrap; align-items:center; font-size:12.5px; }
    .reply-box { background:var(--sc-surface); border:1px solid var(--sc-border); border-radius:10px; padding:14px; margin-top:14px; }
    .reply-box textarea { width:100%; min-height:120px; padding:10px; border:1px solid var(--sc-border); border-radius:6px; font-family:inherit; font-size:14px; }
</style>
@endpush

@section('content')
<div class="page-header">
    <div class="page-header-left">
        <a href="{{ route('forum.show', $forum) }}" class="text-small" style="text-decoration:none"><i class="fa-solid fa-arrow-left"></i> {{ $forum->name }}</a>
        <h1 style="margin:4px 0 0">{{ $topic->title }}</h1>
        <div class="text-small text-muted">{{ $topic->posts_count }} posts · {{ $topic->views_count }} views · started by {{ $topic->author?->name ?? '—' }} {{ $topic->created_at->diffForHumans() }}</div>
    </div>
    <div class="page-header-right">
        <form method="POST" action="{{ route('forum.topic.subscribe', [$forum, $topic]) }}" style="display:inline">
            @csrf
            <button class="btn btn-outline" type="submit">
                <i class="fa-{{ $subscribed ? 'solid' : 'regular' }} fa-bell"></i>
                {{ $subscribed ? 'Unsubscribe' : 'Subscribe' }}
            </button>
        </form>
    </div>
</div>

@if($forum->userCan(auth()->user(), 'moderate'))
    <div class="mod-bar">
        <strong>Moderator:</strong>
        <form method="POST" action="{{ route('forum.topic.flag', [$forum, $topic]) }}" style="display:inline">
            @csrf
            <input type="hidden" name="flag" value="pinned">
            <button class="btn btn-outline btn-sm" type="submit"><i class="fa-solid fa-thumbtack"></i> {{ $topic->is_pinned ? 'Unpin' : 'Pin' }}</button>
        </form>
        <form method="POST" action="{{ route('forum.topic.flag', [$forum, $topic]) }}" style="display:inline">
            @csrf
            <input type="hidden" name="flag" value="locked">
            <button class="btn btn-outline btn-sm" type="submit"><i class="fa-solid fa-lock"></i> {{ $topic->is_locked ? 'Unlock' : 'Lock' }}</button>
        </form>
        @unless($forum->is_announcements)
        <form method="POST" action="{{ route('forum.topic.flag', [$forum, $topic]) }}" style="display:inline">
            @csrf
            <input type="hidden" name="flag" value="public">
            <button class="btn btn-outline btn-sm" type="submit"><i class="fa-solid fa-bullhorn"></i> {{ $topic->is_public ? 'Hide from public' : 'Publish to patients' }}</button>
        </form>
        @endunless
    </div>
@endif

@foreach($posts as $p)
    <div class="post-card" id="post-{{ $p->id }}">
        <div class="post-head">
            <div class="pa">
                <div class="avatar">{{ strtoupper(substr($p->author?->name ?? '?', 0, 1)) }}</div>
                <div>
                    <strong>{{ $p->author?->name ?? '—' }}</strong>
                    @if($p->is_first)<span style="color:var(--sc-teal);font-size:11px;font-weight:700;margin-left:6px">OP</span>@endif
                    <div style="color:var(--sc-muted);font-size:11px">{{ $p->created_at->format('d M Y · H:i') }}</div>
                </div>
            </div>
            <a href="#post-{{ $p->id }}" class="text-muted" style="font-size:11px">#{{ $p->id }}</a>
        </div>
        <div class="post-body">{{ $p->body }}</div>
        <div class="post-foot">
            @if($p->edited_at)
                <span><i class="fa-solid fa-pen-to-square"></i> edited {{ $p->edited_at->diffForHumans() }} by {{ $p->editor?->name ?? '—' }}</span>
            @endif
            @if($p->user_id === auth()->id() || $forum->userCan(auth()->user(),'moderate'))
                <a href="{{ route('forum.post.edit', [$forum, $topic, $p]) }}">Edit</a>
                <form method="POST" action="{{ route('forum.post.destroy', [$forum, $topic, $p]) }}" style="display:inline"
                      onsubmit="return confirm('Delete this post?')">
                    @csrf @method('DELETE')
                    <button type="submit">Delete</button>
                </form>
            @endif
        </div>
    </div>
@endforeach

<div>{{ $posts->links() }}</div>

@if($topic->is_locked && !$forum->userCan(auth()->user(),'moderate'))
    <div class="alert alert-info" style="margin-top:14px">
        <i class="fa-solid fa-lock"></i> <div>This topic is locked. No new replies are allowed.</div>
    </div>
@elseif($forum->userCan(auth()->user(),'reply'))
    <div class="reply-box">
        <strong>Post a reply</strong>
        <form method="POST" action="{{ route('forum.reply', [$forum, $topic]) }}" style="margin-top:8px">
            @csrf
            <textarea name="body" required minlength="2" maxlength="20000" placeholder="Write your reply…"></textarea>
            <div style="margin-top:10px;text-align:right">
                <button class="btn btn-primary" type="submit"><i class="fa-solid fa-paper-plane"></i> Post Reply</button>
            </div>
        </form>
    </div>
@endif
@endsection
