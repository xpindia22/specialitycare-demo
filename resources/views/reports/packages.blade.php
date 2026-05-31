@extends('layouts.app')
@section('title', 'Package Usage')
@section('page-title', 'Package Usage Report')
@section('content')

<div class="page-header">
    <div class="page-header-left">
        <h1>Package Usage</h1>
        <div class="breadcrumb"><a href="{{ route('reports.index') }}">Reports</a> &rsaquo; Packages</div>
    </div>
    <a href="{{ route('reports.index') }}" class="btn btn-outline btn-sm"><i class="fa-solid fa-arrow-left"></i> Back</a>
</div>

<form method="GET" class="card mb-3">
    <div class="card-body" style="display:flex;gap:14px;align-items:end;flex-wrap:wrap">
        <div class="form-group" style="margin:0">
            <label class="form-label">From</label>
            <input type="date" name="from" class="form-control" value="{{ $fromDate }}">
        </div>
        <div class="form-group" style="margin:0">
            <label class="form-label">To</label>
            <input type="date" name="to" class="form-control" value="{{ $toDate }}">
        </div>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-filter"></i> Apply</button>
    </div>
</form>

<div class="grid grid-4 mb-3">
    <div class="stat-card">
        <div class="stat-label">Packages In Use</div>
        <div class="stat-value">{{ $summary['packages_in_use'] }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total Uses</div>
        <div class="stat-value">{{ $summary['total_uses'] }}</div>
        <div class="stat-sub">OPD: {{ $summary['opd_uses'] }} &middot; IPD: {{ $summary['ipd_uses'] }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Revenue Collected</div>
        <div class="stat-value">₹{{ number_format($summary['total_revenue'], 0) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Window</div>
        <div class="stat-value" style="font-size:14px">{{ \Carbon\Carbon::parse($fromDate)->format('d M') }} – {{ \Carbon\Carbon::parse($toDate)->format('d M Y') }}</div>
    </div>
</div>

<div class="card">
    <div class="card-header"><i class="fa-solid fa-box"></i> Per-Package Breakdown</div>
    <div class="table-wrap">
        <table class="sc-table">
            <thead>
                <tr>
                    <th>Package</th>
                    <th>Category</th>
                    <th style="text-align:right">Headline Price</th>
                    <th style="text-align:center">OPD Bills</th>
                    <th style="text-align:center">IPD Assigns</th>
                    <th style="text-align:center">Total Uses</th>
                    <th style="text-align:right">Quoted (Total)</th>
                    <th style="text-align:right">Collected</th>
                </tr>
            </thead>
            <tbody>
            @forelse($rows as $r)
            <tr>
                <td class="fw-600">{{ $r->name }}</td>
                <td>{{ \App\Models\BillingPackage::CATEGORIES[$r->category] ?? '—' }}</td>
                <td style="text-align:right">₹{{ number_format($r->package_price, 0) }}</td>
                <td style="text-align:center">{{ $r->bill_count }}</td>
                <td style="text-align:center">{{ $r->adm_count }}</td>
                <td style="text-align:center"><strong>{{ $r->total_uses }}</strong></td>
                <td style="text-align:right">₹{{ number_format($r->bill_total + $r->adm_quoted, 0) }}</td>
                <td style="text-align:right" class="fw-600" style="color:var(--sc-green)">₹{{ number_format($r->total_revenue, 0) }}</td>
            </tr>
            @empty
            <tr><td colspan="8" style="padding:0">
                <x-empty-state
                    icon="fa-box"
                    title="No package usage in this window"
                    message="Try a wider date range, or apply packages on bill creation / admission to populate this report."
                    action="Manage packages"
                    :actionUrl="route('packages.index')" />
            </td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
