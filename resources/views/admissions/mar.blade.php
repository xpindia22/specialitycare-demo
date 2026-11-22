{{-- FILE: resources/views/admissions/mar.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Medicine Chart — {{ $admission->ipd_no }} — {{ \Carbon\Carbon::parse($date)->format('d M Y') }}</title>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: Arial, sans-serif; font-size: 10pt; color: #111; background: #fff; }
    @page { margin: 14mm 12mm; size: A4 landscape; }
    @media print {
        .no-print { display: none !important; }
        body { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
    }
    .no-print { margin: 8px; }

    .letterhead { border-bottom: 2px solid #0f1f3d; padding-bottom: 8px; margin-bottom: 10px; display:flex; justify-content:space-between; align-items:flex-end; }
    .clinic-name { font-size: 15pt; font-weight: 700; color: #0f1f3d; }
    .doc-title { font-size: 13pt; font-weight: 700; text-align:center; text-transform:uppercase; letter-spacing:.8px; margin-bottom:8px; }
    .info-row { display:grid; grid-template-columns:repeat(4,1fr); gap:8px; margin-bottom:10px; font-size:9.5pt; border:1px solid #ccc; }
    .info-cell { padding:4px 8px; border-right:1px solid #eee; }
    .info-cell:last-child { border-right:none; }
    .info-label { font-weight:700; font-size:8.5pt; text-transform:uppercase; color:#555; }

    table.mar-table { width:100%; border-collapse:collapse; font-size:9pt; }
    table.mar-table th { background:#0f1f3d; color:#fff; padding:5px 8px; text-align:center; white-space:nowrap; }
    table.mar-table th.left { text-align:left; }
    table.mar-table td { padding:5px 8px; border:1px solid #ddd; vertical-align:middle; }
    table.mar-table td.drug { font-weight:700; }
    table.mar-table tr:nth-child(even) td { background:#fafafa; }

    .dose-cell { text-align:center; min-width:44px; }
    .status-G { background:#dcfce7; color:#14532d; border-radius:4px; padding:2px 4px; font-weight:700; font-size:8pt; }
    .status-M { background:#fee2e2; color:#991b1b; border-radius:4px; padding:2px 4px; font-weight:700; font-size:8pt; }
    .status-H { background:#fef3c7; color:#92400e; border-radius:4px; padding:2px 4px; font-weight:700; font-size:8pt; }
    .status-R { background:#e0e7ff; color:#3730a3; border-radius:4px; padding:2px 4px; font-weight:700; font-size:8pt; }

    .legend { display:flex; gap:20px; margin-top:10px; font-size:8.5pt; }
    .legend span { display:flex; align-items:center; gap:4px; }
    .sig-row { display:flex; justify-content:flex-end; margin-top:20px; }
    .sig-box { text-align:center; width:180px; border-top:1px solid #333; padding-top:4px; font-size:9pt; }
</style>
</head>
<body>
<div class="no-print" style="background:#f0f4f8;padding:10px;display:flex;gap:10px">
    <button onclick="window.print()" style="padding:7px 18px;background:#0f1f3d;color:#fff;border:none;border-radius:5px;cursor:pointer">Print Medicine Chart</button>
    <a href="{{ route('admissions.show', $admission) }}#tab-mar" style="color:#0ab4b4;text-decoration:none;line-height:2">&#8592; Back</a>

    {{-- Date picker --}}
    <form method="GET" style="margin-left:auto;display:flex;gap:8px;align-items:center">
        <label style="font-size:13px">Date:</label>
        <input type="date" name="date" value="{{ $date }}" style="padding:4px 8px;border:1px solid #ccc;border-radius:4px">
        <button type="submit" style="padding:5px 14px;background:#0ab4b4;color:#fff;border:none;border-radius:4px;cursor:pointer">Go</button>
    </form>
</div>

<div class="letterhead">
    <div>
        <div class="clinic-name">{{ setting('clinic_name', 'James Heart Care Pvt. Ltd.') }}</div>
        <div style="font-size:9pt;color:#555">{{ setting('clinic_address','') }}</div>
    </div>
    <div style="text-align:right;font-size:9.5pt;color:#555">
        Medicine Chart Date: <strong>{{ \Carbon\Carbon::parse($date)->format('d M Y, D') }}</strong>
    </div>
</div>

<div class="doc-title">Medicine Chart</div>

<div class="info-row">
    <div class="info-cell"><div class="info-label">Patient</div>{{ $admission->patient->full_name }}</div>
    <div class="info-cell"><div class="info-label">JHC ID / IPD No.</div>{{ $admission->patient->jhc_id }} / {{ $admission->ipd_no }}</div>
    <div class="info-cell"><div class="info-label">Ward / Bed</div>{{ $admission->ward->name }} — Bed {{ $admission->bed->bed_number }}</div>
    <div class="info-cell"><div class="info-label">Doctor</div>{{ $admission->admittingDoctor->full_name }}</div>
</div>

@php
    $allTimes = $prescriptions->flatMap(fn($rx) => $rx->effective_times)->unique()->sort()->values();
@endphp

<table class="mar-table">
    <thead>
        <tr>
            <th class="left" style="width:200px">Drug / Dose / Route</th>
            <th class="left" style="width:80px">Frequency</th>
            @foreach($allTimes as $t)
            <th>{{ \Carbon\Carbon::createFromFormat('H:i',$t)->format('h:i A') }}</th>
            @endforeach
            <th class="left" style="width:140px">Remarks</th>
        </tr>
    </thead>
    <tbody>
        @forelse($prescriptions as $rx)
        @php $logs = $rx->logs->keyBy('scheduled_time'); @endphp
        <tr>
            <td class="drug">
                {{ $rx->drug_name }}<br>
                <span style="font-weight:400;font-size:8.5pt;color:#555">{{ $rx->dose }} · {{ $rx->route }}</span>
                @if($rx->instructions)<br><span style="font-style:italic;font-size:8pt;color:#777">{{ $rx->instructions }}</span>@endif
            </td>
            <td>{{ $rx->frequency }}</td>
            @foreach($allTimes as $t)
            <td class="dose-cell">
                @if(in_array($t, $rx->effective_times ?? []))
                    @php $log = $logs->get($t); @endphp
                    @if($log)
                        @php $k = strtoupper(substr($log->status,0,1)); @endphp
                        <span class="status-{{ $k }}">{{ $k }}</span>
                        @if($log->actual_time)<div style="font-size:7.5pt;color:#777">{{ $log->actual_time }}</div>@endif
                    @else
                        <span style="color:#ccc">&#9744;</span>
                    @endif
                @else
                    <span style="color:#eee">—</span>
                @endif
            </td>
            @endforeach
            <td style="font-size:8.5pt;color:#555">
                @php $remarks = $rx->logs->pluck('remarks')->filter()->join('; '); @endphp
                {{ $remarks ?: '' }}
            </td>
        </tr>
        @empty
        <tr><td colspan="{{ 2 + $allTimes->count() + 1 }}" style="text-align:center;padding:20px;color:#999">No medications for this date.</td></tr>
        @endforelse
    </tbody>
</table>

<div class="legend">
    <span><span class="status-G">G</span> Given</span>
    <span><span class="status-M">M</span> Missed</span>
    <span><span class="status-H">H</span> Held</span>
    <span><span class="status-R">R</span> Refused</span>
    <span style="color:#aaa">&#9744; Not yet recorded</span>
</div>

<div class="sig-row">
    <div class="sig-box">Nurse Signature<br><span style="font-size:8pt;color:#777">{{ setting('clinic_name','James Heart Care Pvt. Ltd.') }}</span></div>
</div>

</body>
</html>
