@extends('layouts.app')
@section('title', 'Revenue Dashboard')
@section('content')

<div class="page-header">
    <div class="page-header-left">
        <h1>Revenue Dashboard</h1>
        <div class="breadcrumb">Reports &rsaquo; Revenue</div>
    </div>
    <button onclick="window.print()" class="btn btn-outline">
        <i class="fa-solid fa-print"></i> Print
    </button>
</div>

{{-- Summary strip for current month --}}
@php
    $thisMonthTotal = array_sum($donut);
    $lastVal = $chartOpd->last() + $chartIpd->last() + $chartLab->last();
@endphp
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px">
    <div class="stat-card">
        <div class="stat-icon green"><i class="fa-solid fa-circle-dollar-to-slot"></i></div>
        <div>
            <div class="stat-value" style="color:var(--sc-green)">₹{{ number_format($thisMonthTotal,0) }}</div>
            <div class="stat-label">This Month Total</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon navy"><i class="fa-solid fa-stethoscope"></i></div>
        <div>
            <div class="stat-value">₹{{ number_format($donut['OPD'],0) }}</div>
            <div class="stat-label">OPD Collections</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon teal"><i class="fa-solid fa-bed-pulse"></i></div>
        <div>
            <div class="stat-value">₹{{ number_format($donut['IPD'],0) }}</div>
            <div class="stat-label">IPD Deposits</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon amber"><i class="fa-solid fa-flask"></i></div>
        <div>
            <div class="stat-value">₹{{ number_format($donut['Lab'],0) }}</div>
            <div class="stat-label">Lab Revenue</div>
        </div>
    </div>
</div>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;margin-bottom:24px">
    {{-- Monthly bar chart --}}
    <div class="card">
        <div class="card-header"><i class="fa-solid fa-chart-bar"></i> Monthly Revenue — Last 12 Months</div>
        <div class="card-body">
            <canvas id="revenueChart" height="100"></canvas>
        </div>
    </div>

    {{-- Current month donut --}}
    <div class="card">
        <div class="card-header"><i class="fa-solid fa-chart-pie"></i> {{ now()->format('F Y') }} Breakdown</div>
        <div class="card-body" style="display:flex;flex-direction:column;align-items:center;justify-content:center">
            <canvas id="donutChart" width="200" height="200" style="max-width:180px"></canvas>
            <div style="margin-top:16px;width:100%">
                @foreach(['OPD' => '#0ab4b4', 'IPD' => '#0f1f3d', 'Lab' => '#f5a623'] as $label => $color)
                <div style="display:flex;justify-content:space-between;font-size:12px;padding:4px 0;border-bottom:1px solid var(--sc-border)">
                    <span style="display:flex;align-items:center;gap:6px">
                        <span style="width:10px;height:10px;background:{{ $color }};border-radius:50%;display:inline-block"></span>
                        {{ $label }}
                    </span>
                    <span class="fw-600">₹{{ number_format($donut[$label],0) }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

{{-- Top doctors --}}
@if($topDoctors->count())
<div class="card">
    <div class="card-header"><i class="fa-solid fa-ranking-star"></i> Top Doctors by OPD Revenue — {{ now()->format('F Y') }}</div>
    <div class="table-wrap">
        <table class="sc-table">
            <thead>
                <tr><th>#</th><th>Doctor</th><th class="text-right">Bills</th><th class="text-right">Revenue</th><th style="width:30%">Share</th></tr>
            </thead>
            <tbody>
                @foreach($topDoctors as $i => $doc)
                @php $share = $donut['OPD'] > 0 ? round($doc->total / $donut['OPD'] * 100) : 0; @endphp
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
<script>
const labels = @json($chartLabels);
const opd    = @json($chartOpd);
const ipd    = @json($chartIpd);
const lab    = @json($chartLab);

new Chart(document.getElementById('revenueChart'), {
    type: 'bar',
    data: {
        labels,
        datasets: [
            { label: 'OPD', data: opd, backgroundColor: '#0ab4b4cc', borderRadius: 4 },
            { label: 'IPD', data: ipd, backgroundColor: '#0f1f3dcc', borderRadius: 4 },
            { label: 'Lab', data: lab, backgroundColor: '#f5a623cc', borderRadius: 4 },
        ]
    },
    options: {
        responsive: true, plugins: { legend: { position: 'top' } },
        scales: { x: { stacked: true }, y: { stacked: true, ticks: { callback: v => '₹'+v.toLocaleString('en-IN') } } }
    }
});

const donutData = @json(array_values($donut));
new Chart(document.getElementById('donutChart'), {
    type: 'doughnut',
    data: {
        labels: ['OPD','IPD','Lab'],
        datasets: [{ data: donutData, backgroundColor: ['#0ab4b4','#0f1f3d','#f5a623'], borderWidth: 2 }]
    },
    options: { responsive: true, plugins: { legend: { display: false } }, cutout: '65%' }
});
</script>
@endpush

@push('styles')
<style>@media print { button { display:none !important; } }</style>
@endpush
@endsection
