@extends('layouts.app')
@section('title', 'Certificates')
@section('page-title', 'Certificates')

@section('content')
<div class="page-header">
    <div class="page-header-left">
        <h1>Certificates</h1>
        <div class="breadcrumb">Administration &rsaquo; Certificates</div>
    </div>
    <div>
        <a href="{{ route('certificates.create') }}" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> New Certificate
        </a>
    </div>
</div>

{{-- Search --}}
<div class="card mb-4">
    <div class="card-body" style="padding:14px 20px">
        <form method="GET" action="{{ route('certificates.index') }}" style="display:flex;gap:12px;align-items:center">
            <div style="flex:1;position:relative">
                <i class="fa-solid fa-magnifying-glass" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--sc-muted);font-size:13px"></i>
                <input type="text" name="q" value="{{ request('q') }}"
                       placeholder="Search by patient name, JHC ID, or certificate number…"
                       class="form-control" style="padding-left:32px">
            </div>
            <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-search"></i> Search</button>
            @if(request('q'))
                <a href="{{ route('certificates.index') }}" class="btn btn-outline btn-sm">Clear</a>
            @endif
        </form>
    </div>
</div>

{{-- Table --}}
<div class="card">
    <div class="card-header">
        <i class="fa-solid fa-file-certificate"></i> All Certificates
        <span class="badge badge-teal" style="margin-left:8px">{{ $certificates->total() }}</span>
    </div>
    <div class="table-wrap">
        @if($certificates->isEmpty())
            <div style="text-align:center;padding:48px 24px;color:var(--sc-muted)">
                <i class="fa-solid fa-file-certificate" style="font-size:40px;margin-bottom:12px;display:block;opacity:.25"></i>
                <div style="font-size:15px;font-weight:600;margin-bottom:6px">No certificates found</div>
                <div style="font-size:13px">
                    @if(request('q'))
                        No results for "{{ request('q') }}".
                        <a href="{{ route('certificates.index') }}" style="color:var(--sc-teal)">Clear search</a>
                    @else
                        Issue the first certificate using the button above.
                    @endif
                </div>
            </div>
        @else
            <table class="sc-table">
                <thead>
                    <tr>
                        <th>Cert No</th>
                        <th>Patient</th>
                        <th>Type</th>
                        <th>Doctor</th>
                        <th>Issued Date</th>
                        <th style="text-align:right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($certificates as $cert)
                    <tr>
                        <td>
                            <span class="mono" style="font-size:12px;color:var(--sc-teal);font-weight:600">
                                {{ $cert->certificate_no }}
                            </span>
                        </td>
                        <td>
                            <div style="font-weight:600;font-size:13px">{{ $cert->patient->full_name }}</div>
                            <div style="font-size:11px;color:var(--sc-muted)">{{ $cert->patient->jhc_id }}</div>
                        </td>
                        <td>
                            <span class="badge badge-teal">{{ $cert->type_label }}</span>
                        </td>
                        <td>
                            @if($cert->doctor)
                                <span style="font-size:13px">Dr. {{ $cert->doctor->first_name }} {{ $cert->doctor->last_name }}</span>
                            @else
                                <span style="color:var(--sc-muted);font-size:12px">—</span>
                            @endif
                        </td>
                        <td>
                            <span style="font-size:13px">{{ $cert->issued_date->format('d M Y') }}</span>
                        </td>
                        <td style="text-align:right">
                            <div style="display:flex;gap:6px;justify-content:flex-end">
                                <a href="{{ route('certificates.show', $cert) }}" class="btn btn-outline btn-sm" title="View">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                                <a href="{{ route('certificates.print', $cert) }}" target="_blank" class="btn btn-outline btn-sm" title="Print">
                                    <i class="fa-solid fa-print"></i>
                                </a>
                                <form method="POST" action="{{ route('certificates.destroy', $cert) }}"
                                      onsubmit="return confirm('Delete certificate {{ $cert->certificate_no }}?')"
                                      style="display:inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-outline btn-sm" style="color:var(--sc-red)" title="Delete">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
    @if($certificates->hasPages())
    <div class="card-footer" style="display:flex;justify-content:space-between;align-items:center">
        <div style="font-size:12px;color:var(--sc-muted)">
            Showing {{ $certificates->firstItem() }}–{{ $certificates->lastItem() }} of {{ $certificates->total() }}
        </div>
        {{ $certificates->links() }}
    </div>
    @endif
</div>
@endsection
