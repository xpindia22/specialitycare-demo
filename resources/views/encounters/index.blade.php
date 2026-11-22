{{-- FILE: resources/views/encounters/index.blade.php --}}
@extends('layouts.app')
@section('title','Encounters')
@section('page-title','OPD Encounters')
@section('content')

<div class="page-header">
    <div class="page-header-left">
        <h1>OPD Encounters</h1>
        <div class="breadcrumb">Outpatient — Clinical Encounter History</div>
    </div>
    @include('opd._toolbar', ['inline'=>true])
    @if(auth()->user()?->isOwner())
    <a href="{{ route('encounters.deleted') }}" class="btn btn-sm btn-outline" style="color:var(--sc-red);border-color:var(--sc-red);white-space:nowrap">
        <i class="fa-solid fa-trash"></i> Deleted Visits ({{ \App\Models\Encounter::onlyTrashed()->count() }})
    </a>
    @endif
</div>

{{-- Filter bar --}}
<div class="card mb-4">
    <div class="card-body" style="padding:14px 20px">
        <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end">
            <div style="flex:1;min-width:180px">
                <label class="form-label" style="margin-bottom:4px">Search</label>
                <input type="text" name="q" class="form-control" value="{{ $q }}" placeholder="Patient name, ID, encounter no…">
            </div>
            <div>
                <label class="form-label" style="margin-bottom:4px">Date</label>
                <input type="date" name="date" class="form-control" value="{{ $date }}" style="width:150px">
            </div>
            <div>
                <label class="form-label" style="margin-bottom:4px">Doctor</label>
                <select name="doctor_id" class="form-control" style="width:180px">
                    <option value="">All Doctors</option>
                    @foreach($doctors as $d)
                    <option value="{{ $d->id }}" {{ $doctorId == $d->id ? 'selected' : '' }}>Dr. {{ $d->first_name }} {{ $d->last_name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label" style="margin-bottom:4px">Status</label>
                <select name="status" class="form-control" style="width:140px">
                    <option value="">All Status</option>
                    @foreach(['In Progress','Completed','Cancelled'] as $s)
                    <option value="{{ $s }}" {{ $status === $s ? 'selected' : '' }}>{{ $s }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-filter"></i> Filter</button>
            <a href="{{ route('encounters.index') }}" class="btn btn-outline">Reset</a>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <i class="fa-solid fa-stethoscope"></i> Encounters
        <div class="card-actions">
            <span class="badge badge-navy">{{ $encounters->total() }} total</span>
        </div>
    </div>
    <div class="table-wrap">
        <table class="sc-table">
            <thead>
                <tr>
                    <th>Encounter No.</th>
                    <th>Patient</th>
                    <th>Doctor</th>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Chief Complaint</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @forelse($encounters as $enc)
            @php
                $statusColors = ['In Progress' => 'amber', 'Completed' => 'green', 'Cancelled' => 'red'];
                $sc = $statusColors[$enc->status] ?? 'gray';
            @endphp
            <tr style="cursor:pointer" onclick="location='{{ route('encounters.show', $enc) }}'">
                <td class="mono fw-600">{{ $enc->encounter_no }}</td>
                <td>
                    <div class="fw-600">{{ $enc->patient->full_name }}</div>
                    <div class="text-small text-muted mono">{{ $enc->patient->jhc_id }}</div>
                </td>
                <td>Dr. {{ $enc->doctor->first_name }} {{ $enc->doctor->last_name }}</td>
                <td style="white-space:nowrap">{{ $enc->encounter_date->format('d M Y') }}</td>
                <td><span class="badge badge-navy">{{ $enc->visit_type }}</span></td>
                <td style="max-width:200px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                    {{ $enc->chief_complaint ? Str::limit(strip_tags($enc->chief_complaint), 60) : '—' }}
                </td>
                <td><span class="badge badge-{{ $sc }}">{{ $enc->status }}</span></td>
                <td>
                    <a href="{{ route('encounters.show', $enc) }}" class="btn btn-sm btn-outline">
                        <i class="fa-solid fa-eye"></i> View
                    </a>
                </td>
            </tr>
            @empty
            <tr><td colspan="8" class="text-center text-muted" style="padding:40px">No encounters found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $encounters->links() }}</div>
</div>

@endsection
