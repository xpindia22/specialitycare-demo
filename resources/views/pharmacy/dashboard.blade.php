{{-- FILE: resources/views/pharmacy/dashboard.blade.php --}}
@extends('layouts.app')
@section('title','Pharmacy')
@section('page-title','Pharmacy')
@section('content')

@include('pharmacy._toolbar')
<div class="page-header">
    <div class="page-header-left"><h1>Pharmacy Dashboard</h1><div class="breadcrumb">Medicine stock & dispensing</div></div>
    <a href="{{ route('pharmacy.purchases.create') }}" class="btn btn-primary"><i class="fa-solid fa-truck-ramp-box"></i> New Stock Receipt</a>
    <a href="{{ route('pharmacy.purchases.index') }}" class="btn btn-outline"><i class="fa-solid fa-receipt"></i> Purchases</a>
    <a href="{{ route('pharmacy.stock') }}" class="btn btn-outline"><i class="fa-solid fa-boxes-stacked"></i> Quick Stock Adjustment</a>
    <a href="{{ route('pharmacy.stock-register') }}" class="btn btn-outline"><i class="fa-solid fa-book"></i> Stock Register</a>
    <a href="{{ route('pharmacy.stock-management') }}" class="btn btn-outline" style="border-color:var(--sc-red);color:var(--sc-red)"><i class="fa-solid fa-boxes-stacked"></i> Stock Management</a>
    <a href="{{ route('pharmacy.interactions.index') }}" class="btn btn-outline" style="border-color:var(--sc-amber);color:var(--sc-amber)"><i class="fa-solid fa-triangle-exclamation"></i> Drug Interactions</a>
</div>

@php
    $stillZeroGst = \App\Models\Medicine::where('is_active', true)
        ->where(fn($q) => $q->whereNull('gst_rate_id')->orWhereHas('gstRate', fn($g) => $g->where('rate', 0)))->count();
@endphp
@if($stillZeroGst > 0)
<div class="alert" style="background:#fff3cd;border:1px solid #ffe69c;color:#664d03;padding:10px 14px;border-radius:6px;margin-bottom:14px;display:flex;justify-content:space-between;align-items:center">
    <div><i class="fa-solid fa-triangle-exclamation"></i> <strong>{{ $stillZeroGst }} medicines</strong> are still at GST 0% (the system default). Purchases &amp; GSTR will be inaccurate until you set proper rates.</div>
    <a href="{{ route('pharmacy.medicines.tax-fix') }}" class="btn btn-sm btn-primary"><i class="fa-solid fa-wand-magic-sparkles"></i> Fix in bulk</a>
</div>
@endif

<!-- Stats -->
<div class="grid grid-4 mb-4">
    <div class="stat-card"><div class="stat-icon teal"><i class="fa-solid fa-pills"></i></div><div><div class="stat-value">{{ $totalMedicines }}</div><div class="stat-label">Active Medicines</div></div></div>
    <div class="stat-card"><div class="stat-icon amber"><i class="fa-solid fa-battery-low"></i></div><div><div class="stat-value" style="color:var(--sc-amber)">{{ $lowStock->count() }}</div><div class="stat-label">Low Stock Items</div></div></div>
    <div class="stat-card"><div class="stat-icon red"><i class="fa-solid fa-circle-xmark"></i></div><div><div class="stat-value" style="color:var(--sc-red)">{{ $outOfStock }}</div><div class="stat-label">Out of Stock</div></div></div>
    <div class="stat-card"><div class="stat-icon amber"><i class="fa-solid fa-clock"></i></div><div><div class="stat-value" style="color:var(--sc-amber)">{{ $expiringSoon->count() }}</div><div class="stat-label">Expiring in 90 Days</div></div></div>
</div>

<div class="grid grid-2">
    <!-- PENDING PRESCRIPTIONS -->
    <div class="card">
        <div class="card-header"><i class="fa-solid fa-prescription"></i> Pending Prescriptions
            <span class="badge badge-red" style="margin-left:8px">{{ $pendingPrescriptions->count() }}</span>
        </div>
        @forelse($pendingPrescriptions as $rx)
        @php
            $inStockItems    = $rx->items->filter(fn($i) => $i->medicine?->isInStock() && !$i->is_dispensed);
            $outOfStockItems = $rx->items->filter(fn($i) => !$i->medicine?->isInStock() && !$i->is_dispensed);
        @endphp
        <div style="padding:12px 16px;border-bottom:1px solid var(--sc-border)">
            <div style="display:flex;justify-content:space-between;align-items:flex-start">
                <div>
                    <span class="mono fw-600">{{ $rx->prescription_no }}</span>
                    <span class="text-muted" style="font-size:12px;margin-left:8px">{{ $rx->patient->full_name }} ({{ $rx->patient->jhc_id }})</span>
                    <div style="margin-top:4px;display:flex;flex-wrap:wrap;gap:4px">
                        {{-- Only show in-stock medicines --}}
                        @foreach($inStockItems->take(3) as $item)
                        <span class="badge badge-green" style="font-size:10px">{{ $item->medicine->name }}</span>
                        @endforeach
                        @if($inStockItems->count() > 3)
                            <span class="text-muted text-small">+{{ $inStockItems->count()-3 }} more in stock</span>
                        @endif
                        @if($outOfStockItems->count())
                            <span class="badge badge-amber" style="font-size:10px" title="{{ $outOfStockItems->pluck('medicine.name')->join(', ') }}">
                                ⚠️ {{ $outOfStockItems->count() }} out of stock
                            </span>
                        @endif
                    </div>
                </div>
                <div style="display:flex;gap:6px">
                    <a href="{{ route('prescriptions.print', $rx) }}" target="_blank" class="btn btn-sm btn-outline"><i class="fa-solid fa-print"></i></a>
                    @if($inStockItems->count())
                    <form method="POST" action="{{ route('pharmacy.dispense', $rx) }}" style="margin:0" onsubmit="return confirm('Dispense {{ $inStockItems->count() }} available medicine(s) for {{ $rx->prescription_no }}?')">
                        @csrf
                        {{-- Only dispense in-stock items --}}
                        @foreach($inStockItems as $idx => $item)
                            <input type="hidden" name="items[{{ $idx }}][item_id]" value="{{ $item->id }}">
                            <input type="hidden" name="items[{{ $idx }}][dispense]" value="1">
                        @endforeach
                        <button type="submit" class="btn btn-sm btn-primary">Dispense</button>
                    </form>
                    @else
                    <button class="btn btn-sm btn-outline" disabled style="opacity:.5;cursor:not-allowed" title="No medicines in stock">No Stock</button>
                    @endif
                </div>
            </div>
        </div>
        @empty
        <div class="card-body text-center text-muted" style="padding:30px">No pending prescriptions.</div>
        @endforelse
    </div>

    <!-- PRESCRIBED BUT NOT AVAILABLE -->
    <div class="card">
        <div class="card-header"><i class="fa-solid fa-circle-exclamation" style="color:var(--sc-red)"></i> Prescribed But Not Available
            <span class="badge badge-red" style="margin-left:8px">{{ $unavailablePrescribedMedicines->count() }}</span>
        </div>
        @forelse($unavailablePrescribedMedicines as $row)
        <div style="padding:12px 16px;border-bottom:1px solid var(--sc-border)">
            <div style="display:flex;justify-content:space-between;gap:14px;align-items:flex-start">
                <div style="min-width:0">
                    <div class="fw-600">{{ $row->medicine->name }}</div>
                    <div class="text-small text-muted">
                        {{ $row->medicine->generic_name ?: $row->medicine->dosage_form }}
                    </div>
                    <div style="margin-top:8px;display:flex;flex-direction:column;gap:5px">
                        @foreach($row->items->take(4) as $item)
                            <div class="text-small">
                                <span class="mono">{{ $item->prescription->prescription_no }}</span>
                                <span class="text-muted">· {{ $item->prescription->patient->full_name ?? 'Patient' }}</span>
                                <span class="text-muted">· Qty {{ (int) $item->quantity }}</span>
                            </div>
                        @endforeach
                        @if($row->items->count() > 4)
                            <div class="text-small text-muted">+{{ $row->items->count() - 4 }} more prescription item(s)</div>
                        @endif
                    </div>
                </div>
                <div style="text-align:right;white-space:nowrap">
                    <div class="badge {{ $row->available_quantity > 0 ? 'badge-amber' : 'badge-red' }}">
                        {{ $row->available_quantity > 0 ? 'Insufficient' : 'No Stock' }}
                    </div>
                    <div style="font-size:13px;margin-top:8px"><strong>Need:</strong> {{ $row->needed_quantity }}</div>
                    <div class="text-small text-muted">Available: {{ $row->available_quantity }}</div>
                    <div class="text-small" style="color:var(--sc-red)">Short: {{ $row->shortage_quantity }}</div>
                    <a href="{{ route('pharmacy.stock', ['add_stock' => 1, 'medicine_id' => $row->medicine->id, 'quantity' => $row->shortage_quantity]) }}"
                       class="btn btn-sm btn-primary"
                       style="margin-top:10px">
                        <i class="fa-solid fa-plus"></i> Add Stock
                    </a>
                </div>
            </div>
        </div>
        @empty
        <div class="card-body text-center text-muted" style="padding:30px">All medicines in pending prescriptions are available.</div>
        @endforelse
    </div>
</div>
@endsection
