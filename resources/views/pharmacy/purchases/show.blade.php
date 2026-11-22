@extends('layouts.app')
@section('title','Stock Receipt #'.$purchase->id)
@section('page-title','Stock Receipt #'.$purchase->id)
@section('content')

@include('pharmacy._toolbar')

<div class="page-header">
    <div class="page-header-left">
        <a href="{{ route('pharmacy.purchases.index') }}" class="text-small"><i class="fa-solid fa-arrow-left"></i> Purchases</a>
        <h1><i class="fa-solid fa-receipt"></i> Stock Receipt #{{ $purchase->id }}</h1>
        <div class="text-small text-muted">From {{ $purchase->supplier?->name }} — invoice {{ $purchase->invoice_no ?: '(none)' }} dated {{ $purchase->invoice_date->format('d-M-Y') }}</div>
    </div>
    @if($purchase->voucher)
    <a href="{{ route('accounts.vouchers.show', $purchase->voucher) }}" class="btn btn-outline"><i class="fa-solid fa-book"></i> Voucher {{ $purchase->voucher->voucher_no }}</a>
    @endif
</div>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

<div class="card">
    <div class="table-wrap">
        <table class="sc-table" style="margin:0">
            <thead>
                <tr><th>Medicine</th><th>Batch</th><th>Expiry</th><th class="text-right">Qty</th><th class="text-right">Rate</th><th>HSN</th><th class="text-right">GST%</th><th class="text-right">Taxable</th><th class="text-right">Tax</th><th class="text-right">Total</th></tr>
            </thead>
            <tbody>
            @foreach($purchase->items as $it)
                <tr>
                    <td>{{ $it->medicine?->name }}</td>
                    <td class="mono">{{ $it->batch_number ?: '—' }}</td>
                    <td class="text-small">{{ $it->expiry_date?->format('M-Y') ?: '—' }}</td>
                    <td class="text-right mono">{{ rtrim(rtrim(number_format((float)$it->quantity,2),'0'),'.') }}</td>
                    <td class="text-right mono">₹{{ number_format((float)$it->unit_cost,2) }}</td>
                    <td class="mono">{{ $it->hsn_code }}</td>
                    <td class="text-right mono">{{ rtrim(rtrim(number_format((float)$it->gst_rate,2),'0'),'.') }}%</td>
                    <td class="text-right mono">₹{{ number_format((float)$it->taxable_amount,2) }}</td>
                    <td class="text-right mono text-muted">₹{{ number_format((float)$it->cgst_amount + (float)$it->sgst_amount + (float)$it->igst_amount,2) }}</td>
                    <td class="text-right mono">₹{{ number_format((float)$it->line_total,2) }}</td>
                </tr>
            @endforeach
            </tbody>
            <tfoot>
                <tr><td colspan="7" class="text-right fw-600">Subtotal</td><td class="text-right mono">₹{{ number_format((float)$purchase->subtotal,2) }}</td><td colspan="2"></td></tr>
                @if((float)$purchase->cgst_amount > 0)
                <tr><td colspan="7" class="text-right">CGST</td><td colspan="2" class="text-right mono">₹{{ number_format((float)$purchase->cgst_amount,2) }}</td><td></td></tr>
                <tr><td colspan="7" class="text-right">SGST</td><td colspan="2" class="text-right mono">₹{{ number_format((float)$purchase->sgst_amount,2) }}</td><td></td></tr>
                @endif
                @if((float)$purchase->igst_amount > 0)
                <tr><td colspan="7" class="text-right">IGST</td><td colspan="2" class="text-right mono">₹{{ number_format((float)$purchase->igst_amount,2) }}</td><td></td></tr>
                @endif
                @if(abs((float)$purchase->round_off) > 0.001)
                <tr><td colspan="7" class="text-right text-muted">Round-off</td><td colspan="2" class="text-right mono text-muted">₹{{ number_format((float)$purchase->round_off,2) }}</td><td></td></tr>
                @endif
                <tr style="background:#f0f0f0"><td colspan="7" class="text-right fw-700">GRAND TOTAL ({{ $purchase->payment_mode }})</td><td colspan="2" class="text-right mono fw-700">₹{{ number_format((float)$purchase->total_amount,2) }}</td><td></td></tr>
            </tfoot>
        </table>
    </div>
</div>

@if($purchase->narration)
<div class="card" style="margin-top:12px"><div class="card-body"><strong>Narration:</strong> {{ $purchase->narration }}</div></div>
@endif

@endsection
