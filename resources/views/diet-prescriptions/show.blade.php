{{-- FILE: resources/views/diet-prescriptions/show.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Diet Prescription — {{ $dietPrescription->patient->full_name }}</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
* { box-sizing:border-box;margin:0;padding:0 }
body { font-family:'Segoe UI',Arial,sans-serif;font-size:11.5pt;color:#111;background:#fff }
@page { margin:15mm 18mm }
@media print {
    .no-print { display:none !important }
    body { print-color-adjust:exact;-webkit-print-color-adjust:exact }
}
.no-print { background:#0f1f3d;padding:10px 20px;display:flex;gap:10px;align-items:center;margin-bottom:0 }
.no-print button, .no-print a { padding:7px 18px;border-radius:5px;border:none;cursor:pointer;font-size:13px;text-decoration:none }
.no-print button { background:#0ab4b4;color:#fff }
.no-print a { background:rgba(255,255,255,.15);color:#fff }
.letterhead { display:flex;justify-content:space-between;align-items:flex-end;padding-bottom:10px;border-bottom:3px double #0f1f3d;margin-bottom:12px }
.clinic-name { font-size:17pt;font-weight:700;color:#0f1f3d }
.clinic-sub  { font-size:9pt;color:#555;margin-top:2px }
.doc-title   { text-align:center;font-size:14pt;font-weight:700;text-transform:uppercase;text-decoration:underline;letter-spacing:1px;margin:10px 0 12px }
.info-grid   { display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;margin-bottom:12px }
.info-cell   { background:#f7f8fc;border-radius:6px;padding:8px 12px }
.info-cell .lbl { font-size:8.5pt;color:#666;text-transform:uppercase;letter-spacing:.5px;margin-bottom:2px }
.info-cell .val { font-size:12pt;font-weight:700;color:#0f1f3d }
.bmi-badge   { display:inline-block;padding:3px 12px;border-radius:12px;font-size:9.5pt;font-weight:700;margin-left:8px }
.section { margin:12px 0 }
.sec-title { background:#0f1f3d;color:#fff;font-size:9.5pt;font-weight:700;text-transform:uppercase;letter-spacing:.6px;padding:4px 10px;margin-bottom:8px }
.key-points ul { padding-left:18px;font-size:10.5pt;line-height:1.8 }
.tag-list { display:flex;flex-wrap:wrap;gap:5px;margin-bottom:8px }
.tag-avoid  { padding:2px 10px;border-radius:10px;font-size:9.5pt;background:#fef2f2;color:#991b1b;border:1px solid #fca5a5 }
.tag-include{ padding:2px 10px;border-radius:10px;font-size:9.5pt;background:#f0fdf4;color:#166534;border:1px solid #86efac }
.meal-table { width:100%;border-collapse:collapse;font-size:10.5pt }
.meal-table td { padding:5px 8px;vertical-align:top;border:1px solid #ddd }
.meal-table td:first-child { font-weight:700;color:#0f1f3d;width:26%;background:#f7f8fc;white-space:nowrap }
.plan-block { margin-bottom:10px;padding:8px 10px;border:1px solid #e8ecf0;border-radius:5px;page-break-inside:avoid }
.plan-title { font-weight:700;font-size:11pt;color:#0f1f3d;margin-bottom:4px }
.cond-badge { display:inline-block;padding:2px 10px;border-radius:10px;font-size:9pt;font-weight:700;margin:2px;border:1px solid #e5e7eb }
.sig-area { display:flex;justify-content:space-between;margin-top:24px }
.sig-box  { text-align:center;width:150px }
.sig-box .line { border-top:1px solid #333;padding-top:5px;font-size:9pt;line-height:1.5 }
.footer { margin-top:12px;font-size:8pt;color:#888;border-top:1px solid #ccc;padding-top:6px;text-align:center }
.two-col { display:grid;grid-template-columns:1fr 1fr;gap:12px }
</style>
</head>
<body>

<div class="no-print">
    <button onclick="window.print()"><i class="fa-solid fa-print"></i> Print / Save PDF</button>
    <a href="{{ route('diet-prescriptions.create', ['patient_id'=>$dietPrescription->patient_id]) }}"><i class="fa-solid fa-plus"></i> New Prescription</a>
    <a href="{{ route('patients.show', $dietPrescription->patient) }}"><i class="fa-solid fa-arrow-left"></i> Back to Patient</a>
</div>

{{-- Letterhead --}}
@if(setting('print_header_enabled','1') !== '0')
<div class="letterhead">
    <div>
        <div class="clinic-name">{{ setting('clinic_name','Hospital') }}</div>
        @if(setting('clinic_address'))<div class="clinic-sub">{{ setting('clinic_address') }}</div>@endif
        @if(setting('clinic_phone'))<div class="clinic-sub">Ph: {{ setting('clinic_phone') }}</div>@endif
    </div>
    <div style="text-align:right;font-size:9pt;color:#555">
        <div>Date: <strong>{{ $dietPrescription->prescribed_at->format('d M Y') }}</strong></div>
        <div>Prescribed by: <strong>{{ $dietPrescription->prescribedBy->name }}</strong></div>
        <div>Rx #: <strong class="mono">DP-{{ str_pad($dietPrescription->id, 5, '0', STR_PAD_LEFT) }}</strong></div>
    </div>
</div>
@endif

<div class="doc-title">Diet Prescription</div>

{{-- Patient info --}}
<div class="info-grid">
    <div class="info-cell">
        <div class="lbl">Patient</div>
        <div class="val">{{ $dietPrescription->patient->full_name }}</div>
        <div style="font-size:9pt;color:#555">{{ $dietPrescription->patient->jhc_id }} · {{ $dietPrescription->patient->age }}</div>
    </div>
    <div class="info-cell">
        <div class="lbl">Height / Weight</div>
        <div class="val">{{ $dietPrescription->height_cm }} cm &nbsp;·&nbsp; {{ $dietPrescription->weight_kg }} kg</div>
        @if($dietPrescription->target_weight_kg)
        <div style="font-size:9pt;color:#555">Target: {{ $dietPrescription->target_weight_kg }} kg</div>
        @endif
    </div>
    <div class="info-cell">
        <div class="lbl">BMI</div>
        <div class="val" style="color:{{ \App\Models\DietPrescription::bmiColor($dietPrescription->bmi) }}">
            {{ $dietPrescription->bmi }}
            <span class="bmi-badge" style="background:{{ \App\Models\DietPrescription::bmiColor($dietPrescription->bmi) }}20;color:{{ \App\Models\DietPrescription::bmiColor($dietPrescription->bmi) }};font-size:9pt">
                {{ \App\Models\DietPrescription::bmiCategory($dietPrescription->bmi) }}
            </span>
        </div>
    </div>
</div>
<div class="info-grid">
    <div class="info-cell">
        <div class="lbl">Daily Calorie Target</div>
        <div class="val" style="color:#0ab4b4;font-size:18pt">{{ number_format($dietPrescription->calorie_target) }} kcal</div>
        <div style="font-size:9pt;color:#555">Activity: {{ \App\Models\DietPrescription::ACTIVITY_LEVELS[$dietPrescription->activity_level]['label'] }}</div>
    </div>
    <div class="info-cell" style="grid-column:span 2">
        <div class="lbl">Conditions</div>
        <div style="margin-top:4px">
            @foreach($dietPrescription->conditions as $ck)
            @php $cm = $conditions[$ck] ?? null; @endphp
            @if($cm)
            <span class="cond-badge">{{ $cm['label'] }}</span>
            @endif
            @endforeach
        </div>
    </div>
</div>

{{-- Per-condition diet plans --}}
@foreach($dietPrescription->conditions as $ck)
@php $plan = $dietPlans[$ck] ?? null; $cm = $conditions[$ck] ?? null; @endphp
@if($plan)
<div class="section plan-block">
    <div class="plan-title">{{ $plan['title'] }} &nbsp;<span style="font-size:9pt;color:#666;font-weight:400">{{ $plan['macro'] }}</span></div>
    <div class="key-points"><ul>@foreach($plan['key_points'] as $pt)<li>{{ $pt }}</li>@endforeach</ul></div>
</div>
@endif
@endforeach

{{-- Combined avoid/include --}}
<div class="two-col">
    <div class="section">
        <div class="sec-title"><i class="fa-solid fa-ban"></i> Foods to Avoid</div>
        <div class="tag-list">
            @foreach(array_unique(array_merge(...array_map(fn($k) => $dietPlans[$k]['avoid'] ?? [], $dietPrescription->conditions))) as $f)
            <span class="tag-avoid">{{ $f }}</span>
            @endforeach
        </div>
    </div>
    <div class="section">
        <div class="sec-title"><i class="fa-solid fa-check"></i> Foods to Include</div>
        <div class="tag-list">
            @foreach(array_unique(array_merge(...array_map(fn($k) => $dietPlans[$k]['include'] ?? [], $dietPrescription->conditions))) as $f)
            <span class="tag-include">{{ $f }}</span>
            @endforeach
        </div>
    </div>
</div>

{{-- Sample Meal Plan (from primary condition) --}}
@php $primaryKey = $dietPrescription->conditions[0] ?? null; $primaryPlan = $primaryKey ? ($dietPlans[$primaryKey] ?? null) : null; @endphp
@if($primaryPlan && isset($primaryPlan['meal_plan']))
<div class="section">
    <div class="sec-title"><i class="fa-solid fa-clock"></i> Sample Meal Plan ({{ $primaryPlan['title'] }})</div>
    <table class="meal-table">
        @foreach($primaryPlan['meal_plan'] as $time => $food)
        <tr><td>{{ $time }}</td><td>{{ $food }}</td></tr>
        @endforeach
    </table>
</div>
@endif

@if($dietPrescription->diet_notes)
<div class="section">
    <div class="sec-title"><i class="fa-solid fa-pen"></i> Special Instructions</div>
    <div style="font-size:10.5pt;line-height:1.7;padding:4px 0">{{ $dietPrescription->diet_notes }}</div>
</div>
@endif

<div class="sig-area">
    <div class="sig-box"><div class="line">Patient Signature</div></div>
    <div class="sig-box">
        <div class="line">
            {{ $dietPrescription->prescribedBy->name }}<br>
            Dietitian / Physician<br>
            {{ $dietPrescription->prescribed_at->format('d M Y') }}
        </div>
    </div>
</div>
@if(setting('print_footer_enabled','1') !== '0')
<div class="footer">Computer-generated diet prescription &nbsp;·&nbsp; {{ setting('clinic_name','Hospital') }} &nbsp;·&nbsp; {{ now()->format('d M Y, h:i A') }}</div>
@endif

</body>
</html>
