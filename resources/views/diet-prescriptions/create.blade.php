{{-- FILE: resources/views/diet-prescriptions/create.blade.php --}}
@extends('layouts.app')
@section('title','Diet Prescription')
@section('page-title','Diet Prescription')
@section('content')

<div class="page-header">
    <div class="page-header-left">
        <h1>Diet Prescription</h1>
        <div class="breadcrumb">Clinical &rsaquo; Diet Prescription</div>
    </div>
    <a href="{{ route('diet-prescriptions.index') }}" class="btn btn-outline"><i class="fa-solid fa-list"></i> All Prescriptions</a>
</div>

@if($errors->any())
<div class="alert" style="background:#fef2f2;border:1px solid #fca5a5;color:#991b1b;border-radius:8px;padding:12px 16px;margin-bottom:16px">
    <ul style="margin:0;padding-left:18px">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif

<form method="POST" action="{{ route('diet-prescriptions.store') }}" id="diet-form">
@csrf

<div class="grid" style="grid-template-columns:1fr 1fr;gap:20px;align-items:start">

{{-- ── LEFT COLUMN ── --}}
<div>

    {{-- Patient --}}
    <div class="card mb-4">
        <div class="card-header"><i class="fa-solid fa-user"></i> Patient</div>
        <div class="card-body">
            <div class="form-group">
                <label class="form-label">Search Patient <span style="color:#ef4444">*</span></label>
                <input type="text" id="patient-search" class="form-control" placeholder="Type name or JHC ID…"
                       value="{{ $patient ? $patient->full_name.' ('.$patient->jhc_id.')' : '' }}" autocomplete="off">
                <div id="patient-dropdown" style="display:none;position:absolute;z-index:200;background:#fff;border:1px solid var(--sc-border);border-radius:0 0 8px 8px;width:100%;max-height:220px;overflow-y:auto;box-shadow:0 4px 12px rgba(0,0,0,.1)"></div>
                <input type="hidden" name="patient_id" id="patient-id" value="{{ $patient?->id }}">
            </div>
            <div id="patient-info" style="display:{{ $patient ? 'block' : 'none' }};margin-top:10px;padding:10px 12px;background:var(--sc-bg);border-radius:8px;font-size:13px">
                @if($patient)
                <div class="fw-600">{{ $patient->full_name }}</div>
                <div class="text-muted">{{ $patient->jhc_id }} · {{ $patient->age }} · {{ ucfirst($patient->gender) }}</div>
                @if($patient->patientAllergies->isNotEmpty())
                <div style="margin-top:6px;display:flex;flex-wrap:wrap;gap:4px">
                    @foreach($patient->patientAllergies as $al)
                    <span style="padding:2px 8px;border-radius:10px;font-size:11px;font-weight:600;background:#fef2f2;color:#991b1b;border:1px solid #fca5a5"><i class="fa-solid fa-triangle-exclamation" style="font-size:9px"></i> {{ $al->allergen }}</span>
                    @endforeach
                </div>
                @endif
                @endif
            </div>
        </div>
    </div>

    {{-- Measurements --}}
    <div class="card mb-4">
        <div class="card-header"><i class="fa-solid fa-ruler"></i> Measurements & Calorie Calculator</div>
        <div class="card-body">
            <div class="form-row cols-2" style="margin-bottom:16px">
                <div class="form-group">
                    <label class="form-label">Height (cm) <span style="color:#ef4444">*</span></label>
                    <input type="number" name="height_cm" id="height_cm" class="form-control" step="0.1" min="50" max="250"
                           placeholder="e.g. 165" value="{{ old('height_cm') }}" oninput="calcBMIAndCal()">
                </div>
                <div class="form-group">
                    <label class="form-label">Weight (kg) <span style="color:#ef4444">*</span></label>
                    <input type="number" name="weight_kg" id="weight_kg" class="form-control" step="0.1" min="10" max="300"
                           placeholder="e.g. 72" value="{{ old('weight_kg') }}" oninput="calcBMIAndCal()">
                </div>
            </div>
            <div class="form-row cols-2" style="margin-bottom:16px">
                <div class="form-group">
                    <label class="form-label">Target Weight (kg)</label>
                    <input type="number" name="target_weight_kg" id="target_weight_kg" class="form-control" step="0.1"
                           placeholder="Ideal weight" value="{{ old('target_weight_kg') }}" oninput="calcBMIAndCal()">
                </div>
                <div class="form-group">
                    <label class="form-label">Activity Level <span style="color:#ef4444">*</span></label>
                    <select name="activity_level" id="activity_level" class="form-control" onchange="calcBMIAndCal()">
                        @foreach($activityLevels as $key => $al)
                        <option value="{{ $key }}" {{ old('activity_level',$key==='sedentary'?'sedentary':'') === $key ? 'selected':'' }}>{{ $al['label'] }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- BMI Result Box --}}
            <div id="bmi-box" style="display:none;border:1px solid var(--sc-border);border-radius:10px;overflow:hidden;margin-bottom:16px">
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;text-align:center">
                    <div style="padding:12px 8px;border-right:1px solid var(--sc-border)">
                        <div style="font-size:10px;color:var(--sc-muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px">BMI</div>
                        <div id="bmi-val" style="font-size:22px;font-weight:800"></div>
                        <div id="bmi-cat" style="font-size:11px;font-weight:600;margin-top:2px"></div>
                    </div>
                    <div style="padding:12px 8px;border-right:1px solid var(--sc-border)">
                        <div style="font-size:10px;color:var(--sc-muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px">Ideal Weight</div>
                        <div id="ideal-val" style="font-size:18px;font-weight:700;color:var(--sc-teal)"></div>
                        <div style="font-size:11px;color:var(--sc-muted);margin-top:2px">kg (BMI 22)</div>
                    </div>
                    <div style="padding:12px 8px">
                        <div style="font-size:10px;color:var(--sc-muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px">Target Calories</div>
                        <div id="cal-val" style="font-size:22px;font-weight:800;color:var(--sc-navy)"></div>
                        <div style="font-size:11px;color:var(--sc-muted);margin-top:2px">kcal / day</div>
                    </div>
                </div>
                <div id="bmi-bar-wrap" style="height:8px;background:var(--sc-border)">
                    <div id="bmi-bar" style="height:100%;background:var(--sc-teal);transition:.3s;border-radius:0 4px 4px 0"></div>
                </div>
                <div id="cal-advice" style="padding:10px 14px;font-size:12px;color:var(--sc-muted);background:var(--sc-bg)"></div>
            </div>
        </div>
    </div>

    {{-- Conditions --}}
    <div class="card mb-4">
        <div class="card-header"><i class="fa-solid fa-stethoscope"></i> Select Conditions <span style="font-size:12px;font-weight:400;opacity:.7">(choose all that apply)</span></div>
        <div class="card-body">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                @foreach($conditions as $key => $cond)
                <label style="display:flex;align-items:center;gap:10px;padding:10px 12px;border:1.5px solid var(--sc-border);border-radius:8px;cursor:pointer;transition:.15s" class="condition-label" data-key="{{ $key }}">
                    <input type="checkbox" name="conditions[]" value="{{ $key }}"
                           {{ in_array($key, old('conditions',[])) ? 'checked':'' }}
                           onchange="updatePreview()" style="width:16px;height:16px;accent-color:var(--sc-teal);flex-shrink:0">
                    <i class="fa-solid {{ $cond['icon'] }}" style="color:{{ $cond['color'] }};width:18px;text-align:center;font-size:15px"></i>
                    <span style="font-size:13px;font-weight:600;line-height:1.3">{{ $cond['label'] }}</span>
                </label>
                @endforeach
            </div>
            @error('conditions')<div style="color:#ef4444;font-size:12px;margin-top:8px">{{ $message }}</div>@enderror
        </div>
    </div>

    {{-- Notes --}}
    <div class="card mb-4">
        <div class="card-header"><i class="fa-solid fa-pen-to-square"></i> Additional Notes</div>
        <div class="card-body">
            <textarea name="diet_notes" class="form-control" rows="3" placeholder="Additional dietary instructions, restrictions, or special needs…">{{ old('diet_notes') }}</textarea>
        </div>
    </div>

    <button type="submit" class="btn btn-primary" style="width:100%;padding:12px">
        <i class="fa-solid fa-save"></i> Save Diet Prescription
    </button>

</div>

{{-- ── RIGHT COLUMN: LIVE PREVIEW ── --}}
<div>
    <div class="card" style="position:sticky;top:20px">
        <div class="card-header" style="background:var(--sc-navy);color:#fff">
            <i class="fa-solid fa-utensils"></i> Diet Plan Preview
            <span id="preview-badge" style="margin-left:8px;font-size:11px;opacity:.7">Select conditions to see recommendations</span>
        </div>
        <div id="diet-preview" style="max-height:calc(100vh - 160px);overflow-y:auto">
            <div id="no-conditions-msg" style="padding:40px 24px;text-align:center;color:var(--sc-muted)">
                <i class="fa-solid fa-leaf" style="font-size:2rem;margin-bottom:12px;display:block;opacity:.4"></i>
                Select one or more conditions on the left to see the combined diet plan.
            </div>
            <div id="preview-content" style="display:none"></div>
        </div>
    </div>
</div>

</div>
</form>

<style>
.condition-label:has(input:checked) { border-color:var(--sc-teal);background:rgba(10,180,180,.06); }
.diet-section-header { font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:var(--sc-muted);padding:10px 16px 6px;border-bottom:1px solid var(--sc-border);background:var(--sc-bg); }
.diet-plan-block { padding:14px 16px;border-bottom:1px solid var(--sc-border); }
.meal-row { display:flex;gap:10px;padding:6px 0;border-bottom:1px solid var(--sc-border);font-size:12.5px; }
.meal-row:last-child { border-bottom:none; }
.meal-time { flex:0 0 160px;color:var(--sc-muted);font-weight:600;font-size:11.5px; }
</style>

<script>
const DIET_PLANS = @json(\App\Models\DietPrescription::DIET_PLANS);
const CONDITIONS  = @json(\App\Models\DietPrescription::CONDITIONS);

// ── Patient search ──
let patientAge = {{ $patient?->age_years ?? 40 }};
let patientGender = '{{ $patient?->gender ?? "male" }}';

const patientSearch = document.getElementById('patient-search');
const patientDrop   = document.getElementById('patient-dropdown');
const patientIdInput= document.getElementById('patient-id');
const patientInfo   = document.getElementById('patient-info');

patientSearch.addEventListener('input', function () {
    const q = this.value.trim();
    if (q.length < 2) { patientDrop.style.display = 'none'; return; }
    fetch('{{ route("patients.search") }}?q=' + encodeURIComponent(q))
        .then(r => r.json()).then(data => {
            if (!data.length) { patientDrop.style.display = 'none'; return; }
            patientDrop.innerHTML = data.map(p =>
                `<div onclick="selectPatient(${p.id},'${escapeHtml(p.full_name)}','${p.jhc_id}',${p.age_years ?? 40},'${p.gender}')"
                      style="padding:9px 14px;cursor:pointer;font-size:13px;border-bottom:1px solid #f1f5f9"
                      onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background=''">
                    <strong>${escapeHtml(p.full_name)}</strong>
                    <span style="color:#94a3b8;font-size:11px;margin-left:6px">${p.jhc_id}</span>
                </div>`
            ).join('');
            patientDrop.style.display = 'block';
        });
});

function selectPatient(id, name, jhcId, age, gender) {
    patientIdInput.value = id;
    patientSearch.value = name + ' (' + jhcId + ')';
    patientDrop.style.display = 'none';
    patientAge    = age;
    patientGender = gender;
    patientInfo.innerHTML = `<div class="fw-600">${escapeHtml(name)}</div><div class="text-muted text-small">${jhcId}</div>`;
    patientInfo.style.display = 'block';
    calcBMIAndCal();
}
document.addEventListener('click', e => { if (!patientSearch.contains(e.target)) patientDrop.style.display = 'none'; });

// ── BMI & Calorie calculator ──
function calcBMIAndCal() {
    const h = parseFloat(document.getElementById('height_cm').value);
    const w = parseFloat(document.getElementById('weight_kg').value);
    if (!h || !w) { document.getElementById('bmi-box').style.display = 'none'; return; }

    const bmi = w / ((h/100) ** 2);
    const hM  = h / 100;
    const idealW = (22 * hM * hM).toFixed(1);

    const actFactor = { sedentary:1.2, light:1.375, moderate:1.55, active:1.725 };
    const act = document.getElementById('activity_level').value;
    const factor = actFactor[act] || 1.2;
    let bmr;
    if (patientGender === 'female') bmr = 447.6 + 9.25*w + 3.10*h - 4.33*patientAge;
    else                            bmr = 88.36 + 13.4*w + 4.8*h - 5.7*patientAge;
    const tdee = bmr * factor;

    let adj, advice;
    if      (bmi < 18.5) { adj = +300;  advice = '⬆️ Underweight — calories increased by 300 kcal/day to promote healthy weight gain'; }
    else if (bmi < 23.0) { adj = 0;     advice = '✅ Normal weight (Asian BMI) — calorie target set to maintain current weight'; }
    else if (bmi < 27.5) { adj = -500;  advice = '⬇️ Overweight — deficit of 500 kcal/day targets ~0.5 kg weight loss per week'; }
    else if (bmi < 32.0) { adj = -700;  advice = '⬇️ Obese (Grade I) — deficit of 700 kcal/day targets ~0.7 kg weight loss per week'; }
    else                 { adj = -1000; advice = '⬇️ Obese (Grade II+) — deficit of 1000 kcal/day targets ~1 kg weight loss per week'; }

    const cal = Math.max(1200, Math.round((tdee + adj) / 50) * 50);

    // Target weight calorie note
    const tw = parseFloat(document.getElementById('target_weight_kg').value);
    const toGo = tw ? (w - tw).toFixed(1) : null;
    const weeks = tw && w > tw ? Math.ceil((w - tw) / (Math.abs(adj)/7000)) : null;

    const bmiCats = [
        {max:18.5, label:'Underweight', color:'#3b82f6'},
        {max:23.0, label:'Normal',      color:'#22c55e'},
        {max:27.5, label:'Overweight',  color:'#f59e0b'},
        {max:999,  label:'Obese',       color:'#ef4444'},
    ];
    const cat = bmiCats.find(c => bmi < c.max);
    const pct = Math.min(100, (bmi / 40) * 100);

    document.getElementById('bmi-val').textContent  = bmi.toFixed(1);
    document.getElementById('bmi-val').style.color  = cat.color;
    document.getElementById('bmi-cat').textContent  = cat.label;
    document.getElementById('bmi-cat').style.color  = cat.color;
    document.getElementById('ideal-val').textContent = idealW;
    document.getElementById('cal-val').textContent  = cal.toLocaleString();
    document.getElementById('bmi-bar').style.width  = pct + '%';
    document.getElementById('bmi-bar').style.background = cat.color;

    let adviceHtml = advice;
    if (toGo && toGo > 0 && weeks) {
        adviceHtml += ` &nbsp;·&nbsp; <strong>${toGo} kg to target</strong> ≈ ${weeks} weeks`;
    }
    document.getElementById('cal-advice').innerHTML = adviceHtml;
    document.getElementById('bmi-box').style.display = 'block';
}

// ── Diet plan preview ──
function updatePreview() {
    const checked = [...document.querySelectorAll('input[name="conditions[]"]:checked')].map(c => c.value);

    if (!checked.length) {
        document.getElementById('no-conditions-msg').style.display = 'block';
        document.getElementById('preview-content').style.display   = 'none';
        document.getElementById('preview-badge').textContent = 'Select conditions to see recommendations';
        return;
    }

    document.getElementById('no-conditions-msg').style.display = 'none';
    document.getElementById('preview-content').style.display   = 'block';
    document.getElementById('preview-badge').textContent       = checked.length + ' condition(s) selected';

    let html = '';

    // Combined avoid / include list
    const allAvoid   = [...new Set(checked.flatMap(k => DIET_PLANS[k]?.avoid ?? []))];
    const allInclude = [...new Set(checked.flatMap(k => DIET_PLANS[k]?.include ?? []))];

    // For multi-condition: show combined calorie macro summary
    if (checked.length > 1) {
        html += `<div style="padding:14px 16px;background:#fffbeb;border-bottom:1px solid var(--sc-border)">
            <div style="font-weight:700;font-size:13px;color:#92400e;margin-bottom:6px"><i class="fa-solid fa-triangle-exclamation"></i> Combined Conditions Note</div>
            <div style="font-size:12px;color:#78350f">For patients with multiple conditions, the most restrictive guideline applies.
            Follow the avoid list of ALL selected conditions simultaneously.
            Consult a registered dietitian for a fully personalised meal plan.</div>
        </div>`;
    }

    // Per-condition summaries
    checked.forEach(k => {
        const plan = DIET_PLANS[k];
        const cond = CONDITIONS[k];
        if (!plan) return;
        html += `<div class="diet-plan-block">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px">
                <i class="fa-solid ${cond.icon}" style="color:${cond.color};font-size:16px"></i>
                <span style="font-weight:700;font-size:14px">${plan.title}</span>
                <span style="font-size:11px;color:var(--sc-muted);margin-left:auto">${plan.macro}</span>
            </div>
            <ul style="margin:0 0 10px;padding-left:18px;font-size:12.5px;color:var(--sc-text)">
                ${plan.key_points.map(p => `<li style="margin-bottom:3px">${p}</li>`).join('')}
            </ul>
        </div>`;
    });

    // Combined avoid/include
    html += `<div class="diet-section-header">Foods to AVOID</div>
    <div style="padding:12px 16px;display:flex;flex-wrap:wrap;gap:6px">
        ${allAvoid.map(f => `<span style="padding:3px 10px;border-radius:12px;font-size:12px;background:#fef2f2;color:#991b1b;border:1px solid #fca5a5">${f}</span>`).join('')}
    </div>
    <div class="diet-section-header">Foods to INCLUDE</div>
    <div style="padding:12px 16px;display:flex;flex-wrap:wrap;gap:6px">
        ${allInclude.map(f => `<span style="padding:3px 10px;border-radius:12px;font-size:12px;background:#f0fdf4;color:#166534;border:1px solid #86efac">${f}</span>`).join('')}
    </div>`;

    // Meal plan (first condition or merged)
    const primaryKey = checked[0];
    const primaryPlan = DIET_PLANS[primaryKey];
    if (primaryPlan?.meal_plan) {
        html += `<div class="diet-section-header">Sample Meal Plan ${checked.length > 1 ? '(based on ' + DIET_PLANS[primaryKey].title + ', apply restrictions from all conditions)' : ''}</div>
        <div style="padding:0 16px">`;
        Object.entries(primaryPlan.meal_plan).forEach(([time, food]) => {
            html += `<div class="meal-row"><div class="meal-time">${time}</div><div style="flex:1">${food}</div></div>`;
        });
        html += `</div>`;
    }

    document.getElementById('preview-content').innerHTML = html;
}

// Init
document.addEventListener('DOMContentLoaded', () => { calcBMIAndCal(); updatePreview(); });

function escapeHtml(t) {
    return String(t).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>
@endsection
