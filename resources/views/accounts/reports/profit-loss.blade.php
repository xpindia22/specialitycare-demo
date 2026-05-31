@extends('layouts.app')
@section('title','P&L — ' . $from->format('d-M-Y') . ' to ' . $to->format('d-M-Y'))
@section('page-title','Profit & Loss')
@section('content')

<div class="page-header">
    <div class="page-header-left">
        <a href="{{ route('accounts.index') }}" class="text-small"><i class="fa-solid fa-arrow-left"></i> Accounts</a>
        <h1>Profit &amp; Loss</h1>
        <div class="text-small text-muted">{{ $from->format('d-M-Y') }} to {{ $to->format('d-M-Y') }} · FY {{ $fyYear }}-{{ substr($fyYear+1,-2) }} · {{ setting('clinic_name','SpecialityCare') }}</div>
    </div>
    <a href="javascript:window.print()" class="btn btn-outline"><i class="fa-solid fa-print"></i> Print</a>
</div>

<form method="GET" class="card" style="padding:12px;margin-bottom:12px;display:flex;gap:8px;align-items:end">
    <div><label>From</label><input type="date" name="from" value="{{ $from->toDateString() }}" class="form-control"></div>
    <div><label>To</label><input type="date" name="to" value="{{ $to->toDateString() }}" class="form-control"></div>
    <button class="btn btn-primary">Show</button>
</form>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
    <div class="card">
        <div class="card-header"><strong>Expenses (Dr)</strong></div>
        <div class="table-wrap">
            <table class="sc-table" style="margin:0">
                <tbody>
                    @if(!empty($expense['direct']))
                    <tr style="background:#fafaf6"><td colspan="2" class="fw-700">Direct Expenses</td></tr>
                    @foreach($expense['direct'] as $row)
                        <tr><td style="padding-left:24px">{{ $row['name'] }}</td><td class="text-right mono">{{ number_format($row['amount'],2) }}</td></tr>
                    @endforeach
                    <tr><td class="text-right fw-600">Sub-total</td><td class="text-right mono fw-600">{{ number_format($expense['total_direct'],2) }}</td></tr>
                    @endif

                    @if(!empty($expense['indirect']))
                    <tr style="background:#fafaf6"><td colspan="2" class="fw-700">Indirect Expenses</td></tr>
                    @foreach($expense['indirect'] as $row)
                        <tr><td style="padding-left:24px">{{ $row['name'] }}</td><td class="text-right mono">{{ number_format($row['amount'],2) }}</td></tr>
                    @endforeach
                    <tr><td class="text-right fw-600">Sub-total</td><td class="text-right mono fw-600">{{ number_format($expense['total_indirect'],2) }}</td></tr>
                    @endif

                    @if($netProfit >= 0)
                    <tr style="background:#eef9ee"><td class="fw-700">Net Profit (transferred to Capital)</td><td class="text-right mono fw-700">{{ number_format($netProfit,2) }}</td></tr>
                    @endif
                </tbody>
                <tfoot>
                    <tr style="background:#f0f0f0"><td class="fw-700">Total</td><td class="text-right mono fw-700">₹{{ number_format($totalExpense + max($netProfit,0),2) }}</td></tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><strong>Income (Cr)</strong></div>
        <div class="table-wrap">
            <table class="sc-table" style="margin:0">
                <tbody>
                    @if(!empty($income['direct']))
                    <tr style="background:#fafaf6"><td colspan="2" class="fw-700">Direct Income</td></tr>
                    @foreach($income['direct'] as $row)
                        <tr><td style="padding-left:24px">{{ $row['name'] }}</td><td class="text-right mono">{{ number_format($row['amount'],2) }}</td></tr>
                    @endforeach
                    <tr><td class="text-right fw-600">Sub-total</td><td class="text-right mono fw-600">{{ number_format($income['total_direct'],2) }}</td></tr>
                    @endif

                    @if(!empty($income['indirect']))
                    <tr style="background:#fafaf6"><td colspan="2" class="fw-700">Indirect Income</td></tr>
                    @foreach($income['indirect'] as $row)
                        <tr><td style="padding-left:24px">{{ $row['name'] }}</td><td class="text-right mono">{{ number_format($row['amount'],2) }}</td></tr>
                    @endforeach
                    <tr><td class="text-right fw-600">Sub-total</td><td class="text-right mono fw-600">{{ number_format($income['total_indirect'],2) }}</td></tr>
                    @endif

                    @if($netProfit < 0)
                    <tr style="background:#fdecec"><td class="fw-700">Net Loss (transferred to Capital)</td><td class="text-right mono fw-700">{{ number_format(abs($netProfit),2) }}</td></tr>
                    @endif
                </tbody>
                <tfoot>
                    <tr style="background:#f0f0f0"><td class="fw-700">Total</td><td class="text-right mono fw-700">₹{{ number_format($totalIncome + max(-$netProfit,0),2) }}</td></tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<div class="card" style="margin-top:14px;padding:14px;text-align:center;background:{{ $netProfit >= 0 ? '#eef9ee' : '#fdecec' }}">
    <div class="text-small text-muted">Gross Profit (Direct Income − Direct Expenses)</div>
    <div style="font-size:1.4rem">₹{{ number_format($grossProfit,2) }}</div>
    <div style="margin-top:8px" class="text-small text-muted">Net Profit (after Indirect)</div>
    <div style="font-size:1.8rem;font-weight:700;color:{{ $netProfit >= 0 ? 'var(--sc-green,#1f8b4c)' : 'var(--sc-red,#b54344)' }}">
        ₹{{ number_format($netProfit,2) }} {{ $netProfit >= 0 ? '✓' : '✗' }}
    </div>
</div>

@endsection
