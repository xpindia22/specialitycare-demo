@extends('layouts.app')
@section('title', $forum->name)
@section('page-title', $forum->name)

@push('styles')
<style>
    .topic-row { display:grid; grid-template-columns: 32px 1fr 110px 220px; gap:14px; align-items:center; padding:11px 14px; border-bottom:1px solid var(--sc-border); }
    .topic-row .ti-icon { font-size:18px; color:var(--sc-muted); }
    .topic-row .ti-icon.pinned { color:#b45309; }
    .topic-row .ti-icon.locked { color:#9ca3af; }
    .topic-row a.ti-title { font-weight:600; font-size:14px; text-decoration:none; }
    .topic-row .ti-meta  { font-size:11.5px; color:var(--sc-muted); margin-top:2px; }
    .topic-row .ti-stat  { font-size:12px; color:var(--sc-muted); text-align:right; }
    .topic-row .ti-last  { font-size:12px; color:var(--sc-muted); border-left:1px solid var(--sc-border); padding-left:14px; }
    .pin-pill, .lock-pill, .pub-pill { display:inline-block; font-size:10px; font-weight:700; border-radius:10px; padding:1px 6px; margin-left:6px; }
    .pin-pill  { color:#92400e; background:#fef3c7; }
    .lock-pill { color:#4b5563; background:#e5e7eb; }
    .pub-pill  { color:#a16207; background:#fef3c7; }
    @media (max-width: 720px) {
      .topic-row { grid-template-columns: 28px 1fr; }
      .topic-row .ti-stat, .topic-row .ti-last { display:none; }
    }
</style>
@endpush

@section('content')
<div class="page-header">
    <div class="page-header-left">
        <a href="{{ route('forum.index') }}" class="text-small" style="text-decoration:none"><i class="fa-solid fa-arrow-left"></i> Forum</a>
        <h1>{{ $forum->name }}</h1>
        @if($forum->description)
            <p class="text-small text-muted" style="margin:4px 0 0">{{ $forum->description }}</p>
        @endif
    </div>
    <div class="page-header-right">
        @if($forum->isPrivate())
            <form method="POST" action="{{ route('forum.lock', $forum) }}" style="display:inline">
                @csrf
                <button class="btn btn-outline" type="submit"><i class="fa-solid fa-lock"></i> Lock now</button>
            </form>
        @endif
        @if($forum->userCan(auth()->user(),'post'))
            <a href="{{ route('forum.topic.create', $forum) }}" class="btn btn-primary"><i class="fa-solid fa-plus"></i> New Topic</a>
        @endif
    </div>
</div>

<div style="background:var(--sc-surface);border:1px solid var(--sc-border);border-radius:10px;overflow:hidden">
    @forelse($topics as $topic)
        <div class="topic-row">
            <div class="ti-icon @if($topic->is_pinned) pinned @elseif($topic->is_locked) locked @endif">
                @if($topic->is_pinned)<i class="fa-solid fa-thumbtack"></i>
                @elseif($topic->is_locked)<i class="fa-solid fa-lock"></i>
                @else<i class="fa-regular fa-comment"></i>
                @endif
            </div>
            <div>
                <a class="ti-title" href="{{ route('forum.topic', [$forum, $topic]) }}">{{ $topic->title }}</a>
                @if($topic->is_pinned)<span class="pin-pill">PINNED</span>@endif
                @if($topic->is_locked)<span class="lock-pill">LOCKED</span>@endif
                @if($topic->is_public)<span class="pub-pill">PUBLIC</span>@endif
                <div class="ti-meta">by {{ $topic->author?->name ?? '—' }} · {{ $topic->created_at->diffForHumans() }}</div>
            </div>
            <div class="ti-stat">
                {{ $topic->posts_count }} posts<br>{{ $topic->views_count }} views
            </div>
            <div class="ti-last">
                @if($topic->lastPost)
                    by {{ optional($topic->lastPost->author)->name ?? '—' }}<br>
                    {{ $topic->last_post_at?->diffForHumans() }}
                @else
                    —
                @endif
            </div>
        </div>
    @empty
        <div style="padding:40px 20px;text-align:center;color:var(--sc-muted)">No topics yet.</div>
    @endforelse
</div>

<div style="margin-top:14px">{{ $topics->links() }}</div>
@endsection
