{{-- FILE: resources/views/wards/index.blade.php --}}
@extends('layouts.app')
@section('title','Wards & Beds')
@section('page-title','Wards & Beds')
@section('content')

<div class="page-header">
    <div class="page-header-left"><h1>Wards &amp; Beds</h1></div>
    @include('ipd._toolbar', ['inline'=>true, 'extra' => (auth()->user()->isAdmin() ? [
        ['url'=>route('wards.create'), 'icon'=>'fa-plus', 'label'=>'Add Ward', 'bg'=>'#047857'],
    ] : [])])
</div>

{{-- As-of date selector --}}
<form method="GET" class="no-print" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-bottom:14px">
    <label class="text-small text-muted" style="align-self:center">Bed status as of</label>
    <input type="date" name="date" value="{{ $asOf }}" max="{{ today()->toDateString() }}" class="form-control" style="width:160px">
    <button type="submit" class="btn btn-primary btn-sm">View</button>
    <a href="{{ route('wards.index') }}" class="btn btn-outline btn-sm {{ !$isHistorical ? 'btn-primary' : '' }}"><i class="fa-solid fa-bolt"></i> Live (Today)</a>
    <a href="{{ route('wards.index', ['date'=>today()->subDay()->toDateString()]) }}" class="btn btn-outline btn-sm"><i class="fa-solid fa-calendar-minus"></i> Yesterday</a>
    <a href="{{ route('wards.index', ['date'=>today()->subDays(7)->toDateString()]) }}" class="btn btn-outline btn-sm"><i class="fa-solid fa-calendar-week"></i> Last Week</a>
    <a href="{{ route('wards.index', ['date'=>today()->subDays(30)->toDateString()]) }}" class="btn btn-outline btn-sm"><i class="fa-solid fa-calendar-days"></i> Last Month</a>
</form>

@if($isHistorical)
<div class="alert no-print" style="background:#eff6ff;border:1px solid #bfdbfe;color:#1e40af;padding:10px 14px;border-radius:6px;margin-bottom:14px">
    <i class="fa-solid fa-clock-rotate-left"></i> Occupancy <strong>as of {{ $asOfDate->format('d M Y') }}</strong> (from admission history). Reserved / maintenance are live-only and shown as Available for past dates.
</div>
@endif

{{-- Summary bar --}}
<div class="bed-summary-bar">
    <div class="bs-item bs-total">    <div class="bs-num">{{ $summary['total'] }}</div>     <div class="bs-lbl">Total Beds</div></div>
    <div class="bs-item bs-available"><div class="bs-num">{{ $summary['available'] }}</div>  <div class="bs-lbl">Available</div></div>
    <div class="bs-item bs-occupied"> <div class="bs-num">{{ $summary['occupied'] }}</div>   <div class="bs-lbl">Occupied</div></div>
    <div class="bs-item bs-reserved"> <div class="bs-num">{{ $summary['reserved'] }}</div>   <div class="bs-lbl">Reserved</div></div>
    <div class="bs-item bs-maint">    <div class="bs-num">{{ $summary['maintenance'] }}</div><div class="bs-lbl">Maintenance</div></div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

{{-- Ward cards grid --}}
<div class="ward-cards-grid">
@forelse($wards as $ward)
<div class="ward-card {{ !$ward->is_active ? 'ward-inactive' : '' }}">
    <div class="ward-card-header">
        <div>
            <div class="ward-name">{{ $ward->name }}</div>
            <div class="ward-meta">
                <span class="ward-type-badge wt-{{ $ward->type }}">{{ $ward->type_label }}</span>
                @if($ward->floor)<span style="font-size:11px;color:#888;margin-left:6px"><i class="fa-solid fa-stairs"></i> {{ $ward->floor }}</span>@endif
            </div>
        </div>
        <div class="ward-card-actions">
            <a href="{{ route('wards.show', $ward) }}" class="btn btn-sm btn-primary">Manage</a>
            @if(auth()->user()->isAdmin())
            <a href="{{ route('wards.edit', $ward) }}" class="btn btn-sm btn-outline"><i class="fa-solid fa-pen"></i></a>
            @endif
        </div>
    </div>

    @php
        $activeBeds  = $ward->beds->where('is_active', true);
        $wOccupied   = $isHistorical ? $activeBeds->filter(fn($b) => isset($admissions[$b->id]))->count() : $ward->occupied_beds_count;
        $wAvailable  = $isHistorical ? ($activeBeds->count() - $wOccupied) : $ward->available_beds_count;
    @endphp
    {{-- Bed mini-map --}}
    <div class="bed-mini-map">
        @forelse($activeBeds as $bed)
        @php $bs = $isHistorical ? (isset($admissions[$bed->id]) ? 'occupied' : 'available') : $bed->status; @endphp
        <div class="bed-mini bed-{{ $bs }}" title="{{ $bed->bed_number }} — {{ $isHistorical ? ucfirst($bs) : $bed->status_label }}">
            {{ $bed->bed_number }}
        </div>
        @empty
        <div style="font-size:12px;color:#aaa;padding:8px">No beds configured</div>
        @endforelse
    </div>

    <div class="ward-card-footer">
        <span class="wf-available"><i class="fa-solid fa-circle" style="color:#16a34a;font-size:9px"></i> {{ $wAvailable }} available</span>
        <span class="wf-occupied"><i class="fa-solid fa-circle" style="color:#dc2626;font-size:9px"></i> {{ $wOccupied }} occupied</span>
        <span class="wf-total" style="margin-left:auto">{{ $ward->total_beds_count }} total</span>
    </div>
</div>
@empty
<div class="card" style="padding:40px;text-align:center;color:#aaa;grid-column:1/-1">
    No wards configured yet. <a href="{{ route('wards.create') }}">Add the first ward</a>.
</div>
@endforelse
</div>

@push('styles')
<style>
.bed-summary-bar { display:flex; gap:10px; margin-bottom:18px; flex-wrap:wrap; }
.bs-item { background:#fff; border:1px solid #ddd; border-radius:6px; padding:10px 20px; text-align:center; min-width:100px; }
.bs-num  { font-size:28px; font-weight:800; line-height:1; }
.bs-lbl  { font-size:10.5px; color:#666; margin-top:2px; }
.bs-total     .bs-num { color:#333; }
.bs-available .bs-num { color:#16a34a; }
.bs-occupied  .bs-num { color:#dc2626; }
.bs-reserved  .bs-num { color:#d97706; }
.bs-maint     .bs-num { color:#9ca3af; }

.ward-cards-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(320px,1fr)); gap:16px; }

.ward-card { background:#fff; border:1px solid #ddd; border-radius:8px; overflow:hidden; }
.ward-inactive { opacity:.6; }
.ward-card-header { display:flex; align-items:flex-start; justify-content:space-between; padding:14px 16px; border-bottom:1px solid #f0f0f0; }
.ward-name { font-size:15px; font-weight:700; }
.ward-meta { margin-top:4px; }
.ward-card-actions { display:flex; gap:6px; align-items:center; }
.ward-card-footer { display:flex; gap:12px; align-items:center; padding:8px 16px; background:#fafafa; border-top:1px solid #f0f0f0; font-size:11.5px; color:#555; }

.ward-type-badge { display:inline-block; padding:1px 8px; border-radius:10px; font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.3px; }
.wt-general      { background:#dbeafe; color:#1e40af; }
.wt-semi_private { background:#ede9fe; color:#5b21b6; }
.wt-private      { background:#fef9c3; color:#854d0e; }
.wt-icu          { background:#fee2e2; color:#7f1d1d; }
.wt-hdu          { background:#fce7f3; color:#831843; }
.wt-emergency    { background:#ffedd5; color:#7c2d12; }

/* Bed mini-map */
.bed-mini-map { display:flex; flex-wrap:wrap; gap:5px; padding:12px 14px; min-height:56px; }
.bed-mini { width:46px; height:34px; border-radius:4px; display:flex; align-items:center; justify-content:center; font-size:10px; font-weight:700; cursor:default; border:1px solid transparent; }
.bed-available   { background:#dcfce7; color:#14532d; border-color:#86efac; }
.bed-occupied    { background:#fee2e2; color:#7f1d1d; border-color:#fca5a5; }
.bed-reserved    { background:#fef9c3; color:#713f12; border-color:#fde047; }
.bed-maintenance { background:#f3f4f6; color:#9ca3af; border-color:#d1d5db; }
</style>
@endpush
@endsection
