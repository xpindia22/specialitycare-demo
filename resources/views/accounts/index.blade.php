@extends('layouts.app')
@section('title','Accounts')
@section('page-title','Accounts')
@push('styles')
<style>
    /* Colour-coded Accounts dashboard buttons. Warm hues = money-out
       actions, cool hues = master-data / admin. Filled style for the
       three most-used (Expense, Salary, Supplier) so they pop; subtler
       tinted-outline for the rest so the page doesn't look like a paint
       store. Same border-radius / padding / icon spacing throughout. */
    /* Always-filled coloured buttons, always white text. Hover = darken
       background ~10%. No outline/fill swap, no cascade fragility — text
       is white at every state, on a fixed dark colour. */
    .acc-btn { font-weight:600; color:#fff !important; border:1px solid transparent; transition:transform .12s, box-shadow .12s, filter .15s; }
    .acc-btn:hover { transform:translateY(-1px); box-shadow:0 4px 12px rgba(0,0,0,.12); filter:brightness(0.88); color:#fff !important; }
    .acc-btn i { color:#fff !important; }
    .acc-btn.expense   { background:#b91c1c; }   /* dark red */
    .acc-btn.salary    { background:#b45309; }   /* dark amber */
    .acc-btn.supplier  { background:#0369a1; }   /* dark sky */
    .acc-btn.voucher   { background:#6d28d9; }   /* dark purple */
    .acc-btn.payees    { background:#15803d; }   /* dark green */
    .acc-btn.suppliers { background:#1d4ed8; }   /* dark blue */
    .acc-btn.ledgers   { background:#334155; }   /* dark slate */
    .acc-btn.settings  { background:#4b5563; }   /* dark grey */
</style>
@endpush
@section('content')

<div class="page-header">
    <div class="page-header-left"><h1><i class="fa-solid fa-book"></i> Accounts &amp; GST</h1></div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
        <a href="{{ route('accounts.expenses.quick') }}"        class="btn acc-btn expense"><i class="fa-solid fa-receipt"></i> Quick Expense</a>
        <a href="{{ route('accounts.expenses.salary') }}"       class="btn acc-btn salary"><i class="fa-solid fa-money-bill-wave"></i> Pay Salary</a>
        <a href="{{ route('accounts.expenses.pay-supplier') }}" class="btn acc-btn supplier"><i class="fa-solid fa-truck-fast"></i> Pay Supplier</a>
        <a href="{{ route('accounts.vouchers.create','PAY') }}" class="btn acc-btn voucher"><i class="fa-solid fa-plus"></i> Voucher</a>
        <a href="{{ route('accounts.payees.index') }}"          class="btn acc-btn payees"><i class="fa-solid fa-people-group"></i> Payees</a>
        <a href="{{ route('accounts.suppliers.index') }}"       class="btn acc-btn suppliers"><i class="fa-solid fa-truck-fast"></i> Suppliers</a>
        <a href="{{ route('accounts.ledgers.index') }}"         class="btn acc-btn ledgers"><i class="fa-solid fa-list"></i> Ledgers</a>
        <a href="{{ route('accounts.settings.edit') }}"         class="btn acc-btn settings"><i class="fa-solid fa-gear"></i></a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> {{ session('success') }}</div>
@endif

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;margin-bottom:18px">
    <div class="card"><div class="card-body">
        <div class="text-muted text-small">This Month — Income</div>
        <div style="font-size:1.6rem;font-weight:700;color:var(--sc-green,#1f8b4c)">₹{{ number_format($month['income'], 2) }}</div>
    </div></div>
    <div class="card"><div class="card-body">
        <div class="text-muted text-small">This Month — Expenses</div>
        <div style="font-size:1.6rem;font-weight:700;color:var(--sc-red,#b54344)">₹{{ number_format($month['expense'], 2) }}</div>
    </div></div>
    <div class="card"><div class="card-body">
        <div class="text-muted text-small">This Month — Net</div>
        <div style="font-size:1.6rem;font-weight:700;color:{{ $month['net'] >= 0 ? 'var(--sc-green,#1f8b4c)' : 'var(--sc-red,#b54344)' }}">₹{{ number_format($month['net'], 2) }}</div>
    </div></div>
    <div class="card"><div class="card-body">
        <div class="text-muted text-small">FY {{ $fyYear }}-{{ substr($fyYear+1, -2) }} Net Profit</div>
        <div style="font-size:1.6rem;font-weight:700;color:{{ $fy['net'] >= 0 ? 'var(--sc-green,#1f8b4c)' : 'var(--sc-red,#b54344)' }}">₹{{ number_format($fy['net'], 2) }}</div>
        <div class="text-muted text-small">Income ₹{{ number_format($fy['income'],2) }} − Exp ₹{{ number_format($fy['expense'],2) }}</div>
    </div></div>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(340px,1fr));gap:14px">
    <div class="card">
        <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
            <strong><i class="fa-solid fa-chart-line"></i> Reports</strong>
        </div>
        <div class="card-body" style="display:flex;flex-direction:column;gap:6px">
            <a href="{{ route('accounts.reports.day-book') }}" class="btn btn-outline btn-sm" style="justify-content:flex-start"><i class="fa-solid fa-calendar-day fa-fw"></i> Day Book (today's transactions)</a>
            <a href="{{ route('accounts.reports.trial-balance') }}" class="btn btn-outline btn-sm" style="justify-content:flex-start"><i class="fa-solid fa-scale-balanced fa-fw"></i> Trial Balance</a>
            <a href="{{ route('accounts.reports.profit-loss') }}" class="btn btn-outline btn-sm" style="justify-content:flex-start"><i class="fa-solid fa-chart-line fa-fw"></i> Profit &amp; Loss</a>
            <a href="{{ route('accounts.reports.balance-sheet') }}" class="btn btn-outline btn-sm" style="justify-content:flex-start"><i class="fa-solid fa-book-open fa-fw"></i> Balance Sheet</a>
            <a href="{{ route('accounts.reports.gstr1') }}" class="btn btn-outline btn-sm" style="justify-content:flex-start"><i class="fa-solid fa-file-invoice fa-fw"></i> GSTR-1 (outward supplies)</a>
            <a href="{{ route('accounts.reports.gstr3b') }}" class="btn btn-outline btn-sm" style="justify-content:flex-start"><i class="fa-solid fa-file-invoice-dollar fa-fw"></i> GSTR-3B (summary)</a>
            <a href="{{ route('accounts.reports.hsn-summary') }}" class="btn btn-outline btn-sm" style="justify-content:flex-start"><i class="fa-solid fa-barcode fa-fw"></i> HSN-wise Summary</a>
        </div>
    </div>

    <div class="card">
        <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
            <strong><i class="fa-solid fa-clock-rotate-left"></i> Recent Vouchers</strong>
            <a href="{{ route('accounts.vouchers.index') }}" class="text-small">View all →</a>
        </div>
        <div class="card-body" style="padding:0">
            @if($recentVouchers->isEmpty())
            <div style="padding:18px;text-align:center;color:var(--sc-muted)">No vouchers yet.</div>
            @else
            <table class="sc-table" style="margin:0">
                <thead><tr><th>Date</th><th>No.</th><th>Type</th><th>Party</th><th class="text-right">Amount</th></tr></thead>
                <tbody>
                @foreach($recentVouchers as $v)
                <tr>
                    <td class="text-small">{{ \Carbon\Carbon::parse($v->voucher_date)->format('d-M') }}</td>
                    <td><a href="{{ route('accounts.vouchers.show',$v) }}" class="mono">{{ $v->voucher_no }}</a></td>
                    <td><span class="badge badge-navy">{{ $v->type->name }}</span></td>
                    <td class="text-small">{{ $v->party?->name ?: '—' }}</td>
                    <td class="text-right mono">₹{{ number_format((float) $v->total_amount, 2) }}</td>
                </tr>
                @endforeach
                </tbody>
            </table>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-header"><strong><i class="fa-solid fa-rectangle-list"></i> This Month Activity</strong></div>
        <div class="card-body">
            @if($voucherCounts->isEmpty())
            <div class="text-muted">No vouchers this month yet.</div>
            @else
            <div style="display:flex;flex-direction:column;gap:6px">
            @foreach($voucherCounts as $row)
                <a href="{{ route('accounts.vouchers.index', ['type' => $row->code, 'from' => $monthStart->toDateString(), 'to' => $monthEnd->toDateString()]) }}"
                   style="display:flex;justify-content:space-between;padding:6px 4px;border-bottom:1px dashed #eee;text-decoration:none;color:inherit;border-radius:4px;transition:background .15s"
                   onmouseover="this.style.background='#f5f9fa'" onmouseout="this.style.background=''"
                   title="View {{ $row->name }} vouchers for {{ $monthStart->format('M Y') }}">
                    <span><i class="fa-solid fa-chevron-right text-muted" style="font-size:10px"></i> {{ $row->name }}</span>
                    <strong>{{ $row->cnt }}</strong>
                </a>
            @endforeach
            </div>
            @endif
        </div>
    </div>
</div>

@endsection
