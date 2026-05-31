@extends('public.layout')
@section('title', 'Appointment Requested')

@push('head')
<style>
.confirm-card { max-width:560px; margin:30px auto; text-align:center; }
.confirm-icon { width:80px; height:80px; border-radius:50%; background:rgba(39,196,122,0.12); color:var(--c-green); display:flex; align-items:center; justify-content:center; font-size:36px; margin:0 auto 18px; }
.confirm-title { font-size:24px; font-weight:700; margin-bottom:8px; }
.confirm-sub { color:var(--c-muted); margin-bottom:22px; }
.confirm-details { background:#f8fafc; border:1px solid var(--c-border); border-radius:8px; padding:18px; margin-bottom:18px; text-align:left; font-size:14px; }
.confirm-details .row { display:flex; justify-content:space-between; padding:6px 0; border-bottom:1px dashed var(--c-border); }
.confirm-details .row:last-child { border-bottom:none; }
.confirm-details .label { color:var(--c-muted); }
.confirm-details .val { font-weight:600; text-align:right; }
.appt-no { display:inline-block; padding:6px 14px; background:var(--c-navy); color:#fff; border-radius:6px; font-family:'DM Mono', monospace; font-size:13px; letter-spacing:0.5px; margin-top:8px; }
.next-steps { text-align:left; background:#fffbeb; border:1px solid #fde68a; border-radius:8px; padding:14px 18px; font-size:13px; color:#78350f; }
.next-steps strong { color:#78350f; }
.next-steps ul { margin:6px 0 0 18px; padding:0; }
</style>
@endpush

@section('content')
<div class="card confirm-card">
    <div class="card-body">
        <div class="confirm-icon"><i class="fa-solid fa-check"></i></div>
        <div class="confirm-title">Appointment requested!</div>
        <div class="confirm-sub">We'll call <strong>{{ $appointment->public_phone }}</strong> shortly to confirm your slot.</div>

        <div class="appt-no">{{ $appointment->appointment_no }}</div>

        <div class="confirm-details" style="margin-top:18px">
            <div class="row"><span class="label">Patient</span><span class="val">{{ $appointment->public_name }}</span></div>
            <div class="row"><span class="label">Doctor</span><span class="val">Dr. {{ $appointment->doctor->first_name }} {{ $appointment->doctor->last_name }}</span></div>
            <div class="row"><span class="label">Date</span><span class="val">{{ \Illuminate\Support\Carbon::parse($appointment->appointment_date)->format('D, d M Y') }}</span></div>
            @if($appointment->appointment_time)
            <div class="row"><span class="label">Preferred time</span><span class="val">{{ \Illuminate\Support\Carbon::parse($appointment->appointment_time)->format('h:i A') }}</span></div>
            @endif
            @if($appointment->chief_complaint)
            <div class="row"><span class="label">Reason</span><span class="val">{{ $appointment->chief_complaint }}</span></div>
            @endif
        </div>

        <div class="next-steps">
            <strong>What's next?</strong>
            <ul>
                <li>Our front desk will call you to confirm the exact time.</li>
                <li>Please reach 10 minutes before your slot for registration.</li>
                <li>Carry any old reports / prescriptions if relevant.</li>
                @if(!empty($clinic['phone']))
                <li>Need to change anything? Call <a href="tel:{{ $clinic['phone'] }}" style="color:var(--c-teal); font-weight:600">{{ $clinic['phone'] }}</a>.</li>
                @endif
            </ul>
        </div>

        <a href="{{ route('public.book.show') }}" class="btn btn-primary" style="margin-top:22px">
            <i class="fa-solid fa-arrow-left"></i> Book Another
        </a>
    </div>
</div>
@endsection
