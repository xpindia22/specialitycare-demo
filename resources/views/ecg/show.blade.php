{{-- FILE: resources/views/ecg/show.blade.php --}}
@extends('layouts.app')
@section('title', 'ECG '.$ecg->record_no)
@section('page-title','ECG Report')
@section('content')
<div class="page-header">
    <div class="page-header-left"><h1>ECG Report — <span class="mono">{{ $ecg->record_no }}</span></h1></div>
    <a href="{{ route('ecg.edit', $ecg) }}" class="btn btn-primary"><i class="fa-solid fa-pencil me-1"></i> Edit</a>
    @if($ecg->ecg_file)
    @php $hdrUrl = str_contains($ecg->ecg_file,'/') ? asset('storage/'.$ecg->ecg_file) : asset('storage/ecg_files/'.$ecg->ecg_file); @endphp
    <a href="{{ $hdrUrl }}" target="_blank" class="btn btn-outline"><i class="fa-solid fa-eye"></i> View File</a>
    <a href="{{ $hdrUrl }}" download="{{ basename($ecg->ecg_file) }}" class="btn btn-outline"><i class="fa-solid fa-download"></i> Download</a>
    @endif
    <button onclick="window.print()" class="btn btn-outline"><i class="fa-solid fa-print"></i> Print</button>
</div>
<div class="patient-strip mb-3">
    <div class="ps-avatar">{{ strtoupper(substr($ecg->patient->first_name,0,1)) }}</div>
    <div><div class="ps-name">{{ $ecg->patient->full_name }}</div><div class="ps-jhc_id">{{ $ecg->patient->jhc_id }}</div>
    <div class="ps-meta"><span>{{ $ecg->patient->age }}</span><span>{{ $ecg->patient->gender }}</span><span><i class="fa-solid fa-calendar"></i> {{ $ecg->record_date->format('d M Y') }}</span><span>{{ $ecg->doctor->full_name }}</span></div></div>
</div>
<div class="grid grid-2">
    <div class="card">
        <div class="card-header"><i class="fa-solid fa-heart-pulse"></i> ECG Parameters</div>
        <div class="card-body">
            @php $params = [['Heart Rate', $ecg->heart_rate],['Rhythm', $ecg->rhythm],['PR Interval', $ecg->pr_interval],['QRS Duration', $ecg->qrs_duration],['QT/QTc', $ecg->qt_qtc],['Axis', $ecg->axis]]; @endphp
            @foreach($params as [$k,$v])
            <div style="display:flex;padding:8px 0;border-bottom:1px solid var(--sc-border)">
                <div style="width:45%;color:var(--sc-muted);font-size:12px;font-weight:600">{{ $k }}</div>
                <div class="fw-600">{{ $v ?: '—' }}</div>
            </div>
            @endforeach
        </div>
    </div>
    <div class="card">
        <div class="card-header"><i class="fa-solid fa-file-medical"></i> Interpretation</div>
        <div class="card-body">
            <p style="font-size:13px;line-height:1.6">{{ $ecg->interpretation ?: 'No interpretation recorded.' }}</p>
        </div>
        <div class="card-footer">
            <div style="font-size:11px;color:var(--sc-muted)">Fee: ₹{{ number_format($ecg->price, 2) }} &nbsp;·&nbsp; Performed by: {{ $ecg->performedBy?->name ?? '—' }}</div>
        </div>
    </div>
</div>

@if($ecg->ecg_file)
@php
    $isPdf = str_ends_with(strtolower($ecg->ecg_file), '.pdf');
    $isLegacy = str_contains($ecg->ecg_file, '/');
    $fileUrl = $isLegacy
        ? asset('storage/' . $ecg->ecg_file)
        : asset('storage/ecg_files/' . $ecg->ecg_file);
@endphp
<div class="card" style="margin-top:20px">
    <div class="card-header" style="display:flex;align-items:center;justify-content:space-between">
        <span><i class="fa-solid fa-file-waveform" style="color:var(--sc-teal)"></i> Uploaded ECG Report &mdash; <span style="font-size:12px;color:var(--sc-muted);font-weight:400">{{ $ecg->ecg_file }}</span></span>
        <div style="display:flex;gap:8px">
            <a href="{{ $fileUrl }}" target="_blank" class="btn btn-sm btn-outline"><i class="fa-solid fa-eye"></i> View</a>
            <a href="{{ $fileUrl }}" download="{{ $ecg->ecg_file }}" class="btn btn-sm btn-outline"><i class="fa-solid fa-download"></i> Download</a>
            @if($isPdf)<button onclick="document.getElementById('ecg-iframe').contentWindow.print()" class="btn btn-sm btn-outline"><i class="fa-solid fa-print"></i> Print</button>@endif
            <form method="POST" action="{{ route('ecg.file.destroy', $ecg) }}" style="display:inline" onsubmit="return confirm('Delete this uploaded file? The ECG record will be kept.')">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-sm btn-outline" style="color:var(--sc-red)" title="Delete file"><i class="fa-solid fa-trash"></i> Delete File</button>
            </form>
        </div>
    </div>
    <div class="card-body" style="padding:0">
        @if($isPdf)
            <iframe id="ecg-iframe" src="{{ $fileUrl }}" style="width:100%;height:700px;border:0"></iframe>
        @else
            <img src="{{ $fileUrl }}" alt="ECG Trace" style="width:100%;display:block;border-radius:0 0 8px 8px">
        @endif
    </div>
</div>
@endif {{-- ecg_file --}}
@endsection
