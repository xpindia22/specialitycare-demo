@extends('layouts.app')
@section('title', 'Claim ' . ($claim->claim_no ?? '#'.$claim->id))
@section('page-title', 'Insurance Claim')
@section('content')

@php $badgeMap = ['blue'=>'navy','indigo'=>'purple']; $col = $badgeMap[$claim->status_color] ?? $claim->status_color; @endphp

<div class="page-header">
    <div class="page-header-left">
        <h1>Claim {{ $claim->claim_no ?? '#'.$claim->id }}</h1>
        <div class="breadcrumb"><a href="{{ route('insurance.claims.index') }}">Insurance / TPA</a> &rsaquo; {{ $claim->insurer->name }} · {{ $claim->patient->full_name }}</div>
    </div>
    @include('insurance._toolbar', ['inline'=>true, 'extra' => [
        ['url'=>route('insurance.claims.edit', $claim), 'icon'=>'fa-pen', 'label'=>'Edit', 'bg'=>'#475569'],
    ]])
</div>

@if(session('success'))<div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> {{ session('success') }}</div>@endif

<div style="margin-bottom:14px"><span class="badge badge-{{ $col }}">{{ $claim->status_label }}</span></div>

<div class="grid" style="grid-template-columns:2fr 1fr;gap:16px;margin-bottom:16px">
    {{-- Claim info --}}
    <div class="card">
        <div class="card-header">Claim Details</div>
        <div class="card-body">
            <div class="grid" style="grid-template-columns:1fr 1fr 1fr;gap:12px 24px">
                <div><div class="text-small text-muted">IPD No.</div><div style="font-weight:600">{{ $claim->admission->ipd_no ?? '—' }}</div></div>
                <div><div class="text-small text-muted">Patient</div><div style="font-weight:600">{{ $claim->patient->full_name }}</div></div>
                <div><div class="text-small text-muted">Insurer / TPA</div><div style="font-weight:600">{{ $claim->insurer->name }}</div></div>
                <div><div class="text-small text-muted">Policy No.</div><div style="font-weight:600" class="mono">{{ $claim->policy_no ?? '—' }}</div></div>
                <div><div class="text-small text-muted">Member ID</div><div style="font-weight:600" class="mono">{{ $claim->member_id ?? '—' }}</div></div>
                <div><div class="text-small text-muted">Policy Holder</div><div style="font-weight:600">{{ $claim->policy_holder_name ?? '—' }}</div></div>
                <div><div class="text-small text-muted">Sum Insured</div><div style="font-weight:600">{{ $claim->sum_insured ? '₹'.number_format($claim->sum_insured,2) : '—' }}</div></div>
                <div><div class="text-small text-muted">Approved</div><div style="font-weight:600;color:var(--sc-teal)">{{ $claim->approved_amount ? '₹'.number_format($claim->approved_amount,2) : '—' }}</div></div>
                <div><div class="text-small text-muted">Settled</div><div style="font-weight:600;color:var(--sc-green)">{{ $claim->settled_amount ? '₹'.number_format($claim->settled_amount,2) : '—' }}</div></div>
                <div><div class="text-small text-muted">Co-pay</div><div style="font-weight:600;color:var(--sc-amber)">{{ $claim->co_pay_amount ? '₹'.number_format($claim->co_pay_amount,2) : '—' }}</div></div>
                <div><div class="text-small text-muted">Pending</div><div style="font-weight:600;color:var(--sc-red)">{{ $claim->pending_amount ? '₹'.number_format($claim->pending_amount,2) : '—' }}</div></div>
                <div><div class="text-small text-muted">Policy Expiry</div><div style="font-weight:600">{{ $claim->policy_expiry?->format('d M Y') ?? '—' }}</div></div>
                <div style="grid-column:1 / -1"><div class="text-small text-muted">Diagnosis / ICD</div><div style="font-weight:600">{{ $claim->diagnosis_icd ?? '—' }}</div></div>
                @if($claim->remarks)<div style="grid-column:1 / -1"><div class="text-small text-muted">Remarks</div><div style="font-weight:600">{{ $claim->remarks }}</div></div>@endif
                @if($claim->rejection_reason)<div style="grid-column:1 / -1"><div class="text-small" style="color:var(--sc-red)">Rejection Reason</div><div style="font-weight:600;color:var(--sc-red)">{{ $claim->rejection_reason }}</div></div>@endif
            </div>
        </div>
    </div>

    {{-- Update status --}}
    <div class="card">
        <div class="card-header">Update Status</div>
        <form method="POST" action="{{ route('insurance.claims.status', $claim) }}">
            @csrf
            <div class="card-body" style="display:flex;flex-direction:column;gap:10px">
                <div class="form-group" style="margin:0">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        @foreach(\App\Models\InsuranceClaim::STATUSES as $k => $s)
                        <option value="{{ $k }}" {{ $claim->status === $k ? 'selected' : '' }}>{{ $s['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group" style="margin:0">
                    <label>Approved Amount (₹)</label>
                    <input type="number" name="approved_amount" step="0.01" min="0" value="{{ $claim->approved_amount }}" class="form-control">
                </div>
                <div class="form-group" style="margin:0">
                    <label>Settled Amount (₹)</label>
                    <input type="number" name="settled_amount" step="0.01" min="0" value="{{ $claim->settled_amount }}" class="form-control">
                </div>
                <div class="form-group" style="margin:0">
                    <label>Remarks</label>
                    <textarea name="remarks" rows="2" class="form-control">{{ $claim->remarks }}</textarea>
                </div>
                <div class="form-group" style="margin:0">
                    <label>Rejection Reason</label>
                    <textarea name="rejection_reason" rows="2" class="form-control">{{ $claim->rejection_reason }}</textarea>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%">Update Status</button>
            </div>
        </form>
    </div>
</div>

{{-- Documents --}}
<div class="card">
    <div class="card-header" style="display:flex;align-items:center;justify-content:space-between">
        <span>Documents</span>
        <button type="button" onclick="document.getElementById('uploadForm').style.display = (document.getElementById('uploadForm').style.display==='none'||!document.getElementById('uploadForm').style.display)?'grid':'none'" class="btn btn-outline btn-sm"><i class="fa-solid fa-plus"></i> Upload</button>
    </div>
    <div class="card-body">
        <form id="uploadForm" method="POST" action="{{ route('insurance.documents.upload', $claim) }}" enctype="multipart/form-data"
              style="display:none;background:var(--sc-surface);border-radius:8px;padding:14px;margin-bottom:14px;grid-template-columns:1fr 1fr auto;gap:14px;align-items:end">
            @csrf
            <div class="form-group" style="margin:0">
                <label>Document Type</label>
                <select name="document_type" class="form-control">
                    @foreach(\App\Models\InsuranceDocument::DOCUMENT_TYPES as $k => $lbl)
                    <option value="{{ $k }}">{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group" style="margin:0">
                <label>File (PDF / Image, max 10 MB)</label>
                <input type="file" name="document_file" accept=".pdf,.jpg,.jpeg,.png" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary">Upload</button>
        </form>

        @if($claim->documents->isEmpty())
        <p class="text-small text-muted" style="margin:0">No documents uploaded yet.</p>
        @else
        <div class="table-wrap">
            <table class="sc-table zebra">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>File</th>
                        <th style="text-align:right">Size</th>
                        <th>Uploaded By</th>
                        <th>Date</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($claim->documents as $doc)
                    <tr>
                        <td>{{ $doc->type_label }}</td>
                        <td><a href="{{ asset('storage/'.$doc->file_path) }}" target="_blank">{{ $doc->original_name ?? basename($doc->file_path) }}</a></td>
                        <td style="text-align:right" class="text-muted">{{ $doc->file_size_human }}</td>
                        <td>{{ $doc->uploadedBy?->name ?? '—' }}</td>
                        <td class="text-small text-muted">{{ $doc->created_at->format('d M Y') }}</td>
                        <td style="text-align:right">
                            <form method="POST" action="{{ route('insurance.documents.delete', [$claim, $doc]) }}" onsubmit="return confirm('Remove this document?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-outline btn-sm" style="color:var(--sc-red);border-color:var(--sc-red)"><i class="fa-solid fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>
@endsection
