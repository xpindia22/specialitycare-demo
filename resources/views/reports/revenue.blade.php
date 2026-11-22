@extends('layouts.app')
@section('title', 'Revenue Dashboard')
@section('content')

<div class="page-header">
    <div class="page-header-left">
        <h1>Revenue Dashboard</h1>
        <div class="breadcrumb">Reports &rsaquo; Revenue &rsaquo; {{ $from->format('d M Y') }} – {{ $to->format('d M Y') }}</div>
    </div>
    <button onclick="window.print()" class="btn btn-outline">
        <i class="fa-solid fa-print"></i> Print
    </button>
</div>

{{-- Range + grouping --}}
<form method="GET" class="card mb-4" id="rev-filter">
    <div class="card-body" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;padding:14px 20px">
        <div class="form-group" style="margin:0">
            <label class="form-label">From</label>
            <input type="date" name="from" value="{{ $from->toDateString() }}" max="{{ today()->toDateString() }}" class="form-control" style="width:160px">
        </div>
        <div class="form-group" style="margin:0">
            <label class="form-label">To</label>
            <input type="date" name="to" value="{{ $to->toDateString() }}" max="{{ today()->toDateString() }}" class="form-control" style="width:160px">
        </div>
        <div class="form-group" style="margin:0">
            <label class="form-label">View</label>
            <div style="display:flex;border:1px solid var(--sc-border);border-radius:var(--radius);overflow:hidden">
                @foreach(['day' => 'Day-wise', 'week' => 'Week-wise', 'month' => 'Month-wise'] as $g => $label)
                <label style="margin:0;padding:7px 14px;font-size:13px;font-weight:600;cursor:pointer;border-right:1px solid var(--sc-border);
                              {{ $group === $g ? 'background:var(--sc-teal);color:#fff' : 'color:var(--sc-muted)' }}">
                    <input type="radio" name="group" value="{{ $g }}" {{ $group === $g ? 'checked' : '' }} onchange="this.form.submit()" style="display:none">{{ $label }}
                </label>
                @endforeach
            </div>
        </div>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-filter"></i> Apply</button>
    </div>
    <div style="display:flex;gap:6px;flex-wrap:wrap;padding:0 20px 14px">
        @foreach($presets as $label => [$pf, $pt])
        @php $active = $pf->isSameDay($from) && $pt->isSameDay($to); @endphp
        <a href="{{ route('reports.revenue', ['from' => $pf->toDateString(), 'to' => $pt->toDateString()]) }}"
           class="btn btn-sm {{ $active ? 'btn-primary' : 'btn-outline' }}">{{ $label }}</a>
        @endforeach
    </div>
</form>

{{-- Totals for the range --}}
@php $margin = $totals['lab'] > 0 ? round($totals['profit'] / $totals['lab'] * 100) : 0; @endphp
@php
    // Indian digit grouping: 2147792 -> 21,47,792
    $inr = function ($n) {
        $n = (string) round($n); $neg = str_starts_with($n, '-'); $n = ltrim($n, '-');
        $out = strlen($n) > 3 ? preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', substr($n, 0, -3)) . ',' . substr($n, -3) : $n;
        return ($neg ? '-' : '') . '₹' . $out;
    };
    $pharmMargin = $totals['pharm'] > 0 ? round($totals['pharm_profit'] / $totals['pharm'] * 100) : 0;
    $kpis = [
        ['Total Revenue',   $totals['all'],          'var(--sc-green)', null],
        ['OPD Collections', $totals['opd'],          'var(--sc-navy)',  null],
        ['IPD Deposits',    $totals['ipd'],          'var(--sc-teal)',  null],
        ['Lab Revenue',     $totals['lab'],          'var(--sc-amber)', null],
        ['Lab Profit',      $totals['profit'],       'var(--sc-green)', $margin.'% of lab'],
        ['Pharmacy Sales',  $totals['pharm'],        '#8b5cf6',         null],
        ['Medicine Profit', $totals['pharm_profit'], 'var(--sc-green)', $pharmMargin.'% of pharmacy'],
        ['Total Profit',    $totals['profit_all'],   'var(--sc-green)', 'lab + medicines'],
    ];
@endphp
<div class="rev-kpis">
    @foreach($kpis as [$label, $value, $color, $sub])
    <div class="rev-kpi" style="border-left-color:{{ $color }}" title="{{ $label }}: ₹{{ number_format($value, 2) }}">
        <div class="rev-kpi-label">{{ $label }}</div>
        <div class="rev-kpi-value" @if(str_contains($label, 'Profit') || $label === 'Total Revenue') style="color:var(--sc-green)" @endif>{{ $inr($value) }}</div>
        @if($sub)<div class="rev-kpi-sub">{{ $sub }}</div>@endif
    </div>
    @endforeach
</div>
@if($pharmUnpriced > 0)
{{-- alert-keep: opt out of the layout's 4-second auto-dismiss; this one has its own ✓ --}}
<div class="alert alert-warning alert-keep mb-4" id="pharm-alert" style="font-size:13px;position:relative;padding-right:44px">
    <i class="fa-solid fa-circle-info"></i>
    Medicine profit leaves out {{ $pharmUnpriced }} of {{ $pharmLines }} medicine dispenses in this range that have no selling price or no purchase cost.
    Set selling prices in the medicine list (or the pharmacy sale markup in accounts settings) and record purchase costs on stock to include them.
    {{-- Dismiss tick: appears after the alert has been shown for 10 seconds. --}}
    <button type="button" id="pharm-alert-ok" title="Dismiss" aria-label="Dismiss"
            onclick="document.getElementById('pharm-alert').remove()"
            style="display:none;position:absolute;top:8px;right:8px;width:26px;height:26px;border-radius:50%;border:1px solid currentColor;background:transparent;color:inherit;cursor:pointer;font-size:12px;line-height:1">
        <i class="fa-solid fa-check"></i>
    </button>
</div>
<script>setTimeout(function () { var b = document.getElementById('pharm-alert-ok'); if (b) b.style.display = 'inline-flex', b.style.alignItems = 'center', b.style.justifyContent = 'center'; }, 10000);</script>
@endif

<div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;margin-bottom:24px">
    {{-- Period bar chart --}}
    <div class="card">
        <div class="card-header"><i class="fa-solid fa-chart-bar"></i> {{ ['day'=>'Daily','week'=>'Weekly','month'=>'Monthly'][$group] }} Revenue &amp; Profit</div>
        <div class="card-body">
            <canvas id="revenueChart" height="100"></canvas>
        </div>
    </div>

    {{-- Range donut --}}
    <div class="card">
        <div class="card-header"><i class="fa-solid fa-chart-pie"></i> Breakdown</div>
        <div class="card-body" style="display:flex;flex-direction:column;align-items:center;justify-content:center">
            <canvas id="donutChart" width="200" height="200" style="max-width:180px"></canvas>
            <div style="margin-top:16px;width:100%">
                @foreach(['OPD' => ['opd','#0ab4b4'], 'IPD' => ['ipd','#0f1f3d'], 'Lab' => ['lab','#f5a623'], 'Pharmacy' => ['pharm','#8b5cf6']] as $label => [$key, $color])
                <div style="display:flex;justify-content:space-between;font-size:12px;padding:4px 0;border-bottom:1px solid var(--sc-border)">
                    <span style="display:flex;align-items:center;gap:6px">
                        <span style="width:10px;height:10px;background:{{ $color }};border-radius:50%;display:inline-block"></span>
                        {{ $label }}
                    </span>
                    <span class="fw-600">₹{{ number_format($totals[$key],0) }}</span>
                </div>
                @endforeach
                <div style="display:flex;justify-content:space-between;font-size:12px;padding:4px 0;color:var(--sc-green)">
                    <span style="padding-left:16px">Lab Profit</span>
                    <span class="fw-600">₹{{ number_format($totals['profit'],0) }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:12px;padding:4px 0;color:var(--sc-green)">
                    <span style="padding-left:16px">Medicine Profit</span>
                    <span class="fw-600">₹{{ number_format($totals['pharm_profit'],0) }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Period table (newest first). Week/month rows open that period day-wise. --}}
<div class="card mb-4">
    <div class="card-header"><i class="fa-solid fa-table"></i> {{ ['day'=>'Day-wise','week'=>'Week-wise','month'=>'Month-wise'][$group] }} breakdown</div>
    <div class="table-wrap">
        <table class="sc-table">
            <thead>
                <tr>
                    <th>{{ ['day'=>'Date','week'=>'Week','month'=>'Month'][$group] }}</th>
                    <th class="text-right">OPD</th><th class="text-right">IPD</th><th class="text-right">Lab</th><th class="text-right">Pharmacy</th>
                    <th class="text-right">Total</th><th class="text-right">Lab Profit</th><th class="text-right">Lab Margin</th><th class="text-right">Medicine Profit</th>
                </tr>
            </thead>
            <tbody>
                @foreach(array_reverse($periods, true) as $p)
                @php $tot = $p['opd'] + $p['ipd'] + $p['lab'] + $p['pharm']; @endphp
                @if($group === 'day')
                <tr>
                @else
                <tr style="cursor:pointer" title="See this {{ $group }} day-wise"
                    onclick="location='{{ route('reports.revenue', ['from' => $p['start']->toDateString(), 'to' => $p['end']->toDateString(), 'group' => 'day']) }}'">
                @endif
                    <td class="fw-600">{{ $group === 'day' ? $p['start']->format('d M Y (D)') : $p['label'] }}</td>
                    <td class="text-right">{{ $p['opd'] ? '₹'.number_format($p['opd'],0) : '—' }}</td>
                    <td class="text-right">{{ $p['ipd'] ? '₹'.number_format($p['ipd'],0) : '—' }}</td>
                    <td class="text-right">{{ $p['lab'] ? '₹'.number_format($p['lab'],0) : '—' }}</td>
                    <td class="text-right">{{ $p['pharm'] ? '₹'.number_format($p['pharm'],0) : '—' }}</td>
                    <td class="text-right fw-700">{{ $tot ? '₹'.number_format($tot,0) : '—' }}</td>
                    <td class="text-right fw-600" style="color:var(--sc-green)">{{ $p['profit'] ? '₹'.number_format($p['profit'],0) : '—' }}</td>
                    <td class="text-right text-muted">{{ $p['lab'] > 0 ? round($p['profit'] / $p['lab'] * 100).'%' : '—' }}</td>
                    <td class="text-right fw-600" style="color:var(--sc-green)">{{ $p['pharm_profit'] ? '₹'.number_format($p['pharm_profit'],0) : '—' }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="font-weight:700;border-top:2px solid var(--sc-border)">
                    <td>Total</td>
                    <td class="text-right">₹{{ number_format($totals['opd'],0) }}</td>
                    <td class="text-right">₹{{ number_format($totals['ipd'],0) }}</td>
                    <td class="text-right">₹{{ number_format($totals['lab'],0) }}</td>
                    <td class="text-right">₹{{ number_format($totals['pharm'],0) }}</td>
                    <td class="text-right">₹{{ number_format($totals['all'],0) }}</td>
                    <td class="text-right" style="color:var(--sc-green)">₹{{ number_format($totals['profit'],0) }}</td>
                    <td class="text-right">{{ $totals['lab'] > 0 ? $margin.'%' : '—' }}</td>
                    <td class="text-right" style="color:var(--sc-green)">₹{{ number_format($totals['pharm_profit'],0) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

{{-- Top doctors --}}
@if($topDoctors->count())
<div class="card">
    <div class="card-header"><i class="fa-solid fa-ranking-star"></i> Top Doctors by OPD Revenue — {{ $from->format('d M Y') }} to {{ $to->format('d M Y') }}</div>
    <div class="table-wrap">
        <table class="sc-table">
            <thead>
                <tr><th>#</th><th>Doctor</th><th class="text-right">Bills</th><th class="text-right">Revenue</th><th style="width:30%">Share</th></tr>
            </thead>
            <tbody>
                @foreach($topDoctors as $i => $doc)
                @php $share = $totals['opd'] > 0 ? round($doc->total / $totals['opd'] * 100) : 0; @endphp
                <tr>
                    <td class="fw-700 text-muted">{{ $i+1 }}</td>
                    <td class="fw-600">{{ $doc->name }}</td>
                    <td class="text-right text-muted">{{ $doc->cnt }}</td>
                    <td class="text-right fw-700" style="color:var(--sc-teal)">₹{{ number_format($doc->total,0) }}</td>
                    <td>
                        <div style="display:flex;align-items:center;gap:8px">
                            <div style="flex:1;height:6px;background:var(--sc-border);border-radius:3px;overflow:hidden">
                                <div style="height:100%;width:{{ $share }}%;background:var(--sc-teal);border-radius:3px"></div>
                            </div>
                            <span style="font-size:11px;color:var(--sc-muted);width:28px">{{ $share }}%</span>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@php
    $chartPeriods = [];
    foreach ($periods as $p) {
        $chartPeriods[] = ['label' => $p['label'], 'opd' => round($p['opd']), 'ipd' => round($p['ipd']), 'lab' => round($p['lab']), 'profit' => round($p['profit']),
                           'pharm' => round($p['pharm']), 'pharm_profit' => round($p['pharm_profit'])];
    }
@endphp
<script>
const periods = @json($chartPeriods);
const pick = k => periods.map(p => p[k]);

new Chart(document.getElementById('revenueChart'), {
    type: 'bar',
    data: {
        labels: pick('label'),
        datasets: [
            { label: 'OPD', data: pick('opd'), backgroundColor: '#0ab4b4cc', borderRadius: 4 },
            { label: 'IPD', data: pick('ipd'), backgroundColor: '#0f1f3dcc', borderRadius: 4 },
            { label: 'Lab', data: pick('lab'), backgroundColor: '#f5a623cc', borderRadius: 4 },
            { label: 'Pharmacy', data: pick('pharm'), backgroundColor: '#8b5cf6cc', borderRadius: 4 },
            // Profit is part of lab revenue, not extra revenue: drawn as its own
            // (unstacked) line so it doesn't add to the bar totals.
            { type: 'line', label: 'Lab Profit', data: pick('profit'), stack: 'profit', borderColor: '#16a34a', backgroundColor: '#16a34a', borderWidth: 2, pointRadius: periods.length > 60 ? 0 : 3, tension: .25 },
            { type: 'line', label: 'Medicine Profit', data: pick('pharm_profit'), stack: 'pharm_profit', borderColor: '#7c3aed', backgroundColor: '#7c3aed', borderWidth: 2, borderDash: [5, 4], pointRadius: periods.length > 60 ? 0 : 3, tension: .25 },
        ]
    },
    options: {
        responsive: true, plugins: { legend: { position: 'top' } },
        scales: { x: { stacked: true }, y: { stacked: true, ticks: { callback: v => '₹'+v.toLocaleString('en-IN') } } }
    }
});

new Chart(document.getElementById('donutChart'), {
    type: 'doughnut',
    data: {
        labels: ['OPD','IPD','Lab','Pharmacy'],
        datasets: [{ data: [{{ round($totals['opd']) }}, {{ round($totals['ipd']) }}, {{ round($totals['lab']) }}, {{ round($totals['pharm']) }}], backgroundColor: ['#0ab4b4','#0f1f3d','#f5a623','#8b5cf6'], borderWidth: 2 }]
    },
    options: { responsive: true, plugins: { legend: { display: false } }, cutout: '65%' }
});
</script>
@endpush

@push('styles')
<style>
    @media print { button, #rev-filter { display:none !important; } }
    /* KPI strip: all eight figures on one row, wrapping to 4 per row on narrow screens */
    .rev-kpis { display:grid; grid-template-columns:repeat(8, minmax(0, 1fr)); gap:10px; margin-bottom:20px; }
    .rev-kpi { background:var(--sc-surface); border:1px solid var(--sc-border); border-left:4px solid; border-radius:var(--radius); padding:10px 12px; min-width:0; }
    .rev-kpi-label { font-size:11px; color:var(--sc-muted); text-transform:uppercase; letter-spacing:.02em; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .rev-kpi-value { font-size:clamp(14px, 1.35vw, 19px); font-weight:700; color:var(--sc-navy); line-height:1.25; margin-top:3px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; font-variant-numeric:tabular-nums; }
    .rev-kpi-sub { font-size:11px; color:var(--sc-muted); margin-top:2px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    @media (max-width: 1100px) { .rev-kpis { grid-template-columns:repeat(4, minmax(0, 1fr)); } }
    @media (max-width: 520px)  { .rev-kpis { grid-template-columns:repeat(2, minmax(0, 1fr)); } }
</style>
@endpush
@endsection
