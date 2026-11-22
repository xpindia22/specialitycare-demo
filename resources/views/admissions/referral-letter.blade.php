{{-- FILE: resources/views/admissions/referral-letter.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Referral Letter — {{ $admission->ipd_no }}</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;600;700&family=DM+Mono&display=swap" rel="stylesheet">
    <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    html { font-size: 14px; }
    body {
        font-family: 'DM Sans', Arial, sans-serif;
        color: #1a2942;
        background: #fff;
        padding: 0;
        margin: 0;
    }

    .print-actions {
        background: #0f1f3d;
        padding: 10px 24px;
        display: flex;
        gap: 10px;
        align-items: center;
    }
    .btn-print {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 18px;
        background: #0ab4b4;
        color: #fff;
        border: none;
        border-radius: 6px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
    }
    .btn-back {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 18px;
        background: transparent;
        color: rgba(255,255,255,.7);
        border: 1px solid rgba(255,255,255,.3);
        border-radius: 6px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
    }
    /* ── EDITABLE FIELDS ── */
    [contenteditable] {
        outline: none;
        border-radius: 3px;
        transition: background .15s;
    }
    [contenteditable]:hover {
        background: #f0f9ff;
    }
    [contenteditable]:focus {
        background: #e0f2fe;
        box-shadow: 0 0 0 2px #0ab4b4;
    }
    [contenteditable][data-placeholder]:empty::before {
        content: attr(data-placeholder);
        color: #94a3b8;
        font-style: italic;
        pointer-events: none;
    }
    .edit-hint {
        font-size: 11px;
        color: #0ab4b4;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    @media print {
        .print-actions { display: none !important; }
        .edit-hint { display: none !important; }
        [contenteditable] { background: none !important; box-shadow: none !important; }
        @page { margin: 14mm 18mm; size: A4; }
        body { font-size: 13px; }
    }

    /* ── DOCUMENT ── */
    .document {
        max-width: 740px;
        margin: 0 auto;
        padding: 28px 34px 48px;
    }

    /* ── LETTERHEAD ── */
    .letterhead {
        border-bottom: 3px solid #0f1f3d;
        padding-bottom: 16px;
        margin-bottom: 6px;
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 20px;
    }
    .lh-logo {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .lh-logo-icon {
        width: 54px; height: 54px;
        border-radius: 12px;
        background: linear-gradient(135deg, #0ab4b4, #0f1f3d);
        display: flex; align-items: center; justify-content: center;
        font-size: 26px; color: #fff;
    }
    .lh-name {
        font-size: 1.5rem;
        font-weight: 800;
        color: #0f1f3d;
        line-height: 1.1;
    }
    .lh-tagline {
        font-size: 0.78rem;
        color: #0ab4b4;
        font-weight: 700;
        letter-spacing: 1.5px;
        text-transform: uppercase;
        margin-top: 2px;
    }
    .lh-contact {
        text-align: right;
        font-size: 11.5px;
        color: #64748b;
        line-height: 1.8;
    }
    .accent-bar {
        height: 4px;
        background: linear-gradient(to right, #0ab4b4, #0f1f3d);
        border-radius: 2px;
        margin-bottom: 28px;
    }

    /* ── LETTER META ── */
    .letter-meta {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 22px;
        gap: 20px;
    }
    .meta-date {
        font-size: 13px;
        color: #1a2942;
    }
    .meta-ref {
        font-size: 11.5px;
        color: #64748b;
        font-family: 'DM Mono', monospace;
        text-align: right;
    }

    /* ── TO / FROM / RE ── */
    .address-block {
        margin-bottom: 22px;
    }
    .addr-row {
        display: flex;
        gap: 0;
        margin-bottom: 8px;
        align-items: flex-start;
    }
    .addr-label {
        font-size: 12px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        color: #64748b;
        min-width: 56px;
        padding-top: 2px;
    }
    .addr-value {
        flex: 1;
        font-size: 13.5px;
        font-weight: 600;
        color: #1a2942;
        border-bottom: 1px solid #cbd5e1;
        min-height: 26px;
        padding-bottom: 2px;
    }
    .addr-value.blank {
        color: transparent;
    }

    /* ── SUBJECT LINE ── */
    .subject-line {
        background: #f0f4f8;
        border-left: 4px solid #0ab4b4;
        padding: 10px 14px;
        margin-bottom: 22px;
        border-radius: 0 6px 6px 0;
    }
    .subject-line .sl-label {
        font-size: 10.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: #64748b;
        margin-bottom: 3px;
    }
    .subject-line .sl-value {
        font-size: 14px;
        font-weight: 700;
        color: #0f1f3d;
    }

    /* ── BODY TEXT ── */
    .letter-body {
        font-size: 13.5px;
        line-height: 1.85;
        color: #1a2942;
        margin-bottom: 22px;
    }
    .letter-body p {
        margin-bottom: 12px;
    }

    /* ── CLINICAL DETAILS BOX ── */
    .clinical-box {
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        overflow: hidden;
        margin-bottom: 22px;
    }
    .clinical-box-header {
        background: #0f1f3d;
        color: #fff;
        padding: 8px 14px;
        font-size: 11.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    .clinical-box-body {
        padding: 14px 16px;
    }
    .cd-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px 24px;
    }
    .cd-item { }
    .cd-label {
        font-size: 10.5px;
        text-transform: uppercase;
        letter-spacing: 0.7px;
        color: #64748b;
        font-weight: 700;
        margin-bottom: 2px;
    }
    .cd-value {
        font-size: 13px;
        font-weight: 600;
        color: #1a2942;
    }
    .cd-value.blank {
        border-bottom: 1px solid #cbd5e1;
        min-height: 22px;
        color: transparent;
    }

    /* ── NOTES AREA ── */
    .notes-area {
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 12px 14px;
        min-height: 80px;
        font-size: 13px;
        color: #1a2942;
        line-height: 1.7;
        margin-bottom: 22px;
        white-space: pre-wrap;
    }
    .notes-area.empty {
        min-height: 60px;
        color: transparent;
    }

    /* ── SIGNATURE ── */
    .signature-block {
        display: flex;
        justify-content: flex-end;
        margin-top: 32px;
    }
    .sig-box {
        width: 280px;
        text-align: center;
    }
    .sig-space { height: 52px; }
    .sig-line {
        border-top: 1px solid #0f1f3d;
        margin-bottom: 6px;
    }
    .sig-name {
        font-size: 13.5px;
        font-weight: 800;
        color: #0f1f3d;
    }
    .sig-title {
        font-size: 11px;
        color: #64748b;
        margin-top: 2px;
    }

    /* ── FOOTER ── */
    .letter-footer {
        margin-top: 32px;
        border-top: 1px solid #e2e8f0;
        padding-top: 10px;
        font-size: 10.5px;
        color: #94a3b8;
        text-align: center;
        line-height: 1.6;
    }
    </style>
</head>
<body>

<!-- Print actions bar -->
<div class="print-actions">
    <button class="btn-print" onclick="window.print()">&#128424; Print Letter</button>
    <a href="{{ route('admissions.show', $admission) }}" class="btn-back">&#8592; Back to Admission</a>
    <span style="color:rgba(255,255,255,.5);font-size:12px;margin-left:8px">&#9998; Click any field to edit before printing</span>
</div>

<div class="document">

    <!-- Letterhead -->
    @if(setting('print_header_enabled','1') !== '0')
    <div class="letterhead">
        <div class="lh-logo">
            <div class="lh-logo-icon">&#10084;</div>
            <div>
                <div class="lh-name">James Heart Care Pvt. Ltd.</div>
                <div style="font-size:11px;color:#64748b;margin-top:4px">{{ setting('clinic_address', '') }}</div>
            </div>
        </div>
        <div class="lh-contact">
            @if(setting('clinic_phone'))
            <div>Tel: {{ setting('clinic_phone') }}</div>
            @endif
            @if(setting('clinic_email'))
            <div>{{ setting('clinic_email') }}</div>
            @endif
        </div>
    </div>
    <div class="accent-bar"></div>
    @endif

    <!-- Letter Meta -->
    <div class="letter-meta">
        <div class="meta-date">Date: <strong>{{ now()->format('d F Y') }}</strong></div>
        <div class="meta-ref">
            Ref: {{ $admission->ipd_no }}<br>
            Admitted: {{ $admission->admission_date->format('d M Y') }}
        </div>
    </div>

    <!-- To / From / Re -->
    <div class="address-block">
        <div class="addr-row">
            <div class="addr-label">To</div>
            <div class="addr-value" contenteditable="true" data-placeholder="Doctor / Hospital name"></div>
        </div>
        <div class="addr-row">
            <div class="addr-label"></div>
            <div class="addr-value" contenteditable="true" data-placeholder="Address / Department / City"></div>
        </div>
        <div class="addr-row" style="margin-top:10px">
            <div class="addr-label">From</div>
            <div class="addr-value" contenteditable="true">{{ $admission->admittingDoctor->full_name }}@if($admission->admittingDoctor->specialization), {{ $admission->admittingDoctor->specialization }}@endif, James Heart Care Pvt. Ltd.</div>
        </div>
    </div>

    <!-- Subject Line -->
    <div class="subject-line">
        <div class="sl-label">Subject / Re</div>
        <div class="sl-value">
            Patient: {{ $admission->patient->full_name }},
            {{ $admission->patient->age }},
            {{ ucfirst($admission->patient->gender) }}
            @if($admission->patient->blood_group) &nbsp;&bull;&nbsp; Blood Group: {{ $admission->patient->blood_group }}@endif
        </div>
    </div>

    <!-- Letter Body -->
    <div class="letter-body" contenteditable="true">
        <p>Dear Doctor,</p>

        <p>
            The above named patient, <strong>{{ $admission->patient->full_name }}</strong>
            ({{ $admission->patient->age }}, {{ ucfirst($admission->patient->gender) }}),
            is under our care at James Heart Care Pvt. Ltd.
            with a diagnosis of <strong>{{ $admission->provisional_diagnosis ?: '[Diagnosis not recorded]' }}</strong>.
        </p>

        <p>
            We are referring this patient for further evaluation and management. Please find the clinical details
            below and kindly provide your expert opinion / management.
        </p>
    </div>

    <!-- Clinical Details -->
    <div class="clinical-box">
        <div class="clinical-box-header">Clinical Details</div>
        <div class="clinical-box-body">
            <div class="cd-grid">
                <div class="cd-item">
                    <div class="cd-label">Patient Name</div>
                    <div class="cd-value">{{ $admission->patient->full_name }}</div>
                </div>
                <div class="cd-item">
                    <div class="cd-label">JHC ID</div>
                    <div class="cd-value" style="font-family:'DM Mono',monospace">{{ $admission->patient->jhc_id }}</div>
                </div>
                <div class="cd-item">
                    <div class="cd-label">Age / Gender</div>
                    <div class="cd-value">{{ $admission->patient->age }} / {{ ucfirst($admission->patient->gender) }}</div>
                </div>
                <div class="cd-item">
                    <div class="cd-label">Blood Group</div>
                    <div class="cd-value">{{ $admission->patient->blood_group ?: '—' }}</div>
                </div>
                <div class="cd-item">
                    <div class="cd-label">Admission Date</div>
                    <div class="cd-value">{{ $admission->admission_date->format('d M Y') }}</div>
                </div>
                <div class="cd-item">
                    <div class="cd-label">Admission Type</div>
                    <div class="cd-value">{{ ucfirst($admission->admission_type) }}</div>
                </div>
                <div class="cd-item" style="grid-column:span 2">
                    <div class="cd-label">Provisional Diagnosis</div>
                    <div class="cd-value" contenteditable="true" data-placeholder="Enter diagnosis…">{{ $admission->provisional_diagnosis ?: '' }}</div>
                </div>
                <div class="cd-item" style="grid-column:span 2">
                    <div class="cd-label">Clinical Notes</div>
                    <div class="cd-value" contenteditable="true" style="white-space:pre-wrap" data-placeholder="Add clinical notes…">{{ $admission->notes ?? '' }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Additional Notes area -->
    <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;color:#64748b;margin-bottom:6px">
        Reason for Referral / Additional Notes
    </div>
    <div class="notes-area" contenteditable="true" data-placeholder="Type the reason for referral, specific requests, or additional clinical notes here…"></div>

    <!-- Closing -->
    <div class="letter-body" contenteditable="true">
        <p>
            We would be grateful for your kind attention to this patient. Please do not hesitate to contact us
            if you require any additional information.
        </p>
        <p>Thanking you.</p>
    </div>

    <!-- Signature -->
    <div class="signature-block">
        <div class="sig-box">
            <div class="sig-space"></div>
            <div class="sig-line"></div>
            <div class="sig-name">{{ $admission->admittingDoctor->full_name }}</div>
            <div class="sig-title">
                @if($admission->admittingDoctor->specialization){{ $admission->admittingDoctor->specialization }}, @endif
                James Heart Care Pvt. Ltd.
            </div>
        </div>
    </div>

    <!-- Footer -->
    @if(setting('print_footer_enabled','1') !== '0')
    <div class="letter-footer">
        James Heart Care Pvt. Ltd. &nbsp;&bull;&nbsp; {{ setting('clinic_address', '') }}
        @if(setting('clinic_phone')) &nbsp;&bull;&nbsp; Tel: {{ setting('clinic_phone') }} @endif
    </div>
    @endif

</div>
</body>
</html>
