{{-- FILE: resources/views/reports/referral-performance.blade.php --}}
@extends('layouts.app')
@section('title','Referral Performance')
@section('page-title','Referral Performance Report')
@section('content')

<div class="page-header">
    <div class="page-header-left">
        <h1>Referral Performance Report</h1>
        <div class="breadcrumb">Reports &mdash; Referral Source Performance</div>
    </div>
    <div class="page-header-right">
        <a href="{{ route('reports.index') }}" class="btn btn-outline"><i class="fa-solid fa-arrow-left"></i> All Reports</a>
        <a href="{{ route('referrals.index') }}" class="btn btn-outline no-print"><i class="fa-solid fa-handshake"></i> Manage Referrals</a>
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
            <a href="{{ route('reports.referral-performance') }}" class="btn btn-outline">Reset</a>
        </form>
    </div>
</div>

@php
    $totalPatients    = $rows->sum('patients');
    $totalBillAmount  = $rows->sum('bill_total');
    $totalIncentiveDue = $rows->sum('incentive_due');
    $totalPaid        = $rows->sum('paid_amount');
    $totalBalance     = $rows->sum('balance');
@endphp

{{-- Summary cards --}}
<div class="grid" style="grid-template-columns:repeat(5,1fr);gap:16px;margin-bottom:20px">
    <div class="card">
        <div class="card-body" style="padding:16px">
            <div class="form-label">Active Sources</div>
            <div class="fw-700 mono" style="font-size:26px;color:var(--sc-navy)">{{ $rows->count() }}</div>
        </div>
    </div>
    <div class="card">
        <div class="card-body" style="padding:16px">
            <div class="form-label">Patients Referred</div>
            <div class="fw-700 mono" style="font-size:26px;color:var(--sc-teal)">{{ $totalPatients }}</div>
        </div>
    </div>
    <div class="card">
        <div class="card-body" style="padding:16px">
            <div class="form-label">Total Bill Amount</div>
            <div class="fw-700 mono" style="font-size:22px;color:var(--sc-green)">&#8377;{{ number_format($totalBillAmount,2) }}</div>
        </div>
    </div>
    <div class="card">
        <div class="card-body" style="padding:16px">
            <div class="form-label">Incentive Due</div>
            <div class="fw-700 mono" style="font-size:22px;color:var(--sc-amber)">&#8377;{{ number_format($totalIncentiveDue,2) }}</div>
        </div>
    </div>
    <div class="card">
        <div class="card-body" style="padding:16px">
            <div class="form-label">Balance Payable</div>
            <div class="fw-700 mono" style="font-size:22px;color:#dc2626">&#8377;{{ number_format($totalBalance,2) }}</div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <i class="fa-solid fa-handshake"></i>
        Referral Performance &mdash; {{ \Carbon\Carbon::parse($fromDate)->format('d M Y') }} to {{ \Carbon\Carbon::parse($toDate)->format('d M Y') }}
        <div class="card-actions">
            <span class="badge badge-navy">{{ $rows->count() }} sources</span>
        </div>
    </div>
    <div class="table-wrap">
        <table class="sc-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Referral Source</th>
                    <th>Type</th>
                    <th class="text-center">Patients</th>
                    <th class="text-right">IPD Bill</th>
                    <th class="text-right">OPD Bill</th>
                    <th class="text-right">Total Bill</th>
                    <th>Incentive Rate</th>
                    <th class="text-right">Incentive Due</th>
                    <th class="text-right">Paid</th>
                    <th class="text-right">Balance</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $i => $row)
                @php
                    $rateLabel = $row->source->rate_type === 'percent'
                        ? number_format($row->source->rate_value, 2) . '%'
                        : ($row->source->rate_type === 'fixed'
                            ? '&#8377;' . number_format($row->source->rate_value, 2) . '/patient'
                            : '—');
                    $balancePct = $row->incentive_due > 0
                        ? round(($row->paid_amount / $row->incentive_due) * 100)
                        : 0;
                @endphp
                <tr>
                    <td class="text-muted mono">{{ $i + 1 }}</td>
                    <td>
                        <div class="fw-600">{{ $row->source->name }}</div>
                        @if($row->source->contact_person)
                        <div class="text-small text-muted">{{ $row->source->contact_person }}</div>
                        @endif
                    </td>
                    <td><span class="badge badge-navy">{{ $row->source->type_label }}</span></td>
                    <td class="text-center">
                        <span class="badge badge-teal">{{ $row->patients }}</span>
                    </td>
                    <td class="text-right mono">
                        @if($row->ipd_total > 0) &#8377;{{ number_format($row->ipd_total,2) }}
                        @else <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="text-right mono">
                        @if($row->opd_total > 0) &#8377;{{ number_format($row->opd_total,2) }}
                        @else <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="text-right mono fw-600">&#8377;{{ number_format($row->bill_total,2) }}</td>
                    <td>
                        @if($row->source->rate_type)
                        <span class="badge badge-green">{!! $rateLabel !!}</span>
                        @else
                        <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="text-right mono fw-600" style="color:var(--sc-amber)">
                        @if($row->incentive_due > 0) &#8377;{{ number_format($row->incentive_due,2) }}
                        @else <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="text-right mono" style="color:var(--sc-green)">
                        @if($row->paid_amount > 0) &#8377;{{ number_format($row->paid_amount,2) }}
                        @else <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="text-right mono fw-600" style="color:{{ $row->balance > 0 ? '#dc2626' : 'var(--sc-green)' }}">
                        @if($row->balance > 0) &#8377;{{ number_format($row->balance,2) }}
                        @else <span style="color:var(--sc-green)">Settled</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="11" class="text-center text-muted" style="padding:40px">
                        No referral data for the selected period.
                    </td>
                </tr>
                @endforelse
            </tbody>
            @if($rows->isNotEmpty())
            <tfoot>
                <tr style="background:var(--sc-navy);color:#fff;font-weight:700">
                    <td colspan="3" class="fw-700">Grand Total</td>
                    <td class="text-center">{{ $totalPatients }}</td>
                    <td class="text-right mono">&#8377;{{ number_format($rows->sum('ipd_total'),2) }}</td>
                    <td class="text-right mono">&#8377;{{ number_format($rows->sum('opd_total'),2) }}</td>
                    <td class="text-right mono">&#8377;{{ number_format($totalBillAmount,2) }}</td>
                    <td>—</td>
                    <td class="text-right mono">&#8377;{{ number_format($totalIncentiveDue,2) }}</td>
                    <td class="text-right mono">&#8377;{{ number_format($totalPaid,2) }}</td>
                    <td class="text-right mono">&#8377;{{ number_format($totalBalance,2) }}</td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>

@endsection
