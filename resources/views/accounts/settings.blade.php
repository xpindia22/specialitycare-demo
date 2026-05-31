@extends('layouts.app')
@section('title','Accounts Settings')
@section('page-title','Accounts Settings')
@section('content')

<div class="page-header">
    <div class="page-header-left">
        <a href="{{ route('accounts.index') }}" class="text-small"><i class="fa-solid fa-arrow-left"></i> Accounts</a>
        <h1><i class="fa-solid fa-gear"></i> Accounts Settings</h1>
    </div>
</div>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-error">{{ $errors->first() }}</div>@endif

<form method="POST" action="{{ route('accounts.settings.update') }}" class="card" style="padding:16px;max-width:760px">
    @csrf @method('PUT')

    <h3 style="margin-top:0">Clinic GST profile</h3>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
        <div>
            <label>Legal name (for GST)</label>
            <input name="clinic_legal_name" value="{{ old('clinic_legal_name', $values['clinic_legal_name'] ?? '') }}" class="form-control" placeholder="As per GST registration certificate">
        </div>
        <div>
            <label>GSTIN</label>
            <input name="clinic_gstin" value="{{ old('clinic_gstin', $values['clinic_gstin'] ?? '') }}" class="form-control mono" maxlength="20" placeholder="15-character GSTIN">
        </div>
        <div>
            <label>State code <span class="text-muted text-small">(first 2 digits of GSTIN — e.g., 35 = A&amp;N, 29 = KA)</span></label>
            <input name="clinic_state_code" value="{{ old('clinic_state_code', $values['clinic_state_code'] ?? '') }}" class="form-control mono" maxlength="4">
        </div>
        <div>
            <label>State name</label>
            <input name="clinic_state_name" value="{{ old('clinic_state_name', $values['clinic_state_name'] ?? '') }}" class="form-control">
        </div>
    </div>

    <h3 style="margin-top:18px">Financial year</h3>
    <div>
        <label>FY start month</label>
        <select name="fy_start_month" class="form-control" style="max-width:200px">
            @foreach(['1'=>'January','4'=>'April (Indian FY)','7'=>'July','10'=>'October'] as $m=>$name)
                <option value="{{ $m }}" {{ (string)($values['fy_start_month'] ?? 4)===$m?'selected':'' }}>{{ $name }}</option>
            @endforeach
        </select>
        <div class="text-small text-muted">Default 4 (April) for Indian FY. Voucher numbering resets at the start of each FY.</div>
    </div>

    <h3 style="margin-top:18px">Auto-posting</h3>
    <div style="display:flex;flex-direction:column;gap:6px">
        <label style="display:flex;gap:8px;align-items:center">
            <input type="hidden" name="accounts_auto_post_bills" value="0">
            <input type="checkbox" name="accounts_auto_post_bills" value="1" {{ ($values['accounts_auto_post_bills'] ?? '1')==='1' ? 'checked' : '' }}>
            <span>When a bill is created in Billing, auto-post a Sales voucher (Dr Sundry Debtor, Cr Income heads + Cr Output CGST/SGST/IGST)</span>
        </label>
        <label style="display:flex;gap:8px;align-items:center">
            <input type="hidden" name="accounts_auto_post_purchases" value="0">
            <input type="checkbox" name="accounts_auto_post_purchases" value="1" {{ ($values['accounts_auto_post_purchases'] ?? '1')==='1' ? 'checked' : '' }}>
            <span>When pharmacy stock is received, auto-post a Purchase voucher (Dr Stock + Dr Input CGST/SGST/IGST, Cr Sundry Creditor)</span>
        </label>
    </div>

    <div style="margin-top:16px"><button class="btn btn-primary"><i class="fa-solid fa-save"></i> Save</button></div>
</form>

@endsection
