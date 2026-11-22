@extends('layouts.app')
@section('title','Pharmacy Purchases')
@section('page-title','Pharmacy Purchases')
@section('content')

@include('pharmacy._toolbar')

<div class="page-header">
    <div class="page-header-left">
        <a href="{{ route('pharmacy.dashboard') }}" class="text-small"><i class="fa-solid fa-arrow-left"></i> Pharmacy</a>
        <h1><i class="fa-solid fa-truck-ramp-box"></i> Stock Receipts / Purchases</h1>
        <div class="text-small text-muted">Record medicine purchases from suppliers. Each receipt updates stock and auto-posts a Purchase voucher to Accounts.</div>
    </div>
    <a href="{{ route('pharmacy.purchases.create') }}" class="btn btn-primary"><i class="fa-solid fa-plus"></i> New Stock Receipt</a>
</div>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

@if($missingTaxInfo > 0)
<div class="alert" style="background:#fff3cd;border:1px solid #ffe69c;color:#664d03;padding:10px 14px;border-radius:6px;margin-bottom:14px">
    <i class="fa-solid fa-triangle-exclamation"></i> <strong>{{ $missingTaxInfo }} medicines</strong> are missing proper HSN / GST info (currently defaulted to <code>30049099 / 0%</code>). GSTR-3B will be inaccurate until you fix them — <a href="{{ route('pharmacy.medicines') }}">edit medicines</a>.
</div>
@endif

<div class="card">
    <div class="table-wrap">
        <table class="sc-table" style="margin:0">
            <thead>
                <tr>
                    <th>Date</th><th>Invoice #</th><th>Supplier</th><th class="text-right">Subtotal</th>
                    <th class="text-right">GST</th><th class="text-right">Total</th><th>Voucher</th><th></th>
                </tr>
            </thead>
            <tbody>
            @forelse($purchases as $p)
                <tr>
                    <td class="text-small">{{ $p->invoice_date->format('d-M-Y') }}</td>
                    <td class="mono">{{ $p->invoice_no ?: '—' }}</td>
                    <td>{{ $p->supplier?->name }}</td>
                    <td class="text-right mono">₹{{ number_format((float)$p->subtotal,2) }}</td>
                    <td class="text-right mono text-muted">₹{{ number_format((float)$p->cgst_amount + (float)$p->sgst_amount + (float)$p->igst_amount, 2) }}</td>
                    <td class="text-right mono fw-700">₹{{ number_format((float)$p->total_amount,2) }}</td>
                    <td class="text-small">
                        @if($p->voucher)
                            <a href="{{ route('accounts.vouchers.show', $p->voucher) }}" class="mono">{{ $p->voucher->voucher_no }}</a>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td><a href="{{ route('pharmacy.purchases.show', $p) }}" class="btn btn-sm btn-outline">View</a></td>
                </tr>
            @empty
                <tr><td colspan="8" style="text-align:center;padding:18px;color:var(--sc-muted)">No purchases yet. <a href="{{ route('pharmacy.purchases.create') }}">Record your first stock receipt</a>.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
{{ $purchases->links() }}

@endsection
