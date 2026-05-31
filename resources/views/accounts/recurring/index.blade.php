@extends('layouts.app')
@section('title','Recurring Expenses')
@section('page-title','Recurring Expenses')
@section('content')

<div class="page-header">
    <div class="page-header-left">
        <a href="{{ route('accounts.index') }}" class="text-small"><i class="fa-solid fa-arrow-left"></i> Accounts</a>
        <h1><i class="fa-solid fa-repeat"></i> Recurring Expenses</h1>
        <div class="text-small text-muted">Auto-post monthly fixed expenses (rent, electricity, salary). Vouchers post automatically when you open the Accounts dashboard on/after the due day.</div>
    </div>
    @php
        $thisMonth = now()->format('Y-m');
        $alreadyPosted = $items->filter(fn($r) => $r->is_active && $r->last_posted_on && $r->last_posted_on->format('Y-m') === $thisMonth)
            ->map(fn($r) => $r->name . ' (' . $r->last_posted_on->format('d-M') . ')')
            ->values();
    @endphp
    <form method="POST" action="{{ route('accounts.recurring.run-due') }}"
          onsubmit="return confirmRunDue(this)">
        @csrf
        <button class="btn btn-outline" title="Post any due recurring expenses right now"><i class="fa-solid fa-bolt"></i> Run due now</button>
    </form>
    <script>
        window.__alreadyPostedThisMonth = @json($alreadyPosted);
        function confirmRunDue(form) {
            var list = window.__alreadyPostedThisMonth || [];
            if (!list.length) return true;
            var msg = 'These recurring payments have already posted this month:\n\n• '
                + list.join('\n• ')
                + '\n\nThey will be SKIPPED — duplicates are blocked by the system.'
                + '\n\nContinue anyway? (Only entries not yet posted will fire.)';
            return window.confirm(msg);
        }
    </script>
</div>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-error">{{ $errors->first() }}</div>@endif

<div style="display:grid;grid-template-columns:1fr 1.4fr;gap:14px">
    <form method="POST" action="{{ route('accounts.recurring.store') }}" class="card" style="padding:16px">
        @csrf
        <h3 style="margin-top:0">Add Recurring Expense</h3>
        <label>Name *</label>
        <input name="name" class="form-control" placeholder="e.g., Clinic rent" required>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:8px">
            <div>
                <label>Expense ledger *</label>
                <select name="expense_ledger_id" class="form-control" required>
                    @foreach($expenseLedgers as $l)<option value="{{ $l->id }}">{{ $l->name }}</option>@endforeach
                </select>
            </div>
            <div>
                <label>Paid from *</label>
                <select name="counter_ledger_id" class="form-control" required>
                    @foreach($payFrom as $l)<option value="{{ $l->id }}">{{ $l->name }}</option>@endforeach
                </select>
            </div>
            <div>
                <label>Amount (₹) *</label>
                <input type="number" step="0.01" min="0.01" name="amount" class="form-control mono" required>
            </div>
            <div>
                <label>Day of month *</label>
                <input type="number" min="1" max="28" name="day_of_month" value="1" class="form-control mono" required>
            </div>
            <div>
                <label>Starts (optional)</label>
                <input type="date" name="starts_on" class="form-control">
            </div>
            <div>
                <label>Ends (optional)</label>
                <input type="date" name="ends_on" class="form-control">
            </div>
        </div>
        <label style="margin-top:8px">Narration</label>
        <input name="narration" class="form-control" placeholder="e.g., Monthly rent to Mr. Sharma">

        <label style="display:flex;gap:6px;align-items:center;margin-top:10px">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" checked> Active
        </label>

        <button class="btn btn-primary" style="margin-top:12px"><i class="fa-solid fa-plus"></i> Add</button>
    </form>

    <div class="card">
        <div class="card-header"><strong>Configured</strong></div>
        <div class="table-wrap">
            <table class="sc-table" style="margin:0">
                <thead><tr><th>Name</th><th>Expense</th><th>From</th><th class="text-right">Amount</th><th>Day</th><th>Last posted</th><th></th></tr></thead>
                <tbody>
                @forelse($items as $r)
                <tr style="{{ $r->is_active ? '' : 'opacity:0.55' }}">
                    <td class="fw-600">{{ $r->name }}</td>
                    <td class="text-small">{{ $r->expenseLedger?->name }}</td>
                    <td class="text-small">{{ $r->counterLedger?->name }}</td>
                    <td class="text-right mono">₹{{ number_format((float)$r->amount,2) }}</td>
                    <td>{{ $r->day_of_month }}</td>
                    <td class="text-small">{{ $r->last_posted_on?->format('d-M-Y') ?: '—' }}</td>
                    <td>
                        <form method="POST" action="{{ route('accounts.recurring.destroy',$r) }}" onsubmit="return confirm('Remove this recurring expense?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline" style="color:var(--sc-red);border-color:var(--sc-red)"><i class="fa-solid fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" style="text-align:center;padding:14px;color:var(--sc-muted)">No recurring expenses set up yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="text-small text-muted" style="margin-top:14px;padding:10px;background:#fafaf6;border-radius:6px">
    <i class="fa-solid fa-info-circle"></i> <strong>How auto-posting works:</strong> every time you open the Accounts dashboard on or after the chosen <em>day of month</em>, any unposted recurring expense for the current month is posted as a Payment voucher (Dr expense, Cr Cash/Bank). Each is posted at most once per calendar month. If you'd like to force a check right now without waiting, click <strong>Run due now</strong> above. Server cron is <em>not</em> required.
</div>

@endsection
