@extends('layouts.app')
@section('title', 'Edit ECG')
@section('content')

<div class="page-header">
    <div class="page-header-left">
        <h1>Edit ECG &mdash; <span class="mono">{{ $ecg->record_no }}</span></h1>
        <div class="breadcrumb"><a href="{{ route('ecg.index') }}">ECG</a> &rsaquo; <a href="{{ route('ecg.show', $ecg) }}">{{ $ecg->record_no }}</a> &rsaquo; Edit</div>
    </div>
    <a href="{{ route('ecg.show', $ecg) }}" class="btn btn-outline"><i class="fa-solid fa-arrow-left"></i> Back to Report</a>
    @include('diagnostics._toolbar', ['inline'=>true])
</div>

<div class="patient-strip mb-4">
    <div class="ps-avatar">{{ strtoupper(substr($ecg->patient->first_name,0,1)) }}</div>
    <div>
        <div class="ps-name">{{ $ecg->patient->full_name }}</div>
        <div class="ps-jhcid">{{ $ecg->patient->jhc_id }}</div>
        <div class="ps-meta">
            <span><i class="fa-solid fa-calendar"></i> {{ $ecg->patient->age }} yrs</span>
            <span>{{ $ecg->patient->gender }}</span>
            <span><i class="fa-solid fa-wave-square"></i> {{ $ecg->record_date->format('d M Y') }}</span>
        </div>
    </div>
</div>

<form method="POST" action="{{ route('ecg.update', $ecg) }}" enctype="multipart/form-data">
@csrf @method('PUT')
<input type="hidden" name="patient_id" value="{{ $ecg->patient_id }}">

<div style="display:grid;grid-template-columns:1fr 280px;gap:20px;align-items:start">
<div>

    <div class="card mb-3">
        <div class="card-header"><i class="fa-solid fa-user-doctor"></i> Doctor & Date</div>
        <div class="card-body">
            <div class="form-row cols-3">
                <div class="form-group">
                    <label class="form-label">Doctor <span class="req">*</span></label>
                    <select name="doctor_id" class="form-control" required>
                        @foreach($doctors as $d)
                            <option value="{{ $d->id }}" {{ $ecg->doctor_id == $d->id ? 'selected' : '' }}>Dr. {{ $d->first_name }} {{ $d->last_name }}{{ $d->specialization ? ' - '.$d->specialization : '' }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Record Date <span class="req">*</span></label>
                    <input type="date" name="record_date" class="form-control" value="{{ $ecg->record_date->toDateString() }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Referred By</label>
                    <select id="ecg-ref-select" class="form-control" onchange="onEcgRefSelectChange(this)">
                        <option value="">— None / Self —</option>
                        @foreach($referralSources ?? [] as $rs)
                            <option value="{{ $rs->id }}" data-name="{{ $rs->name }}" {{ (old('referral_source_id', $ecg->referral_source_id) == $rs->id) || (!old('referral_source_id', $ecg->referral_source_id) && old('ref_by', $ecg->ref_by) === $rs->name) ? 'selected' : '' }}>{{ $rs->name }}</option>
                        @endforeach
                        <option value="__add__" style="font-style:italic;color:var(--sc-teal,#0ab4b4)">＋ Add new referring doctor…</option>
                    </select>
                    <input type="hidden" name="ref_by" id="ecg-ref-text" value="{{ old('ref_by', $ecg->ref_by) }}">
                    <input type="hidden" name="referral_source_id" id="ecg-ref-id" value="{{ old('referral_source_id', $ecg->referral_source_id) }}">
                </div>
            </div>
        </div>
    </div>
    <script>
    function onEcgRefSelectChange(sel) {
        if (sel.value === '__add__') {
            const name = (window.prompt('New referring doctor / dept name (e.g. Dr. R. Kumar, Apollo Hospital):') || '').trim();
            if (!name) { sel.value = ''; document.getElementById('ecg-ref-text').value = ''; document.getElementById('ecg-ref-id').value = ''; return; }
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
                  document.getElementById('ecg-ref-text').value = rs.name;
                  document.getElementById('ecg-ref-id').value = rs.id;
              })
              .catch(() => { alert('Could not save the new referring doctor. Please try again or use the Referrals page.'); sel.value=''; document.getElementById('ecg-ref-text').value=''; document.getElementById('ecg-ref-id').value=''; });
            return;
        }
        const opt = sel.options[sel.selectedIndex];
        document.getElementById('ecg-ref-text').value = opt?.dataset?.name || '';
        document.getElementById('ecg-ref-id').value = (sel.value === '__existing__' || sel.value === '') ? '' : sel.value;
    }
    (function() {
        const sel = document.getElementById('ecg-ref-select');
        const txt = document.getElementById('ecg-ref-text');
        if (sel && txt && txt.value && sel.value === '') {
            const opt = new Option(txt.value, '__existing__', true, true);
            opt.dataset.name = txt.value;
            sel.insertBefore(opt, sel.querySelector('option[value="__add__"]'));
        }
    })();
    </script>

    <div class="card mb-3">
        <div class="card-header">
            <i class="fa-solid fa-wave-square"></i> ECG Parameters
            <div class="card-actions">
                <button type="button" class="btn btn-sm btn-outline" onclick="applyNormal()"><i class="fa-solid fa-check-circle"></i> Fill Normal Values</button>
            </div>
        </div>
        <div class="card-body">
            <div id="ecg-flag" style="display:none;margin-bottom:14px;padding:10px 14px;border-radius:8px;font-size:13px;line-height:1.45"></div>
            <div class="form-row cols-3" style="margin-bottom:16px">
                <div class="form-group">
                    <label class="form-label">Heart Rate (bpm)</label>
                    <input type="text" name="heart_rate" id="f_hr" class="form-control" value="{{ $ecg->heart_rate }}" placeholder="e.g. 72">
                    <div style="font-size:11px;color:var(--sc-muted);margin-top:3px">Normal: 60-100 bpm</div>
                </div>
                <div class="form-group">
                    <label class="form-label">Rhythm</label>
                    <select name="rhythm" id="f_rhythm" class="form-control">
                        @foreach(['Sinus Rhythm','Sinus Tachycardia','Sinus Bradycardia','Atrial Fibrillation','Atrial Flutter','SVT','Ventricular Tachycardia','Ventricular Fibrillation','Junctional Rhythm','Paced Rhythm','Complete Heart Block (CHB)','2nd Degree AV Block (Mobitz I)','2nd Degree AV Block (Mobitz II)','1st Degree AV Block','LBBB','RBBB','LAHB','LPHB','Other'] as $r)
                            <option {{ $ecg->rhythm == $r ? 'selected' : '' }}>{{ $r }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Axis</label>
                    <select name="axis" id="f_axis" class="form-control">
                        @foreach(['Normal Axis (-30 to +90)','Left Axis Deviation (<-30)','Right Axis Deviation (>+90)','Extreme / Northwest Axis'] as $a)
                            <option {{ $ecg->axis == $a ? 'selected' : '' }}>{{ $a }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="form-row cols-3" style="margin-bottom:16px">
                <div class="form-group">
                    <label class="form-label">PR Interval</label>
                    <input type="text" name="pr_interval" id="f_pr" class="form-control" value="{{ $ecg->pr_interval }}" placeholder="e.g. 160 ms">
                    <div style="font-size:11px;color:var(--sc-muted);margin-top:3px">Normal: 120-200 ms</div>
                </div>
                <div class="form-group">
                    <label class="form-label">QRS Duration</label>
                    <input type="text" name="qrs_duration" id="f_qrs" class="form-control" value="{{ $ecg->qrs_duration }}" placeholder="e.g. 90 ms">
                    <div style="font-size:11px;color:var(--sc-muted);margin-top:3px">Normal: &lt;120 ms</div>
                </div>
                <div class="form-group">
                    <label class="form-label">QT / QTc</label>
                    <input type="text" name="qt_qtc" id="f_qt" class="form-control" value="{{ $ecg->qt_qtc }}" placeholder="e.g. 380/420 ms">
                    <div style="font-size:11px;color:var(--sc-muted);margin-top:3px">Normal QTc: &lt;440ms (M) / &lt;460ms (F)</div>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Interpretation / Findings</label>
                <div style="margin-bottom:10px;padding:12px 14px;background:var(--sc-bg);border-radius:8px;border:1.5px solid var(--sc-border)">
                    <div style="font-size:11px;font-weight:700;color:var(--sc-muted);letter-spacing:.5px;margin-bottom:6px">RHYTHM &amp; CONDUCTION</div>
                    <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:10px">
                        <button type="button" class="btn btn-sm btn-outline interp-btn" data-key="normal">Normal ECG</button>
                        <button type="button" class="btn btn-sm btn-outline interp-btn" data-key="af">Atrial Fibrillation</button>
                        <button type="button" class="btn btn-sm btn-outline interp-btn" data-key="aflutter">Atrial Flutter</button>
                        <button type="button" class="btn btn-sm btn-outline interp-btn" data-key="avb1">1st Degree AV Block</button>
                        <button type="button" class="btn btn-sm btn-outline interp-btn" data-key="avb2m1">2nd Degree AVB Mobitz I</button>
                        <button type="button" class="btn btn-sm btn-outline interp-btn" data-key="avb2m2">2nd Degree AVB Mobitz II</button>
                        <button type="button" class="btn btn-sm btn-outline interp-btn" data-key="chb">Complete Heart Block</button>
                        <button type="button" class="btn btn-sm btn-outline interp-btn" data-key="wpw">WPW Syndrome</button>
                    </div>
                    <div style="font-size:11px;font-weight:700;color:var(--sc-muted);letter-spacing:.5px;margin-bottom:6px">BUNDLE BRANCH &amp; FASCICULAR</div>
                    <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:10px">
                        <button type="button" class="btn btn-sm btn-outline interp-btn" data-key="lbbb">LBBB</button>
                        <button type="button" class="btn btn-sm btn-outline interp-btn" data-key="rbbb">RBBB</button>
                        <button type="button" class="btn btn-sm btn-outline interp-btn" data-key="lahb">LAHB</button>
                        <button type="button" class="btn btn-sm btn-outline interp-btn" data-key="lphb">LPHB</button>
                        <button type="button" class="btn btn-sm btn-outline interp-btn" data-key="bifascicular">Bifascicular Block (RBBB+LAHB)</button>
                    </div>
                    <div style="font-size:11px;font-weight:700;color:var(--sc-muted);letter-spacing:.5px;margin-bottom:6px">HYPERTROPHY</div>
                    <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:10px">
                        <button type="button" class="btn btn-sm btn-outline interp-btn" data-key="lvh">LVH</button>
                        <button type="button" class="btn btn-sm btn-outline interp-btn" data-key="rvh">RVH</button>
                        <button type="button" class="btn btn-sm btn-outline interp-btn" data-key="ppulmonale">P Pulmonale (RAE)</button>
                        <button type="button" class="btn btn-sm btn-outline interp-btn" data-key="pmitrale">P Mitrale (LAE)</button>
                    </div>
                    <div style="font-size:11px;font-weight:700;color:var(--sc-muted);letter-spacing:.5px;margin-bottom:6px">ISCHAEMIA / INFARCTION</div>
                    <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:10px">
                        <button type="button" class="btn btn-sm btn-outline interp-btn" data-key="stemi_inf">STEMI - Inferior</button>
                        <button type="button" class="btn btn-sm btn-outline interp-btn" data-key="stemi_ant">STEMI - Anterior</button>
                        <button type="button" class="btn btn-sm btn-outline interp-btn" data-key="stemi_lat">STEMI - Lateral</button>
                        <button type="button" class="btn btn-sm btn-outline interp-btn" data-key="nstemi">NSTEMI / Unstable Angina</button>
                        <button type="button" class="btn btn-sm btn-outline interp-btn" data-key="old_mi_inf">Old MI - Inferior</button>
                        <button type="button" class="btn btn-sm btn-outline interp-btn" data-key="old_mi_ant">Old MI - Anterior</button>
                    </div>
                    <div style="font-size:11px;font-weight:700;color:var(--sc-muted);letter-spacing:.5px;margin-bottom:6px">OTHER PATTERNS</div>
                    <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:10px">
                        <button type="button" class="btn btn-sm btn-outline interp-btn" data-key="qt_prolonged">Prolonged QTc</button>
                        <button type="button" class="btn btn-sm btn-outline interp-btn" data-key="early_repol">Early Repolarization</button>
                        <button type="button" class="btn btn-sm btn-outline interp-btn" data-key="pericarditis">Pericarditis Pattern</button>
                        <button type="button" class="btn btn-sm btn-outline interp-btn" data-key="pe">Pulmonary Embolism Pattern</button>
                        <button type="button" class="btn btn-sm btn-outline interp-btn" data-key="hyperkalaemia">Hyperkalaemia</button>
                        <button type="button" class="btn btn-sm btn-outline interp-btn" data-key="hypokalaemia">Hypokalaemia</button>
                        <button type="button" class="btn btn-sm btn-outline interp-btn" data-key="digoxin">Digoxin Effect</button>
                    </div>
                    <div style="display:flex;gap:8px;align-items:center;padding-top:8px;border-top:1px solid var(--sc-border)">
                        <button type="button" class="btn btn-sm btn-outline" onclick="clearInterp()" style="color:var(--sc-red);border-color:var(--sc-red)"><i class="fa-solid fa-xmark"></i> Clear All</button>
                        <span id="selected-count" style="font-size:12px;color:var(--sc-muted)">0 findings selected</span>
                    </div>
                </div>
                <textarea name="interpretation" id="f_interp" class="form-control" rows="8" placeholder="Select findings above or type directly...">{{ $ecg->interpretation }}</textarea>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header"><i class="fa-solid fa-file-image"></i> ECG Trace File (optional)</div>
        <div class="card-body">
            @if($ecg->ecg_file)
                <div style="margin-bottom:10px;padding:10px 14px;background:var(--sc-bg);border-radius:7px;font-size:13px;display:flex;align-items:center;gap:10px">
                    <i class="fa-solid fa-file" style="color:var(--sc-teal)"></i>
                    <span>{{ $ecg->ecg_file }}</span>
                    <a href="{{ asset('storage/'.$ecg->ecg_file) }}" target="_blank" class="btn btn-sm btn-outline">View</a>
                </div>
            @endif
            <input type="file" name="ecg_file" class="form-control" accept="image/*,.pdf,.bmp,.tiff">
            <div style="font-size:12px;color:var(--sc-muted);margin-top:6px">Upload new file to replace existing. Accepted: JPEG, PNG, PDF, BMP, TIFF — max 20 MB</div>
            <div style="margin-top:12px;display:flex;align-items:center;gap:10px;flex-wrap:wrap">
                <button type="button" id="ecg-read-btn" class="btn btn-sm" style="background:#0891b2;color:#fff;border-color:#0891b2" onclick="ecgReadFile()">
                    <i class="fa-solid fa-wand-magic-sparkles"></i> Read, analyze &amp; save
                </button>
                <span id="ecg-read-status" style="font-size:12px;color:var(--sc-muted)"></span>
            </div>
            <div style="font-size:11px;color:var(--sc-muted);margin-top:6px">Reads the uploaded ECG (or the saved file), fills the fields above and <b>saves the record</b>. Use Edit afterwards to change anything.</div>
        </div>
    </div>

</div>
<div>
    <div class="card" style="position:sticky;top:80px">
        <div class="card-body">
            <div class="form-group">
                <label class="form-label">Fee (&#8377;)</label>
                <input type="number" name="price" class="form-control" value="{{ $ecg->price }}" required step="0.01" min="0">
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;margin-bottom:8px">
                <i class="fa-solid fa-save"></i> Update ECG Record
            </button>
            <a href="{{ route('ecg.show', $ecg) }}" class="btn btn-outline" style="width:100%;justify-content:center">
                <i class="fa-solid fa-xmark"></i> Cancel
            </a>
        </div>
        <div class="card-footer" style="padding:14px">
            <div style="font-size:11px;font-weight:700;color:var(--sc-muted);letter-spacing:.5px;margin-bottom:10px">NORMAL REFERENCE</div>
            <table style="width:100%;border-collapse:collapse;font-size:12px">
                <tr style="border-bottom:1px solid var(--sc-border)"><td style="padding:5px 0">Heart Rate</td><td style="padding:5px 0;text-align:right;color:var(--sc-muted)">60-100 bpm</td></tr>
                <tr style="border-bottom:1px solid var(--sc-border)"><td style="padding:5px 0">PR Interval</td><td style="padding:5px 0;text-align:right;color:var(--sc-muted)">120-200 ms</td></tr>
                <tr style="border-bottom:1px solid var(--sc-border)"><td style="padding:5px 0">QRS Duration</td><td style="padding:5px 0;text-align:right;color:var(--sc-muted)">&lt;120 ms</td></tr>
                <tr style="border-bottom:1px solid var(--sc-border)"><td style="padding:5px 0">QTc (Male)</td><td style="padding:5px 0;text-align:right;color:var(--sc-muted)">&lt;440 ms</td></tr>
                <tr style="border-bottom:1px solid var(--sc-border)"><td style="padding:5px 0">QTc (Female)</td><td style="padding:5px 0;text-align:right;color:var(--sc-muted)">&lt;460 ms</td></tr>
                <tr><td style="padding:5px 0">Axis</td><td style="padding:5px 0;text-align:right;color:var(--sc-muted)">-30 to +90 deg</td></tr>
            </table>
        </div>
    </div>
</div>
</div>
</form>
@endsection

@push('scripts')
<script>
const interps = {
    normal:`Normal sinus rhythm. Rate: 72 bpm.\nPR interval: 160ms (normal). QRS duration: 90ms (normal). QTc: 420ms (normal).\nNormal axis. No ST-T wave changes. No chamber hypertrophy. No conduction defect.\n\nIMPRESSION: Normal ECG.`,
    lvh:`Sinus rhythm. Rate: 74 bpm.\nVoltage criteria for LVH met (Sokolow-Lyon index: SV1 + RV5/V6 > 35mm).\nLeft axis deviation. Broad, notched QRS.\nST depression and T wave inversion in lateral leads (V5, V6, I, aVL) — LV strain pattern.\nPR: 160ms. QRS: 110ms. QTc: 440ms.\n\nIMPRESSION: Left ventricular hypertrophy with strain pattern. Echocardiography recommended.`,
    rvh:`Sinus rhythm. Rate: 82 bpm.\nRight axis deviation.\nTall R waves in V1 (R > S in V1). Deep S waves in V5/V6.\nST depression and T wave inversion in V1-V3 — RV strain pattern.\nP pulmonale (tall, peaked P waves in lead II).\n\nIMPRESSION: Right ventricular hypertrophy with strain. Consider pulmonary hypertension or congenital heart disease.`,
    af:`Irregularly irregular rhythm. Rate: ~90-110 bpm (uncontrolled).\nAbsent P waves — replaced by fibrillatory baseline.\nVariable RR intervals throughout.\nQRS morphology normal. No acute ST-T changes.\n\nIMPRESSION: Atrial fibrillation with uncontrolled ventricular rate. Rate control and anticoagulation assessment recommended.`,
    lbbb:`Sinus rhythm. Rate: 70 bpm.\nCOMPLETE LEFT BUNDLE BRANCH BLOCK (LBBB):\n- Broad QRS: >120ms\n- Broad, notched R waves in I, aVL, V5, V6\n- Deep S waves / QS pattern in V1, V2\n- Secondary discordant ST-T changes\n- Left axis deviation\n\nIMPRESSION: Complete LBBB. New LBBB = STEMI equivalent until proven otherwise. Urgent cardiology review.`,
    rbbb:`Sinus rhythm. Rate: 72 bpm.\nCOMPLETE RIGHT BUNDLE BRANCH BLOCK (RBBB):\n- Broad QRS: >120ms\n- RSR' (M-shaped / rabbit ears) pattern in V1, V2\n- Broad, slurred S wave in I, aVL, V5, V6\n- Secondary ST-T changes in V1-V3\n\nIMPRESSION: Complete RBBB. May be normal variant or associated with RV pathology. Correlate clinically.`,
    chb:`Ventricular escape rhythm. Ventricular rate: ~35-40 bpm.\nCOMPLETE HEART BLOCK (3rd Degree AV Block):\n- Complete AV dissociation — P waves and QRS bear no relationship\n- Atrial rate: ~80 bpm (independent P waves)\n- Ventricular rate: ~38 bpm (escape rhythm)\n- Broad, aberrant QRS escape complexes\n\nIMPRESSION: Complete heart block. URGENT — cardiology referral for temporary/permanent pacemaker implantation.`,
    stemi:`Sinus tachycardia. Rate: 102 bpm.\nST ELEVATION MYOCARDIAL INFARCTION (STEMI) — INFERIOR:\n- Significant ST elevation (>2mm) in leads II, III, aVF\n- Reciprocal ST depression in I, aVL\n- Hyperacute T waves in inferior leads\n- No Q waves yet — acute/hyperacute phase\n\nIMPRESSION: ACUTE INFERIOR STEMI. URGENT — activate cath lab. Dual antiplatelet, anticoagulation, primary PCI.`,
    nstemi:`Sinus rhythm. Rate: 88 bpm.\nST depression (1-2mm) and T wave inversion in V4-V6, I, aVL.\nNo significant ST elevation. No pathological Q waves.\nDynamic ECG changes — correlate with serial troponin.\n\nIMPRESSION: ECG changes consistent with NSTEMI / Unstable Angina. Serial ECGs and troponin. Cardiology review. Antiplatelet and anticoagulation therapy.`,
    old_mi:`Sinus rhythm. Rate: 72 bpm.\nPathological Q waves in leads II, III, aVF (duration >40ms, depth >25% of R wave).\nT wave inversion in inferior leads.\nNo acute ST elevation or depression.\nHR, PR, QRS, QTc within normal limits.\n\nIMPRESSION: ECG evidence of old inferior myocardial infarction. Correlate with clinical history and echocardiography.`,
};

const selectedFindings = new Set();

function toggleInterp(key, btn) {
    if (selectedFindings.has(key)) {
        selectedFindings.delete(key);
        btn.style.background = '';
        btn.style.color = '';
        btn.style.borderColor = '';
    } else {
        selectedFindings.add(key);
        btn.style.background = 'var(--sc-teal)';
        btn.style.color = '#fff';
        btn.style.borderColor = 'var(--sc-teal)';
    }
    rebuildText();
    document.getElementById('selected-count').textContent = selectedFindings.size + ' finding(s) selected';
}

function rebuildText() {
    const parts = [];
    selectedFindings.forEach(k => {
        const t = (interps[k] || '').replace(/\\n/g, '\n');
        if (t) parts.push(t);
    });
    document.getElementById('f_interp').value = parts.join('\n\n---\n\n');
}

function clearInterp() {
    selectedFindings.clear();
    document.querySelectorAll('.interp-btn').forEach(b => {
        b.style.background = b.style.color = b.style.borderColor = '';
    });
    document.getElementById('f_interp').value = '';
    document.getElementById('selected-count').textContent = '0 findings selected';
}

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.interp-btn').forEach(btn => {
        btn.addEventListener('click', function() { toggleInterp(this.dataset.key, this); });
    });
});
function applyNormal() {
    document.getElementById('f_hr').value = '72';
    document.getElementById('f_rhythm').value = 'Sinus Rhythm';
    document.getElementById('f_axis').value = 'Normal Axis (-30 to +90)';
    document.getElementById('f_pr').value = '160 ms';
    document.getElementById('f_qrs').value = '90 ms';
    document.getElementById('f_qt').value = '380/420 ms';
    document.getElementById('f_interp').value = interps.normal.replace(/\\n/g, '\n');
}

// ── Auto-read ECG file → fill the parameter fields ──
window.ECG_READ_URL = @json(route('ecg.read'));
window.ECG_CSRF     = @json(csrf_token());
window.ECG_ID       = @json($ecg->id);

function ecgSetVal(id, val){ var e = document.getElementById(id); if (e && val) e.value = val; }
function ecgSetSelect(id, val){
    var e = document.getElementById(id); if (!e || !val) return;
    for (var i=0;i<e.options.length;i++){ if (e.options[i].value===val || e.options[i].text===val){ e.selectedIndex=i; return; } }
}
function ecgShowFlag(flag){
    var box = document.getElementById('ecg-flag');
    if (!box) return;
    if (!flag || !flag.level || flag.level==='none'){ box.style.display='none'; return; }
    var c = {critical:['#fef2f2','#fecaca','#991b1b'], warn:['#fff7ed','#fed7aa','#9a3412'], ok:['#f0fdf4','#bbf7d0','#166534']}[flag.level] || ['#f1f5f9','#e2e8f0','#334155'];
    var icon = flag.level==='ok' ? 'fa-circle-check' : 'fa-triangle-exclamation';
    box.style.background=c[0]; box.style.border='1px solid '+c[1]; box.style.color=c[2];
    box.innerHTML = '<i class="fa-solid '+icon+'"></i> '+ (flag.text||'');
    box.style.display='';
}
function ecgReadFile(){
    var fileInput = document.querySelector('input[name="ecg_file"]');
    var btn = document.getElementById('ecg-read-btn');
    var status = document.getElementById('ecg-read-status');
    var fd = new FormData();
    if (fileInput && fileInput.files && fileInput.files.length){
        fd.append('ecg_file', fileInput.files[0]);
    } else if (window.ECG_ID){
        fd.append('ecg_id', window.ECG_ID);
    } else {
        alert('Choose an ECG file to read first.'); return;
    }
    fd.append('_token', window.ECG_CSRF);
    btn.disabled = true;
    var orig = btn.innerHTML;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Reading ECG…';
    status.textContent = '';
    fetch(window.ECG_READ_URL, { method:'POST', headers:{'X-Requested-With':'XMLHttpRequest'}, body: fd })
        .then(function(r){ return r.json().then(function(j){ return {ok:r.ok, body:j}; }); })
        .then(function(res){
            var d = res.body || {};
            if (!d.ok){ btn.disabled=false; btn.innerHTML=orig; alert(d.error || 'Could not read the ECG.'); return; }
            var v = d.values || {};
            ecgSetVal('f_hr', v.heart_rate);
            ecgSetVal('f_pr', v.pr_interval);
            ecgSetVal('f_qrs', v.qrs_duration);
            ecgSetVal('f_qt', v.qt_qtc);
            ecgSetSelect('f_rhythm', v.rhythm);
            ecgSetSelect('f_axis', v.axis);
            if (v.interpretation) ecgSetVal('f_interp', v.interpretation);
            ecgShowFlag(d.flag);
            // Persist immediately by submitting the form (saves values + file).
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving…';
            status.innerHTML = 'Read complete' + (d.ai_used ? ' (AI-assisted)' : '') + ' — saving record…';
            var form = btn.closest('form');
            setTimeout(function(){ form.submit(); }, (d.flag && d.flag.level === 'critical') ? 1500 : 500);
        })
        .catch(function(){ btn.disabled=false; btn.innerHTML=orig; alert('Read failed — please try again or enter values manually.'); });
}
</script>
@endpush
