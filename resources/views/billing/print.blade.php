{{-- FILE: resources/views/billing/print.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Bill — {{ $bill->bill_no }}</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        * { margin:0;padding:0;box-sizing:border-box; }
        body { font-family:'DM Sans',sans-serif;font-size:13px;color:#1a2942;background:#fff;padding:24px;max-width:800px;margin:0 auto; }
        .bill-header { display:flex;justify-content:space-between;align-items:flex-start;padding-bottom:16px;border-bottom:3px solid #0ab4b4;margin-bottom:16px; }
        .clinic-info h1 { font-size:20px;font-weight:700;color:#0f1f3d; }
        .clinic-info p { font-size:11.5px;color:#64748b;margin-top:3px; }
        .bill-title { text-align:right; }
        .bill-title h2 { font-size:26px;font-weight:700;color:#0ab4b4;letter-spacing:-0.5px; }
        .bill-title .bill-no { font-family:'DM Mono',monospace;font-size:12px;color:#64748b; }
        .bill-title .bill-status { display:inline-block;padding:4px 12px;border-radius:20px;font-size:11px;font-weight:600;margin-top:4px; }
        .status-paid { background:#dcfce7;color:#166534; }
        .status-partial { background:#fef3c7;color:#92400e; }
        .status-pending { background:#fee2e2;color:#991b1b; }
        .meta-grid { display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;background:#f8fafc;padding:14px;border-radius:8px; }
        .meta-group { }
        .meta-label { font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#64748b;margin-bottom:3px; }
        .meta-val { font-size:13px;font-weight:600;color:#1a2942; }
        table.items { width:100%;border-collapse:collapse;margin-bottom:16px; }
        table.items th { background:#0f1f3d;color:#fff;padding:8px 12px;text-align:left;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.3px; }
        table.items th.text-right, table.items td.text-right { text-align:right; }
        table.items td { padding:9px 12px;border-bottom:1px solid #e2e8f0;font-size:12.5px; }
        table.items tr:last-child td { border-bottom:none; }
        .totals-section { display:flex;justify-content:flex-end;margin-bottom:16px; }
        .totals-table { width:240px;border-collapse:collapse; }
        .totals-table td { padding:5px 0;font-size:13px; }
        .totals-table td:last-child { text-align:right;font-weight:600; }
        .totals-table .grand-total td { font-size:16px;font-weight:700;border-top:2px solid #1a2942;padding-top:8px; }
        .payment-section { background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:12px 16px;margin-bottom:16px; }
        .payment-section h4 { font-size:12px;color:#166534;font-weight:700;margin-bottom:8px; }
        .footer-bar { border-top:2px solid #e2e8f0;padding-top:14px;display:flex;justify-content:space-between;align-items:flex-end;font-size:11px;color:#64748b; }
        .sig-line { width:180px;border-top:1px solid #1a2942;padding-top:6px;text-align:center;font-size:11px;color:#64748b;margin-top:40px; }
        @media print { .no-print { display:none; } }
    </style>
</head>
<body>

<div class="no-print" style="text-align:center;margin-bottom:16px">
    <button onclick="window.print()" style="background:#0ab4b4;color:#fff;border:none;padding:10px 24px;border-radius:7px;font-size:13px;font-weight:600;cursor:pointer">🖨 Print Bill</button>
    <button onclick="window.close()" style="background:#e2e8f0;color:#1a2942;border:none;padding:10px 24px;border-radius:7px;font-size:13px;font-weight:600;cursor:pointer;margin-left:10px">Close</button>
</div>

@if(setting('print_header_enabled','1') !== '0')
<div class="bill-header">
    <div class="clinic-info">
        <h1>{{ setting('clinic_name', 'SpecialityCare Hospital') }}</h1>
        <p>{{ setting('clinic_address') }}</p>
        <p>📞 {{ setting('clinic_phone') }} &nbsp;·&nbsp; {{ setting('clinic_email') }}</p>
        <p>GST/Reg No.: {{ setting('clinic_reg_no') }}</p>
    </div>
    <div class="bill-title">
        <h2>INVOICE</h2>
        <div class="bill-no">{{ $bill->bill_no }}</div>
        <div>
            <span class="bill-status status-{{ strtolower($bill->status) === 'paid' ? 'paid' : (strtolower($bill->status) === 'partial' ? 'partial' : 'pending') }}">
                {{ strtoupper($bill->status) }}
            </span>
        </div>
    </div>
</div>
@endif

<div class="meta-grid">
    <div class="meta-group">
        <div class="meta-label">Billed To</div>
        <div class="meta-val">{{ $bill->patient->full_name }}</div>
        <div style="font-size:11.5px;color:#64748b;margin-top:3px;font-family:'DM Mono',monospace">{{ $bill->patient->jhc_id }}</div>
        <div style="font-size:11.5px;color:#64748b">{{ $bill->patient->phone }}</div>
        <div style="font-size:11.5px;color:#64748b">{{ $bill->patient->city }}</div>
    </div>
    <div class="meta-group" style="text-align:right">
        <div class="meta-label">Bill Details</div>
        <div class="meta-val">Date: {{ $bill->bill_date->format('d M Y') }}</div>
        @if($bill->encounter)
        <div style="font-size:11.5px;color:#64748b">Encounter: {{ $bill->encounter->encounter_no }}</div>
        <div style="font-size:11.5px;color:#64748b">Doctor: {{ $bill->encounter->doctor->full_name }}</div>
        @endif
        <div style="font-size:11.5px;color:#64748b">{{ $bill->payment_method ?: '' }}</div>
    </div>
</div>

<!-- Items -->
<table class="items">
    <thead>
        <tr>
            <th style="width:30px">#</th>
            <th>Description</th>
            <th>Category</th>
            <th class="text-right" style="width:50px">Qty</th>
            <th class="text-right" style="width:90px">Unit Price</th>
            <th class="text-right" style="width:80px">Discount</th>
            <th class="text-right" style="width:90px">Amount</th>
        </tr>
    </thead>
    <tbody>
    @foreach($bill->items as $i => $item)
    <tr>
        <td>{{ $i+1 }}</td>
        <td style="font-weight:600">{{ $item->item_name }}</td>
        <td style="color:#64748b;font-size:12px">{{ $item->item_type }}</td>
        <td class="text-right">{{ $item->quantity }}</td>
        <td class="text-right">₹{{ number_format($item->unit_price, 2) }}</td>
        <td class="text-right" style="color:#e84c4c">{{ $item->discount > 0 ? '-₹'.number_format($item->discount, 2) : '—' }}</td>
        <td class="text-right" style="font-weight:600">₹{{ number_format($item->total, 2) }}</td>
    </tr>
    @endforeach
    </tbody>
</table>

<!-- Totals -->
<div class="totals-section">
    <table class="totals-table">
        <tr><td style="color:#64748b">Subtotal</td><td>₹{{ number_format($bill->subtotal, 2) }}</td></tr>
        @if($bill->discount_amount > 0)
        <tr><td style="color:#e84c4c">Discount ({{ $bill->discount_percent }}%)</td><td style="color:#e84c4c">-₹{{ number_format($bill->discount_amount, 2) }}</td></tr>
        @endif
        @if($bill->tax_amount > 0)
        <tr><td style="color:#64748b">Tax</td><td>₹{{ number_format($bill->tax_amount, 2) }}</td></tr>
        @endif
        <tr class="grand-total"><td>Total</td><td>₹{{ number_format($bill->total_amount, 2) }}</td></tr>
        <tr><td style="color:#27c47a;padding-top:4px">Paid</td><td style="color:#27c47a">₹{{ number_format($bill->paid_amount, 2) }}</td></tr>
        @if($bill->due_amount > 0)
        <tr><td style="color:#e84c4c;font-weight:700">Due</td><td style="color:#e84c4c;font-weight:700">₹{{ number_format($bill->due_amount, 2) }}</td></tr>
        @endif
    </table>
</div>

<!-- Payments -->
@if($bill->payments->count())
<div class="payment-section">
    <h4>✅ Payment History</h4>
    @foreach($bill->payments as $pay)
    <div style="display:flex;justify-content:space-between;font-size:12px;padding:3px 0">
        <span>{{ $pay->payment_date->format('d M Y') }} — {{ $pay->payment_method }}{{ $pay->transaction_ref ? ' ('.$pay->transaction_ref.')' : '' }}</span>
        <strong style="color:#166534">₹{{ number_format($pay->amount, 2) }}</strong>
    </div>
    @endforeach
</div>
@endif

@if($bill->notes)
<div style="font-size:12px;color:#64748b;margin-bottom:14px"><strong>Notes:</strong> {{ $bill->notes }}</div>
@endif

@if(setting('print_footer_enabled','1') !== '0')
<div class="footer-bar">
    <div>
        <div class="sig-line">Authorised Signatory</div>
    </div>
    <div style="text-align:right">
        <div>Generated: {{ now()->format('d M Y, h:i A') }}</div>
        <div style="font-family:'DM Mono',monospace;margin-top:3px">{{ $bill->bill_no }}</div>
        <div style="font-size:10px;color:#94a3b8;margin-top:4px">This is a computer-generated invoice</div>
    </div>
</div>
@endif
</body>
</html>
