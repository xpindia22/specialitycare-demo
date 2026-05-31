@extends('layouts.app')
@section('title','Add Referral Source')
@section('page-title','Add Referral Source')
@section('content')

<div class="page-header">
  <div class="page-header-left">
    <h1>Add Referral Source</h1>
    <div class="breadcrumb"><a href="{{ route('referrals.index') }}">Referrals</a> &rsaquo; Add Source</div>
  </div>
</div>

<form method="POST" action="{{ route('referrals.store') }}">
@csrf
<div class="grid" style="grid-template-columns:1fr 300px;gap:20px;align-items:start">
  <div class="card">
    <div class="card-header"><i class="fa-solid fa-handshake"></i> Referral Source Details</div>
    <div class="card-body">
      <div class="form-row cols-2">
        <div class="form-group">
          <label class="form-label">Name <span class="req">*</span></label>
          <input type="text" name="name" class="form-control" value="{{ old('name') }}" required placeholder="Dr. Name / Hospital Name">
        </div>
        <div class="form-group">
          <label class="form-label">Type <span class="req">*</span></label>
          <select name="type" class="form-control" required id="typeSelect" onchange="toggleOtherType(this.value)">
            @foreach(['doctor'=>'Doctor','hospital'=>'Hospital','clinic'=>'Clinic','nurse'=>'Nurse','other'=>'Other'] as $v => $l)
            <option value="{{ $v }}" {{ old('type') === $v ? 'selected' : '' }}>{{ $l }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group" id="otherTypeBox" style="display:{{ old('type')==='other' ? 'block' : 'none' }}">
          <label class="form-label">Specify Type <span class="req">*</span></label>
          <input type="text" name="type_other" class="form-control" value="{{ old('type_other') }}"
                 placeholder="e.g. Physiotherapist, Pharmacy, NGO…" id="otherTypeInput">
        </div>
        <div class="form-group">
          <label class="form-label">Contact Person</label>
          <input type="text" name="contact_person" class="form-control" value="{{ old('contact_person') }}" placeholder="Name of contact">
        </div>
        <div class="form-group">
          <label class="form-label">Phone</label>
          <input type="tel" name="phone" class="form-control" value="{{ old('phone') }}">
        </div>
        <div class="form-group">
          <label class="form-label">Email</label>
          <input type="email" name="email" class="form-control" value="{{ old('email') }}">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Address</label>
        <textarea name="address" class="form-control" rows="2">{{ old('address') }}</textarea>
      </div>
      <div class="form-group">
        <label class="form-label">Notes</label>
        <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
      </div>
    </div>
  </div>

  <div style="display:flex;flex-direction:column;gap:16px">
    <div class="card">
      <div class="card-header"><i class="fa-solid fa-percent"></i> Incentive Rate</div>
      <div class="card-body">
        <div class="form-group">
          <label class="form-label">Rate Type <span class="req">*</span></label>
          <select name="rate_type" class="form-control" required id="rateType" onchange="toggleRateLabel()">
            <option value="percentage" {{ old('rate_type') === 'percentage' ? 'selected' : '' }}>Percentage of Bill</option>
            <option value="fixed"      {{ old('rate_type') === 'fixed' ? 'selected' : '' }}>Fixed Amount per Patient</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label" id="rateLabel">Rate (%) <span class="req">*</span></label>
          <input type="number" name="rate_value" class="form-control" value="{{ old('rate_value', 0) }}" step="0.01" min="0" required>
        </div>
        <div class="form-group">
          <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
            <input type="checkbox" name="is_active" value="1" {{ old('is_active', '1') === '1' ? 'checked' : '' }}>
            <span class="form-label" style="margin:0">Active</span>
          </label>
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%">
          <i class="fa-solid fa-floppy-disk"></i> Save Referral Source
        </button>
      </div>
    </div>
  </div>
</div>
</form>

<script>
function toggleOtherType(val) {
    const box   = document.getElementById('otherTypeBox');
    const input = document.getElementById('otherTypeInput');
    box.style.display = val === 'other' ? 'block' : 'none';
    input.required    = val === 'other';
}
toggleOtherType(document.getElementById('typeSelect').value);

function toggleRateLabel() {
    const t = document.getElementById('rateType').value;
    document.getElementById('rateLabel').innerHTML = t === 'percentage' ? 'Rate (%) <span class="req">*</span>' : 'Amount (₹) <span class="req">*</span>';
}
toggleRateLabel();
</script>
@endsection
