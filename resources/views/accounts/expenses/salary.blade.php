@extends('layouts.app')
@section('title','Pay Salary')
@section('page-title','Pay Salary')
@section('content')

<div class="page-header">
    <div class="page-header-left">
        <a href="{{ route('accounts.index') }}" class="text-small"><i class="fa-solid fa-arrow-left"></i> Accounts</a>
        <h1><i class="fa-solid fa-money-bill-wave"></i> Pay Salary</h1>
        <div class="text-small text-muted">Pick a payee — their monthly amount + pay-from ledger auto-fill. Override if half-month / bonus / deduction.</div>
    </div>
    <a href="{{ route('accounts.payees.index') }}" class="btn btn-outline"><i class="fa-solid fa-people-group"></i> Manage Payees</a>
</div>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-error">{{ $errors->first() }}</div>@endif

@if($payees->isEmpty())
<div class="card"><div class="card-body" style="text-align:center;padding:24px">
    <p>No active payees yet. <a href="{{ route('accounts.payees.create') }}">Add Sample Physician or your maid first</a>, then come back to pay them.</p>
</div></div>
@else
<div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
    <form method="POST" action="{{ route('accounts.expenses.salary.store') }}" class="card" style="padding:16px">
        @csrf
        @php
            $payeesById = $payees->keyBy('id')->toArray();
            $defaultPayeeId = old('payee_id', request('payee'));
        @endphp

        <label>Payee *</label>
        <select name="payee_id" id="payeeSelect" class="form-control" required autofocus onchange="prefillPayee(this)">
            <option value="">— pick payee —</option>
            @foreach($payees as $p)
                <option value="{{ $p->id }}"
                    data-amount="{{ (float)$p->monthly_amount }}"
                    data-payfrom="{{ $p->pay_from_ledger_id }}"
                    data-payfromname="{{ $p->payFromLedger?->name }}"
                    {{ (int)$defaultPayeeId===$p->id ? 'selected' : '' }}>
                    {{ $p->name }} ({{ $p->role ?: '—' }}) — default ₹{{ number_format((float)$p->monthly_amount,2) }}
                </option>
            @endforeach
        </select>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:10px">
            <div>
                <label>Amount (₹) *</label>
                <input type="number" step="0.01" min="0.01" name="amount" id="amountInput" value="{{ old('amount') }}" class="form-control mono" required>
                <div class="text-small text-muted" id="amountHint"></div>
            </div>
            <div>
                <label>Date *</label>
                <input type="date" name="date" value="{{ old('date', today()->toDateString()) }}" class="form-control" required>
            </div>
            <div style="grid-column:span 2">
                <label>Pay from <span class="text-muted text-small">(blank = use payee's default)</span></label>
                <select name="pay_from" class="form-control" id="payFromOverride">
                    <option value="">— use payee default —</option>
                    @foreach($payees->pluck('payFromLedger')->filter()->unique('id') as $l)
                        <option value="{{ $l->id }}">{{ $l->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label>Reference</label>
                <input name="reference_no" value="{{ old('reference_no') }}" class="form-control" placeholder="Cheque no., bank ref">
            </div>
            <div>
                <label>Narration</label>
                <input name="narration" value="{{ old('narration') }}" class="form-control" placeholder="(auto: 'Salary - Name (Month Year)')">
            </div>
        </div>

        <div style="margin-top:14px;display:flex;gap:8px">
            <button class="btn btn-primary"><i class="fa-solid fa-check"></i> Pay Salary</button>
            <a href="{{ route('accounts.expenses.quick') }}" class="btn btn-outline">Other expense →</a>
        </div>
    </form>

    <div class="card">
        <div class="card-header"><strong><i class="fa-solid fa-clock-rotate-left"></i> Recent Salary Payments</strong></div>
        <div class="card-body" style="padding:0">
            @if($recent->isEmpty())
            <div style="padding:18px;text-align:center;color:var(--sc-muted)">No salary payments posted yet.</div>
            @else
            <table class="sc-table" style="margin:0">
                <thead><tr><th>Date</th><th>Voucher</th><th>Paid To</th><th class="text-right">Amount</th></tr></thead>
                <tbody>
                @foreach($recent as $v)
                    @php $dr = $v->entries->firstWhere(fn($e) => $e->dr_amount > 0); @endphp
                    <tr>
                        <td class="text-small">{{ \Carbon\Carbon::parse($v->voucher_date)->format('d-M') }}</td>
                        <td><a href="{{ route('accounts.vouchers.show',$v) }}" class="mono">{{ $v->voucher_no }}</a></td>
                        <td class="text-small">{{ str_replace('Salary - ', '', $dr?->ledger?->name ?? '—') }}</td>
                        <td class="text-right mono">₹{{ number_format((float)$v->total_amount,2) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            @endif
        </div>
    </div>
</div>

<script>
function prefillPayee(sel) {
    const opt = sel.options[sel.selectedIndex];
    const amt = document.getElementById('amountInput');
    const hint = document.getElementById('amountHint');
    // Empty option ("— pick payee —") clears both fields
    if (!opt || !opt.dataset.amount) {
        if (amt) amt.value = '';
        if (hint) hint.textContent = '';
        return;
    }
    // Always overwrite with the chosen payee's default — user can hand-edit after
    if (amt) amt.value = opt.dataset.amount;
    if (hint) hint.textContent = opt.dataset.amount > 0
        ? `Default ₹${parseFloat(opt.dataset.amount).toLocaleString('en-IN', {minimumFractionDigits: 2})} — override for half-month / bonus / deduction.`
        : '';
}
// On load, if there's a preselected payee (via ?payee= query), prefill
document.addEventListener('DOMContentLoaded', () => {
    const sel = document.getElementById('payeeSelect');
    if (sel && sel.value) prefillPayee(sel);
});
</script>
@endif

@endsection
