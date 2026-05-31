@extends('layouts.app')
@section('title','GSTR-1 — ' . $monthYear)
@section('page-title','GSTR-1 (Outward Supplies)')
@section('content')

<div class="page-header">
    <div class="page-header-left">
        <a href="{{ route('accounts.index') }}" class="text-small"><i class="fa-solid fa-arrow-left"></i> Accounts</a>
        <h1>GSTR-1 — {{ $monthYear }}</h1>
        <div class="text-small text-muted">Outward supplies (sales). Download the GSTN portal JSON to upload at gst.gov.in → Returns → Prepare Offline.</div>
    </div>
    <div style="display:flex;gap:8px">
        <a href="{{ route('accounts.reports.gstr1', ['month'=>$from->month,'year'=>$from->year,'format'=>'json']) }}" class="btn btn-primary"><i class="fa-solid fa-file-arrow-down"></i> Download JSON (portal)</a>
        <a href="javascript:window.print()" class="btn btn-outline"><i class="fa-solid fa-print"></i> Print</a>
    </div>
</div>

<form method="GET" class="card" style="padding:12px;margin-bottom:12px;display:flex;gap:8px;align-items:end">
    <div><label>Month</label>
        <select name="month" class="form-control">
            @for($m=1;$m<=12;$m++)<option value="{{ $m }}" {{ $from->month===$m?'selected':'' }}>{{ \Carbon\Carbon::create()->month($m)->format('F') }}</option>@endfor
        </select>
    </div>
    <div><label>Year</label>
        <input type="number" name="year" value="{{ $from->year }}" class="form-control mono" style="width:100px">
    </div>
    <button class="btn btn-primary">Show</button>
</form>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:10px;margin-bottom:14px">
    <div class="card"><div class="card-body"><div class="text-muted text-small">Taxable Value</div><div class="mono fw-700" style="font-size:1.2rem">₹{{ number_format($totals['taxable'],2) }}</div></div></div>
    <div class="card"><div class="card-body"><div class="text-muted text-small">CGST</div><div class="mono fw-700" style="font-size:1.2rem">₹{{ number_format($totals['cgst'],2) }}</div></div></div>
    <div class="card"><div class="card-body"><div class="text-muted text-small">SGST</div><div class="mono fw-700" style="font-size:1.2rem">₹{{ number_format($totals['sgst'],2) }}</div></div></div>
    <div class="card"><div class="card-body"><div class="text-muted text-small">IGST</div><div class="mono fw-700" style="font-size:1.2rem">₹{{ number_format($totals['igst'],2) }}</div></div></div>
    <div class="card"><div class="card-body"><div class="text-muted text-small">Invoice Value</div><div class="mono fw-700" style="font-size:1.2rem">₹{{ number_format($totals['invoice_value'],2) }}</div></div></div>
</div>

<div class="card" style="margin-bottom:14px">
    <div class="card-header"><strong>B2B (registered parties — {{ count($b2b) }})</strong></div>
    <div class="table-wrap">
        <table class="sc-table" style="margin:0">
            <thead><tr><th>Date</th><th>Invoice</th><th>Party (GSTIN)</th><th>State</th><th class="text-right">Taxable</th><th class="text-right">CGST</th><th class="text-right">SGST</th><th class="text-right">IGST</th><th class="text-right">Total</th></tr></thead>
            <tbody>
            @forelse($b2b as $r)
                <tr>
                    <td class="text-small">{{ \Carbon\Carbon::parse($r['voucher_date'])->format('d-M') }}</td>
                    <td><a href="{{ route('accounts.vouchers.show', $r['voucher_id']) }}" class="mono">{{ $r['voucher_no'] }}</a></td>
                    <td>{{ $r['party_name'] }}<div class="text-small text-muted mono">{{ $r['party_gstin'] }}</div></td>
                    <td class="text-small mono">{{ $r['party_state'] ?: '—' }}</td>
                    <td class="text-right mono">{{ number_format($r['taxable'],2) }}</td>
                    <td class="text-right mono">{{ number_format($r['cgst'],2) }}</td>
                    <td class="text-right mono">{{ number_format($r['sgst'],2) }}</td>
                    <td class="text-right mono">{{ number_format($r['igst'],2) }}</td>
                    <td class="text-right mono fw-600">{{ number_format($r['invoice_value'],2) }}</td>
                </tr>
            @empty
                <tr><td colspan="9" style="text-align:center;padding:12px;color:var(--sc-muted)">No B2B invoices this month.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header"><strong>B2C (cash patients / unregistered — {{ count($b2c) }})</strong></div>
    <div class="table-wrap">
        <table class="sc-table" style="margin:0">
            <thead><tr><th>Date</th><th>Voucher</th><th>Party</th><th class="text-right">Taxable</th><th class="text-right">CGST</th><th class="text-right">SGST</th><th class="text-right">IGST</th><th class="text-right">Total</th></tr></thead>
            <tbody>
            @forelse($b2c as $r)
                <tr>
                    <td class="text-small">{{ \Carbon\Carbon::parse($r['voucher_date'])->format('d-M') }}</td>
                    <td><a href="{{ route('accounts.vouchers.show', $r['voucher_id']) }}" class="mono">{{ $r['voucher_no'] }}</a></td>
                    <td class="text-small">{{ $r['party_name'] }}</td>
                    <td class="text-right mono">{{ number_format($r['taxable'],2) }}</td>
                    <td class="text-right mono">{{ number_format($r['cgst'],2) }}</td>
                    <td class="text-right mono">{{ number_format($r['sgst'],2) }}</td>
                    <td class="text-right mono">{{ number_format($r['igst'],2) }}</td>
                    <td class="text-right mono fw-600">{{ number_format($r['invoice_value'],2) }}</td>
                </tr>
            @empty
                <tr><td colspan="8" style="text-align:center;padding:12px;color:var(--sc-muted)">No B2C invoices this month.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
