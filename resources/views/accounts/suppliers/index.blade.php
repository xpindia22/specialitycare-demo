@extends('layouts.app')
@section('title','Suppliers')
@section('page-title','Suppliers')
@section('content')

<div class="page-header">
    <div class="page-header-left">
        <a href="{{ route('accounts.index') }}" class="text-small"><i class="fa-solid fa-arrow-left"></i> Accounts</a>
        <h1><i class="fa-solid fa-truck-fast"></i> Suppliers</h1>
        <div class="text-small text-muted">People you buy medicines / supplies from. Hidden complexity (group, party type) is handled for you.</div>
    </div>
    <div style="display:flex;gap:8px">
        <a href="{{ route('accounts.expenses.pay-supplier') }}" class="btn btn-outline"><i class="fa-solid fa-money-bill"></i> Pay Supplier</a>
        <a href="{{ route('accounts.suppliers.create') }}" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Add Supplier</a>
    </div>
</div>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-error">{{ $errors->first() }}</div>@endif

<form method="GET" class="card" style="padding:12px;margin-bottom:14px;display:flex;gap:10px;align-items:end">
    <div style="flex:1">
        <label class="text-small">Search</label>
        <input name="q" value="{{ $q }}" class="form-control" placeholder="Name, code, or GSTIN">
    </div>
    <label style="display:flex;gap:6px;align-items:center;margin-bottom:8px">
        <input type="checkbox" name="show_inactive" value="1" {{ $showAll ? 'checked' : '' }}> Show inactive
    </label>
    <button class="btn btn-outline">Filter</button>
</form>

@if($suppliers->isEmpty())
<div class="card"><div class="card-body" style="text-align:center;padding:24px">
    <p>No suppliers yet. <a href="{{ route('accounts.suppliers.create') }}">Add your first supplier</a>.</p>
</div></div>
@else
<div class="card">
    <div class="table-wrap">
        <table class="sc-table" style="margin:0">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Code</th>
                    <th>GSTIN</th>
                    <th>State</th>
                    <th>Phone</th>
                    <th class="text-right">Outstanding</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @foreach($suppliers as $s)
                <tr style="{{ $s->is_active ? '' : 'opacity:0.55' }}">
                    <td class="fw-600">{{ $s->name }}</td>
                    <td class="mono text-small">{{ $s->code }}</td>
                    <td class="mono text-small">{{ $s->gstin ?: '—' }}</td>
                    <td class="text-small">{{ $s->state_code ?: '—' }}</td>
                    <td class="text-small">{{ $s->contact_phone ?: '—' }}</td>
                    <td class="text-right mono fw-600 {{ $s->outstanding > 0 ? '' : 'text-muted' }}">
                        {{ $s->outstanding > 0 ? '₹' . number_format($s->outstanding, 2) : '—' }}
                    </td>
                    <td><span class="badge {{ $s->is_active ? 'badge-green' : 'badge-gray' }}">{{ $s->is_active ? 'Active' : 'Inactive' }}</span></td>
                    <td>
                        <div style="display:flex;gap:4px">
                            @if($s->is_active && $s->outstanding > 0)
                                <a href="{{ route('accounts.expenses.pay-supplier', ['supplier' => $s->id]) }}" class="btn btn-sm btn-outline" title="Pay {{ $s->name }}"><i class="fa-solid fa-money-bill"></i></a>
                            @endif
                            <a href="{{ route('accounts.ledgers.show', $s) }}" class="btn btn-sm btn-outline" title="View ledger statement"><i class="fa-solid fa-list"></i></a>
                            <a href="{{ route('accounts.suppliers.edit', $s) }}" class="btn btn-sm btn-outline" title="Edit"><i class="fa-solid fa-pen"></i></a>
                            <form method="POST" action="{{ route('accounts.suppliers.destroy', $s) }}" onsubmit="return confirm('Remove this supplier? (If they have any posted vouchers, they will be marked Inactive instead.)')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline" style="color:var(--sc-red);border-color:var(--sc-red)" title="Delete or inactivate"><i class="fa-solid fa-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
            @endforeach
            </tbody>
            <tfoot>
                <tr style="background:#f0f0f0">
                    <td colspan="5" class="fw-700">{{ $suppliers->where('is_active',true)->count() }} active supplier(s)</td>
                    <td class="text-right mono fw-700">₹{{ number_format($suppliers->sum('outstanding'), 2) }}</td>
                    <td colspan="2" class="text-small text-muted">total outstanding</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endif

@endsection
