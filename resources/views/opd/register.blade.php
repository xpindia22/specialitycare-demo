@extends('layouts.app')
@section('title','OPD Register — ' . ($from === $to ? \Carbon\Carbon::parse($from)->format('d M Y') : \Carbon\Carbon::parse($from)->format('d M Y').' to '.\Carbon\Carbon::parse($to)->format('d M Y')))
@section('page-title','OPD Daily Register')
@section('content')

@push('styles')
<style>
.reg-page { max-width:1200px; }

.letterhead { text-align:center; margin-bottom:14px; border-bottom:2px solid #1e3a5f; padding-bottom:10px; }
.letterhead .hosp-name { font-size:18px; font-weight:700; color:#1e3a5f; }
.letterhead .hosp-sub  { font-size:11px; color:#555; margin-top:2px; }
.letterhead .doc-title  { font-size:14px; font-weight:700; margin-top:8px; letter-spacing:.5px; }
.letterhead .doc-meta   { font-size:11px; color:#444; margin-top:2px; }

.summary-row { display:flex; gap:16px; margin-bottom:14px; }
.summary-box { flex:1; border:1px solid #ccc; border-radius:4px; padding:8px 12px; text-align:center; }
.summary-box .num { font-size:22px; font-weight:800; line-height:1; }
.summary-box .lbl { font-size:10px; color:#666; margin-top:2px; }

.reg-table { width:100%; border-collapse:collapse; font-size:11.5px; }
.reg-table thead th { background:#1e3a5f; color:#fff; padding:7px 8px; text-align:left; font-weight:600; font-size:11px; }
.reg-table tbody tr:nth-child(even) { background:#f7f8fa; }
.reg-table tbody td { padding:6px 8px; border-bottom:1px solid #e0e0e0; vertical-align:top; }
.token-cell { font-weight:700; font-size:14px; color:#1e3a5f; }
.urgent-row { background:#fff5f5 !important; }
.reg-badge { display:inline-block; padding:1px 7px; border-radius:10px; font-size:10px; font-weight:600; }
.b-done     { background:#dcfce7; color:#14532d; }
.b-waiting  { background:#fef3c7; color:#92400e; }
.b-consult  { background:#dbeafe; color:#1e40af; }
.b-skipped  { background:#f3f4f6; color:#9ca3af; }
.b-urgent   { background:#fee2e2; color:#991b1b; }
.b-normal   { background:#f3f4f6; color:#555; }

.sig-row { display:flex; gap:40px; margin-top:24px; }
.sig-box  { flex:1; border-top:1px solid #333; padding-top:4px; font-size:11px; text-align:center; color:#555; }

@media print {
    .reg-letterhead-print { display:block !important; }
}
.reg-letterhead-print { display:none; }
</style>
@endpush

<div class="reg-page">

{{-- Toolbar (hidden on print by layout's @media print) --}}
<div class="page-header no-print">
    <div class="page-header-left">
        <h1>OPD Daily Register</h1>
        <div class="breadcrumb"><a href="{{ route('opd.index') }}">OPD</a> &rsaquo; Daily Register</div>
    </div>
    @include('opd._toolbar', ['inline'=>true, 'extra' => [
        ['url'=>'#', 'onclick'=>'window.print();return false;', 'icon'=>'fa-print', 'label'=>'Print Register', 'bg'=>'#0f766e'],
    ]])
</div>

{{-- Filter bar --}}
<form method="GET" class="no-print" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-bottom:16px">
    <label class="text-small text-muted" style="align-self:center">From</label>
    <input type="date" name="from" value="{{ $from }}" class="form-control" style="width:150px">
    <label class="text-small text-muted" style="align-self:center">To</label>
    <input type="date" name="to" value="{{ $to }}" class="form-control" style="width:150px">
    <select name="doctor_id" class="form-control" style="width:190px">
        <option value="">All Doctors</option>
        @foreach($doctors as $d)
        <option value="{{ $d->id }}" {{ $doctorId == $d->id ? 'selected' : '' }}>Dr. {{ $d->first_name }} {{ $d->last_name }}</option>
        @endforeach
    </select>
    <button type="submit" class="btn btn-primary">Apply</button>
    <a href="{{ route('opd.register', ['doctor_id' => $doctorId, 'from' => today()->toDateString(), 'to' => today()->toDateString()]) }}" class="btn btn-outline {{ $from === $to && $from === today()->toDateString() ? 'btn-primary' : '' }}"><i class="fa-solid fa-calendar-day"></i> Today</a>
    <a href="{{ route('opd.register', ['doctor_id' => $doctorId, 'from' => today()->subDay()->toDateString(), 'to' => today()->subDay()->toDateString()]) }}" class="btn btn-outline"><i class="fa-solid fa-calendar-minus"></i> Yesterday</a>
    <a href="{{ route('opd.register', ['doctor_id' => $doctorId, 'from' => today()->subDays(6)->toDateString(), 'to' => today()->toDateString()]) }}" class="btn btn-outline"><i class="fa-solid fa-calendar-week"></i> Last Week</a>
    <a href="{{ route('opd.register', ['doctor_id' => $doctorId, 'from' => today()->subDays(29)->toDateString(), 'to' => today()->toDateString()]) }}" class="btn btn-outline"><i class="fa-solid fa-calendar-days"></i> Last Month</a>
</form>

{{-- Letterhead (visible only when printing) --}}
<div class="reg-letterhead-print letterhead">
    <div class="hosp-name">{{ setting('clinic_name', 'James Heart Care Pvt. Ltd.') }}</div>
    <div class="hosp-sub">{{ setting('clinic_address', '') }}</div>
    <div class="doc-title">OPD DAILY REGISTER</div>
    <div class="doc-meta">
        {{ $from === $to ? 'Date' : 'Period' }}: <strong>{{ \Carbon\Carbon::parse($from)->format('d F Y') }}{{ $from === $to ? '' : ' — '.\Carbon\Carbon::parse($to)->format('d F Y') }}</strong>
        @if($doctorId)
            &nbsp;|&nbsp; Doctor: <strong>Dr. {{ $doctors->firstWhere('id', $doctorId)?->first_name }} {{ $doctors->firstWhere('id', $doctorId)?->last_name }}</strong>
        @endif
    </div>
</div>

{{-- Summary --}}
<div class="summary-row">
    <div class="summary-box"><div class="num">{{ $stats['total'] }}</div><div class="lbl">Total Patients</div></div>
    <div class="summary-box"><div class="num" style="color:#14532d">{{ $stats['done'] }}</div><div class="lbl">Completed</div></div>
    <div class="summary-box"><div class="num" style="color:#92400e">{{ $stats['waiting'] }}</div><div class="lbl">Waiting</div></div>
    <div class="summary-box"><div class="num" style="color:#9ca3af">{{ $stats['skipped'] }}</div><div class="lbl">Skipped</div></div>
</div>

{{-- Register table --}}
<div class="card">
<div class="table-wrap">
<table class="reg-table">
    <thead>
        <tr>
            @if($from !== $to)<th style="width:7%">Date</th>@endif
            <th style="width:6%">Token</th>
            <th style="width:5%">Time</th>
            <th style="width:16%">Patient Name</th>
            <th style="width:8%">Reg. No.</th>
            <th style="width:6%">Age/Sex</th>
            <th style="width:14%">Doctor</th>
            <th style="width:16%">Purpose / Complaint</th>
            <th style="width:8%">Priority</th>
            <th style="width:8%">BP</th>
            <th style="width:6%">Pulse</th>
            <th style="width:7%">Status</th>
            <th style="width:8%">Done At</th>
        </tr>
    </thead>
    <tbody>
    @forelse($tokens as $t)
    <tr class="{{ $t->priority === 'urgent' ? 'urgent-row' : '' }}">
        @if($from !== $to)<td>{{ \Carbon\Carbon::parse($t->token_date)->format('d-m-y') }}</td>@endif
        <td class="token-cell">{{ $t->display }}</td>
        <td>{{ $t->created_at->format('h:i A') }}</td>
        <td><strong>{{ $t->patient->full_name }}</strong></td>
        <td style="font-size:10px;color:#555">{{ $t->patient->jhc_id }}</td>
        <td>{{ $t->patient->age }} / {{ substr($t->patient->gender, 0, 1) }}</td>
        <td>{{ $t->doctor ? 'Dr. '.$t->doctor->first_name.' '.$t->doctor->last_name : '—' }}@if($t->department)<div style="font-size:11px;color:#555">{{ $t->department->name }}</div>@endif</td>
        <td>{{ $t->purpose ?: '—' }}</td>
        <td><span class="reg-badge {{ $t->priority === 'urgent' ? 'b-urgent' : 'b-normal' }}">{{ ucfirst($t->priority) }}</span></td>
        <td>
            @if($t->triage && $t->triage->bp_systolic)
                {{ $t->triage->bp_systolic }}/{{ $t->triage->bp_diastolic }}
            @elseif($t->encounter && $t->encounter->bp_systolic)
                {{ $t->encounter->bp_systolic }}/{{ $t->encounter->bp_diastolic }}
            @else —
            @endif
        </td>
        <td>
            @if($t->triage && $t->triage->pulse) {{ $t->triage->pulse }}
            @elseif($t->encounter && $t->encounter->pulse) {{ $t->encounter->pulse }}
            @else —
            @endif
        </td>
        <td>
            @php $badgeMap = ['done'=>'b-done','waiting'=>'b-waiting','in_consultation'=>'b-consult','skipped'=>'b-skipped','called'=>'b-consult']; @endphp
            <span class="reg-badge {{ $badgeMap[$t->status] ?? '' }}">{{ ucwords(str_replace('_',' ',$t->status)) }}</span>
        </td>
        <td style="font-size:10px">{{ $t->completed_at?->format('h:i A') ?? '—' }}</td>
    </tr>
    @empty
    <tr><td colspan="{{ $from !== $to ? 13 : 12 }}" style="text-align:center;padding:30px;color:#888">No OPD tokens for this {{ $from === $to ? 'date' : 'period' }}.</td></tr>
    @endforelse
    </tbody>
    @if($tokens->isNotEmpty())
    <tfoot>
        <tr style="background:#f0f4f8;font-weight:700">
            <td colspan="{{ $from !== $to ? 7 : 6 }}" style="padding:6px 8px">Total: {{ $stats['total'] }} patients</td>
            <td colspan="6" style="padding:6px 8px;text-align:right">Completed: {{ $stats['done'] }} &nbsp;|&nbsp; Skipped: {{ $stats['skipped'] }}</td>
        </tr>
    </tfoot>
    @endif
</table>
</div>
</div>

{{-- Signatures (print only) --}}
<div class="sig-row" style="margin-top:30px">
    <div class="sig-box">Receptionist / Registration In-charge</div>
    <div class="sig-box">Nursing In-charge</div>
    <div class="sig-box">Medical Officer / OPD In-charge</div>
</div>

</div>
@endsection
