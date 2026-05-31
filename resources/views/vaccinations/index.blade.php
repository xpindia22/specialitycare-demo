@extends('layouts.app')
@section('title', 'Vaccinations — ' . $patient->full_name)
@section('page-title', 'Vaccination Card')
@section('content')

<div class="page-header">
    <div class="page-header-left">
        <h1>Vaccination Card</h1>
        <div class="breadcrumb"><a href="{{ route('patients.show', $patient) }}">{{ $patient->full_name }}</a> &rsaquo; Vaccinations</div>
    </div>
    <a href="{{ route('vaccinations.print', $patient) }}" target="_blank" class="btn btn-outline btn-sm"><i class="fa-solid fa-print"></i> Print Card</a>
    <a href="{{ route('patients.show', $patient) }}" class="btn btn-outline btn-sm"><i class="fa-solid fa-arrow-left"></i> Back</a>
</div>

@if(session('success'))<div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> {{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger"><i class="fa-solid fa-circle-xmark"></i> {{ session('error') }}</div>@endif

{{-- Patient strip --}}
<div class="patient-strip mb-3">
    <div class="ps-avatar">{{ strtoupper(substr($patient->first_name,0,1)) }}</div>
    <div>
        <div class="ps-name">{{ $patient->full_name }}</div>
        <div class="ps-jhc_id">{{ $patient->jhc_id }}</div>
        <div class="ps-meta">
            <span><i class="fa-solid fa-calendar"></i> {{ $patient->age }}</span>
            <span><i class="fa-solid fa-venus-mars"></i> {{ $patient->gender }}</span>
            @if($patient->date_of_birth)<span><i class="fa-solid fa-cake-candles"></i> DOB {{ $patient->date_of_birth->format('d M Y') }}</span>@endif
        </div>
    </div>
</div>

@if(!$patient->date_of_birth)
<div class="alert alert-warning">Patient date of birth is missing — schedule status (due / overdue) cannot be calculated until you set DOB.</div>
@endif

@php
    $statusBadge = ['given'=>'badge-green','due'=>'badge-amber','overdue'=>'badge-red','upcoming'=>'badge-outline','pending'=>'badge-outline'];
    $statusLabel = ['given'=>'Given','due'=>'Due now','overdue'=>'Overdue','upcoming'=>'Upcoming','pending'=>'—'];
@endphp

<div class="card">
    <div class="card-header">
        <i class="fa-solid fa-syringe"></i> National Immunisation Schedule
        @php
            $given    = $rows->where('status', 'given')->count();
            $due      = $rows->where('status', 'due')->count();
            $overdue  = $rows->where('status', 'overdue')->count();
            $upcoming = $rows->where('status', 'upcoming')->count();
        @endphp
        <div class="card-actions" style="display:flex;gap:6px;align-items:center">
            <span class="badge badge-green">{{ $given }} given</span>
            @if($due)<span class="badge badge-amber">{{ $due }} due</span>@endif
            @if($overdue)<span class="badge badge-red">{{ $overdue }} overdue</span>@endif
            @if($upcoming)<span class="badge badge-outline">{{ $upcoming }} upcoming</span>@endif
        </div>
    </div>
    <div class="table-wrap">
        <table class="sc-table">
            <thead>
                <tr>
                    <th>Vaccine</th>
                    <th>Dose</th>
                    <th>Recommended Age</th>
                    <th>Due Date</th>
                    <th>Given Date</th>
                    <th>Batch</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @foreach($rows as $row)
            <tr>
                <td class="fw-600">{{ $row->name }}</td>
                <td>{{ $row->dose }}</td>
                <td class="text-small text-muted">{{ $row->rec_label }}</td>
                <td class="text-small">{{ $row->due_date ? \Carbon\Carbon::parse($row->due_date)->format('d M Y') : '—' }}</td>
                <td class="text-small">
                    @if($row->given)
                        <strong>{{ $row->given->given_date->format('d M Y') }}</strong>
                        @if($row->given->givenBy)<div class="text-muted">by {{ $row->given->givenBy->name }}</div>@endif
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </td>
                <td class="text-small mono">{{ $row->given->batch_number ?? '—' }}</td>
                <td><span class="badge {{ $statusBadge[$row->status] }}">{{ $statusLabel[$row->status] }}</span></td>
                <td>
                    @if($row->given)
                        <form method="POST" action="{{ route('vaccinations.destroy', [$patient, $row->given]) }}" onsubmit="return confirm('Remove this vaccination record?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-icon btn-sm btn-outline" style="color:var(--sc-red);border-color:var(--sc-red)" title="Remove"><i class="fa-solid fa-trash"></i></button>
                        </form>
                    @else
                        <button type="button" class="btn btn-sm btn-primary" onclick="openGiveModal('{{ $row->code }}', @js($row->name), @js($row->dose))"><i class="fa-solid fa-syringe"></i> Mark Given</button>
                    @endif
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- Mark Given modal --}}
<div id="give-modal" class="modal-overlay">
    <div class="modal-box" style="max-width:480px">
        <div class="modal-header">
            <i class="fa-solid fa-syringe" style="color:var(--sc-teal)"></i>
            <h3>Record Vaccination</h3>
            <button class="modal-close" onclick="document.getElementById('give-modal').classList.remove('open')">&times;</button>
        </div>
        <form method="POST" action="{{ route('vaccinations.store', $patient) }}">
            @csrf
            <input type="hidden" name="vaccine_code" id="give-code">
            <div class="modal-body" style="display:flex;flex-direction:column;gap:12px">
                <div style="background:var(--sc-bg);padding:10px 14px;border-radius:8px;font-size:13px">
                    <div><strong id="give-name">—</strong></div>
                    <div class="text-muted">Dose: <span id="give-dose">—</span></div>
                </div>
                <div class="form-group" style="margin:0">
                    <label class="form-label">Given Date <span class="req">*</span></label>
                    <input type="date" name="given_date" class="form-control" value="{{ today()->toDateString() }}" max="{{ today()->toDateString() }}" required>
                </div>
                <div class="form-row cols-2">
                    <div class="form-group" style="margin:0">
                        <label class="form-label">Batch Number</label>
                        <input type="text" name="batch_number" class="form-control" placeholder="e.g. ABC-23-04">
                    </div>
                    <div class="form-group" style="margin:0">
                        <label class="form-label">Site</label>
                        <input type="text" name="site" class="form-control" placeholder="e.g. Right deltoid, Left thigh">
                    </div>
                </div>
                <div class="form-row cols-2">
                    <div class="form-group" style="margin:0">
                        <label class="form-label">Manufacturer</label>
                        <input type="text" name="manufacturer" class="form-control">
                    </div>
                    <div class="form-group" style="margin:0">
                        <label class="form-label">Expiry Date</label>
                        <input type="date" name="expiry_date" class="form-control">
                    </div>
                </div>
                <div class="form-group" style="margin:0">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="AEFI, missed dose reason, etc."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('give-modal').classList.remove('open')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save</button>
            </div>
        </form>
    </div>
</div>

<script>
function openGiveModal(code, name, dose) {
    document.getElementById('give-code').value = code;
    document.getElementById('give-name').textContent = name;
    document.getElementById('give-dose').textContent = dose;
    document.getElementById('give-modal').classList.add('open');
}
</script>
@endsection
