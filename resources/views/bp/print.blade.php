<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>BP Diary — {{ $patient->full_name }}</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:'DM Sans',sans-serif; font-size:13px; color:#1a2942; background:#fff; max-width:840px; margin:0 auto; padding:24px 28px; }
.header { display:flex;justify-content:space-between;align-items:flex-start;border-bottom:3px solid #1a2942;padding-bottom:12px;margin-bottom:14px; }
.clinic { font-size:20px;font-weight:800;color:#1a2942 }
.title { font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:1.5px;color:#0ab4b4 }
.meta { background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:10px 14px;margin-bottom:12px;display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:10px;font-size:12px; }
.meta strong { display:block;font-size:10px;text-transform:uppercase;letter-spacing:.4px;color:#64748b;margin-bottom:1px;font-weight:600 }
h3 { font-size:12px;text-transform:uppercase;letter-spacing:.6px;color:#475569;margin:16px 0 6px }
.stats { display:flex;gap:10px;flex-wrap:wrap;margin-bottom:8px }
.stat { border:1px solid #e2e8f0;border-radius:8px;padding:8px 12px;text-align:center;min-width:84px }
.stat .v { font-size:17px;font-weight:800 } .stat .k { font-size:10px;color:#64748b;text-transform:uppercase }
.advice { padding:7px 10px;border-radius:6px;margin-bottom:5px;font-size:12px;line-height:1.45 }
.warn { background:#fef2f2;border:1px solid #fecaca;color:#991b1b }
.good { background:#f0fdf4;border:1px solid #bbf7d0;color:#166534 }
.info { background:#f1f5f9;border:1px solid #e2e8f0;color:#334155 }
table { width:100%;border-collapse:collapse;margin-top:6px;font-size:12px; }
th,td { border:1px solid #e2e8f0;padding:6px 8px;text-align:left;vertical-align:top }
th { background:#f8fafc;color:#475569;font-weight:600;font-size:10px;text-transform:uppercase;letter-spacing:.3px }
.chip { display:inline-block;border:1px solid;border-radius:999px;padding:0 8px;font-weight:700;font-size:11px }
.disclaimer { font-size:10.5px;color:#94a3b8;margin-top:12px;line-height:1.5 }
.footer { margin-top:16px;font-size:11px;color:#64748b;text-align:center;border-top:1px solid #e2e8f0;padding-top:10px }
.no-print { text-align:center;margin-bottom:12px;padding:10px;background:#f0f4f8;border-radius:8px }
.btn { background:#1a2942;color:#fff;border:none;padding:8px 18px;border-radius:6px;font-size:12px;cursor:pointer;font-weight:600 }
.btn-grey { background:#e2e8f0;color:#1a2942;margin-left:8px }
@media print { .no-print { display:none } body { padding:14px 18px } }
</style>
</head>
<body>
<div class="no-print">
    <button class="btn" onclick="window.print()">&#128424; Print</button>
    <button class="btn btn-grey" onclick="window.close()">Close</button>
</div>

@if(setting('print_header_enabled','1') !== '0')
<div class="header">
    <div>
        <div class="clinic">{{ setting('clinic_name', 'James Heart Care Pvt. Ltd.') }}</div>
        <div style="font-size:11px;color:#64748b;margin-top:2px">{{ setting('clinic_address', '') }}</div>
    </div>
    <div style="text-align:right">
        <div class="title">Blood-Pressure Diary</div>
        <div style="font-size:11px;color:#64748b;margin-top:2px">Issued {{ now()->format('d M Y') }}</div>
    </div>
</div>
@endif

<div class="meta">
    <div><strong>Patient</strong>{{ $patient->full_name }}</div>
    <div><strong>ID</strong>{{ $patient->jhc_id }}</div>
    <div><strong>Age / Sex</strong>{{ $patient->age }} / {{ $patient->gender }}</div>
    <div><strong>Phone</strong>{{ $patient->phone ?: '—' }}</div>
</div>

@if($analysis['stats'])
    @php $s = $analysis['stats']; @endphp
    <h3>Summary{{ $s['windowDays'] ? ' (last '.$s['windowDays'].' days)' : '' }}</h3>
    <div class="stats">
        <div class="stat"><div class="v">{{ $s['avgSys'] }}/{{ $s['avgDia'] }}</div><div class="k">Avg mmHg</div></div>
        <div class="stat"><div class="v">{{ $s['goalPct'] }}%</div><div class="k">&lt;140/90</div></div>
        <div class="stat"><div class="v">{{ $s['stage2'] }}</div><div class="k">Stage 2</div></div>
        <div class="stat"><div class="v">{{ $s['crisis'] }}</div><div class="k">Crisis</div></div>
        <div class="stat"><div class="v">{{ $s['low'] }}</div><div class="k">Low</div></div>
        <div class="stat"><div class="v">{{ $s['count'] }}</div><div class="k">Readings</div></div>
    </div>
@endif

@if(count($analysis['messages']))
    <h3>Advice</h3>
    @foreach($analysis['messages'] as $m)
        <div class="advice {{ $m['type'] }}">{{ $m['text'] }}</div>
    @endforeach
@endif

<h3>Readings</h3>
@if($logs->isEmpty())
    <p style="color:#64748b">No entries recorded.</p>
@else
<table>
    <thead><tr><th>Date &amp; time</th><th>BP</th><th>Category</th><th>Pulse</th><th>Medicine</th><th>Symptoms / notes</th></tr></thead>
    <tbody>
    @foreach($logs as $r)
        @php $c = $r->classification(); @endphp
        <tr>
            <td>{{ $r->logged_at->format('d M Y, h:i A') }}</td>
            <td>@if($r->systolic)<span class="chip" style="border-color:{{ $c['color'] }};color:{{ $c['color'] }}">{{ $r->reading() }}</span>@else—@endif</td>
            <td style="color:{{ $c['color'] }};font-weight:600">{{ $c['status']==='none' ? '—' : $c['label'] }}</td>
            <td>{{ $r->pulse ?: '—' }}</td>
            <td>{{ $r->medications ?: '—' }}</td>
            <td>{{ $r->symptoms }}@if($r->symptoms && $r->notes) · @endif{{ $r->notes }}@if(!$r->symptoms && !$r->notes)—@endif</td>
        </tr>
    @endforeach
    </tbody>
</table>
@endif

<div class="disclaimer">
    Categories use ACC/AHA ranges (normal &lt;120/80, elevated 120–129, stage 1 130–139/80–89, stage 2 ≥140/90, crisis ≥180/120, low &lt;90/60). Guidance only — it does not replace the treating doctor's advice.
</div>
<div class="footer">{{ setting('clinic_name', 'James Heart Care Pvt. Ltd.') }} — BP Diary · Generated {{ now()->format('d M Y, h:i A') }}</div>
</body>
</html>
