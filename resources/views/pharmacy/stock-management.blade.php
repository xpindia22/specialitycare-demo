@extends('layouts.app')
@section('title','Stock Management')
@section('page-title','Stock Management')
@section('content')

@include('pharmacy._toolbar')

<div class="page-header">
    <div class="page-header-left">
        <a href="{{ route('pharmacy.dashboard') }}" class="text-small"><i class="fa-solid fa-arrow-left"></i> Pharmacy</a>
        <h1><i class="fa-solid fa-boxes-stacked"></i> Stock Management</h1>
        <div class="text-small text-muted">Clean up expired or unwanted stock, with a full audit trail.</div>
    </div>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:14px">

    {{-- Expired Stock --}}
    <a href="{{ route('pharmacy.expired') }}" class="card" style="text-decoration:none;color:inherit;padding:18px;border-left:4px solid var(--sc-red);transition:transform .15s" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform=''">
        <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:8px">
            <h3 style="margin:0;color:var(--sc-red)"><i class="fa-solid fa-triangle-exclamation"></i> Expired Stock</h3>
            <span class="badge" style="background:var(--sc-red);color:#fff">{{ $expiredCount }}</span>
        </div>
        <p class="text-small text-muted" style="margin:0">Stock past its expiry date. Pick & remove with destroy-method tracking (incineration / returned to supplier / etc.).</p>
        @if($expiredCount > 0)
            <div class="text-small" style="margin-top:8px;color:var(--sc-red);font-weight:600">{{ $expiredCount }} batch(es) need attention →</div>
        @else
            <div class="text-small" style="margin-top:8px;color:var(--sc-green)"><i class="fa-solid fa-circle-check"></i> Nothing expired right now</div>
        @endif
    </a>

    {{-- Bulk Remove --}}
    <a href="{{ route('pharmacy.stock.bulk-remove') }}" class="card" style="text-decoration:none;color:inherit;padding:18px;border-left:4px solid var(--sc-amber);transition:transform .15s" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform=''">
        <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:8px">
            <h3 style="margin:0;color:var(--sc-amber)"><i class="fa-solid fa-trash"></i> Bulk Remove Stock</h3>
            <span class="badge badge-gray">{{ $activeStockCount }} active</span>
        </div>
        <p class="text-small text-muted" style="margin:0">Search + checkbox-select any active stock (damaged batches, wrong entries, returns) and remove with a single reason / method. Not for expired stock — use the Expired page for those.</p>
        @if($expiringCount > 0)
            <div class="text-small" style="margin-top:8px;color:var(--sc-amber)">{{ $expiringCount }} batch(es) expiring in next 90 days</div>
        @endif
    </a>

    {{-- Removal History --}}
    <a href="{{ route('pharmacy.stock.removal-history') }}" class="card" style="text-decoration:none;color:inherit;padding:18px;border-left:4px solid var(--sc-teal);transition:transform .15s" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform=''">
        <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:8px">
            <h3 style="margin:0;color:var(--sc-teal)"><i class="fa-solid fa-clock-rotate-left"></i> Removal History</h3>
            <span class="badge badge-navy">{{ $removalCount30d }} in 30 days</span>
        </div>
        <p class="text-small text-muted" style="margin:0">Audit trail of every expired/removed batch: date, medicine, batch, qty, who removed it, and the reason / method. Filter by date or removal type.</p>
        <div class="text-small" style="margin-top:8px;color:var(--sc-teal);font-weight:600">View full log →</div>
    </a>

</div>

<div class="text-small text-muted" style="margin-top:18px;padding:10px;background:#fafaf6;border-radius:6px">
    <i class="fa-solid fa-info-circle"></i> All removals are <strong>soft-delete</strong> (sets quantity to 0 and marks Inactive). The original row stays in the DB and is reversible from <strong>Edit Stock</strong> (reactivate + set quantity back). Every removal writes a <code>stock_transactions</code> row visible in the Removal History.
</div>

@endsection
