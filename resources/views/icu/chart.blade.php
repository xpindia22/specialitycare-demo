{{-- FILE: resources/views/icu/chart.blade.php — ICU chart: monitor, infusions, trends, hourly flowsheet --}}
@extends('layouts.app')
@section('title','ICU Chart — '.$admission->patient->first_name.' '.$admission->patient->last_name)
@section('page-title','ICU Chart')

@php
    use App\Models\IpdVitals;
    use App\Models\IcuInfusion;
    use App\Services\EarlyWarning;
    $p = $admission->patient;
    $age = $p->date_of_birth ? $p->date_of_birth->age . ' y' : '';
    $lv = $news ? EarlyWarning::LEVELS[$news['level']] : null;
    // flowsheet cell colouring
    $bad = function ($field, $v) {
        if ($v === null || $v === '') return '';
        $rules = ['pulse' => [50, 120, 40, 140], 'bp_systolic' => [90, 180, 80, 200], 'mean_ap' => [65, 110, 55, 130],
                  'respiration_rate' => [10, 24, 8, 30], 'spo2' => [94, 101, 90, 101], 'temperature' => [96.8, 100.4, 95, 102.2],
                  'blood_glucose' => [80, 200, 70, 300], 'news2' => [-1, 4, -1, 6], 'cvp' => [2, 12, 0, 16]];
        if (!isset($rules[$field])) return '';
        [$lo, $hi, $clo, $chi] = $rules[$field];
        if ($v < $clo || $v > $chi) return 'fs-crit';
        if ($v < $lo || $v > $hi) return 'fs-warn';
        return '';
    };
    $last = $latest;
    $isOwnerOrAdmin = auth()->user()->isAdmin();
@endphp

@push('styles')
<style>
    .icu-head { display:flex; gap:16px; align-items:center; flex-wrap:wrap; }
    .icu-head .who { font-size:20px; font-weight:800; color:var(--sc-navy); }
    .icu-head .meta { font-size:13px; color:var(--sc-muted); }
    .mon { display:grid; grid-template-columns:repeat(auto-fill,minmax(130px,1fr)); gap:10px; margin-bottom:16px; }
    .mon-tile { background:#0b1324; color:#e2e8f0; border-radius:10px; padding:10px 12px; min-height:84px; position:relative; }
    .mon-tile .lbl { font-size:11px; text-transform:uppercase; letter-spacing:.06em; opacity:.7; }
    .mon-tile .val { font-size:28px; font-weight:800; line-height:1.1; font-family:'DM Mono',monospace; }
    .mon-tile .sub { font-size:11px; opacity:.75; }
    .mon-tile.hr .val { color:#4ade80; } .mon-tile.bp .val { color:#f87171; } .mon-tile.spo2 .val { color:#38bdf8; }
    .mon-tile.rr .val { color:#facc15; } .mon-tile.temp .val { color:#fb923c; } .mon-tile.gcs .val { color:#c084fc; }
    .mon-tile.cvp .val { color:#f472b6; } .mon-tile.glu .val { color:#a3e635; } .mon-tile.o2 .val { font-size:18px; color:#7dd3fc; }
    .mon-tile.alarm { outline:3px solid #ef4444; animation: blink 1.2s infinite; }
    @keyframes blink { 50% { outline-color: transparent; } }
    .alert-strip { display:flex; gap:6px; flex-wrap:wrap; margin-bottom:12px; }
    .al { padding:5px 10px; border-radius:6px; font-size:13px; font-weight:600; }
    .al.danger { background:#fee2e2; color:#991b1b; } .al.warn { background:#fef3c7; color:#92400e; }
    .news-card { border-radius:10px; padding:12px 14px; margin-bottom:12px; }
    .news-parts span { display:inline-block; font-size:11px; padding:2px 6px; border-radius:4px; background:rgba(255,255,255,.7); margin:2px 2px 0 0; }
    .icu-cols { display:grid; grid-template-columns:minmax(0,1fr) 380px; gap:18px; align-items:start; }
    @media (max-width: 1200px) { .icu-cols { grid-template-columns:1fr; } }
    .chart-box { position:relative; height:220px; }
    .chart-box.sm { height:130px; }
    .inf-table td { vertical-align:middle; }
    .inf-dose { font-size:16px; font-weight:800; color:var(--sc-navy); white-space:nowrap; }
    .inf-rate { font-size:16px; font-weight:800; color:var(--sc-teal); white-space:nowrap; }
    .fs-wrap { overflow:auto; max-height:640px; }
    .fs { border-collapse:separate; border-spacing:0; font-size:12px; }
    .fs th, .fs td { padding:4px 8px; border-bottom:1px solid var(--sc-border); border-right:1px solid var(--sc-border); white-space:nowrap; text-align:center; }
    .fs th:first-child, .fs td:first-child { position:sticky; left:0; background:var(--sc-surface,#fff); text-align:left; font-weight:600; z-index:1; }
    .fs thead th { position:sticky; top:0; background:var(--sc-bg,#f8fafc); z-index:2; }
    .fs thead th:first-child { z-index:3; }
    .fs tr.grp td { background:var(--sc-bg,#f8fafc); font-size:11px; text-transform:uppercase; letter-spacing:.05em; color:var(--sc-muted); font-weight:700; text-align:left; }
    .fs-warn { background:#fef3c7; } .fs-crit { background:#fee2e2; color:#991b1b; font-weight:700; }
    .obs-form .form-group { margin-bottom:8px; }
    .obs-form .form-label { font-size:12px; margin-bottom:2px; }
    .obs-form .form-control { padding:6px 8px; height:auto; }
    .obs-sec { font-size:11px; text-transform:uppercase; letter-spacing:.06em; color:var(--sc-muted); font-weight:700; margin:10px 0 6px; border-bottom:1px solid var(--sc-border); padding-bottom:3px; }
    .g3 { display:grid; grid-template-columns:1fr 1fr 1fr; gap:6px; }
    .g2 { display:grid; grid-template-columns:1fr 1fr; gap:6px; }
    .modal-bg { display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:9999; align-items:center; justify-content:center; overflow-y:auto; }
    .modal-box { background:var(--sc-surface,#fff); border-radius:10px; padding:22px; width:560px; max-width:96vw; margin:20px auto; }
    .modal-title { font-size:16px; font-weight:700; color:var(--sc-navy); margin-bottom:14px; display:flex; justify-content:space-between; }
    .calc-flag { font-size:12px; padding:6px 10px; border-radius:6px; margin-top:6px; }
    @media print {
        .sidebar, .topbar, .no-print, .section-toolbar { display:none !important; }
        .icu-cols { grid-template-columns:1fr; }
        .main-content, .content { margin:0 !important; padding:0 !important; }
        .mon-tile { background:#fff !important; color:#000 !important; border:1px solid #999; }
        .mon-tile .val { color:#000 !important; }
        .fs-wrap { max-height:none; overflow:visible; }
        @page { size: landscape; margin: 8mm; }
    }
</style>
@endpush

@section('content')
<div class="page-header">
    <div class="page-header-left">
        <div class="icu-head">
            <div>
                <div class="who"><i class="fa-solid fa-heart-pulse" style="color:#ef4444"></i> {{ $p->first_name }} {{ $p->last_name }}
                    <span class="meta" style="font-weight:400">{{ $age }} {{ $p->gender }}</span></div>
                <div class="meta">
                    <a href="{{ route('admissions.show', $admission) }}">{{ $admission->ipd_no }}</a>
                    · {{ $admission->ward->name ?? '—' }} {{ $admission->bed ? 'Bed '.$admission->bed->bed_number : '' }}
                    · Day {{ $admission->days_admitted }}
                    @if($admission->admittingDoctor) · Dr. {{ $admission->admittingDoctor->first_name }} {{ $admission->admittingDoctor->last_name }} @endif
                    · Weight <b>{{ $weight ? $weight.' kg' : 'not recorded' }}</b>
                    @if($p->blood_group) · {{ $p->blood_group }} @endif
                    @if($admission->status !== 'admitted') · <span class="badge badge-gray">{{ $admission->status_label }}</span> @endif
                </div>
                @if($admission->provisional_diagnosis)<div class="meta"><i class="fa-solid fa-stethoscope"></i> {{ $admission->provisional_diagnosis }}</div>@endif
                @if($p->allergies)<div class="meta" style="color:#b91c1c"><i class="fa-solid fa-triangle-exclamation"></i> Allergies: {{ $p->allergies }}</div>@endif
            </div>
        </div>
    </div>
    <div class="no-print" style="display:flex;gap:6px;flex-wrap:wrap;align-items:center">
        <select class="form-control" style="width:auto" onchange="location.href='{{ route('icu.chart', $admission) }}?hours='+this.value">
            @foreach(\App\Http\Controllers\IcuController::WINDOWS as $h => $l)<option value="{{ $h }}" @selected($h == $hours)>Last {{ $l }}</option>@endforeach
        </select>
        <a href="{{ route('icu.board') }}" class="btn btn-sm btn-outline"><i class="fa-solid fa-display"></i> ICU Board</a>
        <a href="{{ route('icu.calculator', ['admission' => $admission->id]) }}" class="btn btn-sm btn-outline"><i class="fa-solid fa-calculator"></i> Drug Calculator</a>
        <a href="{{ route('admissions.show', $admission) }}#tab-io" class="btn btn-sm btn-outline"><i class="fa-solid fa-droplet"></i> I/O</a>
        <a href="{{ route('admissions.show', $admission) }}#tab-mar" class="btn btn-sm btn-outline"><i class="fa-solid fa-pills"></i> Medicines</a>
        <button class="btn btn-sm btn-outline" onclick="window.print()"><i class="fa-solid fa-print"></i> Print</button>
    </div>
</div>

{{-- ── Alerts + NEWS2 ── --}}
@if(count($alerts))
<div class="alert-strip">
    @foreach($alerts as [$sev, $msg])<span class="al {{ $sev }}"><i class="fa-solid {{ $sev === 'danger' ? 'fa-circle-exclamation' : 'fa-triangle-exclamation' }}"></i> {{ $msg }}</span>@endforeach
</div>
@endif

{{-- ── Monitor tiles (latest observation) ── --}}
@php
    $t = fn ($cond) => $cond ? ' alarm' : '';
@endphp
<div class="mon">
    <div class="mon-tile hr{{ $t($last && $last->pulse !== null && ($last->pulse > 130 || $last->pulse < 40)) }}"><div class="lbl">HR</div><div class="val">{{ $last->pulse ?? '—' }}</div><div class="sub">{{ $last->rhythm ?? 'bpm' }}</div></div>
    <div class="mon-tile bp{{ $t($last && $last->mean_ap !== null && $last->mean_ap < 65) }}"><div class="lbl">BP / MAP</div><div class="val" style="font-size:22px">{{ $last && $last->bp_systolic ? $last->bp_systolic.'/'.$last->bp_diastolic : '—' }}</div><div class="sub">MAP <b>{{ $last->mean_ap ?? '—' }}</b>{{ $last && $last->map ? ' (art line)' : '' }}</div></div>
    <div class="mon-tile spo2{{ $t($last && $last->spo2 !== null && $last->spo2 < 90) }}"><div class="lbl">SpO₂</div><div class="val">{{ $last->spo2 ?? '—' }}</div><div class="sub">%{{ $last && $last->spo2_scale2 ? ' · target 88–92' : '' }}</div></div>
    <div class="mon-tile rr{{ $t($last && $last->respiration_rate !== null && ($last->respiration_rate > 30 || $last->respiration_rate < 8)) }}"><div class="lbl">RR</div><div class="val">{{ $last->respiration_rate ?? '—' }}</div><div class="sub">/min</div></div>
    <div class="mon-tile temp"><div class="lbl">Temp</div><div class="val" style="font-size:24px">{{ $last->temperature ?? '—' }}</div><div class="sub">°F{{ $last && $last->temp_c ? ' · '.$last->temp_c.' °C' : '' }}</div></div>
    <div class="mon-tile gcs"><div class="lbl">GCS</div><div class="val">{{ $last && $last->gcs !== null ? $last->gcs.($last->intubated ? 'T' : '') : '—' }}</div><div class="sub">{{ $last && $last->gcs !== null ? 'E'.$last->gcs_e.' V'.($last->intubated ? 'T' : $last->gcs_v).' M'.$last->gcs_m : ($last->avpu ?? '') }}{{ $last && $last->rass !== null ? ' · RASS '.$last->rass : '' }}</div></div>
    <div class="mon-tile cvp"><div class="lbl">CVP</div><div class="val">{{ $last->cvp ?? '—' }}</div><div class="sub">mmHg</div></div>
    <div class="mon-tile glu{{ $t($last && $last->blood_glucose !== null && ($last->blood_glucose < 70 || $last->blood_glucose > 300)) }}"><div class="lbl">Glucose</div><div class="val" style="font-size:24px">{{ $last && $last->blood_glucose ? (float) $last->blood_glucose : '—' }}</div><div class="sub">mg/dl</div></div>
    <div class="mon-tile o2"><div class="lbl">O₂ / Vent</div>
        <div class="val">{{ $last && $last->o2_device ? IpdVitals::O2_DEVICES[$last->o2_device] : '—' }}</div>
        <div class="sub">
            @if($last && $last->fio2) FiO₂ {{ $last->fio2 }}% @endif
            @if($last && $last->o2_flow) · {{ (float) $last->o2_flow }} L/min @endif
            @if($last && $last->vent_mode) <br>{{ $last->vent_mode }} @endif
            @if($last && $last->tidal_volume) · Vt {{ $last->tidal_volume }} @endif
            @if($last && $last->vent_rate) · f {{ $last->vent_rate }} @endif
            @if($last && $last->peep !== null) · PEEP {{ (float) $last->peep }} @endif
            @if($last && $last->pip) · PIP {{ $last->pip }} @endif
        </div>
    </div>
</div>
@if($last)<div class="text-small text-muted" style="margin:-8px 0 12px">Last observation {{ $last->recorded_at->format('d M H:i') }} ({{ $last->recorded_at->diffForHumans() }}) by {{ $last->recordedBy->name ?? '—' }}</div>@endif

<div class="icu-cols">
<div style="min-width:0">

    {{-- ── Running infusions ── --}}
    <div class="card mb-4" id="infusions">
        <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
            <span><i class="fa-solid fa-syringe"></i> Running infusions <span class="badge badge-navy">{{ $running->count() }}</span></span>
            @if($canWrite && $admission->status === 'admitted')
            <button class="btn btn-sm btn-primary no-print" onclick="openStart()"><i class="fa-solid fa-plus"></i> Start infusion</button>
            @endif
        </div>
        <div class="table-wrap">
            <table class="sc-table inf-table">
                <thead><tr><th>Drug</th><th>Dose</th><th>Rate</th><th>Syringe</th><th>Since</th><th class="no-print"></th></tr></thead>
                <tbody>
                @forelse($running as $inf)
                    @php $sy = $inf->syringe; $d = $inf->drug; $over = $d && $inf->dose > $d['max']; @endphp
                    <tr>
                        <td><div class="fw-700">{{ $inf->drug_name }}</div>
                            <div class="text-small text-muted">{{ IcuInfusion::fmt($inf->amount) }} {{ $inf->amount_unit }} in {{ IcuInfusion::fmt($inf->volume_ml) }} ml {{ $inf->diluent }} = {{ $inf->concentration_label }}{{ $inf->weight_kg ? ' · '.$inf->weight_kg.' kg' : '' }}</div></td>
                        <td class="inf-dose" @if($over) style="color:#b91c1c" title="Above usual maximum {{ $d['max'] }}" @endif>{{ IcuInfusion::fmt($inf->dose) }} <span class="text-small text-muted" style="font-weight:400">{{ $inf->dose_unit }}</span>@if($over) <i class="fa-solid fa-triangle-exclamation"></i>@endif</td>
                        <td class="inf-rate">{{ IcuInfusion::fmt($inf->rate_ml_h) }} <span class="text-small text-muted" style="font-weight:400">ml/h</span></td>
                        <td class="text-small" style="{{ $sy['hours'] !== null && $sy['hours'] < 1 ? 'color:#b91c1c;font-weight:700' : '' }}">
                            {{ $sy['left'] }} ml left<br><span class="text-muted">{{ $sy['hours'] !== null ? '≈ '.$sy['hours'].' h' : 'paused' }}</span></td>
                        <td class="text-small">{{ $inf->started_at->format('d M H:i') }}<br><span class="text-muted">{{ $inf->startedBy->name ?? '' }}</span></td>
                        <td class="no-print" style="white-space:nowrap">
                            @if($canWrite)
                            <button class="btn btn-sm btn-outline" onclick="openChange({{ json_encode(['id' => $inf->id, 'name' => $inf->drug_name, 'unit' => $inf->dose_unit, 'conc' => $inf->concentration, 'weight' => $inf->weight_kg, 'dose' => $inf->dose, 'rate' => $inf->rate_ml_h, 'key' => $inf->drug_key]) }})"><i class="fa-solid fa-sliders"></i> Titrate</button>
                            <button class="btn btn-sm btn-outline" style="color:var(--sc-red);border-color:var(--sc-red)" onclick="openStop({{ $inf->id }}, {{ json_encode($inf->drug_name) }})"><i class="fa-solid fa-stop"></i></button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted" style="padding:24px">No infusions running.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ── NEWS2 ── --}}
    @if($news)
    <div class="news-card" style="background:{{ $lv['bg'] }};color:{{ $lv['color'] }}">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap">
            <div><span style="font-size:22px;font-weight:800">NEWS2 {{ $news['score'] }}</span>
                <span class="fw-700"> · {{ $lv['label'] }} risk</span>{{ $news['partial'] ? ' (partial — some parameters missing)' : '' }}</div>
            <div class="news-parts">@foreach($news['parts'] as $k => $s)<span>{{ $k }} <b>{{ $s }}</b></span>@endforeach</div>
        </div>
        <div style="font-size:13px;margin-top:4px">{{ $lv['action'] }}</div>
    </div>
    @endif

    {{-- ── Trend charts ── --}}
    <div class="card mb-4">
        <div class="card-header"><i class="fa-solid fa-chart-line"></i> Haemodynamics <span class="text-small text-muted">· HR, BP, MAP (target ≥ 65), CVP</span></div>
        <div class="card-body"><div class="chart-box"><canvas id="ch-haemo"></canvas></div></div>
    </div>
    <div class="card mb-4" id="drug-charts-card" style="{{ $infusions->count() ? '' : 'display:none' }}">
        <div class="card-header"><i class="fa-solid fa-syringe"></i> Infusion doses</div>
        <div class="card-body" id="drug-charts"></div>
    </div>
    <div class="grid grid-2 mb-4" style="gap:16px">
        <div class="card"><div class="card-header"><i class="fa-solid fa-lungs"></i> Respiratory <span class="text-small text-muted">· SpO₂, RR, FiO₂, PEEP</span></div>
            <div class="card-body"><div class="chart-box"><canvas id="ch-resp"></canvas></div></div></div>
        <div class="card"><div class="card-header"><i class="fa-solid fa-temperature-half"></i> Temperature &amp; glucose</div>
            <div class="card-body"><div class="chart-box"><canvas id="ch-temp"></canvas></div></div></div>
        <div class="card"><div class="card-header"><i class="fa-solid fa-brain"></i> GCS, RASS &amp; NEWS2</div>
            <div class="card-body"><div class="chart-box"><canvas id="ch-neuro"></canvas></div></div></div>
        <div class="card"><div class="card-header"><i class="fa-solid fa-droplet"></i> Fluid balance <span class="text-small text-muted">· cumulative charted I/O</span></div>
            <div class="card-body">
                <div style="display:flex;gap:14px;flex-wrap:wrap;font-size:13px;margin-bottom:6px">
                    <span>In <b>{{ number_format($balance['in']) }} ml</b> <span class="text-muted">(infusions {{ number_format($balance['infused']) }})</span></span>
                    <span>Out <b>{{ number_format($balance['out']) }} ml</b></span>
                    <span>Net <b style="color:{{ $balance['in'] - $balance['out'] >= 0 ? '#0369a1' : '#b45309' }}">{{ ($balance['in'] - $balance['out'] >= 0 ? '+' : '') . number_format($balance['in'] - $balance['out']) }} ml</b></span>
                    @if($balance['urine_ml_kg_h'] !== null)<span style="{{ $balance['urine_ml_kg_h'] < 0.5 ? 'color:#b91c1c;font-weight:700' : '' }}">Urine {{ $balance['urine_ml_kg_h'] }} ml/kg/h</span>@endif
                </div>
                <div class="chart-box sm"><canvas id="ch-fluid"></canvas></div>
            </div></div>
    </div>

    {{-- ── Flowsheet ── --}}
    <div class="card mb-4">
        <div class="card-header" style="display:flex;justify-content:space-between"><span><i class="fa-solid fa-table-cells"></i> Flowsheet</span>
            <span class="text-small text-muted">{{ $from->format('d M H:i') }} – {{ $to->format('d M H:i') }} · {{ $vitals->count() }} observations</span></div>
        @if($vitals->isEmpty())
            <div class="card-body text-center text-muted" style="padding:30px">No observations in this period.</div>
        @else
        <div class="fs-wrap">
            <table class="fs">
                <thead><tr><th>Time</th>@foreach($vitals as $v)<th>{{ $v->recorded_at->format('H:i') }}<div style="font-weight:400;font-size:10px;color:var(--sc-muted)">{{ $v->recorded_at->format('d M') }}</div></th>@endforeach</tr></thead>
                <tbody>
                @php
                    $rows = [
                        'Haemodynamics' => [
                            ['HR', 'pulse', fn ($v) => $v->pulse],
                            ['Rhythm', 'rhythm', fn ($v) => $v->rhythm],
                            ['BP', 'bp_systolic', fn ($v) => $v->bp_systolic ? $v->bp_systolic.'/'.$v->bp_diastolic : null],
                            ['MAP', 'mean_ap', fn ($v) => $v->mean_ap],
                            ['CVP', 'cvp', fn ($v) => $v->cvp],
                        ],
                        'Respiratory' => [
                            ['RR', 'respiration_rate', fn ($v) => $v->respiration_rate],
                            ['SpO₂', 'spo2', fn ($v) => $v->spo2],
                            ['O₂ device', '', fn ($v) => $v->o2_device ? IpdVitals::O2_DEVICES[$v->o2_device] : null],
                            ['FiO₂ %', '', fn ($v) => $v->fio2],
                            ['O₂ L/min', '', fn ($v) => $v->o2_flow !== null ? (float) $v->o2_flow : null],
                            ['Mode', '', fn ($v) => $v->vent_mode],
                            ['Vt / f', '', fn ($v) => $v->tidal_volume || $v->vent_rate ? ($v->tidal_volume ?? '—').' / '.($v->vent_rate ?? '—') : null],
                            ['PEEP / PIP', '', fn ($v) => $v->peep !== null || $v->pip ? ($v->peep !== null ? (float) $v->peep : '—').' / '.($v->pip ?? '—') : null],
                        ],
                        'Neuro' => [
                            ['GCS', '', fn ($v) => $v->gcs !== null ? $v->gcs.($v->intubated ? 'T' : '') . ' (E'.$v->gcs_e.'V'.($v->intubated ? 'T' : $v->gcs_v).'M'.$v->gcs_m.')' : null],
                            ['AVPU', '', fn ($v) => $v->avpu],
                            ['Pupils', '', fn ($v) => $v->pupils],
                            ['RASS', '', fn ($v) => $v->rass],
                            ['Pain', '', fn ($v) => $v->pain_score],
                        ],
                        'Other' => [
                            ['Temp °F', 'temperature', fn ($v) => $v->temperature !== null ? (float) $v->temperature : null],
                            ['Glucose', 'blood_glucose', fn ($v) => $v->blood_glucose !== null ? (float) $v->blood_glucose : null],
                            ['Urine', '', fn ($v) => $v->urine_output],
                            ['Weight', '', fn ($v) => $v->weight !== null ? (float) $v->weight : null],
                            ['NEWS2', 'news2', fn ($v) => $v->news2 !== null ? $v->news2.($v->news2_partial ? '*' : '') : null],
                            ['By', '', fn ($v) => $v->recordedBy ? \Illuminate\Support\Str::of($v->recordedBy->name)->explode(' ')->first() : null],
                        ],
                    ];
                @endphp
                @foreach($rows as $grp => $list)
                    @php $any = collect($list)->contains(fn ($r) => $vitals->contains(fn ($v) => ($r[2])($v) !== null && ($r[2])($v) !== '')); @endphp
                    @if($any)
                    <tr class="grp"><td colspan="{{ $vitals->count() + 1 }}">{{ $grp }}</td></tr>
                    @foreach($list as [$label, $field, $fn])
                        @if($vitals->contains(fn ($v) => $fn($v) !== null && $fn($v) !== ''))
                        <tr><td>{{ $label }}</td>@foreach($vitals as $v)@php $val = $fn($v); @endphp<td class="{{ $field ? $bad($field, $field === 'mean_ap' ? $v->mean_ap : $v->{$field}) : '' }}">{{ $val ?? '' }}</td>@endforeach</tr>
                        @endif
                    @endforeach
                    @endif
                @endforeach
                @if($vitals->contains(fn ($v) => $v->notes))
                    <tr><td>Notes</td>@foreach($vitals as $v)<td style="white-space:normal;min-width:90px;text-align:left;font-size:11px">{{ $v->notes }}</td>@endforeach</tr>
                @endif
                </tbody>
            </table>
        </div>
        <div class="card-body text-small text-muted" style="padding:8px 16px">Yellow = outside normal, red = critical. * NEWS2 partial (a parameter was missing). Edit or delete an entry from the admission's Vitals tab.</div>
        @endif
    </div>

    {{-- ── Infusion log ── --}}
    @if($infusions->count())
    <div class="card mb-4">
        <div class="card-header"><i class="fa-solid fa-clock-rotate-left"></i> Infusion log</div>
        <div class="table-wrap">
            <table class="sc-table">
                <thead><tr><th>Time</th><th>Drug</th><th>Action</th><th>Dose</th><th>ml/h</th><th>Note</th><th>By</th><th class="no-print"></th></tr></thead>
                <tbody>
                @php $logRows = $infusions->flatMap(fn ($i) => $i->logs->map(fn ($l) => [$i, $l]))->sortByDesc(fn ($r) => $r[1]->logged_at); @endphp
                @foreach($logRows as [$inf, $log])
                    <tr>
                        <td class="text-small" style="white-space:nowrap">{{ $log->logged_at->format('d M H:i') }}</td>
                        <td class="fw-600">{{ $inf->drug_name }}</td>
                        <td><span class="badge {{ ['start' => 'badge-green', 'stop' => 'badge-red', 'change' => 'badge-teal', 'new_syringe' => 'badge-navy'][$log->action] ?? 'badge-gray' }}">{{ \App\Models\IcuInfusionLog::ACTIONS[$log->action] ?? $log->action }}</span></td>
                        <td>{{ $log->action === 'stop' ? '—' : IcuInfusion::fmt($log->dose).' '.$inf->dose_unit }}</td>
                        <td>{{ $log->action === 'stop' ? '—' : IcuInfusion::fmt($log->rate_ml_h) }}</td>
                        <td class="text-small">{{ $log->note }}</td>
                        <td class="text-small text-muted">{{ $log->user->name ?? '' }}</td>
                        <td class="no-print">
                            @if($log->action === 'start' && ($isOwnerOrAdmin || $inf->started_by === auth()->id()))
                            <form method="POST" action="{{ route('icu.infusions.destroy', [$admission, $inf]) }}" onsubmit="return confirm('Delete this whole {{ $inf->drug_name }} infusion entry (entered by mistake)?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline" title="Delete entry made by mistake" style="color:var(--sc-red);border-color:var(--sc-red)"><i class="fa-solid fa-trash"></i></button>
                            </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>

{{-- ── Record observations ── --}}
<div class="no-print" style="position:sticky;top:80px">
    @if($canWrite)
    <div class="card">
        <div class="card-header"><i class="fa-solid fa-pen-to-square"></i> Record observations</div>
        <div class="card-body obs-form" style="max-height:calc(100vh - 140px);overflow:auto">
            <form method="POST" action="{{ route('icu.obs.store', $admission) }}">
                @csrf
                <input type="hidden" name="hours" value="{{ $hours }}">
                @if($errors->any())<div class="alert alert-danger" style="font-size:12px">{{ implode(' ', $errors->all()) }}</div>@endif
                <div class="form-group"><label class="form-label">Time</label>
                    <input type="datetime-local" name="recorded_at" class="form-control" value="{{ old('recorded_at', now()->format('Y-m-d\TH:i')) }}" required></div>

                <div class="obs-sec">Haemodynamics</div>
                <div class="g3">
                    <div class="form-group"><label class="form-label">HR</label><input type="number" name="pulse" class="form-control" value="{{ old('pulse') }}" min="0" max="300"></div>
                    <div class="form-group"><label class="form-label">SBP</label><input type="number" name="bp_systolic" class="form-control" value="{{ old('bp_systolic') }}" min="20" max="300" oninput="mapCalc()"></div>
                    <div class="form-group"><label class="form-label">DBP</label><input type="number" name="bp_diastolic" class="form-control" value="{{ old('bp_diastolic') }}" min="10" max="200" oninput="mapCalc()"></div>
                    <div class="form-group"><label class="form-label">MAP <span id="map-calc" class="text-muted"></span></label><input type="number" name="map" class="form-control" value="{{ old('map') }}" placeholder="art line"></div>
                    <div class="form-group"><label class="form-label">CVP</label><input type="number" name="cvp" class="form-control" value="{{ old('cvp') }}" min="-10" max="40"></div>
                    <div class="form-group"><label class="form-label">Rhythm</label><input type="text" name="rhythm" class="form-control" value="{{ old('rhythm', $last->rhythm ?? '') }}" list="rhythms" placeholder="SR"></div>
                </div>
                <datalist id="rhythms"><option>SR</option><option>ST</option><option>SB</option><option>AF</option><option>AF-RVR</option><option>A-flutter</option><option>SVT</option><option>VT</option><option>Paced</option><option>Junctional</option><option>CHB</option></datalist>

                <div class="obs-sec">Respiratory</div>
                <div class="g3">
                    <div class="form-group"><label class="form-label">RR</label><input type="number" name="respiration_rate" class="form-control" value="{{ old('respiration_rate') }}" min="0" max="80"></div>
                    <div class="form-group"><label class="form-label">SpO₂ %</label><input type="number" name="spo2" class="form-control" value="{{ old('spo2') }}" min="30" max="100"></div>
                    <div class="form-group"><label class="form-label">FiO₂ %</label><input type="number" name="fio2" class="form-control" value="{{ old('fio2', $last->fio2 ?? '') }}" min="21" max="100"></div>
                </div>
                <div class="g2">
                    <div class="form-group"><label class="form-label">O₂ device</label>
                        <select name="o2_device" class="form-control" onchange="ventShow()">
                            @foreach(IpdVitals::O2_DEVICES as $k => $l)<option value="{{ $k }}" @selected(old('o2_device', $last->o2_device ?? 'air') === $k)>{{ $l }}</option>@endforeach
                        </select></div>
                    <div class="form-group"><label class="form-label">O₂ flow L/min</label><input type="number" name="o2_flow" step="0.5" class="form-control" value="{{ old('o2_flow', isset($last->o2_flow) ? (float) $last->o2_flow : '') }}"></div>
                </div>
                <div id="vent-fields">
                    <div class="g2">
                        <div class="form-group"><label class="form-label">Mode</label>
                            <select name="vent_mode" class="form-control"><option value="">—</option>
                                @foreach(IpdVitals::VENT_MODES as $m)<option @selected(old('vent_mode', $last->vent_mode ?? '') === $m)>{{ $m }}</option>@endforeach
                            </select></div>
                        <div class="form-group"><label class="form-label">Vt (ml)</label><input type="number" name="tidal_volume" class="form-control" value="{{ old('tidal_volume', $last->tidal_volume ?? '') }}"></div>
                    </div>
                    <div class="g3">
                        <div class="form-group"><label class="form-label">Set rate</label><input type="number" name="vent_rate" class="form-control" value="{{ old('vent_rate', $last->vent_rate ?? '') }}"></div>
                        <div class="form-group"><label class="form-label">PEEP</label><input type="number" step="0.5" name="peep" class="form-control" value="{{ old('peep', isset($last->peep) ? (float) $last->peep : '') }}"></div>
                        <div class="form-group"><label class="form-label">PIP</label><input type="number" name="pip" class="form-control" value="{{ old('pip') }}"></div>
                    </div>
                </div>
                <label class="text-small" style="display:flex;gap:6px;align-items:center;cursor:pointer;margin-bottom:6px">
                    <input type="checkbox" name="spo2_scale2" value="1" @checked(old('spo2_scale2', $last->spo2_scale2 ?? false))> COPD / hypercapnic: SpO₂ target 88–92 % (NEWS2 scale 2)</label>

                <div class="obs-sec">Neuro</div>
                <div class="g3">
                    <div class="form-group"><label class="form-label">Eyes</label><select name="gcs_e" class="form-control"><option value="">—</option>@foreach([4=>'4 Spont',3=>'3 Voice',2=>'2 Pain',1=>'1 None'] as $k=>$l)<option value="{{ $k }}" @selected(old('gcs_e') == $k)>{{ $l }}</option>@endforeach</select></div>
                    <div class="form-group"><label class="form-label">Verbal</label><select name="gcs_v" class="form-control" id="gcs_v"><option value="">—</option>@foreach([5=>'5 Orient',4=>'4 Confused',3=>'3 Words',2=>'2 Sounds',1=>'1 None'] as $k=>$l)<option value="{{ $k }}" @selected(old('gcs_v') == $k)>{{ $l }}</option>@endforeach</select></div>
                    <div class="form-group"><label class="form-label">Motor</label><select name="gcs_m" class="form-control"><option value="">—</option>@foreach([6=>'6 Obeys',5=>'5 Localises',4=>'4 Withdraws',3=>'3 Flexion',2=>'2 Extension',1=>'1 None'] as $k=>$l)<option value="{{ $k }}" @selected(old('gcs_m') == $k)>{{ $l }}</option>@endforeach</select></div>
                </div>
                <label class="text-small" style="display:flex;gap:6px;align-items:center;cursor:pointer;margin-bottom:6px">
                    <input type="checkbox" name="intubated" value="1" id="intubated" onchange="document.getElementById('gcs_v').disabled=this.checked" @checked(old('intubated', $last->intubated ?? false))> Intubated / tracheostomy (verbal = T)</label>
                <div class="g2">
                    <div class="form-group"><label class="form-label">AVPU (NEWS2)</label>
                        <select name="avpu" class="form-control">@foreach(IpdVitals::AVPU as $k => $l)<option value="{{ $k }}" @selected(old('avpu', 'A') === $k)>{{ $k }} — {{ $l }}</option>@endforeach</select></div>
                    <div class="form-group"><label class="form-label">RASS</label>
                        <select name="rass" class="form-control"><option value="">—</option>@foreach(IpdVitals::RASS as $k => $l)<option value="{{ $k }}" @selected(old('rass') !== null && (int) old('rass') === $k)>{{ $l }}</option>@endforeach</select></div>
                    <div class="form-group"><label class="form-label">Pupils</label><input type="text" name="pupils" class="form-control" value="{{ old('pupils') }}" placeholder="3 mm R=L reactive"></div>
                    <div class="form-group"><label class="form-label">Pain (0–10)</label><input type="number" name="pain_score" class="form-control" value="{{ old('pain_score') }}" min="0" max="10"></div>
                </div>

                <div class="obs-sec">Other</div>
                <div class="g3">
                    <div class="form-group"><label class="form-label">Temp °F</label><input type="number" step="0.1" name="temperature" class="form-control" value="{{ old('temperature') }}" min="80" max="115"></div>
                    <div class="form-group"><label class="form-label">Glucose</label><input type="number" step="0.1" name="blood_glucose" class="form-control" value="{{ old('blood_glucose') }}"></div>
                    <div class="form-group"><label class="form-label">Weight kg</label><input type="number" step="0.1" name="weight" class="form-control" value="{{ old('weight') }}" placeholder="{{ $weight }}"></div>
                </div>
                <div class="form-group"><label class="form-label">Urine output</label><input type="text" name="urine_output" class="form-control" value="{{ old('urine_output') }}" placeholder="e.g. 60 ml/h (chart volumes in I/O)"></div>
                <div class="form-group"><label class="form-label">Notes</label><input type="text" name="notes" class="form-control" value="{{ old('notes') }}"></div>
                <button class="btn btn-primary" style="width:100%"><i class="fa-solid fa-floppy-disk"></i> Save observations</button>
                <div class="text-small text-muted" style="margin-top:6px">O₂, ventilator settings, rhythm and scale are carried forward from the last entry — check before saving.</div>
            </form>
        </div>
    </div>
    @endif
</div>
</div>

{{-- ── Start infusion modal ── --}}
@if($canWrite && $admission->status === 'admitted')
<div class="modal-bg" id="startModal">
    <div class="modal-box">
        <div class="modal-title"><span><i class="fa-solid fa-syringe"></i> Start infusion</span><button type="button" onclick="closeM('startModal')" style="background:none;border:none;font-size:20px;cursor:pointer">&times;</button></div>
        <form method="POST" action="{{ route('icu.infusions.start', $admission) }}" id="sForm">
            @csrf
            <input type="hidden" name="amount_unit"><input type="hidden" name="dose_unit">
            <div class="form-group"><label class="form-label">Drug</label>
                <select name="drug_key" class="form-control" id="s-drug">
                    @foreach(collect($drugs)->groupBy('group', true) as $group => $list)
                    <optgroup label="{{ $group }}">@foreach($list as $key => $d)<option value="{{ $key }}">{{ $d['name'] }}{{ $d['aka'] ? ' ('.$d['aka'].')' : '' }}</option>@endforeach</optgroup>
                    @endforeach
                </select></div>
            <div class="form-group"><label class="form-label">Dilution</label><select class="form-control" id="s-dil"></select></div>
            <div class="g3">
                <div class="form-group"><label class="form-label">Drug (<span id="s-au"></span>)</label><input type="number" step="any" name="amount" class="form-control" required></div>
                <div class="form-group"><label class="form-label">Volume ml</label><input type="number" step="any" name="volume_ml" class="form-control" required></div>
                <div class="form-group"><label class="form-label">Diluent</label><select name="diluent" class="form-control"><option>NS</option><option>D5</option><option>Undiluted</option><option>RL</option></select></div>
            </div>
            <div class="g3">
                <div class="form-group"><label class="form-label">Weight kg</label><input type="number" step="0.1" name="weight_kg" class="form-control" value="{{ $weight }}"></div>
                <div class="form-group"><label class="form-label">Dose (<span id="s-du"></span>)</label><input type="number" step="any" name="dose" class="form-control" id="s-dose"></div>
                <div class="form-group"><label class="form-label">= ml/h</label><input type="number" step="any" name="rate_ml_h" class="form-control" id="s-rate"></div>
            </div>
            <div id="s-info" class="text-small text-muted"></div>
            <div id="s-flag"></div>
            <div class="g2" style="margin-top:8px">
                <div class="form-group"><label class="form-label">Started at</label><input type="datetime-local" name="started_at" class="form-control" value="{{ now()->format('Y-m-d\TH:i') }}" required></div>
                <div class="form-group"><label class="form-label">Note</label><input type="text" name="notes" class="form-control" placeholder="line, indication…"></div>
            </div>
            <button class="btn btn-primary" style="width:100%"><i class="fa-solid fa-play"></i> Start</button>
        </form>
    </div>
</div>
@endif

{{-- ── Titrate modal ── --}}
<div class="modal-bg" id="changeModal">
    <div class="modal-box">
        <div class="modal-title"><span><i class="fa-solid fa-sliders"></i> <span id="c-title"></span></span><button type="button" onclick="closeM('changeModal')" style="background:none;border:none;font-size:20px;cursor:pointer">&times;</button></div>
        <form method="POST" id="cForm">
            @csrf
            <div class="g2">
                <div class="form-group"><label class="form-label">What</label>
                    <select name="action" class="form-control"><option value="change">Change rate / dose</option><option value="new_syringe">New syringe (same strength)</option></select></div>
                <div class="form-group"><label class="form-label">Time</label><input type="datetime-local" name="logged_at" class="form-control" required></div>
            </div>
            <div class="g2">
                <div class="form-group"><label class="form-label">New dose (<span id="c-du"></span>)</label><input type="number" step="any" name="dose" class="form-control" id="c-dose" style="font-size:18px;font-weight:700"></div>
                <div class="form-group"><label class="form-label">= ml/h</label><input type="number" step="any" name="rate_ml_h" class="form-control" id="c-rate" style="font-size:18px;font-weight:700"></div>
            </div>
            <div id="c-info" class="text-small text-muted"></div>
            <div id="c-flag"></div>
            <div class="form-group" style="margin-top:8px"><label class="form-label">Reason / note</label><input type="text" name="note" class="form-control" placeholder="e.g. MAP 58"></div>
            <button class="btn btn-primary" style="width:100%"><i class="fa-solid fa-check"></i> Save</button>
        </form>
    </div>
</div>

{{-- ── Stop modal ── --}}
<div class="modal-bg" id="stopModal">
    <div class="modal-box" style="width:420px">
        <div class="modal-title"><span><i class="fa-solid fa-stop"></i> Stop <span id="st-title"></span></span><button type="button" onclick="closeM('stopModal')" style="background:none;border:none;font-size:20px;cursor:pointer">&times;</button></div>
        <form method="POST" id="stForm">
            @csrf
            <div class="form-group"><label class="form-label">Stopped at</label><input type="datetime-local" name="stopped_at" class="form-control" required></div>
            <div class="form-group"><label class="form-label">Reason</label><input type="text" name="note" class="form-control" placeholder="e.g. weaned off"></div>
            <button class="btn btn-primary" style="width:100%;background:var(--sc-red);border-color:var(--sc-red)"><i class="fa-solid fa-stop"></i> Stop infusion</button>
        </form>
    </div>
</div>

@include('icu._calc-js')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
<script>
const S = {!! json_encode($series) !!};
const nowLocal = () => { const d = new Date(); d.setMinutes(d.getMinutes() - d.getTimezoneOffset()); return d.toISOString().slice(0, 16); };
function openM(id) { document.getElementById(id).style.display = 'flex'; }
function closeM(id) { document.getElementById(id).style.display = 'none'; }
document.querySelectorAll('.modal-bg').forEach(m => m.addEventListener('click', e => { if (e.target === m) m.style.display = 'none'; }));

function mapCalc() {
    const f = document.querySelector('.obs-form form'), s = +f.bp_systolic.value, d = +f.bp_diastolic.value;
    document.getElementById('map-calc').textContent = s && d ? '(calc ' + Math.round((s + 2 * d) / 3) + ')' : '';
}
function ventShow() {
    const f = document.querySelector('.obs-form form'); if (!f) return;
    document.getElementById('vent-fields').style.display = ['vent', 'niv', 't_piece'].includes(f.o2_device.value) ? '' : 'none';
}
ventShow();
if (document.getElementById('intubated')?.checked) document.getElementById('gcs_v').disabled = true;

function flagHtml(drug, d) {
    const b = ICU.band(drug, d); if (!b) return '';
    const t = { low: ['#e0f2fe', '#075985', 'Below usual range'], ok: ['#dcfce7', '#14532d', 'Within usual range ' + drug.range[0] + '–' + drug.range[1]],
                high: ['#fef3c7', '#92400e', 'Above usual range — check'], over: ['#fee2e2', '#991b1b', 'Above usual maximum ' + drug.max + ' — double-check'] }[b];
    return `<div class="calc-flag" style="background:${t[0]};color:${t[1]}">${t[2]}</div>`;
}

// ── start infusion
const sf = document.getElementById('sForm');
function openStart() { openM('startModal'); sDrug(); }
function sDrug() {
    const d = ICU.DRUGS[sf.drug_key.value];
    document.getElementById('s-dil').innerHTML = d.dilutions.map((x, i) => `<option value="${i}">${x[2]}</option>`).join('') + '<option value="custom">Custom</option>';
    sf.amount_unit.value = d.amount; sf.dose_unit.value = d.unit;
    document.getElementById('s-au').textContent = d.amount; document.getElementById('s-du').textContent = d.unit;
    sDil(); sf.dose.value = d.start; sCalc('dose');
}
function sDil() {
    const d = ICU.DRUGS[sf.drug_key.value], v = document.getElementById('s-dil').value;
    if (v !== 'custom') { sf.amount.value = d.dilutions[v][0]; sf.volume_ml.value = d.dilutions[v][1]; }
    sCalc('dose');
}
function sCalc(from) {
    const d = ICU.DRUGS[sf.drug_key.value], c = ICU.conc(+sf.amount.value, d.amount, +sf.volume_ml.value, d.unit), w = +sf.weight_kg.value || null;
    if (from === 'dose') { const r = ICU.rate(+sf.dose.value, c, d.unit, w); sf.rate_ml_h.value = isFinite(r) && sf.dose.value !== '' ? ICU.fmt(r) : ''; }
    else { const x = ICU.dose(+sf.rate_ml_h.value, c, d.unit, w); sf.dose.value = isFinite(x) && sf.rate_ml_h.value !== '' ? ICU.fmt(x) : ''; }
    document.getElementById('s-info').innerHTML = (isFinite(c) ? 'Concentration ' + ICU.fmt(c) + ' ' + ICU.parts(d.unit).num + '/ml. ' : '')
        + (ICU.parts(d.unit).perKg && !w ? '<b style="color:#b91c1c">Enter weight.</b> ' : '') + d.note;
    document.getElementById('s-flag').innerHTML = flagHtml(d, +sf.dose.value);
}
if (sf) {
    sf.drug_key.addEventListener('change', sDrug);
    document.getElementById('s-dil').addEventListener('change', sDil);
    ['amount', 'volume_ml', 'weight_kg'].forEach(n => sf[n].addEventListener('input', () => sCalc('dose')));
    sf.dose.addEventListener('input', () => sCalc('dose'));
    sf.rate_ml_h.addEventListener('input', () => sCalc('rate'));
    // the server works out the other value from whichever was typed last
    sf.addEventListener('submit', () => { if (sf.dataset.last === 'rate') sf.dose.value = ''; else sf.rate_ml_h.value = ''; });
    sf.rate_ml_h.addEventListener('input', () => sf.dataset.last = 'rate');
    sf.dose.addEventListener('input', () => sf.dataset.last = 'dose');
}

// ── titrate
let cur = null;
const cf = document.getElementById('cForm');
function openChange(inf) {
    cur = inf;
    cf.action = '{{ route('icu.infusions.change', [$admission, '__ID__']) }}'.replace('__ID__', inf.id);
    document.getElementById('c-title').textContent = inf.name;
    document.getElementById('c-du').textContent = inf.unit;
    cf.dose.value = ICU.fmt(inf.dose); cf.rate_ml_h.value = ICU.fmt(inf.rate); cf.logged_at.value = nowLocal(); cf.dataset.last = 'dose';
    cCalc('dose'); openM('changeModal'); cf.dose.focus(); cf.dose.select();
}
function cCalc(from) {
    if (from === 'dose') { const r = ICU.rate(+cf.dose.value, cur.conc, cur.unit, cur.weight); cf.rate_ml_h.value = isFinite(r) && cf.dose.value !== '' ? ICU.fmt(r) : ''; }
    else { const x = ICU.dose(+cf.rate_ml_h.value, cur.conc, cur.unit, cur.weight); cf.dose.value = isFinite(x) && cf.rate_ml_h.value !== '' ? ICU.fmt(x) : ''; }
    document.getElementById('c-info').textContent = 'Was ' + ICU.fmt(cur.dose) + ' ' + cur.unit + ' = ' + ICU.fmt(cur.rate) + ' ml/h · ' + ICU.fmt(cur.conc) + ' ' + ICU.parts(cur.unit).num + '/ml' + (cur.weight ? ' · ' + cur.weight + ' kg' : '');
    document.getElementById('c-flag').innerHTML = cur.key ? flagHtml(ICU.DRUGS[cur.key], +cf.dose.value) : '';
}
cf.dose.addEventListener('input', () => { cf.dataset.last = 'dose'; cCalc('dose'); });
cf.rate_ml_h.addEventListener('input', () => { cf.dataset.last = 'rate'; cCalc('rate'); });
cf.addEventListener('submit', () => { if (cf.dataset.last === 'rate') cf.dose.value = ''; else cf.rate_ml_h.value = ''; });

// ── stop
function openStop(id, name) {
    const f = document.getElementById('stForm');
    f.action = '{{ route('icu.infusions.stop', [$admission, '__ID__']) }}'.replace('__ID__', id);
    document.getElementById('st-title').textContent = name; f.stopped_at.value = nowLocal(); openM('stopModal');
}

// ── charts (linear time axis, no date adapter needed)
const multiDay = S.to - S.from > 26 * 3600e3;
const tfmt = ms => { const d = new Date(ms), p = n => String(n).padStart(2, '0');
    return (multiDay ? p(d.getDate()) + '/' + p(d.getMonth() + 1) + ' ' : '') + p(d.getHours()) + ':' + p(d.getMinutes()); };
const xAxis = { type: 'linear', min: S.from, max: S.to, ticks: { callback: v => tfmt(v), maxTicksLimit: 10, maxRotation: 0 }, grid: { color: 'rgba(0,0,0,.04)' } };
const ds = (label, data, color, extra = {}) => ({ label, data, borderColor: color, backgroundColor: color, pointRadius: 2.5, borderWidth: 2, tension: .2, spanGaps: true, ...extra });
const opts = (y, extra = {}) => ({ responsive: true, maintainAspectRatio: false, animation: false, parsing: false,
    interaction: { mode: 'nearest', intersect: false },
    plugins: { legend: { labels: { boxWidth: 10, font: { size: 11 } } }, tooltip: { callbacks: { title: i => i.length ? tfmt(i[0].parsed.x) : '' } } },
    scales: { x: xAxis, ...y }, ...extra });

if (window.Chart) {
    new Chart(document.getElementById('ch-haemo'), { type: 'line', data: { datasets: [
        ds('HR', S.hr, '#16a34a'),
        ds('SBP', S.sbp, '#dc2626', { pointStyle: 'triangle', borderWidth: 1.5 }),
        ds('DBP', S.dbp, '#f87171', { pointStyle: 'triangle', rotation: 180, borderWidth: 1.5 }),
        ds('MAP', S.map, '#7f1d1d', { borderWidth: 3 }),
        ds('CVP', S.cvp, '#db2777', { yAxisID: 'y2', borderDash: [4, 3] }),
        { label: 'MAP 65', data: [{ x: S.from, y: 65 }, { x: S.to, y: 65 }], borderColor: 'rgba(127,29,29,.35)', borderDash: [6, 4], borderWidth: 1, pointRadius: 0 },
    ] }, options: opts({ y: { suggestedMin: 40, suggestedMax: 160 }, y2: { position: 'right', suggestedMin: 0, suggestedMax: 20, grid: { display: false }, title: { display: true, text: 'CVP' } } }) });

    new Chart(document.getElementById('ch-resp'), { type: 'line', data: { datasets: [
        ds('SpO₂', S.spo2, '#0284c7'), ds('FiO₂', S.fio2, '#7dd3fc', { borderDash: [4, 3] }),
        ds('RR', S.rr, '#ca8a04', { yAxisID: 'y2' }), ds('PEEP', S.peep, '#9333ea', { yAxisID: 'y2', stepped: true }),
    ] }, options: opts({ y: { suggestedMin: 80, max: 100, title: { display: true, text: '%' } }, y2: { position: 'right', suggestedMin: 0, suggestedMax: 35, grid: { display: false } } }) });

    new Chart(document.getElementById('ch-temp'), { type: 'line', data: { datasets: [
        ds('Temp °F', S.temp, '#ea580c'), ds('Glucose', S.glucose, '#65a30d', { yAxisID: 'y2' }),
    ] }, options: opts({ y: { suggestedMin: 96, suggestedMax: 103 }, y2: { position: 'right', suggestedMin: 60, suggestedMax: 300, grid: { display: false } } }) });

    new Chart(document.getElementById('ch-neuro'), { type: 'line', data: { datasets: [
        ds('GCS', S.gcs, '#9333ea', { stepped: true }), ds('NEWS2', S.news2, '#dc2626', { stepped: true }),
        ds('RASS', S.rass, '#64748b', { yAxisID: 'y2', stepped: true, borderDash: [4, 3] }),
    ] }, options: opts({ y: { min: 0, max: 20 }, y2: { position: 'right', min: -5, max: 4, grid: { display: false }, title: { display: true, text: 'RASS' } } }) });

    new Chart(document.getElementById('ch-fluid'), { type: 'line', data: { datasets: [
        ds('Net ml', S.balance, '#0369a1', { stepped: true, fill: { target: 'origin', above: 'rgba(3,105,161,.12)', below: 'rgba(180,83,9,.15)' } }),
    ] }, options: opts({ y: {} }, { plugins: { legend: { display: false } } }) });

    // one small step-chart per infusion drug (units differ)
    const palette = ['#dc2626', '#2563eb', '#16a34a', '#9333ea', '#ea580c', '#0891b2', '#be123c', '#4d7c0f'];
    const box = document.getElementById('drug-charts');
    S.drugs.forEach((d, i) => {
        const wrap = document.createElement('div'); wrap.className = 'chart-box sm'; wrap.style.marginBottom = '6px';
        const c = document.createElement('canvas'); wrap.appendChild(c); box.appendChild(wrap);
        new Chart(c, { type: 'line', data: { datasets: [ds(d.label, d.data, palette[i % palette.length], { stepped: 'before', fill: { target: 'origin' }, backgroundColor: palette[i % palette.length] + '22', spanGaps: false })] },
            options: opts({ y: { beginAtZero: true } }) });
    });
}
@if($errors->hasAny(['weight_kg','amount_unit','drug_name','dose','rate_ml_h','amount','volume_ml']) && old('drug_key') !== null)
sf.drug_key.value = {!! json_encode(old('drug_key')) !!}; openStart();
@endif
</script>
@endsection
