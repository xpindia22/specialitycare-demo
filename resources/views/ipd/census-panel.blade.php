{{-- Panel-mode census — loaded via fetch() into the right-side drawer --}}
<style>
.cp-strip { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:16px; }
.cp-kpi   { background:var(--sc-surface); border:1px solid var(--sc-border); border-radius:8px; padding:10px 14px; text-align:center; flex:1; min-width:72px; }
.cp-kpi-n { font-size:22px; font-weight:800; line-height:1; }
.cp-kpi-l { font-size:10px; color:var(--sc-muted); margin-top:3px; }
.cp-section { font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.8px; color:var(--sc-muted); margin:14px 0 6px; padding-bottom:4px; border-bottom:1px solid var(--sc-border); }
.cp-table { width:100%; border-collapse:collapse; font-size:12px; }
.cp-table th { background:var(--sc-border); padding:5px 8px; text-align:left; font-size:10.5px; font-weight:700; }
.cp-table td { padding:5px 8px; border-bottom:1px solid var(--sc-border); vertical-align:top; }
.cp-table tr:last-child td { border-bottom:none; }
.cp-ward-hdr { background:var(--sc-navy); color:#fff; padding:5px 10px; font-size:11px; font-weight:700; border-radius:5px 5px 0 0; margin-top:12px; display:flex; justify-content:space-between; }
.cp-ls { background:#fff8e1; }
.cp-vitals { font-size:10.5px; color:var(--sc-muted); line-height:1.6; }
</style>

{{-- Summary KPIs --}}
<div class="cp-strip">
    <div class="cp-kpi"><div class="cp-kpi-n">{{ $totalBeds }}</div><div class="cp-kpi-l">Total Beds</div></div>
    <div class="cp-kpi"><div class="cp-kpi-n" style="color:var(--sc-red)">{{ $occupiedBeds }}</div><div class="cp-kpi-l">Occupied</div></div>
    <div class="cp-kpi"><div class="cp-kpi-n" style="color:var(--sc-green)">{{ $availableBeds }}</div><div class="cp-kpi-l">Available</div></div>
    <div class="cp-kpi"><div class="cp-kpi-n" style="color:var(--sc-teal)">{{ $todayAdmissions->count() }}</div><div class="cp-kpi-l">In Today</div></div>
    <div class="cp-kpi"><div class="cp-kpi-n" style="color:var(--sc-amber)">{{ $todayDischarges->count() }}</div><div class="cp-kpi-l">Out Today</div></div>
    @if($totalBeds > 0)
    <div class="cp-kpi"><div class="cp-kpi-n">{{ round(($occupiedBeds/$totalBeds)*100) }}%</div><div class="cp-kpi-l">Occupancy</div></div>
    @endif
</div>

{{-- Per-ward bed status --}}
<div class="cp-section">Ward Status</div>
<table class="cp-table">
    <thead><tr><th>Ward</th><th style="text-align:center">Beds</th><th style="text-align:center">Occupied</th><th style="text-align:center">Free</th></tr></thead>
    <tbody>
    @foreach($allWards as $ward)
    @php $wOcc = $ward->beds->where('status','occupied')->count(); $wFree = $ward->beds->where('status','available')->count(); @endphp
    <tr>
        <td style="font-weight:600">{{ $ward->name }}</td>
        <td style="text-align:center">{{ $ward->beds->count() }}</td>
        <td style="text-align:center;color:var(--sc-red);font-weight:700">{{ $wOcc }}</td>
        <td style="text-align:center;color:var(--sc-green);font-weight:700">{{ $wFree }}</td>
    </tr>
    @endforeach
    </tbody>
</table>

{{-- Today's admissions --}}
<div class="cp-section">Admitted Today ({{ $todayAdmissions->count() }})</div>
@if($todayAdmissions->isNotEmpty())
<table class="cp-table">
    <thead><tr><th>IPD No.</th><th>Patient</th><th>Ward</th><th>Doctor</th></tr></thead>
    <tbody>
    @foreach($todayAdmissions as $adm)
    <tr>
        <td class="mono" style="font-weight:700;font-size:11px">{{ $adm->ipd_no }}</td>
        <td><div style="font-weight:600">{{ $adm->patient->full_name }}</div><div style="font-size:10px;color:var(--sc-muted)">{{ $adm->patient->jhc_id }}</div></td>
        <td style="font-size:11px">{{ $adm->ward->name ?? '—' }}</td>
        <td style="font-size:11px">{{ $adm->admittingDoctor ? 'Dr. '.$adm->admittingDoctor->last_name : '—' }}</td>
    </tr>
    @endforeach
    </tbody>
</table>
@else
<div style="color:var(--sc-muted);font-size:12px;padding:8px 0">No admissions today.</div>
@endif

{{-- Today's discharges --}}
<div class="cp-section">Discharged Today ({{ $todayDischarges->count() }})</div>
@if($todayDischarges->isNotEmpty())
<table class="cp-table">
    <thead><tr><th>IPD No.</th><th>Patient</th><th>Ward</th><th>Time</th></tr></thead>
    <tbody>
    @foreach($todayDischarges as $adm)
    <tr>
        <td class="mono" style="font-weight:700;font-size:11px">{{ $adm->ipd_no }}</td>
        <td><div style="font-weight:600">{{ $adm->patient->full_name }}</div><div style="font-size:10px;color:var(--sc-muted)">{{ $adm->patient->jhc_id }}</div></td>
        <td style="font-size:11px">{{ $adm->ward->name ?? '—' }}</td>
        <td style="font-size:11px">{{ $adm->discharge_date?->format('h:i A') ?? '—' }}</td>
    </tr>
    @endforeach
    </tbody>
</table>
@else
<div style="color:var(--sc-muted);font-size:12px;padding:8px 0">No discharges today.</div>
@endif

{{-- Current inpatients by ward --}}
<div class="cp-section">Current Inpatients ({{ $admissions->count() }})</div>
@forelse($wardGroups as $wardId => $wardAdmissions)
@php $ward = $wardAdmissions->first()->ward; @endphp
<div class="cp-ward-hdr">
    <span>{{ $ward->name ?? 'Unknown Ward' }}</span>
    <span style="opacity:.75;font-weight:400">{{ $wardAdmissions->count() }} pt{{ $wardAdmissions->count()!=1?'s':'' }}</span>
</div>
<table class="cp-table" style="border-radius:0 0 5px 5px;overflow:hidden;margin-bottom:0">
    <thead><tr><th>Bed</th><th>Patient</th><th>Day</th><th>Vitals</th></tr></thead>
    <tbody>
    @foreach($wardAdmissions->sortBy(fn($a) => $a->bed->bed_number ?? '') as $adm)
    @php $lv = $adm->vitals->first(); @endphp
    <tr class="{{ $adm->days_admitted > 7 ? 'cp-ls' : '' }}">
        <td class="mono" style="font-weight:700">{{ $adm->bed->bed_number ?? '—' }}</td>
        <td>
            <a href="{{ route('admissions.show', $adm) }}" style="font-weight:600;color:var(--sc-navy);text-decoration:none;font-size:12px">{{ $adm->patient->full_name }}</a>
            <div style="font-size:10px;color:var(--sc-muted)">{{ $adm->patient->jhc_id }}</div>
        </td>
        <td style="text-align:center">
            <span class="badge {{ $adm->days_admitted > 7 ? 'badge-amber' : 'badge-teal' }}">D{{ $adm->days_admitted }}</span>
        </td>
        <td class="cp-vitals">
            @if($lv)
                @if($lv->bp)<div>BP {{ $lv->bp }}</div>@endif
                @if($lv->pulse)<div>P {{ $lv->pulse }}</div>@endif
                @if($lv->temperature)<div>T {{ $lv->temperature }}°F</div>@endif
            @else <span style="color:var(--sc-border)">—</span>
            @endif
        </td>
    </tr>
    @endforeach
    </tbody>
</table>
@empty
<div style="color:var(--sc-muted);font-size:12px;padding:12px 0;text-align:center">No patients currently admitted.</div>
@endforelse
