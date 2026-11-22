{{-- Referral letter: $r (OpdReferral with patient, doctors, departments, fromEncounter.diagnoses/prescriptions) --}}
@php
    $p = $r->patient;
    $e = $r->fromEncounter;
    $doc = $r->fromDoctor;
    $vitals = $e ? array_filter([
        'BP'     => $e->bp_systolic ? "{$e->bp_systolic}/{$e->bp_diastolic} mmHg" : null,
        'Pulse'  => $e->pulse ? "{$e->pulse} /min" : null,
        'SpO₂'   => $e->spo2 ? "{$e->spo2}%" : null,
        'Temp'   => $e->temperature ? "{$e->temperature} °F" : null,
        'Weight' => $e->weight ? "{$e->weight} kg" : null,
        'Sugar'  => $e->blood_glucose ? "{$e->blood_glucose} mg/dL" : null,
    ]) : [];
    $meds = $e ? $e->prescriptions->flatMap->items : collect();
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Referral Letter — {{ $p->first_name }} {{ $p->last_name }}</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'DM Sans', sans-serif; font-size: 13px; color: #1a2942; background: #fff; max-width: 800px; margin: 0 auto; padding: 28px 32px; line-height: 1.5; }
    .hdr { display: flex; justify-content: space-between; align-items: flex-start; padding-bottom: 12px; margin-bottom: 14px; border-bottom: 3px solid #1a2942; gap: 16px; }
    .clinic { font-size: 21px; font-weight: 800; }
    .muted { color: #64748b; font-size: 11px; line-height: 1.6; }
    .doc { text-align: right; }
    .doc b { font-size: 14px; }
    h2 { font-size: 16px; margin: 4px 0 12px; letter-spacing: .5px; text-align: center; text-transform: uppercase; }
    .meta { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 14px; margin-bottom: 14px; }
    .meta div span, .to span { display: block; font-size: 10px; text-transform: uppercase; letter-spacing: .5px; color: #64748b; font-weight: 600; }
    .meta div b { font-size: 13px; }
    .to { border-left: 4px solid #7c3aed; background: #f5f3ff; padding: 10px 14px; border-radius: 6px; margin-bottom: 14px; display: flex; gap: 30px; flex-wrap: wrap; }
    .to b { font-size: 14px; }
    h3 { font-size: 12px; text-transform: uppercase; letter-spacing: .5px; color: #64748b; margin: 14px 0 6px; }
    p.body { white-space: pre-line; }
    .urgent { display: inline-block; background: #fee2e2; color: #991b1b; font-weight: 700; padding: 1px 8px; border-radius: 10px; font-size: 11px; margin-left: 6px; }
    table { width: 100%; border-collapse: collapse; }
    .kv td { padding: 4px 0; border-bottom: 1px dashed #e2e8f0; vertical-align: top; }
    .kv td:first-child { width: 40%; font-weight: 600; }
    .chips span { display: inline-block; border: 1px solid #cbd5e1; border-radius: 6px; padding: 2px 8px; margin: 0 6px 6px 0; }
    .sign { margin-top: 48px; display: flex; justify-content: space-between; gap: 20px; }
    .sign div { text-align: center; border-top: 1px solid #1a2942; padding-top: 6px; min-width: 200px; }
    .toolbar { margin-bottom: 16px; display: flex; gap: 8px; }
    .toolbar button { padding: 7px 14px; border-radius: 6px; border: 1px solid #1a2942; background: #1a2942; color: #fff; font: inherit; cursor: pointer; }
    @media print { .toolbar { display: none; } body { padding: 10px 16px; } }
    @media (max-width: 600px) { body { padding: 16px; } .meta { grid-template-columns: 1fr 1fr; } .hdr { flex-direction: column; } .doc { text-align: left; } }
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
        @if($r->fromDepartment)<div class="muted">{{ $r->fromDepartment->name }}</div>@endif
    </div>
    @endif
</div>

<h2>Referral Letter @if($r->priority === 'urgent')<span class="urgent">URGENT</span>@endif</h2>

<div class="meta">
    <div><span>Patient</span><b>{{ $p->first_name }} {{ $p->last_name }}</b></div>
    <div><span>Age / Sex</span><b>{{ $p->age ?? '—' }}{{ $p->gender ? ' / '.ucfirst($p->gender) : '' }}</b></div>
    <div><span>Patient ID</span><b>{{ $p->jhc_id ?? $p->id }}</b></div>
    <div><span>Date</span><b>{{ $r->created_at->format('d M Y') }}</b></div>
</div>

<div class="to">
    <div><span>Referred to</span><b>{{ $r->toDepartment?->name }}</b>{{ $r->toDepartment?->room ? ' · '.$r->toDepartment->room : '' }}</div>
    <div><span>Doctor</span><b>{{ $r->toDoctor?->full_name ?? 'Any doctor in the department' }}</b></div>
    @if($r->token)<div><span>Token</span><b>{{ $r->token->display }}</b> · {{ $r->token->token_date->format('d M') }}</div>
    @elseif($r->appointment)<div><span>Appointment</span><b>{{ \Carbon\Carbon::parse($r->appointment->appointment_date)->format('d M Y') }}</b>{{ $r->appointment->appointment_time ? ' · '.\Carbon\Carbon::parse($r->appointment->appointment_time)->format('h:i A') : '' }}</div>
    @elseif($r->when === 'later' && $r->preferred_date)<div><span>Please see on</span><b>{{ $r->preferred_date->format('d M Y') }}</b></div>@endif
</div>

<p>Dear Doctor,</p>
<p style="margin-top:6px">Kindly see this patient for: <b>{{ $r->reason }}</b></p>

@if($r->notes)
<h3>Clinical note</h3>
<p class="body">{{ $r->notes }}</p>
@endif

@if($e && ($e->chief_complaint || $e->examination_findings))
<h3>Presenting complaint / findings</h3>
@if($e->chief_complaint)<p class="body">{{ $e->chief_complaint }}</p>@endif
@if($e->examination_findings)<p class="body" style="margin-top:4px">{{ $e->examination_findings }}</p>@endif
@endif

@if($vitals)
<h3>Vitals ({{ $e->encounter_date?->format('d M') }})</h3>
<div class="chips">@foreach($vitals as $k => $v)<span><b>{{ $k }}</b> {{ $v }}</span>@endforeach</div>
@endif

@if($e && $e->diagnoses->isNotEmpty())
<h3>Diagnosis</h3>
<div class="chips">@foreach($e->diagnoses as $d)<span>{{ $d->diagnosis_name }}{{ $d->icd_code ? ' ('.$d->icd_code.')' : '' }}</span>@endforeach</div>
@endif

@if($p->allergies)
<h3>Allergies</h3>
<p style="color:#b91c1c;font-weight:600">{{ $p->allergies }}</p>
@endif

@if($meds->isNotEmpty())
<h3>Current medicines</h3>
<table class="kv">
    @foreach($meds as $m)
    <tr><td>{{ $m->medicine?->name ?? $m->generic_name }}{{ $m->medicine?->strength ? ' '.$m->medicine->strength : '' }}</td><td>{{ trim(($m->dose ?? '').' '.($m->frequency ?? '')) }}{{ $m->duration_days ? ' · '.$m->duration_days.' days' : '' }}{{ $m->instructions ? ' · '.$m->instructions : '' }}</td></tr>
    @endforeach
</table>
@endif

<p style="margin-top:16px">Thank you.</p>

<div class="sign">
    <div>Seen by (name &amp; signature)</div>
    <div>{{ $doc?->full_name ?? "Referring doctor's signature" }}</div>
</div>
</body>
</html>
