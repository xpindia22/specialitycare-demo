{{-- FILE: resources/views/billing/index.blade.php --}}
@extends('layouts.app')
@section('title','Billing')
@section('page-title','Billing')
@section('content')
<div class="page-header">
    <div class="page-header-left"><h1>Billing</h1><div class="breadcrumb">Patient bills & payments</div></div>
    <a href="{{ route('billing.create') }}" class="btn btn-primary"><i class="fa-solid fa-file-invoice"></i> Create Bill</a>
    <a href="{{ route('billing.daily') }}" class="btn btn-outline"><i class="fa-solid fa-calendar-day"></i> Daily Report</a>
</div>

<div class="grid grid-4 mb-4">
    <div class="stat-card"><div class="stat-icon teal"><i class="fa-solid fa-indian-rupee-sign"></i></div><div><div class="stat-value">₹{{ number_format($todayCollection) }}</div><div class="stat-label">Today's Collection</div></div></div>
    <div class="stat-card"><div class="stat-icon navy"><i class="fa-solid fa-chart-line"></i></div><div><div class="stat-value">₹{{ number_format($todaySummary['billed']) }}</div><div class="stat-label">Today's Billing</div></div></div>
    <div class="stat-card"><div class="stat-icon amber"><i class="fa-solid fa-clock"></i></div><div><div class="stat-value">{{ $pendingBills->count() }}</div><div class="stat-label">Pending Bills</div></div></div>
    <div class="stat-card"><div class="stat-icon red"><i class="fa-solid fa-circle-exclamation"></i></div><div><div class="stat-value">₹{{ number_format($pendingAmount) }}</div><div class="stat-label">Total Pending Amount</div></div></div>
</div>

<div class="card mb-4">
    <div class="card-header"><i class="fa-solid fa-scale-balanced"></i> Today's Billing Position</div>
    <div class="card-body">
        <div class="grid grid-4">
            <div><div class="text-muted text-small">Billed</div><div class="fw-700">₹{{ number_format($todaySummary['billed'], 2) }}</div></div>
            <div><div class="text-muted text-small">Collected</div><div class="fw-700" style="color:var(--sc-green)">₹{{ number_format($todaySummary['collected'], 2) }}</div></div>
            <div><div class="text-muted text-small">Due From Today's Bills</div><div class="fw-700" style="color:var(--sc-red)">₹{{ number_format($todaySummary['due'], 2) }}</div></div>
            <div><div class="text-muted text-small">Net Collected After Discounts</div><div class="fw-700" style="color:{{ $todaySummary['net'] >= 0 ? 'var(--sc-navy)' : 'var(--sc-red)' }}">₹{{ number_format($todaySummary['net'], 2) }}</div></div>
        </div>
        <div class="text-small text-muted" style="margin-top:10px">True profit/loss needs expense and purchase-cost tracking. This section shows cash collected, billing, discounts, and dues for daily control.</div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <i class="fa-solid fa-hourglass-half"></i> Pending Bills
        <span class="badge badge-amber" style="margin-left:auto">{{ $pendingBills->count() }}</span>
    </div>
    <div class="table-wrap">
        <table class="sc-table">
            <thead><tr><th>Bill No.</th><th>Patient</th><th>Date</th><th>Total</th><th>Paid</th><th>Due</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
            @forelse($pendingBills as $b)
            <tr>
                <td class="mono fw-600"><a href="{{ route('billing.show', $b) }}" style="color:var(--sc-navy);text-decoration:none">{{ $b->bill_no }}</a></td>
                <td>{{ $b->patient?->full_name ?? '—' }}<div class="text-small text-muted mono">{{ $b->patient?->jhc_id ?? '' }}</div></td>
                <td>{{ $b->bill_date->format('d M Y') }}</td>
                <td class="fw-600">₹{{ number_format($b->total_amount, 2) }}</td>
                <td style="color:var(--sc-green)">₹{{ number_format($b->paid_amount, 2) }}</td>
                <td style="color:var(--sc-red);font-weight:700">₹{{ number_format($b->due_amount, 2) }}</td>
                <td><span class="badge {{ $b->status === 'Partial' ? 'badge-amber' : 'badge-red' }}">{{ $b->status }}</span></td>
                <td><a href="{{ route('billing.show', $b) }}" class="btn btn-sm btn-primary"><i class="fa-solid fa-money-bill"></i> Pay / Change</a></td>
            </tr>
            @empty
            <tr><td colspan="8" class="text-center text-muted" style="padding:24px">No pending bills.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header"><i class="fa-solid fa-file-invoice-dollar"></i> All Bills</div>
    <div class="card-body" style="padding:12px 20px;border-bottom:1px solid var(--sc-border)">
        <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap">
            <input type="text" name="search" class="form-control" style="width:220px" placeholder="Bill no, JHC ID, name..." value="{{ request('search') }}">
            <input type="date" name="date" class="form-control" style="width:150px" value="{{ request('date') }}">
            <select name="status" class="form-control" style="width:140px">
                <option value="">All Status</option>
                @foreach(['Unpaid','Partial','Paid','Refunded'] as $s)
                    <option value="{{ $s }}" {{ request('status')==$s ? 'selected':'' }}>{{ $s }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-primary btn-sm">Filter</button>
            @if(request()->hasAny(['search','date','status']))<a href="{{ route('billing.index') }}" class="btn btn-outline btn-sm">Clear</a>@endif
        </form>
    </div>
    <div class="table-wrap">
        <table class="sc-table">
            <thead><tr><th>Bill No.</th><th>Patient</th><th>Date</th><th>Items</th><th>Total</th><th>Paid</th><th>Due</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
            @forelse($bills as $b)
            <tr>
                <td class="mono fw-600">{{ $b->bill_no }}</td>
                <td>
                    <a href="{{ route('patients.show', $b->patient) }}" style="color:var(--sc-navy);font-weight:600;text-decoration:none">{{ $b->patient->full_name }}</a>
                    <div class="text-small text-muted mono">{{ $b->patient->jhc_id }}</div>
                </td>
                <td>{{ $b->bill_date->format('d M Y') }}</td>
                <td class="text-muted">{{ $b->items->count() }} item(s)</td>
                <td class="fw-600">₹{{ number_format($b->total_amount,2) }}</td>
                <td style="color:var(--sc-green)">₹{{ number_format($b->paid_amount,2) }}</td>
                <td class="{{ $b->due_amount > 0 ? 'stock-critical' : '' }}">₹{{ number_format($b->due_amount,2) }}</td>
                <td>
                    <span class="badge {{ $b->status==='Paid' ? 'badge-green' : ($b->status==='Partial' ? 'badge-amber' : 'badge-red') }}">{{ $b->status }}</span>
                </td>
                <td>
                    <div style="display:flex;gap:6px">
                        <a href="{{ route('billing.show', $b) }}" class="btn btn-sm btn-outline">View</a>
                        <a href="{{ route('billing.print', $b) }}" class="btn btn-sm btn-outline" target="_blank"><i class="fa-solid fa-print"></i></a>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="9" style="padding:0">
                <x-empty-state
                    icon="fa-file-invoice-dollar"
                    title="No bills match these filters"
                    message="Try a different date range or status — or create a new bill."
                    action="New bill"
                    :actionUrl="route('billing.create')" />
            </td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $bills->links() }}</div>
</div>
@endsection
