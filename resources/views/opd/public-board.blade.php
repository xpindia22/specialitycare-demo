{{-- FILE: resources/views/opd/public-board.blade.php — Public TV Queue Display Board --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="30">
    <title>OPD Queue — James Heart Care Pvt. Ltd.</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
        --bg:       #03071e;
        --surface:  #0d1b2a;
        --card:     #112240;
        --border:   #1e3a5f;
        --teal:     #00d4d4;
        --teal-dim: rgba(0,212,212,0.15);
        --gold:     #fbbf24;
        --green:    #34d399;
        --red:      #f87171;
        --text:     #e2e8f0;
        --muted:    #8899aa;
    }

    html, body {
        height: 100%;
        background: var(--bg);
        color: var(--text);
        font-family: 'Segoe UI', Arial, Helvetica, sans-serif;
        overflow-x: hidden;
    }

    /* ── HEADER ── */
    .tv-header {
        background: var(--surface);
        border-bottom: 3px solid var(--teal);
        padding: 18px 36px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
    }
    .tv-logo {
        display: flex;
        align-items: center;
        gap: 14px;
    }
    .tv-logo-icon {
        width: 54px; height: 54px;
        border-radius: 14px;
        background: linear-gradient(135deg, var(--teal), #0891b2);
        display: flex; align-items: center; justify-content: center;
        font-size: 26px; color: #fff;
        flex-shrink: 0;
    }
    .tv-logo-text .name {
        font-size: 2rem;
        font-weight: 800;
        color: #fff;
        letter-spacing: 0.5px;
        line-height: 1.1;
    }
    .tv-logo-text .sub {
        font-size: 1rem;
        color: var(--teal);
        font-weight: 600;
        margin-top: 2px;
        text-transform: uppercase;
        letter-spacing: 2px;
    }
    .tv-header-right {
        text-align: right;
    }
    .tv-clock {
        font-size: 2.8rem;
        font-weight: 800;
        color: var(--teal);
        font-variant-numeric: tabular-nums;
        line-height: 1;
    }
    .tv-date {
        font-size: 1rem;
        color: var(--muted);
        margin-top: 4px;
    }

    /* ── STATS BAR ── */
    .tv-stats {
        display: flex;
        background: var(--surface);
        border-bottom: 1px solid var(--border);
    }
    .tv-stat {
        flex: 1;
        text-align: center;
        padding: 14px 0;
        border-right: 1px solid var(--border);
    }
    .tv-stat:last-child { border-right: none; }
    .tv-stat-num {
        font-size: 2.4rem;
        font-weight: 900;
        line-height: 1;
    }
    .tv-stat-lbl {
        font-size: 0.85rem;
        color: var(--muted);
        margin-top: 4px;
        text-transform: uppercase;
        letter-spacing: 1px;
        font-weight: 600;
    }
    .stat-total   .tv-stat-num { color: #e2e8f0; }
    .stat-waiting .tv-stat-num { color: var(--gold); }
    .stat-active  .tv-stat-num { color: var(--green); }
    .stat-done    .tv-stat-num { color: #60a5fa; }

    /* ── MAIN CONTENT ── */
    .tv-body {
        padding: 24px 28px;
        min-height: calc(100vh - 210px);
    }
    .tv-body-title {
        font-size: 1rem;
        text-transform: uppercase;
        letter-spacing: 2px;
        color: var(--muted);
        font-weight: 700;
        margin-bottom: 18px;
    }

    /* ── EMPTY STATE ── */
    .tv-empty {
        text-align: center;
        padding: 80px 20px;
        color: var(--border);
    }
    .tv-empty i { font-size: 5rem; margin-bottom: 20px; display: block; }
    .tv-empty p { font-size: 1.5rem; }

    /* ── DOCTOR CARDS GRID ── */
    .tv-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(420px, 1fr));
        gap: 22px;
    }

    .doctor-card {
        background: var(--card);
        border-radius: 16px;
        border: 1px solid var(--border);
        overflow: hidden;
    }
    .doctor-card-header {
        background: rgba(0,212,212,0.1);
        border-bottom: 2px solid var(--teal);
        padding: 14px 20px;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .doctor-card-header i {
        font-size: 1.3rem;
        color: var(--teal);
    }
    .doctor-name {
        font-size: 1.4rem;
        font-weight: 800;
        color: #fff;
    }

    /* ── NOW SERVING ── */
    .now-serving-block {
        padding: 18px 20px 14px;
        border-bottom: 1px solid var(--border);
    }
    .now-serving-label {
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 2px;
        color: var(--green);
        font-weight: 700;
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .now-serving-label::before {
        content: '';
        display: inline-block;
        width: 9px; height: 9px;
        border-radius: 50%;
        background: var(--green);
        animation: pulse 1.5s infinite;
    }
    @keyframes pulse {
        0%, 100% { opacity: 1; transform: scale(1); }
        50%       { opacity: 0.5; transform: scale(0.85); }
    }
    .now-serving-content {
        display: flex;
        align-items: center;
        gap: 18px;
    }
    .now-token-badge {
        font-size: 3rem;
        font-weight: 900;
        color: var(--green);
        line-height: 1;
        min-width: 90px;
        text-align: center;
        background: rgba(52,211,153,0.1);
        border-radius: 12px;
        padding: 8px 14px;
    }
    .now-patient-name {
        font-size: 1.8rem;
        font-weight: 700;
        color: #fff;
        line-height: 1.2;
    }
    .now-patient-meta {
        font-size: 0.95rem;
        color: var(--muted);
        margin-top: 4px;
    }
    .no-current {
        padding: 14px 20px 14px;
        font-size: 1.1rem;
        color: var(--border);
        font-style: italic;
        display: flex;
        align-items: center;
        gap: 10px;
        border-bottom: 1px solid var(--border);
    }

    /* ── QUEUE LIST ── */
    .queue-section {
        padding: 14px 20px 16px;
    }
    .queue-section-label {
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        color: var(--muted);
        font-weight: 700;
        margin-bottom: 10px;
    }
    .queue-item {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 9px 12px;
        background: rgba(255,255,255,0.03);
        border-radius: 8px;
        margin-bottom: 6px;
        border-left: 4px solid transparent;
    }
    .queue-item.urgent { border-left-color: var(--red); }
    .qi-token {
        font-size: 1.5rem;
        font-weight: 800;
        color: var(--teal);
        min-width: 60px;
        line-height: 1;
    }
    .qi-name {
        font-size: 1.1rem;
        font-weight: 600;
        color: var(--text);
    }
    .qi-badge {
        margin-left: auto;
        font-size: 0.78rem;
        font-weight: 700;
        padding: 3px 10px;
        border-radius: 20px;
    }
    .qi-badge.waiting { background: rgba(251,191,36,0.15); color: var(--gold); }
    .qi-badge.called  { background: rgba(96,165,250,0.15); color: #60a5fa; }
    .queue-empty {
        font-size: 1rem;
        color: var(--border);
        font-style: italic;
        text-align: center;
        padding: 6px;
    }

    /* ── FOOTER ── */
    .tv-footer {
        text-align: center;
        padding: 10px;
        background: var(--surface);
        border-top: 1px solid var(--border);
        font-size: 0.85rem;
        color: var(--muted);
    }

    @media (max-width: 900px) {
        .tv-grid { grid-template-columns: 1fr; }
        .tv-logo-text .name { font-size: 1.5rem; }
        .tv-clock { font-size: 2rem; }
    }
    </style>
</head>
<body>

<!-- HEADER -->
<header class="tv-header">
    <div class="tv-logo">
        <div class="tv-logo-icon"><i class="fa-solid fa-heart-pulse"></i></div>
        <div class="tv-logo-text">
            <div class="name">James Heart Care Pvt. Ltd.</div>
            <div class="sub">@if($dept)<i class="fa-solid {{ $dept->icon ?: 'fa-hospital' }}"></i> {{ $dept->name }}@if($dept->room) &middot; {{ $dept->room }}@endif @else OPD Queue Display @endif</div>
        </div>
    </div>
    <div class="tv-header-right">
        <div class="tv-clock" id="tv-clock">--:--:--</div>
        <div class="tv-date" id="tv-date">{{ now()->format('l, d F Y') }}</div>
    </div>
</header>

<!-- STATS BAR -->
<div class="tv-stats">
    <div class="tv-stat stat-total">
        <div class="tv-stat-num">{{ $stats['total'] }}</div>
        <div class="tv-stat-lbl">Total Today</div>
    </div>
    <div class="tv-stat stat-waiting">
        <div class="tv-stat-num">{{ $stats['waiting'] }}</div>
        <div class="tv-stat-lbl">Waiting</div>
    </div>
    <div class="tv-stat stat-active">
        <div class="tv-stat-num">{{ $stats['in_consultation'] }}</div>
        <div class="tv-stat-lbl">In Consultation</div>
    </div>
    <div class="tv-stat stat-done">
        <div class="tv-stat-num">{{ $stats['done'] }}</div>
        <div class="tv-stat-lbl">Completed</div>
    </div>
</div>

<!-- MAIN BODY -->
<div class="tv-body">
    @if($doctorQueues->isEmpty())
        <div class="tv-empty">
            <i class="fa-solid fa-hourglass-half"></i>
            <p>No active queue at this time. Please wait.</p>
        </div>
    @else
        <div class="tv-body-title"><i class="fa-solid fa-display"></i> &nbsp;Doctors on Duty</div>
        <div class="tv-grid">
            @foreach($doctorQueues as $dq)
            <div class="doctor-card">
                <!-- Doctor Header -->
                <div class="doctor-card-header">
                    <i class="fa-solid fa-stethoscope"></i>
                    <span class="doctor-name">Dr. {{ $dq['doctor']->first_name }} {{ $dq['doctor']->last_name }}</span>
                    @php $dqDept = $dept ?: $dq['doctor']->department; @endphp
                    @if($dqDept || $dq['doctor']->specialization)
                    <span style="font-size:0.85rem;color:var(--muted);margin-left:auto">{{ $dqDept?->name ?? $dq['doctor']->specialization }}@if($dqDept?->room) &middot; {{ $dqDept->room }}@endif</span>
                    @endif
                </div>

                <!-- Now Serving -->
                @if($dq['current'])
                <div class="now-serving-block">
                    <div class="now-serving-label">Now Serving</div>
                    <div class="now-serving-content">
                        <div class="now-token-badge">{{ $dq['current']->display }}</div>
                        <div>
                            <div class="now-patient-name">
                                {{ $dq['current']->patient->first_name }} {{ $dq['current']->patient->last_name }}
                            </div>
                            @if($dq['current']->purpose)
                            <div class="now-patient-meta"><i class="fa-solid fa-notes-medical"></i> {{ $dq['current']->purpose }}</div>
                            @endif
                            @if($dq['current']->priority === 'urgent')
                            <div class="now-patient-meta" style="color:var(--red);font-weight:700;margin-top:4px"><i class="fa-solid fa-circle-exclamation"></i> URGENT</div>
                            @endif
                        </div>
                    </div>
                </div>
                @else
                <div class="no-current">
                    <i class="fa-solid fa-hourglass" style="color:var(--border)"></i>
                    Waiting to call next patient
                </div>
                @endif

                <!-- Queue List -->
                <div class="queue-section">
                    <div class="queue-section-label">Next in Queue</div>
                    @forelse($dq['waiting'] as $token)
                    <div class="queue-item {{ $token->priority === 'urgent' ? 'urgent' : '' }}">
                        <div class="qi-token">{{ $token->display }}</div>
                        <div class="qi-name">{{ $token->patient->first_name }} {{ $token->patient->last_name }}</div>
                        <span class="qi-badge {{ $token->status === 'called' ? 'called' : 'waiting' }}">
                            {{ $token->status === 'called' ? 'Called' : 'Waiting' }}
                        </span>
                    </div>
                    @empty
                    <div class="queue-empty">No more patients waiting</div>
                    @endforelse
                </div>
            </div>
            @endforeach
        </div>
    @endif
</div>

<!-- FOOTER -->
<div class="tv-footer">
    James Heart Care Pvt. Ltd. &nbsp;·&nbsp; {{ setting('clinic_address', '') }}
    &nbsp;·&nbsp; Auto-refreshes every 30 seconds
</div>

<script>
// Live clock
function updateClock() {
    const now = new Date();
    document.getElementById('tv-clock').textContent =
        now.toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });
}
updateClock();
setInterval(updateClock, 1000);
</script>
</body>
</html>
