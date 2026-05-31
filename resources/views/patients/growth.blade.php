@extends('layouts.app')
@section('title', 'Growth Charts — ' . $patient->full_name)
@section('page-title', 'Growth Charts')
@section('content')

<div class="page-header">
    <div class="page-header-left">
        <h1>Growth Charts</h1>
        <div class="breadcrumb"><a href="{{ route('patients.show', $patient) }}">{{ $patient->full_name }}</a> &rsaquo; Growth</div>
    </div>
    @if($isPaediatric)
        <a href="{{ route('vaccinations.index', $patient) }}" class="btn btn-outline btn-sm"><i class="fa-solid fa-syringe"></i> Vaccination Card</a>
    @endif
    <a href="{{ route('patients.show', $patient) }}" class="btn btn-outline btn-sm"><i class="fa-solid fa-arrow-left"></i> Back</a>
</div>

<div class="patient-strip mb-3">
    <div class="ps-avatar">{{ strtoupper(substr($patient->first_name,0,1)) }}</div>
    <div>
        <div class="ps-name">{{ $patient->full_name }}</div>
        <div class="ps-jhc_id">{{ $patient->jhc_id }}</div>
        <div class="ps-meta">
            <span><i class="fa-solid fa-calendar"></i> {{ $patient->age }}</span>
            <span><i class="fa-solid fa-venus-mars"></i> {{ $patient->gender }}</span>
            @if($patient->date_of_birth)<span><i class="fa-solid fa-cake-candles"></i> DOB {{ $patient->date_of_birth->format('d M Y') }}</span>@endif
        </div>
    </div>
</div>

@if($weightPoints->isEmpty() && $heightPoints->isEmpty())
<x-empty-state
    icon="fa-chart-line"
    title="No growth data yet"
    message="Weight and height aren't recorded on this patient's encounters. Update vitals on encounters to build a growth chart." />
@else

<div class="grid grid-2" style="gap:20px">
    @if($weightPoints->isNotEmpty())
    <div class="card">
        <div class="card-header"><i class="fa-solid fa-weight-scale"></i> Weight vs Age
            <span class="text-muted" style="font-weight:400;font-size:11px;margin-left:6px">{{ $weightPoints->count() }} readings</span>
        </div>
        <div class="card-body"><canvas id="weightChart" height="120"></canvas></div>
    </div>
    @endif

    @if($heightPoints->isNotEmpty())
    <div class="card">
        <div class="card-header"><i class="fa-solid fa-ruler-vertical"></i> Height vs Age
            <span class="text-muted" style="font-weight:400;font-size:11px;margin-left:6px">{{ $heightPoints->count() }} readings</span>
        </div>
        <div class="card-body"><canvas id="heightChart" height="120"></canvas></div>
    </div>
    @endif

    @if($bmiPoints->isNotEmpty())
    <div class="card" style="grid-column:1/-1">
        <div class="card-header"><i class="fa-solid fa-chart-line"></i> BMI vs Age
            <span class="text-muted" style="font-weight:400;font-size:11px;margin-left:6px">{{ $bmiPoints->count() }} readings</span>
        </div>
        <div class="card-body"><canvas id="bmiChart" height="80"></canvas></div>
    </div>
    @endif
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const xUnit = @json($isPaediatric ? 'months' : 'years');
function build(canvasId, label, color, points, yLabel) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return;
    new Chart(ctx, {
        type: 'line',
        data: {
            datasets: [{
                label: label,
                data: points.map(p => ({ x: xUnit === 'months' ? p.age_months : p.age_years, y: p.value })),
                borderColor: color, backgroundColor: color + '22',
                tension: 0.25, pointRadius: 5, fill: true,
                showLine: true,
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: {
                    label: (ctx) => `${label}: ${ctx.parsed.y} (${xUnit === 'months' ? ctx.parsed.x + ' mo' : ctx.parsed.x + ' yr'})`
                }}
            },
            scales: {
                x: { type: 'linear', title: { display: true, text: xUnit === 'months' ? 'Age (months)' : 'Age (years)' }, ticks: { font: { size: 10 } } },
                y: { title: { display: true, text: yLabel }, ticks: { font: { size: 10 } } }
            }
        }
    });
}

@if($weightPoints->isNotEmpty())
build('weightChart', 'Weight (kg)', '#0ab4b4', @json($weightPoints->map(fn($p) => ['age_months' => $p->age_months, 'age_years' => $p->age_years, 'value' => $p->weight])), 'Weight (kg)');
@endif
@if($heightPoints->isNotEmpty())
build('heightChart', 'Height (cm)', '#1e3360', @json($heightPoints->map(fn($p) => ['age_months' => $p->age_months, 'age_years' => $p->age_years, 'value' => $p->height])), 'Height (cm)');
@endif
@if($bmiPoints->isNotEmpty())
build('bmiChart', 'BMI', '#f5a623', @json($bmiPoints->map(fn($p) => ['age_months' => $p->age_months, 'age_years' => $p->age_years, 'value' => $p->bmi])), 'BMI (kg/m²)');
@endif
</script>
@endif
@endsection
