@extends('layouts.app')
@section('title', 'Certificate — ' . $cert->certificate_no)
@section('page-title', 'Certificate Details')

@section('content')
<div class="page-header">
    <div class="page-header-left">
        <h1>Certificate Details</h1>
        <div class="breadcrumb">
            <a href="{{ route('certificates.index') }}">Certificates</a>
            &rsaquo; {{ $cert->certificate_no }}
        </div>
    </div>
    <div style="display:flex;gap:10px">
        <a href="{{ route('certificates.print', $cert) }}" target="_blank" class="btn btn-outline">
            <i class="fa-solid fa-print"></i> Print
        </a>
        <a href="{{ route('certificates.index') }}" class="btn btn-outline">
            <i class="fa-solid fa-arrow-left"></i> Back to List
        </a>
        <form method="POST" action="{{ route('certificates.destroy', $cert) }}"
              onsubmit="return confirm('Delete this certificate permanently?')">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-danger">
                <i class="fa-solid fa-trash"></i> Delete
            </button>
        </form>
    </div>
</div>

{{-- Certificate header strip --}}
<div class="patient-strip" style="margin-bottom:20px">
    <div class="ps-avatar" style="font-size:18px">
        <i class="fa-solid fa-file-certificate"></i>
    </div>
    <div style="flex:1">
        <div class="ps-name">{{ $cert->patient->full_name }}</div>
        <div class="ps-jhc_id">{{ $cert->patient->jhc_id }}</div>
        <div class="ps-meta">
            <span><i class="fa-solid fa-certificate"></i> {{ $cert->certificate_no }}</span>
            <span><i class="fa-solid fa-calendar"></i> {{ $cert->issued_date->format('d M Y') }}</span>
            @if($cert->patient->age !== 'N/A')
                <span><i class="fa-solid fa-user"></i> {{ $cert->patient->age }}, {{ $cert->patient->gender }}</span>
            @endif
        </div>
    </div>
    <div class="ps-actions">
        <span class="badge badge-teal" style="font-size:13px;padding:6px 14px">{{ $cert->type_label }}</span>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <i class="fa-solid fa-circle-info"></i> Certificate Information
    </div>
    <div class="card-body">
        <div class="grid grid-2" style="gap:24px">

            <div>
                <div class="form-label" style="margin-bottom:4px;color:var(--sc-muted);font-size:11px;text-transform:uppercase;letter-spacing:.5px">Certificate No</div>
                <div class="mono" style="font-size:15px;font-weight:700;color:var(--sc-teal)">{{ $cert->certificate_no }}</div>
            </div>

            <div>
                <div class="form-label" style="margin-bottom:4px;color:var(--sc-muted);font-size:11px;text-transform:uppercase;letter-spacing:.5px">Certificate Type</div>
                <div style="font-size:14px;font-weight:600">{{ $cert->type_label }}</div>
            </div>

            <div>
                <div class="form-label" style="margin-bottom:4px;color:var(--sc-muted);font-size:11px;text-transform:uppercase;letter-spacing:.5px">Patient</div>
                <div style="font-size:14px;font-weight:600">{{ $cert->patient->full_name }}</div>
                <div style="font-size:12px;color:var(--sc-muted)">{{ $cert->patient->jhc_id }}</div>
            </div>

            <div>
                <div class="form-label" style="margin-bottom:4px;color:var(--sc-muted);font-size:11px;text-transform:uppercase;letter-spacing:.5px">Certifying Doctor</div>
                @if($cert->doctor)
                    <div style="font-size:14px;font-weight:600">Dr. {{ $cert->doctor->first_name }} {{ $cert->doctor->last_name }}</div>
                    @if($cert->doctor->specialization)
                        <div style="font-size:12px;color:var(--sc-muted)">{{ $cert->doctor->specialization }}</div>
                    @endif
                @else
                    <div style="color:var(--sc-muted);font-size:13px">—</div>
                @endif
            </div>

            <div>
                <div class="form-label" style="margin-bottom:4px;color:var(--sc-muted);font-size:11px;text-transform:uppercase;letter-spacing:.5px">Issued Date</div>
                <div style="font-size:14px;font-weight:600">{{ $cert->issued_date->format('d M Y') }}</div>
            </div>

            <div>
                <div class="form-label" style="margin-bottom:4px;color:var(--sc-muted);font-size:11px;text-transform:uppercase;letter-spacing:.5px">Purpose</div>
                <div style="font-size:14px">{{ $cert->purpose ?: '—' }}</div>
            </div>

            <div>
                <div class="form-label" style="margin-bottom:4px;color:var(--sc-muted);font-size:11px;text-transform:uppercase;letter-spacing:.5px">Diagnosis</div>
                <div style="font-size:14px">{{ $cert->diagnosis ?: '—' }}</div>
            </div>

            @if($cert->certificate_type === 'medical_leave')
            <div>
                <div class="form-label" style="margin-bottom:4px;color:var(--sc-muted);font-size:11px;text-transform:uppercase;letter-spacing:.5px">Rest Period</div>
                @if($cert->rest_from && $cert->rest_to)
                    @php
                        $days = \Carbon\Carbon::parse($cert->rest_from)->diffInDays(\Carbon\Carbon::parse($cert->rest_to)) + 1;
                    @endphp
                    <div style="font-size:14px;font-weight:600">
                        {{ $cert->rest_from->format('d M Y') }} — {{ $cert->rest_to->format('d M Y') }}
                        <span class="badge badge-amber" style="margin-left:6px">{{ $days }} day{{ $days > 1 ? 's' : '' }}</span>
                    </div>
                @else
                    <div style="color:var(--sc-muted);font-size:13px">—</div>
                @endif
            </div>
            @endif

        </div>

        @if($cert->remarks)
        <div style="margin-top:20px;padding-top:20px;border-top:1px solid var(--sc-border)">
            <div class="form-label" style="margin-bottom:6px;color:var(--sc-muted);font-size:11px;text-transform:uppercase;letter-spacing:.5px">Remarks</div>
            <div class="ql-editor" style="font-size:13.5px;line-height:1.6;color:var(--sc-text);padding:0">{!! $cert->remarks !!}</div>
        </div>
        @endif

        <div style="margin-top:20px;padding-top:16px;border-top:1px solid var(--sc-border);font-size:11.5px;color:var(--sc-muted)">
            Issued by <strong>{{ $cert->createdBy->name }}</strong>
            on {{ $cert->created_at->format('d M Y, h:i A') }}
        </div>
    </div>
</div>
@endsection
