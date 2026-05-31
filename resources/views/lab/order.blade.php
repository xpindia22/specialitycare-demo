{{-- FILE: resources/views/lab/order.blade.php --}}
@extends('layouts.app')
@section('title', 'Lab Order '.$order->order_no)
@section('page-title','Lab Order')
@section('content')
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
    @endif
    @endif
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
@foreach($order->items as $item)
@php
    $flagCode = $item->abnormal_flag ?: 'N';
    $flagLabels = ['N'=>'Normal','H'=>'High','L'=>'Low','HH'=>'Critical High','LL'=>'Critical Low','POS'=>'Positive','NEG'=>'Negative','ABN'=>'Abnormal'];
    $flagSeverity = in_array($flagCode, ['H','L','ABN'], true) ? 'abnormal' : (in_array($flagCode, ['HH','LL','POS'], true) ? 'critical' : 'normal');
    $flagBg = $flagSeverity === 'critical' ? 'rgba(232,76,76,.12)' : ($flagSeverity === 'abnormal' ? 'rgba(245,166,35,.14)' : 'rgba(39,196,122,.12)');
    $flagColor = $flagSeverity === 'critical' ? '#b52b2b' : ($flagSeverity === 'abnormal' ? '#9a6508' : '#1b9e5c');
@endphp
<div class="card mb-3 lab-result-card" id="lab-card-{{ $item->id }}" data-flag-severity="{{ $item->result_value ? $flagSeverity : 'pending' }}" style="{{ $item->result_value && $flagSeverity !== 'normal' ? 'border-color:'.$flagColor.';box-shadow:0 0 0 1px '.$flagColor.'22' : '' }}">
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
        @if($item->test && $item->test->normal_range)
        <div class="alert alert-info" style="margin-bottom:12px"><i class="fa-solid fa-circle-info"></i> Normal Range: <strong>{{ $item->test->normal_range }}</strong> {{ $item->test->unit }}</div>
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
            <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-save"></i> Save Result</button>
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
function saveResults(e, itemId) {
    e.preventDefault();
    const form = e.currentTarget;
    const btn = form.querySelector('button[type=submit]');
    const orig = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving...';

    $.post(`{{ url("/lab/item") }}/${itemId}/results`, {
        result_value: document.getElementById(`result-val-${itemId}`)?.value,
        result_status: document.getElementById(`result-status-${itemId}`)?.value,
        remarks: document.getElementById(`remarks-${itemId}`)?.value,
    }, function(res) {
        if (res.success) {
            updateAutoFlag(itemId, res.flag_label, res.flag_severity);
            btn.innerHTML = '<i class="fa-solid fa-check"></i> Saved!';
            btn.style.background = 'var(--sc-green)';
            setTimeout(() => { btn.innerHTML = orig; btn.style.background = ''; }, 2000);
        }
    }).fail(function(xhr) {
        const message = xhr.responseJSON?.message || 'Could not save result.';
        alert(message);
        btn.innerHTML = orig;
    }).always(function() {
        btn.disabled = false;
    });
}

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
