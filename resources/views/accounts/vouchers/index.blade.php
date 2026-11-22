@extends('layouts.app')
@section('title','Vouchers')
@section('page-title','Vouchers')
@section('content')

@include('accounts._toolbar')

<div class="page-header">
    <div class="page-header-left">
        <a href="{{ route('accounts.index') }}" class="text-small"><i class="fa-solid fa-arrow-left"></i> Accounts</a>
        <h1>Vouchers</h1>
    </div>
    <div style="display:flex;gap:6px;flex-wrap:wrap">
        @foreach($types as $t)
        <a href="{{ route('accounts.vouchers.create', $t->code) }}" class="btn btn-outline btn-sm" title="New {{ $t->name }}"><i class="fa-solid fa-plus"></i> {{ $t->code }}</a>
        @endforeach
    </div>
</div>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-error">{{ $errors->first() }}</div>@endif

<form method="GET" class="card" style="padding:12px;margin-bottom:12px;display:flex;gap:8px;flex-wrap:wrap;align-items:end">
    <div><label>From</label><input type="date" name="from" value="{{ $from->toDateString() }}" class="form-control"></div>
    <div><label>To</label><input type="date" name="to" value="{{ $to->toDateString() }}" class="form-control"></div>
    <div>
        <label>Type</label>
        <select name="type" class="form-control">
            <option value="">All</option>
            @foreach($types as $t)
                <option value="{{ $t->code }}" {{ $typeCode===$t->code?'selected':'' }}>{{ $t->name }}</option>
            @endforeach
        </select>
    </div>
    <div style="flex:1;min-width:180px"><label>Search</label><input type="text" name="q" value="{{ $q }}" placeholder="No. / narration / ref…" class="form-control"></div>
    <button class="btn btn-primary"><i class="fa-solid fa-magnifying-glass"></i></button>
</form>

<div class="card">
    <div class="table-wrap">
        <table class="sc-table">
            <thead>
                <tr>
                    <th>Date</th><th>Voucher No.</th><th>Type</th><th>Party</th><th>Narration</th>
                    <th class="text-right">Amount</th><th>Status</th><th>By</th><th></th>
                </tr>
            </thead>
            <tbody>
            @forelse($vouchers as $v)
                <tr style="{{ $v->status==='Cancelled' ? 'opacity:0.55' : '' }}">
                    <td class="text-small">{{ \Carbon\Carbon::parse($v->voucher_date)->format('d-M-Y') }}</td>
                    <td><a href="{{ route('accounts.vouchers.show',$v) }}" class="mono fw-600">{{ $v->voucher_no }}</a></td>
                    <td><span class="badge badge-navy">{{ $v->type->name }}</span></td>
                    <td class="text-small">{{ $v->party?->name ?: '—' }}</td>
                    <td class="text-small" style="max-width:280px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="{{ $v->narration }}">{{ $v->narration ?: '—' }}</td>
                    <td class="text-right mono fw-600">₹{{ number_format((float)$v->total_amount,2) }}</td>
                    <td><span class="badge {{ $v->status==='Posted'?'badge-green':($v->status==='Cancelled'?'badge-red':'badge-gray') }}">{{ $v->status }}</span></td>
                    <td class="text-small">{{ $v->createdBy?->name ?: '—' }}</td>
                    <td>
                        <a href="{{ route('accounts.vouchers.show',$v) }}" class="btn btn-sm btn-outline"><i class="fa-solid fa-eye"></i></a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="9" style="text-align:center;padding:18px;color:var(--sc-muted)">No vouchers in this range.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div style="margin-top:10px">{{ $vouchers->links() }}</div>

@endsection
