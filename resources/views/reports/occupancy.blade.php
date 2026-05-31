{{-- FILE: resources/views/reports/occupancy.blade.php --}}
@extends('layouts.app')
@section('title','Bed Occupancy Trend')
@section('page-title','Occupancy Trend')
@section('content')

<div class="page-header">
    <div class="page-header-left">
        <h1>Bed Occupancy Trend</h1>
        <div class="breadcrumb">Reports &mdash; Last 30 Days Occupancy</div>
    </div>
    <div class="page-header-right">
        <a href="{{ route('reports.index') }}" class="btn btn-outline"><i class="fa-solid fa-arrow-left"></i> All Reports</a>
        <a href="{{ route('ipd.census') }}" class="btn btn-outline"><i class="fa-solid fa-clipboard-list"></i> Daily Census</a>
        <button onclick="window.print()" class="btn btn-outline no-print"><i class="fa-solid fa-print"></i> Print</button>
    </div>
</div>

{{-- Summary cards --}}
<div class="grid" style="grid-template-columns:repeat(5,1fr);gap:16px;margin-bottom:20px">
    <div class="card">
        <div class="card-body" style="padding:16px">
            <div class="form-label">Total Beds</div>
            <div class="fw-700 mono" style="font-size:26px;color:var(--sc-navy)">{{ $totalBeds }}</div>
        </div>
    </div>
    <div class="card">
        <div class="card-body" style="padding:16px">
            <div class="form-label">Current Occupancy</div>
            <div class="fw-700 mono" style="font-size:26px;color:{{ $currentOccupancy >= 80 ? '#dc2626' : ($currentOccupancy >= 60 ? 'var(--sc-amber)' : 'var(--sc-green)') }}">
                {{ $currentOccupancy }}%
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-body" style="padding:16px">
            <div class="form-label">Avg Occupancy (30d)</div>
            <div class="fw-700 mono" style="font-size:26px;color:var(--sc-teal)">{{ $avgOccupancy }}%</div>
        </div>
    </div>
    <div class="card">
        <div class="card-body" style="padding:16px">
            <div class="form-label">Peak Day</div>
            @if($peakDay)
            <div class="fw-700 mono" style="font-size:22px;color:var(--sc-amber)">{{ $peakDay['pct'] }}%</div>
            <div class="text-small text-muted">{{ \Carbon\Carbon::parse($peakDay['date'])->format('d M Y') }}</div>
            @else
            <div class="fw-700" style="font-size:22px;color:#aaa">—</div>
            @endif
        </div>
    </div>
    <div class="card">
        <div class="card-body" style="padding:16px">
            <div class="form-label">Today Activity</div>
            <div style="font-size:13px;margin-top:6px">
                <div class="fw-600" style="color:var(--sc-green)"><i class="fa-solid fa-arrow-down"></i> {{ $todayAdmissions }} admitted</div>
                <div class="fw-600" style="color:var(--sc-teal);margin-top:4px"><i class="fa-solid fa-arrow-up"></i> {{ $todayDischarges }} discharged</div>
            </div>
        </div>
    </div>
</div>

{{-- Occupancy chart --}}
<div class="card mb-4">
    <div class="card-header">
        <i class="fa-solid fa-chart-line"></i> Daily Occupancy % — Last 30 Days
        <div class="card-actions">
            <span class="badge badge-navy">{{ now()->subDays(29)->format('d M') }} – {{ now()->format('d M Y') }}</span>
        </div>
    </div>
    <div class="card-body" style="padding:20px">
        <canvas id="occupancyChart" style="max-height:320px;width:100%"></canvas>
    </div>
</div>

{{-- Daily data table --}}
<div class="card">
    <div class="card-header">
        <i class="fa-solid fa-table"></i> Daily Occupancy Data
    </div>
    <div class="table-wrap">
        <table class="sc-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th class="text-center">Occupied Beds</th>
                    <th class="text-center">Total Beds</th>
                    <th class="text-center">Occupancy %</th>
                    <th style="width:220px">Visual</th>
                </tr>
            </thead>
            <tbody>
                @foreach($dailyData->reverse() as $day)
                @php
                    $barColor = $day['pct'] >= 90 ? '#dc2626' : ($day['pct'] >= 75 ? '#d97706' : '#0ab4b4');
                    $isToday  = $day['date'] === today()->toDateString();
                @endphp
                <tr @if($isToday) style="background:#fffbeb" @endif>
                    <td>
                        <span class="{{ $isToday ? 'fw-700' : '' }}">
                            {{ \Carbon\Carbon::parse($day['date'])->format('D, d M Y') }}
                        </span>
                        @if($isToday) <span class="badge badge-amber" style="margin-left:6px">Today</span> @endif
                    </td>
                    <td class="text-center mono fw-600">{{ $day['occupied'] }}</td>
                    <td class="text-center mono">{{ $totalBeds }}</td>
                    <td class="text-center">
                        <span class="badge" style="background:{{ $barColor }}20;color:{{ $barColor }};font-size:12px;padding:3px 10px">
                            {{ $day['pct'] }}%
                        </span>
                    </td>
                    <td>
                        <div style="height:8px;background:var(--sc-border);border-radius:4px;overflow:hidden">
                            <div style="height:100%;width:{{ min($day['pct'],100) }}%;background:{{ $barColor }};border-radius:4px;transition:width .3s"></div>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function () {
    const labels   = {!! $chartLabels !!};
    const pctData  = {!! $chartData !!};
    const occData  = {!! $chartOccupied !!};
    const totalBeds = {{ $totalBeds }};

    const ctx = document.getElementById('occupancyChart').getContext('2d');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Occupancy %',
                    data: pctData,
                    borderColor: '#0f1f3d',
                    backgroundColor: 'rgba(15,31,61,0.08)',
                    borderWidth: 2.5,
                    pointRadius: 3,
                    pointHoverRadius: 6,
                    tension: 0.35,
                    fill: true,
                    yAxisID: 'yPct',
                },
                {
                    label: 'Occupied Beds',
                    data: occData,
                    borderColor: '#0ab4b4',
                    backgroundColor: 'transparent',
                    borderWidth: 1.5,
                    borderDash: [5,3],
                    pointRadius: 2,
                    pointHoverRadius: 5,
                    tension: 0.35,
                    fill: false,
                    yAxisID: 'yBeds',
                }
            ]
        },
        options: {
            responsive: true,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { position: 'top' },
                tooltip: {
                    callbacks: {
                        label: function(ctx) {
                            if (ctx.dataset.yAxisID === 'yPct') {
                                return ' Occupancy: ' + ctx.parsed.y + '%';
                            }
                            return ' Beds Occupied: ' + ctx.parsed.y + ' / ' + totalBeds;
                        }
                    }
                }
            },
            scales: {
                yPct: {
                    type: 'linear',
                    position: 'left',
                    min: 0,
                    max: 100,
                    ticks: {
                        callback: v => v + '%',
                        stepSize: 20,
                    },
                    grid: { color: 'rgba(0,0,0,0.05)' },
                    title: { display: true, text: 'Occupancy %' }
                },
                yBeds: {
                    type: 'linear',
                    position: 'right',
                    min: 0,
                    max: totalBeds,
                    ticks: { stepSize: 1 },
                    grid: { drawOnChartArea: false },
                    title: { display: true, text: 'Beds' }
                },
                x: {
                    ticks: { maxRotation: 45, minRotation: 30, font: { size: 10 } },
                    grid: { color: 'rgba(0,0,0,0.04)' }
                }
            }
        }
    });
})();
</script>
@endpush

@endsection
