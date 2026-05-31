@extends('layouts.app')
@section('title', 'Diagnosis Analytics')
@section('page-title', 'Diagnosis Analytics')
@section('content')

<div class="page-header">
    <div class="page-header-left">
        <h1>Diagnosis Analytics</h1>
        <div class="breadcrumb"><a href="{{ route('reports.index') }}">Reports</a> &rsaquo; Diagnoses</div>
    </div>
    <a href="{{ route('reports.index') }}" class="btn btn-outline btn-sm"><i class="fa-solid fa-arrow-left"></i> Back</a>
</div>

<form method="GET" class="card mb-3">
    <div class="card-body" style="display:flex;gap:14px;align-items:end;flex-wrap:wrap">
        <div class="form-group" style="margin:0">
            <label class="form-label">From</label>
            <input type="date" name="from" class="form-control" value="{{ $fromDate }}">
        </div>
        <div class="form-group" style="margin:0">
            <label class="form-label">To</label>
            <input type="date" name="to" class="form-control" value="{{ $toDate }}">
        </div>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-filter"></i> Apply</button>
    </div>
</form>

<div class="grid grid-3 mb-3">
    <div class="stat-card">
        <div class="stat-label">Total Codings</div>
        <div class="stat-value">{{ number_format($totals['total_codings']) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Unique Diagnoses</div>
        <div class="stat-value">{{ number_format($totals['unique_diagnoses']) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Unique Patients</div>
        <div class="stat-value">{{ number_format($totals['unique_patients']) }}</div>
    </div>
</div>

<div class="grid" style="grid-template-columns:1fr 320px;gap:20px;align-items:start">
    <div>
        <div class="card mb-3">
            <div class="card-header"><i class="fa-solid fa-chart-bar"></i> Top 20 Diagnoses</div>
            <div class="table-wrap">
                <table class="sc-table">
                    <thead>
                        <tr><th style="width:40px">#</th><th>Diagnosis</th><th>ICD</th><th style="text-align:right">Codings</th><th style="text-align:right">Unique Patients</th><th style="width:30%">Share</th></tr>
                    </thead>
                    <tbody>
                    @php $maxCount = $top->max('count') ?: 1; @endphp
                    @forelse($top as $i => $row)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td class="fw-600">{{ $row->diagnosis_name }}</td>
                        <td class="mono text-muted">{{ $row->icd_code ?: '—' }}</td>
                        <td style="text-align:right"><strong>{{ $row->count }}</strong></td>
                        <td style="text-align:right">{{ $row->patient_count }}</td>
                        <td>
                            <div style="background:var(--sc-bg);border-radius:6px;height:14px;position:relative;overflow:hidden">
                                <div style="background:var(--sc-teal);height:100%;width:{{ round($row->count * 100 / $maxCount) }}%"></div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" style="padding:0">
                        <x-empty-state icon="fa-diagnoses" title="No diagnoses recorded in this window" message="Try a wider date range or start coding diagnoses on encounters." />
                    </td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($monthly->count() >= 2)
        <div class="card mb-3">
            <div class="card-header"><i class="fa-solid fa-chart-line"></i> Monthly Volume</div>
            <div class="card-body">
                <canvas id="monthlyChart" height="80"></canvas>
            </div>
        </div>
        @endif
    </div>

    <div>
        <div class="card">
            <div class="card-header"><i class="fa-solid fa-tags"></i> By Type</div>
            <div class="card-body" style="display:flex;flex-direction:column;gap:8px">
                @php $typeTotal = $byType->sum('count') ?: 1; @endphp
                @forelse($byType as $t)
                <div>
                    <div style="display:flex;justify-content:space-between;font-size:12.5px;margin-bottom:4px">
                        <span class="fw-600">{{ $t->type ?: 'Unspecified' }}</span>
                        <span class="text-muted">{{ $t->count }} ({{ round($t->count * 100 / $typeTotal) }}%)</span>
                    </div>
                    <div style="background:var(--sc-bg);border-radius:6px;height:8px;overflow:hidden">
                        <div style="background:var(--sc-navy);height:100%;width:{{ round($t->count * 100 / $typeTotal) }}%"></div>
                    </div>
                </div>
                @empty
                <div class="text-muted" style="font-size:12px">No data.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

@if($monthly->count() >= 2)
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
new Chart(document.getElementById('monthlyChart'), {
    type: 'line',
    data: {
        labels: @json($monthly->pluck('ym')),
        datasets: [{
            label: 'Codings',
            data: @json($monthly->pluck('count')),
            borderColor: '#0ab4b4', backgroundColor: 'rgba(10,180,180,.12)',
            tension: 0.25, pointRadius: 4, fill: true,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
    }
});
</script>
@endif
@endsection
