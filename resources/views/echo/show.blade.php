{{-- FILE: resources/views/echo/show.blade.php --}}
@extends('layouts.app')
@section('title','Echo '.$echo->record_no)
@section('page-title','Echo Report')
@section('content')

@include('diagnostics._toolbar')
@php
    $ef = $echo->ef_percent;
    $ef = $ef && !str_contains($ef,'%') ? $ef.'%' : $ef;
    $params = [
        [1,'RV WALL THICKNESS', $echo->rv_wall_thickness, '< 5 mm'],
        [2,'RVID',              $echo->rv_dimension,      '7-23 mm'],
        [3,'TAPSE',             $echo->tapse,             '> 17 mm'],
        [4,'IVC',               $echo->ivc_size,          '< 20 mm'],
        [5,'IVS',               $echo->ivs,               '6-12 mm'],
        [6,'LVPW',              $echo->lvpw,              '6-11 mm'],
        [7,'LVIDD',             $echo->lv_edd,            '35-55 mm'],
        [8,'LVIDS',             $echo->lv_esd,            '24-42 mm'],
        [9,'EPSS',              $echo->epss,              '< 7 mm'],
        [10,'MAPSE',            $echo->mapse,             '> 12 mm'],
        [11,'LA',               $echo->la_size,           '19-40 mm'],
        [12,'AO',               $echo->ao_root,           '25-37 mm'],
        [13,'EF',               $ef,                      '55-70 %'],
        [14,'FS',               $echo->fs_percent,        '28-41 %'],
        [15,"LAP (E/e')",       $echo->lap_ee,            'N <8, Int 8-14, H >14'],
        [16,'PAP',              $echo->pap,               '< 20 mmHg'],
    ];
@endphp

@php
    // Build a clean filename so the browser's inline-PDF "Save" picks it up
    // from the URL path (Chrome ignores Content-Disposition for inline PDFs).
    $pdfNameRaw = trim(($echo->patient->first_name ?? '') . ' ' . ($echo->patient->last_name ?? '')) ?: 'patient';
    $pdfNameRaw = trim(preg_replace('/[^A-Za-z0-9]+/', '-', $pdfNameRaw), '-');
    $pdfNoSafe  = str_replace(['/', '\\'], '-', $echo->record_no);
    $pdfName    = "{$pdfNameRaw}-Echo-{$pdfNoSafe}-" . $echo->record_date->format('d-m-Y') . '.pdf';
@endphp
{{-- Screen-only action bar --}}
<div class="d-print-none page-header">
    <div class="page-header-left">
        <h1>Echo Report — <span class="mono">{{ $echo->record_no }}</span></h1>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('echo.edit', $echo) }}" class="btn btn-primary"><i class="fa-solid fa-pen-to-square me-1"></i>Edit</a>
        @if($echo->echo_file)
        @php $hdrUrl = str_contains($echo->echo_file,'/') ? asset('storage/'.$echo->echo_file) : asset('storage/echo_files/'.$echo->echo_file); @endphp
        <a href="{{ $hdrUrl }}" target="_blank" class="btn btn-outline"><i class="fa-solid fa-eye"></i> View File</a>
        <a href="{{ $hdrUrl }}" download="{{ basename($echo->echo_file) }}" class="btn btn-outline"><i class="fa-solid fa-download"></i> Download</a>
        @endif
        <a href="{{ route('echo.print', ['echo' => $echo, 'filename' => $pdfName]) }}" target="_blank" class="btn btn-outline"><i class="fa-solid fa-print me-1"></i>Print</a>
        <form method="POST" action="{{ route('echo.send-report', $echo) }}" style="display:inline" onsubmit="return confirm('Send the echo report now?\n\nChannels are controlled at Admin → Notification Preferences → echo_report_ready.\n\nProceed?');">
            @csrf
            <button type="submit" class="btn btn-success"><i class="fa-solid fa-paper-plane"></i> Send Report</button>
        </form>
        <button type="button" class="btn btn-outline" onclick="document.getElementById('email-echo-modal').classList.add('open')"><i class="fa-solid fa-envelope"></i> Email</button>
        <button type="button" class="btn btn-outline" onclick="document.getElementById('whatsapp-echo-modal').classList.add('open')"><i class="fa-brands fa-whatsapp"></i> WhatsApp</button>
        <a href="{{ route('echo.index') }}" class="btn btn-outline">Back</a>
    </div>
</div>

{{-- Email Echo Report modal --}}
<div id="email-echo-modal" class="modal-overlay d-print-none">
    <div class="modal-box" style="max-width:480px">
        <div class="modal-header">
            <i class="fa-solid fa-envelope" style="color:var(--sc-teal)"></i>
            <h3>Email Echo Report</h3>
            <button class="modal-close" onclick="document.getElementById('email-echo-modal').classList.remove('open')">&times;</button>
        </div>
        <form method="POST" action="{{ route('echo.email', $echo) }}">
            @csrf
            <div class="modal-body" style="display:flex;flex-direction:column;gap:12px">
                <div class="form-group" style="margin:0">
                    <label class="form-label">To <span class="req">*</span></label>
                    <input type="email" name="to" class="form-control" value="{{ $echo->patient->email ?? '' }}" required>
                </div>
                <div class="form-group" style="margin:0">
                    <label class="form-label">Subject</label>
                    <input type="text" name="subject" class="form-control" placeholder="Auto-filled if left blank">
                </div>
                <div class="form-group" style="margin:0">
                    <label class="form-label">Message</label>
                    <textarea name="message" class="form-control" rows="4" placeholder="Auto-filled if left blank"></textarea>
                </div>
                <div class="text-muted" style="font-size:11px"><i class="fa-solid fa-info-circle"></i> The echo report PDF is attached automatically.</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('email-echo-modal').classList.remove('open')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-paper-plane"></i> Send</button>
            </div>
        </form>
    </div>
</div>

{{-- WhatsApp Echo Report modal --}}
<div id="whatsapp-echo-modal" class="modal-overlay d-print-none">
    <div class="modal-box" style="max-width:480px">
        <div class="modal-header">
            <i class="fa-brands fa-whatsapp" style="color:#25D366"></i>
            <h3>Send Echo Report via WhatsApp</h3>
            <button class="modal-close" onclick="document.getElementById('whatsapp-echo-modal').classList.remove('open')">&times;</button>
        </div>
        <form method="POST" action="{{ route('echo.whatsapp', $echo) }}">
            @csrf
            <div class="modal-body" style="display:flex;flex-direction:column;gap:12px">
                <div class="form-group" style="margin:0">
                    <label class="form-label">Send to <span class="req">*</span></label>
                    <input type="text" name="phone" class="form-control" value="{{ $echo->patient->phone ?? $echo->patient->alt_phone ?? '' }}" required>
                    <div class="text-muted" style="font-size:11px;margin-top:4px">Defaults to the patient's number — edit to send to a different one.</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('whatsapp-echo-modal').classList.remove('open')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fa-brands fa-whatsapp"></i> Send</button>
            </div>
        </form>
    </div>
</div>

{{-- ===== PRINTABLE REPORT ===== --}}
<div class="echo-rpt">

{{-- TWO print-frame tables so each can carry its own <thead>/<tfoot> that
     browsers repeat on every printed page. We split at the Comments
     boundary because page-break-before on an element INSIDE an outer
     table cell is ignored by Chrome — only top-level elements get the
     break honored reliably. --}}
<table class="rpt-print-frame rpt-print-frame--page1" width="100%" cellpadding="0" cellspacing="0">
<thead><tr><td>@include('echo._print_header')</td></tr></thead>
<tfoot><tr><td>@include('echo._print_footer')</td></tr></tfoot>
<tbody><tr><td>
    <div class="rpt-page-content">

        {{-- REPORT TITLE + PATIENT INFO --}}
        <div class="rpt-section-bar">ECHOCARDIOGRAPHY REPORT</div>
        <table class="rpt-info" width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td class="rpt-lbl">Unit No</td><td>{{ $echo->record_no }}</td>
                <td class="rpt-lbl">Date</td><td>{{ $echo->record_date->format('d/m/Y') }}</td>
            </tr>
            <tr>
                <td class="rpt-lbl">Name</td><td>{{ $echo->patient->first_name }}</td>
                <td class="rpt-lbl">Last Name</td><td>{{ $echo->patient->last_name }}</td>
            </tr>
            <tr>
                <td class="rpt-lbl">Age</td><td>{{ $echo->patient->age }}</td>
                <td class="rpt-lbl">Sex</td><td>{{ $echo->patient->gender }}</td>
            </tr>
            <tr>
                <td class="rpt-lbl">Diagnosis</td><td>{{ $echo->diagnosis ?: '—' }}</td>
                <td class="rpt-lbl">Ref By</td><td>{{ $echo->ref_by ?: '—' }}</td>
            </tr>
            <tr>
                <td class="rpt-lbl">Height (cm) / Wt (kg)</td>
                <td>{{ $echo->height_cm ?: '—' }} / {{ $echo->weight_kg ?: '—' }}</td>
                <td class="rpt-lbl">BSA (m²)</td><td>{{ $echo->bsa ?: '—' }}</td>
            </tr>
        </table>

        {{-- PARAMETERS TABLE --}}
        <div class="rpt-section-bar">LEFT &amp; RIGHT HEART PARAMETERS – 2D, M MODE</div>
        <table class="rpt-params" width="100%" cellpadding="0" cellspacing="0">
            <thead>
                <tr>
                    <th class="rpt-sno">SNO</th>
                    <th>PARAMETER</th>
                    <th>PATIENT&apos;S VALUE</th>
                    <th>NORMAL VALUE</th>
                </tr>
            </thead>
            <tbody>
                @foreach($params as [$no,$name,$val,$normal])
                <tr>
                    <td class="rpt-sno">{{ $no }}</td>
                    <td class="rpt-param-name">{{ $name }}</td>
                    <td class="rpt-patient-val">{{ $val ?: '' }}</td>
                    <td class="rpt-normal-val">{{ $normal }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        {{-- WALL MOTION --}}
        <div class="rpt-section-bar">WALL MOTION AND FUNCTION</div>
        <table class="rpt-wm" width="100%" cellpadding="0" cellspacing="0">
            <thead>
                <tr>
                    <th colspan="2" style="width:50%;text-align:center">LEFT VENTRICLE</th>
                    <th colspan="2" style="width:50%;text-align:center">RIGHT VENTRICLE</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="rpt-wm-lbl">Diastolic Function :</td>
                    <td class="rpt-wm-val">{{ $echo->lv_diastolic_function }}</td>
                    <td class="rpt-wm-lbl">Longitudinal Function</td>
                    <td class="rpt-wm-val">{{ $echo->rv_longitudinal_function }}</td>
                </tr>
                <tr>
                    <td class="rpt-wm-lbl">EA Ratio :</td>
                    <td class="rpt-wm-val">{{ $echo->ea_ratio }}</td>
                    <td class="rpt-wm-lbl">Transverse Function :</td>
                    <td class="rpt-wm-val">{{ $echo->rv_transverse_function }}</td>
                </tr>
                <tr>
                    <td class="rpt-wm-lbl">Systolic Function</td>
                    <td class="rpt-wm-val">{{ $echo->lv_systolic_function }}</td>
                    <td class="rpt-wm-lbl">Paradoxical Movement</td>
                    <td class="rpt-wm-val">{{ $echo->rv_paradoxical_movement }}</td>
                </tr>
                <tr>
                    <td class="rpt-wm-lbl">Interventricular Septum</td>
                    <td class="rpt-wm-val">{{ $echo->ivs_motion }}</td>
                    <td class="rpt-wm-lbl">Inter Atrial Septum</td>
                    <td class="rpt-wm-val">{{ $echo->ias_status }}</td>
                </tr>
                <tr>
                    <td class="rpt-wm-lbl">Pericardium</td>
                    <td class="rpt-wm-val">{{ $echo->pericardium_status }}</td>
                    <td class="rpt-wm-lbl">Pericardial Effusion</td>
                    <td class="rpt-wm-val">{{ $echo->pericardial_effusion }}</td>
                </tr>
            </tbody>
        </table>

        @php
            $rwmaSegments = [
                'Anterior'     => ['val' => $echo->anterior_motion,     'terr' => 'LAD'],
                'Anteroseptal' => ['val' => $echo->anteroseptal_motion, 'terr' => 'LAD'],
                'Inferior'     => ['val' => $echo->inferior_motion,     'terr' => 'RCA'],
                'Inferoseptal' => ['val' => $echo->inferoseptal_motion, 'terr' => 'RCA'],
                'Lateral'      => ['val' => $echo->lateral_motion,      'terr' => 'LCx'],
                'Posterior'    => ['val' => $echo->posterior_motion,    'terr' => 'LCx / RCA'],
                'Apex'         => ['val' => $echo->apex_motion,         'terr' => 'LAD / RCA'],
            ];
            $abnormalSegments = collect($rwmaSegments)->filter(fn($s) => $s['val'] && $s['val'] !== 'Normal');
            $hasRwma = $abnormalSegments->isNotEmpty() || !empty($echo->rwma_comments) || !empty($echo->la_clot) || !empty($echo->lv_clot);
        @endphp
        @if($hasRwma)
        <div class="rpt-section-bar">REGIONAL WALL MOTION</div>
        <table class="rpt-doppler" width="100%" cellpadding="0" cellspacing="0">
            <thead>
                <tr>
                    <th style="width:22%">SEGMENT</th>
                    <th style="width:18%">TERRITORY</th>
                    <th>WALL MOTION</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rwmaSegments as $label => $seg)
                    <tr>
                        <td class="rpt-valve-name">{{ $label }}</td>
                        <td>{{ $seg['terr'] }}</td>
                        <td>{{ $seg['val'] ?: 'Normal' }}</td>
                    </tr>
                @endforeach
                @if($echo->la_clot)
                    <tr>
                        <td class="rpt-valve-name">LA Clot</td>
                        <td>—</td>
                        <td>{{ $echo->la_clot }}</td>
                    </tr>
                @endif
                @if($echo->lv_clot)
                    <tr>
                        <td class="rpt-valve-name">LV Clot</td>
                        <td>—</td>
                        <td>{{ $echo->lv_clot }}</td>
                    </tr>
                @endif
            </tbody>
        </table>
        @if($echo->rwma_comments)
            <div style="padding:8px 12px;font-size:13px;line-height:1.5;background:#fafafa;border-left:3px solid var(--sc-teal);margin-bottom:10px">
                <strong>RWMA notes:</strong> {!! nl2br(e($echo->rwma_comments)) !!}
            </div>
        @endif
        @endif

        {{-- COLOR DOPPLER (page 2) --}}
        <div class="rpt-section-bar rpt-page2-start">COLOR DOPPLER STUDY</div>
        <table class="rpt-doppler" width="100%" cellpadding="0" cellspacing="0">
            <thead>
                <tr>
                    <th style="width:8%">VALVE</th>
                    <th style="width:18%">VELOCITY (cm/sec)</th>
                    <th style="width:22%">PRESSURE GRADIENT (mm/hg)</th>
                    <th>VALVULAR ANATOMY</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="rpt-valve-name">MV</td>
                    <td>{{ $echo->mv_velocity }}</td>
                    <td>{{ $echo->mv_gradient }}</td>
                    <td>{{ $echo->mitral_valve }}</td>
                </tr>
                <tr>
                    <td class="rpt-valve-name">AV</td>
                    <td>{{ $echo->av_velocity }}</td>
                    <td>{{ $echo->av_gradient }}</td>
                    <td>{{ $echo->aortic_valve }}</td>
                </tr>
                <tr>
                    <td class="rpt-valve-name">TV</td>
                    <td>{{ $echo->tv_velocity }}</td>
                    <td>{{ $echo->tv_gradient }}</td>
                    <td>{{ $echo->tricuspid_valve }}</td>
                </tr>
                <tr>
                    <td class="rpt-valve-name">PV</td>
                    <td>{{ $echo->pv_velocity }}</td>
                    <td>{{ $echo->pv_gradient }}</td>
                    <td>{{ $echo->pulmonary_valve }}</td>
                </tr>
            </tbody>
        </table>

    </div>{{-- /.rpt-page-content (page 1) --}}
</td></tr></tbody>
</table>{{-- /.rpt-print-frame--page1 --}}

{{-- PAGE 2: flex-column layout — header at top, comments next, then a
     flex-grow spacer that pushes the signature and footer to the bottom
     of the printed page regardless of how short the content is. --}}
<div class="rpt-page-frame rpt-print-frame--page2">
    @include('echo._print_header')

    <div class="rpt-page2-body">
        {{-- COMMENTS --}}
        <table class="rpt-comments" width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td class="rpt-comments-lbl">Comments</td>
                <td class="rpt-comments-val">
                    <div class="rpt-impression">{!! nl2br(e($echo->impression)) !!}</div>
                    @if($echo->recommendations)
                        <div class="rpt-recommendations">
                            <strong>Recommendations:</strong><br>
                            {!! nl2br(e($echo->recommendations)) !!}
                        </div>
                    @endif
                    <div class="rpt-disclaimer-wrap">
                        <em class="rpt-disclaimer">Echo findings are subjective to change depending on the patient's cardiac health. Not for medico legal purpose.</em>
                    </div>
                </td>
            </tr>
        </table>

        {{-- flex-grow spacer: absorbs any extra page height --}}
        <div class="rpt-flex-spacer"></div>

        {{-- SIGNATURE BLOCK — sits just above the footer --}}
        <div class="rpt-signature">
            <strong>{{ $echo->doctor->full_name }}</strong><br>
            @if($echo->doctor->qualification)<span>{!! nl2br(e($echo->doctor->qualification)) !!}</span><br>@endif
            @if($echo->doctor->specialization)<span>{{ $echo->doctor->specialization }}</span>@endif
        </div>
    </div>

    {{-- Footer: pinned to the bottom of the page-frame via flex layout. --}}
    @include('echo._print_footer')
</div>{{-- /.rpt-print-frame--page2 --}}

</div>{{-- /.echo-rpt --}}

@if($echo->echo_file)
@php
    $isPdf = str_ends_with(strtolower($echo->echo_file), '.pdf');
    $isLegacy = str_contains($echo->echo_file, '/');
    $fileUrl = $isLegacy
        ? asset('storage/' . $echo->echo_file)
        : asset('storage/echo_files/' . $echo->echo_file);
@endphp
<div class="card d-print-none" style="max-width:880px;margin:0 auto 40px">
    <div class="card-header" style="display:flex;align-items:center;justify-content:space-between">
        <span><i class="fa-solid fa-file-waveform" style="color:var(--sc-teal)"></i> Uploaded Echo Report &mdash; <span style="font-size:12px;color:var(--sc-muted);font-weight:400">{{ basename($echo->echo_file) }}</span></span>
        <div style="display:flex;gap:8px">
            <a href="{{ $fileUrl }}" target="_blank" class="btn btn-sm btn-outline"><i class="fa-solid fa-eye"></i> View</a>
            <a href="{{ $fileUrl }}" download="{{ basename($echo->echo_file) }}" class="btn btn-sm btn-outline"><i class="fa-solid fa-download"></i> Download</a>
            @if($isPdf)<button onclick="document.getElementById('echo-iframe').contentWindow.print()" class="btn btn-sm btn-outline"><i class="fa-solid fa-print"></i> Print</button>@endif
            <form method="POST" action="{{ route('echo.file.destroy', $echo) }}" style="display:inline" onsubmit="return confirm('Delete this uploaded file? The Echo record will be kept.')">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-sm btn-outline" style="color:var(--sc-red)" title="Delete file"><i class="fa-solid fa-trash"></i> Delete File</button>
            </form>
        </div>
    </div>
    <div class="card-body" style="padding:0">
        @if($isPdf)
            <iframe id="echo-iframe" src="{{ $fileUrl }}" style="width:100%;height:700px;border:0"></iframe>
        @else
            <img src="{{ $fileUrl }}" alt="Echo Report" style="width:100%;display:block;border-radius:0 0 8px 8px">
        @endif
    </div>
</div>
@endif {{-- echo_file --}}

@push('styles')
<style>
/* ===== REPORT STYLES (screen + print) ===== */
.echo-rpt {
    max-width: 880px;
    margin: 0 auto 40px;
    font-family: Arial, Helvetica, sans-serif;
    font-size: 11.5px;
    color: #111;
    line-height: 1.4;
}

/* Each print frame is a "page" on screen — boxed with background but no
   border (flat), sized to roughly an A4 sheet so a sparse page (e.g. page
   2 with just Comments) still looks like a full sheet rather than a thin
   strip. */
.rpt-print-frame,
.rpt-page-frame {
    background: #fff;
    border: none;
    box-shadow: none;
    padding: 18px 22px;
    width: 100%;
    box-sizing: border-box;
    min-height: 270mm;
}
/* The body cell takes the remaining height so the tfoot (services/
   timings) hugs the bottom of the page even when the body is short. */
.rpt-print-frame > tbody > tr > td { height: 100%; vertical-align: top; }
.rpt-print-frame--page2 { margin-top: 18px; }

/* Page-2 frame uses flex column so the footer (services/timings) sticks
   to the bottom of the page even when the body content is short. */
.rpt-page-frame.rpt-print-frame--page2 {
    display: flex;
    flex-direction: column;
}
.rpt-page-frame .rpt-page2-body {
    flex: 1;
    display: flex;
    flex-direction: column;
}
.rpt-page-frame .rpt-flex-spacer { flex: 1; }

/* ── Disclaimer ── */
.rpt-disclaimer { font-size: 9.5px; color: #666; }

/* ── Header ── */
.rpt-page-header { margin-bottom: 6px; }
.rpt-heart-icon { font-size: 28px; color: #c00; line-height: 1; }
.rpt-clinic-name {
    font-size: 16px; font-weight: 900;
    text-transform: uppercase; letter-spacing: 0.3px;
    color: #111; line-height: 1.1;
    word-wrap: break-word;
}
.rpt-tagline { font-size: 10px; color: #555; margin-top: 2px; }
.rpt-doc-info { font-size: 10.5px; line-height: 1.6; color: #333; word-wrap: break-word; overflow-wrap: break-word; }
.rpt-doc-info strong { font-size: 12px; }
.rpt-hr { border: none; border-top: 2px solid #333; margin: 6px 0 0; }
/* Force fixed table layout for the 3-column header so the long
   center clinic-name can't grow its column and squeeze the right
   address/phones column off the page. */
.rpt-page-header > table { table-layout: fixed; }

/* ── Footer (screen) ── */
.rpt-page-footer { margin-top: 10px; }
.rpt-footer-hr { border: none; border-top: 1px solid #ccc; margin: 0 0 3px; }
.rpt-footer-services, .rpt-footer-timings {
    font-size: 9.5px; color: #444; text-align: center; line-height: 1.6;
}

/* ── Section bars ── */
.rpt-section-bar {
    background: #d8d8d8; font-weight: 700; font-size: 11px;
    padding: 3px 8px; margin-top: 6px; margin-bottom: 0;
    letter-spacing: 0.3px;
}

/* ── Patient info table ── */
.rpt-info { border-collapse: collapse; margin-bottom: 4px; }
.rpt-info td { border: 1px solid #999; padding: 3px 8px; font-size: 11px; }
.rpt-lbl { font-weight: 700; background: #f0f0f0; width: 18%; white-space: nowrap; }

/* ── Parameters table ── */
.rpt-params { border-collapse: collapse; margin-bottom: 4px; }
.rpt-params th, .rpt-params td { border: 1px solid #999; padding: 2px 7px; font-size: 11px; }
.rpt-params th { font-weight: 700; background: #e8e8e8; font-size: 10.5px; }
.rpt-sno { text-align: center; width: 4%; }
.rpt-param-name { font-weight: 600; width: 25%; }
.rpt-patient-val { width: 28%; min-height: 18px; }
.rpt-normal-val { color: #555; width: 28%; font-size: 10.5px; }

/* ── Wall motion table ── */
.rpt-wm { border-collapse: collapse; margin-bottom: 4px; }
.rpt-wm th, .rpt-wm td { border: 1px solid #999; padding: 2px 7px; font-size: 11px; }
.rpt-wm th { font-weight: 700; background: #e8e8e8; padding: 3px 7px; }
.rpt-wm-lbl { font-weight: 600; width: 24%; color: #333; }
.rpt-wm-val { width: 26%; min-height: 18px; }

/* ── Doppler table ── */
.rpt-doppler { border-collapse: collapse; margin-bottom: 18px; }
.rpt-doppler th, .rpt-doppler td { border: 1px solid #999; padding: 2px 7px; font-size: 11px; }
.rpt-doppler th { font-weight: 700; background: #e8e8e8; font-size: 10.5px; }
.rpt-valve-name { font-weight: 700; }

/* ── Comments ── */
.rpt-comments { border-collapse: collapse; margin-bottom: 4px; }
.rpt-comments td { border: 1px solid #999; padding: 4px 8px; font-size: 11px; vertical-align: top; }
.rpt-comments-lbl { font-weight: 700; background: #f0f0f0; width: 9%; white-space: nowrap; }
.rpt-comments-val { min-height: 80px; line-height: 1.6; }
.rpt-impression { margin-bottom: 32px; }
.rpt-recommendations { margin-bottom: 32px; }
.rpt-disclaimer-wrap { margin-top: 32px; }

/* ── Signature block (page 2, above footer) ── */
/* margin-top kept modest so the services/timings footer fits on the
   printed page (220px = ~58mm was clipping the footer). */
.rpt-signature {
    margin-top: 60px;
    text-align: right;
    font-size: 10.5px;
    line-height: 1.6;
    color: #333;
}
.rpt-signature strong { font-size: 12px; color: #111; }

/* ===== PRINT STYLES ===== */
@media print {
    /* Hide browser chrome */
    .d-print-none { display: none !important; }
    aside, nav, header, .sidebar, .topbar, .page-header, .alert, footer { display: none !important; }
    body { background: white !important; margin: 0; }

    /* Defeat any layout containers that have overflow set on screen — they
       can render a remnant scrollbar onto the printed page (visible as a
       faint vertical line on the right edge of every page). Force
       overflow: hidden specifically — 'visible' doesn't always suppress
       the scrollbar that the browser already rendered on the live page. */
    html, body, #main-wrap, #page-content {
        overflow: hidden !important;
        height: auto !important;
        max-height: none !important;
    }

    /* Remove screen chrome from report wrapper */
    .echo-rpt {
        border: none; box-shadow: none;
        padding: 0; max-width: 100%; margin: 0;
        overflow: visible !important;
    }

    /* ── Print frames: each <table class="rpt-print-frame"> carries its own
       <thead>/<tfoot> that browsers repeat on every printed page. ── */
    table.rpt-print-frame {
        width: 100%; border-collapse: collapse;
        background: none; border: none; padding: 0;
        margin: 0;
        /* Do NOT min-height in print — combined with the page boundary +
           browser mm-to-px rounding it pushes the frame over the A4
           content area, producing a phantom blank page. Each frame
           should size to its natural content. */
        min-height: 0;
    }
    table.rpt-print-frame > thead { display: table-header-group; }
    table.rpt-print-frame > tfoot { display: table-footer-group; }
    table.rpt-print-frame > thead > tr > td,
    table.rpt-print-frame > tfoot > tr > td { padding: 0; }
    /* Body cell: a little horizontal padding so the data tables don't sit
       flush against the printable-area edge — Chrome's print engine clips
       sub-pixel borders that hit the boundary, dropping the right border. */
    table.rpt-print-frame > tbody > tr > td {
        padding: 0 6px;
        vertical-align: top;
    }

    /* Page 2 frame is forced onto its own page. */
    .rpt-print-frame--page2 { page-break-before: always; }

    /* Page-2 frame: natural flow — no fixed height, no positioning
       tricks. Header → comments → signature → footer all stack
       normally and the frame sizes to its content. Tradeoff: footer
       sits after the signature (not pinned to the paper edge), but
       the whole frame stays on one printed page rather than spilling
       to a 3rd page. */
    .rpt-page-frame.rpt-print-frame--page2 {
        position: static !important;
        display: block !important;
        height: auto !important;
        min-height: 0 !important;
        max-height: none !important;
        padding: 0 !important;
        margin: 0 !important;
        background: none !important;
        overflow: visible !important;
    }
    /* Footer renders inline after the signature; keep it on the same
       page as the rest of page 2. */
    .rpt-page-frame > .rpt-page-footer {
        position: static !important;
        margin-top: 8mm;
        background: white;
        padding: 4px 6px 0 !important;
        page-break-inside: avoid;
        page-break-before: avoid;
    }
    /* Body: simple block layout — no flex, no padding tricks. The
       signature follows comments naturally and the footer follows the
       signature. */
    .rpt-page-frame .rpt-page2-body {
        display: block !important;
        padding-bottom: 0 !important;
    }
    /* Flex spacer is invisible in print so it doesn't introduce extra
       space. */
    .rpt-page-frame .rpt-flex-spacer { display: none !important; }

    .rpt-page-header { background: white; padding-bottom: 2px; }
    .rpt-page-footer { background: white; padding-top: 2px; }
    .rpt-footer-hr { border-top: 1px solid #aaa; margin: 0 0 2px; }
    .rpt-footer-services, .rpt-footer-timings { font-size: 9px; }

    /* Signature margin is now 60px (~16mm) base; no print override
       needed since it's already a print-safe size. */

    @page { margin: 8mm 12mm; }

    /* Crisp solid black borders for print. Light gray borders render as
       halftone dots that look smudged. */
    .rpt-info, .rpt-info td,
    .rpt-params, .rpt-params td, .rpt-params th,
    .rpt-wm, .rpt-wm td, .rpt-wm th,
    .rpt-doppler, .rpt-doppler td, .rpt-doppler th,
    .rpt-comments, .rpt-comments td {
        border: 1px solid #000 !important;
    }
    /* NB: previously used outline + page-break-inside: avoid here. Both
       conspired with Chrome's print pagination to insert blank
       continuation pages after each frame — removed. */

    /* Force the printer/PDF engine to honor background colors (light
       header tinting) instead of dithering them into halftones. */
    .rpt-info, .rpt-params, .rpt-wm, .rpt-doppler, .rpt-comments,
    .rpt-lbl, .rpt-comments-lbl,
    .rpt-params th, .rpt-wm th, .rpt-doppler th, .rpt-section-bar {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
}
</style>
@endpush
@endsection
