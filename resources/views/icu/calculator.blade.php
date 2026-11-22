{{-- FILE: resources/views/icu/calculator.blade.php — infusion calculator, emergency doses, wall chart --}}
@extends('layouts.app')
@section('title','ICU Drug Calculator')
@section('page-title','ICU Drug Calculator')

@push('styles')
<style>
    .calc-grid { display:grid; grid-template-columns: minmax(320px, 1.1fr) 1fr; gap:20px; align-items:start; }
    @media (max-width: 900px) { .calc-grid { grid-template-columns:1fr; } }
    .calc-big { display:grid; grid-template-columns:1fr auto 1fr; gap:10px; align-items:end; }
    .calc-big input { font-size:22px; font-weight:700; height:52px; text-align:center; }
    .calc-swap { font-size:20px; color:var(--sc-muted); padding-bottom:14px; }
    .calc-conc { font-size:13px; color:var(--sc-muted); margin-top:6px; }
    .range-bar { position:relative; height:12px; border-radius:6px; margin:18px 0 6px;
                 background:linear-gradient(90deg,#e0f2fe 0%,#dcfce7 15%,#dcfce7 70%,#fef3c7 70%,#fef3c7 85%,#fee2e2 85%); }
    .range-pin { position:absolute; top:-6px; width:4px; height:24px; background:var(--sc-navy); border-radius:2px; transform:translateX(-2px); transition:left .2s; }
    .range-lbl { display:flex; justify-content:space-between; font-size:11px; color:var(--sc-muted); }
    .dose-flag { margin-top:10px; padding:8px 12px; border-radius:6px; font-size:13px; display:none; }
    .dose-flag.low  { display:block; background:#e0f2fe; color:#075985; }
    .dose-flag.ok   { display:block; background:#dcfce7; color:#14532d; }
    .dose-flag.high { display:block; background:#fef3c7; color:#92400e; }
    .dose-flag.over { display:block; background:#fee2e2; color:#991b1b; font-weight:600; }
    .titr td, .titr th { padding:5px 10px !important; font-size:13px; }
    .titr tr.cur td { background:var(--sc-teal-glow); font-weight:700; }
    .titr tr.over td { color:#b91c1c; }
    .em-table td { vertical-align:top; }
    .em-dose { font-weight:700; white-space:nowrap; }
    .em-ml { font-weight:700; color:var(--sc-teal); white-space:nowrap; }
    .wall td, .wall th { text-align:center; padding:4px 6px !important; font-size:12px; }
    .wall td.dose { text-align:left; font-weight:600; }
    .disclaimer { font-size:12px; color:var(--sc-muted); border-left:3px solid var(--sc-amber); padding:6px 10px; background:rgba(245,166,35,.06); margin-bottom:16px; }
    @media print {
        .sidebar, .topbar, .page-header .section-toolbar, .tab-nav, .no-print { display:none !important; }
        .tab-pane { display:none !important; } .tab-pane.print-me { display:block !important; }
        .main-content, .content { margin:0 !important; padding:0 !important; }
        .card { box-shadow:none !important; border:1px solid #ccc; }
    }
</style>
@endpush

@section('content')
<div class="page-header">
    <div class="page-header-left" style="min-width:320px">
        <h1><i class="fa-solid fa-calculator"></i> ICU Drug Calculator</h1>
        <div class="breadcrumb">Infusion dose ↔ ml/h, emergency doses by weight, printable infusion wall charts</div>
    </div>
    @include('ipd._toolbar', ['inline'=>true])
</div>

<div class="disclaimer">
    <i class="fa-solid fa-triangle-exclamation" style="color:var(--sc-amber)"></i>
    Usual adult critical-care doses for quick reference. Always check the ampoule strength, the patient (renal / hepatic function, age, pregnancy)
    and your hospital protocol. A second person should double-check high-alert infusions.
</div>

<div class="card mb-4">
    <div class="card-body" style="display:flex;gap:16px;flex-wrap:wrap;align-items:flex-end;padding:14px 20px">
        <div>
            <label class="form-label" style="margin-bottom:4px">Patient weight (kg)</label>
            <input type="number" id="wt" class="form-control" step="0.1" min="0.5" max="300" value="{{ $weight }}" placeholder="e.g. 60" style="width:140px;font-size:18px;font-weight:700">
        </div>
        @if($admission)
        <div style="padding-bottom:6px">
            <div class="fw-700">{{ $admission->patient->first_name }} {{ $admission->patient->last_name }}</div>
            <div class="text-small text-muted">{{ $admission->ipd_no }} · {{ $admission->ward->name ?? '' }} {{ $admission->bed ? 'Bed '.$admission->bed->bed_number : '' }}
                · <a href="{{ route('icu.chart', $admission) }}">ICU chart</a></div>
        </div>
        @endif
        <div class="text-small text-muted" style="padding-bottom:8px" id="wt-hint">@if(!$weight) Enter weight for per-kg drugs. @endif</div>
    </div>
</div>

<div class="tab-nav" id="calcTabs">
    <button class="tab-btn active" data-tab="infusion"><i class="fa-solid fa-syringe"></i> Infusion calculator</button>
    <button class="tab-btn" data-tab="emergency"><i class="fa-solid fa-truck-medical"></i> Emergency doses</button>
    <button class="tab-btn" data-tab="wall"><i class="fa-solid fa-table"></i> Infusion wall chart</button>
</div>

{{-- ── Infusion calculator ── --}}
<div class="tab-pane active" id="tab-infusion">
    <div class="calc-grid">
        <div class="card">
            <div class="card-header"><i class="fa-solid fa-syringe"></i> Infusion</div>
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label">Drug</label>
                    <select id="drug" class="form-control" style="font-size:15px">
                        @foreach(collect($drugs)->groupBy('group', true) as $group => $list)
                        <optgroup label="{{ $group }}">
                            @foreach($list as $key => $d)<option value="{{ $key }}">{{ $d['name'] }}{{ $d['aka'] ? ' ('.$d['aka'].')' : '' }}</option>@endforeach
                        </optgroup>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Dilution</label>
                    <select id="dil" class="form-control"></select>
                    <div class="form-row cols-2" id="custom-dil" style="display:none;margin-top:8px">
                        <div><label class="form-label">Drug amount (<span class="amt-unit"></span>)</label><input type="number" id="c-amt" class="form-control" step="any" min="0"></div>
                        <div><label class="form-label">Total volume (ml)</label><input type="number" id="c-vol" class="form-control" step="any" min="0"></div>
                    </div>
                    <div class="calc-conc" id="conc"></div>
                </div>

                <div class="calc-big">
                    <div>
                        <label class="form-label">Dose (<span class="dose-unit"></span>)</label>
                        <input type="number" id="dose" class="form-control" step="any" min="0">
                    </div>
                    <div class="calc-swap"><i class="fa-solid fa-arrows-left-right"></i></div>
                    <div>
                        <label class="form-label">Rate (ml/h)</label>
                        <input type="number" id="rate" class="form-control" step="any" min="0">
                    </div>
                </div>
                <div class="range-bar"><div class="range-pin" id="pin" style="left:0"></div></div>
                <div class="range-lbl"><span id="r-min"></span><span id="r-usual"></span><span id="r-max"></span></div>
                <div class="dose-flag" id="flag"></div>
                <div class="text-small" id="other-units" style="margin-top:8px;color:var(--sc-muted)"></div>
                <div style="margin-top:12px;font-size:13px" id="note"></div>
                <div id="protocol" style="margin-top:10px"></div>

                @if($admission && $admission->status === 'admitted')
                <form method="POST" action="{{ route('icu.infusions.start', $admission) }}" id="startForm" class="no-print" style="margin-top:16px;border-top:1px solid var(--sc-border);padding-top:14px">
                    @csrf
                    <input type="hidden" name="drug_key"><input type="hidden" name="amount"><input type="hidden" name="amount_unit">
                    <input type="hidden" name="volume_ml"><input type="hidden" name="dose_unit"><input type="hidden" name="weight_kg"><input type="hidden" name="dose">
                    <div class="form-row cols-2">
                        <div><label class="form-label">Started at</label><input type="datetime-local" name="started_at" class="form-control" value="{{ now()->format('Y-m-d\TH:i') }}" required></div>
                        <div><label class="form-label">Diluent</label>
                            <select name="diluent" class="form-control"><option>NS</option><option>D5</option><option>Undiluted</option><option>RL</option></select></div>
                    </div>
                    <input type="text" name="notes" class="form-control" placeholder="Note (line, indication…)" style="margin-top:8px">
                    <button type="submit" class="btn btn-primary" style="width:100%;margin-top:10px"><i class="fa-solid fa-play"></i> Start this infusion for {{ $admission->patient->first_name }}</button>
                </form>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
                <span><i class="fa-solid fa-stairs"></i> Titration table</span>
                <span class="text-small text-muted" id="titr-sub"></span>
            </div>
            <div class="table-wrap" style="max-height:620px;overflow:auto">
                <table class="sc-table titr"><thead><tr><th>Dose</th><th>ml/h</th><th>per 24 h</th></tr></thead><tbody id="titr"></tbody></table>
            </div>
        </div>
    </div>
</div>

{{-- ── Emergency doses ── --}}
<div class="tab-pane" id="tab-emergency">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px" class="no-print">
        <div class="text-muted text-small">Doses and ml are worked out for the weight above. Paediatric doses are shown for reference.</div>
        <button class="btn btn-sm btn-outline" onclick="printTab('emergency')"><i class="fa-solid fa-print"></i> Print sheet</button>
    </div>
    @foreach($bolus as $group => $list)
    <div class="card mb-4">
        <div class="card-header"><i class="fa-solid fa-bolt"></i> {{ $group }}</div>
        <div class="table-wrap">
            <table class="sc-table em-table">
                <thead><tr><th style="width:22%">Drug</th><th>Dose</th><th>Volume</th><th>How</th><th style="width:20%">Paediatric</th></tr></thead>
                <tbody>
                @foreach($list as $b)
                <tr class="em-row" data-dose="{{ json_encode($b) }}">
                    <td class="fw-600">{{ $b['name'] }}<div class="text-small text-muted">{{ $b['strength'] ? rtrim(rtrim(number_format($b['strength'], 3), '0'), '.') . ' ' . $b['unit'] . '/ml' : '' }}</div></td>
                    <td class="em-dose"></td>
                    <td class="em-ml"></td>
                    <td class="text-small">{{ $b['how'] }}</td>
                    <td class="text-small text-muted">{{ $b['paeds'] }}</td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endforeach
    <div class="card mb-4">
        <div class="card-header"><i class="fa-solid fa-heart-circle-bolt"></i> Defibrillation / cardioversion</div>
        <div class="table-wrap">
            <table class="sc-table"><thead><tr><th>Rhythm</th><th>Adult</th><th>Paediatric</th><th>For this weight (paeds)</th></tr></thead>
            <tbody>
            @foreach($electrical as $e)
                <tr><td class="fw-600">{{ $e[0] }}</td><td>{{ $e[1] }}</td><td class="text-muted">{{ $e[2] }}</td><td class="joules" data-rule="{{ $e[2] }}"></td></tr>
            @endforeach
            </tbody></table>
        </div>
    </div>
</div>

{{-- ── Wall chart ── --}}
<div class="tab-pane" id="tab-wall">
    <div class="card mb-4 no-print">
        <div class="card-body" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;padding:14px 20px">
            <div><label class="form-label">Drug</label>
                <select id="w-drug" class="form-control" style="min-width:220px">
                    @foreach($drugs as $key => $d)<option value="{{ $key }}">{{ $d['name'] }}</option>@endforeach
                </select></div>
            <div><label class="form-label">Dilution</label><select id="w-dil" class="form-control" style="min-width:220px"></select></div>
            <button class="btn btn-outline" onclick="printTab('wall')"><i class="fa-solid fa-print"></i> Print</button>
        </div>
    </div>
    <div class="card">
        <div class="card-header" id="w-title"></div>
        <div class="table-wrap"><table class="sc-table wall" id="w-table"></table></div>
        <div class="card-body text-small text-muted" id="w-note"></div>
    </div>
</div>

@include('icu._calc-js')
<script>
(function () {
    const D = ICU.DRUGS, $ = id => document.getElementById(id);
    const wt = () => parseFloat($('wt').value) || null;
    let drug, conc = NaN, lastEdited = 'dose';

    // tabs
    document.querySelectorAll('#calcTabs .tab-btn').forEach(b => b.addEventListener('click', () => {
        document.querySelectorAll('#calcTabs .tab-btn').forEach(x => x.classList.toggle('active', x === b));
        document.querySelectorAll('.tab-pane').forEach(p => p.classList.toggle('active', p.id === 'tab-' + b.dataset.tab));
        try { localStorage.setItem('icuCalcTab', b.dataset.tab); } catch (e) {}
    }));
    try { const t = localStorage.getItem('icuCalcTab'); if (t) document.querySelector('#calcTabs .tab-btn[data-tab="' + t + '"]')?.click(); } catch (e) {}
    window.printTab = function (t) {
        document.querySelectorAll('.tab-pane').forEach(p => p.classList.toggle('print-me', p.id === 'tab-' + t));
        window.print();
    };

    function fillDilutions(sel, d, withCustom) {
        sel.innerHTML = d.dilutions.map((x, i) => `<option value="${i}">${x[2]}</option>`).join('') + (withCustom ? '<option value="custom">Custom…</option>' : '');
    }
    function currentDil() {
        if ($('dil').value === 'custom') return [parseFloat($('c-amt').value), parseFloat($('c-vol').value)];
        const x = drug.dilutions[+$('dil').value]; return [x[0], x[1]];
    }

    function selectDrug() {
        drug = D[$('drug').value];
        fillDilutions($('dil'), drug, true);
        document.querySelectorAll('.dose-unit').forEach(e => e.textContent = drug.unit);
        document.querySelectorAll('.amt-unit').forEach(e => e.textContent = drug.amount);
        $('note').innerHTML = '<i class="fa-solid fa-circle-info" style="color:var(--sc-teal)"></i> ' + drug.note;
        $('r-min').textContent = '0'; $('r-usual').textContent = 'usual ' + drug.range[0] + '–' + drug.range[1]; $('r-max').textContent = 'max ' + drug.max;
        $('dose').value = drug.start; lastEdited = 'dose';
        recalc();
    }

    function recalc() {
        const [amt, vol] = currentDil();
        $('custom-dil').style.display = $('dil').value === 'custom' ? '' : 'none';
        conc = ICU.conc(amt, drug.amount, vol, drug.unit);
        const num = ICU.parts(drug.unit).num;
        $('conc').textContent = isFinite(conc) ? 'Concentration: ' + ICU.fmt(conc) + ' ' + num + '/ml' : '';
        const needsWt = ICU.parts(drug.unit).perKg && !wt();
        $('wt-hint').textContent = needsWt ? 'Enter weight — ' + drug.name + ' is dosed per kg.' : '';
        $('wt').style.borderColor = needsWt ? 'var(--sc-red)' : '';

        let d;
        if (lastEdited === 'dose') { d = parseFloat($('dose').value); const r = ICU.rate(d, conc, drug.unit, wt()); $('rate').value = isFinite(r) ? ICU.fmt(r) : ''; }
        else { const r = parseFloat($('rate').value); d = ICU.dose(r, conc, drug.unit, wt()); $('dose').value = isFinite(d) ? ICU.fmt(d) : ''; }

        // range bar: 0 … max*1.2
        const scale = drug.max * 1.2;
        const bar = document.querySelector('.range-bar');
        const pct = v => Math.min(100, v / scale * 100);
        bar.style.background = `linear-gradient(90deg,#e0f2fe 0%,#e0f2fe ${pct(drug.range[0])}%,#dcfce7 ${pct(drug.range[0])}%,#dcfce7 ${pct(drug.range[1])}%,#fef3c7 ${pct(drug.range[1])}%,#fef3c7 ${pct(drug.max)}%,#fee2e2 ${pct(drug.max)}%)`;
        $('pin').style.left = (isFinite(d) ? pct(d) : 0) + '%';
        const b = ICU.band(drug, d), f = $('flag');
        f.className = 'dose-flag ' + (b || '');
        f.innerHTML = { low: 'Below the usual range.', ok: 'Within the usual range.', high: 'Above the usual range — check this is intended.',
                        over: '<i class="fa-solid fa-triangle-exclamation"></i> Above the usual maximum of ' + drug.max + ' ' + drug.unit + '. Double-check.' }[b] || '';

        // same dose in other units
        const ph = ICU.perHour(drug.unit, wt()), p = ICU.parts(drug.unit);
        let other = '';
        if (isFinite(d) && isFinite(ph)) {
            const perH = d * ph; // numerator per hour
            other = 'Total: ' + ICU.fmt(perH) + ' ' + p.num + '/h · ' + ICU.fmt(perH / 60) + ' ' + p.num + '/min · ' + ICU.fmt(perH * 24) + ' ' + p.num + '/24 h';
            const r = parseFloat($('rate').value);
            if (isFinite(r) && r > 0) { const [, vol] = currentDil(); other += ' · a ' + vol + ' ml syringe lasts ' + ICU.fmt(vol / r) + ' h'; }
        }
        $('other-units').textContent = other;

        // amiodarone-style protocol
        $('protocol').innerHTML = (drug.protocol || []).map(s => {
            const r = ICU.rate(s[1], conc, drug.unit, wt());
            return `<div style="padding:6px 10px;background:var(--sc-bg);border-radius:6px;margin-top:4px"><b>${s[0]}</b> = <b style="color:var(--sc-teal)">${ICU.fmt(r)} ml/h</b></div>`;
        }).join('');

        // titration table
        $('titr-sub').textContent = drug.name + ', ' + (isFinite(conc) ? ICU.fmt(conc) + ' ' + p.num + '/ml' : '') + (p.perKg ? ', ' + (wt() || '?') + ' kg' : '');
        $('titr').innerHTML = ICU.steps(drug).map(s => {
            const r = ICU.rate(s, conc, drug.unit, wt());
            const cls = (isFinite(d) && Math.abs(s - d) < 1e-6 ? 'cur' : '') + (s > drug.max ? ' over' : '');
            return `<tr class="${cls}"><td>${ICU.fmt(s)} ${drug.unit}</td><td class="fw-700">${ICU.fmt(r)}</td><td class="text-muted">${isFinite(ph) ? ICU.fmt(s * ph * 24) + ' ' + p.num : '—'}</td></tr>`;
        }).join('');

        // start form
        const f2 = $('startForm');
        if (f2) {
            f2.drug_key.value = $('drug').value; f2.amount.value = amt; f2.amount_unit.value = drug.amount; f2.volume_ml.value = vol;
            f2.dose_unit.value = drug.unit; f2.weight_kg.value = wt() || ''; f2.dose.value = isFinite(d) ? d : '';
            f2.querySelector('button').disabled = !(isFinite(d) && isFinite(conc));
        }
    }

    // emergency doses
    function emergency() {
        const w = wt();
        document.querySelectorAll('.em-row').forEach(tr => {
            const b = JSON.parse(tr.dataset.dose);
            let lo = b.dose[0], hi = b.dose[1];
            if (b.per_kg) {
                if (!w) { tr.querySelector('.em-dose').innerHTML = `${lo}${hi !== lo ? '–' + hi : ''} ${b.unit}/kg`; tr.querySelector('.em-ml').textContent = '—'; return; }
                lo *= w; hi *= w;
                if (b.max) { lo = Math.min(lo, b.max); hi = Math.min(hi, b.max); }
            }
            const txt = ICU.fmt(lo) + (Math.abs(hi - lo) > 1e-9 ? '–' + ICU.fmt(hi) : '') + ' ' + b.unit;
            tr.querySelector('.em-dose').innerHTML = txt + (b.per_kg ? `<div class="text-small text-muted" style="font-weight:400">${b.dose[0]}${b.dose[1] !== b.dose[0] ? '–' + b.dose[1] : ''} ${b.unit}/kg${b.max ? ', max ' + b.max : ''}</div>` : '');
            const ml = b.strength ? [lo / b.strength, hi / b.strength] : (b.unit === 'ml' ? [lo, hi] : null);
            tr.querySelector('.em-ml').textContent = ml ? ICU.fmt(ml[0]) + (Math.abs(ml[1] - ml[0]) > 1e-9 ? '–' + ICU.fmt(ml[1]) : '') + ' ml' : '—';
        });
        document.querySelectorAll('.joules').forEach(td => {
            const nums = [...td.dataset.rule.matchAll(/([\d.]+)(?:–([\d.]+))? J\/kg/g)].map(m => m[2] ? ICU.fmt(+m[1] * w) + '–' + ICU.fmt(+m[2] * w) : ICU.fmt(+m[1] * w));
            td.textContent = w ? nums.map(n => n + ' J').join(', then ') : '—';
        });
    }

    // wall chart: dose rows × weight columns (or just ml/h for non-weight drugs)
    function wall() {
        const d = D[$('w-drug').value];
        if ($('w-dil').dataset.drug !== $('w-drug').value) { fillDilutions($('w-dil'), d, false); $('w-dil').dataset.drug = $('w-drug').value; }
        const x = d.dilutions[+$('w-dil').value], c = ICU.conc(x[0], d.amount, x[1], d.unit), p = ICU.parts(d.unit);
        $('w-title').innerHTML = `<b>${d.name}</b> — ${x[2]} (${ICU.fmt(c)} ${p.num}/ml) · rates in <b>ml/h</b>`;
        const weights = p.perKg ? [40, 50, 60, 70, 80, 90, 100, 110, 120] : [null];
        if (p.perKg && wt() && !weights.includes(wt())) { weights.push(wt()); weights.sort((a, b) => a - b); }
        let h = '<thead><tr><th>Dose (' + d.unit + ')</th>' + weights.map(w => w ? `<th${w === wt() ? ' style="background:var(--sc-teal-glow)"' : ''}>${w} kg</th>` : '<th>ml/h</th>').join('') + '</tr></thead><tbody>';
        ICU.steps(d).forEach(s => {
            h += `<tr${s > d.max ? ' style="color:#b91c1c"' : ''}><td class="dose">${ICU.fmt(s)}</td>` + weights.map(w => `<td>${ICU.fmt(ICU.rate(s, c, d.unit, w || 1))}</td>`).join('') + '</tr>';
        });
        $('w-table').innerHTML = h + '</tbody>';
        $('w-note').innerHTML = d.note + ' Usual range ' + d.range[0] + '–' + d.range[1] + ' ' + d.unit + '; red rows are above the usual maximum.';
    }

    $('drug').addEventListener('change', selectDrug);
    $('dil').addEventListener('change', () => { if ($('dil').value === 'custom' && !$('c-vol').value) { $('c-amt').value = drug.dilutions[0][0]; $('c-vol').value = drug.dilutions[0][1]; } recalc(); });
    ['c-amt', 'c-vol'].forEach(id => $(id).addEventListener('input', recalc));
    $('dose').addEventListener('input', () => { lastEdited = 'dose'; recalc(); });
    $('rate').addEventListener('input', () => { lastEdited = 'rate'; recalc(); });
    $('wt').addEventListener('input', () => { recalc(); emergency(); wall(); });
    $('w-drug').addEventListener('change', wall);
    $('w-dil').addEventListener('change', wall);

    const pre = new URLSearchParams(location.search).get('drug');
    if (pre && D[pre]) { $('drug').value = pre; $('w-drug').value = pre; }
    selectDrug(); emergency(); wall();
})();
</script>
@endsection
