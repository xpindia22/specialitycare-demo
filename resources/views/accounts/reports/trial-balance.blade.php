@extends('layouts.app')
@section('title','Trial Balance — ' . $upto->format('d-M-Y'))
@section('page-title','Trial Balance')
@section('content')

@include('accounts._toolbar')

<div class="page-header">
    <div class="page-header-left">
        <a href="{{ route('accounts.index') }}" class="text-small"><i class="fa-solid fa-arrow-left"></i> Accounts</a>
        <h1>Trial Balance as on {{ $upto->format('d-M-Y') }}</h1>
        <div class="text-small text-muted">All postings up to and including this date · {{ setting('clinic_name','SpecialityCare') }}</div>
    </div>
    <div style="display:flex;gap:8px">
        <a href="javascript:window.print()" class="btn btn-outline"><i class="fa-solid fa-print"></i> Print</a>
    </div>
</div>

<form method="GET" class="card" style="padding:12px;margin-bottom:12px;display:flex;gap:8px;align-items:end">
    <div><label>As on</label><input type="date" name="upto" value="{{ $upto->toDateString() }}" class="form-control"></div>
    <button class="btn btn-primary">Show</button>
</form>

@if(!empty($boD))
    <div class="alert alert-info text-small" style="margin-bottom:12px">
        <i class="fa-solid fa-circle-info"></i> Books opening date <strong>{{ $boD->format('d-M-Y') }}</strong> is active —
        vouchers before this date are excluded; their position is carried by each ledger's opening balance.
    </div>
@endif

<div class="card">
    <div class="table-wrap">
        <table class="sc-table">
            <thead><tr><th>Ledger</th><th class="text-right">Dr</th><th class="text-right">Cr</th></tr></thead>
            <tbody>
            @foreach($grouped as $groupName => $rows)
                <tr style="background:#fafaf6"><td colspan="3" class="fw-700">{{ $groupName }}</td></tr>
                @foreach($rows as $r)
                    @if($r->dr_balance > 0 || $r->cr_balance > 0)
                    <tr>
                        <td style="padding-left:24px">
                            <a href="{{ route('accounts.ledgers.show', $r->id) }}">{{ $r->name }}</a>
                            <span class="text-small text-muted mono">{{ $r->code }}</span>
                        </td>
                        <td class="text-right mono">{{ $r->dr_balance > 0 ? number_format($r->dr_balance,2) : '' }}</td>
                        <td class="text-right mono">{{ $r->cr_balance > 0 ? number_format($r->cr_balance,2) : '' }}</td>
                    </tr>
                    @endif
                @endforeach
            @endforeach
            </tbody>
            <tfoot>
                <tr style="background:#f0f0f0">
                    <td class="fw-700">Totals</td>
                    <td class="text-right mono fw-700">₹{{ number_format($totals['dr'],2) }}</td>
                    <td class="text-right mono fw-700">₹{{ number_format($totals['cr'],2) }}</td>
                </tr>
                <tr>
                    <td colspan="3" style="text-align:center;padding:8px">
                        @if(abs($totals['dr']-$totals['cr']) < 0.01)
                            <span class="badge badge-green"><i class="fa-solid fa-check"></i> Balanced</span>
                        @else
                            <span class="badge badge-red"><i class="fa-solid fa-triangle-exclamation"></i> Off by ₹{{ number_format(abs($totals['dr']-$totals['cr']),2) }} — check for cancelled vouchers with orphan entries</span>
                        @endif
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

@endsection
