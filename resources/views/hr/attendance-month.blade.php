@extends('layouts.app')
@section('title','Attendance Month')
@section('page-title','Attendance Month')
@section('content')

@php
    $today    = today();
    $prevStr  = $cursor->copy()->subMonthNoOverflow()->format('Y-m');
    $nextStr  = $cursor->copy()->addMonthNoOverflow()->format('Y-m');
    $isFuture = $cursor->copy()->endOfMonth()->gt($today);
@endphp

<div class="page-header">
    <div class="page-header-left">
        <h1>Attendance — {{ $cursor->format('F Y') }}</h1>
        <div class="breadcrumb">
            {{ $start->format('d M') }} → {{ $end->format('d M Y') }}
            &nbsp;·&nbsp; {{ $daysInMonth }} days &nbsp;·&nbsp; {{ $people->count() }} active people
        </div>
    </div>
    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
        <a href="{{ route('hr.attendance.month', ['month'=>$prevStr]) }}" class="btn btn-outline btn-sm" title="Previous month">
            <i class="fa-solid fa-chevron-left"></i>
        </a>
        <form method="GET" style="margin:0;display:flex;gap:6px;align-items:center">
            <input type="month" name="month" class="form-control form-control-sm" value="{{ $cursor->format('Y-m') }}" max="{{ $today->format('Y-m') }}" style="width:170px" onchange="this.form.submit()">
        </form>
        <a href="{{ route('hr.attendance.month', ['month'=>$nextStr]) }}" class="btn btn-outline btn-sm {{ $isFuture ? 'disabled' : '' }}" title="Next month" @if($isFuture) onclick="return false" style="pointer-events:none;opacity:.4" @endif>
            <i class="fa-solid fa-chevron-right"></i>
        </a>
        <a href="{{ route('hr.attendance.month', ['month'=>$today->format('Y-m')]) }}" class="btn btn-outline btn-sm">This month</a>
        <a href="{{ route('hr.attendance.index') }}" class="btn btn-outline btn-sm"><i class="fa-solid fa-calendar-day"></i> Daily</a>
        <a href="{{ route('hr.attendance.range') }}" class="btn btn-outline btn-sm"><i class="fa-solid fa-calendar-week"></i> Range</a>
    </div>
</div>

{{-- Legend --}}
<div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:12px;font-size:12px;align-items:center">
    <span class="text-muted">Legend:</span>
    @foreach($codes as $key => [$code, $color, $bg])
    <span style="display:inline-flex;align-items:center;gap:6px">
        <span style="display:inline-block;min-width:22px;text-align:center;padding:2px 6px;border-radius:4px;background:{{ $bg }};color:{{ $color }};font-weight:700;font-size:11px">{{ $code }}</span>
        <span class="text-muted">{{ $statuses[$key]['label'] }}</span>
    </span>
    @endforeach
</div>

@if($people->isEmpty())
<div class="card"><div class="card-body" style="text-align:center;padding:40px;color:var(--sc-muted)">
    <i class="fa-solid fa-users-slash" style="font-size:24px;display:block;margin-bottom:8px;color:#cbd5e1"></i>
    No active staff or doctors yet.
</div></div>
@else
<div class="card">
    <div class="card-header">
        <i class="fa-solid fa-calendar-days"></i> {{ $cursor->format('F Y') }}
    </div>
    <div class="table-responsive" style="overflow-x:auto">
        <table class="sc-table table" style="margin-bottom:0;min-width:max-content;font-size:12px">
            <thead>
            <tr>
                <th style="position:sticky;left:0;background:#f8fafc;z-index:2;min-width:200px;border-right:2px solid var(--sc-border)">Person</th>
                @for($d = 1; $d <= $daysInMonth; $d++)
                    @php
                        $cell    = $start->copy()->day($d);
                        $isWknd  = $cell->isWeekend();
                        $isToday = $cell->isSameDay($today);
                        $isFutD  = $cell->gt($today);
                    @endphp
                    <th style="text-align:center;min-width:34px;padding:6px 4px;
                        {{ $isToday ? 'background:#fef3c7;border-bottom:2px solid #f59e0b;' : ($isWknd ? 'background:#f1f5f9;' : '') }}
                        {{ $isFutD ? 'color:#cbd5e1;' : '' }}">
                        <div style="font-weight:700">{{ $d }}</div>
                        <div style="font-size:9px;font-weight:500;color:{{ $isToday ? '#92400e' : ($isWknd ? '#64748b' : 'var(--sc-muted)') }};text-transform:uppercase">
                            {{ $cell->format('D')[0] }}
                        </div>
                    </th>
                @endfor
                <th style="text-align:center;background:#f0fdf4;min-width:64px;border-left:2px solid var(--sc-border)">Worked</th>
                <th style="text-align:center;background:#fef2f2;min-width:64px">Missed</th>
            </tr>
            </thead>
            <tbody>
            @foreach($people as $p)
            @php $row = $grid[$p->key] ?? []; $t = $totals[$p->key]; @endphp
            <tr>
                <td style="position:sticky;left:0;background:#ffffff;z-index:1;border-right:2px solid var(--sc-border);min-width:200px">
                    <div class="fw-600">
                        {{ $p->name }}
                        @if($p->kind === 'doctor')
                            <span class="badge" style="background:#e0f2fe;color:#0369a1;font-size:10px;padding:1px 6px;margin-left:4px">Doctor</span>
                        @endif
                    </div>
                    <div class="text-small text-muted">{{ $p->designation }} · <span class="mono">{{ $p->code }}</span></div>
                </td>
                @for($d = 1; $d <= $daysInMonth; $d++)
                    @php
                        $st      = $row[$d] ?? null;
                        $cell    = $start->copy()->day($d);
                        $isFutD  = $cell->gt($today);
                        $info    = $st ? ($codes[$st] ?? null) : null;
                    @endphp
                    <td style="text-align:center;padding:4px 2px;min-width:34px;{{ $cell->isWeekend() ? 'background:#f8fafc;' : '' }}">
                        @if($info)
                            <span title="{{ $statuses[$st]['label'] }} — {{ $cell->format('D, d M') }}"
                                  style="display:inline-block;min-width:24px;padding:3px 5px;border-radius:4px;background:{{ $info[2] }};color:{{ $info[1] }};font-weight:700;font-size:11px">
                                {{ $info[0] }}
                            </span>
                        @elseif($isFutD)
                            <span style="color:#e2e8f0">·</span>
                        @else
                            <span class="text-muted" title="Not marked">—</span>
                        @endif
                    </td>
                @endfor
                <td style="text-align:center;background:#f0fdf4;color:#166534;border-left:2px solid var(--sc-border)" class="fw-600">
                    {{ rtrim(rtrim(number_format($t['present_days'], 1, '.', ''), '0'), '.') }}
                </td>
                <td style="text-align:center;background:#fef2f2;color:#991b1b" class="fw-600">
                    {{ $t['absent_days'] }}
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <div style="padding:10px 16px;border-top:1px solid var(--sc-border);background:#f8fafc;font-size:12px;color:var(--sc-muted);line-height:1.6">
        <strong>Worked</strong> = Present + (½ × Half-Day).
        <strong>Missed</strong> = Absent + Unpaid Leave.
        Hover any cell to see the status and date. Weekends are shaded; today is highlighted.
    </div>
</div>
@endif

@endsection
