@extends('layouts.app')
@section('title', $ledger->name . ' — Statement')
@section('page-title', 'Ledger Statement')
@section('content')

@include('accounts._toolbar')

<div class="page-header">
    <div class="page-header-left">
        <a href="{{ route('accounts.ledgers.index') }}" class="text-small"><i class="fa-solid fa-arrow-left"></i> Ledgers</a>
        <h1>{{ $ledger->name }}</h1>
        <div class="text-small text-muted">{{ $ledger->code }} · {{ $ledger->group?->name }}</div>
    </div>
    <div style="display:flex;gap:8px">
        <a href="{{ route('accounts.ledgers.edit',$ledger) }}" class="btn btn-outline"><i class="fa-solid fa-pen"></i> Edit</a>
        <a href="javascript:window.print()" class="btn btn-outline"><i class="fa-solid fa-print"></i> Print</a>
    </div>
</div>

<form method="GET" class="card" style="padding:12px;margin-bottom:12px;display:flex;gap:8px;align-items:end;flex-wrap:wrap">
    <div><label>From</label><input type="date" name="from" value="{{ $from->toDateString() }}" class="form-control"></div>
    <div><label>To</label><input type="date" name="to" value="{{ $to->toDateString() }}" class="form-control"></div>
    <button class="btn btn-primary">Refresh</button>
</form>

<div class="card">
    <div class="table-wrap">
        <table class="sc-table">
            <thead>
                <tr>
                    <th>Date</th><th>Voucher</th><th>Type</th><th>Party</th><th>Narration</th>
                    <th class="text-right">Dr</th><th class="text-right">Cr</th><th class="text-right">Balance</th>
                </tr>
            </thead>
            <tbody>
                <tr style="background:#fafaf6">
                    <td colspan="5" class="fw-600 text-small">Opening Balance ({{ $from->format('d-M-Y') }})</td>
                    <td class="text-right"></td>
                    <td class="text-right"></td>
                    <td class="text-right mono fw-600">{{ number_format($openingBalance, 2) }} {{ $openingBalance >= 0 ? 'Dr' : 'Cr' }}</td>
                </tr>
                @forelse($entries as $e)
                    <tr>
                        <td class="text-small">{{ \Carbon\Carbon::parse($e->voucher_date)->format('d-M-Y') }}</td>
                        <td><a href="{{ route('accounts.vouchers.show', $e->voucher_id) }}" class="mono">{{ $e->voucher_no }}</a></td>
                        <td><span class="badge badge-navy">{{ $e->type_code }}</span></td>
                        <td class="text-small">{{ $e->party_name ?: '—' }}</td>
                        <td class="text-small">{{ $e->e_narr ?: $e->v_narr }}</td>
                        <td class="text-right mono">{{ (float)$e->dr_amount > 0 ? number_format((float)$e->dr_amount,2) : '' }}</td>
                        <td class="text-right mono">{{ (float)$e->cr_amount > 0 ? number_format((float)$e->cr_amount,2) : '' }}</td>
                        <td class="text-right mono">{{ number_format($e->running, 2) }} {{ $e->running >= 0 ? 'Dr' : 'Cr' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" style="text-align:center;padding:14px;color:var(--sc-muted)">No transactions in this period.</td></tr>
                @endforelse
                <tr style="background:#fafaf6">
                    <td colspan="5" class="fw-600 text-small">Period Totals / Closing</td>
                    <td class="text-right mono fw-600">{{ number_format($totals['dr'],2) }}</td>
                    <td class="text-right mono fw-600">{{ number_format($totals['cr'],2) }}</td>
                    <td class="text-right mono fw-700">{{ number_format(abs($closingBalance), 2) }} {{ $closingBalance >= 0 ? 'Dr' : 'Cr' }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

@endsection
