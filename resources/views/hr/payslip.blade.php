<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Salary Slip — {{ $run->subject_name }} · {{ $run->month_label }}</title>
<style>
body { font-family: 'DM Sans', Arial, sans-serif; color:#1a2942; margin:0; padding:24px; font-size:13px; line-height:1.5; }
.slip { max-width:780px; margin:0 auto; border:1px solid #e2e8f0; border-radius:8px; }
.slip-header { background:#0f1f3d; color:#fff; padding:18px 22px; border-radius:8px 8px 0 0; }
.slip-header h1 { font-size:20px; font-weight:700; margin:0; }
.slip-header .sub { font-size:12px; opacity:0.8; margin-top:2px; }
.slip-body { padding:22px; }
.row { display:flex; justify-content:space-between; padding:6px 0; border-bottom:1px dashed #e2e8f0; }
.row:last-child { border-bottom:none; }
.row .lbl { color:#64748b; }
.row .val { font-weight:600; }
.section { margin-bottom:18px; }
.section h3 { font-size:13px; text-transform:uppercase; letter-spacing:0.5px; color:#64748b; margin:0 0 8px; padding-bottom:6px; border-bottom:1px solid #e2e8f0; }
.totals { background:#f8fafc; border-radius:6px; padding:12px 16px; margin-top:14px; }
.net { background:#0ab4b4; color:#fff; padding:14px 18px; border-radius:6px; display:flex; justify-content:space-between; font-size:16px; font-weight:700; margin-top:14px; }
table { width:100%; border-collapse:collapse; font-size:12px; }
th, td { padding:6px 8px; text-align:left; }
th { background:#f1f5f9; font-weight:600; }
tr + tr td { border-top:1px solid #e2e8f0; }
.right { text-align:right; }
.muted { color:#64748b; font-size:11px; }
.btn-print { display:inline-block; background:#0ab4b4; color:#fff; padding:8px 16px; border-radius:6px; text-decoration:none; font-weight:600; margin-bottom:14px; cursor:pointer; border:none; font-family:inherit; font-size:13px; }
@media print { .btn-print { display:none; } body { padding:0; } .slip { border:none; } }
</style>
</head>
<body>
<div style="max-width:780px;margin:0 auto">
<button class="btn-print" onclick="window.print()"><i>🖨</i> Print</button>
<div class="slip">
    <div class="slip-header">
        <h1>{{ $clinic['name'] ?? 'Salary Slip' }}</h1>
        <div class="sub">{{ $clinic['address'] ?? '' }} {{ $clinic['phone'] ? ' · '.$clinic['phone'] : '' }}</div>
    </div>
    <div class="slip-body">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:18px">
            <div>
                <div style="font-size:18px;font-weight:700">Salary Slip</div>
                <div class="muted">For pay period</div>
                <div style="font-size:15px;font-weight:600;color:#0ab4b4">{{ $run->month_label }}</div>
            </div>
            <div style="text-align:right">
                <div class="muted">Generated</div>
                <div style="font-weight:600">{{ now()->format('d M Y') }}</div>
            </div>
        </div>

        @php
            $subj = $run->subject();
            $isDoc = (bool) $run->doctor_id;
            $codeLabel = $isDoc ? 'Doctor ID' : 'Staff ID';
            $codeValue = $isDoc ? ($subj->doctor_id ?? '—') : ($subj->staff_id ?? '—');
            $designation = $isDoc ? ($subj->specialization ?: '—') : ($subj->designation ?: '—');
            $joinDate = $isDoc ? $subj?->created_at : $subj?->joining_date;
        @endphp
        <div class="section">
            <h3>{{ $isDoc ? 'Doctor' : 'Employee' }}</h3>
            <div class="row"><span class="lbl">Name</span><span class="val">{{ $run->subject_name }}</span></div>
            <div class="row"><span class="lbl">{{ $codeLabel }}</span><span class="val">{{ $codeValue }}</span></div>
            <div class="row"><span class="lbl">Designation</span><span class="val">{{ $designation }}</span></div>
            @if($joinDate)
            <div class="row"><span class="lbl">Date of Joining</span><span class="val">{{ \Illuminate\Support\Carbon::parse($joinDate)->format('d M Y') }}</span></div>
            @endif
        </div>

        <div class="section">
            <h3>Attendance Summary ({{ $run->days_in_month }} days)</h3>
            <div class="row"><span class="lbl">Days Present (incl. half-days)</span><span class="val">{{ rtrim(rtrim(number_format($run->days_present,2),'0'),'.') }}</span></div>
            <div class="row"><span class="lbl">Paid Leave</span><span class="val">{{ rtrim(rtrim(number_format($run->days_paid_leave,2),'0'),'.') }}</span></div>
            <div class="row"><span class="lbl">Week Off / Holiday</span><span class="val">{{ rtrim(rtrim(number_format($run->days_off,2),'0'),'.') }}</span></div>
            <div class="row"><span class="lbl">Unpaid Leave</span><span class="val">{{ rtrim(rtrim(number_format($run->days_unpaid_leave,2),'0'),'.') }}</span></div>
            <div class="row"><span class="lbl">Absent</span><span class="val">{{ rtrim(rtrim(number_format($run->days_absent,2),'0'),'.') }}</span></div>
            <div class="row" style="background:#f0fdf4;padding:8px 6px;border-radius:4px;border:none;margin-top:6px">
                <span class="lbl" style="color:#14532d;font-weight:600">Days Payable</span>
                <span class="val" style="color:#14532d;font-size:15px">{{ rtrim(rtrim(number_format($run->days_payable,2),'0'),'.') }}</span>
            </div>
        </div>

        <div class="section">
            <h3>Earnings &amp; Deductions</h3>
            <div class="row"><span class="lbl">Base Salary (full month)</span><span class="val">₹{{ number_format($run->base_salary, 2) }}</span></div>
            <div class="row"><span class="lbl">Base Pay (pro-rated for {{ rtrim(rtrim(number_format($run->days_payable,2),'0'),'.') }} / {{ $run->days_in_month }})</span><span class="val">₹{{ number_format($run->base_pay_prorated, 2) }}</span></div>
        </div>

        @if($adjustments->isNotEmpty())
        <div class="section">
            <h3>Adjustments</h3>
            <table>
                <thead><tr><th>Date</th><th>Type</th><th>Notes</th><th class="right">Amount</th></tr></thead>
                <tbody>
                @foreach($adjustments as $adj)
                @php $sign = \App\Models\PayrollAdjustment::TYPES[$adj->type]['sign'] ?? 1; @endphp
                <tr>
                    <td>{{ $adj->date->format('d M') }}</td>
                    <td>{{ $adj->type_label }}</td>
                    <td class="muted">{{ $adj->notes ?: '—' }}</td>
                    <td class="right" style="color:{{ $sign > 0 ? '#16a34a' : '#dc2626' }};font-weight:600">
                        {{ $sign > 0 ? '+' : '−' }}₹{{ number_format($adj->amount, 2) }}
                    </td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @endif

        <div class="totals">
            <div class="row"><span class="lbl">Base Pay (pro-rated)</span><span class="val">₹{{ number_format($run->base_pay_prorated, 2) }}</span></div>
            <div class="row"><span class="lbl">Total Credits</span><span class="val" style="color:#16a34a">+₹{{ number_format($run->credits, 2) }}</span></div>
            <div class="row"><span class="lbl">Total Debits</span><span class="val" style="color:#dc2626">−₹{{ number_format($run->debits, 2) }}</span></div>
        </div>

        <div class="net">
            <span>NET PAYABLE</span>
            <span>₹{{ number_format($run->net_payable, 2) }}</span>
        </div>

        @if($run->status === 'paid')
        <div style="margin-top:18px;background:#f0fdf4;border:1px solid #86efac;border-radius:6px;padding:10px 14px;font-size:12px;color:#14532d">
            ✓ Paid on <strong>{{ $run->paid_at?->format('d M Y') }}</strong> via <strong>{{ str_replace('_',' ',$run->payment_method) }}</strong>
            @if($run->paid_by)— recorded by {{ $run->paidBy?->name ?? '—' }}@endif
            @if($run->notes) <div style="margin-top:4px">Note: {{ $run->notes }}</div>@endif
        </div>
        @endif

        <div style="margin-top:30px;display:flex;justify-content:space-between;color:#64748b;font-size:11px">
            <div>This is a system-generated salary slip.</div>
            <div>{{ $clinic['name'] ?? '' }}</div>
        </div>
    </div>
</div>
</div>
</body>
</html>
