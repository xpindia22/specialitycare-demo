@extends('layouts.app')
@section('title', 'Stock Register')
@section('page-title', 'Stock Register')
@push('styles')
<style>
    .stock-register-header {
        align-items: center;
        flex-wrap: wrap;
    }

    .stock-register-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        justify-content: flex-end;
    }

    .stock-register-filters {
        display: grid;
        grid-template-columns: minmax(260px, 1fr) 160px 160px 160px auto;
        gap: 12px;
        align-items: end;
    }

    .stock-register-filters .form-group {
        margin: 0;
        min-width: 0;
    }

    .stock-register-filters .select2-container {
        width: 100% !important;
    }

    .stock-register-filters .filter-actions {
        display: flex;
        gap: 10px;
    }

    .stock-register-log-card {
        border-color: var(--sc-border);
        box-shadow: 0 6px 24px rgba(15,31,61,.08);
    }

    .stock-register-log-card .card-header {
        background: var(--sc-surface);
        border-bottom: 1px solid var(--sc-border);
    }

    .stock-register-table-wrap {
        width: 100%;
        overflow-x: auto;
        border-top: 1px solid var(--sc-border);
        background: var(--sc-bg);
    }

    .stock-register-table {
        width: 100%;
        min-width: 1040px;
        table-layout: fixed;
        border-collapse: separate;
        border-spacing: 0;
        font-size: 13px;
    }

    .stock-register-table th,
    .stock-register-table td {
        padding: 14px 16px;
        line-height: 1.45;
        vertical-align: top;
    }

    .stock-register-table thead th {
        position: sticky;
        top: 0;
        z-index: 1;
        background: var(--sc-bg);
        border-bottom: 2px solid var(--sc-border);
        color: var(--sc-muted);
        font-size: 11px;
        font-weight: 700;
        letter-spacing: .5px;
        text-transform: uppercase;
    }

    .stock-register-table tbody td {
        border-bottom: 1px solid var(--sc-border);
        background: var(--sc-surface);
    }

    .stock-register-table tbody tr:nth-child(even) td {
        background: rgba(240,244,248,.45);
    }

    .stock-register-table tbody tr:hover td {
        background: rgba(10,180,180,.035);
    }

    .stock-register-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .stock-register-table .col-index { width: 4%; }
    .stock-register-table .col-date { width: 10%; }
    .stock-register-table .col-medicine { width: 20%; }
    .stock-register-table .col-type { width: 10%; }
    .stock-register-table .col-qty { width: 7%; }
    .stock-register-table .col-balance { width: 8%; }
    .stock-register-table .col-batch { width: 10%; }
    .stock-register-table .col-reference { width: 11%; }
    .stock-register-table .col-user { width: 13%; }

    .stock-register-table .index-cell,
    .stock-register-table .date-cell,
    .stock-register-table .type-cell,
    .stock-register-table .qty-cell,
    .stock-register-table .balance-cell,
    .stock-register-table .batch-cell {
        white-space: nowrap;
    }

    /* User cell — wrap long full names like "Dr Robert James" instead of overflowing */
    .stock-register-table .user-cell {
        white-space: normal;
        overflow-wrap: anywhere;
        word-break: break-word;
    }

    .stock-register-table .index-cell {
        text-align: center;
    }

    .stock-register-table .qty-cell,
    .stock-register-table .balance-cell {
        text-align: right;
        font-variant-numeric: tabular-nums;
    }

    .stock-register-table .date-main {
        color: var(--sc-text);
        font-size: 13px;
        font-weight: 600;
    }

    .stock-register-table .date-time {
        margin-top: 2px;
        font-size: 11.5px;
    }

    .stock-register-table .medicine-name {
        color: var(--sc-navy);
        font-size: 13.5px;
        font-weight: 700;
        overflow-wrap: anywhere;
    }

    .stock-register-table .medicine-generic {
        margin-top: 2px;
        font-size: 11.5px;
        overflow-wrap: anywhere;
    }

    .stock-register-table .cell-reference {
        min-width: 0;
        overflow-wrap: anywhere;
        word-break: break-word;
    }

    .stock-register-table .cell-reference > a,
    .stock-register-table .cell-reference > span {
        display: block;
        max-width: 100%;
    }

    .stock-register-table .badge {
        max-width: 100%;
        justify-content: center;
        white-space: nowrap;
        min-width: 94px;
    }

    .stock-register-table .qty-cell span,
    .stock-register-table .balance-cell span {
        display: inline-block;
        min-width: 54px;
    }

    .stock-register-table .empty-cell {
        padding: 56px 20px;
        text-align: center;
        color: var(--sc-muted);
    }

    @media (max-width: 1180px) {
        .stock-register-filters {
            grid-template-columns: minmax(240px, 1fr) repeat(3, minmax(140px, 160px));
        }

        .stock-register-filters .btn {
            justify-content: center;
        }
    }

    @media (max-width: 768px) {
        .stock-register-header {
            align-items: stretch;
        }

        .stock-register-actions {
            width: 100%;
            justify-content: flex-start;
        }

        .stock-register-actions .btn {
            flex: 1 1 180px;
            justify-content: center;
        }

        .stock-register-filters {
            grid-template-columns: 1fr 1fr;
        }

        .stock-register-filters .filter-medicine,
        .stock-register-filters .filter-actions {
            grid-column: 1 / -1;
        }

        .stock-register-filters .filter-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
    }

    @media (max-width: 520px) {
        .stock-register-filters {
            grid-template-columns: 1fr;
        }

        .stock-register-filters .filter-actions {
            grid-template-columns: 1fr;
        }

        .grid-4.stock-register-summary {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush
@section('content')

@include('pharmacy._toolbar')

<div class="page-header stock-register-header">
    <div class="page-header-left">
        <h1>Stock Register</h1>
        <div class="breadcrumb">Complete log of all stock-in and dispensing transactions</div>
    </div>
    <div class="stock-register-actions">
        <a href="{{ route('pharmacy.stock') }}" class="btn btn-outline">
            <i class="fa-solid fa-boxes-stacked"></i> Stock Overview
        </a>
        <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="btn btn-outline" style="color:var(--sc-green);border-color:rgba(39,196,122,.5)">
            <i class="fa-solid fa-download"></i> Export CSV
        </a>
    </div>
</div>

{{-- Summary cards --}}
<div class="grid grid-4 stock-register-summary mb-4">
    <div class="stat-card">
        <div class="stat-icon green"><i class="fa-solid fa-arrow-down-to-bracket"></i></div>
        <div>
            <div class="stat-value" style="color:var(--sc-green)">{{ number_format($summary['stock_in']) }}</div>
            <div class="stat-label">Units Received (period)</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red"><i class="fa-solid fa-pills"></i></div>
        <div>
            <div class="stat-value" style="color:var(--sc-red)">{{ number_format($summary['dispensed']) }}</div>
            <div class="stat-label">Units Dispensed</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon amber"><i class="fa-solid fa-arrows-rotate"></i></div>
        <div>
            <div class="stat-value" style="color:var(--sc-amber)">{{ number_format($summary['adjustments']) }}</div>
            <div class="stat-label">Adjustments / Returns</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon teal"><i class="fa-solid fa-list-check"></i></div>
        <div>
            <div class="stat-value">{{ number_format($summary['total_txns']) }}</div>
            <div class="stat-label">Total Transactions</div>
        </div>
    </div>
</div>

{{-- Filters --}}
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="stock-register-filters">
            <div class="form-group filter-medicine">
                <label class="form-label">Medicine</label>
                <select name="medicine_id" class="form-control select2" style="width:100%">
                    <option value="">All Medicines</option>
                    @foreach($medicineList as $m)
                        <option value="{{ $m->id }}" {{ request('medicine_id') == $m->id ? 'selected' : '' }}>{{ $m->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Type</label>
                <select name="type" class="form-control">
                    <option value="">All Types</option>
                    <option value="stock_in"   {{ request('type') == 'stock_in'   ? 'selected' : '' }}>Stock In</option>
                    <option value="dispensed"  {{ request('type') == 'dispensed'  ? 'selected' : '' }}>Dispensed</option>
                    <option value="adjustment" {{ request('type') == 'adjustment' ? 'selected' : '' }}>Adjustment</option>
                    <option value="return"     {{ request('type') == 'return'     ? 'selected' : '' }}>Return</option>
                    <option value="expired"    {{ request('type') == 'expired'    ? 'selected' : '' }}>Expired</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">From Date</label>
                <input type="date" name="from" class="form-control" value="{{ request('from', now()->startOfMonth()->toDateString()) }}">
            </div>
            <div class="form-group">
                <label class="form-label">To Date</label>
                <input type="date" name="to" class="form-control" value="{{ request('to', now()->toDateString()) }}">
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-magnifying-glass"></i> Filter</button>
                <a href="{{ route('pharmacy.stock-register') }}" class="btn btn-outline">Reset</a>
            </div>
        </form>
    </div>
</div>

{{-- Transactions table --}}
<div class="card stock-register-log-card">
    <div class="card-header">
        <i class="fa-solid fa-book"></i> Transaction Log
        <span class="badge badge-navy" style="margin-left:8px">{{ $transactions->total() }} records</span>
    </div>
    <div class="stock-register-table-wrap">
        <table class="sc-table stock-register-table">
            <colgroup>
                <col class="col-index">
                <col class="col-date">
                <col class="col-medicine">
                <col class="col-type">
                <col class="col-qty">
                <col class="col-qty">
                <col class="col-balance">
                <col class="col-batch">
                <col class="col-reference">
                <col class="col-user">
            </colgroup>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Date &amp; Time</th>
                    <th>Medicine</th>
                    <th>Type</th>
                    <th class="qty-cell">Qty In</th>
                    <th class="qty-cell">Qty Out</th>
                    <th class="balance-cell">Balance</th>
                    <th>Batch</th>
                    <th>Reference</th>
                    <th>By</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $i => $txn)
                @php
                    $typeBadge = [
                        'stock_in'   => ['badge-green', 'Stock In'],
                        'dispensed'  => ['badge-red',   'Dispensed'],
                        'adjustment' => ['badge-amber', 'Adjustment'],
                        'return'     => ['badge-teal',  'Return'],
                        'expired'    => ['badge-gray',  'Expired'],
                    ];
                    [$badgeClass, $label] = $typeBadge[$txn->type] ?? ['badge-gray', ucfirst($txn->type)];
                @endphp
                <tr>
                    <td class="index-cell text-muted text-small">{{ $transactions->firstItem() + $i }}</td>
                    <td class="date-cell">
                        <div class="date-main">{{ $txn->created_at->format('d M Y') }}</div>
                        <div class="date-time text-muted">{{ $txn->created_at->format('h:i A') }}</div>
                    </td>
                    <td class="cell-primary">
                        <div class="medicine-name">{{ $txn->medicine->name ?? '—' }}</div>
                        @if($txn->medicine?->generic_name)
                            <div class="medicine-generic text-muted">{{ $txn->medicine->generic_name }}</div>
                        @endif
                    </td>
                    <td class="type-cell"><span class="badge {{ $badgeClass }}">{{ $label }}</span></td>
                    <td class="qty-cell">
                        @if(in_array($txn->type, ['stock_in', 'return']))
                            <span class="fw-600" style="color:var(--sc-green)">+{{ number_format(abs($txn->quantity)) }}</span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="qty-cell">
                        @if(in_array($txn->type, ['dispensed', 'expired']))
                            <span class="fw-600" style="color:var(--sc-red)">−{{ number_format(abs($txn->quantity)) }}</span>
                        @elseif($txn->type === 'adjustment')
                            <span class="fw-600" style="color:var(--sc-amber)">{{ $txn->quantity > 0 ? '+' : '' }}{{ number_format($txn->quantity) }}</span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="balance-cell">
                        <span class="fw-600 mono">{{ number_format($txn->balance_after) }}</span>
                    </td>
                    <td class="batch-cell mono text-small">{{ $txn->batch_number ?? '—' }}</td>
                    <td class="cell-reference">
                        @if($txn->reference)
                            @if($txn->prescription_id && isset($txn->prescription->encounter_id))
                                <a href="{{ route('encounters.show', $txn->prescription->encounter_id) }}"
                                   class="mono text-small" style="color:var(--sc-teal);text-decoration:none;word-break:break-all">
                                    {{ $txn->reference }}
                                </a>
                            @else
                                <span class="mono text-small">{{ $txn->reference }}</span>
                            @endif
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="user-cell text-small">{{ $txn->user->name ?? 'System' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" class="empty-cell">
                        <i class="fa-solid fa-book-open" style="font-size:32px;display:block;margin-bottom:12px;opacity:.35"></i>
                        <div style="font-size:14px;font-weight:500">No transactions found</div>
                        <div class="text-small" style="margin-top:4px">Try adjusting the filters or date range</div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($transactions->hasPages())
    <div class="card-footer">
        {{ $transactions->appends(request()->query())->links() }}
    </div>
    @endif
</div>
@endsection
