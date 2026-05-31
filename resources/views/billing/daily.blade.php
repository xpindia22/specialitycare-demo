{{-- FILE: resources/views/billing/daily.blade.php --}}
@extends('layouts.app')
@section('title','Daily Report')
@section('page-title','Daily Collection Report')
@section('content')
<div class="page-header">
    <div class="page-header-left">
        <h1>Daily Collection Report</h1>
        <div class="breadcrumb">{{ $date->format('l, d F Y') }}</div>
    </div>
    <button onclick="window.print()" class="btn btn-outline no-print"><i class="fa-solid fa-print"></i> Print</button>
</div>

<form method="GET" class="card mb-4 no-print" style="padding:14px 20px">
    <div style="display:flex;gap:10px;align-items:center">
        <label class="form-label" style="margin:0;white-space:nowrap">Select Date:</label>
        <input type="date" name="date" class="form-control" style="width:200px" value="{{ $date->toDateString() }}">
        <button type="submit" class="btn btn-primary btn-sm">View Report</button>
    </div>
</form>

@php
    // Bank deposit due = physical cash on hand (cheques add here when supported).
    // UPI / Card auto-settle to the bank so they're excluded from the deposit total.
    $bankDeposit = (float) ($summary['cash'] ?? 0) + (float) ($summary['cheque'] ?? 0);
@endphp
<div class="grid grid-4 mb-4">
    <div class="stat-card"><div class="stat-icon teal"><i class="fa-solid fa-file-invoice"></i></div><div><div class="stat-value">{{ $summary['count'] }}</div><div class="stat-label">Bills Generated</div></div></div>
    <div class="stat-card"><div class="stat-icon green"><i class="fa-solid fa-indian-rupee-sign"></i></div><div><div class="stat-value">₹{{ number_format($summary['paid']) }}</div><div class="stat-label">Collected Today</div></div></div>
    <div class="stat-card" style="border:1px solid rgba(245,166,35,.35);background:rgba(245,166,35,.04)">
        <div class="stat-icon amber"><i class="fa-solid fa-vault"></i></div>
        <div>
            <div class="stat-value">₹{{ number_format($bankDeposit) }}</div>
            <div class="stat-label">Bank Deposit Due</div>
            <div class="stat-sub text-muted" style="font-size:11px">Cash on hand{{ ($summary['cheque'] ?? 0) > 0 ? ' + cheques' : '' }}</div>
        </div>
    </div>
    <div class="stat-card"><div class="stat-icon red"><i class="fa-solid fa-clock"></i></div><div><div class="stat-value">₹{{ number_format($summary['due']) }}</div><div class="stat-label">Due From Bills</div></div></div>
</div>

<div class="grid grid-2 mb-4">
    <div class="card">
        <div class="card-header"><i class="fa-solid fa-chart-pie"></i> Payment Mode Breakdown</div>
        <div class="card-body">
            @foreach(['cash' => ['Cash', 'fa-money-bill', 'teal'], 'card' => ['Card/POS', 'fa-credit-card', 'navy'], 'upi' => ['UPI', 'fa-mobile-screen', 'green'], 'cheque' => ['Cheque', 'fa-money-check', 'amber']] as $key => [$label,$icon,$color])
            @continue($key === 'cheque' && ($summary['cheque'] ?? 0) == 0)
            <div style="display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid var(--sc-border)">
                <div class="stat-icon {{ $color }}" style="width:36px;height:36px;font-size:14px;flex-shrink:0"><i class="fa-solid {{ $icon }}"></i></div>
                <div style="flex:1"><div class="fw-600">{{ $label }}</div></div>
                <div class="fw-700" style="font-size:18px">₹{{ number_format($summary[$key]) }}</div>
            </div>
            @endforeach
        </div>
    </div>
    <div class="card">
        <div class="card-header"><i class="fa-solid fa-chart-bar"></i> Summary</div>
        <div class="card-body">
            <table style="width:100%;border-collapse:collapse">
                <tr style="border-bottom:1px solid var(--sc-border)"><td style="padding:8px 0;color:var(--sc-muted)">Total Billed</td><td style="text-align:right;font-weight:600">₹{{ number_format($summary['total']) }}</td></tr>
                <tr style="border-bottom:1px solid var(--sc-border)"><td style="padding:8px 0;color:var(--sc-green)">Amount Collected Today</td><td style="text-align:right;font-weight:600;color:var(--sc-green)">₹{{ number_format($summary['paid']) }}</td></tr>
                <tr style="border-bottom:1px solid var(--sc-border)"><td style="padding:8px 0;color:var(--sc-muted)">Discounts Given</td><td style="text-align:right;font-weight:600">₹{{ number_format($summary['discount']) }}</td></tr>
                <tr style="border-bottom:1px solid var(--sc-border)"><td style="padding:8px 0;color:var(--sc-navy)">Net Collected After Discounts</td><td style="text-align:right;font-weight:700;color:var(--sc-navy)">₹{{ number_format($summary['net']) }}</td></tr>
                <tr><td style="padding:8px 0;color:var(--sc-red)">Outstanding From Bills</td><td style="text-align:right;font-weight:600;color:var(--sc-red)">₹{{ number_format($summary['due']) }}</td></tr>
            </table>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><i class="fa-solid fa-list"></i> Bill Details — {{ $date->format('d M Y') }}</div>
    <div class="table-wrap">
        <table class="sc-table">
            <thead>
                <tr><th>Bill No.</th><th>Patient</th><th>Items</th><th class="text-right">Total</th><th class="text-right">Paid</th><th class="text-right">Due</th><th>Status</th><th class="no-print">Actions</th></tr>
            </thead>
            <tbody>
            @forelse($bills as $b)
            <tr>
                <td class="mono fw-600">{{ $b->bill_no }}</td>
                <td>{{ $b->patient->full_name }}<div class="text-small text-muted mono">{{ $b->patient->jhc_id }}</div></td>
                <td class="text-muted">{{ $b->items->count() }} items</td>
                <td class="text-right fw-600">₹{{ number_format($b->total_amount, 2) }}</td>
                <td class="text-right" style="color:var(--sc-green)">₹{{ number_format($b->paid_amount, 2) }}</td>
                <td class="text-right" style="{{ $b->due_amount > 0 ? 'color:var(--sc-red)' : '' }}">₹{{ number_format($b->due_amount, 2) }}</td>
                <td><span class="badge {{ $b->status==='Paid'?'badge-green':($b->status==='Partial'?'badge-amber':'badge-red') }}">{{ $b->status }}</span></td>
                <td class="no-print"><a href="{{ route('billing.show', $b) }}" class="btn btn-sm btn-outline">View / Pay</a></td>
            </tr>
            @empty
            <tr><td colspan="8" class="text-center text-muted" style="padding:30px">No bills for this date.</td></tr>
            @endforelse
            </tbody>
            @if($bills->count())
            <tfoot>
                <tr style="background:var(--sc-bg);font-weight:700">
                    <td colspan="3" style="padding:10px 14px">TOTAL</td>
                    <td class="text-right" style="padding:10px 14px">₹{{ number_format($bills->sum('total_amount'), 2) }}</td>
                    <td class="text-right" style="padding:10px 14px;color:var(--sc-green)">₹{{ number_format($bills->sum('paid_amount'), 2) }}</td>
                    <td class="text-right" style="padding:10px 14px;color:var(--sc-red)">₹{{ number_format($bills->sum('due_amount'), 2) }}</td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>
@endsection
