@extends('layouts.app')
@section('title','HSN Summary — ' . $monthYear)
@section('page-title','HSN-wise Summary')
@section('content')

<div class="page-header">
    <div class="page-header-left">
        <a href="{{ route('accounts.index') }}" class="text-small"><i class="fa-solid fa-arrow-left"></i> Accounts</a>
        <h1>HSN-wise Summary — {{ $monthYear }}</h1>
        <div class="text-small text-muted">Required in GSTR-1. Group by HSN/SAC + GST rate.</div>
    </div>
    <a href="javascript:window.print()" class="btn btn-outline"><i class="fa-solid fa-print"></i> Print</a>
</div>

<form method="GET" class="card" style="padding:12px;margin-bottom:12px;display:flex;gap:8px;align-items:end">
    <div><label>Direction</label>
        <select name="direction" class="form-control">
            <option value="outward" {{ $direction==='outward'?'selected':'' }}>Outward (sales)</option>
            <option value="inward"  {{ $direction==='inward'?'selected':'' }}>Inward (purchases)</option>
        </select>
    </div>
    <div><label>Month</label>
        <select name="month" class="form-control">
            @for($m=1;$m<=12;$m++)<option value="{{ $m }}" {{ $from->month===$m?'selected':'' }}>{{ \Carbon\Carbon::create()->month($m)->format('F') }}</option>@endfor
        </select>
    </div>
    <div><label>Year</label><input type="number" name="year" value="{{ $from->year }}" class="form-control mono" style="width:100px"></div>
    <button class="btn btn-primary">Show</button>
</form>

<div class="card">
    <div class="table-wrap">
        <table class="sc-table">
            <thead><tr><th>HSN/SAC</th><th class="text-right">GST %</th><th class="text-right">Lines</th><th class="text-right">Taxable</th><th class="text-right">CGST</th><th class="text-right">SGST</th><th class="text-right">IGST</th><th class="text-right">Cess</th></tr></thead>
            <tbody>
            @forelse($rows as $r)
                <tr>
                    <td class="mono">{{ $r->hsn_sac }}</td>
                    <td class="text-right mono">{{ $r->gst_rate !== null ? rtrim(rtrim(number_format((float)$r->gst_rate,2),'0'),'.') : '—' }}</td>
                    <td class="text-right mono">{{ $r->line_count }}</td>
                    <td class="text-right mono">{{ number_format((float)$r->taxable,2) }}</td>
                    <td class="text-right mono">{{ number_format((float)$r->cgst,2) }}</td>
                    <td class="text-right mono">{{ number_format((float)$r->sgst,2) }}</td>
                    <td class="text-right mono">{{ number_format((float)$r->igst,2) }}</td>
                    <td class="text-right mono">{{ number_format((float)$r->cess,2) }}</td>
                </tr>
            @empty
                <tr><td colspan="8" style="text-align:center;padding:14px;color:var(--sc-muted)">No HSN/SAC entries with tax in this period.</td></tr>
            @endforelse
            </tbody>
            <tfoot>
                <tr style="background:#f0f0f0">
                    <td colspan="3" class="fw-700 text-right">Totals</td>
                    <td class="text-right mono fw-700">₹{{ number_format($totals['taxable'],2) }}</td>
                    <td class="text-right mono fw-700">₹{{ number_format($totals['cgst'],2) }}</td>
                    <td class="text-right mono fw-700">₹{{ number_format($totals['sgst'],2) }}</td>
                    <td class="text-right mono fw-700">₹{{ number_format($totals['igst'],2) }}</td>
                    <td class="text-right mono fw-700">₹{{ number_format($totals['cess'],2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

@endsection
