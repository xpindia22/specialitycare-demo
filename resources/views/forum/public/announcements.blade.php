@extends('public.layout')
@section('title', 'Announcements')
@section('meta_description', 'Latest announcements from ' . ($clinic['name'] ?? config('app.name')))

@section('content')
<div style="text-align:center;margin-bottom:24px">
    <h1 style="font-size:28px;font-weight:700;color:var(--c-navy)">Announcements</h1>
    <p style="color:var(--c-muted);margin-top:6px">Latest notices from {{ $clinic['name'] ?? config('app.name') }}.</p>
</div>

@forelse($topics as $topic)
    <article class="card" style="margin-bottom:14px">
        <div class="card-body">
            <h2 style="font-size:18px;margin-bottom:4px"><a href="{{ route('public.announcements.show', $topic) }}" style="text-decoration:none;color:var(--c-navy)">{{ $topic->title }}</a></h2>
            <div style="font-size:12px;color:var(--c-muted);margin-bottom:10px">
                <i class="fa-regular fa-calendar"></i> {{ $topic->created_at->format('d M Y') }}
                @if($topic->last_post_at && $topic->last_post_at->gt($topic->created_at))
                    · last update {{ $topic->last_post_at->diffForHumans() }}
                @endif
            </div>
            @if($topic->firstPost->isNotEmpty())
                <div style="font-size:14px;color:var(--c-text);line-height:1.6;white-space:pre-wrap">{{ \Illuminate\Support\Str::limit(strip_tags($topic->firstPost->first()->body), 320) }}</div>
            @endif
            <div style="margin-top:12px"><a href="{{ route('public.announcements.show', $topic) }}" style="color:var(--c-teal);font-weight:600;font-size:13px;text-decoration:none">Read more →</a></div>
        </div>
    </article>
@empty
    <div class="card"><div class="card-body" style="text-align:center;color:var(--c-muted);padding:40px 20px">
        <i class="fa-regular fa-bell" style="font-size:36px;opacity:.3"></i>
        <p style="margin-top:10px">No announcements have been posted yet.</p>
    </div></div>
@endforelse

<div style="margin-top:16px">{{ $topics->links() }}</div>
@endsection
