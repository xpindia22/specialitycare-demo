@extends('layouts.app')
@section('title','Attendance Range')
@section('page-title','Attendance Range')
@section('content')

<div class="page-header">
    <div class="page-header-left">
        <h1>Attendance — Date Range</h1>
        <div class="breadcrumb">
            {{ $from->format('d M Y') }} → {{ $to->format('d M Y') }}
            &nbsp;·&nbsp; {{ $totalDays }} day{{ $totalDays === 1 ? '' : 's' }}
        </div>
    </div>
    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
        <form method="GET" style="margin:0;display:flex;gap:6px;align-items:center;flex-wrap:wrap">
            <input type="date" name="from" class="form-control form-control-sm" value="{{ $from->toDateString() }}" max="{{ today()->toDateString() }}" style="width:150px">
            <span class="text-muted">→</span>
            <input type="date" name="to" class="form-control form-control-sm" value="{{ $to->toDateString() }}" max="{{ today()->toDateString() }}" style="width:150px">
            <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-magnifying-glass"></i> Apply</button>
        </form>
        <a href="{{ route('hr.attendance.index') }}" class="btn btn-outline btn-sm"><i class="fa-solid fa-calendar-day"></i> Daily</a>
        <a href="{{ route('hr.attendance.month') }}" class="btn btn-outline btn-sm"><i class="fa-solid fa-calendar-days"></i> Month</a>
    </div>
</div>

<div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px">
    <a href="{{ route('hr.attendance.range', ['from'=>today()->toDateString(),'to'=>today()->toDateString()]) }}" class="btn btn-outline btn-sm">Today</a>
    <a href="{{ route('hr.attendance.range', ['from'=>today()->subDay()->toDateString(),'to'=>today()->subDay()->toDateString()]) }}" class="btn btn-outline btn-sm">Yesterday</a>
    <a href="{{ route('hr.attendance.range', ['from'=>today()->subDays(6)->toDateString(),'to'=>today()->toDateString()]) }}" class="btn btn-outline btn-sm">Last 7 days</a>
    <a href="{{ route('hr.attendance.range', ['from'=>today()->subDays(29)->toDateString(),'to'=>today()->toDateString()]) }}" class="btn btn-outline btn-sm">Last 30 days</a>
    <a href="{{ route('hr.attendance.range', ['from'=>today()->startOfMonth()->toDateString(),'to'=>today()->toDateString()]) }}" class="btn btn-outline btn-sm">This month</a>
    <a href="{{ route('hr.attendance.range', ['from'=>today()->subMonthNoOverflow()->startOfMonth()->toDateString(),'to'=>today()->subMonthNoOverflow()->endOfMonth()->toDateString()]) }}" class="btn btn-outline btn-sm">Last month</a>
</div>

@if(empty($rows))
<div class="card"><div class="card-body" style="text-align:center;padding:40px;color:var(--sc-muted)">
    <i class="fa-solid fa-users-slash" style="font-size:24px;display:block;margin-bottom:8px;color:#cbd5e1"></i>
    No active staff to summarise.
</div></div>
@else
<div class="card">
    <div class="card-header">
        <i class="fa-solid fa-calendar-week"></i> Per-staff totals
        <div class="card-actions text-small text-muted">
            {{ count($rows) }} people · range covers {{ $totalDays }} day{{ $totalDays === 1 ? '' : 's' }}
        </div>
    </div>
    <div class="table-responsive">
        <table class="sc-table table" style="margin-bottom:0">
            <thead>
            <tr>
                <th style="width:24%">Person</th>
                <th style="text-align:center">Present</th>
                <th style="text-align:center">Half-Day</th>
                <th style="text-align:center">Absent</th>
                <th style="text-align:center">Paid Lv</th>
                <th style="text-align:center">Unpaid Lv</th>
                <th style="text-align:center">Week Off</th>
                <th style="text-align:center">Holiday</th>
                <th style="text-align:center">Unmarked</th>
                <th style="text-align:center;background:#f0fdf4">Days Worked</th>
                <th style="text-align:center;background:#fef2f2">Days Missed</th>
            </tr>
            </thead>
            <tbody>
            @foreach($rows as $row)
            @php $p = $row['person']; $b = $row['bucket']; @endphp
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
                <td style="text-align:center" class="fw-600" >{{ $b['present'] }}</td>
                <td style="text-align:center">{{ $b['half_day'] }}</td>
                <td style="text-align:center" class="fw-600" >{{ $b['absent'] }}</td>
                <td style="text-align:center">{{ $b['paid_leave'] }}</td>
                <td style="text-align:center">{{ $b['unpaid_leave'] }}</td>
                <td style="text-align:center">{{ $b['week_off'] }}</td>
                <td style="text-align:center">{{ $b['holiday'] }}</td>
                <td style="text-align:center" class="text-muted">{{ $row['unmarked'] }}</td>
                <td style="text-align:center;background:#f0fdf4;color:#166534" class="fw-600">
                    {{ rtrim(rtrim(number_format($row['present_days'], 1, '.', ''), '0'), '.') }}
                </td>
                <td style="text-align:center;background:#fef2f2;color:#991b1b" class="fw-600">
                    {{ $row['absent_days'] }}
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <div style="padding:10px 16px;border-top:1px solid var(--sc-border);background:#f8fafc;font-size:12px;color:var(--sc-muted);line-height:1.6">
        <strong>Days Worked</strong> = Present + (½ × Half-Day).
        <strong>Days Missed</strong> = Absent + Unpaid Leave.
        <strong>Unmarked</strong> = days in range with no attendance entry for that staff (e.g. before they joined, or simply not yet recorded).
    </div>
</div>
@endif

@endsection
