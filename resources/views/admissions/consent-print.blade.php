<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Consent Form – {{ $typeLabel }} – {{ $admission->ipd_no }}</title>
<style>
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:'Times New Roman',serif;font-size:13px;color:#000;background:#fff;padding:20px}
    .page{max-width:780px;margin:0 auto;border:2px solid #000;padding:28px 36px}
    /* letterhead */
    .lh{display:flex;align-items:center;border-bottom:3px double #000;padding-bottom:12px;margin-bottom:16px}
    .lh-logo{font-size:28px;font-weight:900;letter-spacing:1px;color:#1a5276;margin-right:16px;white-space:nowrap}
    .lh-info{flex:1}
    .lh-info h1{font-size:16px;font-weight:700;margin-bottom:2px}
    .lh-info p{font-size:11px;color:#333}
    .lh-right{text-align:right}
    .lh-right .form-title{font-size:17px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;border:2px solid #000;padding:6px 14px;display:inline-block}
    /* patient strip */
    .pt-strip{display:grid;grid-template-columns:repeat(4,1fr);gap:4px 16px;border:1px solid #666;padding:10px 14px;margin-bottom:18px;background:#f9f9f9}
    .pt-field label{font-size:10px;font-weight:700;text-transform:uppercase;color:#555;display:block}
    .pt-field span{font-size:12px;font-weight:600;border-bottom:1px solid #999;display:block;padding-bottom:2px;min-height:18px}
    /* body */
    .consent-title{text-align:center;font-size:15px;font-weight:700;text-decoration:underline;margin-bottom:14px}
    .para{margin-bottom:10px;line-height:1.7;text-align:justify}
    .para strong{font-weight:700}
    .fillable{display:inline-block;border-bottom:1px solid #000;min-width:180px;vertical-align:bottom;padding:0 3px}
    .procedures-block{border:1px dashed #888;padding:8px 12px;margin:10px 0;min-height:50px}
    .procedures-block .label{font-size:10px;font-weight:700;text-transform:uppercase;color:#555;margin-bottom:4px}
    .checkbox-row{display:flex;align-items:center;gap:8px;margin:8px 0}
    .checkbox-row input[type=checkbox]{width:15px;height:15px;flex-shrink:0}
    /* signature grid */
    .sig-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:0 24px;margin-top:24px;border-top:1px solid #000;padding-top:16px}
    .sig-box{text-align:center}
    .sig-box .sig-line{border-bottom:1px solid #000;height:52px;margin-bottom:6px}
    .sig-box .sig-label{font-size:11px;font-weight:700}
    .sig-box .sig-sub{font-size:10px;color:#444}
    /* footer */
    .footer{margin-top:18px;border-top:1px solid #ccc;padding-top:8px;font-size:10px;color:#555;text-align:center}
    /* print controls */
    .no-print{text-align:center;margin-bottom:18px}
    .no-print button{padding:8px 24px;margin:0 6px;font-size:13px;cursor:pointer;border:1px solid #333;border-radius:4px;background:#1a5276;color:#fff}
    .no-print button.sec{background:#fff;color:#333}
    @media print{
        .no-print{display:none}
        body{padding:0}
        .page{border:none;padding:20px 28px}
    }
</style>
</head>
<body>

<div class="no-print">
    <button onclick="window.print()">&#128438; Print / Save PDF</button>
    <button class="sec" onclick="history.back()">&#8592; Back</button>
</div>

<div class="page">

    {{-- Letterhead --}}
    @if(setting('print_header_enabled','1') !== '0')
    <div class="lh">
        <div class="lh-logo">JHC</div>
        <div class="lh-info">
            <h1>Demo Clinic Pvt. Ltd.</h1>
            <p>Multi-Specialty Hospital &amp; Research Centre</p>
            <p>Contact: info@demo.example.com | Emergency: 108</p>
        </div>
        <div class="lh-right">
            <div class="form-title">{{ $typeLabel }}<br>Consent Form</div>
        </div>
    </div>
    @endif

    {{-- Patient strip --}}
    <div class="pt-strip">
        <div class="pt-field">
            <label>Patient Name</label>
            <span>{{ $admission->patient->full_name }}</span>
        </div>
        <div class="pt-field">
            <label>IPD No.</label>
            <span>{{ $admission->ipd_no }}</span>
        </div>
        <div class="pt-field">
            <label>Age / Sex</label>
            <span>{{ $admission->patient->age ?? '—' }} / {{ ucfirst($admission->patient->gender ?? '—') }}</span>
        </div>
        <div class="pt-field">
            <label>Ward / Bed</label>
            <span>{{ $admission->ward->name ?? '—' }} / {{ $admission->bed->bed_number ?? '—' }}</span>
        </div>
        <div class="pt-field">
            <label>Admission Date</label>
            <span>{{ $admission->admission_date->format('d M Y') }}</span>
        </div>
        <div class="pt-field">
            <label>Consultant</label>
            <span>{{ $admission->admittingDoctor->full_name ?? '—' }}</span>
        </div>
        <div class="pt-field">
            <label>Guardian / Relative</label>
            <span>{{ $consent->patient_or_guardian ?? '&nbsp;' }}</span>
        </div>
        <div class="pt-field">
            <label>Relationship</label>
            <span>{{ $consent->relationship ?? '&nbsp;' }}</span>
        </div>
    </div>

    {{-- Consent body --}}
    @if($type === 'admission')
        <div class="consent-title">General Consent for Admission &amp; Treatment</div>
        <div class="para">
            I / We, <span class="fillable">{{ $consent->patient_or_guardian ?? '' }}</span>, being the patient /
            legally authorised representative of the above-named patient, hereby voluntarily consent to
            admission in <strong>Demo Clinic Pvt. Ltd.</strong> and to such examinations, investigations,
            medical or surgical treatment and nursing care as may be deemed necessary by the attending physician
            or surgeon.
        </div>
        <div class="para">
            I understand that the practice of medicine and surgery is not an exact science and I acknowledge
            that no guarantees have been made to me as to the results of the treatment or examination.
        </div>
        <div class="para">
            I consent to the hospital retaining, preserving and using for educational or scientific purposes
            any specimen taken from my body during investigations or procedures, subject to applicable law.
        </div>
        <div class="para">
            I authorise the hospital to release medical information to my insurance company or TPA solely for
            the purpose of processing my insurance claim.
        </div>

    @elseif($type === 'surgical')
        <div class="consent-title">Consent for Surgical Procedure</div>
        @if($consent && $consent->procedure_name)
        <div class="procedures-block">
            <div class="label">Procedure(s) to be performed</div>
            {{ $consent->procedure_name }}
        </div>
        @else
        <div class="procedures-block">
            <div class="label">Procedure(s) to be performed</div>
            &nbsp;
        </div>
        @endif
        <div class="para">
            I / We hereby consent to the performance of the above-mentioned surgical procedure(s) and any
            other procedure(s) that may be found necessary during the course of the operation, by
            <strong>{{ $admission->admittingDoctor->full_name ?? 'the attending surgeon' }}</strong>
            and/or their designated assistants.
        </div>
        <div class="para">
            I acknowledge that the nature of the procedure, expected benefits, material risks (including
            bleeding, infection, anaesthetic complications, and unforeseen events), and reasonable alternatives
            have been explained to me in a language I understand.
        </div>
        <div class="para">
            I consent to the administration of blood or blood products if clinically required during surgery,
            unless I have stated a specific religious or personal objection in writing.
        </div>
        <div class="para">
            I understand that photographs or video recordings may be taken for medical-record or educational
            purposes and will remain strictly confidential.
        </div>

    @elseif($type === 'anaesthesia')
        <div class="consent-title">Consent for Anaesthesia</div>
        <div class="para">
            I / We consent to the administration of anaesthesia — general, regional, or monitored
            anaesthesia care — as selected by the anaesthesiologist to be performed in connection with
            the procedure(s) described in the surgical consent form or as otherwise required.
        </div>
        <div class="para">
            I acknowledge that the type of anaesthesia to be used, its expected effects, and its material
            risks (including rare complications such as nerve injury, awareness, or cardiovascular events)
            have been explained to me.
        </div>
        <div class="para">
            I understand that the anaesthesiologist will exercise professional judgement to modify the
            anaesthetic plan as circumstances require during the procedure.
        </div>

    @elseif($type === 'blood_transfusion')
        <div class="consent-title">Consent for Blood / Blood Product Transfusion</div>
        <div class="para">
            I / We consent to the transfusion of blood or blood products (including packed red cells,
            platelets, fresh frozen plasma, cryoprecipitate, or other derivatives) as deemed clinically
            necessary by the attending physician.
        </div>
        <div class="para">
            I have been informed of the benefits of transfusion and the risks including but not limited to
            allergic reactions, haemolytic reactions, transmission of infections (however rare with current
            screening), and fluid overload.
        </div>
        <div class="para">
            I understand that all blood products used are sourced from licensed blood banks and subjected to
            mandatory screening as required by the Drug &amp; Cosmetic Act.
        </div>
        <div class="checkbox-row">
            <input type="checkbox" {{ ($consent && $consent->is_obtained) ? 'checked' : '' }} disabled>
            <span>I have no religious or personal objection to receiving blood products.</span>
        </div>

    @elseif($type === 'procedure')
        <div class="consent-title">Consent for Invasive / Diagnostic Procedure</div>
        @if($consent && $consent->procedure_name)
        <div class="procedures-block">
            <div class="label">Procedure(s)</div>
            {{ $consent->procedure_name }}
        </div>
        @else
        <div class="procedures-block">
            <div class="label">Procedure(s)</div>
            &nbsp;
        </div>
        @endif
        <div class="para">
            I / We consent to the above-mentioned invasive or diagnostic procedure(s) and any related
            interventions that may become necessary during the procedure.
        </div>
        <div class="para">
            The indications, expected benefits, possible complications (including bleeding, infection,
            organ injury, and failure of the procedure), and alternatives have been explained to me.
        </div>

    @else
        <div class="consent-title">{{ $typeLabel }}</div>
        <div class="para">
            I / We consent to the procedure, treatment, or activity described herein as explained by
            the attending medical officer.
        </div>
    @endif

    {{-- Notes --}}
    @if($consent && $consent->notes)
    <div class="para" style="margin-top:14px;border-left:3px solid #888;padding-left:10px;font-style:italic;color:#333">
        <strong>Notes:</strong> {{ $consent->notes }}
    </div>
    @endif

    <div class="para" style="margin-top:16px">
        I declare that the above information has been explained to me in a language I understand,
        I have had the opportunity to ask questions, and I sign this form of my own free will without
        coercion or undue influence. Date &amp; Time:
        <span class="fillable">{{ $consent && $consent->obtained_at ? $consent->obtained_at->format('d M Y, H:i') : '' }}</span>
    </div>

    {{-- Signature grid --}}
    <div class="sig-grid">
        <div class="sig-box">
            <div class="sig-line"></div>
            <div class="sig-label">Patient / Guardian Signature</div>
            <div class="sig-sub">{{ $consent->patient_or_guardian ?? '___________________________' }}</div>
            <div class="sig-sub">{{ $consent->relationship ? '('.$consent->relationship.')' : '' }}</div>
        </div>
        <div class="sig-box">
            <div class="sig-line"></div>
            <div class="sig-label">Witness Signature</div>
            <div class="sig-sub">{{ $consent->witness_name ?? '___________________________' }}</div>
        </div>
        <div class="sig-box">
            <div class="sig-line"></div>
            <div class="sig-label">Treating Doctor / Surgeon</div>
            <div class="sig-sub">{{ $admission->admittingDoctor->full_name ?? '___________________________' }}</div>
        </div>
    </div>

    @if(setting('print_footer_enabled','1') !== '0')
    <div class="footer">
        Printed: {{ now()->format('d M Y H:i') }} &nbsp;|&nbsp;
        IPD: {{ $admission->ipd_no }} &nbsp;|&nbsp;
        Demo Clinic Pvt. Ltd. — Confidential Medical Record
    </div>
    @endif

</div>
</body>
</html>
