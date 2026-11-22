@extends('layouts.app')
@section('title', 'Live Bed Board')
@section('content')

<div class="page-header">
    <div class="page-header-left">
        <h1><i class="fa-solid fa-bed-pulse" style="color:var(--sc-teal)"></i> {{ $isHistorical ? 'Bed Board' : 'Live Bed Board' }}</h1>
        <div class="breadcrumb">Wards &rsaquo; Bed Board
            @if($isHistorical) &middot; <strong>as of {{ $asOfDate->format('d M Y') }}</strong>
            @else &middot; <span id="lastRefreshed">Updated just now</span> @endif
        </div>
    </div>
    @include('ipd._toolbar', ['inline'=>true, 'extra' => [
        ['url'=>'#', 'onclick'=>'location.reload();return false;', 'icon'=>'fa-rotate-right', 'label'=>'Refresh', 'bg'=>'#0f766e'],
    ]])
</div>

{{-- As-of date selector --}}
<form method="GET" class="no-print" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-bottom:16px">
    <label class="text-small text-muted" style="align-self:center">Bed status as of</label>
    <input type="date" name="date" value="{{ $asOf }}" max="{{ today()->toDateString() }}" class="form-control" style="width:160px">
    <button type="submit" class="btn btn-primary btn-sm">View</button>
    <a href="{{ route('wards.bed-board') }}" class="btn btn-outline btn-sm {{ !$isHistorical ? 'btn-primary' : '' }}"><i class="fa-solid fa-bolt"></i> Live (Today)</a>
    <a href="{{ route('wards.bed-board', ['date'=>today()->subDay()->toDateString()]) }}" class="btn btn-outline btn-sm"><i class="fa-solid fa-calendar-minus"></i> Yesterday</a>
    <a href="{{ route('wards.bed-board', ['date'=>today()->subDays(7)->toDateString()]) }}" class="btn btn-outline btn-sm"><i class="fa-solid fa-calendar-week"></i> Last Week</a>
    <a href="{{ route('wards.bed-board', ['date'=>today()->subDays(30)->toDateString()]) }}" class="btn btn-outline btn-sm"><i class="fa-solid fa-calendar-days"></i> Last Month</a>
</form>

@if($isHistorical)
<div class="alert no-print" style="background:#eff6ff;border:1px solid #bfdbfe;color:#1e40af;padding:10px 14px;border-radius:6px;margin-bottom:16px">
    <i class="fa-solid fa-clock-rotate-left"></i> Showing reconstructed occupancy <strong>as of {{ $asOfDate->format('d M Y') }}</strong> from admission history. Reserved / maintenance are live-only states and are not shown for past dates.
</div>
@endif

{{-- Summary strip --}}
<div style="display:grid;grid-template-columns:repeat(6,1fr);gap:12px;margin-bottom:24px">
    <div class="stat-card">
        <div class="stat-icon navy"><i class="fa-solid fa-hospital"></i></div>
        <div><div class="stat-value">{{ $summary['total'] }}</div><div class="stat-label">Total Beds</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fa-solid fa-circle-check"></i></div>
        <div><div class="stat-value" style="color:var(--sc-green)">{{ $summary['available'] }}</div><div class="stat-label">Available</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red"><i class="fa-solid fa-bed-pulse"></i></div>
        <div><div class="stat-value" style="color:var(--sc-red)">{{ $summary['occupied'] }}</div><div class="stat-label">Occupied</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon amber"><i class="fa-solid fa-clock"></i></div>
        <div><div class="stat-value" style="color:var(--sc-amber)">{{ $summary['reserved'] }}</div><div class="stat-label">Reserved</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon navy"><i class="fa-solid fa-wrench"></i></div>
        <div><div class="stat-value">{{ $summary['maintenance'] }}</div><div class="stat-label">Maintenance</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#e0f2fe;color:#0369a1"><i class="fa-solid fa-broom"></i></div>
        <div><div class="stat-value" style="color:#0369a1">{{ $summary['cleaning'] ?? 0 }}</div><div class="stat-label">Cleaning</div></div>
    </div>
</div>

{{-- Occupancy bar --}}
@if($summary['total'] > 0)
@php $pct = round($summary['occupied'] / $summary['total'] * 100); @endphp
<div class="card mb-4" style="padding:14px 20px">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
        <span style="font-size:13px;font-weight:600;color:var(--sc-navy)">Hospital Occupancy</span>
        <span style="font-size:13px;font-weight:700;color:{{ $pct >= 90 ? 'var(--sc-red)' : ($pct >= 70 ? 'var(--sc-amber)' : 'var(--sc-green)') }}">{{ $pct }}%</span>
    </div>
    <div style="height:10px;background:var(--sc-border);border-radius:5px;overflow:hidden">
        <div style="height:100%;width:{{ $pct }}%;background:{{ $pct >= 90 ? 'var(--sc-red)' : ($pct >= 70 ? 'var(--sc-amber)' : 'var(--sc-teal)') }};border-radius:5px;transition:width .4s"></div>
    </div>
    <div style="display:flex;gap:18px;margin-top:8px;font-size:11px;color:var(--sc-muted)">
        <span><span style="display:inline-block;width:8px;height:8px;background:var(--sc-teal);border-radius:50%;margin-right:4px"></span>Available ({{ $summary['available'] }})</span>
        <span><span style="display:inline-block;width:8px;height:8px;background:var(--sc-red);border-radius:50%;margin-right:4px"></span>Occupied ({{ $summary['occupied'] }})</span>
        <span><span style="display:inline-block;width:8px;height:8px;background:var(--sc-amber);border-radius:50%;margin-right:4px"></span>Reserved ({{ $summary['reserved'] }})</span>
        <span><span style="display:inline-block;width:8px;height:8px;background:var(--sc-muted);border-radius:50%;margin-right:4px"></span>Maintenance ({{ $summary['maintenance'] }})</span>
        <span><span style="display:inline-block;width:8px;height:8px;background:#0369a1;border-radius:50%;margin-right:4px"></span>Cleaning ({{ $summary['cleaning'] ?? 0 }})</span>
    </div>
</div>
@endif

{{-- Stay-length legend --}}
<div class="card mb-4" style="padding:12px 20px">
    <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap">
        <span style="font-size:11.5px;font-weight:700;color:var(--sc-muted);text-transform:uppercase;letter-spacing:.5px;margin-right:6px">Occupied beds by stay:</span>
        <span class="bb-legend-pill" style="background:#f0fdf4;border-color:#86efac;color:#15803d"><i class="fa-solid fa-circle" style="font-size:7px"></i> Day 1 — just admitted</span>
        <span class="bb-legend-pill" style="background:#fffbeb;border-color:#fcd34d;color:#92400e"><i class="fa-solid fa-circle" style="font-size:7px"></i> Day 2–3 — short stay</span>
        <span class="bb-legend-pill" style="background:#fff7ed;border-color:#fdba74;color:#9a3412"><i class="fa-solid fa-circle" style="font-size:7px"></i> Day 4–6 — medium stay</span>
        <span class="bb-legend-pill" style="background:#fef2f2;border-color:#fca5a5;color:#991b1b"><i class="fa-solid fa-circle" style="font-size:7px"></i> Day 7+ — long stay</span>
        <span style="margin-left:auto;display:flex;gap:10px">
            <span class="bb-legend-pill" style="background:#f0fdf4;border-color:#86efac;color:#15803d">Available</span>
            <span class="bb-legend-pill" style="background:#fffbeb;border-color:#fcd34d;color:#d97706">Reserved</span>
            <span class="bb-legend-pill" style="background:#f9fafb;border-color:#d1d5db;color:#6b7280">Maint.</span>
        </span>
    </div>
</div>

{{-- Ward sections --}}
@foreach($wards as $ward)
@php
    $wardAdmissions = $ward->beds->map(fn($b) => $admissions[$b->id] ?? null)->filter();
    $longStay = $wardAdmissions->filter(fn($a) => \Carbon\Carbon::parse($a->admission_date)->diffInDays($asOfDate) >= 6)->count();
    $wardOccupied = $isHistorical ? $ward->beds->filter(fn($b) => isset($admissions[$b->id]))->count() : $ward->beds->where('status','occupied')->count();
    $wardFree     = $isHistorical ? ($ward->beds->count() - $wardOccupied) : $ward->beds->where('status','available')->count();
@endphp
<div class="card mb-4">
    <div class="card-header">
        <i class="fa-solid fa-door-open"></i> {{ $ward->name }}
        <span class="badge badge-navy" style="margin-left:6px">{{ \App\Models\Ward::TYPES[$ward->type] ?? $ward->type }}</span>
        @if($ward->floor) <span class="text-muted text-small" style="margin-left:6px">Floor {{ $ward->floor }}</span> @endif
        @if($longStay > 0)
        <span class="badge badge-red" style="margin-left:8px" title="{{ $longStay }} patient(s) with 7+ day stay">
            <i class="fa-solid fa-triangle-exclamation"></i> {{ $longStay }} long stay
        </span>
        @endif
        <div class="card-actions">
            <span class="text-small text-muted">
                {{ $wardFree }} free /
                {{ $wardOccupied }} occupied /
                {{ $ward->beds->count() }} total
            </span>
        </div>
    </div>
    <div class="card-body">
        @if($ward->beds->isEmpty())
        <div style="text-align:center;color:var(--sc-muted);padding:24px">No beds configured for this ward.</div>
        @else
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:12px">
            @foreach($ward->beds as $bed)
            @php
                $admission = $admissions[$bed->id] ?? null;
                $days = $admission
                    ? (\Carbon\Carbon::parse($admission->admission_date)->diffInDays($asOfDate) + 1)
                    : 0;

                if ($admission) {
                    if ($days === 1) {
                        $c = ['bg'=>'#f0fdf4','border'=>'#86efac','text'=>'#15803d','badge_bg'=>'#dcfce7','badge_text'=>'#15803d','icon'=>'fa-bed-pulse'];
                    } elseif ($days <= 3) {
                        $c = ['bg'=>'#fffbeb','border'=>'#fcd34d','text'=>'#92400e','badge_bg'=>'#fef9c3','badge_text'=>'#92400e','icon'=>'fa-bed-pulse'];
                    } elseif ($days <= 6) {
                        $c = ['bg'=>'#fff7ed','border'=>'#fdba74','text'=>'#9a3412','badge_bg'=>'#ffedd5','badge_text'=>'#9a3412','icon'=>'fa-bed-pulse'];
                    } else {
                        $c = ['bg'=>'#fef2f2','border'=>'#fca5a5','text'=>'#991b1b','badge_bg'=>'#fee2e2','badge_text'=>'#991b1b','icon'=>'fa-triangle-exclamation'];
                    }
                } else {
                    $statusColors = [
                        'available'   => ['bg'=>'#f0fdf4','border'=>'#86efac','text'=>'#15803d','icon'=>'fa-circle-check'],
                        'reserved'    => ['bg'=>'#fffbeb','border'=>'#fcd34d','text'=>'#d97706','icon'=>'fa-clock'],
                        'maintenance' => ['bg'=>'#f9fafb','border'=>'#d1d5db','text'=>'#6b7280','icon'=>'fa-wrench'],
                        'cleaning'    => ['bg'=>'#f0f9ff','border'=>'#7dd3fc','text'=>'#0369a1','icon'=>'fa-broom'],
                    ];
                    // Past dates: reserved/maintenance are live-only, so an empty bed reads as Available.
                    $c = $isHistorical ? $statusColors['available'] : ($statusColors[$bed->status] ?? $statusColors['available']);
                }
            @endphp
            <div class="bed-tile" style="background:{{ $c['bg'] }};border:1.5px solid {{ $c['border'] }};border-radius:10px;padding:12px;position:relative;min-height:100px;{{ $admission ? 'cursor:pointer;' : '' }}"
                 @if($admission) onclick="window.location='{{ route('admissions.show', $admission) }}'" @endif
                 title="{{ $admission ? $admission->patient->full_name . ' — ' . $admission->ipd_no . ' · Day ' . $days : ($isHistorical ? 'Available' : $bed->status_label) }}">

                {{-- Bed number + icon --}}
                <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:6px">
                    <span style="font-weight:700;font-size:14px;color:var(--sc-navy)">{{ $bed->bed_number }}</span>
                    <i class="fa-solid {{ $c['icon'] }}" style="color:{{ $c['text'] }};font-size:13px"></i>
                </div>

                @if($admission)
                {{-- Patient name --}}
                <div style="font-size:12px;font-weight:600;color:var(--sc-navy);line-height:1.3;margin-bottom:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                    {{ Str::limit($admission->patient->full_name, 20) }}
                </div>
                <div style="font-size:10.5px;color:var(--sc-muted);font-family:'DM Mono',monospace;margin-bottom:6px">{{ $admission->ipd_no }}</div>
                {{-- Day badge --}}
                <span style="display:inline-flex;align-items:center;gap:4px;background:{{ $c['badge_bg'] }};color:{{ $c['badge_text'] }};padding:3px 8px;border-radius:20px;font-size:11px;font-weight:700">
                    <i class="fa-solid fa-calendar-days" style="font-size:9px"></i>
                    Day {{ $days }}
                </span>
                @else
                <div style="font-size:11px;color:{{ $c['text'] }};font-weight:600;margin-top:4px">
                    {{ $isHistorical ? 'Available' : $bed->status_label }}
                </div>
                @if($bed->type !== 'general')
                <div style="font-size:10px;color:var(--sc-muted);margin-top:2px">{{ ucfirst($bed->type) }}</div>
                @endif
                @if(!$isHistorical && $bed->status === 'cleaning')
                <form method="POST" action="{{ route('wards.beds.update', $bed) }}" style="margin-top:6px">
                    @csrf @method('PATCH')
                    <input type="hidden" name="status" value="available">
                    <button class="btn btn-sm" style="background:#0369a1;color:#fff;padding:2px 8px;font-size:11px"><i class="fa-solid fa-check"></i> Mark clean</button>
                </form>
                @endif
                @endif
            </div>
            @endforeach
        </div>
        @endif
    </div>
</div>
@endforeach

@push('styles')
<style>
.bed-tile { transition: box-shadow .15s, transform .15s; }
.bed-tile:hover { box-shadow: 0 4px 14px rgba(0,0,0,.12); transform: translateY(-2px); }
.bb-legend-pill {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 4px 10px; border-radius: 20px; font-size: 11.5px;
    font-weight: 600; border: 1.5px solid;
}
@media print {
    .page-header button, .page-header a { display: none !important; }
}
</style>
@endpush
@endsection
