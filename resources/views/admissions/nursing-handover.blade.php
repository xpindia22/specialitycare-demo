{{-- FILE: resources/views/admissions/nursing-handover.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Nursing Handover — {{ $admission->ipd_no }} — {{ \Carbon\Carbon::parse($date)->format('d M Y') }}</title>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: Arial, sans-serif; font-size: 10pt; color: #111; background: #fff; }
    @page { margin: 12mm 14mm; size: A4; }
    @media print {
        .no-print { display: none !important; }
        body { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
    }
    .no-print { background:#f0f4f8; padding:10px; display:flex; gap:10px; align-items:center; }

    .letterhead { border-bottom: 2px solid #0f1f3d; padding-bottom: 8px; margin-bottom: 10px; display:flex; justify-content:space-between; align-items:flex-end; }
    .clinic-name { font-size: 14pt; font-weight: 700; color: #0f1f3d; }
    .doc-title { font-size: 13pt; font-weight: 700; text-align:center; text-transform:uppercase; letter-spacing:.8px; margin-bottom:10px; }

    .info-row { display:grid; grid-template-columns:repeat(5,1fr); border:1px solid #ccc; margin-bottom:12px; }
    .info-cell { padding:5px 8px; border-right:1px solid #ddd; }
    .info-cell:last-child { border-right:none; }
    .info-label { font-size:7.5pt; font-weight:700; text-transform:uppercase; color:#555; }
    .info-val { font-size:9.5pt; font-weight:600; }

    .shift-block { border: 1.5px solid #ddd; border-radius:6px; margin-bottom:12px; overflow:hidden; break-inside:avoid; }
    .shift-header { padding: 7px 12px; display:flex; align-items:center; gap:10px; font-size:10pt; font-weight:700; }
    .shift-morning { background:#fef3c7; border-bottom:1.5px solid #fcd34d; }
    .shift-evening { background:#ccfbf1; border-bottom:1.5px solid #5eead4; }
    .shift-night   { background:#e0e7ff; border-bottom:1.5px solid #a5b4fc; }
    .shift-badge   { font-size:8pt; font-weight:700; padding:2px 8px; border-radius:20px; }
    .badge-stable      { background:#dcfce7; color:#14532d; }
    .badge-improving   { background:#ccfbf1; color:#134e4a; }
    .badge-guarded     { background:#fef3c7; color:#92400e; }
    .badge-critical    { background:#fee2e2; color:#991b1b; }
    .badge-deteriorating { background:#fee2e2; color:#991b1b; }

    .shift-body { display:grid; grid-template-columns:1fr 1fr; padding:10px 12px; gap:8px 16px; font-size:9pt; }
    .field-block { }
    .field-label { font-size:7.5pt; font-weight:700; text-transform:uppercase; color:#555; margin-bottom:2px; }
    .field-value { min-height:18px; }
    .field-pending { color:#b45309; font-weight:600; }
    .full-width { grid-column: span 2; }
    .shift-empty { padding:10px 12px; font-size:9pt; color:#888; font-style:italic; }

    .sig-grid { display:grid; grid-template-columns:1fr 1fr 1fr; gap:20px; margin-top:20px; }
    .sig-box { text-align:center; border-top:1px solid #333; padding-top:4px; font-size:9pt; }

    .footer { border-top:1px solid #ccc; margin-top:14px; padding-top:6px; font-size:8pt; color:#888; text-align:center; }
    .vitals-strip { background:#f0f4f8; border:1px solid #e2e8f0; border-radius:4px; padding:6px 10px; margin-bottom:12px; display:flex; gap:20px; font-size:9pt; }
    .vs-item { }
    .vs-label { font-size:7.5pt; color:#555; text-transform:uppercase; font-weight:700; }
</style>
</head>
<body>
<div class="no-print">
    <button onclick="window.print()" style="padding:7px 18px;background:#0f1f3d;color:#fff;border:none;border-radius:5px;cursor:pointer">🖨 Print Handover Sheet</button>
    <a href="{{ route('admissions.show', $admission) }}#tab-nursing" style="color:#0ab4b4;text-decoration:none">← Back</a>
    <form method="GET" style="margin-left:auto;display:flex;gap:8px;align-items:center">
        <label style="font-size:13px">Date:</label>
        <input type="date" name="date" value="{{ $date }}" style="padding:4px 8px;border:1px solid #ccc;border-radius:4px">
        <button type="submit" style="padding:5px 14px;background:#0ab4b4;color:#fff;border:none;border-radius:4px;cursor:pointer">Go</button>
    </form>
</div>

@if(setting('print_header_enabled','1') !== '0')
<div class="letterhead">
    <div>
        <div class="clinic-name">{{ setting('clinic_name', 'Demo Clinic Pvt. Ltd.') }}</div>
        <div style="font-size:9pt;color:#555">{{ setting('clinic_address','') }}</div>
    </div>
    <div style="text-align:right;font-size:9pt;color:#555">
        @if(setting('clinic_phone'))<div>{{ setting('clinic_phone') }}</div>@endif
    </div>
</div>
@endif

<div class="doc-title">Nursing Shift Handover Report</div>

<div class="info-row">
    <div class="info-cell"><div class="info-label">Patient</div><div class="info-val">{{ $admission->patient->full_name }}</div></div>
    <div class="info-cell"><div class="info-label">Age / Sex</div><div class="info-val">{{ $admission->patient->age }} / {{ ucfirst($admission->patient->gender) }}</div></div>
    <div class="info-cell"><div class="info-label">IPD No.</div><div class="info-val">{{ $admission->ipd_no }}</div></div>
    <div class="info-cell"><div class="info-label">Ward / Bed</div><div class="info-val">{{ $admission->ward->name }}{{ $admission->bed ? ' – '.$admission->bed->bed_number : '' }}</div></div>
    <div class="info-cell"><div class="info-label">Date</div><div class="info-val">{{ \Carbon\Carbon::parse($date)->format('d M Y') }}</div></div>
</div>

@if($latestVitals)
<div class="vitals-strip">
    <strong style="align-self:center;margin-right:4px">Last Vitals:</strong>
    @if($latestVitals->temperature)<div class="vs-item"><div class="vs-label">Temp</div>{{ $latestVitals->temperature }}°F</div>@endif
    @if($latestVitals->pulse)<div class="vs-item"><div class="vs-label">Pulse</div>{{ $latestVitals->pulse }} bpm</div>@endif
    @if($latestVitals->bp)<div class="vs-item"><div class="vs-label">BP</div>{{ $latestVitals->bp }}</div>@endif
    @if($latestVitals->spo2)<div class="vs-item"><div class="vs-label">SpO₂</div>{{ $latestVitals->spo2 }}%</div>@endif
    @if($latestVitals->respiration_rate)<div class="vs-item"><div class="vs-label">RR</div>{{ $latestVitals->respiration_rate }}/min</div>@endif
    <div style="margin-left:auto;font-size:8.5pt;color:#888;align-self:center">{{ $latestVitals->recorded_at->format('d M H:i') }}</div>
</div>
@endif

@php $shiftDefs = \App\Models\NursingShift::SHIFTS; $condBadges = \App\Models\NursingShift::CONDITIONS; @endphp

@foreach(['morning','evening','night'] as $shiftKey)
@php $s = $shifts[$shiftKey] ?? null; $sd = $shiftDefs[$shiftKey]; @endphp
<div class="shift-block">
    <div class="shift-header shift-{{ $shiftKey }}">
        <span>{{ $sd['label'] }} Shift</span>
        <span style="font-size:9pt;font-weight:400">{{ $sd['time'] }}</span>
        @if($s && $s->patient_condition)
        <span class="shift-badge badge-{{ $s->patient_condition }}">{{ $s->condition_label }}</span>
        @endif
        @if($s)
        <span style="margin-left:auto;font-size:9pt;font-weight:400">Recorded by: {{ $s->writtenBy->name ?? '—' }}</span>
        @else
        <span style="margin-left:auto;font-size:9pt;color:#999;font-style:italic">Not recorded</span>
        @endif
    </div>

    @if(!$s)
    <div class="shift-empty">No shift note recorded for this shift.</div>
    @else
    <div class="shift-body">
        @if($s->assessment)
        <div class="field-block"><div class="field-label">Assessment</div><div class="field-value">{{ $s->assessment }}</div></div>
        @endif
        @if($s->care_given)
        <div class="field-block"><div class="field-label">Care Given</div><div class="field-value">{{ $s->care_given }}</div></div>
        @endif
        @if($s->medications_given)
        <div class="field-block"><div class="field-label">Medications Given</div><div class="field-value">{{ $s->medications_given }}</div></div>
        @endif
        @if($s->iv_lines)
        <div class="field-block"><div class="field-label">IV Lines / Sites</div><div class="field-value">{{ $s->iv_lines }}</div></div>
        @endif
        @if($s->special_instructions)
        <div class="field-block full-width"><div class="field-label">Special Instructions</div><div class="field-value">{{ $s->special_instructions }}</div></div>
        @endif
        @if($s->pending_tasks)
        <div class="field-block full-width"><div class="field-label" style="color:#b45309">⚠ Pending Tasks / Handover Points</div><div class="field-value field-pending">{{ $s->pending_tasks }}</div></div>
        @endif
    </div>
    @endif
</div>
@endforeach

<div class="sig-grid">
    @foreach(['morning','evening','night'] as $shiftKey)
    <div class="sig-box">
        {{ $shiftDefs[$shiftKey]['label'] }} Shift Nurse<br>
        <span style="font-size:8pt;color:#777">{{ $shiftDefs[$shiftKey]['time'] }}</span>
    </div>
    @endforeach
</div>

@if(setting('print_footer_enabled','1') !== '0')
<div class="footer">
    {{ setting('clinic_name', 'Demo Clinic Pvt. Ltd.') }} &nbsp;·&nbsp; {{ setting('clinic_address','') }}
    &nbsp;·&nbsp; Printed on {{ now()->format('d M Y, h:i A') }}
</div>
@endif
</body>
</html>
