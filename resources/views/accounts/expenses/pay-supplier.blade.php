@extends('layouts.app')
@section('title','Pay Supplier')
@section('page-title','Pay Supplier')
@section('content')

<div class="page-header">
    <div class="page-header-left">
        <a href="{{ route('accounts.index') }}" class="text-small"><i class="fa-solid fa-arrow-left"></i> Accounts</a>
        <h1><i class="fa-solid fa-truck-fast"></i> Pay Supplier</h1>
        <div class="text-small text-muted">Pick a supplier — their current outstanding auto-fills as the amount. Override for partial payments.</div>
    </div>
    <a href="{{ route('accounts.ledgers.create') }}" class="btn btn-outline"><i class="fa-solid fa-plus"></i> Add Supplier</a>
</div>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-error">{{ $errors->first() }}</div>@endif

@if($suppliers->isEmpty())
<div class="card"><div class="card-body" style="text-align:center;padding:24px">
    <p>No active suppliers yet. <a href="{{ route('accounts.ledgers.create') }}">Add a supplier ledger</a> first (Party Type = Supplier), then come back.</p>
</div></div>
@else
<div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
    <form method="POST" action="{{ route('accounts.expenses.pay-supplier.store') }}" class="card" style="padding:16px">
        @csrf
        @php $preselect = old('supplier_ledger_id', request('supplier') ?: $defaultSupplierId); @endphp

        <label>Supplier *</label>
        <select name="supplier_ledger_id" id="supplierSelect" class="form-control" required autofocus onchange="prefillSupplier(this)">
            <option value="">— pick supplier —</option>
            @foreach($suppliers as $s)
                <option value="{{ $s->id }}"
                    data-outstanding="{{ (float) $s->outstanding }}"
                    {{ (int) $preselect === $s->id ? 'selected' : '' }}>
                    {{ $s->name }} — outstanding ₹{{ number_format((float) $s->outstanding, 2) }}
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
                <label>Pay from *</label>
                <select name="pay_from" class="form-control" required>
                    <option value="">— select cash or bank —</option>
                    @foreach($payFrom as $l)
                        <option value="{{ $l->id }}" {{ (int) old('pay_from') === $l->id ? 'selected' : '' }}>
                            {{ $l->name }} ({{ $l->party_type }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label>Reference</label>
                <input name="reference_no" value="{{ old('reference_no') }}" class="form-control" placeholder="Cheque no., UPI ref">
            </div>
            <div>
                <label>Narration</label>
                <input name="narration" value="{{ old('narration') }}" class="form-control" placeholder="(auto: 'Paid to Supplier Name')">
            </div>
        </div>

        <div style="margin-top:14px;display:flex;gap:8px">
            <button class="btn btn-primary"><i class="fa-solid fa-check"></i> Pay Supplier</button>
            <a href="{{ route('accounts.expenses.quick') }}" class="btn btn-outline">Other expense →</a>
        </div>

        <div class="text-small text-muted" style="margin-top:14px;padding:10px;background:#fafaf6;border-radius:6px">
            <i class="fa-solid fa-info-circle"></i> Posts a Payment voucher: <strong>Dr supplier</strong> (reduces what you owe) / <strong>Cr cash or bank</strong> (money out). Supplier's ledger balance drops by the amount paid.
        </div>
    </form>

    <div class="card">
        <div class="card-header"><strong><i class="fa-solid fa-clock-rotate-left"></i> Recent Supplier Payments</strong></div>
        <div class="card-body" style="padding:0">
            @if($recent->isEmpty())
            <div style="padding:18px;text-align:center;color:var(--sc-muted)">No supplier payments posted yet.</div>
            @else
            <table class="sc-table" style="margin:0">
                <thead><tr><th>Date</th><th>Voucher</th><th>Supplier</th><th class="text-right">Amount</th></tr></thead>
                <tbody>
                @foreach($recent as $v)
                    <tr>
                        <td class="text-small">{{ \Carbon\Carbon::parse($v->voucher_date)->format('d-M') }}</td>
                        <td><a href="{{ route('accounts.vouchers.show', $v) }}" class="mono">{{ $v->voucher_no }}</a></td>
                        <td class="text-small">{{ $v->party?->name ?? '—' }}</td>
                        <td class="text-right mono">₹{{ number_format((float) $v->total_amount, 2) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            @endif
        </div>
    </div>
</div>

<script>
function prefillSupplier(sel) {
    const opt = sel.options[sel.selectedIndex];
    const amt = document.getElementById('amountInput');
    const hint = document.getElementById('amountHint');
    if (!opt || opt.dataset.outstanding === undefined) {
        if (amt) amt.value = '';
        if (hint) hint.textContent = '';
        return;
    }
    const out = parseFloat(opt.dataset.outstanding) || 0;
    if (amt) amt.value = out > 0 ? out.toFixed(2) : '';
    if (hint) {
        if (out > 0) {
            hint.textContent = `Full outstanding: ₹${out.toLocaleString('en-IN', {minimumFractionDigits: 2})} — edit for partial payment.`;
            hint.style.color = '';
        } else if (out < 0) {
            hint.textContent = `Note: this supplier has a Dr balance of ₹${Math.abs(out).toLocaleString('en-IN', {minimumFractionDigits: 2})} (we overpaid, or no purchases yet).`;
            hint.style.color = '#b54344';
        } else {
            hint.textContent = `Nothing outstanding — supplier is fully settled.`;
            hint.style.color = '';
        }
    }
}
document.addEventListener('DOMContentLoaded', () => {
    const sel = document.getElementById('supplierSelect');
    if (sel && sel.value) prefillSupplier(sel);
});
</script>
@endif

@endsection
