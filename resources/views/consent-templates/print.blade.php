<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Consent — {{ $patient->full_name }}</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
* { margin:0; padding:0; box-sizing:border-box }
body { font-family:'DM Sans',sans-serif; font-size:13px; color:#1a2942; background:#fff; max-width:800px; margin:0 auto; padding:24px 28px }
.header { display:flex; justify-content:space-between; align-items:flex-start; border-bottom:3px solid #1a2942; padding-bottom:12px; margin-bottom:18px }
.clinic { font-size:20px; font-weight:800; color:#1a2942 }
.title  { font-size:14px; font-weight:700; text-transform:uppercase; letter-spacing:1.5px; color:#0ab4b4 }
.meta { background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:10px 14px; margin-bottom:14px; display:grid; grid-template-columns:1fr 1fr 1fr; gap:10px; font-size:12px }
.meta strong { display:block; font-size:10px; text-transform:uppercase; letter-spacing:.4px; color:#64748b; margin-bottom:1px; font-weight:600 }
.body { font-size:13px; line-height:1.7; padding:8px 4px 18px }
.body p { margin-bottom:10px }
.signs { display:grid; grid-template-columns:1fr 1fr; gap:30px; margin-top:30px }
.sign-box { border-top:1px solid #1a2942; padding-top:6px; font-size:11.5px; color:#475569 }
.sign-box strong { color:#1a2942; font-size:12.5px }
.no-print { text-align:center; margin-bottom:14px; padding:10px; background:#f0f4f8; border-radius:8px }
.btn { background:#1a2942; color:#fff; border:none; padding:8px 20px; border-radius:6px; font-size:12px; font-weight:600; cursor:pointer }
.btn-grey { background:#e2e8f0; color:#1a2942; margin-left:8px }
@media print { .no-print { display:none } body { padding:14px 20px } }
</style>
</head>
<body>
<div class="no-print">
    <button class="btn" onclick="window.print()">&#128424; Print</button>
    <button class="btn btn-grey" onclick="window.close()">Close</button>
</div>

@if(setting('print_header_enabled','1') !== '0')
<div class="header">
    <div>
        <div class="clinic">{{ setting('clinic_name', 'Demo Clinic Pvt. Ltd.') }}</div>
        <div style="font-size:11px;color:#64748b;margin-top:2px">{{ setting('clinic_address', '') }}</div>
    </div>
    <div style="text-align:right">
        <div class="title">{{ $tpl->name }}</div>
        <div style="font-size:11px;color:#64748b;margin-top:2px">{{ now()->format('d M Y H:i') }}</div>
    </div>
</div>
@endif

<div class="meta">
    <div><strong>Patient</strong>{{ $patient->full_name }}</div>
    <div><strong>JHC ID</strong>{{ $patient->jhc_id }}</div>
    <div><strong>Sex / Age</strong>{{ $patient->gender ?? '—' }} / {{ $patient->age }}</div>
    @if($procedure && $procedure !== '—')<div><strong>Procedure</strong>{{ $procedure }}</div>@endif
    <div><strong>Date</strong>{{ now()->format('d M Y') }}</div>
</div>

<div class="body">
    {!! $body !!}
</div>

<div class="signs">
    <div class="sign-box"><strong>Patient / Guardian Signature</strong><br>Name: ____________________________<br>Date: ___________________________</div>
    <div class="sign-box"><strong>Witness Signature</strong><br>Name: ____________________________<br>Date: ___________________________</div>
</div>
</body>
</html>
