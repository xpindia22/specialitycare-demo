{{-- DomPDF-rendered Echo report — used by EchoController@print and
     PatientNotifier::sendEchoReport. Uses position:fixed header/footer
     inside the @page margin area so DomPDF reliably stamps the clinic
     letterhead at the top and the services/timings footer at the bottom
     of every printed page (DomPDF spec-compliant pattern). --}}
@php
    $ef = $echo->ef_percent;
    $efDisp = $ef && !str_contains((string)$ef,'%') ? $ef.'%' : $ef;
    $params = [
        [1,  'RV WALL THICKNESS', $echo->rv_wall_thickness, '< 5 mm'],
        [2,  'RVID',              $echo->rv_dimension,      '7-23 mm'],
        [3,  'TAPSE',             $echo->tapse,             '> 17 mm'],
        [4,  'IVC',               $echo->ivc_size,          '< 20 mm'],
        [5,  'IVS',               $echo->ivs,               '6-12 mm'],
        [6,  'LVPW',              $echo->lvpw,              '6-11 mm'],
        [7,  'LVIDD',             $echo->lv_edd,            '35-55 mm'],
        [8,  'LVIDS',             $echo->lv_esd,            '24-42 mm'],
        [9,  'EPSS',              $echo->epss,              '< 7 mm'],
        [10, 'MAPSE',             $echo->mapse,             '> 12 mm'],
        [11, 'LA',                $echo->la_size,           '19-40 mm'],
        [12, 'AO',                $echo->ao_root,           '25-37 mm'],
        [13, 'EF',                $efDisp,                  '55-70 %'],
        [14, 'FS',                $echo->fs_percent,        '28-41 %'],
        [15, "LAP (E/e')",        $echo->lap_ee,            'N <8, Int 8-14, H >14'],
        [16, 'PAP',               $echo->pap,               '< 20 mmHg'],
    ];
    preg_match_all('/\d{6,}/', (string) setting('clinic_phone'), $phM);
    $phLine = implode(', ', $phM[0] ?? []);
    $headerOn = setting('print_header_enabled', '1') !== '0';
    $footerOn = setting('print_footer_enabled', '1') !== '0';
    $svcLine     = (string) setting('print_footer_services', 'Medical Consultations · Lab · Pharmacy · ECG · ECHO · Day Care');
    $timingsLine = (string) setting('print_footer_timings',  'Mon – Sat: 8:00 AM – 1:00 PM & 4:00 PM – 6:30 PM   ·   Sundays Closed');
@endphp
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Echo {{ $echo->record_no }}</title>
<style>
/* @page margin reserves space for the fixed header (top) and footer (bottom).
   Header sits in the top margin, footer sits in the bottom margin, body
   content occupies the area in between. */
@page { margin: 38mm 12mm 24mm 12mm; }
body { font-family: DejaVu Sans, sans-serif; color:#111; font-size:11px; line-height:1.4; margin:0; }
table { border-collapse:collapse; }

/* ── Fixed page header (clinic letterhead band) ───────────────────────
   `position: fixed` + negative top pushes the box up into the @page
   top margin area so it repeats on every page without overlapping body. */
.page-header {
    position: fixed;
    top: -32mm; left: 0; right: 0;
    height: 30mm;
}
.page-footer {
    position: fixed;
    bottom: -18mm; left: 0; right: 0;
    height: 16mm;
}

/* ── Header content ───────────────────────────────────────────────── */
.hdr { width:100%; }
.hdr td { vertical-align:top; padding:0; border:none; }
.hdr .doc-info { font-size:10.5px; line-height:1.5; color:#333; }
.hdr .doc-info strong { font-size:12px; color:#111; }
.hdr .clinic-name {
    font-size:16px; font-weight:900; text-transform:uppercase;
    letter-spacing:0.3px; color:#111; line-height:1.1; text-align:center;
}
.hdr .heart { font-size:22px; color:#c00; text-align:center; line-height:1; }
.hdr .right { text-align:right; }
.hdr-rule { border:none; border-top:1.5pt solid #333; margin:4px 0 0; }

/* ── Footer content ───────────────────────────────────────────────── */
.ftr-rule { border:none; border-top:1px solid #aaa; margin:0 0 2px; }
.ftr { text-align:center; font-size:9px; color:#444; line-height:1.5; }

/* ── Body content ──────────────────────────────────────────────────── */
.section-bar {
    background:#d8d8d8; font-weight:700; font-size:11px;
    padding:3px 8px; margin-top:6px; letter-spacing:0.3px;
}
.info { width:100%; }
.info td  { border:1pt solid #000; padding:3px 8px; font-size:11px; }
.info .lbl { font-weight:700; background:#f0f0f0; width:18%; white-space:nowrap; }

.params { width:100%; }
.params th, .params td { border:1pt solid #000; padding:2px 7px; font-size:11px; }
.params th { font-weight:700; background:#e8e8e8; font-size:10.5px; }
.params .sno { text-align:center; width:5%; }

.wm { width:100%; }
.wm th, .wm td { border:1pt solid #000; padding:2px 7px; font-size:11px; }
.wm th { font-weight:700; background:#e8e8e8; padding:3px 7px; }
.wm .lbl { font-weight:600; width:24%; color:#333; }

.dop { width:100%; margin-bottom:18px; }
.dop th, .dop td { border:1pt solid #000; padding:2px 7px; font-size:11px; }
.dop th { font-weight:700; background:#e8e8e8; font-size:10.5px; }
.dop .valve { font-weight:700; }

.comments { width:100%; }
.comments td { border:1pt solid #000; padding:4px 8px; font-size:11px; vertical-align:top; }
.comments .lbl { font-weight:700; background:#f0f0f0; width:9%; white-space:nowrap; }
.comments .val { line-height:1.6; }
.impression { margin-bottom: 10px; }
.recs       { margin-bottom: 10px; }
.disclaimer { font-style:italic; font-size:9.5px; color:#666; }

.page-break { page-break-before: always; }

.signature {
    margin-top: 24px;
    text-align: right;
    font-size: 10.5px;
    line-height: 1.6;
    color: #333;
}
.signature strong { font-size: 12px; color: #111; }
</style>
</head>
<body>

@if($headerOn)
<div class="page-header">
    <table class="hdr">
        <tr>
            <td style="width:34%">
                <div class="doc-info">
                    <strong>{{ $echo->doctor->full_name ?? '' }}</strong><br>
                    @if($echo->doctor->qualification ?? null)
                        {!! nl2br(e($echo->doctor->qualification)) !!}<br>
                    @endif
                    @if($echo->doctor->specialization ?? null)
                        {{ $echo->doctor->specialization }}
                    @endif
                </div>
            </td>
            <td style="width:33%">
                @if(setting('clinic_logo'))
                    <div style="text-align:center"><img src="{{ public_path('storage/' . setting('clinic_logo')) }}" style="height:42px"></div>
                @else
                    <div class="heart">&#9829;</div>
                @endif
                <div class="clinic-name">{{ setting('clinic_name', 'Demo Clinic Pvt. Ltd.') }}</div>
            </td>
            <td style="width:33%" class="right">
                <div class="doc-info" style="text-align:right">
                    <strong>{{ setting('clinic_name') }}</strong><br>
                    @if(setting('clinic_address'))
                        {!! nl2br(e(setting('clinic_address'))) !!}<br>
                    @endif
                    @if($phLine)
                        Ph: {{ $phLine }}
                    @endif
                </div>
            </td>
        </tr>
    </table>
    <div class="hdr-rule"></div>
</div>
@endif

@if($footerOn)
<div class="page-footer">
    <div class="ftr-rule"></div>
    <div class="ftr">
        @if($svcLine !== '')
            <div><strong>Services:</strong>&nbsp;{{ $svcLine }}</div>
        @endif
        @if($timingsLine !== '')
            <div><strong>Timings:</strong>&nbsp;{{ $timingsLine }}</div>
        @endif
    </div>
</div>
@endif

<div class="content">
    {{-- ===== PAGE 1: report tables ===== --}}
    <div class="section-bar">ECHOCARDIOGRAPHY REPORT</div>
            <table class="info">
                <tr>
                    <td class="lbl">Unit No</td><td>{{ $echo->record_no }}</td>
                    <td class="lbl">Date</td><td>{{ optional($echo->record_date)->format('d/m/Y') }}</td>
                </tr>
                <tr>
                    <td class="lbl">Name</td><td>{{ $echo->patient->first_name ?? '' }}</td>
                    <td class="lbl">Last Name</td><td>{{ $echo->patient->last_name ?? '' }}</td>
                </tr>
                <tr>
                    <td class="lbl">Age</td><td>{{ $echo->patient->age ?? '' }}</td>
                    <td class="lbl">Sex</td><td>{{ $echo->patient->gender ?? '' }}</td>
                </tr>
                <tr>
                    <td class="lbl">Diagnosis</td><td>{{ $echo->diagnosis ?: '—' }}</td>
                    <td class="lbl">Ref By</td><td>{{ $echo->ref_by ?: '—' }}</td>
                </tr>
                <tr>
                    <td class="lbl">Height (cm) / Wt (kg)</td>
                    <td>{{ $echo->height_cm ?: '—' }} / {{ $echo->weight_kg ?: '—' }}</td>
                    <td class="lbl">BSA (m²)</td><td>{{ $echo->bsa ?: '—' }}</td>
                </tr>
            </table>

            <div class="section-bar">LEFT &amp; RIGHT HEART PARAMETERS – 2D, M MODE</div>
            <table class="params">
                <thead>
                    <tr>
                        <th class="sno">SNO</th><th>PARAMETER</th>
                        <th>PATIENT'S VALUE</th><th>NORMAL VALUE</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($params as [$n, $name, $val, $normal])
                    <tr>
                        <td class="sno">{{ $n }}</td>
                        <td style="font-weight:600">{{ $name }}</td>
                        <td>{{ $val ?: '' }}</td>
                        <td style="color:#555">{{ $normal }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="section-bar">WALL MOTION AND FUNCTION</div>
            <table class="wm">
                <thead>
                    <tr>
                        <th colspan="2" style="width:50%;text-align:center">LEFT VENTRICLE</th>
                        <th colspan="2" style="width:50%;text-align:center">RIGHT VENTRICLE</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td class="lbl">Diastolic Function :</td><td>{{ $echo->lv_diastolic_function }}</td><td class="lbl">Longitudinal Function</td><td>{{ $echo->rv_longitudinal_function }}</td></tr>
                    <tr><td class="lbl">EA Ratio :</td><td>{{ $echo->ea_ratio }}</td><td class="lbl">Transverse Function :</td><td>{{ $echo->rv_transverse_function }}</td></tr>
                    <tr><td class="lbl">Systolic Function</td><td>{{ $echo->lv_systolic_function }}</td><td class="lbl">Paradoxical Movement</td><td>{{ $echo->rv_paradoxical_movement }}</td></tr>
                    <tr><td class="lbl">Interventricular Septum</td><td>{{ $echo->ivs_motion }}</td><td class="lbl">Inter Atrial Septum</td><td>{{ $echo->ias_status }}</td></tr>
                    <tr><td class="lbl">Pericardium</td><td>{{ $echo->pericardium_status }}</td><td class="lbl">Pericardial Effusion</td><td>{{ $echo->pericardial_effusion }}</td></tr>
                </tbody>
            </table>

            {{-- Page 1 ends here. Regional Wall Motion (if any) + Color Doppler
                 + Comments + signature all flow onto page 2. --}}
            <div class="page-break"></div>

            @php
                $rwmaSegments = [
                    'Anterior'     => $echo->anterior_motion,
                    'Anteroseptal' => $echo->anteroseptal_motion,
                    'Inferior'     => $echo->inferior_motion,
                    'Inferoseptal' => $echo->inferoseptal_motion,
                    'Lateral'      => $echo->lateral_motion,
                    'Posterior'    => $echo->posterior_motion,
                    'Apex'         => $echo->apex_motion,
                ];
                $abnormalSegments = collect($rwmaSegments)->filter(fn($v) => $v && $v !== 'Normal');
                $hasRwma = $abnormalSegments->isNotEmpty() || !empty($echo->rwma_comments);
            @endphp
            @if($hasRwma)
            <div class="section-bar">REGIONAL WALL MOTION</div>
            <table class="parameters" style="margin-bottom:6px">
                <tbody>
                    @foreach(array_chunk($rwmaSegments, 2, true) as $pair)
                        <tr>
                            @foreach($pair as $label => $value)
                                <td class="lbl" style="width:18%">{{ $label }}</td>
                                <td style="width:32%">{{ $value ?: '—' }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @if($echo->rwma_comments)
                <div style="font-size:11px;padding:4px 8px 8px 8px;line-height:1.4">
                    <strong>RWMA notes:</strong> {!! nl2br(e($echo->rwma_comments)) !!}
                </div>
            @endif
            @endif

            <div class="section-bar">COLOR DOPPLER STUDY</div>
            <table class="dop">
                <thead>
                    <tr>
                        <th style="width:8%">VALVE</th>
                        <th style="width:18%">VELOCITY (cm/sec)</th>
                        <th style="width:22%">PRESSURE GRADIENT (mm/hg)</th>
                        <th>VALVULAR ANATOMY</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td class="valve">MV</td><td>{{ $echo->mv_velocity }}</td><td>{{ $echo->mv_gradient }}</td><td>{{ $echo->mitral_valve }}</td></tr>
                    <tr><td class="valve">AV</td><td>{{ $echo->av_velocity }}</td><td>{{ $echo->av_gradient }}</td><td>{{ $echo->aortic_valve }}</td></tr>
                    <tr><td class="valve">TV</td><td>{{ $echo->tv_velocity }}</td><td>{{ $echo->tv_gradient }}</td><td>{{ $echo->tricuspid_valve }}</td></tr>
                    <tr><td class="valve">PV</td><td>{{ $echo->pv_velocity }}</td><td>{{ $echo->pv_gradient }}</td><td>{{ $echo->pulmonary_valve }}</td></tr>
                </tbody>
            </table>

    {{-- Comments + signature flow inline after Doppler on page 2. --}}
    <table class="comments">
        <tr>
            <td class="lbl">Comments</td>
            <td class="val">
                <div class="impression">{!! nl2br(e($echo->impression ?? '')) !!}</div>
                @if($echo->recommendations)
                    <div class="recs">
                        <strong>Recommendations:</strong><br>
                        {!! nl2br(e($echo->recommendations)) !!}
                    </div>
                @endif
                <div class="disclaimer">Echo findings are subjective to change depending on the patient's cardiac health. Not for medico legal purpose.</div>
            </td>
        </tr>
    </table>

    {{-- Signature is part of the body flow — appears at end of report body --}}
    <div class="signature">
        <strong>{{ $echo->doctor->full_name ?? '' }}</strong><br>
        @if($echo->doctor->qualification ?? null)
            {!! nl2br(e($echo->doctor->qualification)) !!}<br>
        @endif
        @if($echo->doctor->specialization ?? null)
            {{ $echo->doctor->specialization }}
        @endif
    </div>
</div>

</body>
</html>
