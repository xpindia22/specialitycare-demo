{{-- FILE: resources/views/opd/index.blade.php — Reception Queue (read-only view) --}}
@extends('layouts.app')
@section('title','Reception Queue')
@section('page-title','Reception Queue')
@section('content')

<div class="page-header">
    <div class="page-header-left"><h1>Reception Queue</h1></div>
    @include('opd._toolbar', ['inline'=>true])
</div>

{{-- Stats bar --}}
<div class="opd-stats">
    <div class="opd-stat opd-stat-total">  <div class="opd-stat-num">{{ $stats['total'] }}</div>          <div class="opd-stat-lbl">Total Today</div></div>
    <div class="opd-stat opd-stat-waiting"><div class="opd-stat-num">{{ $stats['waiting'] }}</div>         <div class="opd-stat-lbl">Waiting</div></div>
    <div class="opd-stat opd-stat-active"> <div class="opd-stat-num">{{ $stats['in_consultation'] }}</div> <div class="opd-stat-lbl">In Consultation</div></div>
    <div class="opd-stat opd-stat-done">   <div class="opd-stat-num">{{ $stats['done'] }}</div>            <div class="opd-stat-lbl">Done</div></div>
    <div class="opd-stat opd-stat-skip">   <div class="opd-stat-num">{{ $stats['skipped'] }}</div>         <div class="opd-stat-lbl">Skipped</div></div>
    <span id="refresh-badge" style="margin-left:auto;font-size:11px;color:#999;white-space:nowrap"><i class="fa-solid fa-rotate" style="font-size:10px"></i> refreshing in <span id="refresh-countdown">45</span>s</span>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div style="display:flex;flex-direction:column;gap:16px">

    {{-- Today's Appointments (pending check-in) --}}
    @if($appointments->count())
    <div>
        <div class="card">
            <div class="card-header d-flex align-items-center gap-2" style="background:#eff6ff;border-bottom:1px solid #bfdbfe">
                <i class="fa-solid fa-calendar-check" style="color:#2563eb"></i>
                <strong style="color:#1e40af">Today's Appointments — {{ $appointments->count() }} pending check-in</strong>
            </div>
            <div class="table-wrap">
                <table class="sc-table">
                    <thead>
                        <tr>
                            <th style="width:10%">Time</th>
                            <th>Patient</th>
                            <th style="width:20%">Doctor</th>
                            <th style="width:14%">Type</th>
                            <th>Complaint</th>
                            <th style="width:10%"></th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($appointments as $apt)
                    <tr>
                        <td class="mono fw-600" style="color:#1e40af">
                            {{ $apt->appointment_time ? \Carbon\Carbon::parse($apt->appointment_time)->format('h:i A') : '—' }}
                        </td>
                        <td>
                            <div class="fw-600">{{ $apt->patient->first_name }} {{ $apt->patient->last_name }}</div>
                            <div style="font-size:11px;color:#666">{{ $apt->patient->jhc_id }}</div>
                        </td>
                        <td style="font-size:12px">{{ $apt->doctor ? 'Dr. '.$apt->doctor->first_name.' '.$apt->doctor->last_name : '—' }}
                            @if($apt->department)<div style="font-size:11px;color:{{ $apt->department->color ?: '#666' }}"><i class="fa-solid {{ $apt->department->icon ?: 'fa-hospital' }}"></i> {{ $apt->department->name }}</div>@endif</td>
                        <td><span class="badge badge-grey">{{ $apt->type }}</span></td>
                        <td style="font-size:12px;color:#555">{{ $apt->chief_complaint ?: '—' }}</td>
                        <td>
                            <form method="POST" action="{{ route('appointments.checkin', $apt) }}">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-primary">
                                    <i class="fa-solid fa-right-to-bracket me-1"></i>Check In
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    {{-- Today's Queue --}}
    <div>
        <div class="card">
            <div class="card-header d-flex align-items-center gap-2">
                <strong>Today's Queue — {{ today()->format('d M Y') }}</strong>
                <form method="GET" action="{{ route('opd.index') }}" class="ms-auto d-flex gap-2 align-items-center">
                    <select name="department_id" class="form-control form-control-sm" onchange="this.form.submit()" style="min-width:160px">
                        <option value="">All Specialities</option>
                        @foreach($departments as $dep)
                            <option value="{{ $dep->id }}" {{ $deptId == $dep->id ? 'selected':'' }}>{{ $dep->name }}</option>
                        @endforeach
                    </select>
                    <select name="doctor_id" class="form-control form-control-sm" onchange="this.form.submit()" style="min-width:160px">
                        <option value="">All Doctors</option>
                        @foreach($doctors as $d)
                            <option value="{{ $d->id }}" {{ $doctorId == $d->id ? 'selected':'' }}>Dr. {{ $d->first_name }} {{ $d->last_name }}</option>
                        @endforeach
                    </select>
                </form>
            </div>
            <div class="table-wrap">
                <table class="sc-table">
                    <thead>
                        <tr>
                            <th style="width:8%">Token</th>
                            <th>Patient</th>
                            <th style="width:18%">Doctor</th>
                            <th style="width:13%">Purpose</th>
                            <th style="width:10%">Priority</th>
                            <th style="width:12%">Status</th>
                            <th style="width:10%">Issued</th>
                            <th style="width:9%">Wait</th>
                            <th style="width:8%"></th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($queue as $t)
                    <tr class="{{ $t->priority === 'urgent' ? 'opd-urgent-row' : '' }}">
                        <td class="mono fw-600 text-center" style="font-size:15px">{{ $t->display }}</td>
                        <td>
                            <div class="fw-600">{{ $t->patient->first_name }} {{ $t->patient->last_name }}</div>
                            <div style="font-size:11px;color:#666">{{ $t->patient->jhc_id }}</div>
                        </td>
                        <td style="font-size:12px">{{ $t->doctor ? 'Dr. '.$t->doctor->first_name.' '.$t->doctor->last_name : '—' }}
                            @if($t->department)<div style="font-size:11px;color:{{ $t->department->color ?: '#666' }}"><i class="fa-solid {{ $t->department->icon ?: 'fa-hospital' }}"></i> {{ $t->department->name }}</div>@endif</td>
                        <td style="font-size:12px">{{ $t->purpose ?: '—' }}</td>
                        <td>
                            @if($t->priority === 'urgent')
                                <span class="badge badge-red"><i class="fa-solid fa-circle-exclamation me-1"></i>Urgent</span>
                            @else
                                <span class="badge badge-grey">Normal</span>
                            @endif
                        </td>
                        <td><span class="badge opd-badge-{{ $t->status }}">{{ ucwords(str_replace('_',' ',$t->status)) }}</span></td>
                        <td style="font-size:11px;color:#666">{{ $t->created_at->format('h:i A') }}</td>
                        <td>
                            @if(in_array($t->status, ['waiting','called']))
                            <span class="opd-wait-badge" data-since="{{ $t->created_at->toIso8601String() }}">—</span>
                            @else
                            <span style="font-size:11px;color:#9ca3af">—</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('opd.slip', $t) }}" class="btn btn-sm btn-outline" target="_blank" title="Print Token Slip">
                                <i class="fa-solid fa-print"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center text-muted" style="padding:40px">No tokens issued today.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.opd-stats { display:flex; gap:10px; align-items:center; margin-bottom:16px; flex-wrap:wrap; }
.opd-stat { background:#fff; border:1px solid #ddd; border-radius:6px; padding:10px 18px; text-align:center; min-width:90px; }
.opd-stat-num { font-size:26px; font-weight:800; line-height:1; }
.opd-stat-lbl { font-size:10.5px; color:#666; margin-top:2px; }
.opd-stat-total .opd-stat-num   { color:#333; }
.opd-stat-waiting .opd-stat-num { color:#d97706; }
.opd-stat-active .opd-stat-num  { color:#2563eb; }
.opd-stat-done .opd-stat-num    { color:#16a34a; }
.opd-stat-skip .opd-stat-num    { color:#9ca3af; }

.opd-queue-panel .card-header { background:#f8f9fa; padding:10px 16px; border-bottom:1px solid #e0e0e0; }
.opd-urgent-row { background:#fff7f7 !important; }

.opd-badge-waiting         { background:#fef3c7; color:#92400e; }
.opd-badge-called          { background:#dbeafe; color:#1e40af; }
.opd-badge-in_consultation { background:#dcfce7; color:#14532d; }
.opd-badge-done            { background:#f3f4f6; color:#374151; }
.opd-badge-skipped         { background:#f3f4f6; color:#9ca3af; }
.badge-grey { background:#f3f4f6; color:#555; }
</style>
@endpush

@push('scripts')
<script>
// ── OPD WAIT TIME ──
(function() {
    function fmtWait(mins) {
        if (mins < 60) return mins + 'm';
        return Math.floor(mins/60) + 'h ' + (mins%60) + 'm';
    }
    function updateWaits() {
        const now = Date.now();
        document.querySelectorAll('.opd-wait-badge').forEach(el => {
            const since = new Date(el.dataset.since).getTime();
            const mins  = Math.floor((now - since) / 60000);
            el.textContent = fmtWait(mins);
            el.style.fontWeight = '600';
            el.style.fontSize   = '12px';
            if (mins >= 30) {
                el.style.color      = 'var(--sc-red)';
                el.style.background = 'rgba(232,76,76,.1)';
            } else if (mins >= 15) {
                el.style.color      = 'var(--sc-amber)';
                el.style.background = 'rgba(245,166,35,.1)';
            } else {
                el.style.color      = 'var(--sc-green)';
                el.style.background = 'rgba(39,196,122,.1)';
            }
            el.style.padding      = '2px 7px';
            el.style.borderRadius = '10px';
        });
    }
    updateWaits();
    setInterval(updateWaits, 30000);
})();
</script>
<script>
(function() {
    const INTERVAL = 45;
    let secs = INTERVAL;
    const countEl = document.getElementById('refresh-countdown');
    setInterval(function() {
        const focused = document.activeElement;
        const isTyping = focused && (focused.tagName === 'INPUT' || focused.tagName === 'TEXTAREA' || focused.tagName === 'SELECT');
        if (isTyping) { secs = INTERVAL; if (countEl) countEl.textContent = secs; return; }
        secs--;
        if (countEl) countEl.textContent = secs;
        if (secs <= 0) location.reload();
    }, 1000);
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) { secs = INTERVAL; if (countEl) countEl.textContent = secs; }
    });
})();
</script>
@endpush

@endsection
