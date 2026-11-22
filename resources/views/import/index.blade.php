@extends('layouts.app')
@section('title','OpenEMR Import')
@section('page-title','OpenEMR Import')
@section('content')

<style>
.imp-tabs { display:flex; gap:4px; margin-bottom:20px; border-bottom:2px solid var(--sc-border); }
.imp-tab  { padding:9px 20px; border:none; background:none; cursor:pointer; font-size:13.5px; font-weight:600;
            color:var(--sc-muted); border-bottom:2px solid transparent; margin-bottom:-2px; transition:.15s; }
.imp-tab.active { color:var(--sc-teal); border-bottom-color:var(--sc-teal); }
.imp-pane { display:none; } .imp-pane.active { display:block; }

.src-radio { display:flex; gap:10px; margin-bottom:16px; flex-wrap:wrap; }
.src-card  { flex:1; min-width:170px; border:2px solid var(--sc-border); border-radius:var(--radius);
             padding:14px 16px; cursor:pointer; transition:.15s; }
.src-card:hover { border-color:var(--sc-teal); }
.src-card.selected { border-color:var(--sc-teal); background:var(--sc-teal-glow); }
.src-card input[type=radio] { display:none; }
.src-card .src-title { font-weight:700; font-size:13px; margin-bottom:3px; }
.src-card .src-desc  { font-size:11.5px; color:var(--sc-muted); }

.term-wrap { background:#0f1f3d; border-radius:var(--radius); overflow:hidden; margin-top:16px; }
.term-bar  { background:#162545; padding:8px 14px; display:flex; align-items:center; justify-content:space-between; }
.term-bar span { font-size:12px; color:#8ba0c0; }
.term-dots span { width:11px;height:11px;border-radius:50%;display:inline-block;margin-right:5px; }
.term-body { padding:16px; max-height:480px; overflow-y:auto; font-family:'DM Mono',monospace; font-size:12px;
             line-height:1.7; white-space:pre-wrap; word-break:break-all; color:#c9d8f0; }
.t-info { color:#27c47a; } .t-warn { color:#f5a623; } .t-err  { color:#e84c4c; }
.t-dry  { color:#0ab4b4; } .t-head { color:#fff; font-weight:700; }

.spinner { display:inline-block; width:16px; height:16px; border:2px solid rgba(10,180,180,.3);
           border-top-color:var(--sc-teal); border-radius:50%; animation:spin .7s linear infinite; vertical-align:middle; }
@keyframes spin { to { transform:rotate(360deg); } }

.script-badge { display:inline-flex; align-items:center; gap:6px; padding:5px 12px; border-radius:20px; font-size:12px; font-weight:600; }
.badge-ok  { background:rgba(39,196,122,.12); color:#27c47a; }
.badge-err { background:rgba(232,76,76,.12);  color:#e84c4c; }
</style>

<div class="page-header">
    <div class="page-header-left">
        <h1>OpenEMR Import</h1>
        <div class="breadcrumb">Migrate data from OpenEMR into SpecialityCare</div>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> {{ session('success') }}</div>
@endif
@if(session('error'))
<div class="alert alert-danger"><i class="fa-solid fa-triangle-exclamation"></i> {{ session('error') }}</div>
@endif

{{-- ── Tabs ─────────────────────────────────────────────────────────── --}}
<div class="imp-tabs">
    <button class="imp-tab active" onclick="switchTab('script')"><i class="fa-solid fa-terminal"></i> Script Import</button>
    <button class="imp-tab"        onclick="switchTab('csv')"   ><i class="fa-solid fa-file-csv"></i>  CSV Import</button>
</div>

{{-- ═══════════════════════════ SCRIPT TAB ═══════════════════════════ --}}
<div id="pane-script" class="imp-pane active">
<div class="grid grid-2" style="gap:20px">

{{-- Left col --}}
<div>

{{-- Script status card --}}
<div class="card mb-3">
    <div class="card-header" style="display:flex;align-items:center;justify-content:space-between">
        <span><i class="fa-solid fa-file-code"></i> Import Script</span>
        @if($scriptExists)
        <span class="script-badge badge-ok"><i class="fa-solid fa-circle-check"></i> Found — {{ $scriptModified }}</span>
        @else
        <span class="script-badge badge-err"><i class="fa-solid fa-circle-xmark"></i> Not found</span>
        @endif
    </div>
    <div class="card-body">
        <p style="font-size:12.5px;color:var(--sc-muted);margin-bottom:12px">
            Upload <code>import_openemr.php</code> or <code>import_openemr_v4.php</code> to replace the active script in
            <code>scripts/import_openemr.php</code>.
        </p>
        <form method="POST" action="{{ route('import.upload-script') }}" enctype="multipart/form-data" style="display:flex;gap:8px;align-items:flex-end">
            @csrf
            <div style="flex:1">
                <label class="form-label" style="font-size:12px">PHP Script File</label>
                <input type="file" name="script_file" class="form-control" accept=".php" required>
            </div>
            <button type="submit" class="btn btn-outline" style="white-space:nowrap">
                <i class="fa-solid fa-upload"></i> Upload Script
            </button>
        </form>
    </div>
</div>

{{-- Run import card --}}
<div class="card">
    <div class="card-header"><i class="fa-solid fa-play"></i> Run Import</div>
    <div class="card-body">
        @if(!$scriptExists)
        <div class="alert alert-warning" style="margin-bottom:0">
            <i class="fa-solid fa-triangle-exclamation"></i>
            Upload the import script above before running.
        </div>
        @else

        {{-- Source type selector --}}
        <label class="form-label">Data Source</label>
        <div class="src-radio" id="src-radio">
            <label class="src-card selected" onclick="selectSrc('livedb')">
                <input type="radio" name="_src" value="livedb" checked>
                <div class="src-title"><i class="fa-solid fa-database"></i> Live Database</div>
                <div class="src-desc">Connect directly to OpenEMR MariaDB/MySQL</div>
            </label>
            <label class="src-card" onclick="selectSrc('sqlfile')">
                <input type="radio" name="_src" value="sqlfile">
                <div class="src-title"><i class="fa-solid fa-file-arrow-up"></i> Upload SQL File</div>
                <div class="src-desc">Upload a .sql dump (auto-imported to temp DB)</div>
            </label>
            <label class="src-card" onclick="selectSrc('serverpath')">
                <input type="radio" name="_src" value="serverpath">
                <div class="src-title"><i class="fa-solid fa-hard-drive"></i> Server File Path</div>
                <div class="src-desc">Use a .sql file already on this server</div>
            </label>
        </div>

        {{-- Live DB fields --}}
        <div id="src-livedb">
            <div class="form-row cols-2">
                <div class="form-group">
                    <label class="form-label">OpenEMR Database <span class="req">*</span></label>
                    <input type="text" id="f-db" class="form-control" value="open72" placeholder="open72">
                </div>
                <div class="form-group">
                    <label class="form-label">Host</label>
                    <input type="text" id="f-host" class="form-control" value="127.0.0.1">
                </div>
            </div>
            <div class="form-row cols-2">
                <div class="form-group">
                    <label class="form-label">MySQL User</label>
                    <input type="text" id="f-user" class="form-control" value="root">
                </div>
                <div class="form-group">
                    <label class="form-label">MySQL Password</label>
                    <input type="password" id="f-pass" class="form-control" placeholder="Leave blank to use app default">
                </div>
            </div>
        </div>

        {{-- SQL file upload --}}
        <div id="src-sqlfile" style="display:none">
            <div class="form-group">
                <label class="form-label">OpenEMR SQL Dump <span class="req">*</span></label>
                <input type="file" id="f-sqlfile" class="form-control" accept=".sql,.gz,.sql.gz">
                <div class="form-text">Accepts <code>.sql</code> or <code>.sql.gz</code> (gzipped). Imported into a temp database, then deleted automatically.</div>
            </div>
        </div>

        {{-- Server path --}}
        <div id="src-serverpath" style="display:none">
            <div class="form-group">
                <label class="form-label">Full Server Path to SQL File <span class="req">*</span></label>
                <input type="text" id="f-serverpath" class="form-control" placeholder="/home/xxx/Downloads/open72-30-4-26.sql">
                <div class="form-text">Absolute path to the .sql file on this server (e.g. from your Downloads folder).</div>
            </div>
        </div>

        {{-- Options --}}
        <div class="form-row cols-2" style="margin-top:8px">
            <div class="form-group">
                <label class="form-label">Import Module</label>
                <select id="f-only" class="form-control" onchange="toggleAllLogs()">
                    <option value="">All modules</option>
                    <option value="patients">Patients only</option>
                    <option value="encounters">Encounters only</option>
                    <option value="prescriptions">Prescriptions only</option>
                    <option value="billing">Billing only</option>
                    <option value="clinical_notes">Clinical Notes only</option>
                    <option value="logs">Activity Logs only</option>
                </select>
            </div>
            <div class="form-group" style="padding-top:22px">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13.5px">
                    <input type="checkbox" id="f-dryrun" style="width:15px;height:15px">
                    <span>Dry run <small style="color:var(--sc-muted)">(no data written)</small></span>
                </label>
            </div>
        </div>
        <div id="all-logs-row" style="display:none;margin-bottom:12px">
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;background:rgba(245,166,35,.08);border:1px solid rgba(245,166,35,.3);padding:10px 14px;border-radius:var(--radius)">
                <input type="checkbox" id="f-alllogs" style="width:15px;height:15px">
                <span><strong>Import all log events</strong> <small style="color:var(--sc-muted)">— including read-only selects (~3.6M rows). Default imports only meaningful actions (~282K rows).</small></span>
            </label>
        </div>

        <button onclick="runImport()" id="run-btn" class="btn btn-primary" style="min-width:160px">
            <i class="fa-solid fa-play"></i> Start Import
        </button>

        @endif
    </div>
</div>

</div>{{-- /left col --}}

{{-- Right col: output --}}
<div>
    <div class="card" style="position:sticky;top:80px">
        <div class="card-header" style="display:flex;align-items:center;gap:10px">
            <i class="fa-solid fa-terminal"></i> Import Output
            <span id="run-status" style="margin-left:auto;font-size:12px;color:var(--sc-muted)">—</span>
        </div>
        <div class="card-body" style="padding:0">
            <div class="term-wrap" style="margin:0;border-radius:0 0 var(--radius) var(--radius)">
                <div class="term-bar">
                    <div class="term-dots">
                        <span style="background:#e84c4c"></span>
                        <span style="background:#f5a623"></span>
                        <span style="background:#27c47a"></span>
                    </div>
                    <span id="term-label">Waiting…</span>
                </div>
                <div class="term-body" id="term-out">Run an import to see output here.</div>
            </div>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-header"><i class="fa-solid fa-circle-info"></i> What gets imported</div>
        <div class="card-body" style="padding:12px 16px">
            <table style="width:100%;border-collapse:collapse;font-size:12.5px">
                <thead><tr>
                    <th style="padding:5px 8px;text-align:left;color:var(--sc-muted);font-weight:600">Module</th>
                    <th style="padding:5px 8px;text-align:left;color:var(--sc-muted);font-weight:600">Source (OpenEMR)</th>
                    <th style="padding:5px 8px;text-align:left;color:var(--sc-muted);font-weight:600">Target</th>
                </tr></thead>
                <tbody>
                @foreach([
                    ['Patients','patient_data','patients'],
                    ['Encounters','form_encounter','encounters'],
                    ['Prescriptions','prescriptions','prescriptions + prescription_items'],
                    ['Billing','billing + ar_activity','bills + bill_items + payments'],
                    ['Clinical Notes','form_clinical_notes','clinical_notes'],
                    ['Activity Logs','log (filtered)','activity_logs'],
                ] as [$mod,$src,$tgt])
                <tr style="border-top:1px solid var(--sc-border)">
                    <td style="padding:6px 8px;font-weight:600">{{ $mod }}</td>
                    <td style="padding:6px 8px"><code style="font-size:11px;background:#f1f5f9;padding:1px 5px;border-radius:4px">{{ $src }}</code></td>
                    <td style="padding:6px 8px;color:var(--sc-teal)">{{ $tgt }}</td>
                </tr>
                @endforeach
                </tbody>
            </table>
            <div class="alert alert-info mt-3" style="font-size:12px">
                <i class="fa-solid fa-shield-halved"></i>
                Import is <strong>idempotent</strong> — re-running skips already-imported records. Use the map table <code>openemr_import_map</code> to track what was imported.
            </div>
        </div>
    </div>
</div>{{-- /right col --}}

</div>{{-- /grid --}}
</div>{{-- /pane-script --}}

{{-- ═══════════════════════════ CSV TAB ══════════════════════════════ --}}
<div id="pane-csv" class="imp-pane">
<div class="grid grid-2" style="gap:20px">
<div>
    <div class="card">
        <div class="card-header"><i class="fa-solid fa-file-csv"></i> Upload Patient CSV</div>
        <div class="card-body">
            <div class="alert alert-info"><i class="fa-solid fa-circle-info"></i>
                <div>Export patients from OpenEMR: <strong>Reports → Demographics → Patient List</strong>.<br>
                Required columns: <code>pid, fname, lname, DOB, sex, phone_cell</code></div>
            </div>
            <form method="POST" action="{{ route('import.openemr') }}" enctype="multipart/form-data">
                @csrf
                <div class="form-group">
                    <label class="form-label">CSV File <span class="req">*</span></label>
                    <input type="file" name="csv_file" class="form-control" accept=".csv,.txt" required>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-file-import"></i> Import CSV</button>
            </form>
        </div>
    </div>
    @if(session('import_errors'))
    <div class="card mt-3">
        <div class="card-header" style="color:var(--sc-red)"><i class="fa-solid fa-triangle-exclamation"></i> Errors</div>
        <div class="card-body">
            @foreach(session('import_errors') as $err)
            <div style="font-size:12px;color:var(--sc-red);padding:4px 0;border-bottom:1px solid var(--sc-border)">{{ $err }}</div>
            @endforeach
        </div>
    </div>
    @endif
</div>
<div>
    <div class="card">
        <div class="card-header"><i class="fa-solid fa-table-columns"></i> Column Mapping</div>
        <div class="card-body">
            <table style="width:100%;border-collapse:collapse;font-size:12.5px">
                <thead><tr>
                    <th style="padding:6px;background:var(--sc-bg)">OpenEMR</th>
                    <th style="padding:6px;background:var(--sc-bg)">SpecialityCare</th>
                </tr></thead>
                <tbody>
                @foreach([['pid','openemr_pid'],['fname','first_name'],['mname','middle_name'],['lname','last_name'],['DOB','date_of_birth'],['sex','gender'],['phone_cell','phone'],['email','email'],['street','address'],['city','city'],['state','state'],['postal_code','pincode'],['blood_type','blood_group']] as [$k,$v])
                <tr style="border-bottom:1px solid var(--sc-border)">
                    <td style="padding:6px"><code style="background:#f1f5f9;padding:2px 5px;border-radius:4px;font-size:11px">{{ $k }}</code></td>
                    <td style="padding:6px;color:var(--sc-teal);font-weight:600">{{ $v }}</td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>
</div>{{-- /pane-csv --}}

<script>
// ── Tabs ──────────────────────────────────────────────────────────────────
function switchTab(name) {
    document.querySelectorAll('.imp-tab,.imp-pane').forEach(el => el.classList.remove('active'));
    document.querySelector(`.imp-tab[onclick="switchTab('${name}')"]`).classList.add('active');
    document.getElementById('pane-' + name).classList.add('active');
}

// ── Show/hide all-logs option ─────────────────────────────────────────────
function toggleAllLogs() {
    const v = document.getElementById('f-only').value;
    document.getElementById('all-logs-row').style.display = (v === '' || v === 'logs') ? '' : 'none';
}

// ── Source selector ───────────────────────────────────────────────────────
function selectSrc(val) {
    document.querySelectorAll('.src-card').forEach(c => c.classList.remove('selected'));
    document.querySelector(`.src-card[onclick="selectSrc('${val}')"]`).classList.add('selected');
    ['livedb','sqlfile','serverpath'].forEach(s => {
        document.getElementById('src-' + s).style.display = (s === val) ? '' : 'none';
    });
}

// ── Run import via AJAX ───────────────────────────────────────────────────
async function runImport() {
    const btn    = document.getElementById('run-btn');
    const out    = document.getElementById('term-out');
    const status = document.getElementById('run-status');
    const label  = document.getElementById('term-label');

    const src = document.querySelector('.src-card.selected').getAttribute('onclick').match(/'(\w+)'/)[1];

    const fd = new FormData();
    fd.append('_token',      '{{ csrf_token() }}');
    fd.append('source_type', src);
    fd.append('dry_run',     document.getElementById('f-dryrun').checked ? '1' : '');
    const onlyVal = document.getElementById('f-only').value;
    if (onlyVal) fd.append('only_module', onlyVal);
    if (document.getElementById('f-alllogs')?.checked) fd.append('all_logs', '1');

    if (src === 'livedb') {
        fd.append('openemr_db',   document.getElementById('f-db').value);
        fd.append('openemr_host', document.getElementById('f-host').value);
        fd.append('openemr_user', document.getElementById('f-user').value);
        fd.append('openemr_pass', document.getElementById('f-pass').value);
    } else if (src === 'sqlfile') {
        const sqlF = document.getElementById('f-sqlfile').files[0];
        if (!sqlF) { alert('Please select a SQL file.'); return; }
        fd.append('sql_file', sqlF);
    } else {
        fd.append('server_path', document.getElementById('f-serverpath').value);
    }

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner"></span> Running…';
    status.textContent = 'Running…';
    label.textContent  = 'Import in progress…';
    out.innerHTML      = '<span style="color:#8ba0c0">Starting import, please wait…</span>';

    const started = Date.now();
    try {
        const resp = await fetch('{{ route('import.run') }}', { method:'POST', body:fd });
        const text = await resp.text();
        const secs = ((Date.now() - started) / 1000).toFixed(1);
        let output;
        try {
            const data = JSON.parse(text);
            output = data.output || '(no output)';
        } catch(_) {
            // Server returned HTML (error page / timeout) — strip tags and show plain text
            output = '[ERR]   Server returned an unexpected response (HTTP ' + resp.status + '):\n'
                   + text.replace(/<[^>]+>/g, ' ').replace(/\s{2,}/g, ' ').trim().substring(0, 800);
        }
        out.innerHTML  = ansiToHtml(output);
        status.textContent = resp.ok ? `Finished in ${secs}s` : `Failed (${resp.status})`;
        label.textContent  = resp.ok ? 'Import complete' : 'Import failed';
        out.scrollTop = out.scrollHeight;
    } catch(e) {
        out.innerHTML = `<span class="t-err">[ERR] ${e.message}</span>`;
        status.textContent = 'Error';
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-play"></i> Start Import';
    }
}

// ── ANSI colour → HTML ────────────────────────────────────────────────────
function ansiToHtml(raw) {
    const esc = raw.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    return esc
        .replace(/\x1b\[0m/g,      '</span>')
        .replace(/\x1b\[32m/g,     '<span class="t-info">')
        .replace(/\x1b\[33m/g,     '<span class="t-warn">')
        .replace(/\x1b\[31m/g,     '<span class="t-err">')
        .replace(/\x1b\[36m/g,     '<span class="t-dry">')
        .replace(/\x1b\[[0-9;]+m/g,'');
}
</script>
@endsection
