{{-- FILE: resources/views/ecg/create.blade.php --}}
@extends('layouts.app')
@section('title','New ECG')
@section('page-title','New ECG Record')
@section('content')
<div class="page-header"><div class="page-header-left"><h1>New ECG Record</h1></div>@include('diagnostics._toolbar', ['inline'=>true])</div>
<form method="POST" action="{{ route('ecg.store') }}" enctype="multipart/form-data">
@csrf
<div class="grid" style="grid-template-columns:1fr 300px;gap:20px;align-items:start">
<div>
    <div class="card mb-3">
        <div class="card-header"><i class="fa-solid fa-user"></i> Patient & Doctor</div>
        <div class="card-body">
            @if($patient)
                <div class="patient-strip" style="padding:12px"><div class="ps-avatar">{{ strtoupper(substr($patient->first_name,0,1)) }}</div>
                <div><div class="ps-name">{{ $patient->full_name }}</div><div class="ps-jhc_id">{{ $patient->jhc_id }}</div></div></div>
                <input type="hidden" name="patient_id" value="{{ $patient->id }}">
            @else
                <div style="position:relative">
                    <input type="text" id="pt-search" class="form-control" placeholder="Search patient...">
                    <div id="pt-results" style="position:absolute;top:100%;left:0;right:0;background:#fff;border:1px solid var(--sc-border);border-radius:8px;box-shadow:var(--shadow-md);z-index:200;display:none;max-height:200px;overflow-y:auto;margin-top:4px"></div>
                </div>
                <input type="hidden" name="patient_id" id="pt-id">
            @endif
            <div class="form-row cols-3 mt-3">
                <div class="form-group">
                    <label class="form-label">Doctor <span class="req">*</span></label>
                    <select name="doctor_id" class="form-control select2" required style="width:100%">
                        <option value="">Select Doctor</option>
                        @foreach($doctors as $d)
                            <option value="{{ $d->id }}" {{ $d->id == 1 ? 'selected' : '' }}>{{ $d->full_name }} — {{ $d->specialization }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Record Date <span class="req">*</span></label>
                    <input type="date" name="record_date" class="form-control" value="{{ today()->toDateString() }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Referred By</label>
                    <select id="ecg-ref-select" class="form-control" onchange="onEcgRefSelectChange(this)">
                        <option value="">— None / Self —</option>
                        @foreach($referralSources ?? [] as $rs)
                            <option value="{{ $rs->id }}" data-name="{{ $rs->name }}" {{ old('ref_by') === $rs->name ? 'selected' : '' }}>{{ $rs->name }}</option>
                        @endforeach
                        <option value="__add__" style="font-style:italic;color:var(--sc-teal,#0ab4b4)">＋ Add new referring doctor…</option>
                    </select>
                    <input type="hidden" name="ref_by" id="ecg-ref-text" value="{{ old('ref_by') }}">
                    <input type="hidden" name="referral_source_id" id="ecg-ref-id" value="{{ old('referral_source_id') }}">
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
    </script>
    <div class="card">
        <div class="card-header"><i class="fa-solid fa-heart-pulse"></i> ECG Parameters</div>
        <div class="card-body">
            <div id="ecg-flag" style="display:none;margin-bottom:14px;padding:10px 14px;border-radius:8px;font-size:13px;line-height:1.45"></div>
            <div class="form-row cols-3">
                <div class="form-group"><label class="form-label">Heart Rate (/min)</label><input type="text" name="heart_rate" class="form-control" placeholder="e.g. 72"></div>
                <div class="form-group"><label class="form-label">Rhythm</label>
                    <select name="rhythm" class="form-control">
                        <option value="">Select</option>
                        @foreach(['Sinus Rhythm','Sinus Tachycardia','Sinus Bradycardia','Atrial Fibrillation','Atrial Flutter','Ventricular Tachycardia','Ventricular Fibrillation','Heart Block','Junctional Rhythm','Paced Rhythm'] as $r)
                            <option>{{ $r }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group"><label class="form-label">Axis</label>
                    <select name="axis" class="form-control">
                        <option value="">Select</option>
                        @foreach(['Normal Axis','Left Axis Deviation','Right Axis Deviation','Extreme Axis Deviation'] as $a)
                            <option>{{ $a }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group"><label class="form-label">PR Interval (ms)</label><input type="text" name="pr_interval" class="form-control" placeholder="e.g. 160ms"></div>
                <div class="form-group"><label class="form-label">QRS Duration (ms)</label><input type="text" name="qrs_duration" class="form-control" placeholder="e.g. 90ms"></div>
                <div class="form-group"><label class="form-label">QT/QTc (ms)</label><input type="text" name="qt_qtc" class="form-control" placeholder="e.g. 400/420ms"></div>
            </div>
            <div class="form-group">
                <label class="form-label">Interpretation / Findings</label>
                <textarea name="interpretation" class="form-control" rows="4" placeholder="ECG interpretation, findings, conclusion..."></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Upload ECG Report (optional)</label>
                <input type="file" name="ecg_file" class="form-control" accept="image/*,.pdf,.bmp,.tiff">
                <div style="font-size:12px;color:var(--sc-muted);margin-top:4px">Accepted: JPEG, PNG, PDF, BMP, TIFF — max 20 MB</div>
                <div style="margin-top:10px;display:flex;align-items:center;gap:10px;flex-wrap:wrap">
                    <button type="button" id="ecg-read-btn" class="btn btn-sm" style="background:#0891b2;color:#fff;border-color:#0891b2" onclick="ecgReadFile()">
                        <i class="fa-solid fa-wand-magic-sparkles"></i> Read, analyze &amp; save
                    </button>
                    <span id="ecg-read-status" style="font-size:12px;color:var(--sc-muted)"></span>
                </div>
                <div style="font-size:11px;color:var(--sc-muted);margin-top:6px">Select the patient &amp; doctor, choose the ECG, then this reads it, fills the fields and <b>saves the record</b>. Use Edit afterwards to change anything.</div>
            </div>
        </div>
    </div>
</div>
<div>
    <div class="card">
        <div class="card-body">
            <div class="form-group"><label class="form-label">Price (₹) <span class="req">*</span></label><input type="number" name="price" class="form-control" value="{{ $price }}" required step="0.01" min="0"></div>
            <button type="submit" class="btn btn-primary btn-lg" style="width:100%;justify-content:center"><i class="fa-solid fa-save"></i> Save ECG Record</button>
        </div>
    </div>
</div>
</div>
</form>

@push('scripts')
<script>
// ── Auto-read ECG file → fill the parameter fields (create form) ──
window.ECG_READ_URL = @json(route('ecg.read'));
window.ECG_CSRF     = @json(csrf_token());

function ecgField(name){ return document.querySelector('[name="'+name+'"]'); }
function ecgSetVal(name, val){ var e = ecgField(name); if (e && val) e.value = val; }
function ecgSetSelect(name, val){
    var e = ecgField(name); if (!e || !val) return;
    for (var i=0;i<e.options.length;i++){ if (e.options[i].value===val || e.options[i].text===val){ e.selectedIndex=i; return; } }
    var lv = val.toLowerCase();
    for (var j=0;j<e.options.length;j++){ var t=(e.options[j].text||'').toLowerCase(); if (t && (lv.indexOf(t)>-1 || t.indexOf(lv)>-1)){ e.selectedIndex=j; return; } }
}
function ecgShowFlag(flag){
    var box = document.getElementById('ecg-flag');
    if (!box) return;
    if (!flag || !flag.level || flag.level==='none'){ box.style.display='none'; return; }
    var c = {critical:['#fef2f2','#fecaca','#991b1b'], warn:['#fff7ed','#fed7aa','#9a3412'], ok:['#f0fdf4','#bbf7d0','#166534']}[flag.level] || ['#f1f5f9','#e2e8f0','#334155'];
    var icon = flag.level==='ok' ? 'fa-circle-check' : 'fa-triangle-exclamation';
    box.style.background=c[0]; box.style.border='1px solid '+c[1]; box.style.color=c[2];
    box.innerHTML = '<i class="fa-solid '+icon+'"></i> ' + (flag.text||'');
    box.style.display='';
}
function ecgReadFile(){
    var fileInput = ecgField('ecg_file');
    var btn = document.getElementById('ecg-read-btn');
    var status = document.getElementById('ecg-read-status');
    if (!(fileInput && fileInput.files && fileInput.files.length)){ alert('Choose an ECG file to read first.'); return; }
    var fd = new FormData();
    fd.append('ecg_file', fileInput.files[0]);
    fd.append('_token', window.ECG_CSRF);
    btn.disabled = true; var orig = btn.innerHTML;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Reading ECG…';
    status.textContent = '';
    fetch(window.ECG_READ_URL, { method:'POST', headers:{'X-Requested-With':'XMLHttpRequest'}, body: fd })
        .then(function(r){ return r.json().then(function(j){ return {ok:r.ok, body:j}; }); })
        .then(function(res){
            var d = res.body || {};
            if (!d.ok){ btn.disabled=false; btn.innerHTML=orig; alert(d.error || 'Could not read the ECG.'); return; }
            var v = d.values || {};
            ecgSetVal('heart_rate', v.heart_rate);
            ecgSetVal('pr_interval', v.pr_interval);
            ecgSetVal('qrs_duration', v.qrs_duration);
            ecgSetVal('qt_qtc', v.qt_qtc);
            ecgSetSelect('rhythm', v.rhythm);
            ecgSetSelect('axis', v.axis);
            if (v.interpretation) ecgSetVal('interpretation', v.interpretation);
            ecgShowFlag(d.flag);
            // Need patient + doctor before we can save a new record.
            var pid = (ecgField('patient_id') || {}).value;
            var did = (ecgField('doctor_id') || {}).value;
            if (!pid || !did){
                btn.disabled=false; btn.innerHTML=orig;
                status.innerHTML = '<i class="fa-solid fa-triangle-exclamation" style="color:#d97706"></i> Values filled — select patient &amp; doctor, then click "Save ECG Record".';
                return;
            }
            // Persist immediately by submitting the form (creates the record + saves file).
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving…';
            status.innerHTML = 'Read complete' + (d.ai_used ? ' (AI-assisted)' : '') + ' — saving record…';
            var form = btn.closest('form');
            setTimeout(function(){ form.submit(); }, (d.flag && d.flag.level === 'critical') ? 1500 : 500);
        })
        .catch(function(){ btn.disabled=false; btn.innerHTML=orig; alert('Read failed — please try again or enter values manually.'); });
}
</script>
@endpush
@endsection
