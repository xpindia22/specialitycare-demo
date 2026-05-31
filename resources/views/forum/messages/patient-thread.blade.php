@extends('layouts.app')
@section('title', $patient->full_name . ' — patient message')
@section('page-title','Patient message')

@push('styles')
<style>
    .pm-list { height:calc(100vh - 320px); min-height:300px; overflow-y:auto; padding:14px; background:#efeae2; }
    .pm-row { display:flex; margin-bottom:8px; }
    .pm-row.staff { justify-content:flex-end; }
    .pm-bubble { max-width:74%; padding:7px 11px; border-radius:9px; font-size:14px; line-height:1.4; box-shadow:0 1px 1px rgba(0,0,0,.08); }
    .pm-row.patient .pm-bubble { background:#fff; border-top-left-radius:0; }
    .pm-row.staff .pm-bubble { background:#d9fdd3; border-top-right-radius:0; }
    .pm-meta { display:block; font-size:10.5px; color:rgba(0,0,0,.45); margin-top:3px; text-align:right; }
    .pm-compose { display:flex; gap:8px; padding:12px 14px; background:#f0f2f5; border-top:1px solid var(--sc-border); }
    .pm-compose textarea { flex:1; border:1px solid var(--sc-border); border-radius:8px; padding:9px 12px; font-size:14px; resize:none; }
</style>
@endpush

@section('content')
<div class="page-header">
    <div class="page-header-left">
        <h1>{{ $patient->full_name }} <span class="text-muted" style="font-size:13px">· {{ $patient->jhc_id }}</span></h1>
        <p class="text-small text-muted">Patient ↔ clinic conversation (shared with all staff).</p>
    </div>
    <div class="page-header-right">
        <a href="{{ route('patients.show', $patient) }}" class="btn btn-outline"><i class="fa-solid fa-user"></i> Chart</a>
        <a href="{{ route('messages.patients.inbox') }}" class="btn btn-outline"><i class="fa-solid fa-arrow-left"></i> All patient messages</a>
    </div>
</div>

<div style="background:var(--sc-surface);border:1px solid var(--sc-border);border-radius:10px;overflow:hidden">
    <div class="pm-list" id="pm-list">
        @forelse($messages as $m)
            <div class="pm-row {{ $m->sender }}">
                <div class="pm-bubble">
                    @if($m->sender === 'staff')<div style="font-size:11px;font-weight:700;color:#00a884">{{ $m->user?->name ?? 'Clinic' }}</div>@endif
                    {!! nl2br(e($m->body)) !!}
                    <span class="pm-meta">{{ $m->created_at->format('d M, H:i') }}</span>
                </div>
            </div>
        @empty
            <p class="text-muted" style="text-align:center;margin-top:40px">No messages yet — send the first one below.</p>
        @endforelse
        <span id="pm-bottom"></span>
    </div>
    <form class="pm-compose" method="POST" action="{{ route('messages.patients.send', $patient) }}">
        @csrf
        <textarea name="body" rows="2" maxlength="10000" required placeholder="Reply to {{ $patient->first_name }}…"></textarea>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-paper-plane"></i></button>
    </form>
</div>
@endsection

@push('scripts')
<script>
    (function () { var el = document.getElementById('pm-list'); if (el) el.scrollTop = el.scrollHeight; })();
</script>
@endpush
