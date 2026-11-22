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
    'rhd'       => 'Rheumatic Heart Disease (RHD)',
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
        <div class="form-row" style="display:grid;grid-template-columns:2.2fr auto auto;gap:8px;align-items:end">
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

{{-- ===== REGIONAL WALL MOTION / ACUTE MI (RWMA) ===== --}}
@php
$segments = [
    ['key'=>'anterior_motion',     'label'=>'Anterior',              'terr'=>'LAD'],
    ['key'=>'anteroseptal_motion', 'label'=>'Septal (Anteroseptal)', 'terr'=>'LAD'],
    ['key'=>'inferior_motion',     'label'=>'Inferior',              'terr'=>'RCA'],
    ['key'=>'inferoseptal_motion', 'label'=>'Inferoseptal',          'terr'=>'RCA'],
    ['key'=>'lateral_motion',      'label'=>'Lateral',               'terr'=>'LCx'],
    ['key'=>'posterior_motion',    'label'=>'Posterior',             'terr'=>'LCx / RCA'],
    ['key'=>'apex_motion',         'label'=>'Apical (Apex)',         'terr'=>'LAD / RCA'],
];
$rwmaOptions = ['Mild Hypokinesia','Moderate Hypokinesia','Severe Hypokinesia','Akinesia','Dyskinesia','Aneurysmal'];
@endphp
<div class="card mb-3" style="border-left:3px solid var(--sc-teal)">
    <div class="card-header">
        <i class="fa-solid fa-heart-circle-bolt me-2"></i>Regional Wall Motion / Acute MI — Lesion Builder
        <span class="card-actions">
            <button type="button" class="btn btn-sm btn-outline" onclick="setAllRwmaNormal()" title="Uncheck and clear every segment / LA / LV clot">
                <i class="fa-solid fa-check-double"></i>All Normal
            </button>
        </span>
    </div>
    <div style="background:rgba(10,180,180,.07);border-bottom:1px solid rgba(10,180,180,.2);padding:8px 16px;font-size:11.5px;color:#065e5e">
        <i class="fa-solid fa-circle-info" style="margin-right:6px"></i>Check the wall(s) involved and/or LA/LV clot — each reveals its own grading dropdown instantly. Coronary territories shown for reference. Leave everything unchecked when the study is non-ischemic.
    </div>
    <div class="card-body">
        <div style="display:flex;flex-wrap:wrap;gap:16px;align-items:center;margin-bottom:6px">
            @foreach($segments as $seg)
                <label style="display:flex;align-items:center;gap:5px;font-size:12.5px;font-weight:600;color:var(--sc-navy);cursor:pointer;margin:0">
                    <input type="checkbox" id="rwma_cb_{{ $seg['key'] }}" onchange="applyRwmaSelection()" style="width:15px;height:15px;cursor:pointer">{{ $seg['label'] }}
                </label>
            @endforeach
            <label style="display:flex;align-items:center;gap:5px;font-size:12.5px;font-weight:600;color:var(--sc-navy);cursor:pointer;margin:0">
                <input type="checkbox" id="rwma_cb_la_clot" onchange="applyRwmaSelection()" style="width:15px;height:15px;cursor:pointer">LA Clot
            </label>
            <label style="display:flex;align-items:center;gap:5px;font-size:12.5px;font-weight:600;color:var(--sc-navy);cursor:pointer;margin:0">
                <input type="checkbox" id="rwma_cb_lv_clot" onchange="applyRwmaSelection()" style="width:15px;height:15px;cursor:pointer">LV Clot
            </label>
        </div>

        @foreach($segments as $seg)
        <div id="rwma-panel-{{ $seg['key'] }}" class="lesion-panel" style="display:none;border-top:1px dashed var(--sc-border);padding-top:10px;margin-top:10px">
            <label class="wm-label">
                {{ $seg['label'] }} Wall Motion
                <span style="font-weight:500;color:var(--sc-muted);font-size:10.5px">· {{ $seg['terr'] }} territory</span>
            </label>
            <select name="{{ $seg['key'] }}" id="{{ $seg['key'] }}" class="form-control ep-sel" style="max-width:320px">
                <option value="">-- select --</option>
                @foreach($rwmaOptions as $opt)
                    <option value="{{ $opt }}" {{ $v($seg['key'])==$opt?'selected':'' }}>{{ $opt }}</option>
                @endforeach
            </select>
        </div>
        @endforeach

        {{-- LA Clot --}}
        <div id="rwma-panel-la_clot" class="lesion-panel" style="display:none;border-top:1px dashed var(--sc-border);padding-top:10px;margin-top:10px">
            <div style="font-weight:700;font-size:11.5px;color:var(--sc-navy);margin-bottom:8px">LA Clot (Thrombus)</div>
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;align-items:end;max-width:600px">
                <div>
                    <label class="wm-label">Location</label>
                    <select id="la_clot_location" class="form-control ep-sel">
                        <option value="">-- select --</option>
                        <option value="LA appendage">LA appendage</option>
                        <option value="LA body">LA body</option>
                    </select>
                </div>
                <div>
                    <label class="wm-label">Size — Quick Select</label>
                    <select id="la_clot_size_sel" class="form-control ep-sel">
                        <option value="">-- select --</option>
                        <option value="0.8">0.8 cm</option>
                        <option value="1.2">1.2 cm</option>
                        <option value="1.8">1.8 cm</option>
                        <option value="2.5">2.5 cm</option>
                    </select>
                </div>
                <div>
                    <label class="wm-label">Size value</label>
                    <input type="text" id="la_clot_size" class="form-control ep-inp" placeholder="cm">
                </div>
            </div>
            <div class="mt-2" style="max-width:600px">
                <label class="wm-label">LA Clot description <span style="color:#999;font-weight:400">(auto-built from above — editable)</span></label>
                <input type="text" name="la_clot" id="la_clot" class="form-control ep-inp" value="{{ $v('la_clot') }}" placeholder="e.g. 1.2 cm, LA appendage">
            </div>
        </div>

        {{-- LV Clot --}}
        <div id="rwma-panel-lv_clot" class="lesion-panel" style="display:none;border-top:1px dashed var(--sc-border);padding-top:10px;margin-top:10px">
            <div style="font-weight:700;font-size:11.5px;color:var(--sc-navy);margin-bottom:8px">LV Clot (Mural Thrombus)</div>
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:10px;align-items:end;max-width:760px">
                <div>
                    <label class="wm-label">Location</label>
                    <select id="lv_clot_location" class="form-control ep-sel">
                        <option value="">-- select --</option>
                        <option value="apical">Apical</option>
                        <option value="anterior wall">Anterior wall</option>
                        <option value="inferior wall">Inferior wall</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div>
                    <label class="wm-label">Mobility</label>
                    <select id="lv_clot_mobility" class="form-control ep-sel">
                        <option value="">-- select --</option>
                        <option value="non-mobile">Non-mobile</option>
                        <option value="mobile">Mobile</option>
                        <option value="layered">Layered / laminated</option>
                    </select>
                </div>
                <div>
                    <label class="wm-label">Size — Quick Select</label>
                    <select id="lv_clot_size_sel" class="form-control ep-sel">
                        <option value="">-- select --</option>
                        <option value="0.8">0.8 cm</option>
                        <option value="1.2">1.2 cm</option>
                        <option value="1.8">1.8 cm</option>
                        <option value="2.5">2.5 cm</option>
                    </select>
                </div>
                <div>
                    <label class="wm-label">Size value</label>
                    <input type="text" id="lv_clot_size" class="form-control ep-inp" placeholder="cm">
                </div>
            </div>
            <div class="mt-2" style="max-width:760px">
                <label class="wm-label">LV Clot description <span style="color:#999;font-weight:400">(auto-built from above — editable)</span></label>
                <input type="text" name="lv_clot" id="lv_clot" class="form-control ep-inp" value="{{ $v('lv_clot') }}" placeholder="e.g. 1.8 cm, apical, non-mobile">
            </div>
        </div>

        <div class="mt-3">
            <label class="wm-label">RWMA Comments <span style="color:#999;font-weight:400">(optional — anything not captured above)</span></label>
            <textarea name="rwma_comments" id="rwma_comments" class="form-control" rows="2" placeholder="e.g. Compensatory hyperkinesia of basal inferior wall.">{{ $v('rwma_comments') }}</textarea>
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
                <select id="echo-ref-select" class="form-control ep-sel" onchange="onEchoRefSelectChange(this)">
                    <option value="">— None / Self —</option>
                    @foreach($referralSources ?? [] as $rs)
                        <option value="{{ $rs->id }}" data-name="{{ $rs->name }}" {{ (old('referral_source_id', $e?->referral_source_id) == $rs->id) || (!old('referral_source_id', $e?->referral_source_id) && $v('ref_by') === $rs->name) ? 'selected' : '' }}>{{ $rs->name }}</option>
                    @endforeach
                    <option value="__add__" style="font-style:italic;color:var(--sc-teal)">＋ Add new referring doctor…</option>
                </select>
                <input type="hidden" name="ref_by" id="echo-ref-text" value="{{ $v('ref_by') }}">
                <input type="hidden" name="referral_source_id" id="echo-ref-id" value="{{ old('referral_source_id', $e?->referral_source_id) }}">
            </div>
        </div>
        <script>
        function onEchoRefSelectChange(sel) {
            if (sel.value === '__add__') {
                const name = (window.prompt('New referring doctor / dept name (e.g. Dr. R. Kumar, Apollo Hospital):') || '').trim();
                if (!name) { sel.value = ''; document.getElementById('echo-ref-text').value = ''; document.getElementById('echo-ref-id').value = ''; return; }
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                fetch(@json(route('referrals.quick')), {
                    method: 'POST',
                    headers: {'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN': token},
                    body: JSON.stringify({name})
                }).then(r => r.ok ? r.json() : Promise.reject(r))
                  .then(rs => {
                      const opt = new Option(rs.name, rs.id, true, true);
                      opt.dataset.name = rs.name;
                      sel.insertBefore(opt, sel.querySelector('option[value="__add__"]'));
                      document.getElementById('echo-ref-text').value = rs.name;
                      document.getElementById('echo-ref-id').value = rs.id;
                  })
                  .catch(() => { alert('Could not save the new referring doctor. Please try again or use the Referrals page.'); sel.value=''; document.getElementById('echo-ref-text').value=''; document.getElementById('echo-ref-id').value=''; });
                return;
            }
            const opt = sel.options[sel.selectedIndex];
            document.getElementById('echo-ref-text').value = opt?.dataset?.name || '';
            document.getElementById('echo-ref-id').value = (sel.value === '__existing__' || sel.value === '') ? '' : sel.value;
        }
        // Pre-existing free-text ref_by values (e.g. saved before this dropdown existed) won't match
        // any referral source id — fall back to showing them as a synthetic selected option so edits don't clobber the text.
        (function() {
            const sel = document.getElementById('echo-ref-select');
            const txt = document.getElementById('echo-ref-text');
            if (sel && txt && txt.value && sel.value === '') {
                const opt = new Option(txt.value, '__existing__', true, true);
                opt.dataset.name = txt.value;
                sel.insertBefore(opt, sel.querySelector('option[value="__add__"]'));
            }
        })();
        </script>

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
            <option value="Rheumatic MS with mild MR">Rheumatic MS with mild MR</option>
            <option value="Rheumatic MS with moderate MR">Rheumatic MS with moderate MR</option>
            <option value="Rheumatic MS with severe MR">Rheumatic MS with severe MR</option>
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
            <option value="Moderate AS with Mild AR">Moderate AS with Mild AR</option>
            <option value="Severe AS with Severe AR">Severe AS with Severe AR</option>
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

{{-- ===== RHD / MULTIVALVULAR LESION BUILDER ===== --}}
<div class="card mb-3" style="border-left:3px solid var(--sc-teal)">
    <div class="card-header">
        <i class="fa-solid fa-stethoscope me-2"></i>RHD / Multivalvular Lesion Builder
        <span style="font-weight:400;font-size:11px;color:var(--sc-muted);margin-left:8px">Check the lesions present — each reveals its own grading dropdowns instantly and composes into the valve fields above. Still editable directly.</span>
    </div>
    <div class="card-body">
        <div style="display:flex;flex-wrap:wrap;gap:16px;align-items:center;margin-bottom:6px">
            @foreach(['ms'=>'MS','mr'=>'MR','as'=>'AS','ar'=>'AR','ts'=>'TS','tr'=>'TR','ps'=>'PS','pr'=>'PR','pah'=>'PAH'] as $lc => $lbl)
                <label style="display:flex;align-items:center;gap:5px;font-size:12.5px;font-weight:600;color:var(--sc-navy);cursor:pointer;margin:0">
                    <input type="checkbox" id="lesion_{{ $lc }}" onchange="applyLesionSelection()" style="width:15px;height:15px;cursor:pointer">{{ $lbl }}
                </label>
            @endforeach
        </div>

        {{-- MS --}}
        <div id="lesion-panel-ms" class="lesion-panel" style="display:none;border-top:1px dashed var(--sc-border);padding-top:10px;margin-top:10px">
            <div style="font-weight:700;font-size:11.5px;color:var(--sc-navy);margin-bottom:8px">Mitral Stenosis (MS)</div>
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:10px;align-items:end">
                <div>
                    <label class="wm-label">Severity</label>
                    <select id="mv_ms_severity" class="form-control ep-sel">
                        <option value="">None</option>
                        <option value="Mild MS">Mild</option>
                        <option value="Moderate MS">Moderate</option>
                        <option value="Severe MS">Severe</option>
                    </select>
                </div>
                <div>
                    <label class="wm-label">Etiology</label>
                    <div class="p-2" style="display:flex;align-items:center;gap:6px;height:30px">
                        <input type="checkbox" id="mv_rheumatic" style="width:16px;height:16px;cursor:pointer">
                        <label for="mv_rheumatic" style="font-size:12.5px;font-weight:500;margin:0;cursor:pointer">Rheumatic</label>
                    </div>
                </div>
                <div>
                    <label class="wm-label">MVA (cm²) — Quick Select</label>
                    <select id="mva_sel" class="form-control ep-sel">
                        <option value="">-- select --</option>
                        <optgroup label="↑↑↑ Severe MS (&lt;1.0 cm²)">
                            <option value="0.6">0.6 cm²</option>
                            <option value="0.8">0.8 cm²</option>
                            <option value="0.9">0.9 cm²</option>
                        </optgroup>
                        <optgroup label="↑↑ Moderate MS (1.0-1.5 cm²)">
                            <option value="1.0">1.0 cm²</option>
                            <option value="1.2">1.2 cm²</option>
                            <option value="1.4">1.4 cm²</option>
                        </optgroup>
                        <optgroup label="↑ Mild MS (1.6-2.5 cm²)">
                            <option value="1.6">1.6 cm²</option>
                            <option value="2.0">2.0 cm²</option>
                            <option value="2.4">2.4 cm²</option>
                        </optgroup>
                    </select>
                </div>
                <div>
                    <label class="wm-label">MVA value</label>
                    <input type="text" name="mva" id="mva" class="form-control ep-inp" value="{{ $v('mva') }}" placeholder="cm²">
                </div>
            </div>
        </div>

        {{-- MR --}}
        <div id="lesion-panel-mr" class="lesion-panel" style="display:none;border-top:1px dashed var(--sc-border);padding-top:10px;margin-top:10px">
            <div style="font-weight:700;font-size:11.5px;color:var(--sc-navy);margin-bottom:8px">Mitral Regurgitation (MR)</div>
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr 1fr;gap:10px;align-items:end">
                <div>
                    <label class="wm-label">Severity</label>
                    <select id="mv_mr_severity" class="form-control ep-sel">
                        <option value="">None</option>
                        <option value="Mild MR">Mild</option>
                        <option value="Moderate MR">Moderate</option>
                        <option value="Severe MR">Severe</option>
                    </select>
                </div>
                <div>
                    <label class="wm-label">Vena Contracta — Quick Select</label>
                    <select id="mr_vena_contracta_sel" class="form-control ep-sel">
                        <option value="">-- select --</option>
                        <optgroup label="✓ Mild MR (&lt;0.3 cm)">
                            <option value="0.2">0.2 cm</option>
                            <option value="0.25">0.25 cm</option>
                        </optgroup>
                        <optgroup label="↑ Moderate MR (0.3-0.69 cm)">
                            <option value="0.4">0.4 cm</option>
                            <option value="0.5">0.5 cm</option>
                            <option value="0.6">0.6 cm</option>
                        </optgroup>
                        <optgroup label="↑↑ Severe MR (&ge;0.7 cm)">
                            <option value="0.7">0.7 cm</option>
                            <option value="0.8">0.8 cm</option>
                            <option value="0.9">0.9 cm</option>
                        </optgroup>
                    </select>
                </div>
                <div>
                    <label class="wm-label">Vena Contracta value</label>
                    <input type="text" name="mr_vena_contracta" id="mr_vena_contracta" class="form-control ep-inp" value="{{ $v('mr_vena_contracta') }}" placeholder="cm">
                </div>
                <div>
                    <label class="wm-label">Regurgitant Volume — Quick Select</label>
                    <select id="mr_regurgitant_vol_sel" class="form-control ep-sel">
                        <option value="">-- select --</option>
                        <optgroup label="✓ Mild MR (&lt;30 mL)">
                            <option value="15">15 mL</option>
                            <option value="20">20 mL</option>
                            <option value="25">25 mL</option>
                        </optgroup>
                        <optgroup label="↑ Moderate MR (30-59 mL)">
                            <option value="35">35 mL</option>
                            <option value="45">45 mL</option>
                            <option value="55">55 mL</option>
                        </optgroup>
                        <optgroup label="↑↑ Severe MR (&ge;60 mL)">
                            <option value="65">65 mL</option>
                            <option value="75">75 mL</option>
                            <option value="90">90 mL</option>
                        </optgroup>
                    </select>
                </div>
                <div>
                    <label class="wm-label">Regurgitant Volume value</label>
                    <input type="text" name="mr_regurgitant_vol" id="mr_regurgitant_vol" class="form-control ep-inp" value="{{ $v('mr_regurgitant_vol') }}" placeholder="mL">
                </div>
            </div>
        </div>

        {{-- AS --}}
        <div id="lesion-panel-as" class="lesion-panel" style="display:none;border-top:1px dashed var(--sc-border);padding-top:10px;margin-top:10px">
            <div style="font-weight:700;font-size:11.5px;color:var(--sc-navy);margin-bottom:8px">Aortic Stenosis (AS)</div>
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:10px;align-items:end">
                <div>
                    <label class="wm-label">Severity</label>
                    <select id="av_as_severity" class="form-control ep-sel">
                        <option value="">None</option>
                        <option value="Mild AS">Mild</option>
                        <option value="Moderate AS">Moderate</option>
                        <option value="Severe AS">Severe</option>
                    </select>
                </div>
                <div>
                    <label class="wm-label">Etiology</label>
                    <select id="av_as_etiology" class="form-control ep-sel">
                        <option value="">-- none --</option>
                        <option value="calcific">Calcific</option>
                        <option value="bicuspid">Bicuspid</option>
                        <option value="rheumatic">Rheumatic</option>
                    </select>
                </div>
                <div>
                    <label class="wm-label">AVA (cm²) — Quick Select</label>
                    <select id="ava_sel" class="form-control ep-sel">
                        <option value="">-- select --</option>
                        <optgroup label="↑↑↑ Severe AS (&lt;1.0 cm²)">
                            <option value="0.6">0.6 cm²</option>
                            <option value="0.8">0.8 cm²</option>
                            <option value="0.9">0.9 cm²</option>
                        </optgroup>
                        <optgroup label="↑↑ Moderate AS (1.0-1.5 cm²)">
                            <option value="1.0">1.0 cm²</option>
                            <option value="1.2">1.2 cm²</option>
                            <option value="1.4">1.4 cm²</option>
                        </optgroup>
                        <optgroup label="↑ Mild AS (1.6-2.0 cm²)">
                            <option value="1.6">1.6 cm²</option>
                            <option value="1.8">1.8 cm²</option>
                            <option value="2.0">2.0 cm²</option>
                        </optgroup>
                    </select>
                </div>
                <div>
                    <label class="wm-label">AVA value</label>
                    <input type="text" name="ava" id="ava" class="form-control ep-inp" value="{{ $v('ava') }}" placeholder="cm²">
                </div>
            </div>
        </div>

        {{-- AR --}}
        <div id="lesion-panel-ar" class="lesion-panel" style="display:none;border-top:1px dashed var(--sc-border);padding-top:10px;margin-top:10px">
            <div style="font-weight:700;font-size:11.5px;color:var(--sc-navy);margin-bottom:8px">Aortic Regurgitation (AR)</div>
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr 1fr;gap:10px;align-items:end">
                <div>
                    <label class="wm-label">Severity</label>
                    <select id="av_ar_severity" class="form-control ep-sel">
                        <option value="">None</option>
                        <option value="Mild AR">Mild</option>
                        <option value="Moderate AR">Moderate</option>
                        <option value="Severe AR">Severe</option>
                    </select>
                </div>
                <div>
                    <label class="wm-label">Vena Contracta — Quick Select</label>
                    <select id="ar_vena_contracta_sel" class="form-control ep-sel">
                        <option value="">-- select --</option>
                        <optgroup label="✓ Mild AR (&lt;0.3 cm)">
                            <option value="0.2">0.2 cm</option>
                            <option value="0.25">0.25 cm</option>
                        </optgroup>
                        <optgroup label="↑ Moderate AR (0.3-0.59 cm)">
                            <option value="0.3">0.3 cm</option>
                            <option value="0.4">0.4 cm</option>
                            <option value="0.5">0.5 cm</option>
                        </optgroup>
                        <optgroup label="↑↑ Severe AR (&ge;0.6 cm)">
                            <option value="0.6">0.6 cm</option>
                            <option value="0.7">0.7 cm</option>
                            <option value="0.8">0.8 cm</option>
                        </optgroup>
                    </select>
                </div>
                <div>
                    <label class="wm-label">Vena Contracta value</label>
                    <input type="text" name="ar_vena_contracta" id="ar_vena_contracta" class="form-control ep-inp" value="{{ $v('ar_vena_contracta') }}" placeholder="cm">
                </div>
                <div>
                    <label class="wm-label">Regurgitant Volume — Quick Select</label>
                    <select id="ar_regurgitant_vol_sel" class="form-control ep-sel">
                        <option value="">-- select --</option>
                        <optgroup label="✓ Mild AR (&lt;30 mL)">
                            <option value="15">15 mL</option>
                            <option value="20">20 mL</option>
                            <option value="25">25 mL</option>
                        </optgroup>
                        <optgroup label="↑ Moderate AR (30-59 mL)">
                            <option value="35">35 mL</option>
                            <option value="45">45 mL</option>
                            <option value="55">55 mL</option>
                        </optgroup>
                        <optgroup label="↑↑ Severe AR (&ge;60 mL)">
                            <option value="65">65 mL</option>
                            <option value="75">75 mL</option>
                            <option value="90">90 mL</option>
                        </optgroup>
                    </select>
                </div>
                <div>
                    <label class="wm-label">Regurgitant Volume value</label>
                    <input type="text" name="ar_regurgitant_vol" id="ar_regurgitant_vol" class="form-control ep-inp" value="{{ $v('ar_regurgitant_vol') }}" placeholder="mL">
                </div>
            </div>
        </div>

        {{-- TS --}}
        <div id="lesion-panel-ts" class="lesion-panel" style="display:none;border-top:1px dashed var(--sc-border);padding-top:10px;margin-top:10px">
            <div style="font-weight:700;font-size:11.5px;color:var(--sc-navy);margin-bottom:8px">Tricuspid Stenosis (TS)</div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;align-items:end">
                <div>
                    <label class="wm-label">Severity</label>
                    <select id="tv_ts_severity" class="form-control ep-sel">
                        <option value="">None</option>
                        <option value="Mild TS">Mild</option>
                        <option value="Moderate TS">Moderate</option>
                        <option value="Severe TS">Severe</option>
                    </select>
                </div>
                <div style="font-size:11px;color:var(--sc-muted);padding-bottom:6px">Sets TV Gradient in the Color Doppler table above to a representative value.</div>
            </div>
        </div>

        {{-- TR --}}
        <div id="lesion-panel-tr" class="lesion-panel" style="display:none;border-top:1px dashed var(--sc-border);padding-top:10px;margin-top:10px">
            <div style="font-weight:700;font-size:11.5px;color:var(--sc-navy);margin-bottom:8px">Tricuspid Regurgitation (TR)</div>
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr 1fr;gap:10px;align-items:end">
                <div>
                    <label class="wm-label">Severity</label>
                    <select id="tv_tr_severity" class="form-control ep-sel">
                        <option value="">None</option>
                        <option value="Mild TR">Mild</option>
                        <option value="Moderate TR">Moderate</option>
                        <option value="Severe TR">Severe</option>
                    </select>
                </div>
                <div>
                    <label class="wm-label">Vena Contracta — Quick Select</label>
                    <select id="tr_vena_contracta_sel" class="form-control ep-sel">
                        <option value="">-- select --</option>
                        <optgroup label="✓ Mild TR (&lt;0.3 cm)">
                            <option value="0.2">0.2 cm</option>
                            <option value="0.25">0.25 cm</option>
                        </optgroup>
                        <optgroup label="↑ Moderate TR (0.3-0.69 cm)">
                            <option value="0.4">0.4 cm</option>
                            <option value="0.5">0.5 cm</option>
                            <option value="0.6">0.6 cm</option>
                        </optgroup>
                        <optgroup label="↑↑ Severe TR (&ge;0.7 cm)">
                            <option value="0.7">0.7 cm</option>
                            <option value="0.8">0.8 cm</option>
                            <option value="0.9">0.9 cm</option>
                        </optgroup>
                    </select>
                </div>
                <div>
                    <label class="wm-label">Vena Contracta value</label>
                    <input type="text" name="tr_vena_contracta" id="tr_vena_contracta" class="form-control ep-inp" value="{{ $v('tr_vena_contracta') }}" placeholder="cm">
                </div>
                <div>
                    <label class="wm-label">Regurgitant Volume — Quick Select</label>
                    <select id="tr_regurgitant_vol_sel" class="form-control ep-sel">
                        <option value="">-- select --</option>
                        <optgroup label="✓ Mild TR (&lt;30 mL)">
                            <option value="15">15 mL</option>
                            <option value="20">20 mL</option>
                            <option value="25">25 mL</option>
                        </optgroup>
                        <optgroup label="↑ Moderate TR (30-44 mL)">
                            <option value="35">35 mL</option>
                            <option value="45">45 mL</option>
                        </optgroup>
                        <optgroup label="↑↑ Severe TR (&ge;45 mL)">
                            <option value="55">55 mL</option>
                            <option value="65">65 mL</option>
                            <option value="75">75 mL</option>
                        </optgroup>
                    </select>
                </div>
                <div>
                    <label class="wm-label">Regurgitant Volume value</label>
                    <input type="text" name="tr_regurgitant_vol" id="tr_regurgitant_vol" class="form-control ep-inp" value="{{ $v('tr_regurgitant_vol') }}" placeholder="mL">
                </div>
            </div>
        </div>

        {{-- PS --}}
        <div id="lesion-panel-ps" class="lesion-panel" style="display:none;border-top:1px dashed var(--sc-border);padding-top:10px;margin-top:10px">
            <div style="font-weight:700;font-size:11.5px;color:var(--sc-navy);margin-bottom:8px">Pulmonic Stenosis (PS)</div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;align-items:end">
                <div>
                    <label class="wm-label">Severity</label>
                    <select id="pv_ps_severity" class="form-control ep-sel">
                        <option value="">None</option>
                        <option value="Mild PS">Mild</option>
                        <option value="Moderate PS">Moderate</option>
                        <option value="Severe PS">Severe</option>
                    </select>
                </div>
                <div style="font-size:11px;color:var(--sc-muted);padding-bottom:6px">Sets PV Gradient in the Color Doppler table above to a representative value.</div>
            </div>
        </div>

        {{-- PR --}}
        <div id="lesion-panel-pr" class="lesion-panel" style="display:none;border-top:1px dashed var(--sc-border);padding-top:10px;margin-top:10px">
            <div style="font-weight:700;font-size:11.5px;color:var(--sc-navy);margin-bottom:8px">Pulmonic Regurgitation (PR)</div>
            <div style="display:grid;grid-template-columns:1fr;gap:10px;align-items:end;max-width:220px">
                <div>
                    <label class="wm-label">Severity</label>
                    <select id="pv_pr_severity" class="form-control ep-sel">
                        <option value="">None</option>
                        <option value="Mild PR">Mild</option>
                        <option value="Moderate PR">Moderate</option>
                        <option value="Severe PR">Severe</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- PAH --}}
        <div id="lesion-panel-pah" class="lesion-panel" style="display:none;border-top:1px dashed var(--sc-border);padding-top:10px;margin-top:10px">
            <div style="font-weight:700;font-size:11.5px;color:var(--sc-navy);margin-bottom:8px">Pulmonary Arterial Hypertension (PAH)</div>
            <div style="display:grid;grid-template-columns:1fr 2fr;gap:10px;align-items:end;max-width:520px">
                <div>
                    <label class="wm-label">Severity</label>
                    <select id="pah_severity" class="form-control ep-sel">
                        <option value="">None</option>
                        <option value="Mild">Mild (PAP 20-35 mmHg)</option>
                        <option value="Moderate">Moderate (PAP 36-50 mmHg)</option>
                        <option value="Severe">Severe (PAP &gt;50 mmHg)</option>
                    </select>
                </div>
                <div style="font-size:11px;color:var(--sc-muted);padding-bottom:6px">Sets the PAP field in the parameters table above to a representative value.</div>
            </div>
        </div>

        <div class="mt-3" style="background:rgba(10,180,180,.07);border:1px solid rgba(10,180,180,.2);border-radius:6px;padding:8px 12px;font-size:11.5px;color:#065e5e;display:flex;flex-wrap:wrap;gap:14px">
            <span><i class="fa-solid fa-wand-magic-sparkles" style="margin-right:4px"></i>MV: <strong id="mv-compose-preview">—</strong></span>
            <span>AV: <strong id="av-compose-preview">—</strong></span>
            <span>TV: <strong id="tv-compose-preview">—</strong></span>
            <span>PV: <strong id="pv-compose-preview">—</strong></span>
            <span>PAP: <strong id="pah-compose-preview">—</strong></span>
        </div>
    </div>
</div>

{{-- ===== IMPRESSION & RECOMMENDATIONS ===== --}}
<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="fa-solid fa-file-lines me-2"></i>Comments / Impression &amp; Recommendations</span>
        <button type="button" class="btn btn-sm btn-primary" onclick="autoImpression(true)">
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
// ── RWMA / Acute MI Lesion Builder ──────────────────────────────────────────────
const RWMA_SEGMENT_KEYS = ['anterior_motion','anteroseptal_motion','inferior_motion','inferoseptal_motion','lateral_motion','posterior_motion','apex_motion'];

function composeLaClot() {
    const loc  = gv('la_clot_location');
    const size = gv('la_clot_size');
    const parts = [size ? `${size} cm` : null, loc || null].filter(Boolean);
    sv('la_clot', parts.join(', '));
    if (typeof scheduleAutoImpression === 'function') scheduleAutoImpression();
}

function composeLvClot() {
    const loc  = gv('lv_clot_location');
    const mob  = gv('lv_clot_mobility');
    const size = gv('lv_clot_size');
    const parts = [size ? `${size} cm` : null, loc || null, mob || null].filter(Boolean);
    sv('lv_clot', parts.join(', '));
    if (typeof scheduleAutoImpression === 'function') scheduleAutoImpression();
}

function wireRwmaGrading() {
    RWMA_SEGMENT_KEYS.forEach(key => {
        const el = document.getElementById(key);
        if (el) el.addEventListener('change', function () { if (typeof scheduleAutoImpression === 'function') scheduleAutoImpression(); });
    });
    ['la_clot_location', 'la_clot_size'].forEach(id => {
        const el = document.getElementById(id);
        if (el) { el.addEventListener('change', composeLaClot); el.addEventListener('input', composeLaClot); }
    });
    const laSizeSel = document.getElementById('la_clot_size_sel');
    if (laSizeSel) laSizeSel.addEventListener('change', function () { if (this.value) { sv('la_clot_size', this.value); composeLaClot(); } });

    ['lv_clot_location', 'lv_clot_mobility', 'lv_clot_size'].forEach(id => {
        const el = document.getElementById(id);
        if (el) { el.addEventListener('change', composeLvClot); el.addEventListener('input', composeLvClot); }
    });
    const lvSizeSel = document.getElementById('lv_clot_size_sel');
    if (lvSizeSel) lvSizeSel.addEventListener('change', function () { if (this.value) { sv('lv_clot_size', this.value); composeLvClot(); } });
}

// Show/hide each RWMA segment + LA/LV clot panel per its checkbox; reset (and
// recompose back out) anything that gets hidden — same pattern as applyLesionSelection().
function applyRwmaSelection() {
    RWMA_SEGMENT_KEYS.forEach(key => {
        const cb = document.getElementById('rwma_cb_' + key);
        const panel = document.getElementById('rwma-panel-' + key);
        if (!cb || !panel) return;
        if (cb.checked) { panel.style.display = ''; }
        else { panel.style.display = 'none'; sv(key, ''); }
    });
    const laCb = document.getElementById('rwma_cb_la_clot');
    const laPanel = document.getElementById('rwma-panel-la_clot');
    if (laCb && laPanel) {
        if (laCb.checked) { laPanel.style.display = ''; }
        else { laPanel.style.display = 'none'; sv('la_clot_location', ''); sv('la_clot_size', ''); sv('la_clot', ''); }
    }
    const lvCb = document.getElementById('rwma_cb_lv_clot');
    const lvPanel = document.getElementById('rwma-panel-lv_clot');
    if (lvCb && lvPanel) {
        if (lvCb.checked) { lvPanel.style.display = ''; }
        else { lvPanel.style.display = 'none'; sv('lv_clot_location', ''); sv('lv_clot_mobility', ''); sv('lv_clot_size', ''); sv('lv_clot', ''); }
    }
    if (typeof scheduleAutoImpression === 'function') scheduleAutoImpression();
}

// Best-effort pre-check/reveal from existing field values (edit mode, or right
// after a template is applied).
function syncRwmaFromExisting() {
    RWMA_SEGMENT_KEYS.forEach(key => {
        const has = !!gv(key);
        const cb = document.getElementById('rwma_cb_' + key);
        const panel = document.getElementById('rwma-panel-' + key);
        if (cb) cb.checked = has;
        if (panel) panel.style.display = has ? '' : 'none';
    });
    const hasLa = !!gv('la_clot');
    const laCb = document.getElementById('rwma_cb_la_clot');
    const laPanel = document.getElementById('rwma-panel-la_clot');
    if (laCb) laCb.checked = hasLa;
    if (laPanel) laPanel.style.display = hasLa ? '' : 'none';
    const hasLv = !!gv('lv_clot');
    const lvCb = document.getElementById('rwma_cb_lv_clot');
    const lvPanel = document.getElementById('rwma-panel-lv_clot');
    if (lvCb) lvCb.checked = hasLv;
    if (lvPanel) lvPanel.style.display = hasLv ? '' : 'none';
}

// "All Normal" button: uncheck + clear every segment and LA/LV clot.
function setAllRwmaNormal() {
    RWMA_SEGMENT_KEYS.forEach(key => {
        const cb = document.getElementById('rwma_cb_' + key);
        if (cb) cb.checked = false;
    });
    const laCb = document.getElementById('rwma_cb_la_clot'); if (laCb) laCb.checked = false;
    const lvCb = document.getElementById('rwma_cb_lv_clot'); if (lvCb) lvCb.checked = false;
    applyRwmaSelection();
}

// ── Quick-fill helper ──────────────────────────────────────────────────────────
// NOTE: setting .value from JS does not fire a native 'input' event, so the
// auto-impression watchers (which listen for 'input') would otherwise never see
// a quick-select pick — the impression/conclusion would silently keep showing
// stale (often "Normal") text even after selecting e.g. Severe MS. Explicitly
// schedule regeneration here so quick-select picks behave like typed input.
function fp(id, sel) {
    const val = typeof sel === 'string' ? sel : sel.value;
    if (!val) return;
    const el = document.getElementById(id);
    if (el) el.value = val;
    if (typeof scheduleAutoImpression === 'function') scheduleAutoImpression();
}

// ── RHD / Multivalvular Lesion Builder ──────────────────────────────────────
// Each compose* function builds the single free-text valve field (mitral_valve,
// aortic_valve, tricuspid_valve, pulmonary_valve) that the rest of the report
// (impression, conclusion, templates, PDF) reads from, out of the structured
// severity + quantitative-criteria controls in the Lesion Builder panels.
// applyLesionSelection() shows/hides each lesion's panel per its checkbox and
// resets+recomposes anything that gets hidden. The sync* functions do the
// reverse: given the free-text field (edit mode, or right after a template is
// applied), they pre-select severities and re-check/reveal the right panels.
function gv(id) { const el = document.getElementById(id); return el ? el.value.trim() : ''; }
function sv(id, val) { const el = document.getElementById(id); if (el) el.value = val; }
function gsev(seg) { return /\bsevere\b/.test(seg) ? 'Severe' : /\bmoderate\b/.test(seg) ? 'Moderate' : /\bmild\b/.test(seg) ? 'Mild' : ''; }

function composeMitralValve() {
    const msSev = gv('mv_ms_severity');
    const rheum = document.getElementById('mv_rheumatic')?.checked || false;
    const mva   = gv('mva');
    const mrSev = gv('mv_mr_severity');
    const vc    = gv('mr_vena_contracta');
    const rvol  = gv('mr_regurgitant_vol');

    let msPart = '';
    if (msSev) {
        // "Severe MS" -> "Severe Rheumatic MS" (etiology adjective before the noun, severity kept first)
        msPart = rheum ? msSev.replace(' MS', ' Rheumatic MS') : msSev;
        if (mva) msPart += ` (MVA ${mva} cm²)`;
    }
    let mrPart = '';
    if (mrSev) {
        const detail = [vc ? `vena contracta ${vc} cm` : null, rvol ? `regurgitant volume ${rvol} mL` : null].filter(Boolean).join(', ');
        mrPart = mrSev + (detail ? ` (${detail})` : '');
    }
    // Lowercase just the severity word (not "MR" itself) when MR trails an MS phrase, e.g.
    // "Severe MS with moderate MR" instead of "...with Moderate MR" / "...with moderate mr".
    let composed;
    if (msPart && mrPart) composed = `${msPart} with ${mrPart.replace(/^(Mild|Moderate|Severe)(?= MR)/, m => m.toLowerCase())}`;
    else if (msPart)      composed = msPart;
    else if (mrPart)      composed = mrPart;
    else                   composed = 'Normal - no MS, no MR';

    sv('mitral_valve', composed);
    const preview = document.getElementById('mv-compose-preview'); if (preview) preview.textContent = composed;
    if (typeof scheduleAutoImpression === 'function') scheduleAutoImpression();
}

function composeAorticValve() {
    const asSev = gv('av_as_severity');
    const etio  = document.getElementById('av_as_etiology')?.value || '';
    const ava   = gv('ava');
    const arSev = gv('av_ar_severity');
    const vc    = gv('ar_vena_contracta');
    const rvol  = gv('ar_regurgitant_vol');

    let asPart = '';
    if (asSev) {
        const detail = [etio || null, ava ? `AVA ${ava} cm²` : null].filter(Boolean).join(', ');
        asPart = asSev + (detail ? ` (${detail})` : '');
    }
    let arPart = '';
    if (arSev) {
        const detail = [vc ? `vena contracta ${vc} cm` : null, rvol ? `regurgitant volume ${rvol} mL` : null].filter(Boolean).join(', ');
        arPart = arSev + (detail ? ` (${detail})` : '');
    }
    let composed;
    if (asPart && arPart) composed = `${asPart} with ${arPart.replace(/^(Mild|Moderate|Severe)(?= AR)/, m => m.toLowerCase())}`;
    else if (asPart)      composed = asPart;
    else if (arPart)      composed = arPart;
    else                   composed = 'Normal - no AS, no AR';

    sv('aortic_valve', composed);
    const preview = document.getElementById('av-compose-preview'); if (preview) preview.textContent = composed;
    if (typeof scheduleAutoImpression === 'function') scheduleAutoImpression();
}

function composeTricuspidValve() {
    const tsSev = gv('tv_ts_severity');
    const trSev = gv('tv_tr_severity');
    const vc    = gv('tr_vena_contracta');
    const rvol  = gv('tr_regurgitant_vol');

    const tsPart = tsSev;
    let trPart = '';
    if (trSev) {
        const detail = [vc ? `vena contracta ${vc} cm` : null, rvol ? `regurgitant volume ${rvol} mL` : null].filter(Boolean).join(', ');
        trPart = trSev + (detail ? ` (${detail})` : '');
    }
    let composed;
    if (tsPart && trPart) composed = `${tsPart} with ${trPart.replace(/^(Mild|Moderate|Severe)(?= TR)/, m => m.toLowerCase())}`;
    else if (tsPart)      composed = tsPart;
    else if (trPart)      composed = trPart;
    else                   composed = 'Normal - no TR';

    sv('tricuspid_valve', composed);
    const preview = document.getElementById('tv-compose-preview'); if (preview) preview.textContent = composed;
    if (typeof scheduleAutoImpression === 'function') scheduleAutoImpression();
}

function composePulmonaryValve() {
    const psSev = gv('pv_ps_severity');
    const prSev = gv('pv_pr_severity');
    let composed;
    if (psSev && prSev) composed = `${psSev} with ${prSev.replace(/^(Mild|Moderate|Severe)(?= PR)/, m => m.toLowerCase())}`;
    else if (psSev)      composed = psSev;
    else if (prSev)      composed = prSev;
    else                  composed = 'Normal - no PR';

    sv('pulmonary_valve', composed);
    const preview = document.getElementById('pv-compose-preview'); if (preview) preview.textContent = composed;
    if (typeof scheduleAutoImpression === 'function') scheduleAutoImpression();
}

function applyPahSeverity() {
    const sev = gv('pah_severity');
    const map = { Mild: '28', Moderate: '50', Severe: '70' };
    const preview = document.getElementById('pah-compose-preview');
    if (sev && map[sev]) {
        sv('pap', map[sev]);
        if (preview) preview.textContent = `${map[sev]} mmHg (${sev} PAH)`;
    } else if (preview) {
        preview.textContent = gv('pap') ? `${gv('pap')} mmHg` : '—';
    }
    if (typeof scheduleAutoImpression === 'function') scheduleAutoImpression();
}

// Representative mid-band default for each severity grade, per criterion — auto-filled
// the moment a severity is picked so the report has sensible numbers immediately.
// Every value stays a plain editable text input, so these are just a starting point.
const LESION_DEFAULTS = {
    mva:               { 'Mild MS': '2.0', 'Moderate MS': '1.2', 'Severe MS': '0.8' },
    mr_vena_contracta: { 'Mild MR': '0.25', 'Moderate MR': '0.5', 'Severe MR': '0.8' },
    mr_regurgitant_vol:{ 'Mild MR': '20',   'Moderate MR': '45',  'Severe MR': '75'  },
    ava:               { 'Mild AS': '1.8', 'Moderate AS': '1.2', 'Severe AS': '0.8' },
    ar_vena_contracta: { 'Mild AR': '0.25', 'Moderate AR': '0.4', 'Severe AR': '0.7' },
    ar_regurgitant_vol:{ 'Mild AR': '20',   'Moderate AR': '45',  'Severe AR': '75'  },
    tr_vena_contracta: { 'Mild TR': '0.25', 'Moderate TR': '0.5', 'Severe TR': '0.8' },
    tr_regurgitant_vol:{ 'Mild TR': '20',   'Moderate TR': '40',  'Severe TR': '65'  },
    tv_gradient:       { 'Mild TS': '4',   'Moderate TS': '7',   'Severe TS': '10'  },
    pv_gradient:       { 'Mild PS': '28',  'Moderate PS': '50',  'Severe PS': '70'  },
};
// Auto-fill one or more fields with their severity-band default, then re-run compose.
function applyLesionDefaults(severityId, fieldIds, composeFn) {
    const sel = document.getElementById(severityId);
    if (!sel) return;
    sel.addEventListener('change', function () {
        fieldIds.forEach(fid => { if (LESION_DEFAULTS[fid]?.[this.value]) sv(fid, LESION_DEFAULTS[fid][this.value]); });
        if (composeFn) composeFn();
    });
}

function wireMitralGrading() {
    ['mv_ms_severity', 'mv_rheumatic', 'mva', 'mv_mr_severity', 'mr_vena_contracta', 'mr_regurgitant_vol'].forEach(id => {
        const el = document.getElementById(id);
        if (!el) return;
        el.addEventListener('change', composeMitralValve);
        el.addEventListener('input', composeMitralValve);
    });
    applyLesionDefaults('mv_ms_severity', ['mva'], composeMitralValve);
    applyLesionDefaults('mv_mr_severity', ['mr_vena_contracta', 'mr_regurgitant_vol'], composeMitralValve);
    const mvaSel = document.getElementById('mva_sel');
    if (mvaSel) mvaSel.addEventListener('change', function () { if (this.value) { sv('mva', this.value); composeMitralValve(); } });
    const vcSel = document.getElementById('mr_vena_contracta_sel');
    if (vcSel) vcSel.addEventListener('change', function () { if (this.value) { sv('mr_vena_contracta', this.value); composeMitralValve(); } });
    const rvolSel = document.getElementById('mr_regurgitant_vol_sel');
    if (rvolSel) rvolSel.addEventListener('change', function () { if (this.value) { sv('mr_regurgitant_vol', this.value); composeMitralValve(); } });
}

function wireAorticGrading() {
    ['av_as_severity', 'av_as_etiology', 'ava', 'av_ar_severity', 'ar_vena_contracta', 'ar_regurgitant_vol'].forEach(id => {
        const el = document.getElementById(id);
        if (!el) return;
        el.addEventListener('change', composeAorticValve);
        el.addEventListener('input', composeAorticValve);
    });
    applyLesionDefaults('av_as_severity', ['ava'], composeAorticValve);
    applyLesionDefaults('av_ar_severity', ['ar_vena_contracta', 'ar_regurgitant_vol'], composeAorticValve);
    const avaSel = document.getElementById('ava_sel');
    if (avaSel) avaSel.addEventListener('change', function () { if (this.value) { sv('ava', this.value); composeAorticValve(); } });
    const vcSel = document.getElementById('ar_vena_contracta_sel');
    if (vcSel) vcSel.addEventListener('change', function () { if (this.value) { sv('ar_vena_contracta', this.value); composeAorticValve(); } });
    const rvolSel = document.getElementById('ar_regurgitant_vol_sel');
    if (rvolSel) rvolSel.addEventListener('change', function () { if (this.value) { sv('ar_regurgitant_vol', this.value); composeAorticValve(); } });
}

function wireTricuspidGrading() {
    ['tv_ts_severity', 'tv_tr_severity', 'tr_vena_contracta', 'tr_regurgitant_vol'].forEach(id => {
        const el = document.getElementById(id);
        if (!el) return;
        el.addEventListener('change', composeTricuspidValve);
        el.addEventListener('input', composeTricuspidValve);
    });
    // TS severity has no dedicated quantitative field of its own — nudge the existing
    // TV Gradient quick-pick in the Color Doppler table to a representative value.
    applyLesionDefaults('tv_ts_severity', ['tv_gradient'], null);
    applyLesionDefaults('tv_tr_severity', ['tr_vena_contracta', 'tr_regurgitant_vol'], composeTricuspidValve);
    const vcSel = document.getElementById('tr_vena_contracta_sel');
    if (vcSel) vcSel.addEventListener('change', function () { if (this.value) { sv('tr_vena_contracta', this.value); composeTricuspidValve(); } });
    const rvolSel = document.getElementById('tr_regurgitant_vol_sel');
    if (rvolSel) rvolSel.addEventListener('change', function () { if (this.value) { sv('tr_regurgitant_vol', this.value); composeTricuspidValve(); } });
}

function wirePulmonaryGrading() {
    ['pv_ps_severity', 'pv_pr_severity'].forEach(id => {
        const el = document.getElementById(id);
        if (!el) return;
        el.addEventListener('change', composePulmonaryValve);
    });
    // PS severity has no dedicated quantitative field of its own — nudge the existing
    // PV Gradient quick-pick in the Color Doppler table to a representative value.
    applyLesionDefaults('pv_ps_severity', ['pv_gradient'], null);
}

function wirePahGrading() {
    const sel = document.getElementById('pah_severity');
    if (sel) sel.addEventListener('change', applyPahSeverity);
}

// Best-effort pre-select severity + etiology from an existing free-text valve
// value (edit mode, or right after a template is applied) so the Lesion
// Builder panels reflect what's actually in the field, checkboxes included.
// Quantitative sub-fields (MVA/AVA/vena contracta/regurgitant volume) aren't
// re-derived here — they're real stored fields already filled directly from
// $v()/the template.
function syncComboFromText(fieldId, tag1, tag2, sel1Id, sel2Id, cb1Id, cb2Id, panel1Id, panel2Id, previewId) {
    const raw = gv(fieldId);
    const lower = raw.toLowerCase();
    const withIdx = lower.indexOf(' with ');
    const seg1 = withIdx >= 0 ? lower.slice(0, withIdx) : lower;
    const seg2 = withIdx >= 0 ? lower.slice(withIdx) : lower;
    const re1 = new RegExp('\\b' + tag1 + '\\b');
    const re2 = new RegExp('\\b' + tag2 + '\\b');
    const sev1 = raw && re1.test(seg1) ? gsev(seg1) : '';
    const sev2 = raw && re2.test(seg2) ? gsev(seg2) : '';
    if (sel1Id) sv(sel1Id, sev1 ? sev1 + ' ' + tag1.toUpperCase() : '');
    if (sel2Id) sv(sel2Id, sev2 ? sev2 + ' ' + tag2.toUpperCase() : '');
    const cb1 = document.getElementById(cb1Id); if (cb1) cb1.checked = !!sev1;
    const cb2 = document.getElementById(cb2Id); if (cb2) cb2.checked = !!sev2;
    const p1 = document.getElementById(panel1Id); if (p1) p1.style.display = sev1 ? '' : 'none';
    const p2 = document.getElementById(panel2Id); if (p2) p2.style.display = sev2 ? '' : 'none';
    const preview = document.getElementById(previewId); if (preview) preview.textContent = raw || '—';
    return { sev1, sev2 };
}

function syncMitralGradingFromText() {
    const { sev1: msSev } = syncComboFromText('mitral_valve', 'ms', 'mr', 'mv_ms_severity', 'mv_mr_severity', 'lesion_ms', 'lesion_mr', 'lesion-panel-ms', 'lesion-panel-mr', 'mv-compose-preview');
    const rheumCb = document.getElementById('mv_rheumatic');
    if (rheumCb) rheumCb.checked = msSev ? /rheumatic/.test(gv('mitral_valve').toLowerCase()) : false;
}

function syncAorticGradingFromText() {
    const raw = gv('aortic_valve').toLowerCase();
    const { sev1: asSev } = syncComboFromText('aortic_valve', 'as', 'ar', 'av_as_severity', 'av_ar_severity', 'lesion_as', 'lesion_ar', 'lesion-panel-as', 'lesion-panel-ar', 'av-compose-preview');
    const etioSel = document.getElementById('av_as_etiology');
    if (etioSel) etioSel.value = !asSev ? '' : /calcific/.test(raw) ? 'calcific' : /bicuspid/.test(raw) ? 'bicuspid' : /rheumatic/.test(raw) ? 'rheumatic' : '';
}

function syncTricuspidGradingFromText() {
    syncComboFromText('tricuspid_valve', 'ts', 'tr', 'tv_ts_severity', 'tv_tr_severity', 'lesion_ts', 'lesion_tr', 'lesion-panel-ts', 'lesion-panel-tr', 'tv-compose-preview');
}

function syncPulmonaryGradingFromText() {
    syncComboFromText('pulmonary_valve', 'ps', 'pr', 'pv_ps_severity', 'pv_pr_severity', 'lesion_ps', 'lesion_pr', 'lesion-panel-ps', 'lesion-panel-pr', 'pv-compose-preview');
}

function syncPahGradingFromText() {
    const pap = parseFloat(gv('pap'));
    const sel = document.getElementById('pah_severity');
    const cb = document.getElementById('lesion_pah');
    const panel = document.getElementById('lesion-panel-pah');
    const preview = document.getElementById('pah-compose-preview');
    const has = !isNaN(pap) && pap > 20;
    const sev = has ? (pap > 50 ? 'Severe' : pap > 35 ? 'Moderate' : 'Mild') : '';
    if (sel) sel.value = sev;
    if (cb) cb.checked = has;
    if (panel) panel.style.display = has ? '' : 'none';
    if (preview) preview.textContent = !isNaN(pap) ? `${pap} mmHg` : '—';
}

function syncAllLesionGradingFromText() {
    syncMitralGradingFromText();
    syncAorticGradingFromText();
    syncTricuspidGradingFromText();
    syncPulmonaryGradingFromText();
    syncPahGradingFromText();
}

// Checkbox-driven show/hide: reveals the grading panel for every checked
// lesion, hides (and resets) the rest, then recomposes all four valve fields
// + PAP so unchecking a lesion actually clears it back out of the report.
function applyLesionSelection() {
    const lesions = {
        ms:  { reset: () => { sv('mv_ms_severity', ''); const cb = document.getElementById('mv_rheumatic'); if (cb) cb.checked = false; sv('mva', ''); } },
        mr:  { reset: () => { sv('mv_mr_severity', ''); sv('mr_vena_contracta', ''); sv('mr_regurgitant_vol', ''); } },
        as:  { reset: () => { sv('av_as_severity', ''); sv('av_as_etiology', ''); sv('ava', ''); } },
        ar:  { reset: () => { sv('av_ar_severity', ''); sv('ar_vena_contracta', ''); sv('ar_regurgitant_vol', ''); } },
        ts:  { reset: () => { sv('tv_ts_severity', ''); } },
        tr:  { reset: () => { sv('tv_tr_severity', ''); sv('tr_vena_contracta', ''); sv('tr_regurgitant_vol', ''); } },
        ps:  { reset: () => { sv('pv_ps_severity', ''); } },
        pr:  { reset: () => { sv('pv_pr_severity', ''); } },
        pah: { reset: () => { sv('pah_severity', ''); } },
    };
    Object.keys(lesions).forEach(k => {
        const cb = document.getElementById('lesion_' + k);
        const panel = document.getElementById('lesion-panel-' + k);
        if (!cb || !panel) return;
        if (cb.checked) { panel.style.display = ''; }
        else { panel.style.display = 'none'; lesions[k].reset(); }
    });
    composeMitralValve();
    composeAorticValve();
    composeTricuspidValve();
    composePulmonaryValve();
    applyPahSeverity();
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
    setV('av_velocity',    pick(['120','140','150','160','180']));
    setV('av_gradient',    pick(['5','8','10','12']));
    setV('tv_velocity',    pick(['70','80']));
    setV('tv_gradient',    pick(['1','2']));
    setV('pv_velocity',    pick(['80','90','100','110','120']));
    setV('pv_gradient',    pick(['5','8','10','12']));
    // Uncheck every Lesion Builder checkbox — this hides all 9 panels, resets their
    // fields, and recomposes mitral/aortic/tricuspid/pulmonary_valve back to "Normal".
    ['ms','mr','as','ar','ts','tr','ps','pr','pah'].forEach(k => {
        const cb = document.getElementById('lesion_' + k);
        if (cb) cb.checked = false;
    });
    if (typeof applyLesionSelection === 'function') applyLesionSelection();
}

// ── Auto-generate impression from entered values ───────────────────────────────
// scroll=true only for the explicit "Generate from Values" click — background
// regeneration (every checkbox/dropdown change, via scheduleAutoImpression) must
// NOT jump the page, or picking several RWMA/valve findings in a row keeps
// yanking focus down to the impression box after each one.
function autoImpression(scroll = false) {
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
    const _rwmaClear = ['anterior_motion','anteroseptal_motion','inferior_motion','inferoseptal_motion','lateral_motion','posterior_motion','apex_motion']
        .every(k => !str(k)) && !str('la_clot') && !str('lv_clot');
    const allNormal = (
        _rwmaClear &&
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
            '7. Mitral, Aortic, Tricuspid and Pulmonary valves are normal in morphology and function.\n' +
            '8. Interventricular septum and inter-atrial septum are intact.\n' +
            '9. No pericardial effusion.\n' +
            '10. Pulmonary artery pressure is within normal limits (PAP ' + _pap + ' mmHg).\n\n' +
            'CONCLUSION: Normal Echocardiogram and Color Doppler Study.';
        document.getElementById('f_recommendations').value =
            'Routine follow-up as clinically indicated.\nRepeat echo if symptoms develop.';
        if (scroll) document.getElementById('f_impression').scrollIntoView({ behavior: 'smooth', block: 'center' });
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
        findings.push(mv.toLowerCase().includes('normal') ? 'Mitral Valve: Normal in morphology and function' : `Mitral valve: ${mv}`);
        const mL = mv.toLowerCase();
        if      (mL.includes('severe ms') || mL.includes('rheumatic ms')) recs.push('Cardiology review. Wilkins score for PTMC candidacy. Penicillin prophylaxis for RHD. Anticoagulation if AF.');
        else if (mL.includes('severe mr') || mL.includes('flail'))        recs.push('Cardiac surgery/cardiology review for mitral valve repair or replacement. Serial echo 6-monthly.');
    }
    if (av) {
        findings.push(av.toLowerCase().includes('normal') ? 'Aortic Valve: Normal in morphology and function' : `Aortic valve: ${av}`);
        const aL = av.toLowerCase();
        if      (aL.includes('severe as')) recs.push('Urgent cardiac surgery/TAVI assessment. Avoid vigorous activity and vasodilators. Cardiology referral.');
        else if (aL.includes('severe ar')) recs.push('Cardiac surgery review for AVR. Vasodilator therapy (nifedipine/ACEi). Serial echo 6-monthly.');
    }
    if (tv) findings.push(tv.toLowerCase().includes('normal') ? 'Tricuspid Valve: Normal in morphology and function' : `Tricuspid valve: ${tv}`);
    if (pv) findings.push(pv.toLowerCase().includes('normal') ? 'Pulmonary Valve: Normal in morphology and function' : `Pulmonary valve: ${pv}`);

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

    // ─ RWMA (Acute / Old MI) ─
    const rwmaSegLabels = {
        anterior_motion: 'Anterior', anteroseptal_motion: 'Septal', inferior_motion: 'Inferior',
        inferoseptal_motion: 'Inferoseptal', lateral_motion: 'Lateral', posterior_motion: 'Posterior', apex_motion: 'Apical',
    };
    const abnormalSegs = Object.keys(rwmaSegLabels).map(k => ({ label: rwmaSegLabels[k], val: str(k) })).filter(s => s.val);
    if (abnormalSegs.length) {
        findings.push('Regional wall motion abnormality: ' + abnormalSegs.map(s => `${s.label} - ${s.val}`).join(', '));
        recs.push('Coronary angiography / ischemia evaluation for the territory-specific RWMA. Optimise antiplatelet and statin therapy.');
    }
    const laClot = str('la_clot');
    if (laClot) { findings.push(`LA clot: ${laClot}`); recs.push('Anticoagulation. Cardiology/CTVS referral for LA thrombus management.'); }
    const lvClot = str('lv_clot');
    if (lvClot) { findings.push(`LV clot: ${lvClot}`); recs.push('Anticoagulation per LV mural thrombus protocol. Avoid strenuous activity. Serial echo to monitor resolution.'); }

    // ─ CONCLUSION ─
    const lvhLabel  = (ivsTk && lvpwTk) ? (lvDil ? 'Eccentric LVH' : 'Concentric LVH') : (ivsTk ? 'Asymmetric septal hypertrophy' : '');
    const efLabel   = efNum !== null ? (efNum >= 55 ? `preserved EF (${efNum}%)` : `reduced EF (${efNum}%)`) : '';
    const diaLabel  = diastDesc || '';
    const pahLabel  = pap !== null && pap > 20 ? (pap > 50 ? 'severe pulmonary hypertension' : pap > 35 ? 'moderate pulmonary hypertension' : 'mild pulmonary hypertension') : '';

    // ─ VALVE SUMMARY (abbreviated, e.g. "RHD - MS with Mod MR, Mod AS with Mild AR, Mod TR, Mild PR") ─
    const abbrevValve = s => s.replace(/\bRheumatic\s+/i, '').replace(/\bModerate\b/gi, 'Mod');
    let valveParts = [];
    let rheumaticFlag = false;
    [mv, av, tv, pv].forEach(vStr => {
        if (!vStr || vStr.toLowerCase().includes('normal')) return;
        if (/rheumatic/i.test(vStr)) rheumaticFlag = true;
        valveParts.push(abbrevValve(vStr));
    });
    const valveLabel = valveParts.length ? (rheumaticFlag ? 'RHD - ' : '') + valveParts.join(', ') : '';

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
    if (valveLabel) concl += (concl ? '. ' : '') + valveLabel;
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
    if (scroll) document.getElementById('f_impression').scrollIntoView({ behavior: 'smooth', block: 'center' });
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
        tplSel.addEventListener('change', function () {
            updateEchoTemplateMeta();
            if (this.value) applyEchoTemplate();
        });
        updateEchoTemplateMeta();
    }

    // ── RHD / Multivalvular Lesion Builder wiring ──────────────────────────────
    wireMitralGrading();
    wireAorticGrading();
    wireTricuspidGrading();
    wirePulmonaryGrading();
    wirePahGrading();
    syncAllLesionGradingFromText();

    // ── RWMA / Acute MI Lesion Builder wiring ──────────────────────────────────
    wireRwmaGrading();
    syncRwmaFromExisting();
});

// ── Template library: keys we apply (must match EchoTemplate::FIELD_KEYS) ─────
const ECHO_TEMPLATE_KEYS = [
    'diagnosis','ref_by','height_cm','weight_kg','bsa',
    'rv_wall_thickness','rv_dimension','tapse','ivc_size',
    'ivs','lvpw','lv_edd','lv_esd','epss','mapse',
    'la_size','ao_root','ef_percent','fs_percent','ea_ratio','lap_ee','pap',
    'mitral_valve','mva','mr_vena_contracta','mr_regurgitant_vol',
    'aortic_valve','ava','ar_vena_contracta','ar_regurgitant_vol',
    'tricuspid_valve','tr_vena_contracta','tr_regurgitant_vol','pulmonary_valve',
    'lv_diastolic_function','lv_systolic_function','ivs_motion','pericardium_status',
    'rv_longitudinal_function','rv_transverse_function','rv_paradoxical_movement',
    'ias_status','pericardial_effusion',
    'anterior_motion','anteroseptal_motion','inferior_motion','inferoseptal_motion',
    'lateral_motion','posterior_motion','apex_motion','rwma_comments','la_clot','lv_clot',
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
    if (typeof syncAllLesionGradingFromText === 'function') syncAllLesionGradingFromText();
    if (typeof syncRwmaFromExisting === 'function') syncRwmaFromExisting();
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
        valvular:'Valvular', rhd:'Rheumatic Heart Disease (RHD)', rv_pah:'RV / Pulmonary HTN',
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
