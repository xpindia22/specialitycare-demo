<!doctype html>
<html><head><meta charset="utf-8">
<title>{{ $voucher->voucher_no }}</title>
<style>
body{font-family:Arial,sans-serif;font-size:12px;color:#222;margin:24px}
h1{margin:0;font-size:18px}
table{width:100%;border-collapse:collapse;margin-top:8px}
th,td{border:1px solid #999;padding:6px 8px}
th{background:#f0f0f0;text-align:left}
.right{text-align:right}.mono{font-family:monospace}.muted{color:#666;font-size:11px}
.head{display:flex;justify-content:space-between;border-bottom:2px solid #222;padding-bottom:8px;margin-bottom:8px}
.totals td{font-weight:bold;background:#f7f7f7}
</style></head><body onload="window.print()">

<div class="head">
    <div>
        <h1>{{ setting('clinic_name','SpecialityCare') }}</h1>
        <div class="muted">{{ setting('clinic_address') }}</div>
        @if(setting('clinic_gstin'))<div class="muted">GSTIN: {{ setting('clinic_gstin') }}</div>@endif
    </div>
    <div class="right">
        <h1>{{ $voucher->type->name }}</h1>
        <div><strong>{{ $voucher->voucher_no }}</strong></div>
        <div class="muted">{{ \Carbon\Carbon::parse($voucher->voucher_date)->format('d-M-Y') }}</div>
    </div>
</div>

<table>
    <tr><th style="width:140px">Party</th><td>{{ $voucher->party?->name ?: '—' }}</td>
        <th style="width:140px">Reference</th><td>{{ $voucher->reference_no ?: '—' }}</td></tr>
    <tr><th>Status</th><td>{{ $voucher->status }}</td>
        <th>Posted By</th><td>{{ $voucher->createdBy?->name ?: '—' }}</td></tr>
    @if($voucher->narration)
    <tr><th>Narration</th><td colspan="3">{{ $voucher->narration }}</td></tr>
    @endif
</table>

<table style="margin-top:12px">
    <thead><tr><th>#</th><th>Ledger</th><th>Note</th><th class="right">Dr</th><th class="right">Cr</th></tr></thead>
    <tbody>
        @foreach($voucher->entries as $e)
        <tr>
            <td>{{ $e->line_no }}</td>
            <td><strong>{{ $e->ledger?->name ?? '(deleted ledger #' . $e->ledger_id . ')' }}</strong><div class="muted">{{ $e->ledger?->group?->name }}</div></td>
            <td>{{ $e->narration ?: '' }}</td>
            <td class="right mono">{{ (float)$e->dr_amount > 0 ? number_format((float)$e->dr_amount,2) : '' }}</td>
            <td class="right mono">{{ (float)$e->cr_amount > 0 ? number_format((float)$e->cr_amount,2) : '' }}</td>
        </tr>
        @endforeach
    </tbody>
    <tfoot class="totals">
        <tr>
            <td colspan="3" class="right">Totals</td>
            <td class="right mono">{{ number_format((float)$voucher->entries->sum('dr_amount'),2) }}</td>
            <td class="right mono">{{ number_format((float)$voucher->entries->sum('cr_amount'),2) }}</td>
        </tr>
    </tfoot>
</table>

<div style="margin-top:36px;display:flex;justify-content:space-between">
    <div>Prepared by: ___________________</div>
    <div>Authorised signatory: ___________________</div>
</div>

</body></html>
