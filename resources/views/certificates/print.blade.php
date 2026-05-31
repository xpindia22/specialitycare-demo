{{-- Standalone print view — no layout --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $cert->type_label }} — {{ $cert->certificate_no }}</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'DM Sans', sans-serif;
            font-size: 13px;
            color: #1a2942;
            background: #fff;
            padding: 32px;
            max-width: 800px;
            margin: 0 auto;
        }

        /* Header */
        .cert-header {
            border-bottom: 3px solid #0ab4b4;
            padding-bottom: 16px;
            margin-bottom: 18px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        .clinic-name { font-size: 20px; font-weight: 700; color: #0f1f3d; }
        .clinic-sub  { font-size: 11.5px; color: #64748b; margin-top: 2px; }
        .cert-meta   { text-align: right; }
        .cert-no     { font-family: 'DM Mono', monospace; font-size: 12px; color: #64748b; }
        .cert-date   { font-size: 12px; color: #64748b; margin-top: 2px; }

        /* Title */
        .cert-type-title {
            text-align: center;
            font-size: 17px;
            font-weight: 700;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: #0f1f3d;
            margin: 20px 0 6px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 12px;
        }

        /* Patient bar */
        .patient-bar {
            background: #0f1f3d;
            color: #fff;
            padding: 10px 16px;
            border-radius: 8px;
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 1fr;
            gap: 12px;
            margin: 16px 0;
            font-size: 12px;
        }
        .patient-bar .pb-label {
            display: block;
            opacity: .6;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: .4px;
            margin-bottom: 2px;
        }

        /* Body */
        .cert-body {
            margin: 20px 0;
            line-height: 1.85;
            font-size: 13.5px;
            color: #1a2942;
        }
        .cert-body p { margin-bottom: 12px; }

        /* Footer */
        .cert-footer {
            margin-top: 48px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }
        .sig-block { text-align: center; }
        .sig-line {
            width: 180px;
            border-top: 1px solid #1a2942;
            padding-top: 5px;
            font-size: 12px;
            color: #1a2942;
        }
        .sig-name { font-weight: 600; font-size: 13px; }
        .sig-sub  { font-size: 11px; color: #64748b; margin-top: 2px; }
        .seal-box {
            width: 110px; height: 110px;
            border: 1.5px dashed #c0ccd8;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 10px; color: #a0aec0; text-align: center;
            line-height: 1.4;
        }
        .printed-note { font-size: 10.5px; color: #94a3b8; margin-top: 4px; }

        .no-print { text-align: center; margin-bottom: 20px; }
        @media print { .no-print { display: none; } body { padding: 20px; } }
    </style>
</head>
<body>

<div class="no-print">
    <button onclick="window.print()"
            style="background:#0ab4b4;color:#fff;border:none;padding:10px 24px;border-radius:7px;font-size:13px;font-weight:600;cursor:pointer">
        <span style="margin-right:6px">&#128438;</span> Print Certificate
    </button>
</div>

{{-- Header --}}
@if(setting('print_header_enabled','1') !== '0')
<div class="cert-header">
    <div>
        <div class="clinic-name">{{ setting('clinic_name', 'SpecialityCare Hospital') }}</div>
        @if(setting('clinic_address'))
            <div class="clinic-sub">{{ setting('clinic_address') }}</div>
        @endif
        @if(setting('clinic_phone'))
            <div class="clinic-sub">Phone: {{ setting('clinic_phone') }}</div>
        @endif
    </div>
    <div class="cert-meta">
        <div class="cert-no">{{ $cert->certificate_no }}</div>
        <div class="cert-date">Date: {{ $cert->issued_date->format('d M Y') }}</div>
    </div>
</div>
@endif

{{-- Certificate Type Title --}}
<div class="cert-type-title">{{ $cert->type_label }}</div>

{{-- Patient Bar --}}
<div class="patient-bar">
    <div><span class="pb-label">Patient Name</span>{{ $cert->patient->full_name }}</div>
    <div><span class="pb-label">JHC ID</span><span style="font-family:'DM Mono',monospace">{{ $cert->patient->jhc_id }}</span></div>
    <div><span class="pb-label">Age / Gender</span>{{ $cert->patient->age }} / {{ $cert->patient->gender }}</div>
    <div><span class="pb-label">Issued On</span>{{ $cert->issued_date->format('d M Y') }}</div>
</div>

{{-- Certificate Body --}}
<div class="cert-body">
@php
    $p        = $cert->patient;
    $name     = $p->full_name;
    $age      = $p->age;
    $gender   = $p->gender ?? '';
    $jhcId    = $p->jhc_id;
    $pronoun  = (strtolower($gender) === 'female') ? 'She' : 'He';
    $pronoun2 = (strtolower($gender) === 'female') ? 'Her' : 'His';
    $diag     = $cert->diagnosis ?: 'illness';
    $date     = $cert->issued_date->format('d M Y');
    $purpose  = $cert->purpose;
    $remarks  = strip_tags($cert->remarks);
@endphp

@if($cert->certificate_type === 'medical_leave')
    @php
        $from = $cert->rest_from ? $cert->rest_from->format('d M Y') : '—';
        $to   = $cert->rest_to   ? $cert->rest_to->format('d M Y')   : '—';
        $days = ($cert->rest_from && $cert->rest_to)
            ? (\Carbon\Carbon::parse($cert->rest_from)->diffInDays(\Carbon\Carbon::parse($cert->rest_to)) + 1)
            : null;
    @endphp
    <p>
        This is to certify that <strong>{{ $name }}</strong>, Age {{ $age }}, {{ $gender }},
        JHC ID <strong>{{ $jhcId }}</strong>, was examined on {{ $date }} and found to be
        suffering from <strong>{{ $diag }}</strong>.
        {{ $pronoun }} is advised complete rest from <strong>{{ $from }}</strong> to <strong>{{ $to }}</strong>
        @if($days) ({{ $days }} day{{ $days > 1 ? 's' : '' }}) @endif
        and is unfit for duty during this period.
        @if($purpose) {{ $purpose }}. @endif
    </p>
    @if($remarks)
        <p>{{ $remarks }}</p>
    @endif

@elseif($cert->certificate_type === 'fitness')
    <p>
        This is to certify that <strong>{{ $name }}</strong>, Age {{ $age }}, {{ $gender }},
        JHC ID <strong>{{ $jhcId }}</strong>, was examined on {{ $date }} and found to be
        <strong>medically fit</strong> for all purposes.
        @if($purpose) {{ $purpose }}. @endif
    </p>
    @if($remarks)
        <p>{{ $remarks }}</p>
    @endif

@elseif($cert->certificate_type === 'accident')
    <p>
        This is to certify that <strong>{{ $name }}</strong>, Age {{ $age }}, {{ $gender }},
        JHC ID <strong>{{ $jhcId }}</strong>, was attended on {{ $date }} with a history of
        <strong>accidental injury</strong>.
        @if($cert->diagnosis) {{ $diag }}. @endif
        Treatment was provided accordingly.
    </p>
    @if($remarks)
        <p>{{ $remarks }}</p>
    @endif

@elseif($cert->certificate_type === 'medical_examination')
    <p>
        This is to certify that <strong>{{ $name }}</strong>, Age {{ $age }}, {{ $gender }},
        JHC ID <strong>{{ $jhcId }}</strong>, was examined on {{ $date }} and the following
        findings were noted:
    </p>
    <p style="padding-left:16px;border-left:3px solid #0ab4b4">
        {{ $diag }}{{ $remarks ? ' — ' . $remarks : '' }}
    </p>

@elseif($cert->certificate_type === 'age_nativity')
    <p>
        This is to certify that <strong>{{ $name }}</strong>, JHC ID <strong>{{ $jhcId }}</strong>,
        appears to be of approximately <strong>{{ $age }}</strong> of age based on
        clinical examination conducted on {{ $date }}.
    </p>
    @if($remarks)
        <p>{{ $remarks }}</p>
    @endif

@elseif($cert->certificate_type === 'attendant')
    <p>
        This is to certify that <strong>{{ $name }}</strong>, Age {{ $age }}, {{ $gender }},
        JHC ID <strong>{{ $jhcId }}</strong>, is under medical care and
        requires an attendant / relative to accompany them.
        @if($purpose) {{ $purpose }}. @endif
    </p>
    @if($remarks)
        <p>{{ $remarks }}</p>
    @endif

@elseif($cert->certificate_type === 'domiciliary')
    <p>
        This is to certify that <strong>{{ $name }}</strong>, Age {{ $age }}, {{ $gender }},
        JHC ID <strong>{{ $jhcId }}</strong>, is under domiciliary treatment for
        <strong>{{ $diag }}</strong> and is unable to attend
        {{ $purpose ?: 'duty' }} during this period.
    </p>
    @if($remarks)
        <p>{{ $remarks }}</p>
    @endif

@else
    <p>
        This is to certify that <strong>{{ $name }}</strong>, Age {{ $age }}, {{ $gender }},
        JHC ID <strong>{{ $jhcId }}</strong>, was examined on {{ $date }}.
        @if($diag !== 'illness') {{ $diag }}. @endif
        @if($purpose) {{ $purpose }}. @endif
        @if($remarks) {{ $remarks }} @endif
    </p>
@endif
</div>

{{-- Footer --}}
<div class="cert-footer">
    <div>
        <div class="printed-note">Printed on: {{ now()->format('d M Y, h:i A') }}</div>
    </div>

    <div class="seal-box">Hospital<br>Seal</div>

    <div class="sig-block">
        <div style="height:50px"></div>
        <div class="sig-line">
            @if($cert->doctor)
                <div class="sig-name">Dr. {{ $cert->doctor->first_name }} {{ $cert->doctor->last_name }}</div>
                @if($cert->doctor->qualification)
                    <div class="sig-sub">{{ $cert->doctor->qualification }}</div>
                @endif
                @if($cert->doctor->registration_no)
                    <div class="sig-sub">Reg. No: {{ $cert->doctor->registration_no }}</div>
                @endif
                @if($cert->doctor->specialization)
                    <div class="sig-sub">{{ $cert->doctor->specialization }}</div>
                @endif
            @else
                <div class="sig-name">Authorised Signatory</div>
                <div class="sig-sub">{{ setting('clinic_name', 'SpecialityCare Hospital') }}</div>
            @endif
        </div>
    </div>
</div>

</body>
</html>
