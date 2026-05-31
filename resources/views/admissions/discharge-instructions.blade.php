{{-- FILE: resources/views/admissions/discharge-instructions.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Discharge Instructions — {{ $admission->ipd_no }}</title>
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

    /* Print button — hidden on print */
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
    @media print {
        .print-actions { display: none !important; }
        @page { margin: 14mm 16mm; size: A4; }
        body { font-size: 13px; }
        .page-break { page-break-before: always; }
    }

    /* ── DOCUMENT ── */
    .document {
        max-width: 760px;
        margin: 0 auto;
        padding: 28px 32px 40px;
    }

    /* ── HOSPITAL HEADER ── */
    .hosp-header {
        border-bottom: 3px solid #0ab4b4;
        padding-bottom: 14px;
        margin-bottom: 18px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
    }
    .hosp-logo-area {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .hosp-logo-icon {
        width: 52px; height: 52px;
        border-radius: 12px;
        background: linear-gradient(135deg, #0ab4b4, #0891b2);
        display: flex; align-items: center; justify-content: center;
        font-size: 24px; color: #fff;
        flex-shrink: 0;
    }
    .hosp-name {
        font-size: 1.4rem;
        font-weight: 800;
        color: #0f1f3d;
        line-height: 1.2;
    }
    .hosp-sub {
        font-size: 0.78rem;
        color: #64748b;
        margin-top: 2px;
        letter-spacing: 0.3px;
    }
    .hosp-contact {
        text-align: right;
        font-size: 11px;
        color: #64748b;
        line-height: 1.7;
    }

    /* ── DOCUMENT TITLE ── */
    .doc-title {
        text-align: center;
        margin-bottom: 18px;
    }
    .doc-title h1 {
        font-size: 1.3rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 2px;
        color: #0f1f3d;
    }
    .doc-title .doc-subtitle {
        font-size: 0.82rem;
        color: #64748b;
        margin-top: 3px;
    }

    /* ── PATIENT INFO BOX ── */
    .patient-info-box {
        background: #f0f4f8;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 14px 18px;
        margin-bottom: 20px;
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 10px 20px;
    }
    .pi-item { }
    .pi-label {
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        color: #64748b;
        font-weight: 700;
        margin-bottom: 2px;
    }
    .pi-value {
        font-size: 13px;
        font-weight: 600;
        color: #1a2942;
    }

    /* ── SECTIONS ── */
    .section {
        margin-bottom: 20px;
    }
    .section-header {
        display: flex;
        align-items: center;
        gap: 8px;
        background: #0f1f3d;
        color: #fff;
        padding: 7px 14px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 10px;
    }
    .section-header.teal { background: #0ab4b4; }
    .section-header.amber { background: #f5a623; color: #0f1f3d; }
    .section-header.red { background: #e84c4c; }
    .section-body {
        padding: 4px 4px 4px 8px;
        font-size: 13px;
        line-height: 1.7;
        color: #1a2942;
    }
    .section-body p {
        margin-bottom: 6px;
    }
    .section-body.empty {
        color: #94a3b8;
        font-style: italic;
    }

    /* ── MEDICATION TABLE ── */
    .med-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 12.5px;
        margin-top: 4px;
    }
    .med-table th {
        background: #f0f4f8;
        padding: 7px 10px;
        text-align: left;
        font-size: 10.5px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #64748b;
        font-weight: 700;
        border-bottom: 2px solid #e2e8f0;
    }
    .med-table td {
        padding: 8px 10px;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: top;
    }
    .med-table tr:last-child td { border-bottom: none; }
    .med-name { font-weight: 700; color: #0f1f3d; }
    .med-generic { font-size: 11px; color: #64748b; }

    /* ── WARNING BOX ── */
    .warning-box {
        background: #fff7ed;
        border: 2px solid #fb923c;
        border-radius: 10px;
        padding: 14px 18px;
        margin-bottom: 20px;
    }
    .warning-box .wh {
        font-weight: 800;
        color: #c2410c;
        font-size: 13px;
        display: flex;
        align-items: center;
        gap: 7px;
        margin-bottom: 8px;
    }
    .warning-list {
        list-style: none;
        padding: 0;
    }
    .warning-list li {
        padding: 4px 0 4px 20px;
        position: relative;
        font-size: 13px;
        color: #7c2d12;
    }
    .warning-list li::before {
        content: '!';
        position: absolute;
        left: 0;
        font-weight: 800;
        color: #ef4444;
    }

    /* ── SIGNATURE BLOCK ── */
    .signature-block {
        margin-top: 32px;
        border-top: 1px dashed #cbd5e1;
        padding-top: 20px;
        display: flex;
        justify-content: space-between;
        gap: 20px;
    }
    .sig-item {
        flex: 1;
        text-align: center;
    }
    .sig-line {
        border-top: 1px solid #1a2942;
        margin-bottom: 6px;
        margin-top: 40px;
    }
    .sig-label {
        font-size: 11px;
        color: #64748b;
    }
    .sig-name {
        font-size: 12px;
        font-weight: 700;
        color: #1a2942;
    }

    /* ── FOOTER ── */
    .doc-footer {
        margin-top: 24px;
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

<!-- Print actions bar (hidden on print) -->
<div class="print-actions">
    <button class="btn-print" onclick="window.print()">&#128424; Print Instructions</button>
    <a href="{{ route('admissions.show', $admission) }}" class="btn-back">&#8592; Back to Admission</a>
</div>

<div class="document">

    <!-- Hospital Header -->
    @if(setting('print_header_enabled','1') !== '0')
    <div class="hosp-header">
        <div class="hosp-logo-area">
            <div class="hosp-logo-icon">&#10084;</div>
            <div>
                <div class="hosp-name">Demo Clinic Pvt. Ltd.</div>
                <div class="hosp-sub">{{ setting('clinic_address', 'Demo Clinic Pvt. Ltd.') }}</div>
            </div>
        </div>
        <div class="hosp-contact">
            @if(setting('clinic_phone'))
            <div>Tel: {{ setting('clinic_phone') }}</div>
            @endif
            @if(setting('clinic_email'))
            <div>{{ setting('clinic_email') }}</div>
            @endif
            <div>IPD No: <strong>{{ $admission->ipd_no }}</strong></div>
        </div>
    </div>
    @endif

    <!-- Document Title -->
    <div class="doc-title">
        <h1>Discharge Instructions</h1>
        <div class="doc-subtitle">
            Prepared on {{ $admission->discharge_date ? $admission->discharge_date->format('d M Y') : now()->format('d M Y') }}
        </div>
    </div>

    <!-- Patient Info -->
    <div class="patient-info-box">
        <div class="pi-item">
            <div class="pi-label">Patient Name</div>
            <div class="pi-value">{{ $admission->patient->full_name }}</div>
        </div>
        <div class="pi-item">
            <div class="pi-label">Age / Gender</div>
            <div class="pi-value">{{ $admission->patient->age }} / {{ ucfirst($admission->patient->gender) }}</div>
        </div>
        <div class="pi-item">
            <div class="pi-label">IPD No.</div>
            <div class="pi-value" style="font-family:'DM Mono',monospace">{{ $admission->ipd_no }}</div>
        </div>
        <div class="pi-item">
            <div class="pi-label">Admission Date</div>
            <div class="pi-value">{{ $admission->admission_date->format('d M Y') }}</div>
        </div>
        <div class="pi-item">
            <div class="pi-label">Discharge Date</div>
            <div class="pi-value">{{ $admission->discharge_date ? $admission->discharge_date->format('d M Y') : '—' }}</div>
        </div>
        <div class="pi-item">
            <div class="pi-label">Treating Doctor</div>
            <div class="pi-value">{{ $admission->admittingDoctor->full_name }}</div>
        </div>
    </div>

    <!-- Diagnosis -->
    <div class="section">
        <div class="section-header"><span>&#9684;</span> Diagnosis</div>
        <div class="section-body {{ !$admission->provisional_diagnosis ? 'empty' : '' }}">
            {{ $admission->provisional_diagnosis ?: 'Not recorded.' }}
        </div>
    </div>

    <!-- Medications -->
    <div class="section">
        <div class="section-header teal"><span>&#128138;</span> Medications at Discharge</div>
        @if($admission->prescriptions->isEmpty())
            <div class="section-body empty">No active medications recorded.</div>
        @else
        <table class="med-table">
            <thead>
                <tr>
                    <th>Medication</th>
                    <th>Dose</th>
                    <th>Frequency</th>
                    <th>Duration</th>
                    <th>Route</th>
                    <th>Instructions</th>
                </tr>
            </thead>
            <tbody>
            @foreach($admission->prescriptions as $rx)
            <tr>
                <td>
                    <div class="med-name">{{ $rx->medicine_name }}</div>
                    @if($rx->generic_name ?? null)
                    <div class="med-generic">({{ $rx->generic_name }})</div>
                    @endif
                </td>
                <td>{{ $rx->dose ?? '—' }}</td>
                <td>{{ $rx->frequency ?? '—' }}</td>
                <td>{{ $rx->duration ?? '—' }}</td>
                <td>{{ $rx->route ?? '—' }}</td>
                <td style="font-size:11.5px">{{ $rx->instructions ?? '' }}</td>
            </tr>
            @endforeach
            </tbody>
        </table>
        @endif
    </div>

    <!-- Discharge Instructions (free-text field) -->
    @if($admission->discharge_summary)
    <div class="section">
        <div class="section-header"><span>&#128203;</span> Additional Instructions</div>
        <div class="section-body" style="white-space:pre-wrap">{{ $admission->discharge_summary }}</div>
    </div>
    @endif

    <!-- Diet Instructions -->
    <div class="section">
        <div class="section-header amber"><span>&#127825;</span> Diet Instructions</div>
        <div class="section-body empty">As advised by your doctor. Maintain a balanced, nutritious diet. Avoid spicy, oily, or processed foods unless otherwise specified.</div>
    </div>

    <!-- Activity Restrictions -->
    <div class="section">
        <div class="section-header"><span>&#127939;</span> Activity &amp; Restrictions</div>
        <div class="section-body empty">Follow your doctor's advice regarding physical activity. Rest adequately and gradually resume normal activities.</div>
    </div>

    <!-- Follow-up -->
    <div class="section">
        <div class="section-header teal"><span>&#128197;</span> Follow-up Appointment</div>
        <div class="section-body">
            <p>Please visit <strong>Demo Clinic Pvt. Ltd.</strong> for a follow-up appointment with <strong>{{ $admission->admittingDoctor->full_name }}</strong>.</p>
            <p style="margin-top:8px">Follow-up Date: <span style="border-bottom:1px solid #1a2942;min-width:140px;display:inline-block">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span></p>
        </div>
    </div>

    <!-- Warning Signs -->
    <div class="warning-box">
        <div class="wh">&#9888; When to Return Immediately</div>
        <ul class="warning-list">
            <li>High fever (temperature above 38.5&deg;C / 101.3&deg;F)</li>
            <li>Severe chest pain, shortness of breath, or difficulty breathing</li>
            <li>Sudden severe headache, vision changes, or confusion</li>
            <li>Excessive bleeding or wound discharge</li>
            <li>Signs of infection: redness, swelling, warmth, or pus at wound site</li>
            <li>Inability to tolerate food or fluids, severe vomiting or diarrhoea</li>
            <li>Any other symptoms that concern you or worsen suddenly</li>
        </ul>
        <div style="margin-top:10px;font-size:12px;color:#92400e;font-weight:700">
            Emergency: Contact your nearest emergency room immediately or call your doctor.
        </div>
    </div>

    <!-- Signature Block -->
    <div class="signature-block">
        <div class="sig-item">
            <div class="sig-line"></div>
            <div class="sig-name">{{ $admission->admittingDoctor->full_name }}</div>
            <div class="sig-label">Treating Doctor / Signature</div>
        </div>
        <div class="sig-item">
            <div class="sig-line"></div>
            <div class="sig-name">{{ $admission->patient->full_name }}</div>
            <div class="sig-label">Patient / Guardian Signature</div>
        </div>
        <div class="sig-item">
            <div class="sig-line"></div>
            <div class="sig-name">&nbsp;</div>
            <div class="sig-label">Nurse / Discharge Staff</div>
        </div>
    </div>

    <!-- Footer -->
    @if(setting('print_footer_enabled','1') !== '0')
    <div class="doc-footer">
        Demo Clinic Pvt. Ltd. &nbsp;·&nbsp; {{ setting('clinic_address', '') }}<br>
        These instructions are provided at the time of discharge. Please keep this document safe and bring it to every follow-up visit.
    </div>
    @endif

</div>
</body>
</html>
