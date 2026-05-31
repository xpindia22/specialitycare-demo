@extends('layouts.app')
@section('title', $ledger->exists ? 'Edit Ledger' : 'New Ledger')
@section('page-title', $ledger->exists ? 'Edit Ledger' : 'New Ledger')
@section('content')

<div class="page-header">
    <div class="page-header-left">
        <a href="{{ route('accounts.ledgers.index') }}" class="text-small"><i class="fa-solid fa-arrow-left"></i> Ledgers</a>
        <h1>{{ $ledger->exists ? "Edit: {$ledger->name}" : 'New Ledger' }}</h1>
    </div>
</div>

@if($errors->any())<div class="alert alert-error"><ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

<form method="POST" action="{{ $ledger->exists ? route('accounts.ledgers.update',$ledger) : route('accounts.ledgers.store') }}" class="card" style="padding:16px;max-width:760px">
    @csrf
    @if($ledger->exists) @method('PUT') @endif

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
        <div>
            <label>Name *</label>
            <input name="name" value="{{ old('name', $ledger->name) }}" class="form-control" required>
        </div>
        <div>
            <label>Code <span class="text-muted text-small">(auto if blank)</span></label>
            <input name="code" value="{{ old('code', $ledger->code) }}" class="form-control mono">
        </div>
        <div style="grid-column:span 2">
            <label>Group *</label>
            <select name="group_id" class="form-control" required>
                <option value="">— select —</option>
                @foreach($groups as $g)
                    <option value="{{ $g->id }}" {{ old('group_id', $ledger->group_id)==$g->id?'selected':'' }}>
                        {{ $g->parent_id ? '— ' : '' }}{{ $g->name }} ({{ $g->nature }})
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label>Opening Balance</label>
            <input type="number" step="0.01" min="0" name="opening_balance" value="{{ old('opening_balance', (float)$ledger->opening_balance) }}" class="form-control mono">
        </div>
        <div>
            <label>Dr / Cr</label>
            <select name="opening_balance_dr_cr" class="form-control">
                @foreach(['Dr','Cr'] as $s)
                    <option value="{{ $s }}" {{ old('opening_balance_dr_cr', $ledger->opening_balance_dr_cr)===$s?'selected':'' }}>{{ $s }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label>Party Type</label>
            <select name="party_type" class="form-control">
                <option value="">—</option>
                @foreach(['Customer','Supplier','Employee','Bank','Cash','Tax','Other'] as $pt)
                    <option value="{{ $pt }}" {{ old('party_type',$ledger->party_type)===$pt?'selected':'' }}>{{ $pt }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label>GSTIN</label>
            <input name="gstin" value="{{ old('gstin', $ledger->gstin) }}" class="form-control mono" placeholder="15-char GSTIN">
        </div>
        <div>
            <label>State Code <span class="text-muted text-small">(e.g., 29=KA, 35=A&amp;N)</span></label>
            <input name="state_code" value="{{ old('state_code', $ledger->state_code) }}" class="form-control mono" maxlength="4">
        </div>
        <div>
            <label>Contact Phone</label>
            <input name="contact_phone" value="{{ old('contact_phone', $ledger->contact_phone) }}" class="form-control">
        </div>
        <div>
            <label>Contact Email</label>
            <input type="email" name="contact_email" value="{{ old('contact_email', $ledger->contact_email) }}" class="form-control">
        </div>
        <div style="grid-column:span 2">
            <label>Address</label>
            <textarea name="address" rows="2" class="form-control">{{ old('address', $ledger->address) }}</textarea>
        </div>
        <div style="grid-column:span 2">
            <label>Notes</label>
            <textarea name="notes" rows="2" class="form-control">{{ old('notes', $ledger->notes) }}</textarea>
        </div>
        <div>
            <label style="display:flex;gap:6px;align-items:center;margin-top:22px">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $ledger->exists ? $ledger->is_active : 1) ? 'checked' : '' }}> Active
            </label>
        </div>
    </div>

    <div style="margin-top:16px;display:flex;gap:8px">
        <button class="btn btn-primary"><i class="fa-solid fa-save"></i> Save</button>
        <a href="{{ route('accounts.ledgers.index') }}" class="btn btn-outline">Cancel</a>
    </div>
</form>

@endsection
