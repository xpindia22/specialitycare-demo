{{-- FILE: resources/views/triage/index.blade.php — Nursing Station (merged OPD queue + triage) --}}
@extends('layouts.app')
@section('title','Nursing Station')
@section('page-title','Nursing Station')
@section('content')

<div class="page-header">
    <div class="page-header-left"><h1>Nursing Station</h1></div>
    @include('opd._toolbar', ['inline'=>true])
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

{{-- Stats bar --}}
<div class="ns-stats">
    <div class="ns-stat ns-stat-total">  <div class="ns-stat-num">{{ $stats['total'] }}</div>           <div class="ns-stat-lbl">Total Today</div></div>
    <div class="ns-stat ns-stat-waiting"><div class="ns-stat-num">{{ $stats['waiting'] }}</div>          <div class="ns-stat-lbl">Waiting</div></div>
    <div class="ns-stat ns-stat-active"> <div class="ns-stat-num">{{ $stats['in_consultation'] }}</div>  <div class="ns-stat-lbl">In Consult</div></div>
    <div class="ns-stat ns-stat-triage"> <div class="ns-stat-num">{{ $stats['pending_triage'] }}</div>   <div class="ns-stat-lbl">Pending Triage</div></div>
    <div class="ns-stat ns-stat-done">   <div class="ns-stat-num">{{ $stats['done'] }}</div>             <div class="ns-stat-lbl">Done</div></div>
    <span id="refresh-badge" style="margin-left:auto;font-size:11px;color:#999;white-space:nowrap"><i class="fa-solid fa-rotate" style="font-size:10px"></i> refreshing in <span id="refresh-countdown">45</span>s</span>
</div>

<div class="ns-layout">

    {{-- Issue Token Form --}}
    <div class="card ns-form-card">
        <div class="card-header"><strong><i class="fa-solid fa-ticket me-1"></i>Issue Token</strong></div>
        <div class="card-body">
            <form method="POST" action="{{ route('opd.store') }}" id="tokenForm">
                @csrf
                <div class="form-group" style="position:relative">
                    <label class="form-label">Patient <span class="text-danger">*</span></label>
                    <input type="text" id="patientSearch" class="form-control" placeholder="Search by name, ID or phone…" autocomplete="off">
                    <input type="hidden" name="patient_id" id="patientId">
                    <div id="patientSuggestions" class="search-dropdown" style="display:none"></div>
                    <div id="selectedPatient" class="selected-patient-box" style="display:none"></div>
                </div>
                <div class="form-group mt-3">
                    <label class="form-label">Speciality</label>
                    <select name="department_id" id="tokenDept" class="form-control" onchange="filterTokenDoctors()">
                        <option value="">— Any Speciality —</option>
                        @foreach($departments as $dep)
                            <option value="{{ $dep->id }}">{{ $dep->name }}{{ $dep->token_prefix ? ' ('.$dep->token_prefix.'-tokens)' : '' }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group mt-3">
                    <label class="form-label">Doctor</label>
                    <select name="doctor_id" id="tokenDoctor" class="form-control">
                        <option value="">— Any Doctor —</option>
                        @foreach($doctors as $d)
                            <option value="{{ $d->id }}" data-depts="{{ implode(',', $d->departmentIds()) }}">Dr. {{ $d->first_name }} {{ $d->last_name }}</option>
                        @endforeach
                    </select>
                </div>
                <script>
                // Speciality → only that department's doctors
                function filterTokenDoctors() {
                    const dep = document.getElementById('tokenDept').value, sel = document.getElementById('tokenDoctor');
                    Array.from(sel.options).forEach(o => { o.hidden = !!(o.value && dep && !(o.dataset.depts || '').split(',').includes(dep)); });
                    if (sel.selectedOptions[0]?.hidden) sel.value = '';
                    const vis = Array.from(sel.options).filter(o => o.value && !o.hidden);
                    if (dep && vis.length === 1) sel.value = vis[0].value;
                }
                </script>
                <div class="form-group mt-3">
                    <label class="form-label">Priority</label>
                    <div class="d-flex gap-3 mt-1">
                        <label class="d-flex align-items-center gap-1" style="cursor:pointer">
                            <input type="radio" name="priority" value="normal" checked> Normal
                        </label>
                        <label class="d-flex align-items-center gap-1" style="cursor:pointer;color:#c00">
                            <input type="radio" name="priority" value="urgent"> <strong>Urgent</strong>
                        </label>
                    </div>
                </div>
                <div class="form-group mt-3">
                    <label class="form-label">Purpose / Chief Complaint</label>
                    <input type="text" name="purpose" class="form-control" placeholder="e.g. Fever, Follow-up, BP check…">
                </div>
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary w-100" id="issueBtn" disabled>
                        <i class="fa-solid fa-ticket me-1"></i>Issue Token
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Right panel: Pending Triage + Active Queue --}}
    <div>

        {{-- Pending Triage --}}
        <div class="card mb-3">
            <div class="card-header" style="background:#fffbeb;border-bottom:1px solid #fde68a">
                <strong><i class="fa-solid fa-triangle-exclamation me-1" style="color:#d97706"></i>Pending Triage
                @if($stats['pending_triage'] > 0)
                    <span class="badge" style="background:#fef3c7;color:#92400e;margin-left:6px">{{ $stats['pending_triage'] }}</span>
                @endif
                </strong>
            </div>
            <div class="table-wrap">
                <table class="sc-table">
                    <thead>
                        <tr>
                            <th style="width:8%">Token</th>
                            <th>Patient</th>
                            <th style="width:16%">Doctor</th>
                            <th style="width:10%">Priority</th>
                            <th style="width:10%">Status</th>
                            <th style="width:10%">Triage</th>
                            <th style="width:10%">Time</th>
                            <th style="width:10%"></th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($tokens as $t)
                    <tr class="{{ $t->priority === 'urgent' ? 'triage-urgent-row' : '' }}">
                        <td class="mono fw-600 text-center" style="font-size:15px">{{ $t->display }}</td>
                        <td>
                            <div class="fw-600">{{ $t->patient->first_name }} {{ $t->patient->last_name }}</div>
                            <div style="font-size:11px;color:#666">{{ $t->patient->jhc_id }}</div>
                            @if($t->purpose)<div style="font-size:11px;color:#888;font-style:italic">{{ $t->purpose }}</div>@endif
                        </td>
                        <td style="font-size:12px">{{ $t->doctor ? 'Dr. '.$t->doctor->first_name.' '.$t->doctor->last_name : '—' }}
                            @if($t->department)
                            <div style="font-size:11px;font-weight:600;color:{{ $t->department->color ?: '#666' }}"><i class="fa-solid {{ $t->department->icon ?: 'fa-hospital' }}"></i> {{ $t->department->name }}@if($t->department->room) &middot; {{ $t->department->room }}@endif</div>
                            @endif
                        </td>
                        <td>
                            @if($t->priority === 'urgent')
                                <span class="badge badge-red"><i class="fa-solid fa-circle-exclamation me-1"></i>Urgent</span>
                            @else
                                <span class="badge badge-grey">Normal</span>
                            @endif
                        </td>
                        <td><span class="badge opd-badge-{{ $t->status }}">{{ ucwords(str_replace('_', ' ', $t->status)) }}</span></td>
                        <td>
                            @if($t->is_triaged && $t->triage)
                                <span class="triage-pill triage-{{ $t->triage->triage_level }}">{{ strtoupper($t->triage->triage_level) }}</span>
                            @else
                                <span style="font-size:11px;color:#aaa">Pending</span>
                            @endif
                        </td>
                        <td style="font-size:11px;color:#666">{{ $t->created_at->format('h:i A') }}</td>
                        <td>
                            <div class="d-flex gap-1">
                                @if($t->is_triaged)
                                    <a href="{{ route('triage.show', $t) }}" class="btn btn-sm btn-outline" title="View Triage"><i class="fa-solid fa-eye"></i></a>
                                    <a href="{{ route('triage.create', $t) }}" class="btn btn-sm btn-outline" title="Re-Triage"><i class="fa-solid fa-rotate"></i></a>
                                @else
                                    <a href="{{ route('triage.create', $t) }}" class="btn btn-sm btn-primary" title="Triage Now">
                                        <i class="fa-solid fa-notes-medical me-1"></i>Triage
                                    </a>
                                @endif
                                @php
                                    $qvData = $t->triage ? ['bp_s'=>$t->triage->bp_systolic,'bp_d'=>$t->triage->bp_diastolic,'pulse'=>$t->triage->pulse,'temp'=>$t->triage->temperature,'spo2'=>$t->triage->spo2,'weight'=>$t->triage->weight] : (object)[];
                                @endphp
                                <button class="btn btn-sm btn-outline" title="Quick Vitals"
                                    onclick="openQV({{ $t->id }}, '{{ route('triage.quick-vitals', $t) }}', '{{ addslashes($t->patient->first_name.' '.$t->patient->last_name) }}', @json($qvData))">
                                    <i class="fa-solid fa-heart-pulse"></i>
                                </button>
                                <a href="{{ route('opd.slip', $t) }}" class="btn btn-sm btn-outline" target="_blank" title="Print Slip"><i class="fa-solid fa-print"></i></a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center text-muted" style="padding:30px">No active patients right now.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Completed Today --}}
        @if($done->count())
        <div class="card">
            <div class="card-header" style="background:#f8f9fa;border-bottom:1px solid #e0e0e0">
                <strong style="color:#666"><i class="fa-solid fa-circle-check me-1"></i>Completed Today</strong>
            </div>
            <div class="table-wrap">
                <table class="sc-table">
                    <thead>
                        <tr>
                            <th style="width:8%">Token</th>
                            <th>Patient</th>
                            <th style="width:12%">Triage</th>
                            <th style="width:11%">BP</th>
                            <th style="width:9%">Pulse</th>
                            <th style="width:9%">Temp</th>
                            <th style="width:9%">SpO₂</th>
                            <th style="width:9%">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($done as $t)
                    <tr>
                        <td class="mono fw-600 text-center">{{ $t->display }}</td>
                        <td>
                            <div class="fw-600" style="font-size:13px">{{ $t->patient->first_name }} {{ $t->patient->last_name }}</div>
                            <div style="font-size:11px;color:#888">{{ $t->patient->jhc_id }}</div>
                        </td>
                        <td>
                            @if($t->triage)
                                <span class="triage-pill triage-{{ $t->triage->triage_level }}">{{ strtoupper($t->triage->triage_level) }}</span>
                            @else
                                <span style="color:#aaa;font-size:12px">—</span>
                            @endif
                        </td>
                        <td style="font-size:12px">{{ $t->triage?->bp ?? '—' }}</td>
                        <td style="font-size:12px">{{ $t->triage?->pulse ? $t->triage->pulse.' bpm' : '—' }}</td>
                        <td style="font-size:12px">{{ $t->triage?->temperature ? $t->triage->temperature.'°F' : '—' }}</td>
                        <td style="font-size:12px">{{ $t->triage?->spo2 ? $t->triage->spo2.'%' : '—' }}</td>
                        <td><span class="badge opd-badge-{{ $t->status }}">{{ ucwords(str_replace('_', ' ', $t->status)) }}</span></td>
                    </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

    </div>{{-- end right panel --}}
</div>{{-- end ns-layout --}}

{{-- Quick Vitals Modal --}}
<div id="qv-modal" style="display:none;position:fixed;inset:0;z-index:1050;background:rgba(0,0,0,.45);align-items:center;justify-content:center">
    <div style="background:#fff;border-radius:10px;width:440px;max-width:94vw;padding:0;box-shadow:0 8px 32px rgba(0,0,0,.2)">
        <div style="padding:14px 18px;border-bottom:1px solid #e0e0e0;display:flex;align-items:center;justify-content:space-between">
            <div>
                <div style="font-weight:700;font-size:14px"><i class="fa-solid fa-heart-pulse" style="color:#0ab4b4;margin-right:6px"></i>Quick Vitals</div>
                <div id="qv-patient-name" style="font-size:12px;color:#666;margin-top:2px"></div>
            </div>
            <button onclick="closeQV()" style="background:none;border:none;font-size:18px;cursor:pointer;color:#888">&times;</button>
        </div>
        <form id="qv-form" onsubmit="submitQV(event)" style="padding:18px">
            @csrf
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                <div>
                    <label style="font-size:11px;font-weight:600;color:#555;display:block;margin-bottom:4px">BP Systolic</label>
                    <input type="number" id="qv-bp-s" name="bp_systolic" class="form-control" placeholder="mmHg" min="40" max="300">
                </div>
                <div>
                    <label style="font-size:11px;font-weight:600;color:#555;display:block;margin-bottom:4px">BP Diastolic</label>
                    <input type="number" id="qv-bp-d" name="bp_diastolic" class="form-control" placeholder="mmHg" min="20" max="200">
                </div>
                <div>
                    <label style="font-size:11px;font-weight:600;color:#555;display:block;margin-bottom:4px">Pulse (bpm)</label>
                    <input type="number" id="qv-pulse" name="pulse" class="form-control" placeholder="bpm" min="20" max="300">
                </div>
                <div>
                    <label style="font-size:11px;font-weight:600;color:#555;display:block;margin-bottom:4px">Temperature (°F)</label>
                    <input type="number" id="qv-temp" name="temperature" class="form-control" step="0.1" placeholder="°F" min="90" max="115">
                </div>
                <div>
                    <label style="font-size:11px;font-weight:600;color:#555;display:block;margin-bottom:4px">SpO₂ (%)</label>
                    <input type="number" id="qv-spo2" name="spo2" class="form-control" step="0.1" placeholder="%" min="50" max="100">
                </div>
                <div>
                    <label style="font-size:11px;font-weight:600;color:#555;display:block;margin-bottom:4px">Weight (kg)</label>
                    <input type="number" id="qv-weight" name="weight" class="form-control" step="0.1" placeholder="kg" min="1" max="500">
                </div>
            </div>
            <div id="qv-msg" style="display:none;margin-top:12px;padding:8px 12px;border-radius:6px;font-size:12.5px"></div>
            <div style="display:flex;gap:8px;margin-top:16px;justify-content:flex-end">
                <button type="button" onclick="closeQV()" class="btn btn-outline">Cancel</button>
                <button type="submit" id="qv-save" class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-1"></i>Save Vitals</button>
            </div>
        </form>
    </div>
</div>

@push('styles')
<style>
.ns-stats { display:flex; gap:10px; align-items:center; margin-bottom:16px; flex-wrap:wrap; }
.ns-stat { background:#fff; border:1px solid #ddd; border-radius:6px; padding:10px 18px; text-align:center; min-width:90px; }
.ns-stat-num { font-size:26px; font-weight:800; line-height:1; }
.ns-stat-lbl { font-size:10.5px; color:#666; margin-top:2px; }
.ns-stat-total .ns-stat-num   { color:#333; }
.ns-stat-waiting .ns-stat-num { color:#d97706; }
.ns-stat-active .ns-stat-num  { color:#2563eb; }
.ns-stat-triage .ns-stat-num  { color:#dc2626; }
.ns-stat-done .ns-stat-num    { color:#16a34a; }

.ns-layout { display:grid; grid-template-columns:300px 1fr; gap:16px; align-items:start; }
@media(max-width:900px){ .ns-layout { grid-template-columns:1fr; } }

.ns-form-card { position:sticky; top:84px; }
.ns-form-card .card-header { background:#f8f9fa; padding:10px 16px; border-bottom:1px solid #e0e0e0; }
.ns-form-card .card-body   { padding:16px; }

.search-dropdown { position:absolute; z-index:500; background:#fff; border:1px solid #ccc; border-top:none; width:100%; max-height:220px; overflow-y:auto; box-shadow:0 4px 10px rgba(0,0,0,.1); }
.search-item { padding:8px 12px; cursor:pointer; font-size:13px; border-bottom:1px solid #f0f0f0; }
.search-item:hover { background:#f0f6ff; }
.search-item .search-id { font-size:11px; color:#888; }
.selected-patient-box { background:#f0fdf4; border:1px solid #86efac; border-radius:5px; padding:8px 12px; margin-top:6px; font-size:12.5px; }

.triage-urgent-row { background:#fff7f7 !important; }
.triage-pill { display:inline-block; padding:2px 10px; border-radius:20px; font-size:11px; font-weight:700; letter-spacing:.5px; }
.triage-green  { background:#dcfce7; color:#14532d; }
.triage-yellow { background:#fef9c3; color:#713f12; }
.triage-red    { background:#fee2e2; color:#7f1d1d; }

.opd-badge-waiting         { background:#fef3c7; color:#92400e; }
.opd-badge-called          { background:#dbeafe; color:#1e40af; }
.opd-badge-in_consultation { background:#dcfce7; color:#14532d; }
.opd-badge-done            { background:#f3f4f6; color:#374151; }
.opd-badge-skipped         { background:#f3f4f6; color:#9ca3af; }
.badge-grey { background:#f3f4f6; color:#555; }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('patientSearch');
    const suggestions = document.getElementById('patientSuggestions');
    const patientId   = document.getElementById('patientId');
    const selectedBox = document.getElementById('selectedPatient');
    const issueBtn    = document.getElementById('issueBtn');
    if (!searchInput) return;

    let searchTimer;
    const searchUrl = '{{ route('patients.search') }}';

    searchInput.addEventListener('input', function () {
        clearTimeout(searchTimer);
        const q = this.value.trim();
        if (q.length < 2) { suggestions.innerHTML = ''; suggestions.style.display = 'none'; return; }
        searchTimer = setTimeout(() => {
            fetch(searchUrl + '?q=' + encodeURIComponent(q), {
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            })
            .then(r => r.json())
            .then(data => {
                suggestions.innerHTML = '';
                if (!data.length) {
                    suggestions.innerHTML = '<div class="search-item" style="color:#999">No patients found</div>';
                    suggestions.style.display = 'block';
                    return;
                }
                data.forEach(p => {
                    const div = document.createElement('div');
                    div.className = 'search-item';
                    div.innerHTML = `<strong>${p.name}</strong> <span class="search-id">${p.jhc_id} · ${p.gender}, ${p.age} yrs · ${p.phone}</span>`;
                    div.addEventListener('mousedown', (e) => { e.preventDefault(); selectPatient(p); });
                    suggestions.appendChild(div);
                });
                suggestions.style.display = 'block';
            });
        }, 280);
    });

    searchInput.addEventListener('blur', () => setTimeout(() => { suggestions.style.display = 'none'; }, 200));

    function selectPatient(p) {
        patientId.value = p.id;
        searchInput.value = p.name;
        suggestions.style.display = 'none';
        selectedBox.innerHTML = `<strong>${p.name}</strong> &nbsp;·&nbsp; ${p.jhc_id} &nbsp;·&nbsp; ${p.gender}, ${p.age} yrs &nbsp;·&nbsp; ${p.phone}
            <a href="#" id="clearPtBtn" style="float:right;color:#c00;font-size:11px">✕ Clear</a>`;
        selectedBox.style.display = 'block';
        issueBtn.disabled = false;
        document.getElementById('clearPtBtn').addEventListener('click', e => {
            e.preventDefault();
            patientId.value = ''; searchInput.value = '';
            selectedBox.style.display = 'none';
            issueBtn.disabled = true;
        });
    }
});
</script>
<script>
// Quick Vitals modal
let qvUrl = '';
function openQV(tokenId, url, patientName, existing) {
    qvUrl = url;
    document.getElementById('qv-patient-name').textContent = patientName;
    document.getElementById('qv-bp-s').value    = existing.bp_s   || '';
    document.getElementById('qv-bp-d').value    = existing.bp_d   || '';
    document.getElementById('qv-pulse').value   = existing.pulse  || '';
    document.getElementById('qv-temp').value    = existing.temp   || '';
    document.getElementById('qv-spo2').value    = existing.spo2   || '';
    document.getElementById('qv-weight').value  = existing.weight || '';
    const msg = document.getElementById('qv-msg');
    msg.style.display = 'none';
    document.getElementById('qv-modal').style.display = 'flex';
}
function closeQV() {
    document.getElementById('qv-modal').style.display = 'none';
}
function submitQV(e) {
    e.preventDefault();
    const btn = document.getElementById('qv-save');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i>Saving…';
    const form = document.getElementById('qv-form');
    const data = new FormData(form);
    data.append('_token', '{{ csrf_token() }}');
    fetch(qvUrl, { method: 'POST', body: data, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.json())
        .then(d => {
            const msg = document.getElementById('qv-msg');
            msg.style.display = 'block';
            if (d.success) {
                msg.style.background = '#f0fdf4'; msg.style.border = '1px solid #86efac'; msg.style.color = '#14532d';
                msg.textContent = 'Vitals saved successfully.';
                setTimeout(closeQV, 1200);
            } else {
                msg.style.background = '#fff7ed'; msg.style.border = '1px solid #fed7aa'; msg.style.color = '#9a3412';
                msg.textContent = 'Error saving vitals. Please try again.';
                btn.disabled = false; btn.innerHTML = '<i class="fa-solid fa-floppy-disk me-1"></i>Save Vitals';
            }
        })
        .catch(() => {
            const msg = document.getElementById('qv-msg');
            msg.style.display = 'block'; msg.style.background = '#fff7ed'; msg.style.border = '1px solid #fed7aa'; msg.style.color = '#9a3412';
            msg.textContent = 'Network error. Please try again.';
            btn.disabled = false; btn.innerHTML = '<i class="fa-solid fa-floppy-disk me-1"></i>Save Vitals';
        });
}
document.getElementById('qv-modal').addEventListener('click', function(e) {
    if (e.target === this) closeQV();
});

// Auto-refresh every 45 seconds unless a form/modal is open
(function() {
    const INTERVAL = 45;
    let secs = INTERVAL;
    const countEl = document.getElementById('refresh-countdown');
    setInterval(function() {
        const modalOpen = document.querySelector('.modal-overlay.open') || document.getElementById('qv-modal').style.display === 'flex';
        const focused = document.activeElement;
        const isTyping = focused && (focused.tagName === 'INPUT' || focused.tagName === 'TEXTAREA' || focused.tagName === 'SELECT');
        if (modalOpen || isTyping) { secs = INTERVAL; if (countEl) countEl.textContent = secs; return; }
        secs--;
        if (countEl) countEl.textContent = secs;
        if (secs <= 0) location.reload();
    }, 1000);
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) { secs = INTERVAL; if (countEl) countEl.textContent = secs; }
    });
})();
</script>
@endpush
@endsection
