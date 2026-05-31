@extends('layouts.app')
@section('title','Patient messages')
@section('page-title','Patient messages')

@section('content')
<div class="page-header">
    <div class="page-header-left">
        <h1>Patient messages</h1>
        <p class="text-small text-muted">Messages from patients via the portal. Shared — any staff can reply.</p>
    </div>
    <div class="page-header-right">
        <a href="{{ route('messages.inbox') }}" class="btn btn-outline"><i class="fa-solid fa-arrow-left"></i> Staff messages</a>
    </div>
</div>

<div style="background:var(--sc-surface);border:1px solid var(--sc-border);border-radius:10px;overflow:hidden">
    @forelse($conversations as $conv)
        @php $unread = $conv->staffUnreadCount(); $p = $conv->patient; @endphp
        @continue(!$p)
        <a href="{{ route('messages.patients.thread', $p) }}" style="display:grid;grid-template-columns:38px 1fr 100px 50px;gap:14px;align-items:center;padding:11px 14px;border-bottom:1px solid var(--sc-border);text-decoration:none;color:inherit;{{ $unread ? 'background:var(--sc-teal-glow);' : '' }}">
            <div style="width:34px;height:34px;border-radius:50%;background:var(--sc-teal);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700">{{ strtoupper(substr($p->first_name,0,1)) }}</div>
            <div>
                <div style="font-weight:600;font-size:14px">{{ $p->full_name }} <span class="text-muted" style="font-size:11px;font-weight:normal">· {{ $p->jhc_id }}</span></div>
                <div class="text-muted" style="font-size:12px">
                    @php $last = $conv->messages()->latest('created_at')->first(); @endphp
                    @if($last)<strong>{{ $last->sender === 'patient' ? $p->first_name : 'You' }}:</strong> {{ \Illuminate\Support\Str::limit(strip_tags($last->body), 90) }}@endif
                </div>
            </div>
            <div class="text-muted" style="font-size:11.5px;text-align:right">{{ $conv->last_message_at?->diffForHumans() }}</div>
            <div style="text-align:right">@if($unread)<span style="background:var(--sc-teal);color:#fff;font-weight:700;font-size:11px;padding:2px 7px;border-radius:10px">{{ $unread }}</span>@endif</div>
        </a>
    @empty
        <div style="text-align:center;color:var(--sc-muted);padding:60px 20px">
            <i class="fa-regular fa-comments" style="font-size:36px;opacity:.3"></i>
            <p style="margin-top:10px">No patient messages yet.</p>
        </div>
    @endforelse
</div>

<div style="margin-top:14px">{{ $conversations->links() }}</div>
@endsection
