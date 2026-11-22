{{-- FILE: resources/views/encounters/deleted.blade.php --}}
@extends('layouts.app')
@section('title','Deleted Visits')
@section('page-title','Deleted Visits')
@section('content')

<div class="page-header">
    <div class="page-header-left">
        <h1>Deleted Visits</h1>
        <div class="breadcrumb"><a href="{{ route('encounters.index') }}">OPD Encounters</a> › Deleted Visits (only the clinic owner sees this page)</div>
    </div>
    @include('opd._toolbar', ['inline'=>true])
</div>

{{-- Filter bar --}}
<div class="card mb-4">
    <div class="card-body" style="padding:14px 20px">
        <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end">
            <div style="flex:1;min-width:180px">
                <label class="form-label" style="margin-bottom:4px">Search</label>
                <input type="text" name="q" class="form-control" value="{{ $q }}" placeholder="Patient name, ID, encounter no…">
            </div>
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-filter"></i> Filter</button>
            <a href="{{ route('encounters.deleted') }}" class="btn btn-outline">Reset</a>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <i class="fa-solid fa-trash" style="color:var(--sc-red)"></i> Deleted Visits
        <div class="card-actions">
            <span class="badge badge-red">{{ $encounters->total() }} deleted</span>
        </div>
    </div>
    <div class="table-wrap">
        <table class="sc-table">
            <thead>
                <tr>
                    <th>Encounter No.</th>
                    <th>Patient</th>
                    <th>Doctor</th>
                    <th>Visit Date</th>
                    <th>Deleted By</th>
                    <th>Deleted On</th>
                    <th>Reason</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @forelse($encounters as $enc)
            <tr style="cursor:pointer" onclick="location='{{ route('encounters.show', $enc) }}'">
                <td class="mono fw-600">{{ $enc->encounter_no }}</td>
                <td>
                    <div class="fw-600">{{ $enc->patient?->full_name ?? '—' }}</div>
                    <div class="text-small text-muted mono">{{ $enc->patient?->jhc_id }}</div>
                </td>
                <td>@if($enc->doctor)Dr. {{ $enc->doctor->first_name }} {{ $enc->doctor->last_name }}@else — @endif</td>
                <td style="white-space:nowrap">{{ $enc->encounter_date->format('d M Y') }}</td>
                <td>{{ $enc->deletedBy?->name ?? '—' }}</td>
                <td style="white-space:nowrap">{{ $enc->deleted_at->timezone('Asia/Kolkata')->format('d M Y, h:i A') }}</td>
                <td style="max-width:220px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis" title="{{ $enc->delete_reason }}">
                    <span class="badge badge-amber">{{ $enc->delete_reason ?: '—' }}</span>
                </td>
                <td style="white-space:nowrap" onclick="event.stopPropagation()">
                    <a href="{{ route('encounters.show', $enc) }}" class="btn btn-sm btn-outline">
                        <i class="fa-solid fa-eye"></i> View
                    </a>
                    <form method="POST" action="{{ route('encounters.restore', $enc) }}" style="display:inline" onsubmit="return confirm('Restore visit {{ $enc->encounter_no }}?')">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-success"><i class="fa-solid fa-rotate-left"></i> Restore</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr><td colspan="8" class="text-center text-muted" style="padding:40px">No deleted visits.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">
        {{ $encounters->links() }}
        <div class="text-small text-muted" style="margin-top:6px"><i class="fa-solid fa-info-circle"></i> To erase a visit for good, open it (View) and use <b>Delete permanently</b>.</div>
    </div>
</div>

@endsection
