@extends('layouts.app')
@section('title','Export to OpenEMR')
@section('page-title','Export to OpenEMR')
@section('content')

<style>
.term-wrap { background:#0f1f3d; border-radius:var(--radius); overflow:hidden; margin-top:16px; }
.term-bar  { background:#162545; padding:8px 14px; display:flex; align-items:center; justify-content:space-between; }
.term-bar span { font-size:12px; color:#8ba0c0; }
.term-dots span { width:11px;height:11px;border-radius:50%;display:inline-block;margin-right:5px; }
.term-body { padding:16px; max-height:480px; overflow-y:auto; font-family:'DM Mono',monospace; font-size:12px;
             line-height:1.7; white-space:pre-wrap; word-break:break-all; color:#c9d8f0; }
.t-info { color:#27c47a; } .t-warn { color:#f5a623; } .t-err { color:#e84c4c; }
.t-dry  { color:#0ab4b4; } .t-head { color:#fff; font-weight:700; }
.spinner { display:inline-block; width:16px; height:16px; border:2px solid rgba(10,180,180,.3);
           border-top-color:var(--sc-teal); border-radius:50%; animation:spin .7s linear infinite; vertical-align:middle; }
@keyframes spin { to { transform:rotate(360deg); } }
.script-badge { display:inline-flex; align-items:center; gap:6px; padding:5px 12px; border-radius:20px; font-size:12px; font-weight:600; }
.badge-ok  { background:rgba(39,196,122,.12); color:#27c47a; }
.badge-err { background:rgba(232,76,76,.12);  color:#e84c4c; }
.exp-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:14px; margin-bottom:6px; }
.exp-note { font-size:11.5px; color:var(--sc-muted); margin-top:3px; }
.map-table td,.map-table th { padding:6px 10px; font-size:12px; border-bottom:1px solid var(--sc-border); text-align:left; }
</style>

<div class="page-header">
    <div class="page-header-left">
        <h1>Export to OpenEMR</h1>
        <div class="breadcrumb">Push SpecialityCare data (patients, encounters, pharmacy, lab, accounts) into an OpenEMR database</div>
    </div>
</div>

<div class="card" style="padding:22px;">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;margin-bottom:18px;">
        <div style="font-weight:700;font-size:15px;"><i class="fa-solid fa-right-left" style="color:var(--sc-teal)"></i> SpecialityCare → OpenEMR migration</div>
        @if($scriptExists)
            <span class="script-badge badge-ok"><i class="fa-solid fa-circle-check"></i> script ready &middot; {{ $scriptModified }}</span>
        @else
            <span class="script-badge badge-err"><i class="fa-solid fa-triangle-exclamation"></i> scripts/export_to_openemr.php missing</span>
        @endif
    </div>

    <div class="exp-grid">
        <div>
            <label class="form-label">Target OpenEMR database <span style="color:#e84c4c">*</span></label>
            <input id="f-target" class="form-control" value="open72_sc" autocomplete="off">
            <div class="exp-note">Must end in <code>_sc</code> — a disposable copy. Never a live DB.</div>
        </div>
        <div>
            <label class="form-label">Rebuild: clone structure from</label>
            <input id="f-clonefrom" class="form-control" value="open72" autocomplete="off">
            <div class="exp-note">Source OpenEMR schema to copy (config + lookups kept, patient data wiped).</div>
        </div>
        <div>
            <label class="form-label">Stage</label>
            <select id="f-only" class="form-control">
                <option value="">All stages (full migration)</option>
                @foreach($stages as $s)
                    <option value="{{ $s }}">{{ ucwords(str_replace('_',' ',$s)) }}</option>
                @endforeach
            </select>
            <div class="exp-note">Run one stage, or all in dependency order.</div>
        </div>
    </div>

    <div style="display:flex;gap:22px;flex-wrap:wrap;margin:14px 0 4px;">
        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;">
            <input type="checkbox" id="f-rebuild" checked> <b>Rebuild target first</b> (wipe &amp; clone — recommended for a clean export)
        </label>
        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;">
            <input type="checkbox" id="f-dryrun"> Dry run (no writes)
        </label>
    </div>
    <div class="exp-note" style="margin-bottom:14px;">
        <i class="fa-solid fa-circle-info"></i> Uncheck <b>Rebuild</b> to do an incremental top-up — only patients/records added since the last export are inserted (idempotent via the <code>sc_export_map</code> table). Dry run works for the first stage only.
    </div>

    <button id="run-btn" class="btn btn-primary" onclick="runExport()">
        <i class="fa-solid fa-play"></i> Start Export
    </button>

    {{-- Terminal --}}
    <div class="term-wrap">
        <div class="term-bar">
            <div class="term-dots">
                <span style="background:#ff5f56"></span><span style="background:#ffbd2e"></span><span style="background:#27c93f"></span>
            </div>
            <span id="term-label">Ready</span>
            <span id="run-status"></span>
        </div>
        <div class="term-body" id="term-out"><span style="color:#8ba0c0">Configure the target above and press <b>Start Export</b>.</span></div>
    </div>
</div>

{{-- Mapping reference --}}
<div class="card" style="padding:22px;margin-top:18px;">
    <div style="font-weight:700;margin-bottom:10px;"><i class="fa-solid fa-table-list" style="color:var(--sc-teal)"></i> What gets exported</div>
    <table class="map-table" style="width:100%;border-collapse:collapse;">
        <thead><tr><th>SpecialityCare</th><th>→ OpenEMR</th></tr></thead>
        <tbody>
            <tr><td>doctors (+ users)</td><td>users (providers)</td></tr>
            <tr><td>patients</td><td>patient_data &nbsp;<span class="exp-note">(OpenEMR <code>pid</code> = SC patient id)</span></td></tr>
            <tr><td>encounters (+ clinical narrative)</td><td>form_encounter + forms + clinical notes</td></tr>
            <tr><td>medicines, pharmacy stock</td><td>drugs, drug_inventory</td></tr>
            <tr><td>prescriptions + items</td><td>prescriptions</td></tr>
            <tr><td>lab tests / orders / results</td><td>procedure_type / procedure_order / procedure_result</td></tr>
            <tr><td>bills + items, payments</td><td>billing, ar_session + ar_activity</td></tr>
            <tr><td>clinical notes, allergies</td><td>form_clinical_notes, lists</td></tr>
        </tbody>
    </table>
    <div class="exp-note" style="margin-top:12px;">
        Idempotent — re-running skips already-exported rows (tracked in <code>sc_export_map</code> on the target). Rich-text HTML is converted to clean plain text. UUIDs are backfilled automatically after each run.
    </div>
</div>

<script>
async function runExport() {
    const btn    = document.getElementById('run-btn');
    const out    = document.getElementById('term-out');
    const status = document.getElementById('run-status');
    const label  = document.getElementById('term-label');

    const target = document.getElementById('f-target').value.trim();
    if (!/^[a-z0-9_]+_sc$/.test(target)) {
        alert("Target database name must end in '_sc' (e.g. open72_sc). It is wiped on rebuild, so it must be a disposable copy, never a live DB.");
        return;
    }

    const fd = new FormData();
    fd.append('_token',     '{{ csrf_token() }}');
    fd.append('target_db',  target);
    fd.append('clone_from', document.getElementById('f-clonefrom').value.trim());
    fd.append('rebuild',    document.getElementById('f-rebuild').checked ? '1' : '');
    fd.append('dry_run',    document.getElementById('f-dryrun').checked ? '1' : '');
    const only = document.getElementById('f-only').value;
    if (only) fd.append('only', only);

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner"></span> Running…';
    status.textContent = 'Running…';
    label.textContent  = 'Export in progress…';
    out.innerHTML = '<span style="color:#8ba0c0">Starting export — a full rebuild clones the schema and loads ~700 patients + records, this can take a minute…</span>';

    const started = Date.now();
    try {
        const resp = await fetch('{{ route('export.run') }}', { method:'POST', body:fd });
        const text = await resp.text();
        const secs = ((Date.now() - started) / 1000).toFixed(1);
        let output;
        try { output = (JSON.parse(text).output) || '(no output)'; }
        catch(_) {
            output = '[ERR]   Server returned an unexpected response (HTTP ' + resp.status + '):\n'
                   + text.replace(/<[^>]+>/g,' ').replace(/\s{2,}/g,' ').trim().substring(0,800);
        }
        out.innerHTML = ansiToHtml(output);
        status.textContent = resp.ok ? `Finished in ${secs}s` : `Failed (${resp.status})`;
        label.textContent  = resp.ok ? 'Export complete' : 'Export failed';
        out.scrollTop = out.scrollHeight;
    } catch(e) {
        out.innerHTML = `<span class="t-err">[ERR] ${e.message}</span>`;
        status.textContent = 'Error';
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-play"></i> Start Export';
    }
}

function ansiToHtml(raw) {
    const esc = raw.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    return esc
        .replace(/\x1b\[0m/g,       '</span>')
        .replace(/\x1b\[32m/g,      '<span class="t-info">')
        .replace(/\x1b\[33m/g,      '<span class="t-warn">')
        .replace(/\x1b\[31m/g,      '<span class="t-err">')
        .replace(/\x1b\[36m/g,      '<span class="t-dry">')
        .replace(/\x1b\[[0-9;]+m/g, '');
}
</script>
@endsection
