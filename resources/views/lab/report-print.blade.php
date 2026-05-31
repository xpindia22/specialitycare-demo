{{-- FILE: resources/views/lab/report-print.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Lab Report — {{ $order->order_no }}</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        * { margin:0;padding:0;box-sizing:border-box; }
        html,body { height:100%; }
        /* Full-height flex column so the footer can be pushed to the foot of the
           page even when the report body is short (margin-top:auto on footer). */
        body { font-family:'DM Sans',sans-serif;font-size:13px;color:#1a2942;background:#fff;padding:24px;max-width:800px;margin:0 auto;display:flex;flex-direction:column;min-height:100vh; }
        .report-header { border-bottom:3px solid #0ab4b4;padding-bottom:14px;margin-bottom:14px;display:flex;justify-content:space-between; }
        .clinic-name { font-size:20px;font-weight:700;color:#0f1f3d; }
        .clinic-sub { font-size:11.5px;color:#64748b; }
        .report-title { text-align:right; }
        .report-title h2 { font-size:20px;font-weight:700;color:#0f1f3d; }
        .patient-bar { background:#0f1f3d;color:#fff;padding:10px 16px;border-radius:8px;display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:12px;margin-bottom:14px;font-size:12px; }
        .patient-bar div span { display:block;opacity:.6;font-size:10px;text-transform:uppercase;letter-spacing:.4px;margin-bottom:2px; }
        .test-section { margin-bottom:14px; }
        .test-section h3 { font-size:13px;font-weight:700;padding:7px 12px;background:#f1f5f9;border-left:4px solid #0ab4b4; }
        table.results { width:100%;border-collapse:collapse;font-size:12.5px; }
        table.results th { padding:7px 10px;background:#f8fafc;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.3px;color:#64748b;border-bottom:2px solid #e2e8f0; }
        table.results td { padding:8px 10px;border-bottom:1px solid #f1f5f9; }
        .flag-H,.flag-HH,.flag-POS,.flag-ABN { color:#c02020;font-weight:700;background:#fff1f1; }
        .flag-L,.flag-LL { color:#0369a1;font-weight:700;background:#eff6ff; }
        .flag-N,.flag-NEG { color:#15803d;font-weight:700; }
        /* margin-top:auto eats the leftover vertical space, pinning the footer to
           the bottom of the page; the top border keeps separation when content is long. */
        .report-footer { border-top:1px solid #e2e8f0;margin-top:auto;padding-top:14px;display:flex;justify-content:space-between;font-size:11px;color:#64748b; }
        .sig-line { width:160px;border-top:1px solid #1a2942;padding-top:5px;text-align:center;margin-top:40px; }
        @media print {
            @page { size: A4; margin: 12mm; }
            html,body { height:auto; }
            body { padding:0;min-height:100vh; }   /* @page handles margins; fill the printable page so the footer sits at its foot */
            .no-print { display:none; }
        }
    </style>
</head>
<body>
<div class="no-print" style="text-align:center;margin-bottom:16px">
    <button onclick="window.print()" style="background:#0ab4b4;color:#fff;border:none;padding:10px 24px;border-radius:7px;font-size:13px;font-weight:600;cursor:pointer">🖨 Print Report</button>
</div>
@if(setting('print_header_enabled','1') !== '0')
<div class="report-header">
    <div><div class="clinic-name">{{ setting('clinic_name', 'SpecialityCare Hospital') }}</div>
    <div class="clinic-sub">{{ setting('clinic_address') }}</div>
    <div class="clinic-sub">📞 {{ setting('clinic_phone') }}</div></div>
    <div class="report-title"><h2>LAB REPORT</h2><div style="font-family:'DM Mono',monospace;font-size:11px;color:#64748b">{{ $order->order_no }}</div>
    <div style="font-size:11px;color:#64748b">{{ $order->ordered_date->format('d M Y') }}</div></div>
</div>
@endif
<div class="patient-bar">
    <div><span>Patient Name</span>{{ $order->patient->full_name }}</div>
    <div><span>JHC ID</span><span style="font-family:'DM Mono',monospace">{{ $order->patient->jhc_id }}</span></div>
    <div><span>Age / Gender</span>{{ $order->patient->age }} / {{ $order->patient->gender }}</div>
    <div><span>Blood Group</span>{{ $order->patient->blood_group ?: '—' }}</div>
</div>
<div class="test-section">
    <table class="results">
        <thead><tr><th>Test Name</th><th>Result</th><th>Normal Range</th><th>Unit</th><th>Flag</th><th>Remarks</th></tr></thead>
        <tbody>
        @foreach($order->items as $item)
        @php
            $flagCode = $item->abnormal_flag ?: 'N';
            $flagLabels = ['N'=>'Normal','H'=>'High','L'=>'Low','HH'=>'Critical High','LL'=>'Critical Low','POS'=>'Positive','NEG'=>'Negative','ABN'=>'Abnormal'];
            $isCritical = in_array($flagCode, ['HH','LL'], true);
            $isHigh     = in_array($flagCode, ['H','POS','ABN'], true);
            $isLow      = $flagCode === 'L';
            $valStyle   = '';
            if ($isCritical) {
                $valStyle = 'color:#c02020;font-weight:800;background:#fff1f1;padding:2px 6px;border-radius:4px;';
            } elseif ($isHigh) {
                $valStyle = 'color:#c02020;font-weight:700;';
            } elseif ($isLow) {
                $valStyle = 'color:#0369a1;font-weight:700;';
            }
        @endphp
        <tr>
            <td style="font-weight:600{{ $isCritical ? ';font-weight:800' : '' }}">{{ $item->test?->name ?? $item->panel?->name ?? '—' }}</td>
            <td class="flag-{{ $flagCode }}" style="font-size:14px;{{ $valStyle }}">{{ $item->result_value ?: '—' }}</td>
            <td>{{ $item->test?->normal_range ?: '—' }}</td>
            <td>{{ $item->test?->unit ?: '—' }}</td>
            <td class="flag-{{ $flagCode }}" style="{{ $isCritical ? 'font-weight:800' : '' }}">{{ $flagLabels[$flagCode] ?? 'Review' }}</td>
            <td style="font-size:11px;color:#64748b">{{ $item->remarks ?: '—' }}</td>
        </tr>
        @endforeach
        </tbody>
    </table>
</div>
@if($order->notes)<div style="font-size:12px;padding:8px;background:#f8fafc;border-radius:6px;margin-bottom:10px"><strong>Lab Notes:</strong> {{ $order->notes }}</div>@endif
<div class="report-footer">
    <div><div class="sig-line">Lab Technician Signature</div></div>
    <div style="text-align:right">
        <div>Generated: {{ now()->format('d M Y, h:i A') }}</div>
        <div style="font-family:'DM Mono',monospace;margin-top:3px">{{ $order->order_no }}</div>
        <div style="font-size:10px;color:#94a3b8;margin-top:4px">Computer-generated report</div>
    </div>
</div>
</body>
</html>
