{{-- FILE: resources/views/opd/board.blade.php — Live Queue Board --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OPD Queue Board — {{ setting('clinic_name','Demo Clinic') }}</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
    * { box-sizing:border-box; margin:0; padding:0; }
    body { background:#0f172a; color:#fff; font-family:Arial,Helvetica,sans-serif; min-height:100vh; }

    .board-header {
        background:#1e293b; padding:14px 28px;
        display:flex; align-items:center; justify-content:space-between;
        border-bottom:2px solid #334155;
    }
    .board-clinic { font-size:20px; font-weight:800; letter-spacing:.5px; }
    .board-date   { font-size:13px; color:#94a3b8; }
    .board-clock  { font-size:22px; font-weight:700; color:#38bdf8; }

    .board-stats {
        display:flex; gap:0; border-bottom:1px solid #1e293b;
    }
    .bstat { flex:1; text-align:center; padding:14px 0; border-right:1px solid #1e293b; }
    .bstat:last-child { border-right:none; }
    .bstat-num { font-size:32px; font-weight:900; line-height:1; }
    .bstat-lbl { font-size:11px; color:#94a3b8; margin-top:3px; text-transform:uppercase; letter-spacing:.5px; }
    .bstat-total   .bstat-num { color:#e2e8f0; }
    .bstat-waiting .bstat-num { color:#fbbf24; }
    .bstat-active  .bstat-num { color:#34d399; }
    .bstat-done    .bstat-num { color:#60a5fa; }

    .board-body { display:grid; grid-template-columns:1fr 340px; height:calc(100vh - 130px); }

    /* Queue list */
    .board-queue { padding:20px; overflow-y:auto; }
    .queue-title { font-size:13px; text-transform:uppercase; letter-spacing:1px; color:#64748b; margin-bottom:12px; }
    .queue-row {
        display:flex; align-items:center; gap:14px;
        background:#1e293b; border-radius:8px; padding:12px 16px; margin-bottom:8px;
        border-left:4px solid #334155;
    }
    .queue-row.urgent { border-left-color:#ef4444; }
    .queue-row.called { border-left-color:#3b82f6; background:#1e3a5f; }
    .q-token  { font-size:24px; font-weight:900; color:#38bdf8; min-width:64px; }
    .q-name   { font-size:14px; font-weight:700; }
    .q-meta   { font-size:11px; color:#94a3b8; margin-top:2px; }
    .q-status { margin-left:auto; }
    .qs-waiting { background:#422006; color:#fbbf24; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:700; }
    .qs-called  { background:#1e3a8a; color:#93c5fd; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:700; }

    /* Current token panel */
    .board-current {
        background:#0d1f3c; border-left:2px solid #1e3a5f;
        display:flex; flex-direction:column; align-items:center; justify-content:center;
        padding:30px 20px; text-align:center;
    }
    .now-label   { font-size:12px; text-transform:uppercase; letter-spacing:2px; color:#64748b; margin-bottom:12px; }
    .now-token   { font-size:88px; font-weight:900; color:#34d399; line-height:1; }
    .now-name    { font-size:20px; font-weight:700; margin-top:12px; }
    .now-doctor  { font-size:13px; color:#94a3b8; margin-top:6px; }
    .now-purpose { font-size:13px; color:#64748b; margin-top:8px; font-style:italic; }
    .now-empty   { font-size:16px; color:#334155; }
    .now-urgent  { color:#ef4444; font-size:12px; margin-top:8px; font-weight:700; text-transform:uppercase; letter-spacing:1px; }

    .board-footer { text-align:center; padding:8px; background:#0f172a; font-size:11px; color:#334155; }

    .filter-bar { padding:8px 20px; background:#1e293b; border-bottom:1px solid #334155; display:flex; gap:8px; align-items:center; }
    .filter-bar select { background:#0f172a; color:#e2e8f0; border:1px solid #334155; padding:4px 10px; border-radius:4px; font-size:13px; }
    .filter-bar label { font-size:12px; color:#94a3b8; }
    </style>
</head>
<body>

<div class="board-header">
    <div>
        <div class="board-clinic"><i class="fa-solid fa-heart-pulse" style="color:#ef4444;margin-right:8px"></i>{{ setting('clinic_name','Demo Clinic Pvt. Ltd.') }}</div>
        <div class="board-date">OPD Queue — {{ today()->format('l, d F Y') }}</div>
    </div>
    <div class="board-clock" id="clock"></div>
</div>

<div class="board-stats" id="statsBar">
    <div class="bstat bstat-total">  <div class="bstat-num" id="sTotal">{{ $stats['total'] }}</div>          <div class="bstat-lbl">Total</div></div>
    <div class="bstat bstat-waiting"><div class="bstat-num" id="sWaiting">{{ $stats['waiting'] }}</div>       <div class="bstat-lbl">Waiting</div></div>
    <div class="bstat bstat-active"> <div class="bstat-num" id="sActive">{{ $stats['in_consultation'] }}</div><div class="bstat-lbl">In Consultation</div></div>
    <div class="bstat bstat-done">   <div class="bstat-num" id="sDone">{{ $stats['done'] }}</div>             <div class="bstat-lbl">Done</div></div>
</div>

<div class="filter-bar">
    <label>Filter:</label>
    <select id="doctorFilter" onchange="loadQueue()">
        <option value="">All Doctors</option>
        @foreach($doctors as $d)
            <option value="{{ $d->id }}" {{ $doctorId == $d->id ? 'selected':'' }}>Dr. {{ $d->first_name }} {{ $d->last_name }}</option>
        @endforeach
    </select>
    <span style="font-size:11px;color:#475569;margin-left:auto">Auto-refreshes every 15 seconds</span>
</div>

<div class="board-body">
    <div class="board-queue">
        <div class="queue-title">Queue</div>
        <div id="queueList">
            @forelse($queue as $t)
            <div class="queue-row {{ $t->priority==='urgent'?'urgent':'' }} {{ $t->status==='called'?'called':'' }}">
                <div class="q-token">{{ $t->display }}</div>
                <div>
                    <div class="q-name">{{ $t->patient->first_name }} {{ $t->patient->last_name }}</div>
                    <div class="q-meta">{{ $t->doctor ? 'Dr. '.$t->doctor->first_name.' '.$t->doctor->last_name : 'Any Doctor' }}{{ $t->purpose ? ' · '.$t->purpose : '' }}</div>
                </div>
                <div class="q-status">
                    @if($t->status==='called')
                        <span class="qs-called"><i class="fa-solid fa-bell"></i> Called</span>
                    @else
                        <span class="qs-waiting">Waiting</span>
                    @endif
                </div>
            </div>
            @empty
            <div style="color:#334155;text-align:center;padding:40px">No patients in queue</div>
            @endforelse
        </div>
    </div>

    <div class="board-current" id="currentPanel">
        <div class="now-label">Now Consulting</div>
        @if($current)
        <div class="now-token" id="nowToken">{{ $current->display }}</div>
        <div class="now-name"  id="nowName">{{ $current->patient->first_name }} {{ $current->patient->last_name }}</div>
        <div class="now-doctor" id="nowDoctor">{{ $current->doctor ? 'Dr. '.$current->doctor->first_name.' '.$current->doctor->last_name : '' }}</div>
        @if($current->purpose)<div class="now-purpose" id="nowPurpose">{{ $current->purpose }}</div>@endif
        @if($current->priority==='urgent')<div class="now-urgent"><i class="fa-solid fa-circle-exclamation"></i> Urgent</div>@endif
        @else
        <div class="now-empty" id="nowEmpty"><i class="fa-solid fa-hourglass" style="font-size:48px;display:block;margin-bottom:16px;opacity:.2"></i>Waiting for next patient</div>
        @endif
    </div>
</div>

<div class="board-footer">{{ setting('clinic_name') }} &nbsp;·&nbsp; {{ setting('clinic_address') }}</div>

<script>
// Clock
function updateClock() {
    const now = new Date();
    document.getElementById('clock').textContent =
        now.toLocaleTimeString('en-IN', {hour:'2-digit',minute:'2-digit',second:'2-digit'});
}
updateClock(); setInterval(updateClock, 1000);

// Live queue polling
function loadQueue() {
    const doctorId = document.getElementById('doctorFilter').value;
    const url = `/specialitycare/opd/queue-data${doctorId ? '?doctor_id='+doctorId : ''}`;
    fetch(url)
        .then(r => r.json())
        .then(data => {
            // Stats
            document.getElementById('sTotal').textContent   = data.stats.total;
            document.getElementById('sWaiting').textContent = data.stats.waiting;
            document.getElementById('sActive').textContent  = data.stats.in_consultation;
            document.getElementById('sDone').textContent    = data.stats.done;

            // Queue list (waiting + called only)
            const visible = data.tokens.filter(t => ['waiting','called'].includes(t.status));
            const list = document.getElementById('queueList');
            if (!visible.length) {
                list.innerHTML = '<div style="color:#334155;text-align:center;padding:40px">No patients in queue</div>';
            } else {
                list.innerHTML = visible.map(t => `
                    <div class="queue-row ${t.priority==='urgent'?'urgent':''} ${t.status==='called'?'called':''}">
                        <div class="q-token">${t.display}</div>
                        <div>
                            <div class="q-name">${t.patient}</div>
                            <div class="q-meta">${t.doctor}${t.purpose?' · '+t.purpose:''}</div>
                        </div>
                        <div class="q-status">
                            ${t.status==='called'
                                ? '<span class="qs-called"><i class="fa-solid fa-bell"></i> Called</span>'
                                : '<span class="qs-waiting">Waiting</span>'}
                        </div>
                    </div>`).join('');
            }

            // Current panel
            const current = data.tokens.find(t => t.status==='in_consultation')
                         ?? data.tokens.find(t => t.status==='called');
            const panel = document.getElementById('currentPanel');
            if (current) {
                panel.innerHTML = `
                    <div class="now-label">Now Consulting</div>
                    <div class="now-token">${current.display}</div>
                    <div class="now-name">${current.patient}</div>
                    <div class="now-doctor">${current.doctor}</div>
                    ${current.purpose ? `<div class="now-purpose">${current.purpose}</div>` : ''}
                    ${current.priority==='urgent' ? '<div class="now-urgent"><i class="fa-solid fa-circle-exclamation"></i> Urgent</div>' : ''}`;
            } else {
                panel.innerHTML = `
                    <div class="now-label">Now Consulting</div>
                    <div class="now-empty"><i class="fa-solid fa-hourglass" style="font-size:48px;display:block;margin-bottom:16px;opacity:.2"></i>Waiting for next patient</div>`;
            }
        });
}
setInterval(loadQueue, 15000);
</script>
</body>
</html>
