@extends('layouts.app')
@section('title','Attendance')
@section('page-title','Attendance')
@section('content')

<div class="page-header">
    <div class="page-header-left">
        <h1>Daily Attendance</h1>
        <div class="breadcrumb">{{ $date->format('l, d M Y') }} &nbsp;·&nbsp; {{ $people->count() }} active people</div>
    </div>
    <div style="display:flex;gap:8px;align-items:center">
        <form method="GET" style="margin:0;display:flex;gap:8px;align-items:center">
            <input type="date" name="date" class="form-control" value="{{ $date->toDateString() }}" max="{{ today()->toDateString() }}" style="width:160px" onchange="this.form.submit()">
        </form>
        <button type="button" class="btn btn-outline btn-sm" onclick="setDate(0)">Today</button>
        <button type="button" class="btn btn-outline btn-sm" onclick="setDate(-1)">Yesterday</button>
        <a href="{{ route('hr.attendance.range') }}" class="btn btn-outline btn-sm"><i class="fa-solid fa-calendar-week"></i> Range</a>
        <a href="{{ route('hr.attendance.month') }}" class="btn btn-outline btn-sm"><i class="fa-solid fa-calendar-days"></i> Month</a>
        <a href="{{ route('hr.adjustments.index') }}" class="btn btn-outline"><i class="fa-solid fa-coins"></i> Adjustments</a>
        <a href="{{ route('hr.payroll.index') }}" class="btn btn-outline"><i class="fa-solid fa-money-check-dollar"></i> Payroll</a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> {{ session('success') }}</div>
@endif

@if($people->isEmpty())
<div class="card"><div class="card-body" style="text-align:center;padding:40px;color:var(--sc-muted)">
    <i class="fa-solid fa-users-slash" style="font-size:24px;display:block;margin-bottom:8px;color:#cbd5e1"></i>
    No active staff or doctors yet. Add via <a href="{{ route('staff.index') }}" style="color:var(--sc-teal)">Administration → Staff</a>
    or <a href="{{ route('doctors.index') }}" style="color:var(--sc-teal)">Doctors</a>.
</div></div>
@else
<form method="POST" action="{{ route('hr.attendance.store') }}">
    @csrf
    <input type="hidden" name="date" value="{{ $date->toDateString() }}">

    {{-- KPI row — same .stat-card pattern as the payroll dashboard --}}
    @php
        $count = ['present'=>0,'absent'=>0,'leave'=>0,'off'=>0];
        foreach ($people as $p) {
            $r = $records->get($p->key);
            $st = $r->status ?? null;
            if (in_array($st, ['present','half_day'])) $count['present']++;
            elseif ($st === 'absent')                  $count['absent']++;
            elseif (in_array($st, ['paid_leave','unpaid_leave'])) $count['leave']++;
            elseif (in_array($st, ['week_off','holiday']))        $count['off']++;
        }
    @endphp
    <div class="grid grid-4 mb-4">
        <div class="stat-card">
            <div class="stat-icon green"><i class="fa-solid fa-circle-check"></i></div>
            <div>
                <div class="stat-value">{{ $count['present'] }}<span class="text-muted text-small fw-600"> / {{ $people->count() }}</span></div>
                <div class="stat-label">Present</div>
                <div class="stat-sub text-muted">Includes half-day</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon red"><i class="fa-solid fa-circle-xmark"></i></div>
            <div>
                <div class="stat-value">{{ $count['absent'] }}</div>
                <div class="stat-label">Absent</div>
                <div class="stat-sub text-muted">No-show today</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(139,92,246,.12);color:#7c3aed"><i class="fa-solid fa-plane-departure"></i></div>
            <div>
                <div class="stat-value">{{ $count['leave'] }}</div>
                <div class="stat-label">On Leave</div>
                <div class="stat-sub text-muted">Paid &amp; unpaid</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon navy"><i class="fa-solid fa-mug-hot"></i></div>
            <div>
                <div class="stat-value">{{ $count['off'] }}</div>
                <div class="stat-label">Off / Holiday</div>
                <div class="stat-sub text-muted">Week-off &amp; holidays</div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <i class="fa-solid fa-calendar-day"></i> {{ $date->format('l, d M Y') }}
            <div class="card-actions">
                <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-floppy-disk"></i> Save Attendance</button>
            </div>
        </div>
        <div class="table-responsive">
            <table class="sc-table table" style="margin-bottom:0">
                <thead>
                <tr>
                    <th style="width:34%">Person</th>
                    <th>Status</th>
                    <th style="width:120px">Check-In</th>
                    <th style="width:120px">Check-Out</th>
                    <th>Notes</th>
                </tr>
                </thead>
                <tbody>
                @foreach($people as $p)
                @php $r = $records->get($p->key); $st = $r->status ?? 'present'; @endphp
                <tr>
                    <td>
                        <div class="fw-600">
                            {{ $p->name }}
                            @if($p->kind === 'doctor')
                                <span class="badge" style="background:#e0f2fe;color:#0369a1;font-size:10px;padding:1px 6px;margin-left:4px">Doctor</span>
                            @endif
                        </div>
                        <div class="text-small text-muted">{{ $p->designation }} · <span class="mono">{{ $p->code }}</span></div>
                    </td>
                    <td>
                        <select name="rows[{{ $p->key }}][status]" class="form-control form-control-sm fw-600">
                            @foreach($statuses as $k => $meta)
                            <option value="{{ $k }}" {{ $st === $k ? 'selected' : '' }}>
                                {{ $meta['label'] }}
                            </option>
                            @endforeach
                        </select>
                    </td>
                    <td><input type="time" name="rows[{{ $p->key }}][check_in]"  value="{{ $r?->check_in ? \Carbon\Carbon::parse($r->check_in)->format('H:i') : '' }}" class="form-control form-control-sm"></td>
                    <td><input type="time" name="rows[{{ $p->key }}][check_out]" value="{{ $r?->check_out ? \Carbon\Carbon::parse($r->check_out)->format('H:i') : '' }}" class="form-control form-control-sm"></td>
                    <td><input type="text" name="rows[{{ $p->key }}][notes]" value="{{ $r->notes ?? '' }}" maxlength="255" class="form-control form-control-sm" placeholder="—"></td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div style="padding:12px 16px;text-align:right;border-top:1px solid var(--sc-border)">
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save Attendance</button>
        </div>
    </div>
</form>
@endif

@push('scripts')
<script>
function setDate(offset) {
    const d = new Date();
    d.setDate(d.getDate() + offset);
    const iso = d.toISOString().slice(0,10);
    const el = document.querySelector('input[name="date"]');
    el.value = iso;
    el.form.submit();
}
</script>
@endpush
@endsection
