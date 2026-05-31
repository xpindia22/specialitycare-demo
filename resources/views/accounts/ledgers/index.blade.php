@extends('layouts.app')
@section('title','Ledgers')
@section('page-title','Ledgers')
@section('content')

<div class="page-header">
    <div class="page-header-left">
        <a href="{{ route('accounts.index') }}" class="text-small" style="text-decoration:none"><i class="fa-solid fa-arrow-left"></i> Accounts</a>
        <h1>Chart of Accounts</h1>
    </div>
    <a href="{{ route('accounts.ledgers.create') }}" class="btn btn-primary"><i class="fa-solid fa-plus"></i> New Ledger</a>
</div>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-error">{{ $errors->first() }}</div>@endif

<form method="GET" class="card" style="padding:12px;margin-bottom:12px;display:flex;gap:8px;flex-wrap:wrap">
    <input type="text" name="q" value="{{ $q }}" placeholder="Search name or code…" class="form-control" style="flex:1;min-width:200px">
    <select name="group_id" class="form-control" style="min-width:180px">
        <option value="">All groups</option>
        @foreach($groups as $g)
            <option value="{{ $g->id }}" {{ (int)$groupId===$g->id ? 'selected':'' }}>{{ str_repeat('— ', $g->parent_id ? 1 : 0) }}{{ $g->name }}</option>
        @endforeach
    </select>
    <select name="party_type" class="form-control" style="min-width:140px">
        <option value="">Any type</option>
        @foreach(['Customer','Supplier','Employee','Bank','Cash','Tax','Other'] as $pt)
            <option value="{{ $pt }}" {{ $partyType===$pt?'selected':'' }}>{{ $pt }}</option>
        @endforeach
    </select>
    <button class="btn btn-outline"><i class="fa-solid fa-magnifying-glass"></i></button>
</form>

<div class="card">
    <div class="table-wrap">
        <table class="sc-table">
            <thead>
                <tr>
                    <th>Code</th><th>Name</th><th>Group</th><th>Party Type</th>
                    <th class="text-right">Opening</th>
                    <th>GSTIN</th><th>State</th><th></th>
                </tr>
            </thead>
            <tbody>
            @forelse($ledgers as $l)
                <tr>
                    <td class="mono text-small">{{ $l->code }}</td>
                    <td class="fw-600">
                        <a href="{{ route('accounts.ledgers.show',$l) }}">{{ $l->name }}</a>
                        @if($l->is_system) <span class="badge badge-gray" title="System ledger — referenced by voucher engine">sys</span>@endif
                        @if(!$l->is_active) <span class="badge badge-red">inactive</span>@endif
                    </td>
                    <td class="text-small">{{ $l->group?->name }}</td>
                    <td class="text-small">{{ $l->party_type ?: '—' }}</td>
                    <td class="text-right mono">{{ number_format((float)$l->opening_balance,2) }} {{ $l->opening_balance_dr_cr }}</td>
                    <td class="text-small mono">{{ $l->gstin ?: '—' }}</td>
                    <td class="text-small">{{ $l->state_code ?: '—' }}</td>
                    <td>
                        <div style="display:flex;gap:4px">
                            <a href="{{ route('accounts.ledgers.show',$l) }}" class="btn btn-sm btn-outline" title="Statement"><i class="fa-solid fa-file-lines"></i></a>
                            <a href="{{ route('accounts.ledgers.edit',$l) }}" class="btn btn-sm btn-outline"><i class="fa-solid fa-pen"></i></a>
                            @if(!$l->is_system)
                            <form method="POST" action="{{ route('accounts.ledgers.destroy',$l) }}" onsubmit="return confirm('Delete this ledger?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline" style="color:var(--sc-red);border-color:var(--sc-red)"><i class="fa-solid fa-trash"></i></button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" style="text-align:center;padding:18px;color:var(--sc-muted)">No ledgers found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div style="margin-top:10px">{{ $ledgers->links() }}</div>

@endsection
