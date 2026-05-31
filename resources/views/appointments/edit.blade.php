{{-- FILE: resources/views/appointments/edit.blade.php --}}
@extends('layouts.app')
@section('title','Edit Appointment')
@section('page-title','Edit Appointment')
@section('content')

<div class="page-header">
    <div class="page-header-left">
        <h1>Edit Appointment</h1>
        <div class="breadcrumb"><a href="{{ route('appointments.index') }}">Appointments</a> › <a href="{{ route('appointments.show', $appointment) }}">{{ $appointment->appointment_no }}</a> › Edit</div>
    </div>
</div>

@if($errors->any())
<div class="alert alert-danger"><ul style="margin:0;padding-left:16px">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
@endif

<form method="POST" action="{{ route('appointments.update', $appointment) }}">
@csrf @method('PUT')
<div class="grid" style="grid-template-columns:1fr 300px;gap:20px;align-items:start">
<div>

<div class="card mb-3">
    <div class="card-header"><i class="fa-solid fa-user"></i> Patient</div>
    <div class="card-body">
        <div class="patient-strip" style="padding:12px">
            <div class="ps-avatar">{{ strtoupper(substr($appointment->patient->first_name,0,1)) }}</div>
            <div>
                <div class="ps-name">{{ $appointment->patient->full_name }}</div>
                <div class="ps-jhc_id">{{ $appointment->patient->jhc_id }}</div>
                <div class="ps-meta">
                    <span>{{ $appointment->patient->age }}</span>
                    <span>{{ $appointment->patient->gender }}</span>
                    <span>{{ $appointment->patient->phone }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header"><i class="fa-solid fa-calendar-pen"></i> Appointment Details</div>
    <div class="card-body">
        <div class="form-row cols-2">
            <div class="form-group">
                <label class="form-label">Doctor</label>
                <input type="text" class="form-control" value="{{ $appointment->doctor->full_name }}" disabled style="background:var(--sc-bg);color:var(--sc-muted)">
                <div class="text-small text-muted" style="margin-top:3px">Doctor cannot be changed (reschedule instead).</div>
            </div>
            <div class="form-group">
                <label class="form-label">Status <span class="req">*</span></label>
                <select name="status" class="form-control" required>
                    @foreach(['Scheduled','Confirmed'] as $s)
                    <option value="{{ $s }}" {{ old('status', $appointment->status) === $s ? 'selected' : '' }}>{{ $s }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Date <span class="req">*</span></label>
                <input type="date" name="appointment_date" class="form-control" value="{{ old('appointment_date', $appointment->appointment_date->toDateString()) }}" required>
            </div>
            <div class="form-group">
                <label class="form-label">Time</label>
                <input type="time" name="appointment_time" class="form-control" value="{{ old('appointment_time', \Carbon\Carbon::parse($appointment->appointment_time)->format('H:i')) }}">
            </div>
            <div class="form-group">
                <label class="form-label">Visit Type <span class="req">*</span></label>
                <select name="type" class="form-control" required>
                    @foreach(['New Consultation','Follow-up','Review','Emergency','Teleconsult'] as $t)
                    <option value="{{ $t }}" {{ old('type', $appointment->type) === $t ? 'selected' : '' }}>{{ $t }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Chief Complaint / Reason</label>
            <textarea name="chief_complaint" class="form-control" rows="2" placeholder="Reason for visit…">{{ old('chief_complaint', $appointment->chief_complaint) }}</textarea>
        </div>
        <div class="form-group">
            <label class="form-label">Notes</label>
            <textarea name="notes" class="form-control" rows="2">{{ old('notes', $appointment->notes) }}</textarea>
        </div>
    </div>
</div>

</div>
<div>
    <div class="card mb-3">
        <div class="card-body" style="padding:16px">
            <div class="form-label">Appointment No.</div>
            <div class="fw-700 mono" style="font-size:18px">{{ $appointment->appointment_no }}</div>
            <div class="form-label" style="margin-top:12px">Current Status</div>
            <span class="badge badge-teal">{{ $appointment->status }}</span>
        </div>
    </div>
    <div class="card">
        <div class="card-body" style="display:flex;flex-direction:column;gap:10px;padding:16px">
            <button type="submit" class="btn btn-primary btn-lg" style="width:100%;justify-content:center">
                <i class="fa-solid fa-save"></i> Save Changes
            </button>
            <a href="{{ route('appointments.show', $appointment) }}" class="btn btn-outline" style="width:100%;justify-content:center">Cancel</a>
        </div>
    </div>
</div>
</div>
</form>
@endsection
