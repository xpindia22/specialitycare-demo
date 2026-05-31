{{-- FILE: resources/views/admissions/bill.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>IPD Bill — {{ $admission->ipd_no }}</title>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Times New Roman', serif; font-size: 11pt; color: #111; background: #fff; }
    @page { margin: 18mm 16mm 18mm 16mm; }
    @media print {
        .no-print { display: none !important; }
        body { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
    }
    .no-print { margin: 10px; }

    .letterhead { border-bottom: 3px double #0f1f3d; padding-bottom: 10px; margin-bottom: 14px; }
    .clinic-name { font-size: 18pt; font-weight: 700; color: #0f1f3d; }
    .clinic-sub  { font-size: 9pt; color: #555; margin-top: 2px; }
    .bill-title  { text-align: center; font-size: 14pt; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin: 12px 0 10px; border-bottom: 1px solid #ccc; padding-bottom: 8px; }

    .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0; margin-bottom: 14px; border: 1px solid #ccc; }
    .info-cell { padding: 5px 10px; border-bottom: 1px solid #eee; font-size: 10.5pt; }
    .info-cell:nth-child(odd) { border-right: 1px solid #eee; }
    .info-label { font-weight: 700; font-size: 9pt; text-transform: uppercase; color: #555; }

    table.charge-table { width: 100%; border-collapse: collapse; font-size: 10.5pt; margin-bottom: 16px; }
    table.charge-table th { background: #0f1f3d; color: #fff; padding: 6px 10px; text-align: left; font-size: 9pt; }
    table.charge-table th.right { text-align: right; }
    table.charge-table td { padding: 5px 10px; border-bottom: 1px solid #eee; vertical-align: top; }
    table.charge-table td.right { text-align: right; }
    table.charge-table tr:nth-child(even) td { background: #f9f9f9; }
    table.charge-table tfoot td { font-weight: 700; border-top: 2px solid #0f1f3d; padding: 7px 10px; }

    .section-head { background: #e8edf5; font-weight: 700; font-size: 10pt; padding: 5px 10px; border-left: 3px solid #0f1f3d; }

    .summary-box { display: flex; justify-content: flex-end; margin-bottom: 20px; }
    .summary-table { border: 1px solid #ccc; min-width: 260px; }
    .summary-table td { padding: 5px 14px; font-size: 10.5pt; }
    .summary-table .total-row td { font-size: 13pt; font-weight: 700; background: #0f1f3d; color: #fff; padding: 8px 14px; }

    .signature-row { display: flex; justify-content: space-between; margin-top: 30px; }
    .sig-box { text-align: center; width: 200px; border-top: 1px solid #333; padding-top: 6px; font-size: 9.5pt; }
    .footer-note { margin-top: 18px; font-size: 8.5pt; color: #777; border-top: 1px solid #ccc; padding-top: 6px; text-align: center; }

    .status-badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 9pt; font-weight: 700; }
    .status-admitted   { background: #dcfce7; color: #14532d; }
    .status-discharged { background: #dbeafe; color: #1e3a5f; }
</style>
</head>
<body>

<div class="no-print" style="background:#f0f4f8;padding:12px;display:flex;gap:10px;align-items:center;flex-wrap:wrap">
    <button onclick="window.print()" style="padding:8px 20px;background:#0f1f3d;color:#fff;border:none;border-radius:6px;cursor:pointer;font-size:14px">
        &#128438; Print / Save PDF
    </button>
    @if($balance > 0)
    <button onclick="document.getElementById('payModal').style.display='flex'" style="padding:8px 20px;background:#16a34a;color:#fff;border:none;border-radius:6px;cursor:pointer;font-size:14px;font-weight:700">
        &#128179; Record Payment &nbsp;<span style="background:rgba(255,255,255,.25);padding:2px 8px;border-radius:4px">₹{{ number_format($balance,2) }} due</span>
    </button>
    @else
    <span style="background:#dcfce7;color:#14532d;padding:8px 16px;border-radius:6px;font-weight:700">&#10003; Fully Paid</span>
    @endif
    <a href="{{ route('admissions.show', $admission) }}#tab-billing" style="color:#0ab4b4;text-decoration:none;padding:8px 0">&#8592; Back to Admission</a>
</div>

@if($balance > 0)
{{-- Record Payment Modal --}}
<div id="payModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center">
    <div style="background:#fff;border-radius:10px;padding:28px;width:420px;max-width:95vw">
        <div style="font-size:16px;font-weight:700;margin-bottom:16px;color:#0f1f3d">&#128179; Record Payment</div>
        <div style="background:#fef9ec;border:1px solid #fcd34d;padding:10px 14px;border-radius:6px;margin-bottom:16px;font-size:13px">
            Balance due: <strong>₹{{ number_format($balance,2) }}</strong>
            (Total ₹{{ number_format($total,2) }} &minus; Paid ₹{{ number_format($totalPaid,2) }})
        </div>
        <form method="POST" action="{{ route('admissions.deposits.store', $admission) }}">
            @csrf
            <div style="margin-bottom:12px">
                <label style="font-size:12px;font-weight:600;display:block;margin-bottom:4px">Amount (₹) *</label>
                <input type="number" name="amount" class="form-control" step="0.01" min="0.01"
                       value="{{ $balance }}" required style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:6px;font-size:14px">
            </div>
            <div style="margin-bottom:12px">
                <label style="font-size:12px;font-weight:600;display:block;margin-bottom:4px">Payment Method *</label>
                <select name="payment_method" required style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:6px;font-size:14px">
                    <option value="cash">Cash</option>
                    <option value="upi">UPI</option>
                    <option value="card">Card</option>
                    <option value="cheque">Cheque</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div style="margin-bottom:12px">
                <label style="font-size:12px;font-weight:600;display:block;margin-bottom:4px">Transaction Ref / Receipt No</label>
                <input type="text" name="transaction_ref" class="form-control" placeholder="Optional"
                       style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:6px;font-size:14px">
            </div>
            <div style="margin-bottom:16px">
                <label style="font-size:12px;font-weight:600;display:block;margin-bottom:4px">Notes</label>
                <input type="text" name="notes" placeholder="e.g. Final settlement at discharge"
                       style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:6px;font-size:14px">
            </div>
            <div style="display:flex;gap:10px">
                <button type="submit" style="flex:1;padding:10px;background:#16a34a;color:#fff;border:none;border-radius:6px;font-size:14px;font-weight:700;cursor:pointer">
                    Save Payment
                </button>
                <button type="button" onclick="document.getElementById('payModal').style.display='none'"
                        style="padding:10px 18px;background:#e5e7eb;border:none;border-radius:6px;font-size:14px;cursor:pointer">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>
@endif

{{-- Letterhead --}}
@if(setting('print_header_enabled','1') !== '0')
<div class="letterhead">
    <div class="clinic-name">{{ setting('clinic_name', 'Demo Clinic Pvt. Ltd.') }}</div>
    <div class="clinic-sub">{{ setting('clinic_address', '') }}</div>
    <div class="clinic-sub">{{ setting('clinic_phone', '') }}{{ setting('clinic_email') ? ' · ' . setting('clinic_email') : '' }}</div>
</div>
@endif

<div class="bill-title">Inpatient Bill</div>

{{-- Patient & Admission Info --}}
<div class="info-grid">
    <div class="info-cell"><div class="info-label">Patient Name</div>{{ $admission->patient->full_name }}</div>
    <div class="info-cell"><div class="info-label">IPD No.</div>{{ $admission->ipd_no }}</div>
    <div class="info-cell"><div class="info-label">JHC ID</div>{{ $admission->patient->jhc_id }}</div>
    <div class="info-cell"><div class="info-label">Status</div>
        <span class="status-badge status-{{ $admission->status }}">{{ $admission->status_label }}</span>
    </div>
    <div class="info-cell"><div class="info-label">Age / Gender</div>{{ $admission->patient->age }} / {{ ucfirst($admission->patient->gender) }}</div>
    <div class="info-cell"><div class="info-label">Ward / Bed</div>{{ $admission->ward->name }} — Bed {{ $admission->bed->bed_number }}</div>
    <div class="info-cell"><div class="info-label">Admission Date</div>{{ $admission->admission_date->format('d-m-Y  h:i A') }}</div>
    <div class="info-cell"><div class="info-label">Discharge Date</div>{{ $admission->discharge_date?->format('d-m-Y  h:i A') ?? 'Still Admitted' }}</div>
    <div class="info-cell"><div class="info-label">Admitting Doctor</div>{{ $admission->admittingDoctor->full_name }}</div>
    <div class="info-cell"><div class="info-label">Diagnosis</div>{{ $admission->provisional_diagnosis ?: '—' }}</div>
</div>

{{-- Charges by category --}}
@if($charges->isEmpty())
<p style="text-align:center;color:#999;margin:30px 0">No charges recorded.</p>
@else
<table class="charge-table">
    <thead>
        <tr>
            <th style="width:80px">Date</th>
            <th>Category</th>
            <th>Description</th>
            <th class="right" style="width:90px">Unit Price</th>
            <th class="right" style="width:60px">Qty</th>
            <th class="right" style="width:100px">Amount</th>
        </tr>
    </thead>
    <tbody>
        @php $currentCat = null; @endphp
        @foreach($charges as $c)
            @if($c->category !== $currentCat)
                @php $currentCat = $c->category; @endphp
                <tr>
                    <td colspan="6" class="section-head">
                        {{ \App\Models\IpdCharge::CATEGORIES[$c->category]['label'] ?? $c->category }}
                    </td>
                </tr>
            @endif
            <tr>
                <td>{{ $c->charge_date->format('d M') }}</td>
                <td></td>
                <td>{{ $c->description }}@if($c->notes)<br><small style="color:#777">{{ $c->notes }}</small>@endif</td>
                <td class="right">₹{{ number_format($c->unit_price, 2) }}</td>
                <td class="right">{{ rtrim(rtrim(number_format($c->quantity,2,'.',','),'0'),'.') }}</td>
                <td class="right">₹{{ number_format($c->amount, 2) }}</td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="5" style="text-align:right;background:#f5f5f5">Grand Total</td>
            <td class="right" style="font-size:13pt;background:#f5f5f5">₹{{ number_format($total, 2) }}</td>
        </tr>
    </tfoot>
</table>

{{-- Summary by category --}}
<div class="summary-box">
    <table class="summary-table">
        <tbody>
            @foreach($byCategory as $cat => $items)
            <tr>
                <td>{{ \App\Models\IpdCharge::CATEGORIES[$cat]['label'] ?? $cat }}</td>
                <td style="text-align:right">₹{{ number_format($items->sum('amount'), 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td>Total Amount</td>
                <td style="text-align:right">₹{{ number_format($total, 2) }}</td>
            </tr>
        </tfoot>
    </table>
</div>
@endif

{{-- Payments / Balance --}}
<div class="summary-box" style="margin-top:0">
    <table class="summary-table" style="min-width:320px">
        <tbody>
            <tr>
                <td>Total Charges</td>
                <td style="text-align:right">₹{{ number_format($total, 2) }}</td>
            </tr>
            @foreach($deposits as $dep)
            <tr>
                <td style="color:#16a34a">Payment — {{ strtoupper($dep->payment_method) }}
                    @if($dep->transaction_ref) <span style="font-size:9pt;color:#777">({{ $dep->transaction_ref }})</span>@endif
                    <span style="font-size:9pt;color:#777"> {{ $dep->created_at->format('d M Y') }}</span>
                </td>
                <td style="text-align:right;color:#16a34a">— ₹{{ number_format($dep->amount, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row" style="background:{{ $balance > 0 ? '#b91c1c' : '#16a34a' }} !important">
                <td>{{ $balance > 0 ? 'Balance Due' : 'Paid in Full' }}</td>
                <td style="text-align:right">₹{{ number_format($balance, 2) }}</td>
            </tr>
        </tfoot>
    </table>
</div>

<div class="signature-row">
    <div class="sig-box">Patient / Attendant Signature</div>
    <div class="sig-box">
        Authorised Signatory<br>{{ setting('clinic_name', 'Demo Clinic Pvt. Ltd.') }}
    </div>
</div>

@if(setting('print_footer_enabled','1') !== '0')
<div class="footer-note">
    Bill generated on {{ now()->format('d-m-Y h:i A') }} by {{ auth()->user()->name }} &nbsp;·&nbsp;
    {{ setting('clinic_name', 'Demo Clinic Pvt. Ltd.') }}
    &nbsp;·&nbsp; This is a computer-generated bill.
</div>
@endif

</body>
</html>
