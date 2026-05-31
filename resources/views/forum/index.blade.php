@extends('layouts.app')
@section('title','Forum')
@section('page-title','Forum')

@push('styles')
<style>
    .forum-cat        { background:var(--sc-surface); border:1px solid var(--sc-border); border-radius:10px; margin-bottom:18px; overflow:hidden; }
    .forum-cat-head   { padding:12px 16px; background:var(--sc-surface-2,#f6f8fb); border-bottom:1px solid var(--sc-border); }
    .forum-cat-head h2 { font-size:15px; margin:0; }
    .forum-cat-head p  { font-size:12px; color:var(--sc-muted); margin:2px 0 0; }
    .forum-row        { display:grid; grid-template-columns: 38px 1fr 110px 220px; gap:16px; align-items:center; padding:12px 16px; border-top:1px solid var(--sc-border); }
    .forum-row:first-child { border-top:0; }
    .forum-row .fi-icon { font-size:22px; color:var(--sc-teal); }
    .forum-row .fi-name { font-weight:600; font-size:14px; }
    .forum-row .fi-desc { font-size:12px; color:var(--sc-muted); margin-top:2px; }
    .forum-row .fi-stat { font-size:12px; color:var(--sc-muted); text-align:right; }
    .forum-row .fi-last { font-size:12px; color:var(--sc-muted); border-left:1px solid var(--sc-border); padding-left:16px; }
    .forum-row .fi-last strong { color:var(--sc-text,#222); display:block; font-weight:600; font-size:12.5px; }
    .anno-pill { display:inline-block; font-size:10px; font-weight:700; color:#a16207; background:#fef3c7; border-radius:10px; padding:1px 6px; margin-left:6px; }
    .priv-pill { display:inline-block; font-size:10px; font-weight:700; color:#92400e; background:#fde68a; border-radius:10px; padding:1px 6px; margin-left:6px; }
    @media (max-width: 720px) {
      .forum-row { grid-template-columns: 30px 1fr; }
      .forum-row .fi-stat, .forum-row .fi-last { display:none; }
    }
</style>
@endpush

@section('content')
<div class="page-header">
    <div class="page-header-left">
        <h1>Forum</h1>
        <p class="text-small text-muted">Internal discussion + public patient announcements.</p>
    </div>
    <div class="page-header-right">
        @if(auth()->user()->can('messages.send'))
            <a href="{{ route('messages.inbox') }}" class="btn btn-outline"><i class="fa-solid fa-envelope"></i> Messages</a>
        @endif
        @if(auth()->user()->can('forum.admin'))
            <a href="{{ route('forum.admin.index') }}" class="btn btn-outline"><i class="fa-solid fa-gear"></i> Admin</a>
        @endif
    </div>
</div>

@forelse($categories as $cat)
    <div class="forum-cat">
        <div class="forum-cat-head">
            <h2>{{ $cat->name }}</h2>
            @if($cat->description)<p>{{ $cat->description }}</p>@endif
        </div>
        @foreach($cat->forums as $f)
            <div class="forum-row">
                <div class="fi-icon"><i class="fa-solid {{ $f->icon ?: 'fa-comments' }}"></i></div>
                <div>
                    <div class="fi-name">
                        <a href="{{ route('forum.show', $f) }}" style="text-decoration:none">{{ $f->name }}</a>
                        @if($f->isPrivate())<span class="priv-pill"><i class="fa-solid fa-lock"></i> PRIVATE</span>@endif
                        @if($f->is_announcements)<span class="anno-pill">PUBLIC</span>@endif
                    </div>
                    @if($f->description)<div class="fi-desc">{{ $f->description }}</div>@endif
                </div>
                <div class="fi-stat">
                    {{ $f->topics_count }} topics<br>
                    {{ $f->posts_count }} posts
                </div>
                <div class="fi-last">
                    @if($f->lastPost)
                        <strong>{{ \Illuminate\Support\Str::limit(optional($f->lastPost->topic)->title ?: '—', 36) }}</strong>
                        by {{ optional($f->lastPost->author)->name ?: '—' }}
                        <br>{{ $f->last_post_at?->diffForHumans() }}
                    @else
                        <em>No posts yet</em>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
@empty
    <div class="empty-state" style="padding:60px 20px;text-align:center;color:var(--sc-muted)">
        <i class="fa-solid fa-comments" style="font-size:48px;opacity:.3"></i>
        <p style="margin-top:12px">No forums are visible to your role yet.</p>
        @if(auth()->user()->can('forum.admin'))
            <a href="{{ route('forum.admin.index') }}" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Set up forums</a>
        @endif
    </div>
@endforelse
@endsection
