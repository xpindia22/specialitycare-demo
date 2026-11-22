{{-- FILE: resources/views/admissions/index.blade.php --}}
@extends('layouts.app')
@section('title','Admissions')
@section('page-title','IPD Admissions')
@section('content')

<div class="page-header">
    <div class="page-header-left">
        <h1>IPD Admissions</h1>
        <div class="breadcrumb">Inpatient Department &mdash; Admission Register</div>
    </div>
    @include('ipd._toolbar', ['inline'=>true])
</div>

{{-- Stats --}}
<div class="grid grid-3 mb-4">
    <div class="stat-card">
        <div class="stat-icon teal"><i class="fa-solid fa-bed-pulse"></i></div>
        <div>
            <div class="stat-value">{{ $stats['admitted'] }}</div>
            <div class="stat-label">Currently Admitted</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fa-solid fa-person-walking-arrow-right"></i></div>
        <div>
            <div class="stat-value">{{ $stats['discharged'] }}</div>
            <div class="stat-label">Discharged Today</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon amber"><i class="fa-solid fa-bed"></i></div>
        <div>
            <div class="stat-value">{{ $stats['available'] }}</div>
            <div class="stat-label">Beds Available</div>
        </div>
    </div>
</div>

{{-- Filters --}}
<div class="card mb-3">
    <div class="card-body" style="padding:14px 20px">
        <form method="GET" class="flex gap-3 items-center" style="flex-wrap:wrap;gap:8px">
            <input type="hidden" name="status" value="{{ $status }}">
            <div class="topbar-search" style="width:280px">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" name="q" value="{{ $q }}" placeholder="Search IPD no, patient name, JHC ID…">
            </div>
            <label class="text-small text-muted" style="align-self:center">From</label>
            <input type="date" name="from" value="{{ $from }}" class="form-control" style="width:150px">
            <label class="text-small text-muted" style="align-self:center">To</label>
            <input type="date" name="to" value="{{ $to }}" class="form-control" style="width:150px">
            <button type="submit" class="btn btn-primary btn-sm">Apply</button>
            <a href="{{ route('admissions.index', ['status'=>$status,'q'=>$q,'from'=>today()->toDateString(),'to'=>today()->toDateString()]) }}" class="btn btn-outline btn-sm"><i class="fa-solid fa-calendar-day"></i> Today</a>
            <a href="{{ route('admissions.index', ['status'=>$status,'q'=>$q,'from'=>today()->subDay()->toDateString(),'to'=>today()->subDay()->toDateString()]) }}" class="btn btn-outline btn-sm"><i class="fa-solid fa-calendar-minus"></i> Yesterday</a>
            <a href="{{ route('admissions.index', ['status'=>$status,'q'=>$q,'from'=>today()->subDays(6)->toDateString(),'to'=>today()->toDateString()]) }}" class="btn btn-outline btn-sm"><i class="fa-solid fa-calendar-week"></i> Last Week</a>
            <a href="{{ route('admissions.index', ['status'=>$status,'q'=>$q,'from'=>today()->subDays(29)->toDateString(),'to'=>today()->toDateString()]) }}" class="btn btn-outline btn-sm"><i class="fa-solid fa-calendar-days"></i> Last Month</a>
            @if($from || $to)
            <a href="{{ route('admissions.index', ['status'=>$status,'q'=>$q]) }}" class="btn btn-outline btn-sm" title="Clear date range"><i class="fa-solid fa-xmark"></i> Clear dates</a>
            @endif
            <div style="display:flex;gap:6px;flex-wrap:wrap">
                @foreach(['admitted'=>'Admitted','discharged'=>'Discharged','transferred'=>'Transferred','absconded'=>'Absconded','all'=>'All'] as $val => $label)
                <a href="{{ route('admissions.index', ['status'=>$val,'q'=>$q,'from'=>$from,'to'=>$to]) }}"
                   class="btn btn-sm {{ $status === $val ? 'btn-navy' : 'btn-outline' }}">{{ $label }}</a>
                @endforeach
            </div>
        </form>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> {{ session('success') }}</div>
@endif
@if(session('error'))
<div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation"></i> {{ session('error') }}</div>
@endif

<div class="card">
    <div class="table-wrap">
        <table class="sc-table">
            <thead>
                <tr>
                    <th>IPD No.</th>
                    <th>Patient</th>
                    <th>Ward / Bed</th>
                    <th>Doctor</th>
                    <th>Admitted</th>
                    <th>Days</th>
                    <th>Diagnosis</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($admissions as $adm)
                <tr>
                    <td class="mono fw-600">{{ $adm->ipd_no }}</td>
                    <td>
                        <div class="fw-600">{{ $adm->patient->full_name }}</div>
                        <div class="text-small text-muted mono">{{ $adm->patient->jhc_id }}</div>
                    </td>
                    <td>
                        <div>{{ $adm->ward->name }}</div>
                        <div class="text-small text-muted">Bed {{ $adm->bed->bed_number }}</div>
                    </td>
                    <td>{{ $adm->admittingDoctor->full_name }}</td>
                    <td>{{ $adm->admission_date->format('d M Y') }}<br>
                        <span class="text-small text-muted">{{ $adm->admission_date->format('h:i A') }}</span></td>
                    <td class="text-center">
                        <span class="badge badge-navy">{{ $adm->days_admitted }}d</span>
                    </td>
                    <td style="max-width:180px;white-space:normal">
                        {{ Str::limit($adm->provisional_diagnosis, 60, '…') ?: '—' }}
                    </td>
                    <td>
                        <span class="badge badge-{{ $adm->status_color }}">{{ $adm->status_label }}</span>
                    </td>
                    <td>
                        <div style="display:flex;gap:6px">
                            <a href="{{ route('admissions.show', $adm) }}" class="btn btn-sm btn-outline">View</a>
                            @if($adm->status === 'admitted')
                            <a href="{{ route('admissions.discharge', $adm) }}" class="btn btn-sm btn-amber">Discharge</a>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="9" style="padding:0">
                    <x-empty-state
                        icon="fa-bed-pulse"
                        title="No admissions found"
                        message="Try a different filter — or admit a new patient." />
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($admissions->hasPages())
    <div class="card-footer">{{ $admissions->withQueryString()->links() }}</div>
    @endif
</div>
@endsection
