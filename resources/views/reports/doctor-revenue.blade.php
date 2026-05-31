{{-- FILE: resources/views/reports/doctor-revenue.blade.php --}}
@extends('layouts.app')
@section('title','Doctor-wise Revenue')
@section('page-title','Doctor Revenue Report')
@section('content')

<div class="page-header">
    <div class="page-header-left">
        <h1>Doctor Revenue Report</h1>
        <div class="breadcrumb">Reports &mdash; Doctor-wise Revenue</div>
    </div>
    <div class="page-header-right">
        <a href="{{ route('reports.index') }}" class="btn btn-outline"><i class="fa-solid fa-arrow-left"></i> All Reports</a>
        <button onclick="window.print()" class="btn btn-outline no-print"><i class="fa-solid fa-print"></i> Print</button>
    </div>
</div>

{{-- Date range filter --}}
<div class="card mb-4 no-print">
    <div class="card-body" style="padding:14px 20px">
        <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end">
            <div>
                <label class="form-label" style="margin-bottom:4px">From</label>
                <input type="date" name="from" class="form-control" value="{{ $fromDate }}" style="width:150px">
            </div>
            <div>
                <label class="form-label" style="margin-bottom:4px">To</label>
                <input type="date" name="to" class="form-control" value="{{ $toDate }}" style="width:150px">
            </div>
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-filter"></i> Apply</button>
            <a href="{{ route('reports.doctor-revenue') }}" class="btn btn-outline">Reset</a>
        </form>
    </div>
</div>

@php
    $grandOpdBilled  = $rows->sum('opd_billed');
    $grandOpdPaid    = $rows->sum('opd_paid');
    $grandIpdCharges = $rows->sum('ipd_charges');
    $grandIpdPaid    = $rows->sum('ipd_payments');
    $grandTotal      = $rows->sum('total');
@endphp

{{-- Summary cards --}}
<div class="grid" style="grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:20px">
    <div class="card">
        <div class="card-body" style="padding:16px">
            <div class="form-label">Total Doctors</div>
            <div class="fw-700 mono" style="font-size:26px;color:var(--sc-navy)">{{ $rows->count() }}</div>
        </div>
    </div>
    <div class="card">
        <div class="card-body" style="padding:16px">
            <div class="form-label">OPD Billed</div>
            <div class="fw-700 mono" style="font-size:22px;color:var(--sc-teal)">&#8377;{{ number_format($grandOpdBilled,2) }}</div>
        </div>
    </div>
    <div class="card">
        <div class="card-body" style="padding:16px">
            <div class="form-label">IPD Charges</div>
            <div class="fw-700 mono" style="font-size:22px;color:var(--sc-green)">&#8377;{{ number_format($grandIpdCharges,2) }}</div>
        </div>
    </div>
    <div class="card">
        <div class="card-body" style="padding:16px">
            <div class="form-label">Grand Total</div>
            <div class="fw-700 mono" style="font-size:22px;color:var(--sc-amber)">&#8377;{{ number_format($grandTotal,2) }}</div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <i class="fa-solid fa-user-doctor"></i>
        Doctor-wise Revenue &mdash; {{ \Carbon\Carbon::parse($fromDate)->format('d M Y') }} to {{ \Carbon\Carbon::parse($toDate)->format('d M Y') }}
        <div class="card-actions">
            <span class="badge badge-navy">{{ $rows->count() }} doctors</span>
        </div>
    </div>
    <div class="table-wrap">
        <table class="sc-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Doctor Name</th>
                    <th>Specialization</th>
                    <th class="text-center">OPD Bills</th>
                    <th class="text-right">OPD Billed</th>
                    <th class="text-right">OPD Collected</th>
                    <th class="text-center">IPD Cases</th>
                    <th class="text-right">IPD Charges</th>
                    <th class="text-right">IPD Payments</th>
                    <th class="text-right" style="background:var(--sc-navy);color:#fff">Total Revenue</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $i => $row)
                <tr>
                    <td class="text-muted mono">{{ $i + 1 }}</td>
                    <td class="fw-600">{{ $row->doctor_name }}</td>
                    <td>
                        @if($row->specialization)
                        <span class="badge badge-teal">{{ $row->specialization }}</span>
                        @else
                        <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="text-center">
                        @if($row->opd_count > 0)
                        <span class="badge badge-navy">{{ $row->opd_count }}</span>
                        @else <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="text-right mono">
                        @if($row->opd_billed > 0) &#8377;{{ number_format($row->opd_billed,2) }}
                        @else <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="text-right mono">
                        @if($row->opd_paid > 0) &#8377;{{ number_format($row->opd_paid,2) }}
                        @else <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="text-center">
                        @if($row->ipd_admissions > 0)
                        <span class="badge badge-green">{{ $row->ipd_admissions }}</span>
                        @else <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="text-right mono">
                        @if($row->ipd_charges > 0) &#8377;{{ number_format($row->ipd_charges,2) }}
                        @else <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="text-right mono">
                        @if($row->ipd_payments > 0) &#8377;{{ number_format($row->ipd_payments,2) }}
                        @else <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="text-right mono fw-700" style="background:#f0f4ff">
                        &#8377;{{ number_format($row->total,2) }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" class="text-center text-muted" style="padding:40px">
                        No revenue data for the selected period.
                    </td>
                </tr>
                @endforelse
            </tbody>
            @if($rows->isNotEmpty())
            <tfoot>
                <tr style="background:var(--sc-navy);color:#fff;font-weight:700">
                    <td colspan="3" class="fw-700">Grand Total</td>
                    <td class="text-center">{{ $rows->sum('opd_count') }}</td>
                    <td class="text-right mono">&#8377;{{ number_format($grandOpdBilled,2) }}</td>
                    <td class="text-right mono">&#8377;{{ number_format($grandOpdPaid,2) }}</td>
                    <td class="text-center">{{ $rows->sum('ipd_admissions') }}</td>
                    <td class="text-right mono">&#8377;{{ number_format($grandIpdCharges,2) }}</td>
                    <td class="text-right mono">&#8377;{{ number_format($grandIpdPaid,2) }}</td>
                    <td class="text-right mono">&#8377;{{ number_format($grandTotal,2) }}</td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>

@endsection
