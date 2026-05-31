@extends('layouts.app')
@section('title', $voucher->voucher_no)
@section('page-title','Voucher')
@section('content')

<div class="page-header">
    <div class="page-header-left">
        <a href="{{ route('accounts.vouchers.index') }}" class="text-small"><i class="fa-solid fa-arrow-left"></i> Vouchers</a>
        <h1>{{ $voucher->type->name }} — {{ $voucher->voucher_no }}</h1>
        <div class="text-small text-muted">{{ \Carbon\Carbon::parse($voucher->voucher_date)->format('d-M-Y') }} · Posted by {{ $voucher->createdBy?->name ?: '—' }}</div>
    </div>
    <div style="display:flex;gap:8px">
        <a href="{{ route('accounts.vouchers.print',$voucher) }}" target="_blank" class="btn btn-outline"><i class="fa-solid fa-print"></i> Print</a>
        @if($voucher->status==='Posted')
        <form method="POST" action="{{ route('accounts.vouchers.cancel',$voucher) }}" onsubmit="return confirm('Cancel this voucher? It will be marked as cancelled (kept in history).')">
            @csrf
            <button class="btn btn-outline" style="color:var(--sc-red);border-color:var(--sc-red)"><i class="fa-solid fa-ban"></i> Cancel</button>
        </form>
        @endif
    </div>
</div>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($voucher->status==='Cancelled')<div class="alert alert-error"><i class="fa-solid fa-ban"></i> This voucher has been cancelled.</div>@endif

<div class="card" style="padding:16px;margin-bottom:14px">
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px">
        <div><div class="text-muted text-small">Party</div><div class="fw-600">{{ $voucher->party?->name ?: '—' }}</div></div>
        <div><div class="text-muted text-small">Reference</div><div>{{ $voucher->reference_no ?: '—' }} @if($voucher->reference_date) <span class="text-small text-muted">/ {{ \Carbon\Carbon::parse($voucher->reference_date)->format('d-M-Y') }}</span>@endif</div></div>
        <div><div class="text-muted text-small">Total</div><div class="fw-700 mono">₹{{ number_format((float)$voucher->total_amount, 2) }}</div></div>
        <div><div class="text-muted text-small">Status</div><div><span class="badge {{ $voucher->status==='Posted'?'badge-green':'badge-red' }}">{{ $voucher->status }}</span></div></div>
    </div>
    @if($voucher->narration)
        <div style="margin-top:10px;padding:10px;background:#fafaf6;border-radius:6px">
            <div class="text-muted text-small">Narration</div>
            <div style="white-space:pre-wrap">{{ $voucher->narration }}</div>
        </div>
    @endif
</div>

<div class="card">
    <div class="card-header"><strong>Entries</strong></div>
    <div class="table-wrap">
        <table class="sc-table" style="margin:0">
            <thead>
                <tr>
                    <th>#</th><th>Ledger</th><th>Note</th>
                    <th class="text-right">Dr</th><th class="text-right">Cr</th>
                    <th>HSN/SAC</th><th class="text-right">Taxable</th><th class="text-right">GST%</th>
                    <th class="text-right">CGST</th><th class="text-right">SGST</th><th class="text-right">IGST</th>
                </tr>
            </thead>
            <tbody>
                @foreach($voucher->entries as $e)
                <tr>
                    <td class="text-small">{{ $e->line_no }}</td>
                    <td>
                        @if($e->ledger)
                            <a href="{{ route('accounts.ledgers.show', $e->ledger) }}">{{ $e->ledger->name }}</a>
                            <div class="text-small text-muted">{{ $e->ledger->group?->name }}</div>
                        @else
                            <span class="text-muted">(deleted ledger #{{ $e->ledger_id }})</span>
                        @endif
                    </td>
                    <td class="text-small">{{ $e->narration ?: '—' }}</td>
                    <td class="text-right mono">{{ (float)$e->dr_amount > 0 ? number_format((float)$e->dr_amount,2) : '' }}</td>
                    <td class="text-right mono">{{ (float)$e->cr_amount > 0 ? number_format((float)$e->cr_amount,2) : '' }}</td>
                    <td class="text-small mono">{{ $e->hsn_sac ?: '—' }}</td>
                    <td class="text-right mono">{{ $e->taxable_amount ? number_format((float)$e->taxable_amount,2) : '' }}</td>
                    <td class="text-right mono text-small">{{ $e->gst_rate !== null ? rtrim(rtrim(number_format((float)$e->gst_rate,2),'0'),'.') : '' }}</td>
                    <td class="text-right mono text-small">{{ $e->cgst_amount ? number_format((float)$e->cgst_amount,2) : '' }}</td>
                    <td class="text-right mono text-small">{{ $e->sgst_amount ? number_format((float)$e->sgst_amount,2) : '' }}</td>
                    <td class="text-right mono text-small">{{ $e->igst_amount ? number_format((float)$e->igst_amount,2) : '' }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" class="text-right fw-700">Totals</td>
                    <td class="text-right mono fw-700">{{ number_format((float)$voucher->entries->sum('dr_amount'),2) }}</td>
                    <td class="text-right mono fw-700">{{ number_format((float)$voucher->entries->sum('cr_amount'),2) }}</td>
                    <td colspan="6"></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

@endsection
