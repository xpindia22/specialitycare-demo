{{-- FILE: resources/views/ipd/census.blade.php --}}
@extends('layouts.app')
@section('title','IPD Census — ' . now()->format('d M Y'))
@section('page-title','IPD Daily Census')

@push('styles')
<style>
/* ── Census-specific styles ── */
.census-wrap { max-width:1140px; }

/* Summary strip */
.summary-strip { display:flex; gap:18px; background:var(--sc-surface); border:1px solid var(--sc-border); border-radius:8px; padding:12px 18px; margin-bottom:20px; flex-wrap:wrap; }
.ss-item { display:flex; gap:8px; align-items:center; }
.ss-num  { font-size:22px; font-weight:800; line-height:1; }
.ss-lbl  { font-size:9.5px; color:var(--sc-muted); margin-top:2px; }

/* Section headings */
.census-section { font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.7px; color:var(--sc-navy); border-bottom:2px solid var(--sc-navy); padding-bottom:5px; margin:22px 0 10px; }

/* Ward breakdown table */
table.ward-tbl { width:100%; border-collapse:collapse; font-size:11.5px; margin-bottom:18px; }
table.ward-tbl th { background:var(--sc-border); padding:6px 10px; text-align:left; font-weight:700; font-size:10px; text-transform:uppercase; letter-spacing:.3px; }
table.ward-tbl td { padding:6px 10px; border-bottom:1px solid var(--sc-border); }
table.ward-tbl tfoot td { background:var(--sc-navy); color:#fff; font-weight:700; }
.pct-bar { height:6px; background:var(--sc-border); border-radius:3px; overflow:hidden; margin-top:3px; }
.pct-fill { height:100%; background:var(--sc-navy); border-radius:3px; }

/* Ward section */
.ward-header { background:var(--sc-navy); color:#fff; padding:6px 12px; font-size:11.5px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; margin-bottom:0; border-radius:6px 6px 0 0; display:flex; justify-content:space-between; align-items:center; }
.ward-count  { font-size:10px; opacity:.75; font-weight:400; }

/* Census table */
table.census-tbl { width:100%; border-collapse:collapse; font-size:11px; margin-bottom:18px; border-radius:0 0 6px 6px; overflow:hidden; }
table.census-tbl th { background:var(--sc-border); padding:5px 8px; text-align:left; font-weight:700; font-size:10px; text-transform:uppercase; letter-spacing:.3px; }
table.census-tbl td { padding:6px 8px; border-bottom:1px solid var(--sc-border); vertical-align:top; }
table.census-tbl tr:last-child td { border-bottom:none; }
.cell-mono  { font-family:'DM Mono',monospace; font-weight:700; }
.long-stay td { background:#fff8e1 !important; }
.vitals-cell { font-size:10px; line-height:1.7; color:var(--sc-muted); }

/* Mini tables (admissions/discharges today) */
table.mini-tbl { width:100%; border-collapse:collapse; font-size:11px; margin-bottom:18px; }
table.mini-tbl th { background:var(--sc-border); padding:5px 10px; text-align:left; font-weight:700; font-size:10px; text-transform:uppercase; }
table.mini-tbl td { padding:5px 10px; border-bottom:1px solid var(--sc-border); }
table.mini-tbl tr:last-child td { border-bottom:none; }

/* Signature area */
.sig-area { display:flex; justify-content:space-between; margin-top:32px; }
.sig-box  { text-align:center; width:180px; }
.sig-line { border-top:1px solid var(--sc-muted); padding-top:6px; font-size:10px; color:var(--sc-muted); }
.footer-note { margin-top:14px; font-size:9px; color:var(--sc-muted); border-top:1px solid var(--sc-border); padding-top:6px; text-align:center; }

/* Print overrides */
@media print {
    #sidebar, .app-topbar, .page-header, .no-print { display:none !important; }
    .main-content { margin-left:0 !important; padding:10px !important; }
    body { color:#111; background:#fff; }
    .summary-strip { border:1px solid #ccc; }
    .pct-fill { background:#0f1f3d; }
    .ward-header { background:#0f1f3d !important; color:#fff !important; -webkit-print-color-adjust:exact; print-color-adjust:exact; }
    table.ward-tbl tfoot td { background:#0f1f3d !important; -webkit-print-color-adjust:exact; print-color-adjust:exact; }
    .page-break { page-break-before:always; }
    @page { margin:14mm; }
}
</style>
@endpush

@section('content')

<div class="page-header no-print">
    <div class="page-header-left">
        <h1>IPD Daily Census</h1>
        <div class="breadcrumb">{{ now()->format('l, d F Y') }} &mdash; {{ $occupiedBeds }} patients</div>
    </div>
    @include('ipd._toolbar', ['inline'=>true, 'extra' => [
        ['url'=>'#', 'onclick'=>'window.print();return false;', 'icon'=>'fa-print', 'label'=>'Print', 'bg'=>'#0f766e'],
    ]])
</div>

<div class="census-wrap">

{{-- Summary strip --}}
<div class="summary-strip">
    <div class="ss-item">
        <div><div class="ss-num" style="color:var(--sc-navy)">{{ $totalBeds }}</div><div class="ss-lbl">Total Beds</div></div>
    </div>
    <div class="ss-item">
        <div><div class="ss-num" style="color:var(--sc-red)">{{ $occupiedBeds }}</div><div class="ss-lbl">Occupied</div></div>
    </div>
    <div class="ss-item">
        <div><div class="ss-num" style="color:var(--sc-green)">{{ $availableBeds }}</div><div class="ss-lbl">Available</div></div>
    </div>
    <div class="ss-item">
        <div><div class="ss-num" style="color:var(--sc-teal)">{{ $todayAdmissions->count() }}</div><div class="ss-lbl">Admitted Today</div></div>
    </div>
    <div class="ss-item">
        <div><div class="ss-num" style="color:var(--sc-amber)">{{ $todayDischarges->count() }}</div><div class="ss-lbl">Discharged Today</div></div>
    </div>
    <div class="ss-item">
        <div><div class="ss-num" style="color:var(--sc-red)">{{ $admissions->filter(fn($a) => $a->days_admitted > 7)->count() }}</div><div class="ss-lbl">Long Stay (&gt;7d)</div></div>
    </div>
    @if($totalBeds > 0)
    <div class="ss-item" style="margin-left:auto">
        <div><div class="ss-num" style="color:var(--sc-navy)">{{ round(($occupiedBeds / $totalBeds) * 100) }}%</div><div class="ss-lbl">Occupancy Rate</div></div>
    </div>
    @endif
</div>

{{-- Ward Breakdown Table --}}
<div class="census-section">Per-Ward Bed Status</div>
<table class="ward-tbl">
    <thead>
        <tr>
            <th>Ward Name</th>
            <th>Type</th>
            <th style="text-align:center">Total Beds</th>
            <th style="text-align:center">Occupied</th>
            <th style="text-align:center">Available</th>
            <th style="width:140px">Occupancy</th>
        </tr>
    </thead>
    <tbody>
        @foreach($allWards as $ward)
        @php
            $wTotal     = $ward->total_beds_count;
            $wOccupied  = $ward->occupied_beds_count;
            $wAvailable = $ward->available_beds_count;
            $wPct       = $wTotal > 0 ? round(($wOccupied / $wTotal) * 100) : 0;
        @endphp
        <tr>
            <td style="font-weight:700">{{ $ward->name }}</td>
            <td>{{ $ward->type_label }}</td>
            <td style="text-align:center">{{ $wTotal }}</td>
            <td style="text-align:center;font-weight:700;color:var(--sc-red)">{{ $wOccupied }}</td>
            <td style="text-align:center;font-weight:700;color:var(--sc-green)">{{ $wAvailable }}</td>
            <td>
                <div style="font-size:10px;font-weight:700;margin-bottom:2px">{{ $wPct }}%</div>
                <div class="pct-bar"><div class="pct-fill" style="width:{{ $wPct }}%"></div></div>
            </td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="2">Total</td>
            <td style="text-align:center">{{ $totalBeds }}</td>
            <td style="text-align:center">{{ $occupiedBeds }}</td>
            <td style="text-align:center">{{ $availableBeds }}</td>
            <td>{{ $totalBeds > 0 ? round(($occupiedBeds / $totalBeds) * 100) : 0 }}%</td>
        </tr>
    </tfoot>
</table>

{{-- Today's Admissions --}}
<div class="census-section">Today's Admissions ({{ today()->format('d M Y') }})</div>
@if($todayAdmissions->isNotEmpty())
<table class="mini-tbl">
    <thead>
        <tr>
            <th>IPD No.</th><th>Patient</th><th>Age/Sex</th><th>Ward</th><th>Doctor</th><th>Type</th><th>Time</th><th>Diagnosis</th>
        </tr>
    </thead>
    <tbody>
        @foreach($todayAdmissions as $adm)
        <tr>
            <td class="cell-mono">{{ $adm->ipd_no }}</td>
            <td style="font-weight:600">{{ $adm->patient->full_name }}<div style="font-size:10px;color:var(--sc-muted)">{{ $adm->patient->jhc_id }}</div></td>
            <td>{{ $adm->patient->age }}/{{ strtoupper(substr($adm->patient->gender,0,1)) }}</td>
            <td>{{ $adm->ward->name ?? '—' }}</td>
            <td>{{ $adm->admittingDoctor->full_name ?? '—' }}</td>
            <td><span class="badge badge-navy">{{ \App\Models\Admission::ADMISSION_TYPES[$adm->admission_type] ?? $adm->admission_type }}</span></td>
            <td>{{ $adm->admission_date->format('h:i A') }}</td>
            <td style="font-size:10px">{{ \Illuminate\Support\Str::limit($adm->provisional_diagnosis ?? '—', 50) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@else
<div class="text-muted" style="font-size:12px;padding:12px 0 18px">No admissions today.</div>
@endif

{{-- Today's Discharges --}}
<div class="census-section">Today's Discharges ({{ today()->format('d M Y') }})</div>
@if($todayDischarges->isNotEmpty())
<table class="mini-tbl">
    <thead>
        <tr>
            <th>IPD No.</th><th>Patient</th><th>Age/Sex</th><th>Ward</th><th>Doctor</th><th>Discharge Time</th><th>Condition</th>
        </tr>
    </thead>
    <tbody>
        @foreach($todayDischarges as $adm)
        <tr>
            <td class="cell-mono">{{ $adm->ipd_no }}</td>
            <td style="font-weight:600">{{ $adm->patient->full_name }}<div style="font-size:10px;color:var(--sc-muted)">{{ $adm->patient->jhc_id }}</div></td>
            <td>{{ $adm->patient->age }}/{{ strtoupper(substr($adm->patient->gender,0,1)) }}</td>
            <td>{{ $adm->ward->name ?? '—' }}</td>
            <td>{{ $adm->admittingDoctor->full_name ?? '—' }}</td>
            <td>{{ $adm->discharge_date ? $adm->discharge_date->format('h:i A') : '—' }}</td>
            <td>
                @if($adm->discharge_condition)
                <span class="badge {{ $adm->discharge_condition === 'expired' ? 'badge-amber' : 'badge-teal' }}">
                    {{ \App\Models\Admission::DISCHARGE_CONDITIONS[$adm->discharge_condition] ?? $adm->discharge_condition }}
                </span>
                @else —
                @endif
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
@else
<div class="text-muted" style="font-size:12px;padding:12px 0 18px">No discharges today.</div>
@endif

{{-- Current Ward-wise Census --}}
<div class="census-section page-break">Current Inpatients by Ward</div>

@forelse($wardGroups as $wardId => $wardAdmissions)
@php $ward = $wardAdmissions->first()->ward; @endphp
<div class="ward-header">
    <span>{{ $ward->name ?? 'Unknown' }} <span class="ward-count">| {{ $ward->type_label ?? '' }}</span></span>
    <span class="ward-count">{{ $wardAdmissions->count() }} patient{{ $wardAdmissions->count() != 1 ? 's' : '' }}</span>
</div>
<table class="census-tbl">
    <thead>
        <tr>
            <th style="width:9%">IPD No.</th>
            <th style="width:5%">Bed</th>
            <th style="width:14%">Patient Name</th>
            <th style="width:5%">Age/Sex</th>
            <th style="width:7%">Admitted</th>
            <th style="width:5%">Day</th>
            <th style="width:13%">Doctor</th>
            <th style="width:15%">Diagnosis</th>
            <th style="width:13%">Latest Vitals</th>
            <th style="width:14%">Rounds Notes</th>
        </tr>
    </thead>
    <tbody>
    @foreach($wardAdmissions->sortBy(fn($a) => $a->bed->bed_number ?? '') as $adm)
    @php $lv = $adm->vitals->first(); @endphp
    <tr class="{{ $adm->days_admitted > 7 ? 'long-stay' : '' }}">
        <td class="cell-mono">{{ $adm->ipd_no }}</td>
        <td class="cell-mono">{{ $adm->bed->bed_number ?? '—' }}</td>
        <td style="font-weight:600">
            {{ $adm->patient->full_name }}
            <div style="font-size:9.5px;color:var(--sc-muted)">{{ $adm->patient->jhc_id }}</div>
        </td>
        <td>{{ $adm->patient->age }}/{{ strtoupper(substr($adm->patient->gender,0,1)) }}</td>
        <td>{{ $adm->admission_date->format('d M') }}</td>
        <td style="text-align:center"><span class="badge {{ $adm->days_admitted > 7 ? 'badge-amber' : 'badge-navy' }}">D{{ $adm->days_admitted }}</span></td>
        <td style="font-size:10.5px">{{ $adm->admittingDoctor->full_name ?? '—' }}</td>
        <td style="font-size:10.5px">{{ \Illuminate\Support\Str::limit($adm->provisional_diagnosis ?? '—', 55) }}</td>
        <td class="vitals-cell">
            @if($lv)
                @if($lv->bp)<div>BP {{ $lv->bp }}</div>@endif
                @if($lv->pulse)<div>P {{ $lv->pulse }}/min</div>@endif
                @if($lv->temperature)<div>T {{ $lv->temperature }}°F</div>@endif
                @if($lv->spo2)<div>SpO₂ {{ $lv->spo2 }}%</div>@endif
                <div style="font-size:9px;opacity:.7">{{ $lv->recorded_at->format('d/m h:i A') }}</div>
            @else
                <span style="color:var(--sc-border)">—</span>
            @endif
        </td>
        <td style="border-bottom:1px dotted var(--sc-border);padding-bottom:22px">&nbsp;</td>
    </tr>
    @endforeach
    </tbody>
</table>
@empty
<div class="text-muted" style="font-size:13px;padding:30px 0;text-align:center">No patients currently admitted.</div>
@endforelse

<div class="sig-area no-print" style="margin-top:32px">
    <div class="sig-box"><div class="sig-line">Ward In-charge / Nursing</div></div>
    <div class="sig-box"><div class="sig-line">Medical Officer</div></div>
    <div class="sig-box"><div class="sig-line">HOD / Consultant</div></div>
</div>

<div class="footer-note">
    IPD Daily Census &nbsp;&middot;&nbsp; {{ setting('clinic_name','Hospital') }} &nbsp;&middot;&nbsp; Printed {{ now()->format('d M Y, h:i A') }}
</div>

</div>
@endsection
