{{-- prescription-print.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Prescription</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        html,body { height:100%; }
        body { font-family:'DM Sans',sans-serif; font-size:13px; color:#1a2942; background:#fff; max-width:800px; margin:0 auto; padding:28px 32px 0; min-height:100vh; display:flex; flex-direction:column; }
        .rx-content { flex:1; }
        .rx-header { display:flex; justify-content:space-between; align-items:flex-start; padding-bottom:14px; margin-bottom:16px; border-bottom:3px solid #1a2942; }
        .rx-clinic-name { font-size:22px; font-weight:800; color:#1a2942; letter-spacing:-0.3px; line-height:1.2; }
        .rx-clinic-detail { font-size:11px; color:#64748b; margin-top:3px; line-height:1.6; }
        .rx-doc-block { text-align:right; }
        .rx-doc-name { font-size:15px; font-weight:700; color:#1a2942; }
        .rx-doc-qual { font-size:11px; color:#64748b; margin-top:2px; line-height:1.6; }
        .rx-meta { background:#fff; border:1px solid #e2e8f0; border-radius:8px; padding:12px 16px; margin-bottom:16px; display:grid; grid-template-columns:1fr 1fr 1fr; gap:12px; }
        .rx-meta-label { font-size:10px; font-weight:600; text-transform:uppercase; color:#64748b; letter-spacing:.5px; }
        .rx-meta-val { font-size:13px; font-weight:600; color:#1a2942; margin-top:2px; }
        .rx-section { position:relative; background:#fff; border-radius:8px; padding:16px; margin-bottom:16px; border-left:4px solid #1a2942; overflow:hidden; }
        .rx-symbol { font-size:60px; font-weight:900; color:#1a2942; opacity:.1; position:absolute; top:0; left:0; line-height:1; font-style:italic; }
        .rx-item-row { display:grid; grid-template-columns:24px 1fr; gap:8px; align-items:start; padding:10px 0; border-bottom:1px dashed #e2e8f0; }
        .rx-item-row:last-child { border-bottom:none; }
        .rx-num { width:24px; height:24px; background:#1a2942; color:#fff; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:700; flex-shrink:0; margin-top:2px; }
        .med-name { font-size:14px; font-weight:700; color:#1a2942; }
        .med-generic { font-size:11px; color:#64748b; margin-top:2px; }
        .med-dosage { font-size:12px; margin-top:6px; background:#fff; padding:6px 10px; border-radius:5px; border-bottom:1px dashed #e2e8f0; }
        .med-dosage span { font-weight:600; color:#1a2942; }
        .side-effect-box { margin-top:6px; background:#fff; border:1px solid #e5e2d0; border-radius:5px; padding:5px 8px; font-size:11px; color:#78716c; }
        .contra-box { margin-top:4px; background:#fff; border:1px solid #ddd8d8; border-radius:5px; padding:5px 8px; font-size:11px; color:#7c6f6f; }
        .advice-box { background:#fff; border:1px solid #d1d5db; border-radius:8px; padding:12px 16px; margin-bottom:14px; }
        .advice-box h4 { font-size:12px; color:#1a2942; margin-bottom:6px; }
        .follow-up { background:#fff; border:1px solid #d1d5db; border-radius:8px; padding:10px 14px; font-size:12px; color:#1a2942; margin-bottom:14px; }
        .rx-footer { margin-top:auto; padding:12px 0 16px; border-top:1px solid #cbd5e1; text-align:center; }
        .rx-footer-services { font-size:11px; font-weight:600; color:#1a2942; letter-spacing:0.3px; margin-bottom:4px; }
        .rx-footer-timing { font-size:10.5px; color:#64748b; margin-bottom:3px; }
        .rx-footer-meta { font-size:10px; color:#94a3b8; margin-top:6px; display:flex; justify-content:space-between; }
        @media print {
            body { padding:14px 20px 0; }
            .no-print { display:none !important; }
            .rx-footer { position:fixed; bottom:14px; left:20px; right:20px; background:#fff; }
        }
    </style>
</head>
<body>

<div class="no-print" style="text-align:center;margin-bottom:16px;padding:10px;background:#f0f4f8;border-radius:8px">
    <button onclick="window.print()" style="background:#1a2942;color:#fff;border:none;padding:10px 24px;border-radius:7px;font-size:13px;font-weight:600;cursor:pointer">&#128424; Print Prescription</button>
    <button onclick="window.close()" style="background:#e2e8f0;color:#1a2942;border:none;padding:10px 24px;border-radius:7px;font-size:13px;font-weight:600;cursor:pointer;margin-left:10px">Close</button>
</div>

<div class="rx-content">

@if(setting('print_header_enabled','1') !== '0')
<div class="rx-header">
    <div>
        <div class="rx-clinic-name">{{ setting('clinic_name', 'Demo Clinic Pvt. Ltd.') }}</div>
        @php
            $addr = setting('clinic_address', '');
            $addrParts = array_filter(array_map('trim', explode(',', $addr)));
            $addrLine1 = implode(', ', array_slice($addrParts, 0, 2));
            $addrLine2 = implode(', ', array_slice($addrParts, 2));
        @endphp
        @if($addrLine1)<div class="rx-clinic-detail">{{ $addrLine1 }}</div>@endif
        @if($addrLine2)<div class="rx-clinic-detail">{{ $addrLine2 }}</div>@endif
        <div class="rx-clinic-detail" style="margin-top:4px">Ph: {{ setting('clinic_phone') }} &nbsp;&#183;&nbsp; Email: {{ setting('clinic_email') }}</div>
    </div>
    <div class="rx-doc-block">
        <div class="rx-doc-name">{{ $prescription->doctor->full_name }}</div>
        <div class="rx-doc-qual">{{ $prescription->doctor->qualification }}</div>
        <div class="rx-doc-qual">{{ $prescription->doctor->specialization }}</div>
        <div class="rx-doc-qual">Reg. No: {{ $prescription->doctor->registration_no }}</div>
    </div>
</div>
@endif

<div class="rx-meta">
    <div>
        <div class="rx-meta-label">Patient</div>
        <div class="rx-meta-val">{{ $prescription->patient->full_name }}</div>
        <div style="font-size:11px;color:#64748b;font-family:'DM Mono',monospace">{{ $prescription->patient->jhc_id }}</div>
    </div>
    <div>
        <div class="rx-meta-label">Age / Gender</div>
        <div class="rx-meta-val">{{ $prescription->patient->age }} yrs / {{ $prescription->patient->gender }}</div>
        @if($prescription->patient->blood_group)
        <div style="font-size:11px;color:#64748b">Blood Group: {{ $prescription->patient->blood_group }}</div>
        @endif
    </div>
    <div>
        <div class="rx-meta-label">Date</div>
        <div class="rx-meta-val">{{ $prescription->prescribed_date->format('d M Y') }}</div>
        <div style="font-size:11px;color:#64748b;font-family:'DM Mono',monospace">{{ $prescription->prescription_no }}</div>
    </div>
    @if($prescription->encounter && $prescription->encounter->diagnoses->count())
    <div>
        <div class="rx-meta-label">Diagnosis</div>
        <div class="rx-meta-val" style="font-size:12px">{{ $prescription->encounter->diagnoses->pluck('diagnosis_name')->join(', ') }}</div>
    </div>
    @endif
    @if($prescription->encounter && $prescription->encounter->allergies)
    <div style="background:#fff;border:1px solid #e8e0e0;border-radius:5px;padding:6px">
        <div class="rx-meta-label" style="color:#7c6f6f">&#9888; Allergies</div>
        <div class="rx-meta-val" style="color:#6b6060;font-size:12px">{{ $prescription->encounter->allergies }}</div>
    </div>
    @endif
</div>

<div class="rx-section">
    <div class="rx-symbol">Rx</div>
    @foreach($prescription->items as $i => $item)
    <div class="rx-item-row">
        <div class="rx-num">{{ $i+1 }}</div>
        <div>
            <div class="med-name">{{ $item->medicine->name }}</div>
            @php $generic = $item->generic_name ?: $item->medicine?->resolved_generic; @endphp
            @if($generic || $item->medicine->strength)
            <div class="med-generic">{{ $generic ? ucfirst($generic) : '' }}@if($item->medicine->strength) @if($generic) &mdash; @endif{{ $item->medicine->strength }}@endif</div>
            @endif
            <div class="med-dosage">
                <span>{{ $item->dose }}</span> &nbsp;&#183;&nbsp;
                <span>{{ $item->frequency }}</span> &nbsp;&#183;&nbsp;
                {{ $item->route }} &nbsp;&#183;&nbsp;
                For <span>{{ $item->duration_days }} days</span> &nbsp;&#183;&nbsp;
                Qty: <span>{{ $item->quantity }} {{ $item->medicine->unit }}</span>
                @if($item->instructions) &nbsp;&#183;&nbsp; {{ $item->instructions }}@endif
            </div>
            @if($item->medicine->side_effects)
            <div class="side-effect-box">&#9888; <strong>Side Effects:</strong> {{ $item->medicine->side_effects }}</div>
            @endif
            @if($item->medicine->contraindications)
            <div class="contra-box">&#128683; <strong>Contraindications:</strong> {{ $item->medicine->contraindications }}</div>
            @endif
        </div>
    </div>
    @endforeach
</div>

@if($prescription->advice)
<div class="advice-box">
    <h4>&#9989; Advice to Patient</h4>
    <div style="font-size:13px">{{ $prescription->advice }}</div>
</div>
@endif

@if($prescription->notes)
<div style="background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:10px 14px;margin-bottom:14px;font-size:12px">
    <strong>Notes:</strong> {{ $prescription->notes }}
</div>
@endif

@if($prescription->encounter && $prescription->encounter->follow_up_date)
<div class="follow-up">
    &#128197; <strong>Follow-up:</strong> {{ $prescription->encounter->follow_up_date->format('d M Y') }}
    @if($prescription->encounter->follow_up_instructions) &mdash; {{ $prescription->encounter->follow_up_instructions }}@endif
</div>
@endif

@php $validDays = $prescription->encounter?->follow_up_days ?? 7; @endphp
<div style="font-size:11.5px;color:#64748b;margin-top:6px;margin-bottom:10px;font-style:italic">
    &#9432; The Consultation is valid for {{ $validDays }} day{{ $validDays == 1 ? '' : 's' }}.
</div>

</div>

@if(setting('print_footer_enabled','1') !== '0')
<div class="rx-footer">
    <div class="rx-footer-services">{{ setting('print_footer_services', 'Medical Consultations  |  ECG  |  ECHO  |  Day Care  |  Lab  |  Pharmacy') }}</div>
    <div class="rx-footer-timing">{{ setting('print_footer_timings', 'Timings: 8:00 AM – 1:00 PM  &  4:00 PM – 6:00 PM  (Sundays Closed)  |  Appointments: +91-8900953527') }}</div>
    <div class="rx-footer-meta">
        <span>Generated: {{ now()->format('d M Y, h:i A') }}</span>
        <span style="font-family:'DM Mono',monospace">{{ $prescription->prescription_no }}</span>
        <span>Computer-generated prescription</span>
    </div>
</div>
@endif

</body>
</html>
