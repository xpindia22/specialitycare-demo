@extends('public.layout')
@section('title', $topic->title)
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags($topic->firstPost->first()?->body ?? ''), 160))

@section('content')
<a href="{{ route('public.announcements') }}" style="color:var(--c-teal);font-size:13px;text-decoration:none">
    <i class="fa-solid fa-arrow-left"></i> All announcements
</a>

<article class="card" style="margin-top:14px">
    <div class="card-body">
        <h1 style="font-size:24px;font-weight:700;color:var(--c-navy);margin-bottom:6px">{{ $topic->title }}</h1>
        <div style="font-size:12px;color:var(--c-muted);margin-bottom:18px">
            <i class="fa-regular fa-calendar"></i> Posted {{ $topic->created_at->format('d M Y') }}
        </div>

        @foreach($topic->posts as $i => $p)
            <div style="font-size:15px;color:var(--c-text);line-height:1.7;white-space:pre-wrap; @if($i>0) border-top:1px dashed var(--c-border); padding-top:14px; margin-top:14px; @endif">
                @if($i > 0)
                    <div style="font-size:11.5px;color:var(--c-muted);margin-bottom:6px">
                        <i class="fa-regular fa-clock"></i> Updated {{ $p->created_at->format('d M Y') }}
                    </div>
                @endif
                {{ $p->body }}
            </div>
        @endforeach
    </div>
</article>
@endsection
