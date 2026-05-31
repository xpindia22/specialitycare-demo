{{-- FILE: resources/views/appointments/index.blade.php --}}
@extends('layouts.app')
@section('title','Appointments')
@section('page-title','Appointments')
@push('styles')
<style>
    /* Soothing per-day row colours */
    .sc-table tbody tr.appt-today    { background:#e9f9ef; }              /* today — green */
    .sc-table tbody tr.appt-today:hover    { background:#d9f4e3; }
    .sc-table tbody tr.appt-tomorrow { background:#e8f1fc; }              /* tomorrow — blue */
    .sc-table tbody tr.appt-tomorrow:hover { background:#d6e7f9; }
    .sc-table tbody tr.appt-dayafter { background:#f1ecfb; }              /* day after — lavender */
    .sc-table tbody tr.appt-dayafter:hover { background:#e6dcf7; }
    .sc-table tbody tr.appt-future   { background:#fdf6e3; }              /* later — soft yellow */
    .sc-table tbody tr.appt-future:hover   { background:#f7edcf; }
    .sc-table tbody tr.appt-past     { background:#f3f4f6; color:#6b7280; } /* past — grey */
    .sc-table tbody tr.appt-past:hover     { background:#e8eaed; }
    .appt-legend { display:flex; gap:8px; flex-wrap:wrap; align-items:center; margin-bottom:12px; font-size:12px; }
    .appt-legend .key { display:inline-flex; align-items:center; gap:6px; padding:4px 10px; border:1px solid var(--sc-border); border-radius:14px; text-decoration:none; color:var(--sc-muted); cursor:pointer; }
    .appt-legend .key:hover { background:var(--sc-bg); }
    .appt-legend .key.active { border-color:var(--sc-teal); color:var(--sc-teal); font-weight:600; box-shadow:0 0 0 1px var(--sc-teal) inset; }
    .appt-legend .sw { width:14px; height:14px; border-radius:3px; border:1px solid rgba(0,0,0,.08); display:inline-block; }
</style>
@endpush

@section('content')
<div class="page-header">
    <div class="page-header-left"><h1>Appointments</h1><div class="breadcrumb">Queue & Schedule</div></div>
    <a href="{{ route('appointments.create') }}" class="btn btn-primary"><i class="fa-solid fa-calendar-plus"></i> Book Appointment</a>
</div>

@php $range = $range ?? 'today'; $isCustom = request('date') || request('search') || request('day'); @endphp
<div style="display:flex;gap:6px;margin-bottom:14px">
    <a href="{{ route('appointments.index', ['range'=>'today']) }}" class="btn btn-sm {{ !$isCustom && $range==='today' ? 'btn-primary' : 'btn-outline' }}"><i class="fa-solid fa-calendar-day"></i> Today</a>
    <a href="{{ route('appointments.index', ['range'=>'upcoming']) }}" class="btn btn-sm {{ !$isCustom && $range==='upcoming' ? 'btn-primary' : 'btn-outline' }}"><i class="fa-solid fa-calendar-plus"></i> Upcoming</a>
    <a href="{{ route('appointments.index', ['range'=>'all']) }}" class="btn btn-sm {{ !$isCustom && $range==='all' ? 'btn-primary' : 'btn-outline' }}"><i class="fa-solid fa-calendar"></i> All</a>
</div>

@php $day = $day ?? null; @endphp
<div class="appt-legend">
    <a href="{{ route('appointments.index', ['day'=>'today']) }}"    class="key {{ $day==='today'?'active':'' }}"><span class="sw" style="background:#e9f9ef"></span> Today</a>
    <a href="{{ route('appointments.index', ['day'=>'tomorrow']) }}" class="key {{ $day==='tomorrow'?'active':'' }}"><span class="sw" style="background:#e8f1fc"></span> Tomorrow</a>
    <a href="{{ route('appointments.index', ['day'=>'dayafter']) }}" class="key {{ $day==='dayafter'?'active':'' }}"><span class="sw" style="background:#f1ecfb"></span> Day after</a>
    <a href="{{ route('appointments.index', ['day'=>'later']) }}"    class="key {{ $day==='later'?'active':'' }}"><span class="sw" style="background:#fdf6e3"></span> Later</a>
    <a href="{{ route('appointments.index', ['day'=>'past']) }}"     class="key {{ $day==='past'?'active':'' }}"><span class="sw" style="background:#f3f4f6"></span> Past</a>
</div>

<div class="card mb-3">
    <div class="card-body" style="padding:12px 20px">
        <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end">
            <div><input type="text" name="search" class="form-control" placeholder="Search patient, JHC ID, phone..." value="{{ request('search') }}" style="width:240px"></div>
            <div><input type="date" name="date" class="form-control" value="{{ request('date', !request('search') ? today()->toDateString() : '') }}" style="width:170px"></div>
            <div>
                <select name="doctor_id" class="form-control" style="width:200px">
                    <option value="">All Doctors</option>
                    @foreach($doctors as $d)
                        <option value="{{ $d->id }}" {{ request('doctor_id')==$d->id ? 'selected':'' }}>{{ $d->full_name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <select name="status" class="form-control" style="width:150px">
                    <option value="">All Status</option>
                    @foreach(['Scheduled','Confirmed','Checked-In','In-Progress','Completed','Cancelled','Rescheduled'] as $s)
                        <option value="{{ $s }}" {{ request('status')==$s ? 'selected':'' }}>{{ $s }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-primary btn-sm">Filter</button>
            @if(request()->hasAny(['date','doctor_id','status','search']))
                <a href="{{ route('appointments.index') }}" class="btn btn-outline btn-sm">Reset</a>
            @endif
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <i class="fa-solid fa-calendar-check"></i>
        @if(request('date'))
            Appointments — {{ \Carbon\Carbon::parse(request('date'))->format('D, d M Y') }}
        @elseif(request('search'))
            Search results
        @elseif($day)
            {{ ['today'=>'Today','tomorrow'=>"Tomorrow",'dayafter'=>'Day after tomorrow','later'=>'Later (3+ days)','past'=>'Past'][$day] ?? ucfirst($day) }} appointments
        @elseif($range === 'upcoming')
            Upcoming Appointments (today onward)
        @elseif($range === 'all')
            All Appointments
        @else
            Appointments — {{ today()->format('D, d M Y') }}
        @endif
        <span class="badge badge-teal" style="margin-left:8px">{{ $appointments->total() }}</span>
    </div>
    <div class="table-wrap">
        <table class="sc-table">
            <thead>
                <tr><th>No.</th><th>Patient</th><th>Doctor</th><th>Time</th><th>Type</th><th>Status</th><th>Actions</th></tr>
            </thead>
            <tbody>
            @forelse($appointments as $a)
            @php
                $ad = $a->appointment_date->copy()->startOfDay();
                $t  = today();
                $dayClass = $ad->equalTo($t)                       ? 'appt-today'
                          : ($ad->equalTo($t->copy()->addDay())    ? 'appt-tomorrow'
                          : ($ad->equalTo($t->copy()->addDays(2))  ? 'appt-dayafter'
                          : ($ad->lt($t)                           ? 'appt-past' : 'appt-future')));
            @endphp
            <tr class="{{ $dayClass }}">
                <td class="mono fw-600">{{ $a->appointment_no }}</td>
                <td>
                    <a href="{{ route('patients.show', $a->patient) }}" style="font-weight:600;color:var(--sc-navy);text-decoration:none">{{ $a->patient->full_name }}</a>
                    <div class="text-small text-muted mono">{{ $a->patient->jhc_id }}</div>
                </td>
                <td>{{ $a->doctor->full_name }}<div class="text-small text-muted">{{ $a->doctor->specialization }}</div></td>
                <td>
                    <div class="mono fw-600">{{ \Carbon\Carbon::parse($a->appointment_time)->format('h:i A') }}</div>
                    <div class="text-small text-muted">{{ $a->appointment_date->format('d M Y') }}</div>
                </td>
                <td>
                    <span class="badge badge-navy">{{ $a->type }}</span>
                    @if($a->booking_source === 'public_online')
                    <span class="badge" style="background:#e0f2fe;color:#075985;border:1px solid #7dd3fc;margin-top:3px" title="Booked via the public /book page — call patient to confirm"><i class="fa-solid fa-globe"></i> Online</span>
                    @endif
                </td>
                <td>
                    @php $bc=['Scheduled'=>'badge-gray','Confirmed'=>'badge-teal','Checked-In'=>'badge-amber','In-Progress'=>'badge-purple','Completed'=>'badge-green','Cancelled'=>'badge-red','Rescheduled'=>'badge-navy','No-Show'=>'badge-red']; @endphp
                    <span class="badge {{ $bc[$a->status] ?? 'badge-gray' }}">{{ $a->status }}</span>
                </td>
                <td>
                    <div style="display:flex;gap:5px">
                        @if($a->type === 'Teleconsult' && $a->meeting_url && !in_array($a->status, ['Cancelled','No-Show','Completed']))
                            <a href="{{ $a->meeting_url }}" target="_blank" rel="noopener" class="btn btn-sm btn-primary" title="Join Jitsi Meet call"><i class="fa-solid fa-video"></i> Join</a>
                        @endif
                        @if(in_array($a->status, ['Scheduled','Confirmed']))
                            <form method="POST" action="{{ route('appointments.checkin', $a) }}" style="display:inline">@csrf<button class="btn btn-sm btn-amber" title="Check In">Check In</button></form>
                            @if($a->appointment_date->lte(today()))
                            <form method="POST" action="{{ route('appointments.no-show', $a) }}" style="display:inline" onsubmit="return confirm('Mark as No-Show?')">@csrf<button class="btn btn-sm btn-outline" style="color:var(--sc-red);border-color:var(--sc-red)" title="No Show"><i class="fa-solid fa-user-slash"></i></button></form>
                            @endif
                        @elseif($a->status === 'Checked-In')
                            <a href="{{ route('appointments.start-encounter', $a) }}" class="btn btn-sm btn-primary">Start Visit</a>
                            <form method="POST" action="{{ route('appointments.no-show', $a) }}" style="display:inline" onsubmit="return confirm('Mark as No-Show?')">@csrf<button class="btn btn-sm btn-outline" style="color:var(--sc-red);border-color:var(--sc-red)" title="No Show"><i class="fa-solid fa-user-slash"></i></button></form>
                        @elseif($a->status === 'In-Progress')
                            <a href="{{ route('encounters.show', $a->encounter) }}" class="btn btn-sm btn-amber">Continue</a>
                        @endif
                        @if(in_array($a->status, ['Scheduled','Confirmed','Checked-In']))
                            <button type="button" class="btn btn-sm btn-outline" title="Email reminder to patient (CC clinic)" onclick="scRemind({{ $a->id }}, 'email', this)"><i class="fa-solid fa-envelope"></i></button>
                            <button type="button" class="btn btn-sm btn-outline" style="color:#25D366;border-color:#25D366" title="WhatsApp reminder to patient" onclick="scRemind({{ $a->id }}, 'whatsapp', this)"><i class="fa-brands fa-whatsapp"></i></button>
                        @endif
                        <a href="{{ route('appointments.show', $a) }}" class="btn btn-sm btn-outline"><i class="fa-solid fa-eye"></i></a>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="7" style="padding:0">
                <x-empty-state
                    icon="fa-calendar-plus"
                    title="No appointments scheduled"
                    message="Pick a different date or schedule a new appointment for this date."
                    action="New appointment"
                    :actionUrl="route('appointments.create')" />
            </td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $appointments->links() }}</div>
</div>
@endsection

@push('scripts')
<script>
function scRemind(id, channel, btn) {
    var base = '{{ url("/appointments") }}/' + id;
    var url = channel === 'email' ? base + '/remind-email' : base + '/remind-whatsapp';
    var orig = btn.innerHTML;
    btn.disabled = true; btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
    $.post(url, {}, function (res) {
        btn.innerHTML = '<i class="fa-solid fa-check"></i>';
        btn.style.color = '#15803d'; btn.style.borderColor = '#86efac';
        btn.title = res.message || 'Reminder sent';
    }).fail(function (xhr) {
        btn.disabled = false; btn.innerHTML = orig;
        alert((xhr.responseJSON && xhr.responseJSON.message) || 'Could not send the reminder.');
    });
}
</script>
@endpush
