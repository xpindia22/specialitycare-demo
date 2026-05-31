@extends('layouts.app')
@section('title','Starred messages')
@section('page-title','Starred messages')

@section('content')
<div class="page-header">
    <div class="page-header-left">
        <h1>Starred messages</h1>
        <p class="text-small text-muted">Messages you've bookmarked, newest first.</p>
    </div>
    <div class="page-header-right">
        <a href="{{ route('messages.inbox') }}" class="btn btn-outline"><i class="fa-solid fa-arrow-left"></i> Back to Messages</a>
    </div>
</div>

<div style="background:var(--sc-surface);border:1px solid var(--sc-border);border-radius:10px;overflow:hidden">
    @forelse($stars as $star)
        @php $m = $star->message; @endphp
        @continue(!$m || !$m->thread)
        <div style="padding:12px 14px;border-bottom:1px solid var(--sc-border)">
            <div style="display:flex;justify-content:space-between;gap:12px">
                <div style="font-size:13px">
                    <a href="{{ route('messages.thread', $m->thread) }}#msg-{{ $m->id }}" style="font-weight:600;text-decoration:none;color:var(--sc-teal)">
                        <i class="fa-solid fa-star" style="color:#f5b301"></i> {{ $m->thread->titleFor(auth()->user()) }}
                    </a>
                    <div class="text-muted" style="font-size:11.5px;margin-top:2px">
                        {{ $m->author?->name ?? '—' }} · {{ $m->created_at->format('d M Y, H:i') }}
                    </div>
                    <div style="margin-top:5px;color:#111b21">
                        @if($m->isDeleted())
                            <em class="text-muted">This message was deleted</em>
                        @else
                            {{ \Illuminate\Support\Str::limit($m->body !== '' ? $m->body : '📎 attachment', 220) }}
                        @endif
                    </div>
                </div>
                <form method="POST" action="{{ route('messages.star', [$m->thread, $m]) }}" style="margin:0">
                    @csrf
                    <button type="submit" class="btn btn-sm" title="Remove star"><i class="fa-solid fa-star" style="color:#f5b301"></i></button>
                </form>
            </div>
        </div>
    @empty
        <div style="text-align:center;color:var(--sc-muted);padding:60px 20px">
            <i class="fa-regular fa-star" style="font-size:36px;opacity:.3"></i>
            <p style="margin-top:10px">No starred messages yet. Tap the ☆ on any message to bookmark it.</p>
        </div>
    @endforelse
</div>

<div style="margin-top:14px">{{ $stars->links() }}</div>
@endsection
