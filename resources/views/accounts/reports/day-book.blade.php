@extends('layouts.app')
@section('title','Day Book — ' . $date->format('d-M-Y'))
@section('page-title','Day Book')
@section('content')

<div class="page-header">
    <div class="page-header-left">
        <a href="{{ route('accounts.index') }}" class="text-small"><i class="fa-solid fa-arrow-left"></i> Accounts</a>
        <h1>Day Book — {{ $date->format('d-M-Y') }}</h1>
        <div class="text-small text-muted">{{ $totals['count'] }} voucher(s), Σdr = ₹{{ number_format($totals['dr'],2) }}, Σcr = ₹{{ number_format($totals['cr'],2) }}</div>
    </div>
    <div style="display:flex;gap:8px">
        <a href="javascript:window.print()" class="btn btn-outline"><i class="fa-solid fa-print"></i> Print</a>
    </div>
</div>

<form method="GET" class="card" style="padding:12px;margin-bottom:12px;display:flex;gap:8px;align-items:end">
    <div><label>Date</label><input type="date" name="date" value="{{ $date->toDateString() }}" class="form-control"></div>
    <button class="btn btn-primary">Show</button>
    <a href="{{ route('accounts.reports.day-book', ['date' => $date->copy()->subDay()->toDateString()]) }}" class="btn btn-outline"><i class="fa-solid fa-chevron-left"></i></a>
    <a href="{{ route('accounts.reports.day-book', ['date' => $date->copy()->addDay()->toDateString()]) }}" class="btn btn-outline"><i class="fa-solid fa-chevron-right"></i></a>
</form>

@if($vouchers->isEmpty())
<div class="card"><div class="card-body" style="text-align:center;color:var(--sc-muted)">No vouchers on this date.</div></div>
@else
@foreach($vouchers as $v)
<div class="card" style="margin-bottom:10px">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
        <div>
            <span class="badge badge-navy">{{ $v->type->name }}</span>
            <a href="{{ route('accounts.vouchers.show',$v) }}" class="mono fw-600" style="margin-left:6px">{{ $v->voucher_no }}</a>
            @if($v->party) — {{ $v->party->name }} @endif
        </div>
        <div class="text-small text-muted">Σdr = ₹{{ number_format((float)$v->entries->sum('dr_amount'),2) }} · {{ $v->createdBy?->name }}</div>
    </div>
    <div class="table-wrap">
        <table class="sc-table" style="margin:0">
            <tbody>
            @foreach($v->entries as $e)
                <tr>
                    <td style="width:50%">{{ $e->ledger?->name ?? '(deleted ledger #' . $e->ledger_id . ')' }} <span class="text-small text-muted">({{ $e->ledger?->group?->name }})</span></td>
                    <td class="text-right mono" style="width:20%">{{ $e->dr_amount > 0 ? 'Dr ' . number_format((float)$e->dr_amount,2) : '' }}</td>
                    <td class="text-right mono" style="width:20%">{{ $e->cr_amount > 0 ? 'Cr ' . number_format((float)$e->cr_amount,2) : '' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @if($v->narration)<div style="padding:8px 12px;background:#fafaf6;font-size:13px"><em>{{ $v->narration }}</em></div>@endif
</div>
@endforeach
@endif

@endsection
