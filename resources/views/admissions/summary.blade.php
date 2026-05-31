{{-- FILE: resources/views/admissions/summary.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Discharge Summary — {{ $admission->ipd_no }}</title>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Times New Roman', serif; font-size: 11.5pt; color: #111; background: #fff; }
    @page { margin: 18mm 18mm 18mm 18mm; }
    @media print {
        .no-print { display: none !important; }
        body { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
        .page-break { page-break-before: always; }
    }
    .no-print { background: #f0f4f8; padding: 12px 16px; display: flex; gap: 10px; align-items: center; margin-bottom: 0; }
    .no-print button { padding: 7px 18px; background: #0f1f3d; color: #fff; border: none; border-radius: 5px; cursor: pointer; font-size: 13px; }
    .no-print a { color: #0ab4b4; text-decoration: none; font-size: 13px; }

    .letterhead { border-bottom: 3px double #0f1f3d; padding-bottom: 10px; margin-bottom: 12px; display: flex; justify-content: space-between; align-items: flex-end; }
    .clinic-name { font-size: 17pt; font-weight: 700; color: #0f1f3d; }
    .clinic-sub  { font-size: 9pt; color: #555; margin-top: 2px; }
    .doc-title   { text-align: center; font-size: 14pt; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; text-decoration: underline; margin: 10px 0; }

    table.info-table { width: 100%; border-collapse: collapse; font-size: 10.5pt; }
    table.info-table td { padding: 3.5px 8px; vertical-align: top; }
    table.info-table td.label { font-weight: 700; width: 22%; color: #333; }
    table.info-table td.value { width: 28%; }
    table.info-table tr:nth-child(odd) td { background: #f7f7f7; }

    .section { margin: 12px 0 6px; }
    .section-title { font-size: 10.5pt; font-weight: 700; text-transform: uppercase; letter-spacing: .6px;
        background: #0f1f3d; color: #fff; padding: 4px 10px; margin-bottom: 6px; }
    .section-body  { font-size: 10.5pt; white-space: pre-wrap; line-height: 1.6; padding: 0 4px; }

    table.data-table { width: 100%; border-collapse: collapse; font-size: 10pt; }
    table.data-table th { background: #e8ecf0; font-weight: 700; padding: 5px 8px; text-align: left; border: 1px solid #ccc; }
    table.data-table td { padding: 4px 8px; border: 1px solid #ddd; vertical-align: top; }
    table.data-table tr:nth-child(even) td { background: #fafafa; }

    .sig-area { margin-top: 30px; display: flex; justify-content: space-between; }
    .sig-box { text-align: center; width: 160px; }
    .sig-box .line { border-top: 1px solid #333; padding-top: 5px; font-size: 9.5pt; line-height: 1.5; }

    .footer-note { margin-top: 14px; font-size: 8.5pt; color: #666; border-top: 1px solid #ccc; padding-top: 6px; text-align: center; }
    .bold { font-weight: 700; }
    .mono { font-family: monospace; }
</style>
</head>
<body>

<div class="no-print">
    <button onclick="window.print()">&#128438; Print / Save PDF</button>
    <a href="{{ route('admissions.show', $admission) }}">&#8592; Back to Admission</a>
</div>

{{-- Letterhead --}}
@if(setting('print_header_enabled','1') !== '0')
<div class="letterhead">
    <div>
        <div class="clinic-name">{{ setting('clinic_name', 'Hospital') }}</div>
        @if(setting('clinic_address'))
        <div class="clinic-sub">{{ setting('clinic_address') }}</div>
        @endif
        @if(setting('clinic_phone'))
        <div class="clinic-sub">Ph: {{ setting('clinic_phone') }}{{ setting('clinic_email') ? ' · ' . setting('clinic_email') : '' }}</div>
        @endif
    </div>
    <div style="text-align:right;font-size:9pt;color:#555">
        <div>Reg. No: {{ setting('clinic_reg_no', '—') }}</div>
        <div>IPD: <strong class="mono">{{ $admission->ipd_no }}</strong></div>
        <div>{{ $admission->admission_date->format('d-m-Y') }} — {{ $admission->discharge_date?->format('d-m-Y') ?? 'Not discharged' }}</div>
    </div>
</div>
@endif

<div class="doc-title">Discharge Summary</div>

{{-- Patient & admission info --}}
<div class="section">
    <div class="section-title">Patient Information</div>
    <table class="info-table">
        <tr>
            <td class="label">Patient Name</td><td class="value bold">{{ $admission->patient->full_name }}</td>
            <td class="label">JHC ID</td><td class="value mono">{{ $admission->patient->jhc_id }}</td>
        </tr>
        <tr>
            <td class="label">Age / Gender</td><td>{{ $admission->patient->age }} / {{ ucfirst($admission->patient->gender) }}</td>
            <td class="label">Blood Group</td><td>{{ $admission->patient->blood_group ?: '—' }}</td>
        </tr>
        <tr>
            <td class="label">Phone</td><td>{{ $admission->patient->phone }}</td>
            <td class="label">Address</td><td>{{ Str::limit($admission->patient->address ?? '—', 60) }}</td>
        </tr>
        @if($admission->patient->emergency_contact_name)
        <tr>
            <td class="label">Emergency Contact</td><td colspan="3">{{ $admission->patient->emergency_contact_name }} — {{ $admission->patient->emergency_contact_phone }}</td>
        </tr>
        @endif
    </table>
</div>

<div class="section">
    <div class="section-title">Admission Details</div>
    <table class="info-table">
        <tr>
            <td class="label">IPD Number</td><td class="mono bold">{{ $admission->ipd_no }}</td>
            <td class="label">Admission Type</td><td>{{ \App\Models\Admission::ADMISSION_TYPES[$admission->admission_type] ?? $admission->admission_type }}</td>
        </tr>
        <tr>
            <td class="label">Date of Admission</td><td>{{ $admission->admission_date->format('d M Y, h:i A') }}</td>
            <td class="label">Date of Discharge</td><td>{{ $admission->discharge_date?->format('d M Y, h:i A') ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Total Stay</td><td>{{ $admission->days_admitted }} day{{ $admission->days_admitted != 1 ? 's' : '' }}</td>
            <td class="label">Discharge Condition</td><td>{{ \App\Models\Admission::DISCHARGE_CONDITIONS[$admission->discharge_condition] ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Ward / Bed</td><td>{{ $admission->ward->name }} — Bed {{ $admission->bed->bed_number }}</td>
            <td class="label">Admitting Doctor</td><td>{{ $admission->admittingDoctor->full_name }}{{ $admission->admittingDoctor->specialization ? ' ('.$admission->admittingDoctor->specialization.')' : '' }}</td>
        </tr>
        @if($admission->referred_by)
        <tr><td class="label">Referred By</td><td colspan="3">{{ $admission->referred_by }}</td></tr>
        @endif
    </table>
</div>

@if($admission->provisional_diagnosis)
<div class="section">
    <div class="section-title">Diagnosis</div>
    <div class="section-body">{{ $admission->provisional_diagnosis }}</div>
</div>
@endif

@if(isset($encounters) && $encounters->isNotEmpty())
@php $firstEnc = $encounters->first(); @endphp
<div class="section">
    <div class="section-title">Clinical History &amp; Examination</div>
    <table class="info-table">
        @if($firstEnc->chief_complaint)
        <tr><td class="label" style="width:22%">Chief Complaint</td><td colspan="3">{!! nl2br(e(strip_tags($firstEnc->chief_complaint))) !!}</td></tr>
        @endif
        @if($firstEnc->history_of_present_illness)
        <tr><td class="label">History of Present Illness</td><td colspan="3">{!! nl2br(e(strip_tags($firstEnc->history_of_present_illness))) !!}</td></tr>
        @endif
        @if($firstEnc->past_medical_history)
        <tr><td class="label">Past Medical History</td><td colspan="3">{!! nl2br(e(strip_tags($firstEnc->past_medical_history))) !!}</td></tr>
        @endif
        @if($firstEnc->family_history)
        <tr><td class="label">Family History</td><td colspan="3">{!! nl2br(e(strip_tags($firstEnc->family_history))) !!}</td></tr>
        @endif
        @if($firstEnc->social_history)
        <tr><td class="label">Social History</td><td colspan="3">{!! nl2br(e(strip_tags($firstEnc->social_history))) !!}</td></tr>
        @endif
        @if($firstEnc->examination_findings)
        <tr><td class="label">Examination Findings</td><td colspan="3">{!! nl2br(e(strip_tags($firstEnc->examination_findings))) !!}</td></tr>
        @endif
        @if($firstEnc->impression)
        <tr><td class="label">Impression</td><td colspan="3">{!! nl2br(e(strip_tags($firstEnc->impression))) !!}</td></tr>
        @endif
        @if($firstEnc->plan)
        <tr><td class="label">Plan</td><td colspan="3">{!! nl2br(e(strip_tags($firstEnc->plan))) !!}</td></tr>
        @endif
    </table>
</div>
@endif

@if($admission->discharge_summary)
<div class="section">
    <div class="section-title">Clinical Summary</div>
    <div class="section-body">{{ $admission->discharge_summary }}</div>
</div>
@endif

@if($admission->discharge_notes)
<div class="section">
    <div class="section-title">Discharge Notes / Instructions</div>
    <div class="section-body">{{ $admission->discharge_notes }}</div>
</div>
@endif

{{-- Doctor's rounds / clinical notes --}}
@if($ipdNotes->count())
<div class="section">
    <div class="section-title">Doctor's Progress Notes</div>
    <table class="data-table">
        <thead>
            <tr><th style="width:18%">Date & Time</th><th style="width:20%">By</th><th>Note</th></tr>
        </thead>
        <tbody>
        @foreach($ipdNotes as $note)
        <tr>
            <td style="white-space:nowrap">{{ $note->noted_at->format('d M Y') }}<br><small>{{ $note->noted_at->format('h:i A') }}</small></td>
            <td>{{ $note->writtenBy->name }}</td>
            <td style="white-space:pre-wrap">{{ $note->note }}</td>
        </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- Medications given --}}
@if($admission->prescriptions->count())
<div class="section">
    <div class="section-title">Medications Prescribed During Admission</div>
    <table class="data-table">
        <thead>
            <tr><th>Drug Name</th><th>Dose</th><th>Route</th><th>Frequency</th><th>Start</th><th>End</th><th>Prescribed By</th></tr>
        </thead>
        <tbody>
        @foreach($admission->prescriptions as $rx)
        <tr>
            <td class="bold">{{ $rx->drug_name }}</td>
            <td>{{ $rx->dose }}</td>
            <td>{{ $rx->route }}</td>
            <td>{{ $rx->frequency_label }}</td>
            <td>{{ $rx->start_date->format('d M Y') }}</td>
            <td>{{ $rx->end_date ? $rx->end_date->format('d M Y') : ($rx->is_active ? 'Active' : 'Stopped') }}</td>
            <td>{{ $rx->prescribedBy->name }}</td>
        </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- Charges summary --}}
@if($admission->charges->count())
@php $totalCharges = $admission->charges->sum('amount'); @endphp
<div class="section">
    <div class="section-title">Charges Summary</div>
    <table class="data-table">
        <thead><tr><th>Category</th><th>Total</th></tr></thead>
        <tbody>
        @foreach($admission->charges->groupBy('category') as $cat => $catCharges)
        @php $catMeta = \App\Models\IpdCharge::CATEGORIES[$cat] ?? ['label' => ucfirst($cat)]; @endphp
        <tr><td>{{ $catMeta['label'] }}</td><td>₹{{ number_format($catCharges->sum('amount'), 2) }}</td></tr>
        @endforeach
        </tbody>
        <tfoot>
            <tr><td class="bold">Total</td><td class="bold">₹{{ number_format($totalCharges, 2) }}</td></tr>
        </tfoot>
    </table>
</div>
@endif

<div class="sig-area">
    <div class="sig-box">
        <div class="line">Patient / Attendant Signature</div>
    </div>
    <div class="sig-box">
        <div class="line">Nursing In-charge</div>
    </div>
    <div class="sig-box">
        <div class="line">
            {{ $admission->admittingDoctor->full_name }}<br>
            {{ $admission->admittingDoctor->specialization ?? 'Physician' }}<br>
            @if($admission->admittingDoctor->registration_no)
            Reg. No: {{ $admission->admittingDoctor->registration_no }}
            @endif
        </div>
    </div>
</div>

@if(setting('print_footer_enabled','1') !== '0')
<div class="footer-note">
    Computer-generated discharge summary &nbsp;·&nbsp; {{ setting('clinic_name', 'Hospital') }} &nbsp;·&nbsp; Generated {{ now()->format('d M Y, h:i A') }}
</div>
@endif

</body>
</html>
