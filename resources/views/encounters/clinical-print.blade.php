{{-- Clinical History & Examination print: $encounter, $allVisits, $visits = [[encounter, sections => [[label, html], ...]], ...] (html already sanitised) --}}
@php
    $p = $encounter->patient;
    $doc = $encounter->doctor;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $allVisits ? 'All Visits' : 'Clinical Notes' }} — {{ $p->first_name }} {{ $p->last_name }}</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'DM Sans', sans-serif; font-size: 13px; color: #1a2942; background: #fff; max-width: 800px; margin: 0 auto; padding: 28px 32px; }
    .hdr { display: flex; justify-content: space-between; align-items: flex-start; padding-bottom: 12px; margin-bottom: 14px; border-bottom: 3px solid #1a2942; gap: 16px; }
    .clinic { font-size: 21px; font-weight: 800; }
    .muted { color: #64748b; font-size: 11px; line-height: 1.6; }
    .doc { text-align: right; }
    .doc b { font-size: 14px; }
    .meta { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 14px; margin-bottom: 16px; }
    .meta div span { display: block; font-size: 10px; text-transform: uppercase; letter-spacing: .5px; color: #64748b; font-weight: 600; }
    .meta div b { font-size: 13px; }
    h2 { font-size: 15px; margin: 16px 0 8px; display: flex; align-items: center; gap: 8px; }
    h3 { font-size: 12px; text-transform: uppercase; letter-spacing: .5px; color: #64748b; margin: 14px 0 6px; }
    table { width: 100%; border-collapse: collapse; }
    .rx th, .rx td { border: 1px solid #cbd5e1; padding: 8px 10px; text-align: center; font-size: 14px; }
    .rx th { background: #f1f5f9; font-size: 11px; text-transform: uppercase; letter-spacing: .5px; }
    .rx td.eye { font-weight: 800; text-align: left; width: 110px; }
    .rx td { font-weight: 600; font-variant-numeric: tabular-nums; }
    .kv td { padding: 5px 0; border-bottom: 1px dashed #e2e8f0; vertical-align: top; }
    .kv td:first-child { width: 190px; color: #64748b; font-weight: 500; }
    .sign { margin-top: 48px; display: flex; justify-content: flex-end; }
    .sign div { text-align: center; border-top: 1px solid #1a2942; padding-top: 6px; min-width: 200px; }
    .toolbar { margin-bottom: 16px; display: flex; gap: 8px; }
    .toolbar button { padding: 7px 14px; border-radius: 6px; border: 1px solid #1a2942; background: #1a2942; color: #fff; font: inherit; cursor: pointer; }
    .sec { margin-bottom: 14px; page-break-inside: avoid; }
    .sec h3 { margin: 0 0 5px; padding-bottom: 4px; border-bottom: 1px solid #e2e8f0; }
    .sec-body { font-size: 13px; line-height: 1.55; }
    .sec-body p { margin: 0 0 4px; }
    .sec-body ul, .sec-body ol { padding-left: 20px; margin: 0 0 4px; }
    .visit-hdr { display: flex; justify-content: space-between; gap: 12px; align-items: baseline; background: #f1f5f9; border-left: 4px solid #1a2942; padding: 7px 12px; margin: 22px 0 10px; page-break-after: avoid; }
    .visit-hdr b { font-size: 14px; }
    .visit-hdr span { color: #64748b; font-size: 11.5px; }
    .sec-body.empty { color: #94a3b8; font-style: italic; }
    @media print { .toolbar { display: none; } body { padding: 10px 16px; } }
    @media (max-width: 600px) { body { padding: 16px; } .meta { grid-template-columns: 1fr 1fr; } .hdr { flex-direction: column; } .doc { text-align: left; } .kv td:first-child { width: 120px; } }
</style>
</head>
<body>
<div class="toolbar"><button onclick="window.print()">Print</button></div>

<div class="hdr">
    <div>
        <div class="clinic">{{ setting('clinic_name', 'James Heart Care Pvt. Ltd.') }}</div>
        <div class="muted">{{ setting('clinic_address', '') }}</div>
        <div class="muted">Ph: {{ setting('clinic_phone') }} · {{ setting('clinic_email') }}</div>
    </div>
    @if($doc)
    <div class="doc">
        <b>{{ $doc->full_name }}</b>
        <div class="muted">{{ preg_replace('/\s*[\r\n]+\s*/', ' · ', trim($doc->qualification ?? '')) }}{{ !empty($doc->registration_no) ? ' · Reg. '.$doc->registration_no : '' }}</div>
    </div>
    @endif
</div>

<div class="meta">
    <div><span>Patient</span><b>{{ $p->first_name }} {{ $p->last_name }}</b></div>
    <div><span>Age / Sex</span><b>{{ $p->age ?? '—' }}{{ $p->gender ? ' / '.ucfirst($p->gender) : '' }}</b></div>
    <div><span>Patient ID</span><b>{{ $p->jhc_id ?? $p->id }}</b></div>
    <div><span>Date</span><b>{{ $encounter->encounter_date?->format('d M Y') }}</b></div>
</div>

@if($allVisits)
<h2>All Visits — Clinical History &amp; Examination <span class="muted" style="font-weight:500">({{ $visits->count() }} {{ Str::plural('visit', $visits->count()) }}, date-wise)</span></h2>
@else
<h2>Clinical History &amp; Examination</h2>
@endif

@foreach($visits as $v)
@if($allVisits)
<div class="visit-hdr">
    <b>{{ $v['encounter']->encounter_date?->format('d M Y') ?? '—' }}</b>
    <span>Visit #{{ $v['encounter']->id }}{{ $v['encounter']->doctor ? ' · ' . $v['encounter']->doctor->full_name : '' }}{{ $v['encounter']->id === $encounter->id ? ' · this visit' : '' }}</span>
</div>
@endif
@php $filled = collect($v['sections'])->filter(fn ($s) => $s['html'] !== ''); @endphp
@foreach($allVisits ? $filled : $v['sections'] as $sec)
<div class="sec">
    <h3>{{ $sec['label'] }}</h3>
    @if($sec['html'] !== '')
    <div class="sec-body">{!! $sec['html'] !!}</div>
    @else
    <div class="sec-body empty">Not recorded</div>
    @endif
</div>
@endforeach
@if($allVisits && $filled->isEmpty())
<div class="sec-body empty">Nothing recorded for the selected sections.</div>
@endif
@endforeach

<div class="sign"><div>Doctor's signature</div></div>
</body>
</html>
