@push('styles')
<style>
/* ── Echo form compact controls ───────────────────────────────────────── */
.ep-sel, .ep-inp {
    padding: 5px 8px;
    font-size: 12px;
    height: 30px;
    border-radius: 5px;
}
.ep-sel { width: 100%; cursor: pointer; }
.ep-inp { width: 100%; }

/* ── Parameters / Doppler table ───────────────────────────────────────── */
.ep-table th {
    background: var(--sc-navy);
    color: #fff;
    font-size: 11px;
    letter-spacing: .4px;
    padding: 9px 12px;
    white-space: nowrap;
    border-bottom: none;
}
.ep-table td { padding: 7px 10px; vertical-align: middle; border-bottom: 1px solid var(--sc-border); }
.ep-table tr:last-child td { border-bottom: none; }
.ep-table tr:hover td { background: rgba(10,180,180,.04); }

.ep-num  { text-align: center; color: var(--sc-muted); font-size: 11px; width: 36px; }
.ep-param { font-weight: 700; font-size: 12.5px; color: var(--sc-navy); }
.ep-range {
    font-size: 12px; font-weight: 600;
    color: var(--sc-teal);
    font-family: 'DM Mono', monospace;
    background: rgba(10,180,180,.06);
    white-space: nowrap;
}
.ep-valve {
    text-align: center; font-weight: 700; font-size: 13px;
    color: var(--sc-navy); vertical-align: middle;
    background: var(--sc-bg);
    white-space: nowrap;
}

/* ── Wall motion dropdowns ─────────────────────────────────────────────── */
.wm-label { display: block; font-size: 12.5px; font-weight: 600; color: var(--sc-navy); margin-bottom: 6px; }
</style>
@endpush

{{-- Shared form partial for create and edit --}}
{{-- Variables expected: $isEdit (bool), $echo (EchoRecord|null), $doctors (Collection), $templates (Collection) --}}
@php
$e = $echo ?? null;
$v = fn($f) => old($f, $e?->$f ?? '');
$templates = $templates ?? collect();
$categoryLabels = [
    'normal'    => 'Normal',
    'ihd'       => 'IHD / CAD',
    'lvh'       => 'LVH / Hypertension',
    'dcm'       => 'LV Dysfunction',
    'valvular'  => 'Valvular',
    'rv_pah'    => 'RV / Pulmonary HTN',
    'congenital'=> 'Congenital',
    'other'     => 'Other',
];
$groupedTemplates = $templates->groupBy('category');
@endphp

{{-- ===== TEMPLATE LIBRARY ===== --}}
<div class="card mb-3" style="border-left:3px solid var(--sc-teal)">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="fa-solid fa-bookmark me-2"></i>Echo Template Library</span>
        <span style="font-size:11px;color:var(--sc-muted)">Save common patterns to one-click apply later.</span>
    </div>
    <div class="card-body">
        <div class="form-row" style="display:grid;grid-template-columns:2.2fr auto auto auto;gap:8px;align-items:end">
            <div>
                <label class="wm-label">Template</label>
                <select id="echo-template-select" class="form-control ep-sel">
                    <option value="">-- pick a template --</option>
                    @foreach($categoryLabels as $catKey => $catLabel)
                        @if($groupedTemplates->has($catKey))
                            <optgroup label="{{ $catLabel }}">
                                @foreach($groupedTemplates->get($catKey) as $t)
                                    <option value="{{ $t->id }}" data-builtin="{{ $t->is_builtin ? 1 : 0 }}" data-fields='@json($t->fields)' title="{{ $t->description }}">
                                        {{ $t->name }}{{ $t->is_builtin ? '' : ' ★' }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endif
                    @endforeach
                </select>
            </div>
            <button type="button" class="btn btn-primary" onclick="applyEchoTemplate()">
                <i class="fa-solid fa-wand-magic-sparkles me-1"></i>Apply
            </button>
            <button type="button" class="btn btn-outline" onclick="openSaveTemplateModal()">
                <i class="fa-solid fa-floppy-disk me-1"></i>Save as Template
            </button>
            <button type="button" class="btn btn-outline" id="echo-template-delete" onclick="deleteEchoTemplate()" disabled style="color:#b91c1c">
                <i class="fa-solid fa-trash"></i>
            </button>
        </div>
        <div id="echo-template-desc" class="text-muted mt-2" style="font-size:12px;min-height:18px"></div>
    </div>
</div>

{{-- Save-as-Template modal --}}
<div id="echo-template-modal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.5);align-items:center;justify-content:center">
    <div style="background:#fff;border-radius:10px;width:480px;max-width:90vw;padding:22px;box-shadow:0 20px 60px rgba(0,0,0,.3)">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px">
            <h4 style="margin:0;font-size:16px"><i class="fa-solid fa-floppy-disk me-2"></i>Save as Echo Template</h4>
            <button type="button" onclick="closeSaveTemplateModal()" style="background:none;border:none;font-size:20px;cursor:pointer;color:#999">&times;</button>
        </div>
        <div class="mb-2">
            <label class="wm-label">Template name <span class="req">*</span></label>
            <input type="text" id="template-modal-name" class="form-control" placeholder="e.g. Concentric LVH with mild MR" maxlength="120">
        </div>
        <div class="mb-2">
            <label class="wm-label">Category</label>
            <select id="template-modal-category" class="form-control ep-sel">
                @foreach($categoryLabels as $k => $label)
                    <option value="{{ $k }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label class="wm-label">Description <span style="color:#999;font-weight:400">(optional, shown in dropdown tooltip)</span></label>
            <input type="text" id="template-modal-description" class="form-control" placeholder="When to use this template" maxlength="255">
        </div>
        <div style="display:flex;gap:8px;justify-content:flex-end">
            <button type="button" class="btn btn-outline" onclick="closeSaveTemplateModal()">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="submitSaveTemplate()">
                <i class="fa-solid fa-check me-1"></i>Save Template
            </button>
        </div>
    </div>
</div>

{{-- ===== PATIENT & STUDY INFO ===== --}}
<div class="card mb-3">
    <div class="card-header"><i class="fa-solid fa-user me-2"></i>Patient &amp; Study Details</div>
    <div class="card-body">

        {{-- Row 1: Patient | Doctor | Study Date --}}
        <div class="form-row cols-3">
            <div class="form-group">
                @if($isEdit)
                    <label class="wm-label">Patient</label>
                    <div class="d-flex align-items-center gap-2 p-2" style="background:var(--sc-bg);border-radius:8px">
                        <div style="width:32px;height:32px;flex-shrink:0;border-radius:50%;background:var(--sc-teal);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px">{{ strtoupper(substr($echo->patient->first_name,0,1)) }}</div>
                        <div><div style="font-weight:600;font-size:13px">{{ $echo->patient->full_name }}</div><div class="text-muted" style="font-size:11px">{{ $echo->patient->jhc_id }} &middot; {{ $echo->patient->gender }} &middot; {{ $echo->patient->age }} yrs</div></div>
                    </div>
                    <input type="hidden" name="patient_id" value="{{ $echo->patient_id }}">
                @else
                    @if($patient ?? null)
                        <label class="wm-label">Patient</label>
                        <div class="d-flex align-items-center gap-2 p-2" style="background:var(--sc-bg);border-radius:8px">
                            <div style="width:32px;height:32px;flex-shrink:0;border-radius:50%;background:var(--sc-teal);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px">{{ strtoupper(substr($patient->first_name,0,1)) }}</div>
                            <div><div style="font-weight:600;font-size:13px">{{ $patient->full_name }}</div><div class="text-muted" style="font-size:11px">{{ $patient->jhc_id }} &middot; {{ $patient->gender }} &middot; {{ $patient->age }} yrs</div></div>
                        </div>
                        <input type="hidden" name="patient_id" value="{{ $patient->id }}">
                    @else
                        <label class="wm-label">Patient <span class="req">*</span></label>
                        <select name="patient_id" id="echo-patient-select" class="form-control select2" required style="width:100%">
                            <option value="">Search patient by name, JHC ID, or phone...</option>
                            @foreach($patients ?? [] as $p)
                                <option value="{{ $p->id }}" {{ old('patient_id') == $p->id ? 'selected' : '' }}>
                                    {{ $p->full_name }}{{ $p->jhc_id ? ' - '.$p->jhc_id : '' }}{{ $p->phone ? ' - '.$p->phone : '' }}
                                </option>
                            @endforeach
                        </select>
                    @endif
                @endif
            </div>
            <div class="form-group">
                <label class="wm-label">Doctor <span class="req">*</span></label>
                <select name="doctor_id" class="form-control" required>
                    <option value="">Select Doctor</option>
                    @foreach($doctors as $d)
                        <option value="{{ $d->id }}" {{ $d->id == ($v('doctor_id') ?: ($defaultDoctorId ?? '')) ? 'selected' : '' }}>{{ $d->first_name }} {{ $d->last_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="wm-label">Study Date <span class="req">*</span></label>
                <input type="date" name="record_date" class="form-control" value="{{ old('record_date', $e?->record_date?->format('Y-m-d') ?? today()->toDateString()) }}" required>
            </div>
        </div>

        {{-- Row 2: Diagnosis | Referred By --}}
        <div class="form-row cols-2">
            <div class="form-group">
                <label class="wm-label">Diagnosis</label>
                <input type="text" name="diagnosis" class="form-control" value="{{ $v('diagnosis') }}" placeholder="e.g. CAD, HTN, DCM">
            </div>
            <div class="form-group">
                <label class="wm-label">Referred By</label>
                <input type="text" name="ref_by" class="form-control" value="{{ $v('ref_by') }}" placeholder="Referring doctor / dept">
            </div>
        </div>

        {{-- Row 3: Height | Weight | BSA | Calc --}}
        <div class="form-row cols-4">
            <div class="form-group">
                <label class="wm-label">Height (cm)</label>
                <input type="text" name="height_cm" id="height_cm" class="form-control" value="{{ $v('height_cm') ?: ($isEdit ? '' : '170') }}" placeholder="170">
            </div>
            <div class="form-group">
                <label class="wm-label">Weight (kg)</label>
                <input type="text" name="weight_kg" id="weight_kg" class="form-control" value="{{ $v('weight_kg') ?: ($isEdit ? '' : '70') }}" placeholder="70">
            </div>
            <div class="form-group">
                <label class="wm-label">BSA (m²)</label>
                <input type="text" name="bsa" id="bsa_field" class="form-control" value="{{ $v('bsa') }}" placeholder="1.75">
            </div>
            <div class="form-group">
                <label class="wm-label">&nbsp;</label>
                <button type="button" class="btn btn-outline" style="width:100%" onclick="calcBSA()">Calc BSA</button>
            </div>
        </div>

    </div>
</div>

{{-- ===== 16 PARAMETERS TABLE ===== --}}
<div class="card mb-3">
    <div class="card-header">
        <i class="fa-solid fa-table"></i>LEFT &amp; RIGHT HEART PARAMETERS – 2D, M MODE
        <span class="card-actions">
            <button type="button" class="btn btn-sm btn-outline" onclick="applyNormalDefaults()" title="Fill all parameters with typical normal values">
                <i class="fa-solid fa-check-double"></i>Fill Normal Values
            </button>
        </span>
    </div>
    <div style="background:rgba(10,180,180,.07);border-bottom:1px solid rgba(10,180,180,.2);padding:8px 16px;font-size:11.5px;color:#065e5e">
        <i class="fa-solid fa-circle-info" style="margin-right:6px"></i>Use the <strong>Quick Select</strong> dropdown to pick a value, then fine-tune the number as needed.
    </div>
    <div class="table-wrap">
        <table class="sc-table ep-table">
            <thead>
                <tr>
                    <th style="width:4%">#</th>
                    <th style="width:17%">Parameter</th>
                    <th style="width:30%">Quick Select</th>
                    <th style="width:16%">Patient's Value</th>
                    <th style="width:18%">Normal Range</th>
                </tr>
            </thead>
            <tbody>

{{-- 1. RV Wall Thickness --}}
<tr>
    <td class="text-center ep-num">1</td>
    <td class="ep-param">RV Wall Thickness</td>
    <td>
        <select class="form-control ep-sel" onchange="fp('rv_wall_thickness',this)">
            <option value="">-- select --</option>
            <optgroup label="✓ Normal (&lt;5mm)">
                <option value="3">3 mm</option>
                <option value="4">4 mm</option>
                <option value="5">5 mm (upper normal)</option>
            </optgroup>
            <optgroup label="↑ Mildly thickened (5-7mm)">
                <option value="6">6 mm</option>
                <option value="7">7 mm</option>
            </optgroup>
            <optgroup label="↑↑ Significantly thickened (&gt;7mm)">
                <option value="8">8 mm</option>
                <option value="10">10 mm</option>
            </optgroup>
        </select>
    </td>
    <td><input type="text" name="rv_wall_thickness" id="rv_wall_thickness" class="form-control ep-inp" value="{{ $v('rv_wall_thickness') }}" placeholder="mm"></td>
    <td class="ep-range">&lt; 5 mm</td>
</tr>

{{-- 2. RVID --}}
<tr>
    <td class="text-center ep-num">2</td>
    <td class="ep-param">RVID</td>
    <td>
        <select class="form-control ep-sel" onchange="fp('rv_dimension',this)">
            <option value="">-- select --</option>
            <optgroup label="✓ Normal (7-23mm)">
                <option value="15">15 mm</option>
                <option value="18">18 mm</option>
                <option value="22">22 mm</option>
            </optgroup>
            <optgroup label="↑ Mildly dilated (24-34mm)">
                <option value="26">26 mm</option>
                <option value="30">30 mm</option>
            </optgroup>
            <optgroup label="↑↑ Moderately dilated (35-44mm)">
                <option value="36">36 mm</option>
                <option value="42">42 mm</option>
            </optgroup>
            <optgroup label="↑↑↑ Severely dilated (&gt;44mm)">
                <option value="46">46 mm</option>
                <option value="52">52 mm</option>
            </optgroup>
        </select>
    </td>
    <td><input type="text" name="rv_dimension" id="rv_dimension" class="form-control ep-inp" value="{{ $v('rv_dimension') }}" placeholder="mm"></td>
    <td class="ep-range">7-23 mm</td>
</tr>

{{-- 3. TAPSE --}}
<tr>
    <td class="text-center ep-num">3</td>
    <td class="ep-param">TAPSE</td>
    <td>
        <select class="form-control ep-sel" onchange="fp('tapse',this)">
            <option value="">-- select --</option>
            <optgroup label="✓ Normal (&gt;17mm)">
                <option value="18">18 mm</option>
                <option value="20">20 mm</option>
                <option value="22">22 mm</option>
            </optgroup>
            <optgroup label="↓ Mildly reduced (12-17mm)">
                <option value="16">16 mm</option>
                <option value="14">14 mm</option>
            </optgroup>
            <optgroup label="↓↓ Severely reduced (&lt;12mm)">
                <option value="10">10 mm</option>
                <option value="8">8 mm</option>
            </optgroup>
        </select>
    </td>
    <td><input type="text" name="tapse" id="tapse" class="form-control ep-inp" value="{{ $v('tapse') }}" placeholder="mm"></td>
    <td class="ep-range">&gt; 17 mm</td>
</tr>

{{-- 4. IVC --}}
<tr>
    <td class="text-center ep-num">4</td>
    <td class="ep-param">IVC</td>
    <td>
        <select class="form-control ep-sel" onchange="fp('ivc_size',this)">
            <option value="">-- select --</option>
            <optgroup label="✓ Normal (&lt;20mm, good collapse)">
                <option value="14">14 mm</option>
                <option value="16">16 mm</option>
                <option value="18">18 mm</option>
            </optgroup>
            <optgroup label="↑ Mildly dilated (20-24mm)">
                <option value="21">21 mm</option>
                <option value="23">23 mm</option>
            </optgroup>
            <optgroup label="↑↑ Dilated, poor collapse (&gt;24mm)">
                <option value="26">26 mm</option>
                <option value="30">30 mm</option>
            </optgroup>
        </select>
    </td>
    <td><input type="text" name="ivc_size" id="ivc_size" class="form-control ep-inp" value="{{ $v('ivc_size') }}" placeholder="mm"></td>
    <td class="ep-range">&lt; 20 mm</td>
</tr>

{{-- 5. IVS --}}
<tr>
    <td class="text-center ep-num">5</td>
    <td class="ep-param">IVS</td>
    <td>
        <select class="form-control ep-sel" onchange="fp('ivs',this)">
            <option value="">-- select --</option>
            <optgroup label="✓ Normal (6-12mm)">
                <option value="8">8 mm</option>
                <option value="9">9 mm</option>
                <option value="10">10 mm</option>
            </optgroup>
            <optgroup label="↑ Mild LVH (13-15mm)">
                <option value="13">13 mm</option>
                <option value="14">14 mm</option>
                <option value="15">15 mm</option>
            </optgroup>
            <optgroup label="↑↑ Moderate LVH (16-19mm)">
                <option value="16">16 mm</option>
                <option value="18">18 mm</option>
            </optgroup>
            <optgroup label="↑↑↑ Severe LVH (&ge;20mm)">
                <option value="20">20 mm</option>
                <option value="22">22 mm</option>
                <option value="25">25 mm</option>
            </optgroup>
        </select>
    </td>
    <td><input type="text" name="ivs" id="ivs" class="form-control ep-inp" value="{{ $v('ivs') }}" placeholder="mm"></td>
    <td class="ep-range">6-12 mm</td>
</tr>

{{-- 6. LVPW --}}
<tr>
    <td class="text-center ep-num">6</td>
    <td class="ep-param">LVPW</td>
    <td>
        <select class="form-control ep-sel" onchange="fp('lvpw',this)">
            <option value="">-- select --</option>
            <optgroup label="✓ Normal (6-11mm)">
                <option value="8">8 mm</option>
                <option value="9">9 mm</option>
                <option value="10">10 mm</option>
            </optgroup>
            <optgroup label="↑ Mild LVH (12-14mm)">
                <option value="12">12 mm</option>
                <option value="13">13 mm</option>
                <option value="14">14 mm</option>
            </optgroup>
            <optgroup label="↑↑ Moderate-Severe LVH (&ge;15mm)">
                <option value="15">15 mm</option>
                <option value="17">17 mm</option>
                <option value="20">20 mm</option>
            </optgroup>
        </select>
    </td>
    <td><input type="text" name="lvpw" id="lvpw" class="form-control ep-inp" value="{{ $v('lvpw') }}" placeholder="mm"></td>
    <td class="ep-range">6-11 mm</td>
</tr>

{{-- 7. LVIDD --}}
<tr>
    <td class="text-center ep-num">7</td>
    <td class="ep-param">LVIDD (LV EDD)</td>
    <td>
        <select class="form-control ep-sel" onchange="fp('lv_edd',this)">
            <option value="">-- select --</option>
            <optgroup label="↓ Small LV (&lt;35mm)">
                <option value="28">28 mm (HCM)</option>
                <option value="32">32 mm</option>
            </optgroup>
            <optgroup label="✓ Normal (35-55mm)">
                <option value="44">44 mm</option>
                <option value="48">48 mm</option>
                <option value="52">52 mm</option>
            </optgroup>
            <optgroup label="↑ Mildly dilated (56-64mm)">
                <option value="57">57 mm</option>
                <option value="60">60 mm</option>
            </optgroup>
            <optgroup label="↑↑ Moderately dilated (65-74mm)">
                <option value="65">65 mm</option>
                <option value="70">70 mm</option>
            </optgroup>
            <optgroup label="↑↑↑ Severely dilated (&ge;75mm)">
                <option value="76">76 mm</option>
            </optgroup>
        </select>
    </td>
    <td><input type="text" name="lv_edd" id="lv_edd" class="form-control ep-inp" value="{{ $v('lv_edd') }}" placeholder="mm"></td>
    <td class="ep-range">35-55 mm</td>
</tr>

{{-- 8. LVIDS --}}
<tr>
    <td class="text-center ep-num">8</td>
    <td class="ep-param">LVIDS (LV ESD)</td>
    <td>
        <select class="form-control ep-sel" onchange="fp('lv_esd',this)">
            <option value="">-- select --</option>
            <optgroup label="✓ Normal (24-42mm)">
                <option value="28">28 mm</option>
                <option value="32">32 mm</option>
                <option value="36">36 mm</option>
            </optgroup>
            <optgroup label="↑ Mildly enlarged (43-52mm)">
                <option value="44">44 mm</option>
                <option value="48">48 mm</option>
            </optgroup>
            <optgroup label="↑↑ Moderately-severely enlarged (&gt;52mm)">
                <option value="54">54 mm</option>
                <option value="60">60 mm</option>
            </optgroup>
        </select>
    </td>
    <td><input type="text" name="lv_esd" id="lv_esd" class="form-control ep-inp" value="{{ $v('lv_esd') }}" placeholder="mm"></td>
    <td class="ep-range">24-42 mm</td>
</tr>

{{-- 9. EPSS --}}
<tr>
    <td class="text-center ep-num">9</td>
    <td class="ep-param">EPSS</td>
    <td>
        <select class="form-control ep-sel" onchange="fp('epss',this)">
            <option value="">-- select --</option>
            <optgroup label="✓ Normal (&lt;7mm)">
                <option value="3">3 mm</option>
                <option value="4">4 mm</option>
                <option value="5">5 mm</option>
            </optgroup>
            <optgroup label="↑ Mildly increased (7-10mm)">
                <option value="7">7 mm</option>
                <option value="8">8 mm</option>
                <option value="10">10 mm</option>
            </optgroup>
            <optgroup label="↑↑ Significantly increased (&gt;10mm)">
                <option value="12">12 mm</option>
                <option value="15">15 mm</option>
            </optgroup>
        </select>
    </td>
    <td><input type="text" name="epss" id="epss" class="form-control ep-inp" value="{{ $v('epss') }}" placeholder="mm"></td>
    <td class="ep-range">&lt; 7 mm</td>
</tr>

{{-- 10. MAPSE --}}
<tr>
    <td class="text-center ep-num">10</td>
    <td class="ep-param">MAPSE</td>
    <td>
        <select class="form-control ep-sel" onchange="fp('mapse',this)">
            <option value="">-- select --</option>
            <optgroup label="✓ Normal (&gt;12mm)">
                <option value="14">14 mm</option>
                <option value="16">16 mm</option>
                <option value="18">18 mm</option>
            </optgroup>
            <optgroup label="↓ Mildly reduced (8-12mm)">
                <option value="11">11 mm</option>
                <option value="10">10 mm</option>
                <option value="9">9 mm</option>
            </optgroup>
            <optgroup label="↓↓ Severely reduced (&lt;8mm)">
                <option value="7">7 mm</option>
                <option value="6">6 mm</option>
            </optgroup>
        </select>
    </td>
    <td><input type="text" name="mapse" id="mapse" class="form-control ep-inp" value="{{ $v('mapse') }}" placeholder="mm"></td>
    <td class="ep-range">&gt; 12 mm</td>
</tr>

{{-- 11. LA --}}
<tr>
    <td class="text-center ep-num">11</td>
    <td class="ep-param">LA</td>
    <td>
        <select class="form-control ep-sel" onchange="fp('la_size',this)">
            <option value="">-- select --</option>
            <optgroup label="✓ Normal (19-40mm)">
                <option value="30">30 mm</option>
                <option value="34">34 mm</option>
                <option value="38">38 mm</option>
            </optgroup>
            <optgroup label="↑ Mildly dilated (41-48mm)">
                <option value="43">43 mm</option>
                <option value="46">46 mm</option>
            </optgroup>
            <optgroup label="↑↑ Moderately dilated (49-55mm)">
                <option value="50">50 mm</option>
                <option value="53">53 mm</option>
            </optgroup>
            <optgroup label="↑↑↑ Severely dilated (&gt;55mm)">
                <option value="58">58 mm</option>
                <option value="62">62 mm</option>
            </optgroup>
        </select>
    </td>
    <td><input type="text" name="la_size" id="la_size" class="form-control ep-inp" value="{{ $v('la_size') }}" placeholder="mm"></td>
    <td class="ep-range">19-40 mm</td>
</tr>

{{-- 12. AO --}}
<tr>
    <td class="text-center ep-num">12</td>
    <td class="ep-param">AO Root</td>
    <td>
        <select class="form-control ep-sel" onchange="fp('ao_root',this)">
            <option value="">-- select --</option>
            <optgroup label="✓ Normal (25-37mm)">
                <option value="28">28 mm</option>
                <option value="30">30 mm</option>
                <option value="34">34 mm</option>
            </optgroup>
            <optgroup label="↑ Mildly dilated (38-44mm)">
                <option value="40">40 mm</option>
                <option value="43">43 mm</option>
            </optgroup>
            <optgroup label="↑↑ Moderately-severely dilated (&gt;44mm)">
                <option value="46">46 mm</option>
                <option value="50">50 mm</option>
            </optgroup>
        </select>
    </td>
    <td><input type="text" name="ao_root" id="ao_root" class="form-control ep-inp" value="{{ $v('ao_root') }}" placeholder="mm"></td>
    <td class="ep-range">25-37 mm</td>
</tr>

{{-- 13. EF --}}
<tr>
    <td class="text-center ep-num">13</td>
    <td class="ep-param">EF <span class="text-danger">*</span></td>
    <td>
        <select class="form-control ep-sel" onchange="fp('ef_percent',this)">
            <option value="">-- select --</option>
            <optgroup label="↑ Hyperdynamic (&gt;70%)">
                <option value="72%">72%</option>
                <option value="75%">75%</option>
            </optgroup>
            <optgroup label="✓ Normal (55-70%)">
                <option value="55%">55%</option>
                <option value="58%">58%</option>
                <option value="62%">62%</option>
                <option value="65%">65%</option>
            </optgroup>
            <optgroup label="↓ Mildly reduced (45-54%)">
                <option value="50%">50%</option>
                <option value="48%">48%</option>
            </optgroup>
            <optgroup label="↓↓ Moderately reduced (35-44%)">
                <option value="42%">42%</option>
                <option value="38%">38%</option>
            </optgroup>
            <optgroup label="↓↓↓ Severely reduced (&lt;35%)">
                <option value="30%">30%</option>
                <option value="25%">25%</option>
                <option value="20%">20%</option>
            </optgroup>
        </select>
    </td>
    <td><input type="text" name="ef_percent" id="ef_percent" class="form-control ep-inp" value="{{ $v('ef_percent') }}" placeholder="%" required></td>
    <td class="ep-range">55-70 %</td>
</tr>

{{-- 14. FS --}}
<tr>
    <td class="text-center ep-num">14</td>
    <td class="ep-param">FS</td>
    <td>
        <select class="form-control ep-sel" onchange="fp('fs_percent',this)">
            <option value="">-- select --</option>
            <optgroup label="✓ Normal (28-41%)">
                <option value="30%">30%</option>
                <option value="33%">33%</option>
                <option value="36%">36%</option>
            </optgroup>
            <optgroup label="↓ Mildly reduced (20-27%)">
                <option value="25%">25%</option>
                <option value="22%">22%</option>
            </optgroup>
            <optgroup label="↓↓ Severely reduced (&lt;20%)">
                <option value="18%">18%</option>
                <option value="14%">14%</option>
            </optgroup>
        </select>
    </td>
    <td><input type="text" name="fs_percent" id="fs_percent" class="form-control ep-inp" value="{{ $v('fs_percent') }}" placeholder="%"></td>
    <td class="ep-range">28-41 %</td>
</tr>

{{-- 15. LAP E/e' --}}
<tr>
    <td class="text-center ep-num">15</td>
    <td class="ep-param">LAP (E/e')</td>
    <td>
        <select class="form-control ep-sel" onchange="fp('lap_ee',this)">
            <option value="">-- select --</option>
            <optgroup label="✓ Normal (&lt;8)">
                <option value="5">5</option>
                <option value="6">6</option>
                <option value="7">7</option>
            </optgroup>
            <optgroup label="~ Intermediate (8-14)">
                <option value="9">9</option>
                <option value="11">11</option>
                <option value="13">13</option>
            </optgroup>
            <optgroup label="↑ Elevated (&gt;14)">
                <option value="15">15</option>
                <option value="18">18</option>
                <option value="22">22</option>
            </optgroup>
        </select>
    </td>
    <td><input type="text" name="lap_ee" id="lap_ee" class="form-control ep-inp" value="{{ $v('lap_ee') }}" placeholder="ratio"></td>
    <td class="ep-range">N &lt;8, Int 8-14, H &gt;14</td>
</tr>

{{-- 16. PAP --}}
<tr>
    <td class="text-center ep-num">16</td>
    <td class="ep-param">PAP</td>
    <td>
        <select class="form-control ep-sel" onchange="fp('pap',this)">
            <option value="">-- select --</option>
            <optgroup label="✓ Normal (&lt;20 mmHg)">
                <option value="14">14 mmHg</option>
                <option value="16">16 mmHg</option>
                <option value="18">18 mmHg</option>
            </optgroup>
            <optgroup label="↑ Mild PH (20-35 mmHg)">
                <option value="25">25 mmHg</option>
                <option value="28">28 mmHg</option>
                <option value="32">32 mmHg</option>
            </optgroup>
            <optgroup label="↑↑ Moderate PH (36-50 mmHg)">
                <option value="38">38 mmHg</option>
                <option value="44">44 mmHg</option>
                <option value="50">50 mmHg</option>
            </optgroup>
            <optgroup label="↑↑↑ Severe PH (&gt;50 mmHg)">
                <option value="55">55 mmHg</option>
                <option value="65">65 mmHg</option>
            </optgroup>
        </select>
    </td>
    <td><input type="text" name="pap" id="pap" class="form-control ep-inp" value="{{ $v('pap') }}" placeholder="mmHg"></td>
    <td class="ep-range">&lt; 20 mmHg</td>
</tr>

            </tbody>
        </table>
    </div>
</div>

{{-- ===== WALL MOTION AND FUNCTION ===== --}}

<div class="card mb-3">
    <div class="card-header"><i class="fa-solid fa-heart-pulse me-2"></i>Wall Motion and Function</div>
    <div class="card-body">
        <div class="grid grid-2" style="align-items:start">
            {{-- LEFT PANE: LV function + IVS --}}
            <div style="padding-right:16px;border-right:1px solid #e5e7eb">
                <div style="font-weight:700;text-align:center;margin-bottom:10px;padding:4px;background:#f0f0f0;border-radius:4px;font-size:12px">LEFT VENTRICLE</div>
                <div class="mb-2">
                    <label class="wm-label">Diastolic Function</label>
                    <select name="lv_diastolic_function" class="form-control ep-sel">
                        <option value="">-- select --</option>
                        <option value="Normal" {{ $v('lv_diastolic_function')=='Normal'?'selected':'' }}>Normal</option>
                        <option value="Grade I - Impaired Relaxation" {{ $v('lv_diastolic_function')=='Grade I - Impaired Relaxation'?'selected':'' }}>Grade I - Impaired Relaxation</option>
                        <option value="Grade II - Pseudonormal" {{ $v('lv_diastolic_function')=='Grade II - Pseudonormal'?'selected':'' }}>Grade II - Pseudonormal</option>
                        <option value="Grade III - Restrictive" {{ $v('lv_diastolic_function')=='Grade III - Restrictive'?'selected':'' }}>Grade III - Restrictive</option>
                        <option value="Indeterminate" {{ $v('lv_diastolic_function')=='Indeterminate'?'selected':'' }}>Indeterminate</option>
                    </select>
                </div>
                <div class="mb-2">
                    <label class="wm-label">EA Ratio</label>
                    <select name="ea_ratio" class="form-control ep-sel">
                        <option value="">-- select --</option>
                        <option value="Normal (E/A &gt;1.0)" {{ $v('ea_ratio')=='Normal (E/A &gt;1.0)'?'selected':'' }}>Normal (E/A &gt;1.0)</option>
                        <option value="E/A &lt;1.0 - Grade I" {{ $v('ea_ratio')=='E/A &lt;1.0 - Grade I'?'selected':'' }}>E/A &lt;1.0 – Grade I (impaired relaxation)</option>
                        <option value="E/A 1.0-2.0 - Grade II Pseudonormal" {{ $v('ea_ratio')=='E/A 1.0-2.0 - Grade II Pseudonormal'?'selected':'' }}>E/A 1.0-2.0 – Grade II (pseudonormal)</option>
                        <option value="E/A &gt;2.0 - Grade III Restrictive" {{ $v('ea_ratio')=='E/A &gt;2.0 - Grade III Restrictive'?'selected':'' }}>E/A &gt;2.0 – Grade III (restrictive)</option>
                    </select>
                </div>
                <div class="mb-2">
                    <label class="wm-label">Systolic Function</label>
                    <select name="lv_systolic_function" class="form-control ep-sel">
                        <option value="">-- select --</option>
                        <option value="Normal" {{ $v('lv_systolic_function')=='Normal'?'selected':'' }}>Normal</option>
                        <option value="Hyperdynamic" {{ $v('lv_systolic_function')=='Hyperdynamic'?'selected':'' }}>Hyperdynamic</option>
                        <option value="Mildly reduced (EF 45-54%)" {{ $v('lv_systolic_function')=='Mildly reduced (EF 45-54%)'?'selected':'' }}>Mildly reduced (EF 45-54%)</option>
                        <option value="Moderately reduced (EF 35-44%)" {{ $v('lv_systolic_function')=='Moderately reduced (EF 35-44%)'?'selected':'' }}>Moderately reduced (EF 35-44%)</option>
                        <option value="Severely reduced (EF &lt;35%)" {{ $v('lv_systolic_function')=='Severely reduced (EF &lt;35%)'?'selected':'' }}>Severely reduced (EF &lt;35%)</option>
                    </select>
                </div>
                <div class="mb-2">
                    <label class="wm-label">Interventricular Septum</label>
                    <select name="ivs_motion" class="form-control ep-sel">
                        <option value="">-- select --</option>
                        <option value="Normal / Intact" {{ $v('ivs_motion')=='Normal / Intact'?'selected':'' }}>Normal / Intact</option>
                        <option value="Paradoxical Motion" {{ $v('ivs_motion')=='Paradoxical Motion'?'selected':'' }}>Paradoxical Motion</option>
                        <option value="Hypertrophied" {{ $v('ivs_motion')=='Hypertrophied'?'selected':'' }}>Hypertrophied</option>
                        <option value="LBBB Pattern" {{ $v('ivs_motion')=='LBBB Pattern'?'selected':'' }}>LBBB Pattern</option>
                        <option value="VSD Present" {{ $v('ivs_motion')=='VSD Present'?'selected':'' }}>VSD Present</option>
                    </select>
                </div>
            </div>

            {{-- RIGHT PANE: RV function + structural --}}
            <div style="padding-left:16px">
                <div style="font-weight:700;text-align:center;margin-bottom:10px;padding:4px;background:#f0f0f0;border-radius:4px;font-size:12px">RIGHT VENTRICLE &amp; STRUCTURAL</div>
                {{-- Row 1: Longitudinal + Transverse --}}
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:8px">
                    <div>
                        <label class="wm-label">Longitudinal Function</label>
                        <select name="rv_longitudinal_function" class="form-control ep-sel">
                            <option value="">-- select --</option>
                            <option value="Normal" {{ $v('rv_longitudinal_function')=='Normal'?'selected':'' }}>Normal</option>
                            <option value="Mildly reduced" {{ $v('rv_longitudinal_function')=='Mildly reduced'?'selected':'' }}>Mildly reduced</option>
                            <option value="Moderately reduced" {{ $v('rv_longitudinal_function')=='Moderately reduced'?'selected':'' }}>Moderately reduced</option>
                            <option value="Severely reduced" {{ $v('rv_longitudinal_function')=='Severely reduced'?'selected':'' }}>Severely reduced</option>
                        </select>
                    </div>
                    <div>
                        <label class="wm-label">Transverse Function</label>
                        <select name="rv_transverse_function" class="form-control ep-sel">
                            <option value="">-- select --</option>
                            <option value="Normal" {{ $v('rv_transverse_function')=='Normal'?'selected':'' }}>Normal</option>
                            <option value="Mildly reduced" {{ $v('rv_transverse_function')=='Mildly reduced'?'selected':'' }}>Mildly reduced</option>
                            <option value="Severely reduced" {{ $v('rv_transverse_function')=='Severely reduced'?'selected':'' }}>Severely reduced</option>
                        </select>
                    </div>
                </div>
                {{-- Row 2: Paradoxical Movement --}}
                <div class="mb-2">
                    <label class="wm-label">Paradoxical Movement</label>
                    <select name="rv_paradoxical_movement" class="form-control ep-sel">
                        <option value="">-- select --</option>
                        <option value="Absent" {{ $v('rv_paradoxical_movement')=='Absent'?'selected':'' }}>Absent</option>
                        <option value="Present" {{ $v('rv_paradoxical_movement')=='Present'?'selected':'' }}>Present</option>
                    </select>
                </div>
                {{-- Row 3: Inter Atrial Septum --}}
                <div class="mb-2">
                    <label class="wm-label">Inter Atrial Septum</label>
                    <select name="ias_status" class="form-control ep-sel">
                        <option value="">-- select --</option>
                        <option value="Intact" {{ $v('ias_status')=='Intact'?'selected':'' }}>Intact</option>
                        <option value="PFO" {{ $v('ias_status')=='PFO'?'selected':'' }}>PFO (Patent Foramen Ovale)</option>
                        <option value="ASD - Ostium Secundum" {{ $v('ias_status')=='ASD - Ostium Secundum'?'selected':'' }}>ASD – Ostium Secundum</option>
                        <option value="ASD - Ostium Primum" {{ $v('ias_status')=='ASD - Ostium Primum'?'selected':'' }}>ASD – Ostium Primum</option>
                    </select>
                </div>
                {{-- Row 4: Pericardium + Pericardial Effusion --}}
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:8px">
                    <div>
                        <label class="wm-label">Pericardium</label>
                        <select name="pericardium_status" class="form-control ep-sel">
                            <option value="">-- select --</option>
                            <option value="Normal" {{ $v('pericardium_status')=='Normal'?'selected':'' }}>Normal</option>
                            <option value="Mildly thickened" {{ $v('pericardium_status')=='Mildly thickened'?'selected':'' }}>Mildly thickened</option>
                            <option value="Thickened with effusion" {{ $v('pericardium_status')=='Thickened with effusion'?'selected':'' }}>Thickened with effusion</option>
                            <option value="Constrictive features" {{ $v('pericardium_status')=='Constrictive features'?'selected':'' }}>Constrictive features</option>
                        </select>
                    </div>
                    <div>
                        <label class="wm-label">Pericardial Effusion</label>
                        <select name="pericardial_effusion" class="form-control ep-sel">
                            <option value="">-- select --</option>
                            <option value="None" {{ $v('pericardial_effusion')=='None'?'selected':'' }}>None</option>
                            <option value="Trivial" {{ $v('pericardial_effusion')=='Trivial'?'selected':'' }}>Trivial</option>
                            <option value="Small (&lt;10mm)" {{ $v('pericardial_effusion')=='Small (&lt;10mm)'?'selected':'' }}>Small (&lt;10mm)</option>
                            <option value="Moderate (10-20mm)" {{ $v('pericardial_effusion')=='Moderate (10-20mm)'?'selected':'' }}>Moderate (10-20mm)</option>
                            <option value="Large (&gt;20mm) - Tamponade risk" {{ $v('pericardial_effusion')=='Large (&gt;20mm) - Tamponade risk'?'selected':'' }}>Large (&gt;20mm) – Tamponade risk</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ===== REGIONAL WALL MOTION (RWMA) ===== --}}
@php
$segments = [
    ['key'=>'anterior_motion',     'label'=>'Anterior',      'terr'=>'LAD'],
    ['key'=>'anteroseptal_motion', 'label'=>'Anteroseptal',  'terr'=>'LAD'],
    ['key'=>'inferior_motion',     'label'=>'Inferior',      'terr'=>'RCA'],
    ['key'=>'inferoseptal_motion', 'label'=>'Inferoseptal',  'terr'=>'RCA'],
    ['key'=>'lateral_motion',      'label'=>'Lateral',       'terr'=>'LCx'],
    ['key'=>'posterior_motion',    'label'=>'Posterior',     'terr'=>'LCx / RCA'],
    ['key'=>'apex_motion',         'label'=>'Apex',          'terr'=>'LAD / RCA'],
];
$rwmaOptions = ['Normal','Hypokinetic','Akinetic','Dyskinetic','Aneurysmal'];
@endphp
<div class="card mb-3">
    <div class="card-header">
        <i class="fa-solid fa-heart-circle-bolt me-2"></i>Regional Wall Motion (LV segments)
        <span class="card-actions">
            <button type="button" class="btn btn-sm btn-outline" onclick="setAllRwmaNormal()" title="Mark every segment Normal">
                <i class="fa-solid fa-check-double"></i>All Normal
            </button>
        </span>
    </div>
    <div style="background:rgba(10,180,180,.07);border-bottom:1px solid rgba(10,180,180,.2);padding:8px 16px;font-size:11.5px;color:#065e5e">
        <i class="fa-solid fa-circle-info" style="margin-right:6px"></i>Use for ischemic / infarct patients. Coronary territories shown for reference. Leave blank when the study is non-ischemic.
    </div>
    <div class="card-body">
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px">
            @foreach($segments as $seg)
                <div>
                    <label class="wm-label">
                        {{ $seg['label'] }}
                        <span style="font-weight:500;color:var(--sc-muted);font-size:10.5px">· {{ $seg['terr'] }}</span>
                    </label>
                    <select name="{{ $seg['key'] }}" id="{{ $seg['key'] }}" class="form-control ep-sel">
                        <option value="">--</option>
                        @foreach($rwmaOptions as $opt)
                            <option value="{{ $opt }}" {{ $v($seg['key'])==$opt?'selected':'' }}>{{ $opt }}</option>
                        @endforeach
                    </select>
                </div>
            @endforeach
        </div>
        <div class="mt-3">
            <label class="wm-label">RWMA Comments <span style="color:#999;font-weight:400">(optional — anything not captured above)</span></label>
            <textarea name="rwma_comments" id="rwma_comments" class="form-control" rows="2" placeholder="e.g. Compensatory hyperkinesia of basal inferior wall. No LV thrombus seen.">{{ $v('rwma_comments') }}</textarea>
        </div>
    </div>
</div>

{{-- ===== COLOR DOPPLER ===== --}}
<div class="card mb-3">
    <div class="card-header"><i class="fa-solid fa-wave-square me-2"></i>Color Doppler Study</div>
    <div class="table-wrap">
        <table class="sc-table ep-table">
            <thead>
                <tr>
                    <th style="width:5%">Valve</th>
                    <th style="width:24%">Velocity (cm/sec)</th>
                    <th style="width:24%">Pressure Gradient (mmHg)</th>
                    <th>Valvular Anatomy</th>
                </tr>
            </thead>
            <tbody>

{{-- MV --}}
<tr>
    <td class="ep-valve">MV</td>
    <td>
        <select class="form-control ep-sel" style="margin-bottom:5px" onchange="fp('mv_velocity',this)">
            <option value="">-- select --</option>
            <optgroup label="✓ Normal (60-130 cm/s)">
                <option value="70">70 cm/s</option>
                <option value="80">80 cm/s</option>
                <option value="90">90 cm/s</option>
                <option value="110">110 cm/s</option>
            </optgroup>
            <optgroup label="↑ Mild MS (130-150 cm/s)">
                <option value="135">135 cm/s</option>
                <option value="145">145 cm/s</option>
            </optgroup>
            <optgroup label="↑↑ Moderate MS (150-200 cm/s)">
                <option value="165">165 cm/s</option>
                <option value="185">185 cm/s</option>
            </optgroup>
            <optgroup label="↑↑↑ Severe MS (&gt;200 cm/s)">
                <option value="220">220 cm/s</option>
                <option value="250">250 cm/s</option>
            </optgroup>
        </select>
        <input type="text" name="mv_velocity" id="mv_velocity" class="form-control ep-inp" value="{{ $v('mv_velocity') }}" placeholder="cm/sec">
    </td>
    <td>
        <select class="form-control ep-sel" style="margin-bottom:5px" onchange="fp('mv_gradient',this)">
            <option value="">-- select --</option>
            <optgroup label="✓ Normal (&lt;3 mmHg)">
                <option value="1">1 mmHg</option>
                <option value="2">2 mmHg</option>
                <option value="3">3 mmHg (upper normal)</option>
            </optgroup>
            <optgroup label="↑ Mild MS (5-10 mmHg)">
                <option value="5">5 mmHg</option>
                <option value="7">7 mmHg</option>
                <option value="9">9 mmHg</option>
            </optgroup>
            <optgroup label="↑↑ Moderate MS (10-15 mmHg)">
                <option value="10">10 mmHg</option>
                <option value="12">12 mmHg</option>
            </optgroup>
            <optgroup label="↑↑↑ Severe MS (&gt;15 mmHg)">
                <option value="16">16 mmHg</option>
                <option value="20">20 mmHg</option>
            </optgroup>
        </select>
        <input type="text" name="mv_gradient" id="mv_gradient" class="form-control ep-inp" value="{{ $v('mv_gradient') }}" placeholder="mmHg">
    </td>
    <td>
        <select class="form-control ep-sel" style="margin-bottom:5px" onchange="fp('mitral_valve',this)">
            <option value="">-- quick select --</option>
            <option value="Normal - no MS, no MR">Normal - no MS, no MR</option>
            <option value="Mild MR">Mild MR</option>
            <option value="Moderate MR">Moderate MR</option>
            <option value="Severe MR">Severe MR</option>
            <option value="Mild MS">Mild MS</option>
            <option value="Moderate MS">Moderate MS</option>
            <option value="Severe MS">Severe MS</option>
            <option value="MVP (Prolapse)">MVP (Prolapse)</option>
            <option value="Rheumatic MS">Rheumatic MS</option>
            <option value="Flail leaflet">Flail leaflet</option>
        </select>
        <input type="text" name="mitral_valve" id="mitral_valve" class="form-control ep-inp" value="{{ $v('mitral_valve') }}" placeholder="Normal - no MS, no MR">
    </td>
</tr>

{{-- AV --}}
<tr>
    <td class="ep-valve">AV</td>
    <td>
        <select class="form-control ep-sel" style="margin-bottom:5px" onchange="fp('av_velocity',this)">
            <option value="">-- select --</option>
            <optgroup label="✓ Normal (100-200 cm/s)">
                <option value="120">120 cm/s</option>
                <option value="150">150 cm/s</option>
                <option value="180">180 cm/s</option>
            </optgroup>
            <optgroup label="↑ Mild AS (200-300 cm/s)">
                <option value="220">220 cm/s</option>
                <option value="250">250 cm/s</option>
                <option value="280">280 cm/s</option>
            </optgroup>
            <optgroup label="↑↑ Moderate AS (300-400 cm/s)">
                <option value="320">320 cm/s</option>
                <option value="360">360 cm/s</option>
            </optgroup>
            <optgroup label="↑↑↑ Severe AS (&gt;400 cm/s)">
                <option value="420">420 cm/s</option>
                <option value="480">480 cm/s</option>
                <option value="550">550 cm/s</option>
            </optgroup>
        </select>
        <input type="text" name="av_velocity" id="av_velocity" class="form-control ep-inp" value="{{ $v('av_velocity') }}" placeholder="cm/sec">
    </td>
    <td>
        <select class="form-control ep-sel" style="margin-bottom:5px" onchange="fp('av_gradient',this)">
            <option value="">-- select --</option>
            <optgroup label="✓ Normal (&lt;15 mmHg)">
                <option value="5">5 mmHg</option>
                <option value="8">8 mmHg</option>
                <option value="12">12 mmHg</option>
            </optgroup>
            <optgroup label="↑ Mild AS (15-25 mmHg)">
                <option value="18">18 mmHg</option>
                <option value="22">22 mmHg</option>
            </optgroup>
            <optgroup label="↑↑ Moderate AS (25-50 mmHg)">
                <option value="30">30 mmHg</option>
                <option value="40">40 mmHg</option>
            </optgroup>
            <optgroup label="↑↑↑ Severe AS (&gt;50 mmHg)">
                <option value="55">55 mmHg</option>
                <option value="64">64 mmHg</option>
                <option value="80">80 mmHg</option>
            </optgroup>
        </select>
        <input type="text" name="av_gradient" id="av_gradient" class="form-control ep-inp" value="{{ $v('av_gradient') }}" placeholder="mmHg">
    </td>
    <td>
        <select class="form-control ep-sel" style="margin-bottom:5px" onchange="fp('aortic_valve',this)">
            <option value="">-- quick select --</option>
            <option value="Normal - no AS, no AR">Normal - no AS, no AR</option>
            <option value="Mild AR">Mild AR</option>
            <option value="Moderate AR">Moderate AR</option>
            <option value="Severe AR">Severe AR</option>
            <option value="Mild AS">Mild AS</option>
            <option value="Moderate AS">Moderate AS</option>
            <option value="Severe AS (calcific)">Severe AS (calcific)</option>
            <option value="Bicuspid AV">Bicuspid AV</option>
        </select>
        <input type="text" name="aortic_valve" id="aortic_valve" class="form-control ep-inp" value="{{ $v('aortic_valve') }}" placeholder="Normal - no AS, no AR">
    </td>
</tr>

{{-- TV --}}
<tr>
    <td class="ep-valve">TV</td>
    <td>
        <select class="form-control ep-sel" style="margin-bottom:5px" onchange="fp('tv_velocity',this)">
            <option value="">-- select --</option>
            <optgroup label="✓ Normal inflow (60-100 cm/s)">
                <option value="70">70 cm/s</option>
                <option value="80">80 cm/s</option>
            </optgroup>
            <optgroup label="↑ TR jet – Mild (&lt;250 cm/s)">
                <option value="200">200 cm/s</option>
                <option value="230">230 cm/s</option>
            </optgroup>
            <optgroup label="↑↑ TR jet – Moderate (250-350 cm/s)">
                <option value="270">270 cm/s</option>
                <option value="310">310 cm/s</option>
            </optgroup>
            <optgroup label="↑↑↑ TR jet – Severe (&gt;350 cm/s)">
                <option value="360">360 cm/s</option>
                <option value="420">420 cm/s</option>
            </optgroup>
        </select>
        <input type="text" name="tv_velocity" id="tv_velocity" class="form-control ep-inp" value="{{ $v('tv_velocity') }}" placeholder="cm/sec">
    </td>
    <td>
        <select class="form-control ep-sel" style="margin-bottom:5px" onchange="fp('tv_gradient',this)">
            <option value="">-- select --</option>
            <optgroup label="✓ Normal (&lt;3 mmHg)">
                <option value="1">1 mmHg</option>
                <option value="2">2 mmHg</option>
                <option value="3">3 mmHg (upper normal)</option>
            </optgroup>
            <optgroup label="↑ Mild TS (3-5 mmHg)">
                <option value="4">4 mmHg</option>
                <option value="5">5 mmHg</option>
            </optgroup>
            <optgroup label="↑↑ Moderate-Severe TS (&gt;5 mmHg)">
                <option value="6">6 mmHg</option>
                <option value="8">8 mmHg</option>
                <option value="10">10 mmHg</option>
            </optgroup>
        </select>
        <input type="text" name="tv_gradient" id="tv_gradient" class="form-control ep-inp" value="{{ $v('tv_gradient') }}" placeholder="mmHg">
    </td>
    <td>
        <select class="form-control ep-sel" style="margin-bottom:5px" onchange="fp('tricuspid_valve',this)">
            <option value="">-- quick select --</option>
            <option value="Normal - no TR">Normal - no TR</option>
            <option value="Mild TR">Mild TR</option>
            <option value="Moderate TR">Moderate TR</option>
            <option value="Severe TR">Severe TR</option>
            <option value="Mild TS">Mild TS</option>
            <option value="Moderate TS">Moderate TS</option>
        </select>
        <input type="text" name="tricuspid_valve" id="tricuspid_valve" class="form-control ep-inp" value="{{ $v('tricuspid_valve') }}" placeholder="Normal - no TR">
    </td>
</tr>

{{-- PV --}}
<tr>
    <td class="ep-valve">PV</td>
    <td>
        <select class="form-control ep-sel" style="margin-bottom:5px" onchange="fp('pv_velocity',this)">
            <option value="">-- select --</option>
            <optgroup label="✓ Normal (75-130 cm/s)">
                <option value="80">80 cm/s</option>
                <option value="100">100 cm/s</option>
                <option value="120">120 cm/s</option>
            </optgroup>
            <optgroup label="↑ Mild PS (150-250 cm/s)">
                <option value="160">160 cm/s</option>
                <option value="200">200 cm/s</option>
            </optgroup>
            <optgroup label="↑↑ Moderate PS (250-350 cm/s)">
                <option value="280">280 cm/s</option>
                <option value="320">320 cm/s</option>
            </optgroup>
            <optgroup label="↑↑↑ Severe PS (&gt;350 cm/s)">
                <option value="380">380 cm/s</option>
                <option value="450">450 cm/s</option>
            </optgroup>
        </select>
        <input type="text" name="pv_velocity" id="pv_velocity" class="form-control ep-inp" value="{{ $v('pv_velocity') }}" placeholder="cm/sec">
    </td>
    <td>
        <select class="form-control ep-sel" style="margin-bottom:5px" onchange="fp('pv_gradient',this)">
            <option value="">-- select --</option>
            <optgroup label="✓ Normal (&lt;15 mmHg)">
                <option value="5">5 mmHg</option>
                <option value="8">8 mmHg</option>
                <option value="12">12 mmHg</option>
            </optgroup>
            <optgroup label="↑ Mild PS (15-40 mmHg)">
                <option value="20">20 mmHg</option>
                <option value="28">28 mmHg</option>
                <option value="36">36 mmHg</option>
            </optgroup>
            <optgroup label="↑↑ Moderate PS (40-60 mmHg)">
                <option value="44">44 mmHg</option>
                <option value="55">55 mmHg</option>
            </optgroup>
            <optgroup label="↑↑↑ Severe PS (&gt;60 mmHg)">
                <option value="65">65 mmHg</option>
                <option value="80">80 mmHg</option>
            </optgroup>
        </select>
        <input type="text" name="pv_gradient" id="pv_gradient" class="form-control ep-inp" value="{{ $v('pv_gradient') }}" placeholder="mmHg">
    </td>
    <td>
        <select class="form-control ep-sel" style="margin-bottom:5px" onchange="fp('pulmonary_valve',this)">
            <option value="">-- quick select --</option>
            <option value="Normal - no PR">Normal - no PR</option>
            <option value="Mild PR">Mild PR</option>
            <option value="Moderate PR">Moderate PR</option>
            <option value="Mild PS">Mild PS</option>
            <option value="Moderate PS">Moderate PS</option>
        </select>
        <input type="text" name="pulmonary_valve" id="pulmonary_valve" class="form-control ep-inp" value="{{ $v('pulmonary_valve') }}" placeholder="Normal - no PR">
    </td>
</tr>

            </tbody>
        </table>
    </div>
</div>

{{-- ===== IMPRESSION & RECOMMENDATIONS ===== --}}
<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="fa-solid fa-file-lines me-2"></i>Comments / Impression &amp; Recommendations</span>
        <button type="button" class="btn btn-sm btn-primary" onclick="autoImpression()">
            <i class="fa-solid fa-wand-magic-sparkles me-1"></i>Generate from Values
        </button>
    </div>
    <div class="card-body">
        <div class="alert alert-info py-2 mb-3" style="font-size:12px">
            <i class="fa-solid fa-circle-info me-1"></i>
            Click <strong>Generate from Values</strong> to auto-build the impression from the measurements entered above. You can then edit it freely.
        </div>
        <div class="mb-3">
            <label class="form-label fw-semibold">Impression / Comments <span class="text-danger">*</span></label>
            <textarea name="impression" id="f_impression" class="form-control" rows="9" required>{{ $v('impression') }}</textarea>
        </div>
        <div>
            <label class="form-label fw-semibold">Recommendations</label>
            <textarea name="recommendations" id="f_recommendations" class="form-control" rows="3">{{ $v('recommendations') }}</textarea>
        </div>
    </div>
</div>

{{-- ===== FILE ATTACH ===== --}}
<div class="card mb-3">
    <div class="card-header"><i class="fa-solid fa-file-video me-2"></i>Attach File (optional)</div>
    <div class="card-body">
        @if($isEdit && $echo->echo_file)
        @php $curUrl = str_contains($echo->echo_file,'/') ? asset('storage/'.$echo->echo_file) : asset('storage/echo_files/'.$echo->echo_file); @endphp
            <div class="mb-2 text-muted small"><i class="fa-solid fa-paperclip me-1"></i>Current: <a href="{{ $curUrl }}" target="_blank">{{ basename($echo->echo_file) }}</a></div>
        @endif
        <input type="file" name="echo_file" class="form-control" accept="image/*,.pdf,.mp4">
        <small class="text-muted">Upload image, PDF, or video to replace existing file.</small>
    </div>
</div>

@push('scripts')
<script>
// ── RWMA: mark every LV segment Normal ────────────────────────────────────────
function setAllRwmaNormal() {
    ['anterior_motion','anteroseptal_motion','inferior_motion','inferoseptal_motion',
     'lateral_motion','posterior_motion','apex_motion'].forEach(name => {
        const el = document.getElementById(name) || document.querySelector('[name="'+name+'"]');
        if (el) el.value = 'Normal';
    });
}

// ── Quick-fill helper ──────────────────────────────────────────────────────────
function fp(id, sel) {
    const val = typeof sel === 'string' ? sel : sel.value;
    if (!val) return;
    const el = document.getElementById(id);
    if (el) el.value = val;
}

// ── BSA calculator ─────────────────────────────────────────────────────────────
function calcBSA() {
    const h = parseFloat(document.querySelector('[name=height_cm]')?.value);
    const w = parseFloat(document.querySelector('[name=weight_kg]')?.value);
    if (h && w) {
        document.getElementById('bsa_field').value = Math.sqrt((h * w) / 3600).toFixed(2);
    }
}

// ── Fill all parameters with randomly-selected normal values ──────────────────
function applyNormalDefaults() {
    const pick = arr => arr[Math.floor(Math.random() * arr.length)];
    const setV = (id, val) => { const el = document.getElementById(id); if (el) el.value = val; };
    const setSel = name => {
        const el = document.querySelector('[name=' + name + ']');
        return { to: val => { if (el) el.value = val; } };
    };
    // 16 measurements – random normal values
    setV('rv_wall_thickness', pick(['3','4']));
    setV('rv_dimension',      pick(['15','18','20','22']));
    setV('tapse',             pick(['18','19','20','21','22']));
    setV('ivc_size',          pick(['14','15','16','17','18']));
    setV('ivs',               pick(['8','9','10']));
    setV('lvpw',              pick(['8','9','10']));
    setV('lv_edd',            pick(['44','46','48','50','52']));
    setV('lv_esd',            pick(['28','30','32','34']));
    setV('epss',              pick(['3','4','5']));
    setV('mapse',             pick(['14','15','16','17','18']));
    setV('la_size',           pick(['30','32','34','36']));
    setV('ao_root',           pick(['28','30','32','34']));
    setV('ef_percent',        pick(['55%','58%','60%','62%','65%']));
    setV('fs_percent',        pick(['30%','33%','35%','36%']));
    setV('lap_ee',            pick(['5','6','7']));
    setV('pap',               pick(['14','15','16','18']));
    // Wall motion dropdowns
    setSel('lv_diastolic_function').to('Normal');
    setSel('ea_ratio').to('Normal (E/A >1.0)');
    setSel('lv_systolic_function').to('Normal');
    setSel('ivs_motion').to('Normal / Intact');
    setSel('pericardium_status').to('Normal');
    setSel('rv_longitudinal_function').to('Normal');
    setSel('rv_transverse_function').to('Normal');
    setSel('rv_paradoxical_movement').to('Absent');
    setSel('ias_status').to('Intact');
    setSel('pericardial_effusion').to('None');
    // RWMA — all segments normal for the all-normal preset
    setAllRwmaNormal();
    // Color Doppler – random normal values
    setV('mv_velocity',    pick(['70','80','90','110']));
    setV('mv_gradient',    pick(['1','2']));
    setV('mitral_valve',   'Normal - no MS, no MR');
    setV('av_velocity',    pick(['120','140','150','160','180']));
    setV('av_gradient',    pick(['5','8','10','12']));
    setV('aortic_valve',   'Normal - no AS, no AR');
    setV('tv_velocity',    pick(['70','80']));
    setV('tv_gradient',    pick(['1','2']));
    setV('tricuspid_valve','Normal - no TR');
    setV('pv_velocity',    pick(['80','90','100','110','120']));
    setV('pv_gradient',    pick(['5','8','10','12']));
    setV('pulmonary_valve','Normal - no PR');
}

// ── Auto-generate impression from entered values ───────────────────────────────
function autoImpression() {
    // Helpers
    const num = id => { const el = document.getElementById(id); return el ? (parseFloat(el.value) || null) : null; };
    const str = id => {
        const el = document.getElementById(id) || document.querySelector('[name="' + id + '"]');
        return el ? (el.value || '').trim() : '';
    };
    // ─ ALL-NORMAL SHORTCUT ─────────────────────────────────────────────────────
    const _ef   = parseFloat(str('ef_percent')) || null;
    const _ivs  = num('ivs');  const _lvpw = num('lvpw');
    const _edd  = num('lv_edd'); const _la  = num('la_size');
    const _ao   = num('ao_root'); const _rvid = num('rv_dimension');
    const _tap  = num('tapse'); const _rvwt = num('rv_wall_thickness');
    const _pap  = num('pap');   const _ivc  = num('ivc_size');
    const _epss = num('epss');  const _mapse = num('mapse');
    const _dFn  = str('lv_diastolic_function'); const _sFn = str('lv_systolic_function');
    const _peEff= str('pericardial_effusion');  const _ias = str('ias_status');
    const _mv   = str('mitral_valve'); const _av = str('aortic_valve');
    const _tv   = str('tricuspid_valve'); const _pv = str('pulmonary_valve');
    const allNormal = (
        _ef !== null && _ef >= 55 && _ef <= 70 &&
        _ivs !== null && _ivs <= 12 && _lvpw !== null && _lvpw <= 11 &&
        _edd !== null && _edd >= 35 && _edd <= 55 &&
        _la  !== null && _la  <= 40 &&
        _ao  !== null && _ao  <= 37 &&
        _rvid !== null && _rvid <= 23 &&
        _tap  !== null && _tap  >= 17 &&
        _rvwt !== null && _rvwt < 5  &&
        _pap  !== null && _pap  < 20 &&
        _ivc  !== null && _ivc  < 20 &&
        _epss !== null && _epss < 7  &&
        _mapse !== null && _mapse >= 12 &&
        (_dFn === '' || _dFn === 'Normal') &&
        (_sFn === '' || _sFn === 'Normal') &&
        _peEff === 'None' && _ias === 'Intact' &&
        (_mv === '' || _mv.toLowerCase().includes('normal')) &&
        (_av === '' || _av.toLowerCase().includes('normal')) &&
        (_tv === '' || _tv.toLowerCase().includes('normal')) &&
        (_pv === '' || _pv.toLowerCase().includes('normal'))
    );
    if (allNormal) {
        document.getElementById('f_impression').value =
            'Normal Echo and Color Doppler Study.\n\n' +
            '1. Left ventricle is normal in size and wall thickness.\n' +
            '2. LV systolic function is normal. Ejection Fraction (EF) = ' + _ef + '%.\n' +
            '3. LV diastolic function is normal.\n' +
            '4. Right ventricle is normal in size and function (TAPSE ' + _tap + ' mm).\n' +
            '5. Both atria are normal in size.\n' +
            '6. Aortic root is normal (' + _ao + ' mm).\n' +
            '7. All four cardiac valves are normal – no significant stenosis or regurgitation.\n' +
            '8. Interventricular septum and inter-atrial septum are intact.\n' +
            '9. No pericardial effusion.\n' +
            '10. Pulmonary artery pressure is within normal limits (PAP ' + _pap + ' mmHg).\n\n' +
            'CONCLUSION: Normal Echocardiogram and Color Doppler Study.';
        document.getElementById('f_recommendations').value =
            'Routine follow-up as clinically indicated.\nRepeat echo if symptoms develop.';
        document.getElementById('f_impression').scrollIntoView({ behavior: 'smooth', block: 'center' });
        return;
    }

    // Measurements
    const rvwt   = num('rv_wall_thickness');
    const rvid   = num('rv_dimension');
    const tapse  = num('tapse');
    const ivc    = num('ivc_size');
    const ivs    = num('ivs');
    const lvpw   = num('lvpw');
    const lvidd  = num('lv_edd');
    const lvids  = num('lv_esd');
    const epss   = num('epss');
    const mapse  = num('mapse');
    const la     = num('la_size');
    const ao     = num('ao_root');
    const efNum  = parseFloat(str('ef_percent')) || null;
    const fsNum  = parseFloat(str('fs_percent')) || null;
    const lapEe  = num('lap_ee');
    const pap    = num('pap');

    // Dropdowns
    const dFn       = str('lv_diastolic_function');
    const sFn       = str('lv_systolic_function');
    const eaRatio   = str('ea_ratio');
    const ivsMot    = str('ivs_motion');
    const peEff     = str('pericardial_effusion');
    const iasStat   = str('ias_status');
    const mv        = str('mitral_valve');
    const av        = str('aortic_valve');
    const tv        = str('tricuspid_valve');
    const pv        = str('pulmonary_valve');

    const findings = [];
    const recs     = [];

    // ─ LV SIZE ─
    if (lvidd !== null) {
        if      (lvidd > 70) findings.push(`Severely dilated left ventricle (LVIDD ${lvidd} mm)`);
        else if (lvidd > 60) findings.push(`Moderately dilated left ventricle (LVIDD ${lvidd} mm)`);
        else if (lvidd > 55) findings.push(`Mildly dilated left ventricle (LVIDD ${lvidd} mm)`);
        else if (lvidd < 35) findings.push(`Small LV cavity (LVIDD ${lvidd} mm) – HCM pattern`);
        else                  findings.push(`Normal LV size (LVIDD ${lvidd} mm)`);
    }

    // ─ LV WALL / LVH ─
    const ivsTk  = ivs  !== null && ivs  > 12;
    const lvpwTk = lvpw !== null && lvpw > 11;
    const lvDil  = lvidd !== null && lvidd > 55;
    if (ivsTk || lvpwTk) {
        let sev = '', type = '';
        const maxW = Math.max(ivsTk ? ivs : 0, lvpwTk ? lvpw : 0);
        sev = maxW >= 20 ? 'Severe ' : maxW >= 15 ? 'Moderate ' : 'Mild ';
        if (ivsTk && lvpwTk) type = lvDil ? 'eccentric LVH' : 'concentric LVH';
        else if (ivsTk)       type = 'asymmetric septal hypertrophy';
        else                  type = 'posterior wall hypertrophy';
        const det = [ivsTk ? `IVS ${ivs} mm` : null, lvpwTk ? `LVPW ${lvpw} mm` : null].filter(Boolean).join(', ');
        findings.push(`${sev}${type}${det ? ` (${det})` : ''}`);
    } else if (ivs !== null && lvpw !== null) {
        findings.push(`Normal LV wall thickness (IVS ${ivs} mm, LVPW ${lvpw} mm)`);
    }

    // ─ LV SYSTOLIC FUNCTION ─
    if (efNum !== null) {
        const efStr = efNum + '%';
        if (efNum > 70) {
            findings.push(`Hyperdynamic LV systolic function (EF ${efStr})`);
        } else if (efNum >= 55) {
            findings.push(`Normal LV systolic function (EF ${efStr})`);
        } else if (efNum >= 45) {
            findings.push(`Mildly reduced LV systolic function (EF ${efStr})`);
            recs.push('Cardiology review. Optimise beta-blocker and ACEi/ARB therapy. Repeat echo in 3–6 months.');
        } else if (efNum >= 35) {
            findings.push(`Moderately reduced LV systolic function (EF ${efStr})`);
            recs.push('Cardiology referral. Initiate GDMT: beta-blocker, ACEi/ARBi, MRA, SGLT2i. Repeat echo in 3 months.');
        } else {
            findings.push(`Severely reduced LV systolic function (EF ${efStr})`);
            recs.push('Urgent cardiology referral. GDMT: beta-blocker, ACEi/ARBi, MRA, SGLT2i. ICD/CRT assessment. Salt and fluid restriction.');
        }
    }
    if (fsNum !== null && efNum === null) {
        // FS as fallback
        if (fsNum < 20) findings.push(`Severely reduced fractional shortening (FS ${fsNum}%)`);
        else if (fsNum < 28) findings.push(`Reduced fractional shortening (FS ${fsNum}%)`);
    }

    // ─ LV DIASTOLIC FUNCTION ─
    let diastDesc = dFn || '';
    if (!diastDesc && lapEe !== null) {
        if      (lapEe < 8)  diastDesc = 'Normal LV diastolic function';
        else if (lapEe <= 14) diastDesc = 'Grade II diastolic dysfunction (intermediate filling pressure)';
        else                  diastDesc = 'Grade III diastolic dysfunction (restrictive filling – elevated LA pressure)';
    }
    if (diastDesc) {
        const extra = [lapEe !== null ? `E/e' ${lapEe}` : null, eaRatio || null].filter(Boolean).join('. ');
        findings.push(diastDesc + (extra ? `. ${extra}` : ''));
    }

    // ─ EPSS / MAPSE ─
    if (epss !== null && epss >= 7)  findings.push(`Increased EPSS (${epss} mm) – consistent with impaired LV function`);
    if (mapse !== null && mapse < 12) findings.push(`Reduced MAPSE (${mapse} mm) – LV longitudinal dysfunction`);

    // ─ LA ─
    if (la !== null) {
        if      (la > 55) { findings.push(`Severely dilated LA (${la} mm) – high risk of AF and thrombus`); recs.push('Anticoagulation assessment (CHA₂DS₂-VASc score). Screen for AF.'); }
        else if (la > 48)  findings.push(`Moderately dilated LA (${la} mm)`);
        else if (la > 40)  findings.push(`Mildly dilated LA (${la} mm)`);
        else               findings.push(`Normal LA size (${la} mm)`);
    }

    // ─ AO ROOT ─
    if (ao !== null) {
        if      (ao > 44) { findings.push(`Dilated aortic root (${ao} mm)`); recs.push('CT aorta for full assessment. Surgical review if >55 mm. Serial echo surveillance.'); }
        else if (ao > 37)  { findings.push(`Mildly dilated aortic root (${ao} mm)`); recs.push('Serial echo for aortic root surveillance annually.'); }
        else               findings.push(`Normal aortic root (${ao} mm)`);
    }

    // ─ RV SIZE ─
    if (rvid !== null) {
        if      (rvid > 42) findings.push(`Severely dilated RV (RVID ${rvid} mm)`);
        else if (rvid > 35) findings.push(`Moderately dilated RV (RVID ${rvid} mm)`);
        else if (rvid > 23) findings.push(`Mildly dilated RV (RVID ${rvid} mm)`);
        else                findings.push(`Normal RV size (RVID ${rvid} mm)`);
    }

    // ─ RV FUNCTION ─
    if (tapse !== null) {
        if      (tapse < 12) findings.push(`Severely reduced RV systolic function (TAPSE ${tapse} mm)`);
        else if (tapse < 17) findings.push(`Mildly-moderately reduced RV systolic function (TAPSE ${tapse} mm)`);
        else                 findings.push(`Normal RV systolic function (TAPSE ${tapse} mm)`);
    }

    // ─ RV WALL THICKNESS ─
    if (rvwt !== null && rvwt >= 5) findings.push(`RV free wall thickening (${rvwt} mm) – RV pressure overload`);

    // ─ PAP ─
    if (pap !== null) {
        if      (pap > 50) { findings.push(`Severe pulmonary hypertension (PAP ${pap} mmHg)`); recs.push('Urgent cardiology referral for PAH workup (RHC, VQ scan, CTPA, PFTs). PAH-specific therapy if confirmed.'); }
        else if (pap > 35) { findings.push(`Moderate pulmonary hypertension (PAP ${pap} mmHg)`); recs.push('Cardiology review. Investigate cause of pulmonary hypertension. Diuretics as needed.'); }
        else if (pap > 20) { findings.push(`Mild pulmonary hypertension (PAP ${pap} mmHg)`); recs.push('Monitor PAP. Investigate and treat underlying cause.'); }
        else               findings.push(`Normal pulmonary artery pressure (PAP ${pap} mmHg)`);
    }

    // ─ IVC ─
    if (ivc !== null) {
        if      (ivc > 24) findings.push(`Dilated IVC (${ivc} mm) with poor respiratory collapse – elevated RA pressure`);
        else if (ivc > 20) findings.push(`Mildly dilated IVC (${ivc} mm)`);
        else               findings.push(`Normal IVC (${ivc} mm) with normal respiratory collapse`);
    }

    // ─ VALVES ─
    if (mv) {
        if (mv.toLowerCase().includes('normal'))
            findings.push('Mitral valve: Normal morphology. No significant MS or MR');
        else
            findings.push(`Mitral valve: ${mv}`);
        const mL = mv.toLowerCase();
        if      (mL.includes('severe ms') || mL.includes('rheumatic ms')) recs.push('Cardiology review. Wilkins score for PTMC candidacy. Penicillin prophylaxis for RHD. Anticoagulation if AF.');
        else if (mL.includes('severe mr') || mL.includes('flail'))        recs.push('Cardiac surgery/cardiology review for mitral valve repair or replacement. Serial echo 6-monthly.');
    }
    if (av) {
        if (av.toLowerCase().includes('normal'))
            findings.push('Aortic valve: Normal tricuspid morphology. No significant AS or AR');
        else
            findings.push(`Aortic valve: ${av}`);
        const aL = av.toLowerCase();
        if      (aL.includes('severe as')) recs.push('Urgent cardiac surgery/TAVI assessment. Avoid vigorous activity and vasodilators. Cardiology referral.');
        else if (aL.includes('severe ar')) recs.push('Cardiac surgery review for AVR. Vasodilator therapy (nifedipine/ACEi). Serial echo 6-monthly.');
    }
    if (tv) findings.push(tv.toLowerCase().includes('normal') ? 'Tricuspid valve: Normal. Trivial TR' : `Tricuspid valve: ${tv}`);
    if (pv) findings.push(pv.toLowerCase().includes('normal') ? 'Pulmonary valve: Normal. Trivial PR' : `Pulmonary valve: ${pv}`);

    // ─ IVS MOTION ─
    if      (ivsMot && ivsMot !== 'Normal / Intact' && ivsMot) findings.push(`Interventricular septum: ${ivsMot}`);
    else if (ivsMot === 'Normal / Intact')                      findings.push('Intact interventricular septum. No VSD. No abnormal pressure gradient');

    // ─ IAS ─
    if      (iasStat && iasStat !== 'Intact' && iasStat) findings.push(`Inter-atrial septum: ${iasStat}`);
    else if (iasStat === 'Intact')                         findings.push('Intact inter-atrial septum. No ASD');

    // ─ PERICARDIAL EFFUSION ─
    if (peEff) {
        if (peEff === 'None') findings.push('No pericardial effusion');
        else {
            findings.push(`Pericardial effusion: ${peEff}`);
            if      (peEff.toLowerCase().includes('large'))    recs.push("URGENT: Assess for cardiac tamponade (Beck's triad). Consider emergency pericardiocentesis.");
            else if (peEff.toLowerCase().includes('moderate')) recs.push('Serial echo for pericardial effusion. Investigate cause (viral/autoimmune/malignant/uraemic). NSAIDs/colchicine for pericarditis.');
        }
    }

    // ─ CONCLUSION ─
    const lvhLabel  = (ivsTk && lvpwTk) ? (lvDil ? 'Eccentric LVH' : 'Concentric LVH') : (ivsTk ? 'Asymmetric septal hypertrophy' : '');
    const efLabel   = efNum !== null ? (efNum >= 55 ? `preserved EF (${efNum}%)` : `reduced EF (${efNum}%)`) : '';
    const diaLabel  = diastDesc || '';
    const pahLabel  = pap !== null && pap > 20 ? (pap > 50 ? 'severe pulmonary hypertension' : pap > 35 ? 'moderate pulmonary hypertension' : 'mild pulmonary hypertension') : '';
    let concl = '';
    if (lvhLabel && efLabel && diaLabel && !diaLabel.toLowerCase().includes('normal'))
        concl = `${lvhLabel} with ${efLabel} and ${diaLabel}`;
    else if (lvhLabel && efLabel)
        concl = `${lvhLabel} with ${efLabel}`;
    else if (efNum !== null && efNum >= 55 && !lvhLabel)
        concl = `Normal echocardiographic study with ${efLabel}` + (diaLabel && !diaLabel.toLowerCase().includes('normal') ? `. ${diaLabel}` : '');
    else if (efLabel)
        concl = `LV dysfunction with ${efLabel}` + (diaLabel ? `. ${diaLabel}` : '');
    if (pahLabel) concl += (concl ? '. ' : '') + pahLabel.charAt(0).toUpperCase() + pahLabel.slice(1);
    if (!concl) concl = 'Echo findings as described above';

    // ─ BUILD TEXT ─
    let txt = '';
    findings.forEach((f, i) => { txt += `${i + 1}. ${f}.\n`; });
    txt += `\nCONCLUSION: ${concl}.`;
    document.getElementById('f_impression').value = txt;

    if (!recs.length) {
        recs.push('Routine follow-up as clinically indicated.');
        recs.push('Diet and lifestyle modifications. Regular BP and weight monitoring.');
    }
    document.getElementById('f_recommendations').value = recs.join('\n');
    document.getElementById('f_impression').scrollIntoView({ behavior: 'smooth', block: 'center' });
}

// ── Auto-update impression whenever any measurement or dropdown changes ────────
let _impressionTimer = null;
function scheduleAutoImpression() {
    clearTimeout(_impressionTimer);
    _impressionTimer = setTimeout(autoImpression, 600);
}

// ── Page init ──────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    @if(!$isEdit)
        // Pre-fill height / weight then compute BSA
        const htEl = document.querySelector('[name=height_cm]');
        const wtEl = document.querySelector('[name=weight_kg]');
        if (htEl && !htEl.value) htEl.value = '170';
        if (wtEl && !wtEl.value) wtEl.value = '70';
        calcBSA();
        // Fill all parameters with random normal values and generate impression
        applyNormalDefaults();
        autoImpression();
        // Select2 patient search
        if (typeof $ !== 'undefined' && $.fn.select2) {
            $('#echo-patient-select').select2({ placeholder: 'Search patient...' });
        }
    @endif

    // Wire live auto-regeneration for both create and edit
    const watchIds = [
        'rv_wall_thickness','rv_dimension','tapse','ivc_size','ivs','lvpw',
        'lv_edd','lv_esd','epss','mapse','la_size','ao_root',
        'ef_percent','fs_percent','lap_ee','pap',
        'mitral_valve','aortic_valve','tricuspid_valve','pulmonary_valve'
    ];
    watchIds.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('input', scheduleAutoImpression);
    });
    const watchNames = [
        'lv_diastolic_function','ea_ratio','lv_systolic_function','ivs_motion',
        'pericardium_status','rv_longitudinal_function','rv_transverse_function',
        'rv_paradoxical_movement','ias_status','pericardial_effusion'
    ];
    watchNames.forEach(name => {
        const el = document.querySelector('[name="' + name + '"]');
        if (el) el.addEventListener('change', scheduleAutoImpression);
    });

    // ── Template library wiring ────────────────────────────────────────────────
    const tplSel = document.getElementById('echo-template-select');
    if (tplSel) {
        tplSel.addEventListener('change', updateEchoTemplateMeta);
        updateEchoTemplateMeta();
    }
});

// ── Template library: keys we apply (must match EchoTemplate::FIELD_KEYS) ─────
const ECHO_TEMPLATE_KEYS = [
    'diagnosis','ref_by','height_cm','weight_kg','bsa',
    'rv_wall_thickness','rv_dimension','tapse','ivc_size',
    'ivs','lvpw','lv_edd','lv_esd','epss','mapse',
    'la_size','ao_root','ef_percent','fs_percent','ea_ratio','lap_ee','pap',
    'mitral_valve','aortic_valve','tricuspid_valve','pulmonary_valve',
    'lv_diastolic_function','lv_systolic_function','ivs_motion','pericardium_status',
    'rv_longitudinal_function','rv_transverse_function','rv_paradoxical_movement',
    'ias_status','pericardial_effusion',
    'anterior_motion','anteroseptal_motion','inferior_motion','inferoseptal_motion',
    'lateral_motion','posterior_motion','apex_motion','rwma_comments',
    'mv_velocity','mv_gradient','av_velocity','av_gradient',
    'tv_velocity','tv_gradient','pv_velocity','pv_gradient',
    'impression','recommendations',
];

function updateEchoTemplateMeta() {
    const sel = document.getElementById('echo-template-select');
    const desc = document.getElementById('echo-template-desc');
    const delBtn = document.getElementById('echo-template-delete');
    const opt = sel && sel.selectedOptions[0];
    if (!opt || !opt.value) {
        if (desc) desc.textContent = '';
        if (delBtn) delBtn.disabled = true;
        return;
    }
    if (desc) desc.textContent = opt.title || '';
    if (delBtn) delBtn.disabled = opt.dataset.builtin === '1';
}

function applyEchoTemplate() {
    const sel = document.getElementById('echo-template-select');
    const opt = sel && sel.selectedOptions[0];
    if (!opt || !opt.value) { alert('Pick a template first.'); return; }
    let fields;
    try { fields = JSON.parse(opt.dataset.fields || '{}'); }
    catch (e) { alert('Template data is corrupt.'); return; }

    ECHO_TEMPLATE_KEYS.forEach(k => {
        if (!(k in fields)) return;
        const val = fields[k] ?? '';
        const el = document.getElementById(k) || document.querySelector('[name="' + k + '"]');
        if (el) el.value = val;
    });
    // Trigger any HTML-decoded values on selects (PHP json encoder escapes &gt; as &amp;gt;)
    document.querySelectorAll('select[name]').forEach(s => {
        if (!ECHO_TEMPLATE_KEYS.includes(s.name)) return;
        const want = fields[s.name];
        if (want === undefined) return;
        // First try direct match, then a HTML-decoded match
        let matched = Array.from(s.options).some(o => o.value === want && (s.value = want));
        if (!matched) {
            const decoded = decodeHtmlEntities(want);
            Array.from(s.options).some(o => o.value === decoded && (s.value = decoded));
        }
    });
    if (typeof calcBSA === 'function') calcBSA();
    if (typeof autoImpression === 'function' && !fields.impression) autoImpression();
}

function decodeHtmlEntities(str) {
    const t = document.createElement('textarea');
    t.innerHTML = str;
    return t.value;
}

function openSaveTemplateModal() {
    document.getElementById('template-modal-name').value = '';
    document.getElementById('template-modal-description').value = '';
    document.getElementById('echo-template-modal').style.display = 'flex';
    setTimeout(() => document.getElementById('template-modal-name').focus(), 50);
}
function closeSaveTemplateModal() {
    document.getElementById('echo-template-modal').style.display = 'none';
}

async function submitSaveTemplate() {
    const name = document.getElementById('template-modal-name').value.trim();
    const category = document.getElementById('template-modal-category').value;
    const description = document.getElementById('template-modal-description').value.trim();
    if (!name) { alert('Template name is required.'); return; }

    const fields = {};
    ECHO_TEMPLATE_KEYS.forEach(k => {
        const el = document.getElementById(k) || document.querySelector('[name="' + k + '"]');
        if (el && el.value !== '') fields[k] = el.value;
    });

    try {
        const res = await fetch('{{ route('echo.templates.store') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ name, category, description, fields }),
        });
        if (!res.ok) throw new Error('HTTP ' + res.status);
        const tpl = await res.json();
        // Insert into the dropdown under the right optgroup
        const sel = document.getElementById('echo-template-select');
        let group = Array.from(sel.querySelectorAll('optgroup')).find(g => g.label === categoryLabelFor(tpl.category));
        if (!group) {
            group = document.createElement('optgroup');
            group.label = categoryLabelFor(tpl.category);
            sel.appendChild(group);
        }
        const opt = document.createElement('option');
        opt.value = tpl.id;
        opt.textContent = tpl.name + ' ★';
        opt.dataset.builtin = '0';
        opt.dataset.fields = JSON.stringify(tpl.fields || {});
        opt.title = tpl.description || '';
        group.appendChild(opt);
        sel.value = tpl.id;
        updateEchoTemplateMeta();
        closeSaveTemplateModal();
    } catch (e) {
        alert('Could not save template: ' + e.message);
    }
}

function categoryLabelFor(key) {
    return {
        normal:'Normal', ihd:'IHD / CAD', lvh:'LVH / Hypertension', dcm:'LV Dysfunction',
        valvular:'Valvular', rv_pah:'RV / Pulmonary HTN',
        congenital:'Congenital', other:'Other',
    }[key] || 'Other';
}

async function deleteEchoTemplate() {
    const sel = document.getElementById('echo-template-select');
    const opt = sel && sel.selectedOptions[0];
    if (!opt || !opt.value || opt.dataset.builtin === '1') return;
    if (!confirm('Delete template "' + opt.textContent.replace('★','').trim() + '"?')) return;
    try {
        const res = await fetch('{{ url('echo/templates') }}/' + opt.value, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
        });
        if (!res.ok) throw new Error('HTTP ' + res.status);
        const parent = opt.parentNode;
        opt.remove();
        if (parent.tagName === 'OPTGROUP' && parent.children.length === 0) parent.remove();
        sel.value = '';
        updateEchoTemplateMeta();
    } catch (e) {
        alert('Could not delete template: ' + e.message);
    }
}
</script>
@endpush
