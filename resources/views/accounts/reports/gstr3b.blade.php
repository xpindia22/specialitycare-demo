@extends('layouts.app')
@section('title','GSTR-3B — ' . $monthYear)
@section('page-title','GSTR-3B (Summary)')
@section('content')

<div class="page-header">
    <div class="page-header-left">
        <a href="{{ route('accounts.index') }}" class="text-small"><i class="fa-solid fa-arrow-left"></i> Accounts</a>
        <h1>GSTR-3B — {{ $monthYear }}</h1>
        <div class="text-small text-muted">Monthly summary of outward + inward + tax payable / ITC. Download JSON to upload at gst.gov.in.</div>
    </div>
    <div style="display:flex;gap:8px">
        <a href="{{ route('accounts.reports.gstr3b', ['month'=>$from->month,'year'=>$from->year,'format'=>'json']) }}" class="btn btn-primary"><i class="fa-solid fa-file-arrow-down"></i> Download JSON (portal)</a>
        <a href="javascript:window.print()" class="btn btn-outline"><i class="fa-solid fa-print"></i> Print</a>
    </div>
</div>

<form method="GET" class="card" style="padding:12px;margin-bottom:12px;display:flex;gap:8px;align-items:end">
    <div><label>Month</label>
        <select name="month" class="form-control">
            @for($m=1;$m<=12;$m++)<option value="{{ $m }}" {{ $from->month===$m?'selected':'' }}>{{ \Carbon\Carbon::create()->month($m)->format('F') }}</option>@endfor
        </select>
    </div>
    <div><label>Year</label><input type="number" name="year" value="{{ $from->year }}" class="form-control mono" style="width:100px"></div>
    <button class="btn btn-primary">Show</button>
</form>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
    <div class="card">
        <div class="card-header"><strong>3.1 — Outward supplies</strong></div>
        <div class="card-body">
            <table class="sc-table" style="margin:0">
                <tr><td>Taxable value</td><td class="text-right mono">₹{{ number_format($outward['taxable'],2) }}</td></tr>
                <tr><td>CGST</td><td class="text-right mono">₹{{ number_format($outward['cgst'],2) }}</td></tr>
                <tr><td>SGST</td><td class="text-right mono">₹{{ number_format($outward['sgst'],2) }}</td></tr>
                <tr><td>IGST</td><td class="text-right mono">₹{{ number_format($outward['igst'],2) }}</td></tr>
                <tr><td>Cess</td><td class="text-right mono">₹{{ number_format($outward['cess'],2) }}</td></tr>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><strong>4 — Eligible ITC (from purchases)</strong></div>
        <div class="card-body">
            <table class="sc-table" style="margin:0">
                <tr><td>Taxable value</td><td class="text-right mono">₹{{ number_format($inward['taxable'],2) }}</td></tr>
                <tr><td>CGST</td><td class="text-right mono">₹{{ number_format($inward['cgst'],2) }}</td></tr>
                <tr><td>SGST</td><td class="text-right mono">₹{{ number_format($inward['sgst'],2) }}</td></tr>
                <tr><td>IGST</td><td class="text-right mono">₹{{ number_format($inward['igst'],2) }}</td></tr>
                <tr><td>Cess</td><td class="text-right mono">₹{{ number_format($inward['cess'],2) }}</td></tr>
            </table>
        </div>
    </div>
</div>

<div class="card" style="margin-top:14px">
    <div class="card-header"><strong>6.1 — Tax payable (after ITC offset)</strong></div>
    <div class="card-body">
        <table class="sc-table" style="margin:0">
            <thead><tr><th>Head</th><th class="text-right">Output</th><th class="text-right">ITC</th><th class="text-right">Net Payable</th></tr></thead>
            <tbody>
                <tr><td>CGST</td><td class="text-right mono">{{ number_format($outward['cgst'],2) }}</td><td class="text-right mono">{{ number_format($inward['cgst'],2) }}</td><td class="text-right mono fw-700">₹{{ number_format($taxPayable['cgst'],2) }}</td></tr>
                <tr><td>SGST</td><td class="text-right mono">{{ number_format($outward['sgst'],2) }}</td><td class="text-right mono">{{ number_format($inward['sgst'],2) }}</td><td class="text-right mono fw-700">₹{{ number_format($taxPayable['sgst'],2) }}</td></tr>
                <tr><td>IGST</td><td class="text-right mono">{{ number_format($outward['igst'],2) }}</td><td class="text-right mono">{{ number_format($inward['igst'],2) }}</td><td class="text-right mono fw-700">₹{{ number_format($taxPayable['igst'],2) }}</td></tr>
                <tr><td>Cess</td><td class="text-right mono">{{ number_format($outward['cess'],2) }}</td><td class="text-right mono">{{ number_format($inward['cess'],2) }}</td><td class="text-right mono fw-700">₹{{ number_format($taxPayable['cess'],2) }}</td></tr>
            </tbody>
            <tfoot>
                <tr style="background:#f0f0f0"><td class="fw-700">Total payable</td><td colspan="2"></td><td class="text-right mono fw-700">₹{{ number_format(array_sum($taxPayable),2) }}</td></tr>
            </tfoot>
        </table>
    </div>
</div>

@endsection
