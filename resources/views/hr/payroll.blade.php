@extends('layouts.app')
@section('title','Payroll')
@section('page-title','Payroll')
@section('content')

@php
    $prevMonth = \Illuminate\Support\Carbon::createFromFormat('Y-m', $month)->subMonth()->format('Y-m');
    $nextMonth = \Illuminate\Support\Carbon::createFromFormat('Y-m', $month)->addMonth()->format('Y-m');
@endphp

<div class="page-header">
    <div class="page-header-left">
        <h1>Monthly Payroll</h1>
        <div class="breadcrumb">{{ $monthLabel }} &nbsp;·&nbsp; {{ $rows->count() }} active people</div>
    </div>
    <div style="display:flex;gap:8px;align-items:center">
        <a href="?month={{ $prevMonth }}" class="btn btn-outline btn-sm" title="Previous month"><i class="fa-solid fa-chevron-left"></i></a>
        <form method="GET" style="margin:0">
            <input type="month" name="month" value="{{ $month }}" class="form-control" style="width:160px;display:inline-block" onchange="this.form.submit()">
        </form>
        <a href="?month={{ $nextMonth }}" class="btn btn-outline btn-sm" title="Next month"><i class="fa-solid fa-chevron-right"></i></a>
        <a href="{{ route('hr.attendance.index') }}" class="btn btn-outline"><i class="fa-solid fa-calendar-check"></i> Attendance</a>
        <a href="{{ route('hr.adjustments.index', ['month'=>$month]) }}" class="btn btn-outline"><i class="fa-solid fa-coins"></i> Adjustments</a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> {{ session('success') }}</div>
@endif
@if(session('error'))
<div class="alert alert-danger"><i class="fa-solid fa-triangle-exclamation"></i> {{ session('error') }}</div>
@endif

@if(isset($redundantRecurring) && $redundantRecurring->isNotEmpty())
<div class="card" style="border:1px solid #fde68a;background:#fffbeb;margin-bottom:18px">
    <div class="card-body" style="padding:14px 16px">
        <div style="display:flex;gap:12px;align-items:flex-start">
            <i class="fa-solid fa-triangle-exclamation" style="color:#b45309;font-size:18px;margin-top:2px"></i>
            <div style="flex:1">
                <div class="fw-700" style="color:#78350f">Possible double-posting in Accounts</div>
                <div class="text-small" style="color:#92400e;margin-top:4px">
                    The recurring expense{{ $redundantRecurring->count() === 1 ? '' : 's' }} below post to the same per-payee ledger that HR Payroll now writes to when you click <strong>Pay</strong>. Leaving {{ $redundantRecurring->count() === 1 ? 'it' : 'them' }} active will cause the salary to be booked twice every month. Deactivate to make HR Payroll the single source of truth:
                </div>
                <form method="POST" action="{{ route('hr.payroll.deactivate-recurring') }}" style="margin-top:10px">
                    @csrf
                    <div style="display:flex;flex-direction:column;gap:6px">
                        @foreach($redundantRecurring as $re)
                        <label style="display:flex;align-items:center;gap:8px;padding:6px 10px;background:#fff;border:1px solid #fde68a;border-radius:4px;cursor:pointer">
                            <input type="checkbox" name="ids[]" value="{{ $re->id }}" checked>
                            <span style="flex:1"><strong>{{ $re->name }}</strong> — ₹{{ number_format((float)$re->amount, 2) }}/mo on day {{ $re->day_of_month }}</span>
                            <span class="text-small text-muted">Last posted: {{ $re->last_posted_on ? \Illuminate\Support\Carbon::parse($re->last_posted_on)->format('d M Y') : 'never' }}</span>
                        </label>
                        @endforeach
                    </div>
                    <div style="margin-top:10px;display:flex;gap:8px">
                        <button type="submit" class="btn btn-sm btn-primary" style="background:#b45309;border-color:#b45309" onclick="return confirm('Deactivate the selected recurring expense(s)? They will stop auto-posting at month start.')">
                            <i class="fa-solid fa-power-off"></i> Deactivate Selected
                        </button>
                        <a href="{{ route('accounts.recurring.index') ?? '#' }}" class="btn btn-sm btn-outline">View in Accounts</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endif

{{-- KPI row — same .stat-card pattern as the dashboard --}}
<div class="grid grid-4 mb-4">
    <div class="stat-card">
        <div class="stat-icon" style="background:rgba(15,31,61,.1);color:var(--sc-navy)"><i class="fa-solid fa-sack-dollar"></i></div>
        <div>
            <div class="stat-value">₹{{ number_format($totals['gross'], 0) }}</div>
            <div class="stat-label">Gross</div>
            <div class="stat-sub text-muted">Base + credits</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon teal"><i class="fa-solid fa-wallet"></i></div>
        <div>
            <div class="stat-value">₹{{ number_format($totals['net'], 0) }}</div>
            <div class="stat-label">Net Payable</div>
            <div class="stat-sub text-muted">After debits</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fa-solid fa-circle-check"></i></div>
        <div>
            <div class="stat-value">₹{{ number_format($totals['paid'], 0) }}</div>
            <div class="stat-label">Paid</div>
            <div class="stat-sub text-muted">Settled this month</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon amber"><i class="fa-solid fa-hourglass-half"></i></div>
        <div>
            <div class="stat-value">₹{{ number_format($totals['pending'], 0) }}</div>
            <div class="stat-label">Pending</div>
            <div class="stat-sub text-muted">Yet to disburse</div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <i class="fa-solid fa-users"></i> Payroll — {{ $monthLabel }}
        <div class="card-actions">
            <span class="text-small text-muted">Pro-rated from <a href="{{ route('hr.attendance.index') }}" style="color:var(--sc-teal)">attendance</a> &amp; <a href="{{ route('hr.adjustments.index', ['month'=>$month]) }}" style="color:var(--sc-teal)">adjustments</a></span>
        </div>
    </div>
    <div class="table-responsive">
        <table class="sc-table table" style="margin-bottom:0">
            <thead>
            <tr>
                <th>Person</th>
                <th class="text-center">Days Payable</th>
                <th class="text-right">Base (Pro-rated)</th>
                <th class="text-right">Credits</th>
                <th class="text-right">Debits</th>
                <th class="text-right">Net Payable</th>
                <th>Status</th>
                <th class="text-right">Action</th>
            </tr>
            </thead>
            <tbody>
            @forelse($rows as $run)
            @php
                $subj = $run->subject();
                $isDoc = (bool) $run->doctor_id;
                $designation = $isDoc ? ($subj?->specialization ?: 'Doctor') : ($subj?->designation ?: 'Staff');
            @endphp
            <tr>
                <td>
                    <div class="fw-600">
                        {{ $run->subject_name }}
                        @if($isDoc)
                            <span class="badge" style="background:#e0f2fe;color:#0369a1;font-size:10px;padding:1px 6px;margin-left:4px">Doctor</span>
                        @endif
                    </div>
                    <div class="text-small text-muted">{{ $designation }} &middot; Base ₹{{ number_format($run->base_salary, 0) }}/mo</div>
                </td>
                <td class="text-center">
                    <span class="fw-600">{{ rtrim(rtrim(number_format($run->days_payable, 2), '0'), '.') }}</span>
                    <span class="text-muted text-small">/ {{ $run->days_in_month }}</span>
                </td>
                <td class="text-right mono">₹{{ number_format($run->base_pay_prorated, 2) }}</td>
                <td class="text-right mono" style="color:var(--sc-green)">{{ $run->credits > 0 ? '+₹'.number_format($run->credits, 2) : '—' }}</td>
                <td class="text-right mono" style="color:var(--sc-red)">{{ $run->debits  > 0 ? '−₹'.number_format($run->debits,  2) : '—' }}</td>
                <td class="text-right mono fw-600" style="font-size:14px">₹{{ number_format($run->net_payable, 2) }}</td>
                <td>
                    @if($run->status === 'paid')
                        <span class="badge badge-green"><i class="fa-solid fa-check"></i> Paid</span>
                        @if($run->paid_at)<div class="text-small text-muted" style="margin-top:3px">{{ $run->paid_at->format('d M') }} &middot; {{ str_replace('_',' ', $run->payment_method) }}</div>@endif
                    @else
                        <span class="badge badge-amber">Pending</span>
                    @endif
                </td>
                <td class="text-right">
                    <div style="display:flex;gap:5px;justify-content:flex-end">
                        @if($run->status === 'paid')
                            <a href="{{ route('hr.payroll.slip', $run) }}" target="_blank" class="btn btn-sm btn-outline" title="View slip"><i class="fa-solid fa-receipt"></i></a>
                            <form method="POST" action="{{ route('hr.payroll.unpay', $run) }}" onsubmit="return confirm('Mark this payment as unpaid?')" style="margin:0">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline" style="color:var(--sc-red);border-color:#fecaca" title="Revert to pending"><i class="fa-solid fa-rotate-left"></i></button>
                            </form>
                        @elseif($run->net_payable > 0)
                            <button type="button" class="btn btn-sm btn-primary" onclick="openPay({{ $run->id }}, '{{ addslashes($run->subject_name) }}', {{ $run->net_payable }})">
                                <i class="fa-solid fa-money-bill-wave"></i> Pay
                            </button>
                        @else
                            <span class="text-small text-muted">—</span>
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="8" class="text-center text-muted" style="padding:40px">
                <i class="fa-solid fa-users-slash" style="font-size:24px;display:block;margin-bottom:8px;color:#cbd5e1"></i>
                No active staff or doctors yet. Add via <a href="{{ route('staff.index') }}" style="color:var(--sc-teal)">Administration → Staff</a>
                or <a href="{{ route('doctors.index') }}" style="color:var(--sc-teal)">Doctors</a>.
            </td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Pay modal --}}
<div id="pay-modal" style="display:none;position:fixed;inset:0;background:rgba(15,31,61,0.5);z-index:1000;align-items:center;justify-content:center" onclick="if(event.target===this)closePay()">
    <div class="card" style="width:440px;max-width:92vw;margin:0">
        <div class="card-header"><i class="fa-solid fa-money-bill-wave"></i> Mark Payment</div>
        <div class="card-body">
            <div id="pay-summary" class="text-muted text-small" style="margin-bottom:14px;padding-bottom:12px;border-bottom:1px solid var(--sc-border)"></div>
            <form id="pay-form" method="POST">
                @csrf
                <div class="form-group">
                    <label class="form-label">Payment Method *</label>
                    <select name="payment_method" class="form-control" required>
                        <option value="cash">Cash</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="upi">UPI</option>
                        <option value="cheque">Cheque</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Paid On *</label>
                    <input type="date" name="paid_at" class="form-control" required value="{{ today()->toDateString() }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Notes (optional)</label>
                    <input type="text" name="notes" maxlength="255" class="form-control" placeholder="Cheque no, transaction ref, etc.">
                </div>
                <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:6px">
                    <button type="button" class="btn btn-outline" onclick="closePay()">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Confirm Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function openPay(runId, name, amount) {
    document.getElementById('pay-summary').innerHTML =
        '<strong>' + name + '</strong> &middot; net payable <strong>₹' +
        amount.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</strong>';
    document.getElementById('pay-form').action = '/hr/payroll/' + runId + '/mark-paid';
    document.getElementById('pay-modal').style.display = 'flex';
}
function closePay() { document.getElementById('pay-modal').style.display = 'none'; }
document.addEventListener('keydown', e => { if (e.key === 'Escape') closePay(); });
</script>
@endpush
@endsection
