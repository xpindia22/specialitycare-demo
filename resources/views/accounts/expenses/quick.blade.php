@extends('layouts.app')
@section('title','Quick Expense')
@section('page-title','Quick Expense')
@section('content')

@include('accounts._toolbar')

<div class="page-header">
    <div class="page-header-left">
        <a href="{{ route('accounts.index') }}" class="text-small"><i class="fa-solid fa-arrow-left"></i> Accounts</a>
        <h1><i class="fa-solid fa-receipt"></i> Quick Expense</h1>
        <div class="text-small text-muted">For the common case: rent, electricity, petrol, salary etc. Posts a Payment voucher.</div>
    </div>
    <a href="{{ route('accounts.recurring.index') }}" class="btn btn-outline"><i class="fa-solid fa-repeat"></i> Recurring</a>
</div>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-error">{{ $errors->first() }}</div>@endif

<div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
    <form method="POST" action="{{ route('accounts.expenses.store') }}" class="card" style="padding:16px">
        @csrf
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
            <div style="grid-column:span 2">
                <label>Expense type *</label>
                <select name="expense_ledger" class="form-control" required>
                    <option value="">— pick an expense ledger —</option>
                    @php $byGroup = $expenseLedgers->groupBy(fn($l) => $l->group?->name); @endphp
                    @foreach($byGroup as $groupName => $items)
                    <optgroup label="{{ $groupName }}">
                        @foreach($items as $l)
                            <option value="{{ $l->id }}" {{ (int)old('expense_ledger')===$l->id?'selected':'' }}>{{ $l->name }}</option>
                        @endforeach
                    </optgroup>
                    @endforeach
                </select>
                <div class="text-small text-muted" style="margin-top:4px">Don't see what you need? <a href="{{ route('accounts.ledgers.create') }}">Add a new ledger</a> under "Indirect Expenses".</div>
            </div>
            <div>
                <label>Amount (₹) *</label>
                <input type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount') }}" class="form-control mono" required autofocus>
            </div>
            <div>
                <label>Date *</label>
                <input type="date" name="date" value="{{ old('date', today()->toDateString()) }}" class="form-control" required>
            </div>
            <div style="grid-column:span 2">
                <label>Paid from *</label>
                <select name="pay_from" class="form-control" required>
                    @foreach($payFrom as $l)
                        <option value="{{ $l->id }}" {{ (int)old('pay_from')===$l->id?'selected':'' }}>{{ $l->name }} ({{ $l->party_type }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label>Reference</label>
                <input name="reference_no" value="{{ old('reference_no') }}" class="form-control" placeholder="Bill no., cheque no.">
            </div>
            <div>
                <label>Narration</label>
                <input name="narration" value="{{ old('narration') }}" class="form-control" placeholder="e.g., May rent">
            </div>
        </div>
        <div style="margin-top:14px;display:flex;gap:8px">
            <button class="btn btn-primary"><i class="fa-solid fa-check"></i> Post Expense</button>
            <a href="{{ route('accounts.vouchers.create','PAY') }}" class="btn btn-outline">Full Payment voucher →</a>
        </div>
    </form>

    <div class="card">
        <div class="card-header"><strong><i class="fa-solid fa-clock-rotate-left"></i> Recent Payments</strong></div>
        <div class="card-body" style="padding:0">
            @if($recent->isEmpty())
            <div style="padding:18px;text-align:center;color:var(--sc-muted)">No expenses posted yet.</div>
            @else
            <table class="sc-table" style="margin:0">
                <thead><tr><th>Date</th><th>Voucher</th><th>For</th><th class="text-right">Amount</th></tr></thead>
                <tbody>
                @foreach($recent as $v)
                    @php $dr = $v->entries->firstWhere(fn($e) => $e->dr_amount > 0); @endphp
                    <tr>
                        <td class="text-small">{{ \Carbon\Carbon::parse($v->voucher_date)->format('d-M') }}</td>
                        <td><a href="{{ route('accounts.vouchers.show',$v) }}" class="mono">{{ $v->voucher_no }}</a></td>
                        <td class="text-small">{{ $dr?->ledger?->name ?? '—' }}</td>
                        <td class="text-right mono">₹{{ number_format((float)$v->total_amount,2) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            @endif
        </div>
    </div>
</div>

@endsection
