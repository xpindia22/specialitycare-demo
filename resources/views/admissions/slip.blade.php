{{-- FILE: resources/views/admissions/slip.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admission Slip — {{ $admission->ipd_no }}</title>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Times New Roman', serif; font-size: 11pt; color: #111; background: #fff; }
    @page { margin: 15mm 18mm 15mm 18mm; size: A5 portrait; }
    @media print {
        .no-print { display: none !important; }
        body { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
    }
    .no-print { margin: 10px; display: flex; gap: 8px; }
    button, a.btn { font-family: Arial, sans-serif; font-size: 12px; padding: 6px 14px; border: 1px solid #333; background: #fff; border-radius: 4px; cursor: pointer; text-decoration: none; color: #111; }
    button.primary { background: #0f1f3d; color: #fff; border-color: #0f1f3d; }

    .letterhead { border-bottom: 2.5px double #0f1f3d; padding-bottom: 8px; margin-bottom: 10px; display: flex; justify-content: space-between; align-items: flex-end; }
    .clinic-name { font-size: 16pt; font-weight: 700; color: #0f1f3d; }
    .clinic-sub  { font-size: 8.5pt; color: #555; margin-top: 2px; }
    .doc-title   { text-align: center; font-size: 13pt; font-weight: 700; text-decoration: underline; margin: 8px 0 10px; letter-spacing: .4px; }

    .ipd-badge { border: 2px solid #0f1f3d; padding: 6px 14px; text-align: center; }
    .ipd-badge .label { font-size: 7.5pt; color: #555; letter-spacing: .5px; text-transform: uppercase; }
    .ipd-badge .value { font-size: 15pt; font-weight: 700; color: #0f1f3d; font-family: monospace; }

    table.info { width: 100%; border-collapse: collapse; font-size: 10.5pt; }
    table.info td { padding: 3.5px 8px; vertical-align: top; }
    table.info td:first-child { font-weight: 600; width: 36%; }
    table.info tr:nth-child(odd) td { background: #f5f5f5; }

    .section-title { font-size: 9.5pt; font-weight: 700; text-transform: uppercase; letter-spacing: .6px;
        border-left: 3px solid #0f1f3d; padding-left: 6px; margin: 12px 0 6px; color: #0f1f3d; }

    .box { border: 1px solid #ccc; padding: 8px 10px; border-radius: 3px; font-size: 10pt; line-height: 1.5; }
    .grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 0; }
    .grid2 td { border: 1px solid #ccc; padding: 5px 8px; }
    .grid2 td:first-child { font-weight: 600; background: #f5f5f5; }

    .rules { font-size: 9.5pt; line-height: 1.6; color: #333; }
    .rules li { margin-bottom: 3px; }

    .sig-area { margin-top: 20px; display: flex; justify-content: space-between; }
    .sig-line { text-align: center; width: 130px; }
    .sig-line .line { border-top: 1px solid #555; padding-top: 4px; font-size: 9pt; }

    .footer-note { margin-top: 10px; font-size: 8.5pt; color: #777; text-align: center; border-top: 1px solid #ddd; padding-top: 6px; }
    .badge { font-size: 8.5pt; padding: 2px 8px; border-radius: 20px; font-weight: 600; border: 1px solid; }
    .badge-emergency { border-color: #c0392b; color: #c0392b; background: #fdf2f2; }
    .badge-elective   { border-color: #2c7a5e; color: #2c7a5e; background: #eef7f4; }
    .badge-referral   { border-color: #2560a8; color: #2560a8; background: #eef3fb; }
</style>
</head>
<body>

<div class="no-print">
    <button class="primary" onclick="window.print()">&#128438; Print Slip</button>
    <a class="btn" href="{{ route('admissions.show', $admission) }}">&#8592; Back</a>
</div>

@if(setting('print_header_enabled','1') !== '0')
<div class="letterhead">
    <div>
        <div class="clinic-name">{{ setting('clinic_name', 'Hospital') }}</div>
        @if(setting('clinic_address'))
        <div class="clinic-sub">{{ setting('clinic_address') }}</div>
        @endif
        @if(setting('clinic_phone'))
        <div class="clinic-sub">Ph: {{ setting('clinic_phone') }}</div>
        @endif
    </div>
    <div class="ipd-badge">
        <div class="label">IPD Number</div>
        <div class="value">{{ $admission->ipd_no }}</div>
        <div class="label" style="margin-top:2px">{{ $admission->admission_date->format('d M Y') }}</div>
    </div>
</div>
@endif

<div class="doc-title">ADMISSION SLIP</div>

<div class="section-title">Patient Information</div>
<table class="info" style="margin-bottom:10px">
    <tr><td>Full Name</td><td><strong>{{ $admission->patient->full_name }}</strong></td></tr>
    <tr><td>Registration No.</td><td class="mono">{{ $admission->patient->jhc_id }}</td></tr>
    <tr><td>Age / Gender</td><td>{{ $admission->patient->age }} / {{ ucfirst($admission->patient->gender) }}</td></tr>
    @if($admission->patient->blood_group)
    <tr><td>Blood Group</td><td>{{ $admission->patient->blood_group }}</td></tr>
    @endif
    <tr><td>Contact</td><td>{{ $admission->patient->phone }}</td></tr>
    @if($admission->patient->address)
    <tr><td>Address</td><td>{{ $admission->patient->address }}</td></tr>
    @endif
    @if($admission->patient->emergency_contact_name)
    <tr><td>Emergency Contact</td><td>{{ $admission->patient->emergency_contact_name }} — {{ $admission->patient->emergency_contact_phone }}</td></tr>
    @endif
</table>

<div class="section-title">Admission Details</div>
<table class="info" style="margin-bottom:10px">
    <tr>
        <td>IPD Number</td>
        <td><strong class="mono">{{ $admission->ipd_no }}</strong></td>
    </tr>
    <tr>
        <td>Date &amp; Time</td>
        <td>{{ $admission->admission_date->format('d M Y, h:i A') }}</td>
    </tr>
    <tr>
        <td>Admission Type</td>
        <td>
            <span class="badge badge-{{ $admission->admission_type }}">
                {{ \App\Models\Admission::ADMISSION_TYPES[$admission->admission_type] ?? $admission->admission_type }}
            </span>
        </td>
    </tr>
    <tr>
        <td>Ward</td>
        <td>{{ $admission->ward->name }}
            @if($admission->ward->type_label)
            <span style="color:#777;font-size:9.5pt"> ({{ $admission->ward->type_label }})</span>
            @endif
        </td>
    </tr>
    <tr>
        <td>Bed</td>
        <td>Bed No. <strong>{{ $admission->bed->bed_number }}</strong>
            <span style="color:#777;font-size:9.5pt"> — {{ ucfirst($admission->bed->type) }}</span>
        </td>
    </tr>
    <tr>
        <td>Admitting Doctor</td>
        <td>{{ $admission->admittingDoctor->full_name }}</td>
    </tr>
    @if($admission->referred_by)
    <tr><td>Referred By</td><td>{{ $admission->referred_by }}</td></tr>
    @endif
    @if($admission->provisional_diagnosis)
    <tr><td>Provisional Diagnosis</td><td>{{ $admission->provisional_diagnosis }}</td></tr>
    @endif
</table>

<div class="section-title">General Instructions</div>
<div class="box">
    <ul class="rules" style="padding-left:16px">
        <li>Please report to the nursing station on arrival at the ward.</li>
        <li>Visiting hours: 8:00 AM – 11:00 AM and 4:00 PM – 7:00 PM only.</li>
        <li>Do not leave the ward without informing the nursing staff.</li>
        <li>All investigations and procedures require prior consent.</li>
        <li>Valuables should not be kept in the room. Hospital is not responsible for lost articles.</li>
        <li>Maintain cleanliness and silence. Smoking is strictly prohibited.</li>
        <li>For queries regarding bill, contact the billing department.</li>
    </ul>
</div>

<div class="sig-area">
    <div class="sig-line">
        <div class="line">Patient / Attendant</div>
    </div>
    <div class="sig-line">
        <div class="line">Nursing In-charge</div>
    </div>
    <div class="sig-line">
        <div class="line">Admitting Doctor</div>
    </div>
</div>

@if(setting('print_footer_enabled','1') !== '0')
<div class="footer-note">
    Printed {{ now()->format('d M Y, h:i A') }}
    @if($admission->createdBy)
    &nbsp;&bull;&nbsp; Admitted by {{ $admission->createdBy->name }}
    @endif
    &nbsp;&bull;&nbsp; {{ setting('clinic_name', 'Hospital') }}
</div>
@endif

</body>
</html>
