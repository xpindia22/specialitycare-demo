{{-- FILE: resources/views/appointments/show.blade.php --}}
@extends('layouts.app')
@section('title','Appointment')
@section('page-title','Appointment Details')
@section('content')
<div class="page-header">
    <div class="page-header-left">
        <h1>Appointment — <span class="mono">{{ $appointment->appointment_no }}</span></h1>
        <div class="breadcrumb"><a href="{{ route('appointments.index') }}">Appointments</a> › {{ $appointment->appointment_no }}</div>
    </div>
    @if($appointment->type === 'Teleconsult' && $appointment->meeting_url && !in_array($appointment->status, ['Cancelled','No-Show']))
        <a href="{{ $appointment->meeting_url }}" target="_blank" rel="noopener" class="btn btn-primary" title="Open Jitsi Meet in a new tab"><i class="fa-solid fa-video"></i> Join Call</a>
    @endif
    @if(in_array($appointment->status, ['Scheduled','Confirmed']))
        <a href="{{ route('appointments.edit', $appointment) }}" class="btn btn-outline"><i class="fa-solid fa-pen"></i> Edit</a>
        <form method="POST" action="{{ route('appointments.checkin', $appointment) }}" style="display:inline">@csrf<button class="btn btn-amber">Check In</button></form>
    @elseif($appointment->status === 'Checked-In')
        <a href="{{ route('appointments.start-encounter', $appointment) }}" class="btn btn-primary">Start Visit</a>
    @elseif($appointment->status === 'In-Progress')
        <a href="{{ route('encounters.show', $appointment->encounter) }}" class="btn btn-amber"><i class="fa-solid fa-stethoscope"></i> Continue Visit</a>
    @elseif($appointment->status === 'Completed' && $appointment->encounter)
        <a href="{{ route('encounters.show', $appointment->encounter) }}" class="btn btn-outline"><i class="fa-solid fa-stethoscope"></i> View / Edit Encounter</a>
    @endif
</div>

<div class="grid grid-2">
    <div class="card">
        <div class="card-header"><i class="fa-solid fa-calendar-check"></i> Appointment Info</div>
        <div class="card-body">
            @php $rows = [
                ['Appointment No.', $appointment->appointment_no],
                ['Date', $appointment->appointment_date->format('D, d M Y')],
                ['Time', \Carbon\Carbon::parse($appointment->appointment_time)->format('h:i A')],
                ['Type', $appointment->type],
                ['Status', $appointment->status],
                ['Reason', $appointment->reason ?: '—'],
                ['Notes', $appointment->notes ?: '—'],
            ]; @endphp
            @foreach($rows as [$k,$v])
            <div style="display:flex;padding:8px 0;border-bottom:1px solid var(--sc-border);font-size:13px">
                <div style="width:45%;color:var(--sc-muted);font-weight:600">{{ $k }}</div>
                <div class="fw-600">{{ $v }}</div>
            </div>
            @endforeach
            @if($appointment->type === 'Teleconsult' && $appointment->meeting_url)
            <div style="display:flex;align-items:flex-start;padding:10px 0;border-bottom:1px solid var(--sc-border);font-size:13px;gap:8px">
                <div style="width:45%;color:var(--sc-muted);font-weight:600;flex-shrink:0"><i class="fa-solid fa-video" style="color:var(--sc-teal);margin-right:4px"></i> Meeting URL</div>
                <div style="flex:1;min-width:0;word-break:break-all">
                    <a href="{{ $appointment->meeting_url }}" target="_blank" rel="noopener" style="font-weight:600;color:var(--sc-teal);text-decoration:none">{{ $appointment->meeting_url }}</a>
                    <button type="button" onclick="navigator.clipboard.writeText('{{ $appointment->meeting_url }}').then(()=>this.textContent='✓ copied')" class="btn btn-sm btn-outline" style="margin-left:6px;padding:2px 8px;font-size:11px">copy</button>
                </div>
            </div>
            @endif
            @if($appointment->encounter)
            <div style="display:flex;align-items:center;padding:10px 0;font-size:13px">
                <div style="width:45%;color:var(--sc-muted);font-weight:600">Encounter</div>
                <a href="{{ route('encounters.show', $appointment->encounter) }}"
                   style="display:inline-flex;align-items:center;gap:6px;font-weight:600;color:var(--sc-teal);text-decoration:none">
                    <i class="fa-solid fa-stethoscope"></i>
                    {{ $appointment->encounter->encounter_no }}
                    <i class="fa-solid fa-arrow-up-right-from-square" style="font-size:10px;opacity:.7"></i>
                </a>
            </div>
            @endif
        </div>
        @if(in_array($appointment->status, ['Scheduled','Confirmed']))
        <div class="card-footer">
            <form method="POST" action="{{ route('appointments.cancel', $appointment) }}" onsubmit="return confirm('Cancel this appointment?')">
                @csrf
                <div class="form-group" style="margin-bottom:8px">
                    <input type="text" name="cancel_reason" class="form-control" placeholder="Reason for cancellation..." required>
                </div>
                <button type="submit" class="btn btn-danger btn-sm"><i class="fa-solid fa-ban"></i> Cancel Appointment</button>
            </form>
        </div>
        @endif
    </div>

    <div class="card">
        <div class="card-header"><i class="fa-solid fa-user-doctor"></i> Doctor & Patient</div>
        <div class="card-body">
            <div style="background:var(--sc-bg);border-radius:8px;padding:12px;margin-bottom:12px">
                <div class="text-muted text-small mb-1">Patient</div>
                <a href="{{ route('patients.show', $appointment->patient) }}" style="font-size:16px;font-weight:700;color:var(--sc-navy);text-decoration:none">{{ $appointment->patient->full_name }}</a>
                <div class="text-small text-muted mono">{{ $appointment->patient->jhc_id }}</div>
                <div class="text-small text-muted">{{ $appointment->patient->age }} / {{ $appointment->patient->gender }} · {{ $appointment->patient->phone }}</div>
            </div>
            <div style="background:var(--sc-bg);border-radius:8px;padding:12px">
                <div class="text-muted text-small mb-1">Doctor</div>
                <div style="font-size:15px;font-weight:700;color:var(--sc-navy)">{{ $appointment->doctor->full_name }}</div>
                <div class="text-small text-muted">{{ $appointment->doctor->specialization }}</div>
                <div class="text-small" style="color:var(--sc-teal)">₹{{ $appointment->doctor->consultation_fee }} Consultation Fee</div>
            </div>
        </div>
    </div>
</div>
@endsection
