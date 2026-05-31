@extends('layouts.app')
@section('title', 'Day-End Cash Report – ' . $date->format('d M Y'))
@section('content')

<div class="page-header">
    <div class="page-header-left">
        <h1>Day-End Cash Report</h1>
        <div class="breadcrumb">Reports &rsaquo; Day-End &rsaquo; {{ $date->format('l, d F Y') }}</div>
    </div>
    <div style="display:flex;gap:10px;align-items:center">
        <form method="GET" style="display:flex;gap:8px">
            <input type="date" name="date" value="{{ $date->toDateString() }}" class="form-control" style="width:auto">
            <button type="submit" class="btn btn-outline">Go</button>
        </form>
        <a href="{{ route('reports.day-end.export', ['date' => $date->toDateString()]) }}" class="btn btn-outline">
            <i class="fa-solid fa-file-csv"></i> Export CSV
        </a>
        <button onclick="window.print()" class="btn btn-outline">
            <i class="fa-solid fa-print"></i> Print
        </button>
    </div>
</div>

{{-- Summary cards --}}
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px">
    <div class="stat-card">
        <div class="stat-icon green"><i class="fa-solid fa-circle-dollar-to-slot"></i></div>
        <div>
            <div class="stat-value" style="color:var(--sc-green)">₹{{ number_format($summary['total_collected'], 0) }}</div>
            <div class="stat-label">Total Collected</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon navy"><i class="fa-solid fa-file-invoice-dollar"></i></div>
        <div>
            <div class="stat-value">₹{{ number_format($summary['opd_collected'], 0) }}</div>
            <div class="stat-label">OPD Collected</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon teal"><i class="fa-solid fa-bed-pulse"></i></div>
        <div>
            <div class="stat-value">₹{{ number_format($summary['ipd_deposits'], 0) }}</div>
            <div class="stat-label">IPD Deposits</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon amber"><i class="fa-solid fa-receipt"></i></div>
        <div>
            <div class="stat-value">₹{{ number_format($summary['ipd_charges'], 0) }}</div>
            <div class="stat-label">IPD Charges Billed</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon navy"><i class="fa-solid fa-stethoscope"></i></div>
        <div>
            <div class="stat-value">₹{{ number_format($summary['opd_billed'], 0) }}</div>
            <div class="stat-label">OPD Billed</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon amber"><i class="fa-solid fa-tag"></i></div>
        <div>
            <div class="stat-value" style="color:var(--sc-amber)">₹{{ number_format($summary['opd_discount'], 0) }}</div>
            <div class="stat-label">OPD Discounts</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon teal"><i class="fa-solid fa-flask"></i></div>
        <div>
            <div class="stat-value">₹{{ number_format($summary['lab_revenue'], 0) }}</div>
            <div class="stat-label">Lab Revenue <span class="stat-sub text-muted">({{ $labCount }} orders)</span></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon navy"><i class="fa-solid fa-radiation"></i></div>
        <div>
            <div class="stat-value">₹{{ number_format($summary['radio_revenue'], 0) }}</div>
            <div class="stat-label">Radiology Revenue <span class="stat-sub text-muted">({{ $radioCount }} orders)</span></div>
        </div>
    </div>
</div>

{{-- Payment method breakdowns --}}
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px">
    <div class="card">
        <div class="card-header"><i class="fa-solid fa-credit-card"></i> OPD Payments by Method</div>
        <div class="card-body" style="padding:0">
            @forelse($byMethod as $method => $row)
            <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 20px;border-bottom:1px solid var(--sc-border)">
                <span style="font-weight:600;font-size:13px">{{ $method ?: 'Unknown' }}</span>
                <span style="font-weight:700;font-size:13px">
                    ₹{{ number_format($row->total, 2) }}
                    <span class="text-muted text-small" style="font-weight:400">({{ $row->cnt }} txn)</span>
                </span>
            </div>
            @empty
            <div style="padding:24px;text-align:center;color:var(--sc-muted)">No OPD payments today.</div>
            @endforelse
        </div>
    </div>

    <div class="card">
        <div class="card-header"><i class="fa-solid fa-building-columns"></i> IPD Deposits by Method</div>
        <div class="card-body" style="padding:0">
            @forelse($depositByMethod as $method => $row)
            <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 20px;border-bottom:1px solid var(--sc-border)">
                <span style="font-weight:600;font-size:13px">{{ $method ?: 'Unknown' }}</span>
                <span style="font-weight:700;font-size:13px">₹{{ number_format($row->total, 2) }}</span>
            </div>
            @empty
            <div style="padding:24px;text-align:center;color:var(--sc-muted)">No IPD deposits today.</div>
            @endforelse
        </div>
    </div>
</div>

{{-- OPD Bills --}}
<div class="card mb-4">
    <div class="card-header">
        <i class="fa-solid fa-file-invoice-dollar"></i> OPD Bills — {{ $date->format('d M Y') }}
        <div class="card-actions"><span class="badge badge-navy">{{ $opdBills->count() }}</span></div>
    </div>
    @if($opdBills->isEmpty())
    <div class="card-body text-muted" style="text-align:center;padding:32px">No OPD bills today.</div>
    @else
    <div class="table-wrap">
        <table class="sc-table">
            <thead>
                <tr>
                    <th>Bill No.</th><th>Patient</th>
                    <th class="text-right">Amount</th><th class="text-right">Discount</th>
                    <th class="text-right">Due</th><th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($opdBills as $bill)
                <tr>
                    <td class="mono text-small"><a href="{{ route('billing.show', $bill) }}" class="link">{{ $bill->bill_no }}</a></td>
                    <td>{{ $bill->patient->full_name }}</td>
                    <td class="text-right mono">₹{{ number_format($bill->total_amount, 2) }}</td>
                    <td class="text-right mono" style="color:var(--sc-amber)">{{ $bill->discount_amount ? '₹'.number_format($bill->discount_amount, 2) : '—' }}</td>
                    <td class="text-right mono" style="color:var(--sc-red)">{{ $bill->due_amount > 0 ? '₹'.number_format($bill->due_amount, 2) : '—' }}</td>
                    <td><span class="badge {{ $bill->status==='paid' ? 'badge-green' : 'badge-amber' }}">{{ ucfirst($bill->status) }}</span></td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="background:var(--sc-bg);font-weight:700">
                    <td colspan="2" style="padding:11px 14px">Total ({{ $opdBills->count() }} bills)</td>
                    <td class="text-right mono" style="padding:11px 14px">₹{{ number_format($opdBills->sum('total_amount'), 2) }}</td>
                    <td class="text-right mono" style="padding:11px 14px;color:var(--sc-amber)">₹{{ number_format($opdBills->sum('discount_amount'), 2) }}</td>
                    <td class="text-right mono" style="padding:11px 14px;color:var(--sc-red)">₹{{ number_format($opdBills->sum('due_amount'), 2) }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
    @endif
</div>

{{-- IPD Deposits --}}
<div class="card mb-4">
    <div class="card-header">
        <i class="fa-solid fa-bed-pulse"></i> IPD Deposits — {{ $date->format('d M Y') }}
        <div class="card-actions"><span class="badge badge-teal">{{ $ipdDeposits->count() }}</span></div>
    </div>
    @if($ipdDeposits->isEmpty())
    <div class="card-body text-muted" style="text-align:center;padding:32px">No IPD deposits today.</div>
    @else
    <div class="table-wrap">
        <table class="sc-table">
            <thead>
                <tr>
                    <th>IPD No.</th><th>Patient</th>
                    <th class="text-right">Amount</th><th>Method</th>
                    <th>Received By</th><th>Time</th>
                </tr>
            </thead>
            <tbody>
                @foreach($ipdDeposits as $dep)
                <tr>
                    <td class="mono text-small">{{ $dep->admission->ipd_no }}</td>
                    <td>{{ $dep->admission->patient->full_name }}</td>
                    <td class="text-right mono fw-600" style="color:var(--sc-teal)">₹{{ number_format($dep->amount, 2) }}</td>
                    <td class="text-muted">{{ $dep->payment_method ?? '—' }}</td>
                    <td class="text-muted">{{ $dep->receivedBy?->name ?? '—' }}</td>
                    <td class="text-small text-muted">{{ $dep->created_at->format('H:i') }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="background:var(--sc-bg);font-weight:700">
                    <td colspan="2" style="padding:11px 14px">Total ({{ $ipdDeposits->count() }})</td>
                    <td class="text-right mono" style="padding:11px 14px;color:var(--sc-teal)">₹{{ number_format($ipdDeposits->sum('amount'), 2) }}</td>
                    <td colspan="3"></td>
                </tr>
            </tfoot>
        </table>
    </div>
    @endif
</div>

{{-- IPD Charges --}}
<div class="card">
    <div class="card-header">
        <i class="fa-solid fa-receipt"></i> IPD Charges Billed — {{ $date->format('d M Y') }}
        <div class="card-actions"><span class="badge badge-amber">{{ $ipdCharges->count() }}</span></div>
    </div>
    @if($ipdCharges->isEmpty())
    <div class="card-body text-muted" style="text-align:center;padding:32px">No IPD charges today.</div>
    @else
    <div class="table-wrap">
        <table class="sc-table">
            <thead>
                <tr>
                    <th>IPD No.</th><th>Patient</th><th>Description</th>
                    <th class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($ipdCharges as $ch)
                <tr>
                    <td class="mono text-small">{{ $ch->admission->ipd_no }}</td>
                    <td>{{ $ch->admission->patient->full_name }}</td>
                    <td class="text-muted">{{ $ch->description }}</td>
                    <td class="text-right mono fw-600" style="color:var(--sc-navy)">₹{{ number_format($ch->amount, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="background:var(--sc-bg);font-weight:700">
                    <td colspan="3" style="padding:11px 14px">Total ({{ $ipdCharges->count() }} items)</td>
                    <td class="text-right mono" style="padding:11px 14px;color:var(--sc-navy)">₹{{ number_format($ipdCharges->sum('amount'), 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
    @endif
</div>

@push('styles')
<style>
@media print {
    .page-header form, .page-header button { display: none !important; }
    .sc-table td, .sc-table th { font-size: 11px; padding: 7px 10px; }
    .stat-card { break-inside: avoid; }
}
</style>
@endpush

@endsection
