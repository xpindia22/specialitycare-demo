{{-- FILE: resources/views/admissions/io-chart.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>I/O Chart — {{ $admission->ipd_no }} — {{ \Carbon\Carbon::parse($date)->format('d M Y') }}</title>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: Arial, sans-serif; font-size: 10pt; color: #111; background: #fff; }
    @page { margin: 14mm 12mm; size: A4; }
    @media print {
        .no-print { display: none !important; }
        body { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
    }
    .no-print { margin: 8px; }

    .letterhead { border-bottom: 2px solid #0f1f3d; padding-bottom: 8px; margin-bottom: 10px; display:flex; justify-content:space-between; align-items:flex-end; }
    .clinic-name { font-size: 15pt; font-weight: 700; color: #0f1f3d; }
    .doc-title { font-size: 13pt; font-weight: 700; text-align:center; text-transform:uppercase; letter-spacing:.8px; margin-bottom:8px; }

    .info-row { display:grid; grid-template-columns:repeat(4,1fr); gap:0; margin-bottom:10px; border:1px solid #ccc; }
    .info-cell { padding:5px 8px; border-right:1px solid #ddd; }
    .info-cell:last-child { border-right:none; }
    .info-label { font-weight:700; font-size:8pt; text-transform:uppercase; color:#555; margin-bottom:2px; }
    .info-val { font-size:10pt; }

    .balance-strip { display:grid; grid-template-columns:1fr 1fr 1fr; border:2px solid #0f1f3d; border-radius:6px; overflow:hidden; margin-bottom:14px; }
    .bal-cell { padding:10px 14px; text-align:center; }
    .bal-cell:first-child { background:#dcfce7; }
    .bal-cell:nth-child(2) { background:#fef3c7; border-left:1px solid #ddd; border-right:1px solid #ddd; }
    .bal-cell:last-child { background:#f0f4f8; }
    .bal-label { font-size:8pt; text-transform:uppercase; font-weight:700; color:#555; }
    .bal-value { font-size:16pt; font-weight:800; }
    .bal-in { color:#14532d; }
    .bal-out { color:#92400e; }
    .bal-net { }

    .section-title { background:#0f1f3d; color:#fff; padding:5px 10px; font-size:9.5pt; font-weight:700; text-transform:uppercase; letter-spacing:.8px; margin-bottom:0; }

    table.io-table { width:100%; border-collapse:collapse; font-size:9pt; margin-bottom:14px; }
    table.io-table th { background:#374151; color:#fff; padding:5px 8px; text-align:left; }
    table.io-table th.right { text-align:right; }
    table.io-table td { padding:5px 8px; border-bottom:1px solid #e5e7eb; }
    table.io-table td.right { text-align:right; font-weight:700; }
    table.io-table tr:nth-child(even) td { background:#f9fafb; }
    table.io-table tfoot td { font-weight:700; background:#f0f4f8; border-top:2px solid #0f1f3d; }

    .sig-row { display:flex; justify-content:flex-end; margin-top:24px; }
    .sig-box { text-align:center; width:180px; border-top:1px solid #333; padding-top:4px; font-size:9pt; }
    .footer { border-top:1px solid #ccc; margin-top:16px; padding-top:6px; font-size:8pt; color:#888; text-align:center; }
</style>
</head>
<body>
<div class="no-print" style="background:#f0f4f8;padding:10px;display:flex;gap:10px;align-items:center">
    <button onclick="window.print()" style="padding:7px 18px;background:#0f1f3d;color:#fff;border:none;border-radius:5px;cursor:pointer">🖨 Print I/O Chart</button>
    <a href="{{ route('admissions.show', $admission) }}#tab-io" style="color:#0ab4b4;text-decoration:none">← Back</a>
    <form method="GET" style="margin-left:auto;display:flex;gap:8px;align-items:center">
        <label style="font-size:13px">Date:</label>
        <input type="date" name="date" value="{{ $date }}" style="padding:4px 8px;border:1px solid #ccc;border-radius:4px">
        <button type="submit" style="padding:5px 14px;background:#0ab4b4;color:#fff;border:none;border-radius:4px;cursor:pointer">Go</button>
    </form>
</div>

<div class="letterhead">
    <div>
        <div class="clinic-name">{{ setting('clinic_name', 'Demo Clinic Pvt. Ltd.') }}</div>
        <div style="font-size:9pt;color:#555">{{ setting('clinic_address','') }}</div>
    </div>
    <div style="text-align:right;font-size:9pt;color:#555">
        @if(setting('clinic_phone'))<div>{{ setting('clinic_phone') }}</div>@endif
        @if(setting('clinic_email'))<div>{{ setting('clinic_email') }}</div>@endif
    </div>
</div>

<div class="doc-title">Input / Output Chart</div>

<div class="info-row">
    <div class="info-cell"><div class="info-label">Patient</div><div class="info-val">{{ $admission->patient->full_name }}</div></div>
    <div class="info-cell"><div class="info-label">IPD No.</div><div class="info-val">{{ $admission->ipd_no }}</div></div>
    <div class="info-cell"><div class="info-label">Ward / Bed</div><div class="info-val">{{ $admission->ward->name }}{{ $admission->bed ? ' · '.$admission->bed->bed_number : '' }}</div></div>
    <div class="info-cell"><div class="info-label">Date</div><div class="info-val">{{ \Carbon\Carbon::parse($date)->format('d M Y') }}</div></div>
</div>

<div class="balance-strip">
    <div class="bal-cell">
        <div class="bal-label">Total Input</div>
        <div class="bal-value bal-in">{{ number_format($totalIn) }} mL</div>
    </div>
    <div class="bal-cell">
        <div class="bal-label">Total Output</div>
        <div class="bal-value bal-out">{{ number_format($totalOut) }} mL</div>
    </div>
    <div class="bal-cell">
        <div class="bal-label">Net Balance</div>
        <div class="bal-value bal-net" style="color:{{ $balance >= 0 ? '#14532d' : '#991b1b' }}">
            {{ $balance >= 0 ? '+' : '' }}{{ number_format($balance) }} mL
        </div>
    </div>
</div>

@php
    $inputs  = $entries->where('io_type','input');
    $outputs = $entries->where('io_type','output');
@endphp

{{-- INPUTS --}}
<div class="section-title">Inputs</div>
@if($inputs->isEmpty())
<p style="padding:8px 10px;font-size:9pt;color:#888;border:1px solid #eee;border-top:none;margin-bottom:14px">No input entries.</p>
@else
<table class="io-table">
    <thead>
        <tr><th>Time</th><th>Category</th><th>Remarks / Details</th><th class="right">Amount (mL)</th><th>Recorded By</th></tr>
    </thead>
    <tbody>
    @foreach($inputs as $e)
    <tr>
        <td>{{ \Carbon\Carbon::parse($e->recorded_at)->format('H:i') }}</td>
        <td>{{ $e->category_label }}</td>
        <td>{{ $e->remarks ?? '—' }}</td>
        <td class="right">{{ number_format($e->amount_ml) }}</td>
        <td>{{ $e->recordedBy->name ?? '—' }}</td>
    </tr>
    @endforeach
    </tbody>
    <tfoot>
        <tr><td colspan="3" style="text-align:right">Total Input</td><td class="right">{{ number_format($totalIn) }} mL</td><td></td></tr>
    </tfoot>
</table>
@endif

{{-- OUTPUTS --}}
<div class="section-title">Outputs</div>
@if($outputs->isEmpty())
<p style="padding:8px 10px;font-size:9pt;color:#888;border:1px solid #eee;border-top:none;margin-bottom:14px">No output entries.</p>
@else
<table class="io-table">
    <thead>
        <tr><th>Time</th><th>Category</th><th>Remarks / Details</th><th class="right">Amount (mL)</th><th>Recorded By</th></tr>
    </thead>
    <tbody>
    @foreach($outputs as $e)
    <tr>
        <td>{{ \Carbon\Carbon::parse($e->recorded_at)->format('H:i') }}</td>
        <td>{{ $e->category_label }}</td>
        <td>{{ $e->remarks ?? '—' }}</td>
        <td class="right">{{ number_format($e->amount_ml) }}</td>
        <td>{{ $e->recordedBy->name ?? '—' }}</td>
    </tr>
    @endforeach
    </tbody>
    <tfoot>
        <tr><td colspan="3" style="text-align:right">Total Output</td><td class="right">{{ number_format($totalOut) }} mL</td><td></td></tr>
    </tfoot>
</table>
@endif

<div class="sig-row">
    <div class="sig-box">Nurse Signature<br><span style="font-size:8pt;color:#777">{{ setting('clinic_name','Demo Clinic Pvt. Ltd.') }}</span></div>
</div>

<div class="footer">
    {{ setting('clinic_name', 'Demo Clinic Pvt. Ltd.') }} &nbsp;·&nbsp; {{ setting('clinic_address', '') }}
    @if(setting('clinic_phone')) &nbsp;·&nbsp; {{ setting('clinic_phone') }} @endif
    &nbsp;·&nbsp; Printed on {{ now()->format('d M Y, h:i A') }}
</div>
</body>
</html>
