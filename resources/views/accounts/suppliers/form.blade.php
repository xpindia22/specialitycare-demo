@extends('layouts.app')
@section('title', $supplier->exists ? 'Edit Supplier' : 'New Supplier')
@section('page-title', $supplier->exists ? 'Edit Supplier' : 'New Supplier')
@section('content')

<div class="page-header">
    <div class="page-header-left">
        <a href="{{ route('accounts.suppliers.index') }}" class="text-small"><i class="fa-solid fa-arrow-left"></i> Suppliers</a>
        <h1>{{ $supplier->exists ? "Edit: {$supplier->name}" : 'New Supplier' }}</h1>
        <div class="text-small text-muted">Will be filed under Sundry Creditors (Party Type = Supplier). Both set automatically — no need to think about them.</div>
    </div>
</div>

@if($errors->any())<div class="alert alert-error"><ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

<form method="POST" action="{{ $supplier->exists ? route('accounts.suppliers.update', $supplier) : route('accounts.suppliers.store') }}" class="card" style="padding:16px;max-width:720px">
    @csrf
    @if($supplier->exists) @method('PUT') @endif

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
        <div style="grid-column:span 2">
            <label>Supplier Name *</label>
            <input name="name" value="{{ old('name', $supplier->name) }}" class="form-control" required autofocus placeholder="e.g., Apollo Distributors">
        </div>

        <div>
            <label>Code <span class="text-muted text-small">(auto if blank)</span></label>
            <input name="code" value="{{ old('code', $supplier->code) }}" class="form-control mono" placeholder="SCR-...">
        </div>
        <div>
            <label>Phone</label>
            <input name="contact_phone" value="{{ old('contact_phone', $supplier->contact_phone) }}" class="form-control" placeholder="+91-...">
        </div>

        <div>
            <label>GSTIN <span class="text-muted text-small">(15-char — needed for Input GST credit)</span></label>
            <input name="gstin" value="{{ old('gstin', $supplier->gstin) }}" class="form-control mono" placeholder="29AABCT1332L1ZG">
        </div>
        <div>
            <label>State Code <span class="text-muted text-small">(2-digit, e.g., 29=KA, 33=TN, 35=A&amp;N)</span></label>
            <input name="state_code" value="{{ old('state_code', $supplier->state_code) }}" class="form-control mono" maxlength="4">
        </div>

        <div>
            <label>Email</label>
            <input type="email" name="contact_email" value="{{ old('contact_email', $supplier->contact_email) }}" class="form-control">
        </div>
        <div>
            <label style="display:flex;gap:6px;align-items:center;margin-top:22px">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $supplier->exists ? $supplier->is_active : 1) ? 'checked' : '' }}> Active
            </label>
        </div>

        <div style="grid-column:span 2">
            <label>Address</label>
            <textarea name="address" rows="2" class="form-control">{{ old('address', $supplier->address) }}</textarea>
        </div>

        <div>
            <label>Opening Balance (₹) <span class="text-muted text-small">(only if migrating from old books)</span></label>
            <input type="number" step="0.01" min="0" name="opening_balance" value="{{ old('opening_balance', (float) $supplier->opening_balance) }}" class="form-control mono">
        </div>
        <div>
            <label>Dr / Cr</label>
            <select name="opening_balance_dr_cr" class="form-control">
                @foreach(['Cr' => 'Cr (we owe them — normal)', 'Dr' => 'Dr (they owe us — rare)'] as $v => $label)
                    <option value="{{ $v }}" {{ old('opening_balance_dr_cr', $supplier->opening_balance_dr_cr ?: 'Cr') === $v ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div style="grid-column:span 2">
            <label>Notes</label>
            <textarea name="notes" rows="2" class="form-control" placeholder="Anything you want to remember about this supplier">{{ old('notes', $supplier->notes) }}</textarea>
        </div>
    </div>

    <div style="margin-top:16px;display:flex;gap:8px">
        <button class="btn btn-primary"><i class="fa-solid fa-save"></i> Save</button>
        <a href="{{ route('accounts.suppliers.index') }}" class="btn btn-outline">Cancel</a>
    </div>
</form>

@endsection
