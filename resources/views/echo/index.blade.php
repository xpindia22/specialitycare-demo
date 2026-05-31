{{-- FILE: resources/views/echo/index.blade.php --}}
@extends('layouts.app')
@section('title','Echo')
@section('page-title','Echocardiography Records')
@section('content')
<div class="page-header">
    <div class="page-header-left">
        <h1>Echo Records</h1>
        @if(isset($filterPatient) && $filterPatient)
        <div class="breadcrumb">Filtered: {{ $filterPatient->full_name }} &nbsp;<a href="{{ route('echo.index') }}">Clear filter</a></div>
        @endif
    </div>
    <a href="{{ route('echo.create') }}{{ isset($filterPatient) && $filterPatient ? '?patient_id='.$filterPatient->id : '' }}" class="btn btn-primary"><i class="fa-solid fa-plus"></i> New Echo</a>
    @if(isset($filterPatient) && $filterPatient)
    <a href="{{ route('patients.show', $filterPatient) }}" class="btn btn-outline"><i class="fa-solid fa-arrow-left"></i> Back to Patient</a>
    @endif
</div>
<form method="GET" action="{{ route('echo.index') }}" style="display:flex;gap:8px;margin-bottom:14px">
    @if(isset($filterPatient) && $filterPatient)<input type="hidden" name="patient_id" value="{{ $filterPatient->id }}">@endif
    <input type="search" name="q" value="{{ $q }}" placeholder="Search by patient, record no., doctor, EF%…" class="form-control" style="max-width:380px">
    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
    @if($q)<a href="{{ route('echo.index', array_filter(['patient_id' => request('patient_id')])) }}" class="btn btn-outline">Clear</a>@endif
</form>
<div class="card"><div class="table-wrap"><table class="sc-table">
    <thead><tr><th>Record No.</th><th>Patient</th><th>Date</th><th>EF%</th><th>LV EDD</th><th>Doctor</th><th>Actions</th></tr></thead>
    <tbody>
    @forelse($records as $r)
    @php
        $ef = $r->ef_percent;
        $ef = $ef && !str_contains($ef, '%') ? $ef.'%' : $ef;
    @endphp
    <tr>
        <td class="mono fw-600">{{ $r->record_no }}</td>
        <td><a href="{{ route('patients.show',$r->patient) }}" style="font-weight:600;color:var(--sc-navy);text-decoration:none">{{ $r->patient->full_name }}</a><div class="text-small mono text-muted">{{ $r->patient->jhc_id }}</div></td>
        <td>{{ $r->record_date->format('d M Y') }}</td>
        <td>{{ $ef ?: '—' }}</td>
        <td>{{ $r->lv_edd ?: '—' }}</td>
        <td>{{ $r->doctor->full_name }}</td>
        <td style="white-space:nowrap">
            <a href="{{ route('echo.show',$r) }}" class="btn btn-sm btn-outline" title="View"><i class="fa-solid fa-eye"></i></a>
            @if($r->echo_file)
            @php $fUrl = str_contains($r->echo_file,'/') ? asset('storage/'.$r->echo_file) : asset('storage/echo_files/'.$r->echo_file); @endphp
            <a href="{{ $fUrl }}" target="_blank" class="btn btn-sm btn-outline" title="View file"><i class="fa-solid fa-file-waveform"></i></a>
            <a href="{{ $fUrl }}" download="{{ basename($r->echo_file) }}" class="btn btn-sm btn-outline" title="Download"><i class="fa-solid fa-download"></i></a>
            @endif
            <a href="{{ route('echo.edit',$r) }}" class="btn btn-sm btn-outline" title="Edit"><i class="fa-solid fa-pencil"></i></a>
            <form method="POST" action="{{ route('echo.destroy', $r) }}" style="display:inline" onsubmit="return confirm('Delete Echo {{ $r->record_no }}? This cannot be undone.')">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-sm btn-outline" title="Delete"><i class="fa-solid fa-trash"></i></button>
            </form>
        </td>
    </tr>
    @empty
    <tr><td colspan="7" class="text-center text-muted" style="padding:40px">No echo records.</td></tr>
    @endforelse
    </tbody>
</table></div><div class="card-footer">{{ $records->links() }}</div></div>

@if($traceUploads->count())
<div class="card" style="margin-top:16px">
    <div class="card-header" style="background:var(--sc-navy);color:#fff">
        <i class="fa-solid fa-file-waveform" style="color:var(--sc-teal)"></i> Uploaded Echo Files
        <span class="badge badge-teal" style="font-size:10px;margin-left:6px">{{ $traceUploads->count() }}</span>
    </div>
    <div class="card-body">
        @foreach($traceUploads as $upload)
        @php $fUrl = asset('storage/' . $upload->file_path); @endphp
        <div style="display:flex;gap:12px;align-items:flex-start;padding:12px;border:1px solid var(--sc-border);border-radius:8px;background:var(--sc-bg);margin-bottom:10px">
            <div style="width:38px;height:38px;border-radius:8px;background:#fff;border:1px solid var(--sc-border);display:flex;align-items:center;justify-content:center;color:var(--sc-teal);flex:0 0 auto">
                <i class="fa-solid {{ str_contains($upload->mime_type ?? '', 'pdf') ? 'fa-file-pdf' : 'fa-file-image' }}"></i>
            </div>
            <div style="min-width:0;flex:1">
                <div style="font-weight:700;font-size:13px">{{ $upload->title ?: $upload->original_name }}</div>
                <div style="font-size:11px;color:var(--sc-muted);margin-top:2px">
                    {{ $upload->encounter ? 'Visit '.$upload->encounter->encounter_date->format('d M Y') : '' }}
                    · {{ $upload->file_size_label }} · {{ $upload->created_at->format('d M Y h:i A') }}
                </div>
                @if($upload->notes)<div style="font-size:11px;margin-top:4px">{{ $upload->notes }}</div>@endif
            </div>
            <div style="display:flex;gap:6px;align-items:center;flex:0 0 auto">
                <a href="{{ $fUrl }}" target="_blank" class="btn btn-sm btn-outline" title="View"><i class="fa-solid fa-eye"></i></a>
                <a href="{{ $fUrl }}" download="{{ $upload->original_name }}" class="btn btn-sm btn-outline" title="Download"><i class="fa-solid fa-download"></i></a>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif
@endsection
