@extends('layouts.app')
@section('title', 'Outstanding Dues')
@section('content')

<div class="page-header">
    <div class="page-header-left">
        <h1>Outstanding Dues</h1>
        <div class="breadcrumb">Reports &rsaquo; Outstanding Dues</div>
    </div>
    <div style="display:flex;gap:8px">
        <a href="{{ route('reports.outstanding-dues.export', request()->query()) }}" class="btn btn-outline">
            <i class="fa-solid fa-file-csv"></i> Export CSV
        </a>
        <button onclick="window.print()" class="btn btn-outline">
            <i class="fa-solid fa-print"></i> Print
        </button>
    </div>
</div>

{{-- Filters --}}
<form method="GET" class="card mb-4">
    <div class="card-body" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;padding:16px 20px">
        <div class="form-group" style="margin:0;min-width:240px">
            <label class="form-label">Search</label>
            <input type="text" name="q" value="{{ $q }}" placeholder="Patient name / JHC ID / IPD No."
                   class="form-control">
        </div>
        <div class="form-group" style="margin:0">
            <label class="form-label">Type</label>
            <select name="type" class="form-control">
                <option value="all" {{ $type==='all' ? 'selected' : '' }}>All (OPD + IPD)</option>
                <option value="opd" {{ $type==='opd' ? 'selected' : '' }}>OPD only</option>
                <option value="ipd" {{ $type==='ipd' ? 'selected' : '' }}>IPD only</option>
            </select>
        </div>
        <div class="form-group" style="margin:0;width:140px">
            <label class="form-label">Older than (days)</label>
            <input type="number" name="min_days" min="0" value="{{ $minDays }}"
                   class="form-control" placeholder="0 = all">
        </div>
        <button type="submit" class="btn btn-primary">
            <i class="fa-solid fa-filter"></i> Filter
        </button>
    </div>
</form>

{{-- Summary cards --}}
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:24px">
    <div class="stat-card">
        <div class="stat-icon navy"><i class="fa-solid fa-file-invoice-dollar"></i></div>
        <div>
            <div class="stat-value">{{ $totals['count'] }}</div>
            <div class="stat-label">Records with outstanding dues</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon teal"><i class="fa-solid fa-receipt"></i></div>
        <div>
            <div class="stat-value">₹{{ number_format($totals['total'], 0) }}</div>
            <div class="stat-label">Total Billed</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red"><i class="fa-solid fa-triangle-exclamation"></i></div>
        <div>
            <div class="stat-value" style="color:var(--sc-red)">₹{{ number_format($totals['due'], 0) }}</div>
            <div class="stat-label">Total Outstanding</div>
        </div>
    </div>
</div>

{{-- Table --}}
<div class="card">
    <div class="table-wrap">
        <table class="sc-table">
            <thead>
                <tr>
                    <th>Patient</th>
                    <th>JHC ID</th>
                    <th>Ref. No.</th>
                    <th>Source</th>
                    <th>Date</th>
                    <th class="text-right">Total</th>
                    <th class="text-right">Paid</th>
                    <th class="text-right">Due</th>
                    <th class="text-center">Age (days)</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($allDues as $row)
                @php
                    $age = $row['days_old'];
                    $ageColor = $age >= 30 ? 'var(--sc-red)' : ($age >= 7 ? 'var(--sc-amber)' : 'var(--sc-muted)');
                @endphp
                <tr>
                    <td class="fw-600">{{ $row['patient'] }}</td>
                    <td class="mono text-small text-muted">{{ $row['jhc_id'] ?? '—' }}</td>
                    <td class="mono text-small">{{ $row['ref_no'] }}</td>
                    <td>
                        <span class="badge {{ str_contains($row['source'], 'IPD') ? 'badge-teal' : 'badge-navy' }}">
                            {{ $row['source'] }}
                        </span>
                    </td>
                    <td class="text-small text-muted">{{ \Carbon\Carbon::parse($row['bill_date'])->format('d M Y') }}</td>
                    <td class="text-right mono">₹{{ number_format($row['total'], 2) }}</td>
                    <td class="text-right mono" style="color:var(--sc-green)">₹{{ number_format($row['paid'], 2) }}</td>
                    <td class="text-right mono fw-600" style="color:var(--sc-red)">₹{{ number_format($row['due'], 2) }}</td>
                    <td class="text-center fw-700" style="color:{{ $ageColor }}">{{ $age }}</td>
                    <td><a href="{{ $row['link'] }}" class="btn btn-sm btn-outline">View</a></td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" style="text-align:center;padding:48px;color:var(--sc-muted)">
                        <i class="fa-solid fa-circle-check" style="font-size:2rem;margin-bottom:8px;display:block;color:var(--sc-green)"></i>
                        No outstanding dues found.
                    </td>
                </tr>
                @endforelse
            </tbody>
            @if($allDues->count())
            <tfoot>
                <tr style="background:var(--sc-bg);font-weight:700">
                    <td colspan="5" style="padding:11px 14px;color:var(--sc-navy)">
                        Total ({{ $totals['count'] }} records)
                    </td>
                    <td class="text-right mono" style="padding:11px 14px">₹{{ number_format($totals['total'], 2) }}</td>
                    <td class="text-right mono" style="padding:11px 14px;color:var(--sc-green)">₹{{ number_format($totals['paid'], 2) }}</td>
                    <td class="text-right mono" style="padding:11px 14px;color:var(--sc-red)">₹{{ number_format($totals['due'], 2) }}</td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>

@push('styles')
<style>
@media print {
    .page-header button, form { display: none !important; }
    .sc-table td, .sc-table th { font-size: 11px; padding: 7px 10px; }
}
</style>
@endpush

@endsection
