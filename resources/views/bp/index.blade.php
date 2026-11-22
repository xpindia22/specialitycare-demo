@extends('layouts.app')
@section('title', 'BP Diary — ' . $patient->full_name)
@section('page-title', 'Blood-Pressure Diary')
@section('patient-context')
@include('partials._patient_ctx', ['patient' => $patient, 'active' => 'bp'])
@endsection
@section('content')

<style>
    .bp-badge{display:inline-block;border:1.5px solid var(--c);color:var(--c);background:#fff;border-radius:999px;padding:1px 10px;font-size:12px;font-weight:700;white-space:nowrap}
    .gl-advice{display:flex;gap:10px;align-items:flex-start;padding:10px 14px;border-radius:8px;margin-bottom:8px;font-size:13.5px;line-height:1.5}
    .gl-advice i{margin-top:2px}
    .gl-warn{background:#fef2f2;border:1px solid #fecaca;color:#991b1b}
    .gl-good{background:#f0fdf4;border:1px solid #bbf7d0;color:#166534}
    .gl-info{background:#f1f5f9;border:1px solid #e2e8f0;color:#334155}
    .gl-stat{flex:1;min-width:110px;background:var(--sc-bg,#f8fafc);border-radius:10px;padding:12px 14px;text-align:center}
    .gl-stat .v{font-size:24px;font-weight:800;line-height:1.1}
    .gl-stat .k{font-size:11px;color:#64748b;text-transform:uppercase;letter-spacing:.04em;margin-top:3px}
    .gl-statrow{display:flex;gap:12px;flex-wrap:wrap;margin-bottom:16px}
    .gl-disclaimer{font-size:11.5px;color:#94a3b8;margin-top:10px;line-height:1.5}
    .gl-chips{display:flex;flex-wrap:wrap;gap:5px;margin-bottom:6px}
    .gl-chips:empty{display:none}
    .gl-tag{display:inline-flex;align-items:center;gap:6px;background:#eef2ff;border:1px solid #c7d2fe;color:#3730a3;border-radius:999px;padding:2px 6px 2px 10px;font-size:12px;font-weight:600}
    .gl-tag button{border:none;background:transparent;color:#6366f1;cursor:pointer;font-size:15px;line-height:1;padding:0}
    .gl-tag button:hover{color:#dc2626}
    tr.bp-row{cursor:pointer}
    tr.bp-row:hover{background:#eef2ff}
    /* Column-by-time diary grid (shared look with the glucose diary) */
    .gl-cell{display:flex;flex-direction:column;gap:3px;align-items:center}
    .gl-grid td,.gl-grid th{text-align:center;vertical-align:top}
    .gl-grid td.gl-date,.gl-grid th.gl-date{text-align:left;white-space:nowrap;min-width:110px}
    .gl-meds{font-size:12px;text-align:left;color:#334155}
    .gl-meds .gl-time{color:#94a3b8;font-size:10px;font-weight:600;margin-right:4px}
    .gl-grid th.gl-notes,.gl-grid td.gl-notes{width:120px;min-width:96px;max-width:150px;white-space:normal;word-wrap:break-word;text-align:left;font-size:10.5px;line-height:1.35}
    .gl-rdg{display:flex;flex-direction:column;align-items:center;gap:2px;margin-top:4px}
    .gl-rdg .gl-rtime{font-size:10px;color:#64748b;font-weight:500;font-variant-numeric:tabular-nums}
    .gl-rdg .gl-pulse{font-size:11px;color:#64748b;font-weight:600}
    .gl-rmeds{font-size:11px;color:#475569;text-align:center;line-height:1.5;margin-top:1px}
    .gl-rmeds .gl-time{color:#94a3b8;font-weight:400}
    .gl-editable{cursor:pointer;border-radius:4px;padding:1px 3px}
    .gl-editable:hover{background:#eef2ff}
</style>

<div class="page-header">
    <div class="page-header-left">
        <h1>Blood-Pressure Diary</h1>
        <div class="breadcrumb"><a href="{{ route('patients.show', $patient) }}">{{ $patient->full_name }}</a> &rsaquo; BP Diary</div>
    </div>
    @if($logs->isNotEmpty())
        <a href="{{ route('bp.print', $patient) }}" target="_blank" class="btn btn-outline btn-sm"><i class="fa-solid fa-print"></i> Print</a>
    @endif
    <a href="{{ route('glucose.index', $patient) }}" class="btn btn-sm" style="background:#0891b2;color:#fff;border-color:#0891b2"><i class="fa-solid fa-droplet"></i> Diabetes Diary</a>
    <a href="{{ route('health.index', $patient) }}"  class="btn btn-sm" style="background:#059669;color:#fff;border-color:#059669"><i class="fa-solid fa-heart"></i> Health Tracker</a>
    <a href="{{ route('patients.show', $patient) }}" class="btn btn-outline btn-sm"><i class="fa-solid fa-arrow-left"></i> Back</a>
</div>

@if(session('success'))<div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> {{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger"><i class="fa-solid fa-circle-xmark"></i> {{ session('error') }}</div>@endif


{{-- Add entry --}}
<div class="card mb-3">
    <div class="card-header"><i class="fa-solid fa-plus"></i> Add reading</div>
    <div class="card-body">
        <form method="POST" action="{{ route('bp.store', $patient) }}">
            @csrf
            <div class="form-row" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px">
                <div class="form-group" style="margin:0">
                    <label class="form-label">Date &amp; time <span class="req">*</span></label>
                    <input type="datetime-local" name="logged_at" class="form-control"
                           value="{{ old('logged_at', now()->format('Y-m-d\TH:i')) }}" required>
                </div>
                <div class="form-group" style="margin:0">
                    <label class="form-label">Systolic <span class="text-muted" style="font-weight:400">(upper)</span></label>
                    <input type="number" min="50" max="300" name="systolic" class="form-control" placeholder="e.g. 130" value="{{ old('systolic', 120) }}">
                </div>
                <div class="form-group" style="margin:0">
                    <label class="form-label">Diastolic <span class="text-muted" style="font-weight:400">(lower)</span></label>
                    <input type="number" min="30" max="200" name="diastolic" class="form-control" placeholder="e.g. 85" value="{{ old('diastolic', 80) }}">
                </div>
                <div class="form-group" style="margin:0">
                    <label class="form-label">Pulse (/min)</label>
                    <input type="number" min="20" max="300" name="pulse" class="form-control" placeholder="e.g. 76" value="{{ old('pulse', 80) }}">
                </div>
                @include('glucose._picker', ['key'=>'medications','field'=>'bp_med','label'=>'Medicine taken','name'=>'medications','options'=>\App\Models\BpLog::MEDICINES,'extras'=>$medExtras,'pinned'=>$medPinned,'value'=>old('medications')])
                @include('glucose._picker', ['key'=>'symptoms','field'=>'bp_symptom','label'=>'Symptoms','name'=>'symptoms','options'=>\App\Models\BpLog::SYMPTOMS,'extras'=>$symptomExtras,'value'=>old('symptoms')])
                <div class="form-group" style="margin:0;grid-column:1/-1">
                    <label class="form-label">Notes</label>
                    <input type="text" name="notes" class="form-control" placeholder="position, arm, activity, anything to remember (optional)" value="{{ old('notes', 'BP checked in supine position in the left arm') }}">
                </div>
            </div>
            <div style="margin-top:12px">
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save reading</button>
                <span class="text-muted text-small" style="margin-left:8px">Tip: measure at the same times each day (e.g. morning &amp; evening), seated and rested.</span>
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
                <div class="gl-stat"><div class="v">{{ $s['avgSys'] }}/{{ $s['avgDia'] }}</div><div class="k">Avg mmHg</div></div>
                <div class="gl-stat"><div class="v" style="color:#16a34a">{{ $s['goalPct'] }}%</div><div class="k">&lt;140/90</div></div>
                <div class="gl-stat"><div class="v" style="color:#dc2626">{{ $s['stage2'] }}</div><div class="k">Stage 2</div></div>
                <div class="gl-stat"><div class="v" style="color:#7f1d1d">{{ $s['crisis'] }}</div><div class="k">Crisis</div></div>
                <div class="gl-stat"><div class="v" style="color:#2563eb">{{ $s['low'] }}</div><div class="k">Low</div></div>
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
            Categories: normal ≤120/80, elevated 121–129/≤80, stage 1 ≥130 or &gt;80, stage 2 ≥140/90, crisis ≥180/120, low &lt;90/60.
            Guidance only — it does not replace the treating doctor's advice. Home and clinic targets may differ.
        </div>
    </div>
</div>
@endif

{{-- AI medicine suggestions --}}
@include('partials._ai_suggestions', ['patient' => $patient, 'focus' => 'bp'])

{{-- Trend chart --}}
@if($points->isNotEmpty())
<div class="card mb-3">
    <div class="card-header"><i class="fa-solid fa-chart-line"></i> BP trend
        <span class="text-muted" style="font-weight:400;font-size:11px;margin-left:6px">{{ $points->count() }} readings</span>
    </div>
    <div class="card-body"><canvas id="bpChart" height="90"></canvas></div>
</div>
@endif

{{-- Log table --}}
@if($logs->isEmpty())
    <x-empty-state icon="fa-heart-pulse" title="No BP entries yet"
        message="Use “Add reading” above to record blood pressure and the medicines taken. No encounter is needed." />
@else
<div class="card">
    <div class="card-header"><i class="fa-solid fa-table-list"></i> Day-by-day log
        <span class="text-muted" style="font-weight:400;font-size:11px;margin-left:6px">each reading sits in the column for the time it was checked</span>
    </div>
    <div class="table-wrap">
        <table class="sc-table gl-grid">
            <thead>
                <tr>
                    <th class="gl-date">Date</th>
                    @foreach(\App\Models\BpLog::DIARY_COLUMNS as $dc)
                        <th style="background:{{ $dc['color'] }}">{{ $dc['label'] }}<div class="text-muted" style="font-weight:400;font-size:9px">{{ $dc['range'] }}</div></th>
                    @endforeach
                    <th class="gl-notes" style="background:{{ \App\Models\BpLog::NOTES_COLUMN_COLOR }}">Notes-Symptoms</th>
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
                    @foreach(\App\Models\BpLog::DIARY_COLUMNS as $ci => $dc)
                        @php $cellReadings = $readingsByDayCol[$day['date']][$ci] ?? collect(); @endphp
                        <td style="background:{{ $dc['color'] }};{{ $sep }}">
                            <div class="gl-cell">
                                @foreach($cellReadings as $r)
                                    @php $c = $r->classification(); $t = $r->logged_at->format('h:i A'); @endphp
                                    <div class="gl-rdg gl-editable" onclick="bpEdit({{ $r->id }})" title="{{ $c['label'] }}{{ $r->recordedBy ? ' · '.$r->recordedBy->name : '' }} — click to edit / delete">
                                        <span class="bp-badge" style="--c:{{ $c['color'] }}">{{ $r->reading() }}</span>
                                        @if($r->pulse)<span class="gl-pulse"><i class="fa-solid fa-heart-pulse" style="font-size:9px;color:#dc2626"></i> {{ $r->pulse }}</span>@endif
                                        <span class="gl-rtime">{{ $t }}</span>
                                    </div>
                                    @if($r->medications)
                                        <div class="gl-rmeds gl-editable" onclick="bpEdit({{ $r->id }})" title="click to edit / delete">
                                            @foreach(array_filter(array_map('trim', explode(',', $r->medications))) as $med)
                                                <div>{{ $med }} <span class="gl-time">({{ $t }})</span></div>
                                            @endforeach
                                        </div>
                                    @endif
                                @endforeach
                                @if($cellReadings->isEmpty())<span class="text-muted">—</span>@endif
                            </div>
                        </td>
                    @endforeach
                    <td class="gl-meds gl-notes" style="background:{{ \App\Models\BpLog::NOTES_COLUMN_COLOR }};{{ $sep }}">
                        @foreach($day['meds'] as $r)
                            <div class="gl-editable" onclick="bpEdit({{ $r->id }})" title="medicine only — click to edit / delete">
                                <span class="gl-time">{{ $r->logged_at->format('h:i A') }}</span>{{ $r->medications }}
                            </div>
                        @endforeach
                        @foreach($day['notes'] as $r)
                            <div class="gl-editable" style="font-size:11px" onclick="bpEdit({{ $r->id }})" title="click to edit / delete">
                                <span class="gl-time">{{ $r->logged_at->format('h:i A') }}</span>
                                @if($r->symptoms)<span style="color:#b91c1c">{{ $r->symptoms }}</span>@endif
                                @if($r->notes)<span class="text-muted">@if($r->symptoms) · @endif{{ $r->notes }}</span>@endif
                            </div>
                        @endforeach
                        @if($day['meds']->isEmpty() && $day['notes']->isEmpty())<span class="text-muted">—</span>@endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- Edit modal --}}
<div id="bp-edit-modal" class="modal-overlay">
    <div class="modal-box" style="max-width:560px">
        <div class="modal-header">
            <i class="fa-solid fa-pen" style="color:var(--sc-teal)"></i>
            <h3>Edit reading</h3>
            <button class="modal-close" type="button" onclick="bpCloseEdit()">&times;</button>
        </div>
        <form id="bp-edit-form" method="POST">
            @csrf
            @method('PUT')
            <div class="modal-body" style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px">
                <div class="form-group" style="margin:0;grid-column:1/-1">
                    <label class="form-label">Date &amp; time <span class="req">*</span></label>
                    <input type="datetime-local" name="logged_at" class="form-control" required>
                </div>
                <div class="form-group" style="margin:0">
                    <label class="form-label">Systolic</label>
                    <input type="number" min="50" max="300" name="systolic" class="form-control">
                </div>
                <div class="form-group" style="margin:0">
                    <label class="form-label">Diastolic</label>
                    <input type="number" min="30" max="200" name="diastolic" class="form-control">
                </div>
                <div class="form-group" style="margin:0">
                    <label class="form-label">Pulse</label>
                    <input type="number" min="20" max="300" name="pulse" class="form-control">
                </div>
                <div style="grid-column:1/-1">
                    @include('glucose._picker', ['key'=>'emed','field'=>'bp_med','label'=>'Medicine taken','name'=>'medications','options'=>\App\Models\BpLog::MEDICINES,'extras'=>$medExtras,'pinned'=>$medPinned,'value'=>''])
                </div>
                <div style="grid-column:1/-1">
                    @include('glucose._picker', ['key'=>'esym','field'=>'bp_symptom','label'=>'Symptoms','name'=>'symptoms','options'=>\App\Models\BpLog::SYMPTOMS,'extras'=>$symptomExtras,'value'=>''])
                </div>
                <div class="form-group" style="margin:0;grid-column:1/-1">
                    <label class="form-label">Notes</label>
                    <input type="text" name="notes" class="form-control">
                </div>
            </div>
            <div class="modal-footer" style="justify-content:space-between">
                <button type="button" class="btn btn-outline" style="color:var(--sc-red);border-color:var(--sc-red)" onclick="bpDeleteFromModal()"><i class="fa-solid fa-trash"></i> Delete</button>
                <span>
                    <button type="button" class="btn btn-outline" onclick="bpCloseEdit()">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save changes</button>
                </span>
            </div>
        </form>
    </div>
</div>
<form id="bp-delete-form" method="POST" style="display:none">@csrf @method('DELETE')</form>

<script>
// ---- multi-pick chips (shared logic, keyed by element key) ----
var GL_RXOPT_URL = @json(route('rx-options.store'));
var GL_RXPIN_URL = @json(route('rx-options.pin'));
var GL_CSRF      = @json(csrf_token());
var BP_BASE      = @json(route('bp.index', $patient));
var BP_ENTRIES   = @json($entries);

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
        var noun=field==='bp_med'?'drug':'symptom';
        var nv=(prompt('Add a new '+noun+' (saved for next time):')||'').replace(/,/g,' ').trim();
        sel.value=''; if(!nv)return;
        glAdd(key,nv); glAddSavedOption(sel,nv);
        fetch(GL_RXOPT_URL,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':GL_CSRF,'X-Requested-With':'XMLHttpRequest'},body:JSON.stringify({field:field,value:nv})}).catch(function(){});
        return;
    }
    glAdd(key,v); sel.value='';
}
document.addEventListener('click',function(e){
    var b=e.target.closest?e.target.closest('.gl-tag button'):null; if(!b)return;
    e.stopPropagation();
    var key=b.getAttribute('data-key'),val=b.getAttribute('data-val');
    glSet(key, glGet(key).filter(function(x){return x!==val;}));
});
glSet('medications', glGet('medications'));
glSet('symptoms', glGet('symptoms'));

// ---- edit modal ----
function bpEdit(id){
    var e=BP_ENTRIES[id]; if(!e)return;
    var f=document.getElementById('bp-edit-form');
    f.action=BP_BASE+'/'+id;
    document.getElementById('bp-delete-form').action=BP_BASE+'/'+id;
    f.querySelector('[name=logged_at]').value=e.logged_at||'';
    f.querySelector('[name=systolic]').value=(e.systolic==null?'':e.systolic);
    f.querySelector('[name=diastolic]').value=(e.diastolic==null?'':e.diastolic);
    f.querySelector('[name=pulse]').value=(e.pulse==null?'':e.pulse);
    f.querySelector('[name=notes]').value=e.notes||'';
    glSet('emed',(e.medications||'').split(',').map(function(s){return s.trim();}).filter(Boolean));
    glSet('esym',(e.symptoms||'').split(',').map(function(s){return s.trim();}).filter(Boolean));
    document.getElementById('bp-edit-modal').classList.add('open');
}
function bpCloseEdit(){document.getElementById('bp-edit-modal').classList.remove('open');}
function bpDeleteFromModal(){ if(confirm('Delete this reading?')) document.getElementById('bp-delete-form').submit(); }
</script>

@if($points->isNotEmpty())
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(function () {
    var pts = @json($points);
    var ctx = document.getElementById('bpChart');
    if (!ctx) return;
    var labels = pts.map(function (p) { return p.label; });
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                { label: 'Systolic',  data: pts.map(function (p) { return p.sys; }), borderColor: '#dc2626', backgroundColor: 'rgba(220,38,38,.05)', pointRadius: 4, tension: 0.25, fill: false, order: 1 },
                { label: 'Diastolic', data: pts.map(function (p) { return p.dia; }), borderColor: '#2563eb', backgroundColor: 'rgba(37,99,235,.05)', pointRadius: 4, tension: 0.25, fill: false, order: 2 },
                { label: 'SBP goal (140)', data: labels.map(function () { return 140; }), borderColor: '#d97706', borderDash: [6,4], pointRadius: 0, fill: false, order: 3 },
                { label: 'DBP goal (90)',  data: labels.map(function () { return 90; }),  borderColor: '#f59e0b', borderDash: [4,4], pointRadius: 0, fill: false, order: 4 }
            ]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: true, labels: { boxWidth: 12, font: { size: 11 } } } },
            scales: {
                x: { ticks: { font: { size: 10 }, maxRotation: 60, minRotation: 0 } },
                y: { title: { display: true, text: 'mmHg' }, ticks: { font: { size: 10 } }, beginAtZero: false }
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
    var el = document.querySelector('input[name="systolic"]');
    if (el) { el.scrollIntoView({ behavior: 'smooth', block: 'center' }); el.focus(); }
};
</script>
@endsection
