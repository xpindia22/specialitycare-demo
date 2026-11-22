@extends('layouts.app')
@section('title','Balance Sheet — ' . $upto->format('d-M-Y'))
@section('page-title','Balance Sheet')
@section('content')

@include('accounts._toolbar')

<div class="page-header">
    <div class="page-header-left">
        <a href="{{ route('accounts.index') }}" class="text-small"><i class="fa-solid fa-arrow-left"></i> Accounts</a>
        <h1>Balance Sheet as on {{ $upto->format('d-M-Y') }}</h1>
        <div class="text-small text-muted">{{ setting('clinic_name','SpecialityCare') }} · FY {{ $fyYear }}-{{ substr($fyYear+1,-2) }}</div>
    </div>
    <a href="javascript:window.print()" class="btn btn-outline"><i class="fa-solid fa-print"></i> Print</a>
</div>

<form method="GET" class="card" style="padding:12px;margin-bottom:12px;display:flex;gap:8px;align-items:end">
    <div><label>As on</label><input type="date" name="upto" value="{{ $upto->toDateString() }}" class="form-control"></div>
    <button class="btn btn-primary">Show</button>
</form>

@if(!empty($boD))
    <div class="alert alert-info text-small" style="margin-bottom:12px">
        <i class="fa-solid fa-circle-info"></i> Books opening date <strong>{{ $boD->format('d-M-Y') }}</strong> is active —
        vouchers before this date are excluded; their position is carried by each ledger's opening balance.
    </div>
@endif

<div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
    <div class="card">
        <div class="card-header"><strong>Liabilities &amp; Capital (Cr)</strong></div>
        <div class="table-wrap">
            <table class="sc-table" style="margin:0">
                <tbody>
                @foreach($liabilities as $group => $rows)
                    <tr style="background:#fafaf6"><td colspan="2" class="fw-700">{{ $group }}</td></tr>
                    @foreach($rows as $row)
                        <tr><td style="padding-left:24px">{{ $row['name'] }}</td><td class="text-right mono">{{ number_format($row['amount'],2) }}</td></tr>
                    @endforeach
                @endforeach
                @if(empty($liabilities))
                <tr><td colspan="2" style="text-align:center;color:var(--sc-muted);padding:14px">No liabilities recorded.</td></tr>
                @endif
                </tbody>
                <tfoot>
                    <tr style="background:#f0f0f0"><td class="fw-700">Total Liabilities</td><td class="text-right mono fw-700">₹{{ number_format($liabTotal,2) }}</td></tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><strong>Assets (Dr)</strong></div>
        <div class="table-wrap">
            <table class="sc-table" style="margin:0">
                <tbody>
                @foreach($assets as $group => $rows)
                    <tr style="background:#fafaf6"><td colspan="2" class="fw-700">{{ $group }}</td></tr>
                    @foreach($rows as $row)
                        <tr><td style="padding-left:24px">{{ $row['name'] }}</td><td class="text-right mono">{{ number_format($row['amount'],2) }}</td></tr>
                    @endforeach
                @endforeach
                @if(empty($assets))
                <tr><td colspan="2" style="text-align:center;color:var(--sc-muted);padding:14px">No assets recorded.</td></tr>
                @endif
                </tbody>
                <tfoot>
                    <tr style="background:#f0f0f0"><td class="fw-700">Total Assets</td><td class="text-right mono fw-700">₹{{ number_format($assetsTotal,2) }}</td></tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<div class="card" style="margin-top:14px;padding:14px;text-align:center;background:{{ abs($difference) < 0.01 ? '#eef9ee' : '#fff4e0' }}">
    @if(abs($difference) < 0.01)
        <span class="badge badge-green"><i class="fa-solid fa-check"></i> Balanced — Assets = Liabilities</span>
    @else
        <span class="badge" style="background:#f0ad4e;color:#fff"><i class="fa-solid fa-triangle-exclamation"></i> Difference: ₹{{ number_format($difference,2) }}</span>
        <div class="text-small text-muted" style="margin-top:6px">Usually means opening balances aren't set yet, or some FA / Capital ledgers are missing values. Net profit ₹{{ number_format($netProfit,2) }} for the current FY is already included on the Liabilities side under Capital.</div>
    @endif
</div>

@endsection
