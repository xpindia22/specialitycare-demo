@extends('layouts.app')
@section('title', 'Diabetes Diary — ' . $patient->full_name)
@section('page-title', 'Diabetes Diary')
@section('patient-context')
@include('partials._patient_ctx', ['patient' => $patient, 'active' => 'glucose'])
@endsection
@section('content')

@php
    $fmt = fn ($v) => $v === null || $v === '' ? '' : rtrim(rtrim(number_format((float) $v, 1), '0'), '.');
    $types = \App\Models\GlucoseLog::TYPES;
@endphp

<style>
    .gl-chip{display:inline-block;border:1.5px solid var(--c);color:var(--c);background:#fff;border-radius:999px;
        padding:2px 11px;font-size:13px;font-weight:800;cursor:pointer;line-height:1.45;font-family:inherit;white-space:nowrap;box-shadow:0 1px 2px rgba(0,0,0,.06)}
    .gl-chip:hover{background:var(--c);color:#fff}
    .gl-cell{display:flex;flex-direction:column;gap:3px;align-items:flex-start}
    .gl-grid td,.gl-grid th{text-align:center;vertical-align:top}
    .gl-grid td.gl-date,.gl-grid th.gl-date{text-align:left;white-space:nowrap;min-width:110px}
    .gl-meds{font-size:12px;text-align:left;color:#334155}
    .gl-meds .gl-time{color:#94a3b8;font-size:10px;font-weight:600;margin-right:4px}
    .gl-grid th.gl-notes,.gl-grid td.gl-notes{width:96px;min-width:80px;max-width:115px;white-space:normal;word-wrap:break-word;text-align:left;font-size:10px;line-height:1.3}
    .gl-rdg{display:flex;align-items:center;justify-content:center;gap:6px;flex-wrap:wrap;margin-top:5px}
    .gl-rdg .gl-rtype{font-size:10px;font-weight:700;color:#1e293b;text-transform:uppercase;letter-spacing:.4px}
    .gl-rdg .gl-rtime{font-size:10px;color:#64748b;font-weight:500;font-variant-numeric:tabular-nums}
    .gl-food{display:inline-flex;align-items:center;gap:5px;background:rgba(255,255,255,.62);border:1px solid rgba(180,83,9,.22);
        color:#9a3412;border-radius:8px;padding:2px 9px;font-size:11.5px;font-weight:600;line-height:1.4;margin-top:3px;cursor:pointer}
    .gl-food:hover{background:#fff;border-color:rgba(180,83,9,.4)}
    .gl-food .gl-ftime{color:#c2630a;font-size:10px;font-weight:500;font-variant-numeric:tabular-nums}
    .gl-food i{font-size:9px;opacity:.65}
    .gl-rmeds{font-size:11px;color:#475569;text-align:center;line-height:1.5;margin-top:1px}
    .gl-rmeds .gl-time{color:#94a3b8;font-weight:400}
    .gl-advice{display:flex;gap:10px;align-items:flex-start;padding:10px 14px;border-radius:8px;margin-bottom:8px;font-size:13.5px;line-height:1.5}
    .gl-advice i{margin-top:2px}
    .gl-warn{background:#fef2f2;border:1px solid #fecaca;color:#991b1b}
    .gl-good{background:#f0fdf4;border:1px solid #bbf7d0;color:#166534}
    .gl-info{background:#f1f5f9;border:1px solid #e2e8f0;color:#334155}
    .gl-stat{flex:1;min-width:120px;background:var(--sc-bg,#f8fafc);border-radius:10px;padding:12px 14px;text-align:center}
    .gl-stat .v{font-size:24px;font-weight:800;line-height:1.1}
    .gl-stat .k{font-size:11px;color:#64748b;text-transform:uppercase;letter-spacing:.04em;margin-top:3px}
    .gl-statrow{display:flex;gap:12px;flex-wrap:wrap;margin-bottom:16px}
    .gl-disclaimer{font-size:11.5px;color:#94a3b8;margin-top:10px;line-height:1.5}
    .gl-chips{display:flex;flex-wrap:wrap;gap:5px;margin-bottom:6px}
    .gl-chips:empty{display:none}
    .gl-tag{display:inline-flex;align-items:center;gap:6px;background:#eef2ff;border:1px solid #c7d2fe;color:#3730a3;border-radius:999px;padding:2px 6px 2px 10px;font-size:12px;font-weight:600}
    .gl-tag button{border:none;background:transparent;color:#6366f1;cursor:pointer;font-size:15px;line-height:1;padding:0}
    #gl-meal_add-chips .gl-tag,#gl-emeal-chips .gl-tag{cursor:pointer}
    #gl-meal_add-chips .gl-tag:hover,#gl-emeal-chips .gl-tag:hover{background:#e0e7ff}
    .gl-tag button:hover{color:#dc2626}
    .gl-editable{cursor:pointer;border-radius:4px;padding:1px 3px}
    .gl-editable:hover{background:#eef2ff}
</style>

<div class="page-header">
    <div class="page-header-left">
        <h1>Diabetes Diary</h1>
        <div class="breadcrumb"><a href="{{ route('patients.show', $patient) }}">{{ $patient->full_name }}</a> &rsaquo; Diabetes Diary</div>
    </div>
    @if($logs->isNotEmpty())
        <a href="{{ route('glucose.print', $patient) }}" target="_blank" class="btn btn-outline btn-sm"><i class="fa-solid fa-print"></i> Print</a>
    @endif
    <a href="{{ route('bp.index', $patient) }}"     class="btn btn-sm" style="background:#dc2626;color:#fff;border-color:#dc2626"><i class="fa-solid fa-heart-pulse"></i> BP Diary</a>
    <a href="{{ route('health.index', $patient) }}" class="btn btn-sm" style="background:#059669;color:#fff;border-color:#059669"><i class="fa-solid fa-heart"></i> Health Tracker</a>
    <a href="{{ route('patients.show', $patient) }}" class="btn btn-outline btn-sm"><i class="fa-solid fa-arrow-left"></i> Back</a>
</div>

@if(session('success'))<div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> {{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger"><i class="fa-solid fa-circle-xmark"></i> {{ session('error') }}</div>@endif

{{-- Add entry --}}
<div class="card mb-3">
    <div class="card-header"><i class="fa-solid fa-plus"></i> Add entry</div>
    <div class="card-body">
        <form method="POST" action="{{ route('glucose.store', $patient) }}">
            @csrf
            <div class="form-row" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px">
                <div class="form-group" style="margin:0">
                    <label class="form-label">Date &amp; time <span class="req">*</span></label>
                    <input type="datetime-local" name="logged_at" class="form-control"
                           value="{{ old('logged_at', now()->format('Y-m-d\TH:i')) }}" required>
                </div>
                <div class="form-group" style="margin:0">
                    <label class="form-label">Reading</label>
                    <select name="reading_type" id="gl-type" class="form-control">
                        <option value="">— Medicine / note only —</option>
                        @foreach(\App\Models\GlucoseLog::READING_OPTIONS as $code => $lbl)
                            <option value="{{ $code }}" data-unit="mg/dL" @selected(old('reading_type')===$code)>{{ $lbl }}</option>
                        @endforeach
                        <option value="hba1c" data-unit="%" @selected(old('reading_type')==='hba1c')>HbA1c (%)</option>
                        @if(!empty($readingExtras))
                            <optgroup label="Custom">
                                @foreach($readingExtras as $o)<option value="{{ $o }}" data-unit="mg/dL" @selected(old('reading_type')===$o)>{{ $o }}</option>@endforeach
                            </optgroup>
                        @endif
                        <option value="__add__">➕ Add new type…</option>
                    </select>
                </div>
                <div class="form-group" style="margin:0">
                    <label class="form-label">Value <span id="gl-unit" class="text-muted" style="font-weight:400"></span></label>
                    <input type="number" step="0.1" min="0" name="value" id="gl-value" class="form-control" placeholder="e.g. 110" value="{{ old('value') }}">
                </div>
                @include('glucose._picker', ['key'=>'medications','field'=>'diabetes_med','label'=>'Medicine taken','name'=>'medications','options'=>\App\Models\GlucoseLog::MEDICINES,'extras'=>$medExtras,'pinned'=>$medPinned,'value'=>old('medications')])
                <div class="form-group" style="margin:0">
                    <label class="form-label">Insulin (units)</label>
                    <input type="number" step="0.5" min="0" name="insulin_units" class="form-control" placeholder="e.g. 10" value="{{ old('insulin_units') }}">
                </div>
                @include('glucose._picker', ['key'=>'symptoms','field'=>'glucose_symptom','label'=>'Symptoms','name'=>'symptoms','options'=>\App\Models\GlucoseLog::SYMPTOMS,'extras'=>$symptomExtras,'value'=>old('symptoms')])
                <div style="grid-column:1/-1">
                    @include('glucose._food_picker', ['key'=>'meal_add','label'=>'🍽️ Meal / food eaten','name'=>'meal_notes','foodExtras'=>$foodExtras,'pinned'=>$foodPinned,'value'=>old('meal_notes')])
                </div>
                <div class="form-group" style="margin:0;grid-column:1/-1">
                    <label class="form-label">Notes</label>
                    <input type="text" name="notes" class="form-control" placeholder="activity, anything to remember (optional)" value="{{ old('notes') }}">
                </div>
            </div>
            <div style="margin-top:12px">
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save entry</button>
                <span class="text-muted text-small" style="margin-left:8px">Tip: log FBS, pre-lunch, pre-dinner, 2-hr after a meal, and bedtime as separate entries through the day.</span>
            </div>
        </form>
    </div>
</div>

{{-- Auto-analysis --}}
@if($analysis['stats'] || count($analysis['messages']))
<div class="card mb-3">
    <div class="card-header"><i class="fa-solid fa-wand-magic-sparkles"></i> Auto-analysis &amp; advice
        @if(($analysis['stats']['windowDays'] ?? null))
            <span class="text-muted" style="font-weight:400;font-size:11px;margin-left:6px">last {{ $analysis['stats']['windowDays'] }} days</span>
        @endif
    </div>
    <div class="card-body">
        @if($analysis['stats'])
            @php $s = $analysis['stats']; @endphp
            <div class="gl-statrow">
                <div class="gl-stat"><div class="v">{{ $s['avg'] }}</div><div class="k">Avg mg/dL</div></div>
                <div class="gl-stat"><div class="v" style="color:#16a34a">{{ $s['inPct'] }}%</div><div class="k">In target</div></div>
                <div class="gl-stat"><div class="v">{{ $s['estA1c'] ? $s['estA1c'].'%' : '—' }}</div><div class="k">Est. HbA1c</div></div>
                <div class="gl-stat"><div class="v" style="color:#dc2626">{{ $s['low'] }}</div><div class="k">Lows</div></div>
                <div class="gl-stat"><div class="v" style="color:#991b1b">{{ $s['high'] }}</div><div class="k">Highs</div></div>
                <div class="gl-stat"><div class="v">{{ $s['count'] }}</div><div class="k">Readings</div></div>
            </div>
        @endif

        @foreach($analysis['messages'] as $m)
            <div class="gl-advice gl-{{ $m['type'] }}">
                <i class="fa-solid {{ $m['type']==='good' ? 'fa-circle-check' : ($m['type']==='warn' ? 'fa-triangle-exclamation' : 'fa-circle-info') }}"></i>
                <span>{{ $m['text'] }}</span>
            </div>
        @endforeach

        <div class="gl-disclaimer">
            <i class="fa-solid fa-circle-info"></i>
            These notes are generated automatically from the logged values using general targets (fasting/pre-meal 80–130, 2-hr after-meal under 180 mg/dL, HbA1c under 7%).
            They are guidance only and do not replace the treating doctor's advice. Individual targets may differ.
        </div>
    </div>
</div>
@endif

{{-- Day-by-day diary --}}
@if($days->isEmpty())
    <x-empty-state icon="fa-droplet" title="No diary entries yet"
        message="Use “Add entry” above to record blood-sugar readings and the diabetes medicines taken. No encounter is needed." />
@else
<div class="card">
    <div class="card-header"><i class="fa-solid fa-table-list"></i> Day-by-day log</div>
    <div class="table-wrap">
        <table class="sc-table gl-grid">
            <thead>
                <tr>
                    <th class="gl-date">Date</th>
                    @foreach(\App\Models\GlucoseLog::DIARY_COLUMNS as $dc)
                        <th style="background:{{ $dc['color'] }}">{{ $dc['label'] }}@if($dc['range'])<div class="text-muted" style="font-weight:400;font-size:9px">{{ $dc['range'] }}</div>@endif</th>
                    @endforeach
                    <th class="gl-notes" style="background:{{ \App\Models\GlucoseLog::NOTES_COLUMN_COLOR }}">Notes-Symptoms</th>
                </tr>
            </thead>
            <tbody>
            @foreach($days as $day)
                @php $sep = $loop->first ? '' : 'border-top:2px solid #cbd5e1;'; @endphp
                <tr>
                    <td class="gl-date fw-600" style="{{ $sep }}">
                        {{ \Carbon\Carbon::parse($day['date'])->format('d M Y') }}
                        <div class="text-muted text-small">{{ \Carbon\Carbon::parse($day['date'])->format('D') }}</div>
                    </td>
                    @foreach(\App\Models\GlucoseLog::DIARY_COLUMNS as $ci => $dc)
                        @php $cellReadings = $readingsByDayCol[$day['date']][$ci] ?? collect(); $cellFoods = $foodByDayCol[$day['date']][$ci] ?? []; @endphp
                        <td style="background:{{ $dc['color'] }};{{ $sep }}">
                            <div class="gl-cell" style="align-items:center">
                                @foreach($cellReadings as $r)
                                    @php $c = $r->classification(); $t = $r->logged_at->format('h:i A'); @endphp
                                    <div class="gl-rdg">
                                        <span class="gl-rtype">{{ \App\Models\GlucoseLog::TYPES[$r->reading_type]['short'] ?? '' }}</span>
                                        <button type="button" class="gl-chip" style="--c:{{ $c['color'] }}" onclick="glEdit({{ $r->id }})"
                                            title="{{ \App\Models\GlucoseLog::TYPES[$r->reading_type]['short'] ?? '' }} · {{ $c['label'] }} · {{ $t }}{{ $r->recordedBy ? ' · '.$r->recordedBy->name : '' }} — click to edit / delete">{{ $fmt($r->value) }}</button>
                                        <span class="gl-rtime">{{ $t }}</span>
                                    </div>
                                    @if($r->medications || $r->insulin_units !== null)
                                        <div class="gl-rmeds gl-editable" onclick="glEdit({{ $r->id }})" title="click to edit / delete">
                                            @if($r->medications)
                                                @foreach(array_filter(array_map('trim', explode(',', $r->medications))) as $med)
                                                    <div>{{ $med }} <span class="gl-time">({{ $t }})</span></div>
                                                @endforeach
                                            @endif
                                            @if($r->insulin_units !== null)
                                                <div style="font-weight:700;color:#0369a1">{{ $fmt($r->insulin_units) }} U insulin <span class="gl-time">({{ $t }})</span></div>
                                            @endif
                                        </div>
                                    @endif
                                @endforeach
                                @foreach($cellFoods as $f)
                                    @php $isLabel = in_array(strtolower($f['time']), ['morning','late morning','noon','lunch','afternoon','evening','dinner','night','random'], true); @endphp
                                    <div class="gl-food" onclick="glEdit({{ $f['logId'] }})" title="click to edit / delete (food, quantity, time)"><i class="fa-solid fa-utensils"></i> {{ $f['food'] }}@if(!$isLabel) <span class="gl-ftime">{{ $f['time'] }}</span>@endif</div>
                                @endforeach
                                @if($cellReadings->isEmpty() && empty($cellFoods))<span class="text-muted">—</span>@endif
                            </div>
                        </td>
                    @endforeach
                    @php $otherMeds = $day['meds']->filter(fn($r) => !$r->reading_type); @endphp
                    <td class="gl-meds gl-notes" style="background:{{ \App\Models\GlucoseLog::NOTES_COLUMN_COLOR }};{{ $sep }}">
                        @foreach($otherMeds as $r)
                            <div class="gl-editable" onclick="glEdit({{ $r->id }})" title="click to edit / delete">
                                <span class="gl-time">{{ $r->logged_at->format('h:i A') }}</span>
                                @if($r->medications){{ $r->medications }}@endif
                                @if($r->insulin_units !== null)<strong>{{ $fmt($r->insulin_units) }} U insulin</strong>@endif
                            </div>
                        @endforeach
                        @foreach($day['hba1c'] as $r)
                            @php $c = $r->classification(); @endphp
                            <div class="gl-editable" onclick="glEdit({{ $r->id }})" title="click to edit / delete"><span class="gl-time">{{ $r->logged_at->format('h:i A') }}</span>
                                <span style="color:{{ $c['color'] }};font-weight:700">HbA1c {{ $fmt($r->value) }}%</span>
                                <span class="text-muted">({{ $c['label'] }})</span></div>
                        @endforeach
                        @foreach($day['notes'] as $r)
                            @php $isCustom = $r->reading_type && !in_array($r->reading_type, \App\Models\GlucoseLog::GRID_COLUMNS) && $r->reading_type !== 'hba1c'; @endphp
                            <div class="gl-editable" style="font-size:11.5px" onclick="glEdit({{ $r->id }})" title="click to edit / delete">
                                <span class="gl-time">{{ $r->logged_at->format('h:i A') }}</span>
                                @if($isCustom && $r->value !== null)<span style="color:#475569;font-weight:600">{{ $r->reading_type }}: {{ $fmt($r->value) }} mg/dL</span> @endif
                                @if($r->notes)<span class="text-muted"><i class="fa-solid fa-note-sticky" style="font-size:10px"></i> {{ $r->notes }}</span>@endif
                                @if($r->symptoms)<span class="text-muted"> · {{ $r->symptoms }}</span>@endif
                            </div>
                        @endforeach
                        @if($otherMeds->isEmpty() && $day['hba1c']->isEmpty() && $day['notes']->isEmpty())<span class="text-muted">—</span>@endif
                    </td>
                </tr>
                @php $dayBp = $bpByDay[$day['date']] ?? collect(); @endphp
                @if($dayBp->isNotEmpty())
                <tr style="background:#fff1f2">
                    <td class="gl-date" style="color:#be123c;font-size:11px;font-weight:600;vertical-align:middle">
                        <i class="fa-solid fa-heart-pulse" style="margin-right:4px;opacity:.7"></i> BP
                    </td>
                    @foreach(\App\Models\GlucoseLog::DIARY_COLUMNS as $ci => $dc)
                        @php $cellBp = $bpByDayCol[$day['date']][$ci] ?? collect(); @endphp
                        <td style="vertical-align:top">
                            <div class="gl-cell" style="align-items:center">
                                @foreach($cellBp as $bp)
                                    @php $bc = $bp->classification(); $bt = $bp->logged_at->format('h:i A'); @endphp
                                    <div class="gl-rdg gl-editable" onclick="window.location='{{ route('bp.index', $patient) }}'"
                                        title="{{ $bc['label'] }} · {{ $bt }} — open BP diary">
                                        <span style="font-size:11.5px;font-weight:700;color:{{ $bc['color'] }}">{{ $bp->systolic }}/{{ $bp->diastolic }}</span>
                                        @if($bp->pulse)<span style="font-size:10px;color:#64748b"><i class="fa-solid fa-heart-pulse" style="font-size:8px;color:#dc2626"></i> {{ $bp->pulse }}</span>@endif
                                        <span class="gl-rtime">{{ $bt }}</span>
                                    </div>
                                    @if($bp->medications)
                                        <div class="gl-rmeds" style="color:#9f1239">{{ $bp->medications }} <span class="gl-time">({{ $bt }})</span></div>
                                    @endif
                                @endforeach
                                @if($cellBp->isEmpty())<span class="text-muted" style="opacity:.4">·</span>@endif
                            </div>
                        </td>
                    @endforeach
                    <td class="gl-notes" style="background:#fff1f2"></td>
                </tr>
                @endif
                @php $dayMeals = $mealsByDay[$day['date']] ?? collect(); @endphp
                @if($dayMeals->isNotEmpty())
                <tr style="background:#f0f9ff">
                    <td colspan="{{ count(\App\Models\GlucoseLog::DIARY_COLUMNS) + 2 }}" style="padding:5px 12px;font-size:11.5px;color:#0369a1">
                        <i class="fa-solid fa-utensils" style="margin-right:4px;opacity:.7"></i>
                        @foreach(['breakfast'=>'Breakfast','snack'=>'Snack / Mid-meal','lunch'=>'Lunch','dinner'=>'Dinner'] as $slot => $slotLabel)
                            @php $items = $dayMeals->where('meal', $slot); @endphp
                            @if($items->isNotEmpty())
                                <span style="margin-right:14px"><strong>{{ $slotLabel }}:</strong>
                                    @foreach($items as $fi)
                                        <a href="{{ route('health.index', $patient) }}?date={{ $day['date'] }}" style="color:#0369a1;text-decoration:none" title="Open Health Tracker for this day">{{ $fi->food }}@if($fi->calories) ({{ $fi->calories }} kcal)@endif</a>@if(!$loop->last), @endif
                                    @endforeach
                                </span>
                            @endif
                        @endforeach
                    </td>
                </tr>
                @endif
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- AI medicine suggestions --}}
@include('partials._ai_suggestions', ['patient' => $patient, 'focus' => 'diabetes'])

{{-- Trend chart --}}
@if($points->isNotEmpty())
<div class="card mb-3">
    <div class="card-header"><i class="fa-solid fa-chart-line"></i> Blood-sugar trend
        <span class="text-muted" style="font-weight:400;font-size:11px;margin-left:6px">{{ $points->count() }} readings (mg/dL)</span>
    </div>
    <div class="card-body"><canvas id="glChart" height="90"></canvas></div>
</div>
@endif

{{-- Edit-entry modal --}}
<div id="gl-edit-modal" class="modal-overlay">
    <div class="modal-box" style="max-width:560px">
        <div class="modal-header">
            <i class="fa-solid fa-pen" style="color:var(--sc-teal)"></i>
            <h3>Edit entry</h3>
            <button class="modal-close" type="button" onclick="glCloseEdit()">&times;</button>
        </div>
        <form id="gl-edit-form" method="POST">
            @csrf
            @method('PUT')
            <div class="modal-body" style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                <div class="form-group" style="margin:0">
                    <label class="form-label">Date &amp; time <span class="req">*</span></label>
                    <input type="datetime-local" name="logged_at" class="form-control" required>
                </div>
                <div class="form-group" style="margin:0">
                    <label class="form-label">Reading</label>
                    <select name="reading_type" class="form-control">
                        <option value="">— Medicine / note only —</option>
                        @foreach(\App\Models\GlucoseLog::READING_OPTIONS as $code => $lbl)<option value="{{ $code }}" data-unit="mg/dL">{{ $lbl }}</option>@endforeach
                        <option value="hba1c" data-unit="%">HbA1c (%)</option>
                        @if(!empty($readingExtras))
                            <optgroup label="Custom">
                                @foreach($readingExtras as $o)<option value="{{ $o }}" data-unit="mg/dL">{{ $o }}</option>@endforeach
                            </optgroup>
                        @endif
                        <option value="__add__">➕ Add new type…</option>
                    </select>
                </div>
                <div class="form-group" style="margin:0">
                    <label class="form-label">Value <span class="text-muted" style="font-weight:400">(mg/dL, or % for HbA1c)</span></label>
                    <input type="number" step="0.1" min="0" name="value" class="form-control" placeholder="e.g. 110">
                </div>
                <div class="form-group" style="margin:0">
                    <label class="form-label">Insulin (units)</label>
                    <input type="number" step="0.5" min="0" name="insulin_units" class="form-control" placeholder="e.g. 10">
                </div>
                <div style="grid-column:1/-1">
                    @include('glucose._picker', ['key'=>'emed','field'=>'diabetes_med','label'=>'Medicine taken','name'=>'medications','options'=>\App\Models\GlucoseLog::MEDICINES,'extras'=>$medExtras,'pinned'=>$medPinned,'value'=>''])
                </div>
                <div style="grid-column:1/-1">
                    @include('glucose._picker', ['key'=>'esym','field'=>'glucose_symptom','label'=>'Symptoms','name'=>'symptoms','options'=>\App\Models\GlucoseLog::SYMPTOMS,'extras'=>$symptomExtras,'value'=>''])
                </div>
                <div style="grid-column:1/-1">
                    @include('glucose._food_picker', ['key'=>'emeal','label'=>'🍽️ Meal / food eaten','name'=>'meal_notes','foodExtras'=>$foodExtras,'pinned'=>$foodPinned,'value'=>''])
                </div>
                <div class="form-group" style="margin:0;grid-column:1/-1">
                    <label class="form-label">Notes</label>
                    <input type="text" name="notes" class="form-control">
                </div>
            </div>
            <div class="modal-footer" style="justify-content:space-between">
                <button type="button" class="btn btn-outline" style="color:var(--sc-red);border-color:var(--sc-red)" onclick="glDeleteFromModal()"><i class="fa-solid fa-trash"></i> Delete</button>
                <span>
                    <button type="button" class="btn btn-outline" onclick="glCloseEdit()">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save changes</button>
                </span>
            </div>
        </form>
    </div>
</div>
<form id="gl-delete-form" method="POST" style="display:none">@csrf @method('DELETE')</form>

<script>
// ---- reading-type unit hint (add form) ----
(function () {
    var sel = document.getElementById('gl-type'),
        unit = document.getElementById('gl-unit'),
        val = document.getElementById('gl-value');
    if (!sel) return;
    function syncUnit() {
        var opt = sel.options[sel.selectedIndex], u = opt ? opt.getAttribute('data-unit') : '';
        unit.textContent = u ? '(' + u + ')' : '';
        val.placeholder = sel.value === 'hba1c' ? 'e.g. 7.2' : 'e.g. 110';
    }
    sel.addEventListener('change', function () {
        if (sel.value === '__add__') {
            var nv = (prompt('New reading type (e.g. Post walk, 3 AM, Before sleep):') || '').replace(/,/g,' ').trim();
            sel.value = '';
            if (nv) {
                fetch(GL_RXOPT_URL, {method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':GL_CSRF,'X-Requested-With':'XMLHttpRequest'},body:JSON.stringify({field:'glucose_reading_type',value:nv})}).catch(function(){});
                var addOpt = sel.querySelector('option[value="__add__"]');
                var grp = sel.querySelector('optgroup[label="Custom"]');
                if (!grp) { grp = document.createElement('optgroup'); grp.label = 'Custom'; sel.insertBefore(grp, addOpt); }
                if (!Array.prototype.slice.call(grp.children).some(function(o){return o.value===nv;})) {
                    var opt = document.createElement('option'); opt.value=nv; opt.textContent=nv; opt.setAttribute('data-unit','mg/dL'); grp.appendChild(opt);
                }
                sel.value = nv;
            }
        }
        syncUnit();
    });
    syncUnit();
})();

// ---- multi-pick chips: a chosen option becomes a removable chip; the joined,
// de-duplicated list is stored in the hidden field. "➕ Add new…" also persists
// the entry to the shared picklist via rx-options. Keyed by element key. ----
var GL_RXOPT_URL = @json(route('rx-options.store'));
var GL_RXPIN_URL = @json(route('rx-options.pin'));
var GL_CSRF      = @json(csrf_token());
var GL_BASE      = @json(route('glucose.index', $patient));
var GL_ENTRIES   = @json($entries);

function glEsc(s){return String(s).replace(/[&<>"']/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];});}
function glGet(key){var h=document.getElementById('gl-'+key);return h?h.value.split(',').map(function(s){return s.trim();}).filter(Boolean):[];}
function glRender(key,list){
    var c=document.getElementById('gl-'+key+'-chips'); if(!c) return;
    c.innerHTML=list.map(function(v){return '<span class="gl-tag">'+glEsc(v)+'<button type="button" data-key="'+key+'" data-val="'+glEsc(v)+'" title="remove">&times;</button></span>';}).join('');
}
function glSet(key,list){
    var seen={},out=[];
    list.forEach(function(v){var k=v.toLowerCase();if(v&&!seen[k]){seen[k]=1;out.push(v);}});
    var h=document.getElementById('gl-'+key); if(h) h.value=out.join(', ');
    glRender(key,out);
}
function glAdd(key,value){value=(value||'').replace(/,/g,' ').trim();if(!value)return;var l=glGet(key);l.push(value);glSet(key,l);}
// Food items carry a quantity AND the time eaten, stored as "name × N @ HH:MM".
// The count lets "aalu parotha" be logged 3× (instead of collapsing to one
// chip); the time records when that food was taken.
function glNowHM(){var d=new Date(),h=d.getHours(),m=('0'+d.getMinutes()).slice(-2);return (h%12||12)+':'+m+' '+(h>=12?'PM':'AM');}
// Normalise a typed time (24h "14:30", "8:30", or "8:30 pm") to "h:MM AM/PM".
function glFmtTime(t){
    t=String(t||'').trim(); if(!t)return '';
    var m=t.match(/^(\d{1,2})[:.](\d{2})\s*([ap]\.?m\.?)?$/i); if(!m)return t;
    var h=parseInt(m[1],10), mi=m[2], ap=(m[3]||'').toLowerCase();
    if(ap){ if(ap[0]==='p'&&h<12)h+=12; if(ap[0]==='a'&&h===12)h=0; }
    return (h%12||12)+':'+mi+' '+(h>=12?'PM':'AM');
}
function glFoodParse(s){
    var name=String(s).trim(),qty=1,time='';
    var mt=name.match(/@\s*([^@]+)$/);    if(mt){time=mt[1].trim(); name=name.slice(0,mt.index).trim();}
    var mq=name.match(/[×x]\s*(\d+)\s*$/);if(mq){qty=parseInt(mq[1],10)||1; name=name.slice(0,mq.index).trim();}
    return {name:name,qty:qty,time:time};
}
// One prompt for both count + time. Returns null if the user cancels.
// Food's time comes from its own date/time picker; column placement is by time.
function glFoodWhen(key){
    var dt=document.getElementById('gl-'+key+'-dt');
    if(dt && dt.value){
        var tm = dt.value.split('T')[1] || '';   // "HH:MM"
        return glFmtTime(tm) || glNowHM();
    }
    return glNowHM();
}
function glAskFoodQty(name){
    var r=prompt('How many "'+name+'"?','1'); if(r===null)return null;
    var q=parseInt(r,10); return (q&&q>0)?q:1;
}
function glAddFood(key,name,qty,time){
    name=(name||'').replace(/,/g,' ').trim();if(!name)return;qty=qty||1;time=glFmtTime(time);
    var label=name+(qty>1?(' × '+qty):'')+(time?(' @ '+time):''), nl=name.toLowerCase();
    // one entry per food: re-adding the same food REPLACES it, so editing its
    // time/qty moves the existing chip instead of creating a duplicate.
    var l=glGet(key).filter(function(x){return glFoodParse(x).name.toLowerCase()!==nl;});
    l.push(label); glSet(key,l);
}
function glAddSavedOption(sel,value){
    var addOpt=sel.querySelector('option[value="__add__"]');
    var grp=sel.querySelector('optgroup[label="Added"]');
    if(!grp){grp=document.createElement('optgroup');grp.label='Added';sel.insertBefore(grp,addOpt);}
    if(!Array.prototype.slice.call(grp.children).some(function(o){return o.value===value;})){var o=document.createElement('option');o.value=value;o.textContent=value;grp.appendChild(o);}
}
// Pin / unpin a drug to the top of the medicine picker (drug fields only).
function glPinPrompt(field){
    var pd=(prompt('Drug to pin / unpin at the top of this list:')||'').replace(/,/g,' ').trim();
    if(!pd)return;
    fetch(GL_RXPIN_URL,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':GL_CSRF,'X-Requested-With':'XMLHttpRequest'},body:JSON.stringify({field:field,value:pd})})
      .then(function(r){return r.json();})
      .then(function(d){ if(d&&d.value) glSetPinned(field,d.value,d.pinned); })
      .catch(function(){});
}
function glSetPinned(field,value,pinned){
    document.querySelectorAll('select[data-pinfield="'+field+'"]').forEach(function(sel){
        var grp=sel.querySelector('optgroup.gl-pinned-grp');
        if(pinned){
            if(!grp){ grp=document.createElement('optgroup'); grp.label='📌 Pinned'; grp.className='gl-pinned-grp'; grp.setAttribute('data-field',field); sel.insertBefore(grp, sel.children[1]||null); }
            if(!Array.prototype.slice.call(grp.children).some(function(o){return o.value===value;})){ var o=document.createElement('option'); o.value=value; o.textContent='📌 '+value; grp.appendChild(o); }
        } else if(grp){
            Array.prototype.slice.call(grp.children).forEach(function(o){ if(o.value===value) grp.removeChild(o); });
            if(!grp.children.length && grp.parentNode) grp.parentNode.removeChild(grp);
        }
        sel.value='';
    });
}
function glPick(sel,key,field){
    var v=sel.value; if(!v)return;
    if(v==='__pin__'){ sel.value=''; glPinPrompt(field); return; }
    if(v==='__add__'){
        var noun=field==='diabetes_med'?'drug':(field==='food_item'?'food item':'symptom');
        var nv=(prompt('Add a new '+noun+' (saved for next time):')||'').replace(/,/g,' ').trim();
        sel.value=''; if(!nv)return;
        if(field==='food_item'){var q=glAskFoodQty(nv); if(q!==null){glAddFood(key,nv,q,glFoodWhen(key));}}else{glAdd(key,nv);}
        glAddSavedOption(sel,nv);
        fetch(GL_RXOPT_URL,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':GL_CSRF,'X-Requested-With':'XMLHttpRequest'},body:JSON.stringify({field:field,value:nv})}).catch(function(){});
        return;
    }
    if(field==='food_item'){var q=glAskFoodQty(v); if(q!==null){glAddFood(key,v,q,glFoodWhen(key));} sel.value=''; return;}
    glAdd(key,v); sel.value='';
}
// delegated chip removal
document.addEventListener('click',function(e){
    var b=e.target.closest?e.target.closest('.gl-tag button'):null; if(!b)return;
    var key=b.getAttribute('data-key'),val=b.getAttribute('data-val');
    glSet(key, glGet(key).filter(function(x){return x!==val;}));
});
// click a FOOD chip (not its ×) to edit its quantity / time in place
function glIsFoodKey(k){return k==='meal_add'||k==='emeal';}
document.addEventListener('click',function(e){
    if(!e.target.closest)return;
    if(e.target.closest('.gl-tag button'))return;            // × removal handled above
    var tag=e.target.closest('.gl-tag'); if(!tag)return;
    var btn=tag.querySelector('button'); if(!btn)return;
    var key=btn.getAttribute('data-key'), val=btn.getAttribute('data-val');
    if(!glIsFoodKey(key))return;                             // only food chips are editable
    var p=glFoodParse(val);
    var raw=prompt('Edit "'+p.name+'" — quantity @ time:', p.qty+' @ '+(p.time||glNowHM()));
    if(raw===null)return;
    var qty=1,time=''; raw=raw.trim();
    var mt=raw.match(/@\s*(.+)$/); if(mt){time=mt[1].trim(); raw=raw.slice(0,mt.index).trim();}
    var q=parseInt(raw,10); if(q&&q>0)qty=q;
    glSet(key, glGet(key).filter(function(x){return x!==val;}));  // drop old
    glAddFood(key,p.name,qty,time);                              // re-add edited (name-only replace)
});
// seed add-form chips from any repopulated (old) values
glSet('medications', glGet('medications'));
glSet('symptoms', glGet('symptoms'));
glSet('meal_add', glGet('meal_add'));

// ---- edit modal ----
function glEdit(id){
    var e=GL_ENTRIES[id]; if(!e)return;
    var f=document.getElementById('gl-edit-form');
    f.action=GL_BASE+'/'+id;
    document.getElementById('gl-delete-form').action=GL_BASE+'/'+id;
    f.querySelector('[name=logged_at]').value=e.logged_at||'';
    f.querySelector('[name=reading_type]').value=e.reading_type||'';
    f.querySelector('[name=value]').value=(e.value==null?'':e.value);
    f.querySelector('[name=insulin_units]').value=(e.insulin_units==null?'':e.insulin_units);
    glSet('emeal',(e.meal_notes||'').split(',').map(function(s){return s.trim();}).filter(Boolean));
    var emealDt=document.getElementById('gl-emeal-dt'); if(emealDt) emealDt.value=e.logged_at||'';
    f.querySelector('[name=notes]').value=e.notes||'';
    glSet('emed',(e.medications||'').split(',').map(function(s){return s.trim();}).filter(Boolean));
    glSet('esym',(e.symptoms||'').split(',').map(function(s){return s.trim();}).filter(Boolean));
    document.getElementById('gl-edit-modal').classList.add('open');
}
function glCloseEdit(){document.getElementById('gl-edit-modal').classList.remove('open');}
// Inline-edit a single food's time straight from the grid (no modal needed).
function glFoodTime(id, food, time){
    var nt=prompt('Time eaten for "'+food+'"  (e.g. 1:40 PM — blank to clear):', time||'');
    if(nt===null)return;
    fetch(GL_BASE+'/'+id+'/food-time',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':GL_CSRF,'X-Requested-With':'XMLHttpRequest'},body:JSON.stringify({food:food,time:nt})})
      .then(function(r){return r.json();})
      .then(function(d){ if(d&&d.ok){ location.reload(); } else { alert('Could not update the time.'); } })
      .catch(function(){ alert('Could not update the time — please try again.'); });
}
function glDeleteFromModal(){ if(confirm('Delete this entry?')) document.getElementById('gl-delete-form').submit(); }
</script>

@if($points->isNotEmpty())
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(function () {
    var pts = @json($points);
    var ctx = document.getElementById('glChart');
    if (!ctx) return;
    var labels = pts.map(function (p) { return p.label; });
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Blood sugar (mg/dL)',
                    data: pts.map(function (p) { return p.y; }),
                    borderColor: '#1e3360', backgroundColor: 'rgba(30,51,96,.06)',
                    pointBackgroundColor: pts.map(function (p) { return p.color; }),
                    pointBorderColor: pts.map(function (p) { return p.color; }),
                    pointRadius: 5, pointHoverRadius: 7, tension: 0.25, fill: true, order: 1
                },
                { label: 'Low (70)',  data: labels.map(function () { return 70; }),  borderColor: '#dc2626', borderDash: [6,4], pointRadius: 0, fill: false, order: 2 },
                { label: 'High (180)', data: labels.map(function () { return 180; }), borderColor: '#d97706', borderDash: [6,4], pointRadius: 0, fill: false, order: 3 }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: true, labels: { boxWidth: 12, font: { size: 11 } } },
                tooltip: { callbacks: { label: function (c) {
                    if (c.datasetIndex !== 0) return c.dataset.label;
                    return (pts[c.dataIndex].type || 'Reading') + ': ' + c.parsed.y + ' mg/dL';
                } } }
            },
            scales: {
                x: { ticks: { font: { size: 10 }, maxRotation: 60, minRotation: 0 } },
                y: { title: { display: true, text: 'mg/dL' }, ticks: { font: { size: 10 } }, beginAtZero: false }
            }
        }
    });
})();
</script>
@endif
<script>
(function () {
    var warns = document.querySelectorAll('.gl-advice.gl-warn');
    var all   = document.querySelectorAll('.gl-advice');
    if (!all.length) return;
    var flashed = 0;
    if (warns.length) {
        var iv = setInterval(function () {
            flashed++;
            warns.forEach(function (el) {
                el.style.boxShadow = (flashed % 2 === 1) ? '0 0 0 3px rgba(220,38,38,.45)' : 'none';
            });
            if (flashed >= 4) clearInterval(iv);
        }, 550);
    }
    setTimeout(function () {
        all.forEach(function (el) {
            el.style.overflow   = 'hidden';
            el.style.maxHeight  = el.offsetHeight + 'px';
            el.style.transition = 'opacity 1.5s ease, max-height 1.5s ease, margin-bottom 1.5s ease, padding 1.5s ease';
            setTimeout(function () {
                el.style.opacity      = '0';
                el.style.maxHeight    = '0';
                el.style.marginBottom = '0';
                el.style.padding      = '0';
            }, 30);
        });
    }, 10000);
})();
scTrackPatient({{ $patient->id }}, @json($patient->full_name), @json($patient->jhc_id), @json(route('patients.show', $patient)));
window.scPatientVisitUrl = @json(route('appointments.create', ['patient_id' => $patient->id]));
window.scNewEntryOverride = function () {
    var el = document.querySelector('input[name="logged_at"]');
    if (el) { el.scrollIntoView({ behavior: 'smooth', block: 'center' }); el.focus(); }
};
</script>
@endsection
