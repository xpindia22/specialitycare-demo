{{-- FILE: resources/views/opd/doctor.blade.php — Doctor's Queue View --}}
@extends('layouts.app')
@section('title',"Doctor's Queue")
@section('page-title',"Doctor's Queue")
@section('content')

<div class="page-header">
    <div class="page-header-left"><h1>Doctor's Queue</h1></div>
    <div class="d-flex gap-2 align-items-center">
        <form method="GET" action="{{ route('opd.doctor') }}" class="d-flex gap-2 align-items-center">
            <select name="doctor_id" class="form-control" onchange="this.form.submit()" style="min-width:180px">
                @foreach($doctors as $d)
                    <option value="{{ $d->id }}" {{ $doctorId == $d->id ? 'selected':'' }}>Dr. {{ $d->first_name }} {{ $d->last_name }}</option>
                @endforeach
            </select>
        </form>
        <a href="{{ route('opd.index') }}" class="btn btn-outline"><i class="fa-solid fa-ticket me-1"></i>Reception</a>
        <a href="{{ route('opd.board') }}" class="btn btn-outline" target="_blank"><i class="fa-solid fa-display me-1"></i>Board</a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="doctor-queue-layout">

    {{-- Current Patient Banner --}}
    @if($current)
    <div class="card mb-3" style="border-left:4px solid var(--sc-teal)">
        <div class="card-body" style="padding:14px 18px">
            <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap">
                <div style="font-size:32px;font-weight:900;color:var(--sc-teal);line-height:1;min-width:80px">{{ $current->display }}</div>
                <div style="flex:1">
                    <div style="font-weight:700;font-size:16px">{{ $current->patient->first_name }} {{ $current->patient->last_name }}
                        @if($current->priority==='urgent')<span class="badge badge-red ms-2">Urgent</span>@endif
                    </div>
                    <div style="font-size:12px;color:#666">{{ $current->patient->jhc_id }} &nbsp;·&nbsp; {{ $current->purpose ?: 'No purpose listed' }}</div>
                    @if($current->patient->allergies)
                    <div style="background:#fef2f2;border:1px solid #fca5a5;border-radius:6px;padding:6px 12px;font-size:12px;font-weight:600;color:#991b1b;margin-top:4px">
                        <i class="fa-solid fa-triangle-exclamation"></i> ALLERGIES: {{ $current->patient->allergies }}
                    </div>
                    @endif
                </div>
                <div style="display:flex;gap:8px;flex-wrap:wrap">
                    @if($current->encounter)
                    <a href="{{ route('encounters.show', $current->encounter) }}" class="btn btn-outline btn-sm">
                        <i class="fa-solid fa-stethoscope me-1"></i>Open Encounter
                    </a>
                    @endif
                    <form method="POST" action="{{ route('opd.status', $current) }}">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="done">
                        <button class="btn btn-outline btn-sm"><i class="fa-solid fa-circle-check me-1"></i>Mark Done</button>
                    </form>
                    @if($waiting->count())
                    <form method="POST" action="{{ route('opd.next-patient', $current) }}">
                        @csrf
                        <button class="btn btn-primary btn-sm">
                            <i class="fa-solid fa-forward-step me-1"></i>Done → Next Patient
                            <span style="opacity:.8;font-size:11px">({{ $waiting->first()->display }})</span>
                        </button>
                    </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @elseif($waiting->count())
    <div class="card mb-3" style="border-left:4px solid var(--sc-border)">
        <div class="card-body" style="padding:12px 18px;display:flex;align-items:center;gap:14px">
            <i class="fa-solid fa-hourglass" style="color:#ccc;font-size:20px"></i>
            <div style="flex:1;color:#666;font-size:13px">No patient in consultation — <strong>{{ $waiting->count() }}</strong> waiting.</div>
            <form method="POST" action="{{ route('opd.start-encounter', $waiting->first()) }}">
                @csrf
                <button class="btn btn-primary btn-sm"><i class="fa-solid fa-play me-1"></i>Start {{ $waiting->first()->display }}</button>
            </form>
        </div>
    </div>
    @endif

    <div class="doctor-bottom-grid">
        {{-- Waiting Queue --}}
        <div class="card">
            <div class="card-header" style="background:#f8f9fa;border-bottom:1px solid #e0e0e0">
                <strong>Waiting ({{ $waiting->count() }})</strong>
            </div>
            <div class="table-wrap">
                <table class="sc-table">
                    <thead><tr><th>Token</th><th>Patient</th><th>Purpose</th><th>Priority</th><th></th></tr></thead>
                    <tbody>
                    @forelse($waiting as $t)
                    <tr class="{{ $t->priority === 'urgent' ? 'opd-urgent-row' : '' }}">
                        <td class="mono fw-600 text-center" style="font-size:15px">{{ $t->display }}</td>
                        <td>
                            <div class="fw-600">{{ $t->patient->first_name }} {{ $t->patient->last_name }}</div>
                            <div style="font-size:11px;color:#666">{{ $t->patient->jhc_id }}</div>
                            @if($t->patient->allergies)
                            <div style="font-size:10px;color:#dc2626;font-weight:600;margin-top:2px">
                                <i class="fa-solid fa-triangle-exclamation"></i> {{ Str::limit($t->patient->allergies, 40) }}
                            </div>
                            @endif
                        </td>
                        <td style="font-size:12px">{{ $t->purpose ?: '—' }}</td>
                        <td>
                            @if($t->priority==='urgent')
                                <span class="badge badge-red">Urgent</span>
                            @else
                                <span class="badge badge-grey">Normal</span>
                            @endif
                        </td>
                        <td>
                            @if($t->status === 'waiting' || $t->status === 'called')
                            <form method="POST" action="{{ route('opd.start-encounter', $t) }}">
                                @csrf
                                <button class="btn btn-sm btn-primary" title="Start Consultation"><i class="fa-solid fa-play"></i></button>
                            </form>
                            @else
                                <span class="badge opd-badge-called">Called</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center text-muted" style="padding:24px">No patients waiting.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Completed --}}
        <div class="card">
            <div class="card-header" style="background:#f8f9fa;border-bottom:1px solid #e0e0e0">
                <strong>Completed Today ({{ $done->count() }})</strong>
            </div>
            <div class="table-wrap">
                <table class="sc-table">
                    <thead><tr><th>Token</th><th>Patient</th><th>Status</th><th>Done At</th><th></th></tr></thead>
                    <tbody>
                    @forelse($done as $t)
                    <tr>
                        <td class="mono fw-600 text-center">{{ $t->display }}</td>
                        <td style="font-size:12px">{{ $t->patient->first_name }} {{ $t->patient->last_name }}</td>
                        <td><span class="badge opd-badge-{{ $t->status }}">{{ ucfirst($t->status) }}</span></td>
                        <td style="font-size:11px;color:#666">{{ $t->completed_at?->format('h:i A') ?? '—' }}</td>
                        <td>@if($t->encounter)<a href="{{ route('encounters.show', $t->encounter) }}" class="btn btn-sm btn-outline">Encounter</a>@endif</td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="text-center text-muted" style="padding:24px">None yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.doctor-queue-layout { max-width:1100px; }
.doctor-bottom-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
@media(max-width:800px){ .doctor-bottom-grid { grid-template-columns:1fr; } }


.opd-urgent-row { background:#fff7f7 !important; }
.opd-badge-waiting         { background:#fef3c7; color:#92400e; }
.opd-badge-called          { background:#dbeafe; color:#1e40af; }
.opd-badge-in_consultation { background:#dcfce7; color:#14532d; }
.opd-badge-done            { background:#f3f4f6; color:#374151; }
.opd-badge-skipped         { background:#f3f4f6; color:#9ca3af; }
.badge-grey { background:#f3f4f6; color:#555; }
</style>
@endpush
@endsection
