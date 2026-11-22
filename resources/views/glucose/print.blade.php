<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Diabetes Diary — {{ $patient->full_name }}</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
@php
    $fmt = fn ($v) => $v === null || $v === '' ? '' : rtrim(rtrim(number_format((float) $v, 1), '0'), '.');
    $types = \App\Models\GlucoseLog::TYPES;
@endphp
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
.stat { border:1px solid #e2e8f0;border-radius:8px;padding:8px 12px;text-align:center;min-width:90px }
.stat .v { font-size:18px;font-weight:800 } .stat .k { font-size:10px;color:#64748b;text-transform:uppercase }
.advice { padding:7px 10px;border-radius:6px;margin-bottom:5px;font-size:12px;line-height:1.45 }
.warn { background:#fef2f2;border:1px solid #fecaca;color:#991b1b }
.good { background:#f0fdf4;border:1px solid #bbf7d0;color:#166534 }
.info { background:#f1f5f9;border:1px solid #e2e8f0;color:#334155 }
table { width:100%;border-collapse:collapse;margin-top:6px;font-size:12px; }
th,td { border:1px solid #e2e8f0;padding:6px 8px;text-align:center;vertical-align:top }
th { background:#f8fafc;color:#475569;font-weight:600;font-size:10px;text-transform:uppercase;letter-spacing:.3px }
td.l,th.l { text-align:left }
.chip { display:inline-block;border:1px solid;border-radius:999px;padding:0 7px;font-weight:700;font-size:11px;margin:1px }
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
        <div class="title">Diabetes Diary</div>
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
        <div class="stat"><div class="v">{{ $s['avg'] }}</div><div class="k">Avg mg/dL</div></div>
        <div class="stat"><div class="v">{{ $s['inPct'] }}%</div><div class="k">In target</div></div>
        <div class="stat"><div class="v">{{ $s['estA1c'] ? $s['estA1c'].'%' : '—' }}</div><div class="k">Est. HbA1c</div></div>
        <div class="stat"><div class="v">{{ $s['low'] }}</div><div class="k">Lows</div></div>
        <div class="stat"><div class="v">{{ $s['high'] }}</div><div class="k">Highs</div></div>
        <div class="stat"><div class="v">{{ $s['count'] }}</div><div class="k">Readings</div></div>
    </div>
@endif

@if(count($analysis['messages']))
    <h3>Advice</h3>
    @foreach($analysis['messages'] as $m)
        <div class="advice {{ $m['type'] }}">{{ $m['text'] }}</div>
    @endforeach
@endif

<h3>Day-by-day log</h3>
@if($days->isEmpty())
    <p style="color:#64748b">No entries recorded.</p>
@else
<table>
    <thead>
        <tr>
            <th class="l">Date</th>
            @foreach(\App\Models\GlucoseLog::GRID_COLUMNS as $col)<th>{{ $types[$col]['short'] }}</th>@endforeach
            <th class="l">Medicine / insulin / notes</th>
        </tr>
    </thead>
    <tbody>
    @foreach($days as $day)
        <tr>
            <td class="l">{{ \Carbon\Carbon::parse($day['date'])->format('d M Y') }}<br><span style="color:#94a3b8;font-size:10px">{{ \Carbon\Carbon::parse($day['date'])->format('D') }}</span></td>
            @foreach(\App\Models\GlucoseLog::GRID_COLUMNS as $col)
                <td>
                    @foreach($day['cells'][$col] as $r)
                        @php $c = $r->classification(); @endphp
                        <span class="chip" style="border-color:{{ $c['color'] }};color:{{ $c['color'] }}" title="{{ $r->logged_at->format('h:i A') }}">{{ $fmt($r->value) }}</span>
                    @endforeach
                    @if($day['cells'][$col]->isEmpty())—@endif
                </td>
            @endforeach
            <td class="l">
                @foreach($day['meds'] as $r)
                    <div><span style="color:#94a3b8;font-size:10px">{{ $r->logged_at->format('h:i A') }}</span>
                        {{ $r->medications }}@if($r->insulin_units !== null) <strong>{{ $fmt($r->insulin_units) }} U insulin</strong>@endif</div>
                @endforeach
                @foreach($day['hba1c'] as $r)
                    @php $c = $r->classification(); @endphp
                    <div style="color:{{ $c['color'] }};font-weight:700">HbA1c {{ $fmt($r->value) }}% <span style="font-weight:400;color:#64748b">({{ $c['label'] }})</span></div>
                @endforeach
                @foreach($day['notes'] as $r)
                    <div style="color:#64748b;font-size:11px">{{ $r->notes }}@if($r->symptoms) · {{ $r->symptoms }}@endif</div>
                @endforeach
                @if($day['meds']->isEmpty() && $day['hba1c']->isEmpty() && $day['notes']->isEmpty())—@endif
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
@endif

<div class="disclaimer">
    Targets used: fasting / before-meal 80–130 mg/dL, 2-hour after-meal under 180 mg/dL, HbA1c under 7%. Auto-generated guidance only — it does not replace the treating doctor's advice. Individual targets may differ.
</div>

<div class="footer">{{ setting('clinic_name', 'James Heart Care Pvt. Ltd.') }} — Diabetes Diary · Generated {{ now()->format('d M Y, h:i A') }}</div>
</body>
</html>
