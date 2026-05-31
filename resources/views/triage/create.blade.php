{{-- FILE: resources/views/triage/create.blade.php --}}
@extends('layouts.app')
@section('title','Triage Patient')
@section('page-title','Triage')
@section('content')

<div class="page-header">
    <div class="page-header-left">
        <h1>Triage — <span class="mono">{{ $token->display }}</span></h1>
    </div>
    <a href="{{ route('triage.index') }}" class="btn btn-outline">Back</a>
</div>

{{-- Patient summary bar --}}
<div class="triage-patient-bar">
    <div class="tpb-token">{{ $token->display }}</div>
    <div class="tpb-info">
        <div class="tpb-name">{{ $token->patient->first_name }} {{ $token->patient->last_name }}</div>
        <div class="tpb-meta">{{ $token->patient->jhc_id }} &nbsp;·&nbsp; {{ $token->patient->gender }}, {{ $token->patient->age }} yrs
            @if($token->doctor) &nbsp;·&nbsp; Dr. {{ $token->doctor->first_name }} {{ $token->doctor->last_name }} @endif
        </div>
    </div>
    @if($token->purpose)
    <div class="tpb-purpose"><i class="fa-solid fa-comment-medical me-1"></i>{{ $token->purpose }}</div>
    @endif
</div>

<form method="POST" action="{{ route('triage.store', $token) }}">
@csrf

<div class="triage-form-grid">

    {{-- Triage Level --}}
    <div class="card triage-level-card">
        <div class="card-header"><strong><i class="fa-solid fa-triangle-exclamation me-1"></i>Triage Level</strong></div>
        <div class="card-body">
            <div class="triage-level-options">
                <label class="triage-opt triage-opt-green {{ old('triage_level', $token->triage?->triage_level ?? 'green') === 'green' ? 'selected' : '' }}">
                    <input type="radio" name="triage_level" value="green"
                        {{ old('triage_level', $token->triage?->triage_level ?? 'green') === 'green' ? 'checked' : '' }}>
                    <div class="triage-opt-icon"><i class="fa-solid fa-circle-check"></i></div>
                    <div class="triage-opt-label">GREEN</div>
                    <div class="triage-opt-desc">Non-Urgent<br>Stable, can wait</div>
                </label>
                <label class="triage-opt triage-opt-yellow {{ old('triage_level', $token->triage?->triage_level) === 'yellow' ? 'selected' : '' }}">
                    <input type="radio" name="triage_level" value="yellow"
                        {{ old('triage_level', $token->triage?->triage_level) === 'yellow' ? 'checked' : '' }}>
                    <div class="triage-opt-icon"><i class="fa-solid fa-circle-exclamation"></i></div>
                    <div class="triage-opt-label">YELLOW</div>
                    <div class="triage-opt-desc">Semi-Urgent<br>Needs attention soon</div>
                </label>
                <label class="triage-opt triage-opt-red {{ old('triage_level', $token->triage?->triage_level) === 'red' ? 'selected' : '' }}">
                    <input type="radio" name="triage_level" value="red"
                        {{ old('triage_level', $token->triage?->triage_level) === 'red' ? 'checked' : '' }}>
                    <div class="triage-opt-icon"><i class="fa-solid fa-circle-radiation"></i></div>
                    <div class="triage-opt-label">RED</div>
                    <div class="triage-opt-desc">Critical / Urgent<br>Immediate attention</div>
                </label>
            </div>
        </div>
    </div>

    {{-- Chief Complaint --}}
    <div class="card">
        <div class="card-header"><strong><i class="fa-solid fa-comment-medical me-1"></i>Chief Complaint</strong></div>
        <div class="card-body">
            <textarea name="chief_complaint" rows="3" class="form-control"
                placeholder="Patient's main complaint…">{{ old('chief_complaint', $token->triage?->chief_complaint ?? $token->purpose) }}</textarea>
        </div>
    </div>

    {{-- Vitals --}}
    <div class="card triage-vitals-card">
        <div class="card-header"><strong><i class="fa-solid fa-heart-pulse me-1"></i>Vitals</strong></div>
        <div class="card-body">
            <div class="vitals-grid">
                <div class="vital-group">
                    <label class="form-label">Blood Pressure</label>
                    <div class="d-flex gap-2 align-items-center">
                        <input type="number" name="bp_systolic" class="form-control" placeholder="Sys" min="40" max="300"
                            value="{{ old('bp_systolic', $token->triage?->bp_systolic ?? 120) }}" style="width:80px">
                        <span style="color:#888">/</span>
                        <input type="number" name="bp_diastolic" class="form-control" placeholder="Dia" min="20" max="200"
                            value="{{ old('bp_diastolic', $token->triage?->bp_diastolic ?? 80) }}" style="width:80px">
                        <span class="vital-unit">mmHg</span>
                    </div>
                </div>
                <div class="vital-group">
                    <label class="form-label">Pulse</label>
                    <div class="d-flex gap-2 align-items-center">
                        <input type="number" name="pulse" class="form-control" placeholder="e.g. 72" min="20" max="300"
                            value="{{ old('pulse', $token->triage?->pulse ?? 72) }}" style="width:100px">
                        <span class="vital-unit">bpm</span>
                    </div>
                </div>
                <div class="vital-group">
                    <label class="form-label">Temperature</label>
                    <div class="d-flex gap-2 align-items-center">
                        <input type="number" name="temperature" class="form-control" placeholder="e.g. 98.6" step="0.1" min="90" max="115"
                            value="{{ old('temperature', $token->triage?->temperature ?? 98.6) }}" style="width:100px">
                        <span class="vital-unit">°F</span>
                    </div>
                </div>
                <div class="vital-group">
                    <label class="form-label">SpO2</label>
                    <div class="d-flex gap-2 align-items-center">
                        <input type="number" name="spo2" class="form-control" placeholder="e.g. 98" step="0.1" min="50" max="100"
                            value="{{ old('spo2', $token->triage?->spo2 ?? 98) }}" style="width:100px">
                        <span class="vital-unit">%</span>
                    </div>
                </div>
                <div class="vital-group">
                    <label class="form-label">Weight</label>
                    <div class="d-flex gap-2 align-items-center">
                        <input type="number" name="weight" id="weight" class="form-control" placeholder="e.g. 68" step="0.1" min="1" max="500"
                            value="{{ old('weight', $token->triage?->weight) }}" style="width:100px" oninput="calcBmi()">
                        <span class="vital-unit">kg</span>
                    </div>
                </div>
                <div class="vital-group">
                    <label class="form-label">Height</label>
                    <div class="d-flex gap-2 align-items-center">
                        <input type="number" name="height" id="height" class="form-control" placeholder="e.g. 165" step="0.1" min="10" max="300"
                            value="{{ old('height', $token->triage?->height) }}" style="width:100px" oninput="calcBmi()">
                        <span class="vital-unit">cm</span>
                    </div>
                </div>
                <div class="vital-group">
                    <label class="form-label">BMI <span id="bmiVal" style="font-weight:700;color:#1d4ed8"></span></label>
                    <div class="d-flex gap-2 align-items-center">
                        <input type="text" id="bmiDisplay" class="form-control" placeholder="Auto-calculated" readonly
                            style="width:100px;background:#f8f9fa">
                        <span class="vital-unit" id="bmiCategory" style="font-size:11px;color:#666"></span>
                    </div>
                </div>
                <div class="vital-group">
                    <label class="form-label">Blood Glucose</label>
                    <div class="d-flex gap-2 align-items-center">
                        <input type="text" name="blood_glucose" class="form-control" placeholder="e.g. 110 mg/dL"
                            value="{{ old('blood_glucose', $token->triage?->blood_glucose) }}" style="width:140px">
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Nurse Notes --}}
    <div class="card">
        <div class="card-header"><strong><i class="fa-solid fa-pen-to-square me-1"></i>Nurse Notes</strong></div>
        <div class="card-body">
            <textarea name="nurse_notes" rows="3" class="form-control"
                placeholder="Any additional observations…">{{ old('nurse_notes', $token->triage?->nurse_notes ?? 'Came for regular check up') }}</textarea>
        </div>
    </div>

</div>

<div class="mt-4 d-flex gap-2">
    <button type="submit" class="btn btn-primary btn-lg"><i class="fa-solid fa-floppy-disk me-1"></i>Save Triage</button>
    <a href="{{ route('triage.index') }}" class="btn btn-outline btn-lg">Cancel</a>
</div>

</form>

@push('styles')
<style>
.triage-patient-bar {
    display:flex; align-items:center; gap:16px;
    background:#fff; border:1px solid #e0e0e0; border-radius:6px;
    padding:12px 18px; margin-bottom:16px;
}
.tpb-token { font-size:28px; font-weight:900; color:#1d4ed8; min-width:80px; }
.tpb-name  { font-size:16px; font-weight:700; }
.tpb-meta  { font-size:12px; color:#666; margin-top:2px; }
.tpb-purpose { margin-left:auto; font-size:12px; color:#555; font-style:italic; background:#f0f6ff; padding:4px 10px; border-radius:4px; }

.triage-form-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
.triage-level-card { grid-column:1/-1; }
.triage-vitals-card { grid-column:1/-1; }
@media(max-width:700px){ .triage-form-grid { grid-template-columns:1fr; } }

.card-header { background:#f8f9fa; padding:10px 16px; border-bottom:1px solid #e0e0e0; }
.card-body   { padding:16px; }

/* Triage level selector */
.triage-level-options { display:flex; gap:16px; }
@media(max-width:600px){ .triage-level-options { flex-direction:column; } }
.triage-opt { flex:1; border:2px solid #e0e0e0; border-radius:8px; padding:16px; text-align:center; cursor:pointer; transition:.15s; }
.triage-opt input { display:none; }
.triage-opt-icon { font-size:28px; margin-bottom:8px; }
.triage-opt-label { font-size:16px; font-weight:800; letter-spacing:1px; margin-bottom:4px; }
.triage-opt-desc  { font-size:11px; color:#888; line-height:1.4; }
.triage-opt-green  { border-color:#e0e0e0; } .triage-opt-green.selected  { border-color:#16a34a; background:#f0fdf4; } .triage-opt-green  .triage-opt-icon { color:#16a34a; }
.triage-opt-yellow { border-color:#e0e0e0; } .triage-opt-yellow.selected { border-color:#d97706; background:#fffbeb; } .triage-opt-yellow .triage-opt-icon { color:#d97706; }
.triage-opt-red    { border-color:#e0e0e0; } .triage-opt-red.selected    { border-color:#dc2626; background:#fff5f5; } .triage-opt-red    .triage-opt-icon { color:#dc2626; }

/* Vitals */
.vitals-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(200px,1fr)); gap:16px; }
.vital-group label { font-size:11.5px; font-weight:600; margin-bottom:6px; display:block; }
.vital-unit { font-size:11px; color:#888; white-space:nowrap; }
</style>
@endpush

@push('scripts')
<script>
// Triage option visual selection
document.querySelectorAll('.triage-opt input').forEach(radio => {
    radio.addEventListener('change', () => {
        document.querySelectorAll('.triage-opt').forEach(o => o.classList.remove('selected'));
        radio.closest('.triage-opt').classList.add('selected');
    });
});

// BMI auto-calc
function calcBmi() {
    const w = parseFloat(document.getElementById('weight').value);
    const h = parseFloat(document.getElementById('height').value);
    if (w && h) {
        const hm = h / 100;
        const bmi = (w / (hm * hm)).toFixed(1);
        document.getElementById('bmiDisplay').value = bmi;
        let cat = '';
        if (bmi < 18.5) cat = 'Underweight';
        else if (bmi < 25) cat = 'Normal';
        else if (bmi < 30) cat = 'Overweight';
        else cat = 'Obese';
        document.getElementById('bmiCategory').textContent = cat;
    } else {
        document.getElementById('bmiDisplay').value = '';
        document.getElementById('bmiCategory').textContent = '';
    }
}
calcBmi();
</script>
@endpush
@endsection
