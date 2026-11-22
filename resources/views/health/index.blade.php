@extends('layouts.app')
@section('title', 'Health Tracker — ' . $patient->full_name)
@section('page-title', 'Health Tracker')
@section('patient-context')
@include('partials._patient_ctx', ['patient' => $patient, 'active' => 'health'])
@endsection
@section('content')

@php
    $metricsCfg = \App\Models\HealthMetric::METRICS;
    $meals = \App\Models\DietLog::MEALS;
    $fmt = fn ($v) => $v === null || $v === '' ? '' : rtrim(rtrim(number_format((float) $v, 2), '0'), '.');
@endphp

<style>
    .ht-cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:12px;margin-bottom:16px}
    .ht-card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:14px}
    .ht-card .k{font-size:11px;color:#64748b;text-transform:uppercase;letter-spacing:.04em}
    .ht-card .v{font-size:24px;font-weight:800;line-height:1.15;margin-top:4px}
    .ht-card .s{font-size:12px;font-weight:600;margin-top:2px}
    .gl-disclaimer{font-size:11.5px;color:#94a3b8;margin-top:10px;line-height:1.5}
    .ht-del{border:none;background:transparent;color:#cbd5e1;cursor:pointer;font-size:13px}
    .ht-del:hover{color:#dc2626}
    .ht-day{font-weight:700;background:#f8fafc}
</style>

<div class="page-header">
    <div class="page-header-left">
        <h1>Health Tracker</h1>
        <div class="breadcrumb"><a href="{{ route('patients.show', $patient) }}">{{ $patient->full_name }}</a> &rsaquo; Health Tracker</div>
    </div>
    <a href="{{ route('glucose.index', $patient) }}" class="btn btn-sm" style="background:#0891b2;color:#fff;border-color:#0891b2"><i class="fa-solid fa-droplet"></i> Diabetes Diary</a>
    <a href="{{ route('bp.index', $patient) }}"      class="btn btn-sm" style="background:#dc2626;color:#fff;border-color:#dc2626"><i class="fa-solid fa-heart-pulse"></i> BP Diary</a>
    <a href="{{ route('patients.show', $patient) }}" class="btn btn-outline btn-sm"><i class="fa-solid fa-arrow-left"></i> Back</a>
</div>

@if(session('success'))<div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> {{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger"><i class="fa-solid fa-circle-xmark"></i> {{ session('error') }}</div>@endif

{{-- Summary cards --}}
<div class="ht-cards">
    <div class="ht-card">
        <div class="k"><i class="fa-solid fa-weight-scale"></i> Weight</div>
        @if($weightTrend)
            <div class="v">{{ $fmt($weightTrend['latest']) }} <span style="font-size:13px;font-weight:600;color:#64748b">kg</span></div>
            @if($weightTrend['count'] > 1)
                @php $ch = $weightTrend['change']; @endphp
                <div class="s" style="color:{{ $ch < 0 ? '#16a34a' : ($ch > 0 ? '#dc2626' : '#64748b') }}">
                    {{ $ch < 0 ? '▼ '.abs($ch).' kg lost' : ($ch > 0 ? '▲ '.$ch.' kg gained' : 'no change') }}
                    <span style="color:#94a3b8;font-weight:400">over {{ $weightTrend['days'] ?: '0' }}d</span>
                </div>
            @endif
        @else <div class="v" style="color:#cbd5e1">—</div><div class="s text-muted">no readings</div>@endif
    </div>
    <div class="ht-card"><div class="k"><i class="fa-solid fa-utensils"></i> Today in</div><div class="v" style="color:#0ea5e9">{{ $todayIn }}</div><div class="s text-muted">kcal eaten</div></div>
    <div class="ht-card"><div class="k"><i class="fa-solid fa-fire"></i> Today out</div><div class="v" style="color:#ea580c">{{ $todayOut }}</div><div class="s text-muted">kcal burned</div></div>
    <div class="ht-card"><div class="k"><i class="fa-solid fa-scale-balanced"></i> Net today</div><div class="v">{{ $todayIn - $todayOut }}</div><div class="s text-muted">kcal (in − out)</div></div>
    @foreach(['heart_rate','spo2','stress'] as $m)
        @php $row = $vitalsLatest[$m]; $c = $row ? $row->classification() : null; @endphp
        <div class="ht-card">
            <div class="k"><i class="fa-solid {{ $metricsCfg[$m]['icon'] }}"></i> {{ $metricsCfg[$m]['label'] }}</div>
            @if($row)
                <div class="v" style="color:{{ $c['color'] }}">{{ $fmt($row->value) }} <span style="font-size:12px;font-weight:600;color:#64748b">{{ $metricsCfg[$m]['unit'] }}</span></div>
                <div class="s" style="color:{{ $c['color'] }}">{{ $c['label'] ?: $row->logged_at->diffForHumans() }}</div>
            @else <div class="v" style="color:#cbd5e1">—</div><div class="s text-muted">no readings</div>@endif
        </div>
    @endforeach
    {{-- Latest blood sugar --}}
    @php $gc = $latestGlucose ? $latestGlucose->classification() : null; @endphp
    <div class="ht-card">
        <div class="k"><i class="fa-solid fa-droplet" style="color:#0891b2"></i> Last BS</div>
        @if($latestGlucose)
            <div class="v" style="color:{{ $gc['color'] }}">{{ rtrim(rtrim(number_format((float)$latestGlucose->value,1),'0'),'.') }}</div>
            <div class="s" style="color:{{ $gc['color'] }}">mg/dL · {{ $latestGlucose->typeShort() }}</div>
        @else <div class="v" style="color:#cbd5e1">—</div><div class="s text-muted">no readings</div>@endif
    </div>
    {{-- Latest BP --}}
    @php $bc = $latestBp ? $latestBp->classification() : null; @endphp
    <div class="ht-card">
        <div class="k"><i class="fa-solid fa-heart-pulse" style="color:#dc2626"></i> Last BP</div>
        @if($latestBp)
            <div class="v" style="color:{{ $bc['color'] }};font-size:20px">{{ $latestBp->systolic }}/{{ $latestBp->diastolic }}</div>
            <div class="s" style="color:{{ $bc['color'] }}">mmHg{{ $latestBp->pulse ? ' · '.$latestBp->pulse.' bpm' : '' }}</div>
        @else <div class="v" style="color:#cbd5e1">—</div><div class="s text-muted">no readings</div>@endif
    </div>
</div>

{{-- Quick add --}}
<div class="card mb-3">
    <div class="card-header"><i class="fa-solid fa-plus"></i> Add entry</div>
    <div class="card-body">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:18px">

            {{-- Measurement --}}
            <form method="POST" action="{{ route('health.metric.store', $patient) }}">
                @csrf
                <div style="font-weight:700;margin-bottom:8px;color:#1e3360"><i class="fa-solid fa-ruler"></i> Measurement</div>
                <div class="form-group" style="margin:0 0 8px"><label class="form-label">Type</label>
                    <select name="metric" id="ht-metric" class="form-control">
                        @foreach($metricsCfg as $code => $cfg)<option value="{{ $code }}" data-unit="{{ $cfg['unit'] }}" data-step="{{ $cfg['step'] }}">{{ $cfg['label'] }} ({{ $cfg['unit'] }})</option>@endforeach
                    </select>
                </div>
                <div class="form-group" style="margin:0 0 8px"><label class="form-label">Value <span id="ht-metric-unit" class="text-muted" style="font-weight:400"></span></label>
                    <input type="number" step="0.1" min="0" name="value" id="ht-metric-value" class="form-control" required>
                </div>
                <div class="form-group" style="margin:0 0 8px"><label class="form-label">Date &amp; time</label>
                    <input type="datetime-local" name="logged_at" class="form-control" value="{{ request('date') ? request('date').'T'.now()->format('H:i') : now()->format('Y-m-d\TH:i') }}" required>
                </div>
                <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-floppy-disk"></i> Save</button>
            </form>

            {{-- Food --}}
            <form method="POST" action="{{ route('health.diet.store', $patient) }}" id="ht-food-form">
                @csrf
                <div style="font-weight:700;margin-bottom:8px;color:#1e3360"><i class="fa-solid fa-utensils"></i> Food eaten</div>
                <div class="form-row" style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:8px">
                    <div class="form-group" style="margin:0"><label class="form-label">Meal</label>
                        <select name="meal" class="form-control">
                            <option value="">—</option>
                            @foreach($meals as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
                        </select>
                    </div>
                    <div class="form-group" style="margin:0"><label class="form-label">Servings</label>
                        <input type="number" step="0.5" min="0" name="quantity" id="ht-food-qty" class="form-control" value="1" oninput="htFoodCalc()">
                    </div>
                </div>
                <div class="form-group" style="margin:0 0 8px"><label class="form-label">Food</label>
                    <select id="ht-food" class="form-control" onchange="htFood(this)">
                        <option value="">— choose food —</option>
                        @foreach(\App\Models\DietLog::FOODS as $grp => $items)
                            <optgroup label="{{ $grp }}">
                                @foreach($items as $name => $cal)<option value="{{ $name }}" data-cal="{{ $cal }}">{{ $name }} · {{ $cal }} kcal</option>@endforeach
                            </optgroup>
                        @endforeach
                        @if(!empty($foodExtras))<optgroup label="Added">@foreach($foodExtras as $f)<option value="{{ $f }}">{{ $f }}</option>@endforeach</optgroup>@endif
                        <option value="__add__">➕ Add new food…</option>
                    </select>
                    <input type="hidden" name="food" id="ht-food-name">
                    <input type="hidden" id="ht-food-base">
                </div>
                <div class="form-row" style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:8px">
                    <div class="form-group" style="margin:0"><label class="form-label">Calories</label>
                        <input type="number" min="0" name="calories" id="ht-food-cal" class="form-control" placeholder="kcal">
                    </div>
                    <div class="form-group" style="margin:0"><label class="form-label">Time</label>
                        <input type="datetime-local" name="logged_at" id="ht-food-time" class="form-control" value="{{ request('date') ? request('date').'T'.now()->format('H:i') : now()->format('Y-m-d\TH:i') }}" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-floppy-disk"></i> Log food</button>
            </form>

            {{-- Exercise --}}
            <form method="POST" action="{{ route('health.exercise.store', $patient) }}">
                @csrf
                <div style="font-weight:700;margin-bottom:8px;color:#1e3360"><i class="fa-solid fa-person-running"></i> Exercise</div>
                <div class="form-group" style="margin:0 0 8px"><label class="form-label">Activity</label>
                    <select name="activity" id="ht-act" class="form-control" onchange="htAct()">
                        @foreach(\App\Models\ExerciseLog::ACTIVITIES as $name => $met)<option value="{{ $name }}" data-met="{{ $met }}">{{ $name }}</option>@endforeach
                        @if(!empty($exerciseExtras))<optgroup label="Added">@foreach($exerciseExtras as $a)<option value="{{ $a }}">{{ $a }}</option>@endforeach</optgroup>@endif
                        <option value="__add__">➕ Add new activity…</option>
                    </select>
                </div>
                <div class="form-row" style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:8px">
                    <div class="form-group" style="margin:0"><label class="form-label">Minutes</label>
                        <input type="number" min="1" name="minutes" id="ht-min" class="form-control" oninput="htAct()" placeholder="e.g. 30">
                    </div>
                    <div class="form-group" style="margin:0"><label class="form-label">kcal burned</label>
                        <input type="number" min="0" name="calories_burned" id="ht-burn" class="form-control" placeholder="auto">
                    </div>
                </div>
                <div class="form-group" style="margin:0 0 8px"><label class="form-label">Date &amp; time</label>
                    <input type="datetime-local" name="logged_at" class="form-control" value="{{ request('date') ? request('date').'T'.now()->format('H:i') : now()->format('Y-m-d\TH:i') }}" required>
                </div>
                <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-floppy-disk"></i> Log exercise</button>
                @if($latestWeight)<div class="text-muted text-small" style="margin-top:4px">kcal auto-estimated from {{ $fmt($latestWeight) }} kg.</div>
                @else<div class="text-muted text-small" style="margin-top:4px">Log a weight to auto-estimate kcal.</div>@endif
            </form>
        </div>
    </div>
</div>

{{-- AI suggestions --}}
@include('partials._ai_suggestions', ['patient' => $patient, 'focus' => 'health'])


{{-- Tables --}}
<div class="grid grid-2" style="gap:16px">
    {{-- Measurements --}}
    <div class="card">
        <div class="card-header"><i class="fa-solid fa-ruler"></i> Measurements</div>
        <div class="table-wrap">
            <table class="sc-table">
                <thead><tr><th>When</th><th>Type</th><th>Value</th><th></th></tr></thead>
                <tbody>
                @forelse($metrics->sortByDesc('logged_at')->take(40) as $r)
                    @php $c = $r->classification(); @endphp
                    <tr>
                        <td class="text-small">{{ $r->logged_at->format('d M, h:i A') }}</td>
                        <td class="text-small">{{ $r->label() }}</td>
                        <td class="text-small" style="font-weight:600;color:{{ $c['color'] }}">{{ $fmt($r->value) }} {{ $r->unit() }} @if($c['label'])<span style="font-weight:400">· {{ $c['label'] }}</span>@endif</td>
                        <td style="white-space:nowrap">
                            <button type="button" class="ht-del" style="color:#6366f1" title="edit" onclick="htMetricEdit({{ $r->id }})"><i class="fa-solid fa-pen"></i></button>
                            <form method="POST" action="{{ route('health.metric.destroy', [$patient, $r]) }}" style="display:inline" onsubmit="return confirm('Delete this {{ $r->label() }} reading?')">@csrf @method('DELETE')<button class="ht-del" title="delete"><i class="fa-solid fa-trash"></i></button></form>
                        </td>
                    </tr>
                @empty <tr><td colspan="4" class="text-muted text-small">No measurements yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Exercise --}}
    <div class="card">
        <div class="card-header"><i class="fa-solid fa-person-running"></i> Exercise</div>
        <div class="table-wrap">
            <table class="sc-table">
                <thead><tr><th>When</th><th>Activity</th><th>Min</th><th>kcal</th><th></th></tr></thead>
                <tbody>
                @forelse($exercise->take(40) as $r)
                    <tr>
                        <td class="text-small">{{ $r->logged_at->format('d M, h:i A') }}</td>
                        <td class="text-small">{{ $r->activity }}</td>
                        <td class="text-small">{{ $r->minutes ?: '—' }}</td>
                        <td class="text-small" style="color:#ea580c;font-weight:600">{{ $r->calories_burned ?: '—' }}</td>
                        <td><form method="POST" action="{{ route('health.exercise.destroy', [$patient, $r]) }}" onsubmit="return confirm('Delete this activity?')">@csrf @method('DELETE')<button class="ht-del" title="delete"><i class="fa-solid fa-trash"></i></button></form></td>
                    </tr>
                @empty <tr><td colspan="5" class="text-muted text-small">No activity yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Diet (grouped by day) --}}
<div class="card" style="margin-top:16px">
    <div class="card-header"><i class="fa-solid fa-utensils"></i> Food log</div>
    <div class="table-wrap">
        <table class="sc-table">
            <thead><tr><th>Time</th><th>Meal</th><th>Food</th><th>Serv.</th><th>kcal</th><th></th></tr></thead>
            <tbody>
            @php $foodDates = collect($dietByDay->keys())->merge(array_keys($diaryFoodsByDay))->unique()->sortDesc()->values(); @endphp
            @forelse($foodDates as $date)
                @php $rows = $dietByDay[$date] ?? collect(); $dfoods = $diaryFoodsByDay[$date] ?? []; @endphp
                <tr class="ht-day"><td colspan="4">{{ \Carbon\Carbon::parse($date)->format('d M Y') }}</td><td colspan="2" style="text-align:right">{{ $rows->sum('calories') }} kcal total</td></tr>
                @foreach($rows->sortByDesc('logged_at') as $r)
                    <tr>
                        <td class="text-small">{{ $r->logged_at->format('h:i A') }}</td>
                        <td class="text-small">{{ $meals[$r->meal] ?? '—' }}</td>
                        <td class="text-small">{{ $r->food }}</td>
                        <td class="text-small text-muted">{{ $r->quantity ? rtrim(rtrim($r->quantity, '0'), '.').'×' : '—' }}</td>
                        <td class="text-small" style="color:#0ea5e9;font-weight:600">{{ $r->calories ?? '—' }}</td>
                        <td style="white-space:nowrap">
                            <button type="button" class="ht-del" style="color:#6366f1" title="edit" onclick="htDietEdit({{ $r->id }})"><i class="fa-solid fa-pen"></i></button>
                            <form method="POST" action="{{ route('health.diet.destroy', [$patient, $r]) }}" style="display:inline" onsubmit="return confirm('Delete this food entry?')">@csrf @method('DELETE')<button class="ht-del" title="delete"><i class="fa-solid fa-trash"></i></button></form>
                        </td>
                    </tr>
                @endforeach
                @foreach($dfoods as $f)
                    <tr style="background:#fffbeb">
                        <td class="text-small" style="color:#b45309">{{ $f['time'] }}</td>
                        <td class="text-small"><span style="font-size:10px;background:#fde68a;color:#92400e;padding:1px 6px;border-radius:6px;white-space:nowrap"><i class="fa-solid fa-droplet" style="font-size:8px"></i> Diary</span></td>
                        <td class="text-small">{{ $f['food'] }}</td>
                        <td class="text-small text-muted">—</td>
                        <td class="text-small text-muted">—</td>
                        <td style="white-space:nowrap"><a href="{{ route('glucose.index', $patient) }}" title="entered in the Diabetes Diary — edit it there" style="color:#6366f1"><i class="fa-solid fa-arrow-up-right-from-square" style="font-size:11px"></i></a></td>
                    </tr>
                @endforeach
            @empty <tr><td colspan="6" class="text-muted text-small">No food logged yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Blood Sugar log --}}
@if($glucoseLogs->isNotEmpty())
<div class="card" style="margin-top:16px">
    <div class="card-header" style="display:flex;align-items:center;gap:8px">
        <i class="fa-solid fa-droplet" style="color:#0891b2"></i> Blood Sugar readings
        <a href="{{ route('glucose.index', $patient) }}" class="btn btn-sm btn-outline" style="margin-left:auto;font-size:11px;padding:2px 10px"><i class="fa-solid fa-arrow-up-right-from-square"></i> Open Diabetes Diary</a>
    </div>
    <div class="table-wrap">
        <table class="sc-table">
            <thead><tr><th>Date &amp; time</th><th>Type</th><th>Value</th><th>Category</th><th>Medicines</th></tr></thead>
            <tbody>
            @foreach($glucoseLogs->filter(fn($l)=>$l->value!==null)->sortByDesc('logged_at')->take(30) as $r)
                @php $gc2 = $r->classification(); @endphp
                <tr>
                    <td class="text-small">{{ $r->logged_at->format('d M Y, h:i A') }}</td>
                    <td class="text-small">{{ $r->typeShort() }}</td>
                    <td class="text-small" style="font-weight:700;color:{{ $gc2['color'] }}">{{ rtrim(rtrim(number_format((float)$r->value,1),'0'),'.') }} {{ $r->unit() }}</td>
                    <td class="text-small" style="color:{{ $gc2['color'] }}">{{ $gc2['label'] ?: '—' }}</td>
                    <td class="text-small text-muted">{{ $r->medications ?: '—' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- BP log --}}
@if($bpLogs->isNotEmpty())
<div class="card" style="margin-top:16px">
    <div class="card-header" style="display:flex;align-items:center;gap:8px">
        <i class="fa-solid fa-heart-pulse" style="color:#dc2626"></i> Blood Pressure readings
        <a href="{{ route('bp.index', $patient) }}" class="btn btn-sm btn-outline" style="margin-left:auto;font-size:11px;padding:2px 10px"><i class="fa-solid fa-arrow-up-right-from-square"></i> Open BP Diary</a>
    </div>
    <div class="table-wrap">
        <table class="sc-table">
            <thead><tr><th>Date &amp; time</th><th>BP (mmHg)</th><th>Category</th><th>Pulse</th><th>Medicines</th></tr></thead>
            <tbody>
            @foreach($bpLogs->filter(fn($l)=>$l->systolic&&$l->diastolic)->sortByDesc('logged_at')->take(30) as $r)
                @php $bc2 = $r->classification(); @endphp
                <tr>
                    <td class="text-small">{{ $r->logged_at->format('d M Y, h:i A') }}</td>
                    <td class="text-small" style="font-weight:700"><span style="color:{{ $bc2['color'] }}">{{ $r->systolic }}/{{ $r->diastolic }}</span></td>
                    <td class="text-small" style="color:{{ $bc2['color'] }}">{{ $bc2['label'] ?: '—' }}</td>
                    <td class="text-small">{{ $r->pulse ? $r->pulse.' bpm' : '—' }}</td>
                    <td class="text-small text-muted">{{ $r->medications ?: '—' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<div class="gl-disclaimer">
    <i class="fa-solid fa-circle-info"></i>
    Calorie values are approximate references for common servings; exercise burn is estimated from body weight and activity (MET). Use as a guide, not a precise measurement.
</div>

<script>
var GL_RXOPT_URL = @json(route('rx-options.store'));
var GL_CSRF = @json(csrf_token());
var HT_WEIGHT = @json($latestWeight);

// If opened from Diabetes Diary with ?date=, scroll to food form and highlight it
(function(){
    var d=new URLSearchParams(window.location.search).get('date');
    if(d){
        var f=document.getElementById('ht-food-form');
        if(f){ setTimeout(function(){ f.scrollIntoView({behavior:'smooth',block:'center'}); f.style.outline='2px solid #0891b2'; f.style.borderRadius='8px'; setTimeout(function(){f.style.outline='';},2000); },300); }
    }
})();

// Measurement unit/step hint
(function(){
    var sel=document.getElementById('ht-metric'),u=document.getElementById('ht-metric-unit'),v=document.getElementById('ht-metric-value');
    function sync(){var o=sel.options[sel.selectedIndex];u.textContent=o?'('+o.getAttribute('data-unit')+')':'';v.step=o?o.getAttribute('data-step'):'0.1';}
    sel.addEventListener('change',sync); sync();
})();

// Food: per-serving calories × servings (auto, editable); add-new persists
function htFoodCalc(){
    var base=parseFloat(document.getElementById('ht-food-base').value),
        qty=parseFloat(document.getElementById('ht-food-qty').value);
    if(!isNaN(base) && !isNaN(qty)){ document.getElementById('ht-food-cal').value=Math.round(base*qty); }
}
function htFood(sel){
    var name=document.getElementById('ht-food-name'), base=document.getElementById('ht-food-base');
    if(sel.value==='__add__'){
        var nm=(prompt('New food name:')||'').trim(); sel.value='';
        if(!nm) return;
        var kc=(prompt('Approx calories for ONE serving (kcal):')||'').trim();
        var opt=document.createElement('option'); opt.value=nm; opt.textContent=nm+(kc?(' · '+kc+' kcal'):''); if(kc) opt.setAttribute('data-cal',kc);
        var addOpt=sel.querySelector('option[value="__add__"]');
        var grp=sel.querySelector('optgroup[label="Added"]'); if(!grp){grp=document.createElement('optgroup');grp.label='Added';sel.insertBefore(grp,addOpt);} grp.appendChild(opt);
        sel.value=nm; name.value=nm; base.value=kc||''; htFoodCalc();
        fetch(GL_RXOPT_URL,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':GL_CSRF,'X-Requested-With':'XMLHttpRequest'},body:JSON.stringify({field:'food_item',value:nm})}).catch(function(){});
        return;
    }
    name.value=sel.value||'';
    var o=sel.options[sel.selectedIndex], dc=o?o.getAttribute('data-cal'):null;
    base.value=dc||'';
    htFoodCalc();
}

// Exercise: auto-estimate kcal = MET*3.5*kg/200*min
function htAct(){
    var sel=document.getElementById('ht-act'), min=document.getElementById('ht-min'), burn=document.getElementById('ht-burn');
    if(sel.value==='__add__'){
        var nm=(prompt('New activity name:')||'').trim(); sel.value='';
        if(!nm) return;
        var opt=document.createElement('option'); opt.value=nm; opt.textContent=nm;
        var addOpt=sel.querySelector('option[value="__add__"]');
        var grp=sel.querySelector('optgroup[label="Added"]'); if(!grp){grp=document.createElement('optgroup');grp.label='Added';sel.insertBefore(grp,addOpt);} grp.appendChild(opt);
        sel.value=nm;
        fetch(GL_RXOPT_URL,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':GL_CSRF,'X-Requested-With':'XMLHttpRequest'},body:JSON.stringify({field:'exercise_item',value:nm})}).catch(function(){});
        return;
    }
    var o=sel.options[sel.selectedIndex], met=o?parseFloat(o.getAttribute('data-met')):null;
    if(met && HT_WEIGHT && min.value){ burn.value=Math.round(met*3.5*HT_WEIGHT/200*parseFloat(min.value)); }
}
// initialise hidden food name to current select value (none by default)
document.getElementById('ht-food-name').value='';
</script>


{{-- Metric edit modal --}}
<div id="ht-metric-modal" class="modal-overlay">
    <div class="modal-box" style="max-width:420px">
        <div class="modal-header">
            <i class="fa-solid fa-ruler" style="color:var(--sc-teal)"></i><h3>Edit Measurement</h3>
            <button class="modal-close" type="button" onclick="document.getElementById('ht-metric-modal').classList.remove('open')">&times;</button>
        </div>
        <form id="ht-metric-edit-form" method="POST">
            @csrf @method('PUT')
            <div class="modal-body" style="display:grid;gap:12px">
                <div class="form-group" style="margin:0"><label class="form-label">Type</label>
                    <select name="metric" class="form-control">
                        @foreach(\App\Models\HealthMetric::METRICS as $code => $cfg)<option value="{{ $code }}">{{ $cfg['label'] }} ({{ $cfg['unit'] }})</option>@endforeach
                    </select>
                </div>
                <div class="form-group" style="margin:0"><label class="form-label">Value</label>
                    <input type="number" step="0.1" min="0" name="value" class="form-control" required>
                </div>
                <div class="form-group" style="margin:0"><label class="form-label">Date &amp; time</label>
                    <input type="datetime-local" name="logged_at" class="form-control" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('ht-metric-modal').classList.remove('open')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save</button>
            </div>
        </form>
    </div>
</div>

{{-- Diet edit modal --}}
<div id="ht-diet-modal" class="modal-overlay">
    <div class="modal-box" style="max-width:480px">
        <div class="modal-header">
            <i class="fa-solid fa-utensils" style="color:var(--sc-teal)"></i><h3>Edit Food Entry</h3>
            <button class="modal-close" type="button" onclick="document.getElementById('ht-diet-modal').classList.remove('open')">&times;</button>
        </div>
        <form id="ht-diet-edit-form" method="POST">
            @csrf @method('PUT')
            <div class="modal-body" style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                <div class="form-group" style="margin:0"><label class="form-label">Meal</label>
                    <select name="meal" class="form-control">
                        <option value="">—</option>
                        @foreach(\App\Models\DietLog::MEALS as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
                    </select>
                </div>
                <div class="form-group" style="margin:0"><label class="form-label">Servings</label>
                    <input type="number" step="0.5" min="0" name="quantity" class="form-control">
                </div>
                <div class="form-group" style="margin:0;grid-column:1/-1"><label class="form-label">Food</label>
                    <input type="text" name="food" class="form-control" required>
                </div>
                <div class="form-group" style="margin:0"><label class="form-label">Calories (kcal)</label>
                    <input type="number" min="0" name="calories" class="form-control">
                </div>
                <div class="form-group" style="margin:0"><label class="form-label">Date &amp; time</label>
                    <input type="datetime-local" name="logged_at" class="form-control" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('ht-diet-modal').classList.remove('open')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save</button>
            </div>
        </form>
    </div>
</div>

@php
    $htMetricsJson = $metrics->mapWithKeys(fn($r) => [$r->id => [
        'metric'    => $r->metric,
        'value'     => $r->value,
        'logged_at' => $r->logged_at->format('Y-m-d\TH:i'),
    ]]);
    $htDietJson = $diet->mapWithKeys(fn($r) => [$r->id => [
        'meal'      => $r->meal,
        'food'      => $r->food,
        'quantity'  => $r->quantity,
        'calories'  => $r->calories,
        'logged_at' => $r->logged_at->format('Y-m-d\TH:i'),
    ]]);
@endphp
<script>
var HT_BASE_METRIC  = @json(route('health.metric.store', $patient));
var HT_BASE_DIET    = @json(route('health.diet.store', $patient));
var HT_METRICS_DATA = @json($htMetricsJson);
var HT_DIET_DATA    = @json($htDietJson);

function htMetricEdit(id){
    var e=HT_METRICS_DATA[id]; if(!e) return;
    var f=document.getElementById('ht-metric-edit-form');
    f.action=HT_BASE_METRIC.replace('/health/metric', '/health/metric/'+id);
    f.querySelector('[name=metric]').value=e.metric;
    f.querySelector('[name=value]').value=e.value;
    f.querySelector('[name=logged_at]').value=e.logged_at;
    document.getElementById('ht-metric-modal').classList.add('open');
}
function htDietEdit(id){
    var e=HT_DIET_DATA[id]; if(!e) return;
    var f=document.getElementById('ht-diet-edit-form');
    f.action=HT_BASE_DIET.replace('/health/diet', '/health/diet/'+id);
    f.querySelector('[name=meal]').value=e.meal||'';
    f.querySelector('[name=food]').value=e.food||'';
    f.querySelector('[name=quantity]').value=e.quantity||'';
    f.querySelector('[name=calories]').value=e.calories||'';
    f.querySelector('[name=logged_at]').value=e.logged_at;
    document.getElementById('ht-diet-modal').classList.add('open');
}
scTrackPatient({{ $patient->id }}, @json($patient->full_name), @json($patient->jhc_id), @json(route('patients.show', $patient)));
window.scPatientVisitUrl = @json(route('appointments.create', ['patient_id' => $patient->id]));
</script>
@endsection
