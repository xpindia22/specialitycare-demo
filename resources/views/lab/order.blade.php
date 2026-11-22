{{-- FILE: resources/views/lab/order.blade.php --}}
@extends('layouts.app')
@section('title', 'Lab Order '.$order->order_no)
@section('page-title','Lab Order')
@section('content')

@include('diagnostics._toolbar')
<div class="page-header">
    <div class="page-header-left">
        <h1>Lab Order — <span class="mono">{{ $order->order_no }}</span></h1>
        <div class="breadcrumb"><a href="{{ route('lab.index') }}">Lab</a> › {{ $order->order_no }}</div>
    </div>
    @if($order->status !== 'Cancelled')
    <form method="POST" action="{{ route('lab.update-status', $order) }}" style="display:flex;gap:10px;align-items:center">
        @csrf
        <select name="status" class="form-control" style="width:160px">
            @foreach(['Ordered','Collected','Processing','Completed','Cancelled'] as $s)
                <option value="{{ $s }}" {{ $order->status === $s ? 'selected':'' }}>{{ $s }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn btn-primary btn-sm">Update Status</button>
    </form>
    @if($order->status === 'Completed')
        <a href="{{ route('lab.print-report', $order) }}" class="btn btn-outline" target="_blank"><i class="fa-solid fa-print"></i> Print Report</a>
        <button type="button" class="btn btn-outline" onclick="document.getElementById('email-lab-modal').classList.add('open')"><i class="fa-solid fa-envelope"></i> Email</button>
        <button type="button" class="btn btn-outline" onclick="document.getElementById('whatsapp-lab-modal').classList.add('open')"><i class="fa-brands fa-whatsapp"></i> WhatsApp</button>
    @endif
    @endif
</div>

{{-- Email Lab Report modal --}}
<div id="email-lab-modal" class="modal-overlay">
    <div class="modal-box" style="max-width:480px">
        <div class="modal-header">
            <i class="fa-solid fa-envelope" style="color:var(--sc-teal)"></i>
            <h3>Email Lab Report</h3>
            <button class="modal-close" onclick="document.getElementById('email-lab-modal').classList.remove('open')">&times;</button>
        </div>
        <form method="POST" action="{{ route('lab.email', $order) }}">
            @csrf
            <div class="modal-body" style="display:flex;flex-direction:column;gap:12px">
                <div class="form-group" style="margin:0">
                    <label class="form-label">To <span class="req">*</span></label>
                    <input type="email" name="to" class="form-control" value="{{ $order->patient->email ?? '' }}" required>
                </div>
                <div class="form-group" style="margin:0">
                    <label class="form-label">Subject</label>
                    <input type="text" name="subject" class="form-control" placeholder="Auto-filled if left blank">
                </div>
                <div class="form-group" style="margin:0">
                    <label class="form-label">Message</label>
                    <textarea name="message" class="form-control" rows="4" placeholder="Auto-filled if left blank"></textarea>
                </div>
                <div class="text-muted" style="font-size:11px"><i class="fa-solid fa-info-circle"></i> The lab report PDF is attached automatically.</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('email-lab-modal').classList.remove('open')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-paper-plane"></i> Send</button>
            </div>
        </form>
    </div>
</div>

{{-- WhatsApp Lab Report modal --}}
<div id="whatsapp-lab-modal" class="modal-overlay">
    <div class="modal-box" style="max-width:480px">
        <div class="modal-header">
            <i class="fa-brands fa-whatsapp" style="color:#25D366"></i>
            <h3>Send Lab Report via WhatsApp</h3>
            <button class="modal-close" onclick="document.getElementById('whatsapp-lab-modal').classList.remove('open')">&times;</button>
        </div>
        <form method="POST" action="{{ route('lab.whatsapp', $order) }}">
            @csrf
            <div class="modal-body" style="display:flex;flex-direction:column;gap:12px">
                <div class="form-group" style="margin:0">
                    <label class="form-label">Send to <span class="req">*</span></label>
                    <input type="text" name="phone" class="form-control" value="{{ $order->patient->phone ?? $order->patient->alt_phone ?? '' }}" required>
                    <div class="text-muted" style="font-size:11px;margin-top:4px">Defaults to the patient's number — edit to send to a different one.</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('whatsapp-lab-modal').classList.remove('open')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fa-brands fa-whatsapp"></i> Send</button>
            </div>
        </form>
    </div>
</div>

<div class="patient-strip mb-3">
    <div class="ps-avatar">{{ strtoupper(substr($order->patient->first_name,0,1)) }}</div>
    <div>
        <div class="ps-name">{{ $order->patient->full_name }}</div>
        <div class="ps-jhc_id">{{ $order->patient->jhc_id }}</div>
        <div class="ps-meta">
            <span><i class="fa-solid fa-calendar"></i> {{ $order->patient->age }}</span>
            <span>{{ $order->patient->gender }}</span>
            <span><i class="fa-solid fa-flask-vial"></i> Ordered: {{ $order->ordered_date->format('d M Y') }}</span>
            @if($order->priority !== 'Routine')<span class="badge badge-red" style="font-size:11px">{{ $order->priority }}</span>@endif
        </div>
    </div>
    <div class="ps-actions">
        <span class="badge {{ $order->status==='Completed' ? 'badge-green' : ($order->status==='Ordered' ? 'badge-amber' : 'badge-teal') }}" style="font-size:13px;padding:8px 14px">{{ $order->status }}</span>
        @if($order->admission_id)
        <a href="{{ route('admissions.show', $order->admission_id) }}#tab-labs" class="btn btn-sm btn-outline" style="color:#fff;border-color:rgba(255,255,255,.3)">
            <i class="fa-solid fa-bed-pulse"></i> IPD Admission
        </a>
        @endif
    </div>
</div>

@if(session('success'))
<div class="alert alert-success" style="margin-bottom:14px"><i class="fa-solid fa-check"></i><div>{{ session('success') }}</div></div>
@endif
@if($errors->any())
<div class="alert alert-danger" style="margin-bottom:14px"><i class="fa-solid fa-circle-xmark"></i><div>{{ $errors->first() }}</div></div>
@endif

<!-- Add more tests/panels to this order -->
@if($order->status !== 'Cancelled')
<div class="card mb-3">
    <div class="card-header" style="cursor:pointer" onclick="document.getElementById('add-tests-body').style.display = (document.getElementById('add-tests-body').style.display==='none'?'block':'none')">
        <i class="fa-solid fa-plus"></i> Add Tests / Panels
        <span style="margin-left:auto;font-size:12px;color:var(--sc-muted)"><span id="lab-selected-count">0</span> selected — click to expand</span>
    </div>
    <div class="card-body" id="add-tests-body" style="display:none">
        <form method="POST" action="{{ route('lab.add-tests', $order) }}">
            @csrf
            @include('lab._test-picker')
            <div style="margin-top:14px;display:flex;justify-content:flex-end">
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Add Selected to Order</button>
            </div>
        </form>
    </div>
</div>
@endif

<!-- Test Items with Result Entry -->
@if($order->status !== 'Cancelled' && $order->items->count())
<div class="card mb-3" style="background:var(--sc-bg)">
    <div class="card-body" style="display:flex;align-items:center;gap:10px;padding:10px 16px;flex-wrap:wrap">
        <i class="fa-solid fa-bolt" style="color:var(--sc-teal)"></i>
        <span class="text-small">Results save automatically as you type.</span>
        <label style="display:flex;align-items:center;gap:6px;margin-left:auto;font-size:12.5px;cursor:pointer" title="Saves each result to the server every 3 seconds while you type">
            <input type="checkbox" id="lab-autosave-toggle" onchange="setLabAutoSave(this.checked)"> Auto-save every 3 s
        </label>
        <span id="lab-autosave-status" style="font-size:12px;color:var(--sc-muted);min-width:130px;text-align:right"></span>
    </div>
</div>
@endif

@php
    // Group the order's items by panel so each panel reads as one block, with
    // a slightly darker separator row above it; loose (individual) tests last.
    $itemGroups  = $order->items->groupBy(fn($it) => (int) ($it->lab_panel_id ?: 0));
    $orderedKeys = $itemGroups->keys()->sortBy(fn($k) => $k === 0 ? 1 : 0)->values();
@endphp
@foreach($orderedKeys as $gKey)
@php $groupItems = $itemGroups[$gKey]; $groupPanel = $gKey ? optional($groupItems->first())->panel : null; @endphp
@if($groupPanel)
<div class="lab-panel-sep" style="background:#b7c4d8;border-left:5px solid var(--sc-navy,#1f3a5f);border-radius:6px;padding:10px 14px;margin:22px 0 10px;font-weight:700;color:#17273f;display:flex;align-items:center;gap:8px;box-shadow:0 1px 0 rgba(0,0,0,.06)">
    <i class="fa-solid fa-layer-group"></i> {{ $groupPanel->name }}{{ $groupPanel->short_code ? ' ('.$groupPanel->short_code.')' : '' }}
    <span style="margin-left:auto;font-weight:600;font-size:12px;color:#3a4a63">{{ $groupItems->count() }} test{{ $groupItems->count() > 1 ? 's' : '' }}</span>
</div>
@elseif($orderedKeys->count() > 1)
<div class="lab-panel-sep" style="background:#ccd4e0;border-left:5px solid #9aa8bd;border-radius:6px;padding:9px 14px;margin:22px 0 10px;font-weight:700;color:#28374f">
    <i class="fa-solid fa-flask-vial"></i> Individual tests
</div>
@endif
@foreach($groupItems as $item)
@php
    $flagCode = $item->abnormal_flag ?: 'N';
    $flagLabels = ['N'=>'Normal','H'=>'High','L'=>'Low','HH'=>'Critical High','LL'=>'Critical Low','POS'=>'Positive','NEG'=>'Negative','ABN'=>'Abnormal'];
    $flagSeverity = in_array($flagCode, ['H','L','ABN'], true) ? 'abnormal' : (in_array($flagCode, ['HH','LL','POS'], true) ? 'critical' : 'normal');
    $flagBg = $flagSeverity === 'critical' ? 'rgba(232,76,76,.12)' : ($flagSeverity === 'abnormal' ? 'rgba(245,166,35,.14)' : 'rgba(39,196,122,.12)');
    $flagColor = $flagSeverity === 'critical' ? '#b52b2b' : ($flagSeverity === 'abnormal' ? '#9a6508' : '#1b9e5c');
@endphp
<div class="card mb-3 lab-result-card" id="lab-card-{{ $item->id }}" data-lab-item="{{ $item->id }}" data-flag-severity="{{ $item->result_value ? $flagSeverity : 'pending' }}" style="{{ $item->result_value && $flagSeverity !== 'normal' ? 'border-color:'.$flagColor.';box-shadow:0 0 0 1px '.$flagColor.'22' : '' }}">
    <div class="card-header">
        <i class="fa-solid fa-flask"></i>
        <strong>{{ $item->test?->name ?? $item->panel?->name ?? 'Test' }}</strong>
        @if($item->test?->code)<span class="mono text-muted" style="font-size:11px;margin-left:6px">{{ $item->test->code }}</span>@endif
        <span class="badge {{ $item->result_status === 'Final' ? 'badge-green' : ($item->result_status === 'Preliminary' ? 'badge-amber' : 'badge-gray') }}" style="margin-left:8px">{{ $item->result_status }}</span>
        <div class="card-actions">
            <span class="fw-600 text-muted text-small">₹{{ number_format($item->price, 2) }}</span>
        </div>
    </div>
    <div class="card-body">
        @php
            $firstResult = $item->results->first();
            $normalRange = $item->test?->normal_range ?: ($firstResult->normal_range ?? null);
            $refUnit     = $item->test?->unit ?: ($firstResult->unit ?? ($item->test?->unit ?? null));
        @endphp
        @if($normalRange)
        <div class="alert alert-info" style="margin-bottom:12px"><i class="fa-solid fa-circle-info"></i> Normal Range: <strong>{{ $normalRange }}</strong> {{ $refUnit }}</div>
        @endif
        @if($item->panel && $item->panel->tests->count() && $loop->first)
        <div class="alert alert-info" style="margin-bottom:12px">
            <i class="fa-solid fa-layer-group"></i> <strong>{{ $item->panel->name }}</strong> — reference ranges:
            <table class="sc-table" style="margin-top:8px;background:#fff">
                <thead><tr><th>Test</th><th style="width:180px">Normal Range</th><th style="width:90px">Unit</th></tr></thead>
                <tbody>
                @foreach($item->panel->tests as $pt)
                    <tr><td>{{ $pt->name }}</td><td class="mono">{{ $pt->normal_range ?: '—' }}</td><td>{{ $pt->unit }}</td></tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @endif

        <form onsubmit="saveResults(event, {{ $item->id }})">
            <div class="form-row cols-3">
                <div class="form-group">
                    <label class="form-label">Result Value</label>
                    <div style="display:flex;gap:8px">
                        <input type="text" id="result-val-{{ $item->id }}" class="form-control" value="{{ $item->result_value ?? $item->test?->normal_value }}" placeholder="Enter result...">
                        @if($item->test?->unit)<span style="padding:9px 10px;background:var(--sc-bg);border:1px solid var(--sc-border);border-radius:7px;white-space:nowrap;font-size:13px">{{ $item->test->unit }}</span>@endif
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select id="result-status-{{ $item->id }}" class="form-control">
                        @foreach(['Pending','Preliminary','Final'] as $s)
                            <option value="{{ $s }}" {{ $item->result_status === $s ? 'selected':'' }}>{{ $s }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Auto Flag</label>
                    <div id="auto-flag-{{ $item->id }}" class="form-control" style="background:{{ $item->result_value ? $flagBg : 'var(--sc-bg)' }};color:{{ $item->result_value ? $flagColor : 'var(--sc-muted)' }};font-weight:700">
                        {{ $item->result_value ? ($flagLabels[$flagCode] ?? 'Review') : 'Calculated on save' }}
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Remarks</label>
                <textarea id="remarks-{{ $item->id }}" class="form-control" rows="2">{{ $item->remarks }}</textarea>
            </div>
            @if($order->status !== 'Cancelled')
            <div style="display:flex;align-items:center;gap:12px">
                <button type="submit" id="lab-save-btn-{{ $item->id }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-save"></i> Save Result</button>
                <span id="lab-save-status-{{ $item->id }}" style="font-size:12px;color:var(--sc-muted)"></span>
            </div>
            @endif
        </form>

        @if($item->result_value && $item->result_entered_at)
        <div class="text-muted text-small mt-2">
            <i class="fa-solid fa-clock"></i> Entered: {{ $item->result_entered_at->format('d M Y, h:i A') }}
            @if($item->result_entered_by) by {{ $item->resultEnteredBy?->name }} @endif
        </div>
        @endif

        @if(isset($labTrends[$item->lab_test_id]))
        @php $trend = $labTrends[$item->lab_test_id]; @endphp
        <div style="margin-top:12px;padding-top:12px;border-top:1px solid var(--sc-border)">
            <div style="font-size:11px;font-weight:600;color:var(--sc-muted);text-transform:uppercase;margin-bottom:6px">
                <i class="fa-solid fa-chart-line" style="color:var(--sc-teal)"></i> Trend — last {{ count($trend['points']) }} readings
            </div>
            <canvas id="trend-chart-{{ $item->lab_test_id }}" height="80"></canvas>
        </div>
        @endif
    </div>
</div>
@endforeach
@endforeach

<!-- Order Summary -->
<div class="card">
    <div class="card-body" style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
        <div>
            @if($order->notes)<div class="text-small"><strong>Notes:</strong> {{ $order->notes }}</div>@endif
            @if($order->referred_doctor)<div class="text-small mt-1"><strong>Referred By:</strong> {{ $order->referred_doctor }}</div>@endif
        </div>
        <div class="text-right">
            <div class="text-muted text-small">Net Amount</div>
            <div style="font-size:22px;font-weight:700;color:var(--sc-navy)">₹{{ number_format($order->net_amount, 2) }}</div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function labItemSignature(itemId) {
    const v = document.getElementById(`result-val-${itemId}`);
    if (!v) return null;
    return JSON.stringify({
        v: v.value,
        s: document.getElementById(`result-status-${itemId}`)?.value || '',
        r: document.getElementById(`remarks-${itemId}`)?.value || '',
    });
}

function setLabItemStatus(itemId, text, isError) {
    const el = document.getElementById(`lab-save-status-${itemId}`);
    if (!el) return;
    el.textContent = text;
    el.style.color = isError ? 'var(--sc-red)' : 'var(--sc-muted)';
}

// Make each item's button reflect autosave state so it's obvious results save themselves.
function updateLabButton(itemId, state) {
    const btn = document.getElementById(`lab-save-btn-${itemId}`);
    if (!btn) return;
    btn.classList.remove('btn-primary', 'btn-outline');
    btn.style.background = ''; btn.style.borderColor = ''; btn.style.color = '';
    btn.disabled = false;
    if (state === 'saving') {
        btn.classList.add('btn-outline');
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving…';
        return;
    }
    if (!_labAutoSaveOn) {                       // autosave off -> normal manual Save
        btn.classList.add('btn-primary');
        btn.innerHTML = '<i class="fa-solid fa-save"></i> Save Result';
        return;
    }
    const val = (document.getElementById(`result-val-${itemId}`)?.value || '').trim();
    if (val === '') {                            // autosave on, nothing entered yet
        btn.classList.add('btn-outline');
        btn.innerHTML = '<i class="fa-solid fa-bolt"></i> Auto-save on';
    } else {                                     // autosave on, value saved
        btn.classList.add('btn-outline');
        btn.style.borderColor = 'var(--sc-green)';
        btn.style.color = 'var(--sc-green)';
        btn.innerHTML = '<i class="fa-solid fa-circle-check"></i> Auto-saved';
    }
}

function refreshAllLabButtons() {
    document.querySelectorAll('[data-lab-item]').forEach(c => updateLabButton(c.getAttribute('data-lab-item'), 'idle'));
}

// Manual Save button just forces an immediate save; the button label is managed centrally.
function saveResults(e, itemId) {
    e.preventDefault();
    postLabItem(itemId, false);
}

// Shared poster used by both the manual button and the 3-second autosave.
function postLabItem(itemId, isAuto) {
    const valEl = document.getElementById(`result-val-${itemId}`);
    const val = (valEl?.value || '').trim();
    if (val === '') {                            // endpoint requires a value
        if (!isAuto) setLabItemStatus(itemId, 'Enter a result first', true);
        return;
    }
    const sig = labItemSignature(itemId);
    _labBusy[itemId] = true;
    updateLabButton(itemId, 'saving');
    setLabItemStatus(itemId, 'Saving…');
    return $.post(`{{ url("/lab/item") }}/${itemId}/results`, {
        result_value: val,
        result_status: document.getElementById(`result-status-${itemId}`)?.value,
        remarks: document.getElementById(`remarks-${itemId}`)?.value,
    }, function(res) {
        if (res.success) {
            updateAutoFlag(itemId, res.flag_label, res.flag_severity);
            _labItemLast[itemId] = sig;                 // baseline so autosave won't repost identical
            const t = new Date().toLocaleTimeString([], {hour:'2-digit', minute:'2-digit', second:'2-digit'});
            setLabItemStatus(itemId, (_labAutoSaveOn ? 'Auto-saved ' : 'Saved ') + t);
        }
    }).fail(function(xhr) {
        const msg = xhr.status === 419 ? 'Session expired — reload' : (xhr.responseJSON?.message || 'Save failed');
        setLabItemStatus(itemId, msg, true);
        if (!isAuto && xhr.status !== 419) alert(xhr.responseJSON?.message || 'Could not save result.');
    }).always(function() {
        _labBusy[itemId] = false;
        updateLabButton(itemId, 'idle');
    });
}

// ---- Lab results: auto-save every 3 s while typing (same pattern as clinical notes) ----
const LAB_AUTOSAVE_MS = 3000;
let _labAutoSaveOn = true;
const _labItemLast = {};   // itemId -> signature the server already has
const _labBusy = {};

function setLabAutoSave(on) {
    _labAutoSaveOn = !!on;
    try { localStorage.setItem('lab_autosave', on ? '1' : '0'); } catch (e) {}
    const s = document.getElementById('lab-autosave-status');
    if (s) { s.textContent = on ? 'Auto-save on — results save as you type' : 'Auto-save off — click Save'; s.style.color = 'var(--sc-muted)'; }
    refreshAllLabButtons();
}

function labAutoSaveTick() {
    if (!_labAutoSaveOn) return;
    document.querySelectorAll('[data-lab-item]').forEach(function(card) {
        const itemId = card.getAttribute('data-lab-item');
        if (_labBusy[itemId]) return;
        const valEl = document.getElementById(`result-val-${itemId}`);
        if (!valEl || valEl.value.trim() === '') return;   // endpoint needs a value; skip empties
        const sig = labItemSignature(itemId);
        if (sig === _labItemLast[itemId]) return;           // nothing changed
        postLabItem(itemId, true);
    });
}

(function initLabAutoSave() {
    try { _labAutoSaveOn = localStorage.getItem('lab_autosave') !== '0'; } catch (e) {}
    function boot() {
        const t = document.getElementById('lab-autosave-toggle');
        if (t) t.checked = _labAutoSaveOn;
        const s = document.getElementById('lab-autosave-status');
        if (s) s.textContent = _labAutoSaveOn ? 'Auto-save on — results save as you type' : 'Auto-save off — click Save';
        // Baseline every item so an untouched page never posts; mark already-saved rows.
        document.querySelectorAll('[data-lab-item]').forEach(function(card) {
            const itemId = card.getAttribute('data-lab-item');
            _labItemLast[itemId] = labItemSignature(itemId);
            const v = (document.getElementById(`result-val-${itemId}`)?.value || '').trim();
            if (v !== '') setLabItemStatus(itemId, _labAutoSaveOn ? 'Auto-saved' : 'Saved');
        });
        refreshAllLabButtons();
        setInterval(labAutoSaveTick, LAB_AUTOSAVE_MS);
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
    else boot();
})();

function updateAutoFlag(itemId, label, severity) {
    const flag = document.getElementById(`auto-flag-${itemId}`);
    const card = document.getElementById(`lab-card-${itemId}`);
    const palette = {
        normal: { bg: 'rgba(39,196,122,.12)', color: '#1b9e5c' },
        abnormal: { bg: 'rgba(245,166,35,.14)', color: '#9a6508' },
        critical: { bg: 'rgba(232,76,76,.12)', color: '#b52b2b' },
        review: { bg: 'rgba(245,166,35,.14)', color: '#9a6508' },
    };
    const p = palette[severity] || palette.review;
    flag.textContent = label || 'Review';
    flag.style.background = p.bg;
    flag.style.color = p.color;
    flag.style.fontWeight = '700';
    if (severity && severity !== 'normal') {
        card.style.borderColor = p.color;
        card.style.boxShadow = `0 0 0 1px ${p.color}22`;
    } else {
        card.style.borderColor = '';
        card.style.boxShadow = '';
    }
}
</script>
@if(isset($labTrends) && count($labTrends))
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(function() {
    const trends = @json($labTrends);
    Object.entries(trends).forEach(([testId, trend]) => {
        const ctx = document.getElementById('trend-chart-' + testId);
        if (!ctx) return;
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: trend.points.map(p => p.date),
                datasets: [{
                    label: trend.name,
                    data: trend.points.map(p => p.value),
                    borderColor: '#0ab4b4',
                    backgroundColor: 'rgba(10,180,180,.08)',
                    tension: 0.3, pointRadius: 4, fill: true,
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    y: { ticks: { font: { size: 10 } } },
                    x: { ticks: { font: { size: 10 } } }
                }
            }
        });
    });
})();
</script>
@endif
@endpush
