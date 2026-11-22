{{-- FILE: resources/views/icu/board.blade.php — ICU central monitor --}}
@extends('layouts.app')
@section('title','ICU Board')
@section('page-title','ICU Board')

@push('styles')
<style>
    .icu-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(340px,1fr)); gap:14px; }
    .icu-tile { background:#0b1324; color:#e2e8f0; border-radius:12px; padding:14px; display:flex; flex-direction:column; gap:8px; text-decoration:none; border:2px solid transparent; }
    .icu-tile:hover { border-color:#38bdf8; color:#e2e8f0; }
    .icu-tile.lv-high { border-color:#ef4444; } .icu-tile.lv-medium { border-color:#f97316; }
    .icu-tile .top { display:flex; justify-content:space-between; align-items:flex-start; gap:8px; }
    .icu-tile .bed { font-size:12px; background:rgba(255,255,255,.1); padding:2px 8px; border-radius:5px; white-space:nowrap; }
    .icu-tile .name { font-weight:800; font-size:16px; color:#fff; }
    .icu-tile .dx { font-size:12px; opacity:.7; }
    .vit { display:grid; grid-template-columns:repeat(4,1fr); gap:6px; }
    .vit div { background:rgba(255,255,255,.05); border-radius:6px; padding:4px 6px; }
    .vit .l { font-size:10px; opacity:.6; text-transform:uppercase; }
    .vit .v { font-size:20px; font-weight:800; font-family:'DM Mono',monospace; line-height:1.1; }
    .vit .hr { color:#4ade80; } .vit .bp { color:#f87171; } .vit .sp { color:#38bdf8; } .vit .rr { color:#facc15; }
    .vit .tp { color:#fb923c; } .vit .gc { color:#c084fc; } .vit .nw { color:#fff; } .vit .fl { color:#7dd3fc; }
    .vit .crit { background:rgba(239,68,68,.35); }
    .inf { font-size:12px; display:flex; flex-wrap:wrap; gap:4px; }
    .inf span { background:rgba(56,189,248,.15); color:#bae6fd; padding:2px 7px; border-radius:4px; }
    .inf span.over { background:rgba(239,68,68,.3); color:#fecaca; }
    .als { display:flex; flex-wrap:wrap; gap:4px; }
    .als span { font-size:11px; font-weight:700; padding:2px 7px; border-radius:4px; }
    .als .danger { background:#ef4444; color:#fff; } .als .warn { background:#f59e0b; color:#1f2937; }
    .foot { font-size:11px; opacity:.6; display:flex; justify-content:space-between; }
</style>
@endpush

@section('content')
<div class="page-header">
    <div class="page-header-left" style="min-width:320px">
        <h1><i class="fa-solid fa-display"></i> ICU Board</h1>
        <div class="breadcrumb">Patients in ICU wards and anyone on a running infusion · refreshes every minute · <span id="upd">{{ now()->format('H:i:s') }}</span>
            · <a href="#" onclick="document.documentElement.requestFullscreen?.();return false"><i class="fa-solid fa-expand"></i> Full screen</a></div>
    </div>
    @include('ipd._toolbar', ['inline'=>true])
</div>

<div id="tiles">@include('icu._board-tiles')</div>

<script>
setInterval(() => {
    fetch('{{ route('icu.board') }}?partial=1', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.ok ? r.text() : Promise.reject())
        .then(h => { document.getElementById('tiles').innerHTML = h; document.getElementById('upd').textContent = new Date().toLocaleTimeString(); })
        .catch(() => { document.getElementById('upd').textContent = 'refresh failed — retrying'; });
}, 60000);
</script>
@endsection
