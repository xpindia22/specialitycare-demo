{{-- FILE: resources/views/lab/create-order.blade.php --}}
@extends('layouts.app')
@section('title','Create Lab Order')
@section('page-title','Create Lab Order')
@section('content')
<div class="page-header">
    <div class="page-header-left">
        <h1>Create Lab Order</h1>
        @if(isset($admission) && $admission)
        <div class="breadcrumb"><a href="{{ route('admissions.show', $admission) }}">{{ $admission->ipd_no }}</a> &rsaquo; Lab Tests</div>
        @endif
    </div>
    @if(isset($admission) && $admission)
    <a href="{{ route('admissions.show', $admission) }}#tab-labs" class="btn btn-outline"><i class="fa-solid fa-arrow-left"></i> Back to Admission</a>
    @endif
</div>

@if(isset($admission) && $admission)
<div class="alert" style="background:#eff6ff;border:1px solid #bfdbfe;color:#1e40af;border-radius:8px;padding:10px 16px;margin-bottom:16px;display:flex;gap:10px;align-items:center">
    <i class="fa-solid fa-bed-pulse"></i>
    <span>IPD Admission: <strong>{{ $admission->ipd_no }}</strong> &mdash; {{ $admission->patient->full_name }} &mdash; {{ $admission->ward->name }} Bed {{ $admission->bed->bed_number }}</span>
</div>
@endif

<form method="POST" action="{{ route('lab.store-order') }}" id="lab-form">
@csrf
@if(isset($encounter_id) && $encounter_id)<input type="hidden" name="encounter_id" value="{{ $encounter_id }}">@endif
@if(isset($admission_id) && $admission_id)<input type="hidden" name="admission_id" value="{{ $admission_id }}">@endif

<div class="grid" style="grid-template-columns:1fr 320px;gap:20px;align-items:start">
<div>
    <!-- Patient -->
    <div class="card mb-3 lab-patient-card">
        <div class="card-header"><i class="fa-solid fa-user"></i> Patient</div>
        <div class="card-body">
            @if(isset($patient) && $patient)
                <div class="patient-strip" style="padding:12px"><div class="ps-avatar">{{ strtoupper(substr($patient->first_name,0,1)) }}</div>
                <div><div class="ps-name">{{ $patient->full_name }}</div><div class="ps-jhc_id">{{ $patient->jhc_id }}</div></div></div>
                <input type="hidden" name="patient_id" value="{{ $patient->id }}">
            @else
                <select name="patient_id" id="patient-select" class="form-control select2" required style="width:100%">
                    <option value="">Search patient by name, JHC ID, or phone...</option>
                    @foreach($patients as $p)
                        <option value="{{ $p->id }}" {{ old('patient_id') == $p->id ? 'selected' : '' }}>
                            {{ $p->full_name }}{{ $p->jhc_id ? ' - '.$p->jhc_id : '' }}{{ $p->phone ? ' - '.$p->phone : '' }}
                        </option>
                    @endforeach
                </select>
            @endif
        </div>
    </div>

    <!-- Test/Panel Selection -->
    <div class="card">
        <div class="card-header"><i class="fa-solid fa-flask-vial"></i> Select Tests / Panels</div>
        <div class="card-body">
            @include('lab._test-picker')
        </div>
    </div>
</div>

<!-- Sidebar -->
<div>
    <div class="card" style="position:sticky;top:84px">
        <div class="card-header"><i class="fa-solid fa-cart-shopping"></i> Selected Tests</div>
        <div id="selected-tests" style="min-height:60px;padding:12px 16px">
            <div id="no-selection" class="text-center text-muted text-small" style="padding:16px">No tests selected</div>
        </div>
        <div class="card-footer">
            <table style="width:100%">
                <tr><td style="padding:4px 0;color:var(--sc-muted);font-size:13px">Subtotal</td><td style="text-align:right;font-weight:600" id="lab-subtotal">₹0</td></tr>
                <tr><td style="padding:4px 0;color:var(--sc-muted);font-size:13px">Discount</td><td style="text-align:right">
                    <input type="number" name="discount_amount" id="lab-disc" class="form-control" style="width:80px;text-align:right;padding:4px 8px" value="0" min="0" oninput="updateTotal()">
                </td></tr>
                <tr><td style="padding:6px 0;font-weight:700;font-size:15px">Total</td><td style="text-align:right;font-weight:700;font-size:15px;color:var(--sc-navy)" id="lab-total">₹0</td></tr>
            </table>
        </div>
        <div style="padding:14px 16px">
            <div class="form-group">
                <label class="form-label">Priority</label>
                <select name="priority" class="form-control">
                    <option value="Routine">Routine</option>
                    <option value="Urgent">Urgent</option>
                    <option value="Stat">STAT</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-control" rows="2" placeholder="Clinical notes for lab..."></textarea>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center"><i class="fa-solid fa-flask-vial"></i> Create Lab Order</button>
        </div>
    </div>
</div>
</div>
</form>
@endsection

@push('styles')
<style>
.lab-patient-card { overflow: visible; position: relative; z-index: 30; }
.lab-patient-card .card-body { overflow: visible; }
.select2-container--classic .select2-results__option {
    padding: 8px 12px;
    color: var(--sc-text);
    font-size: 13px;
}
.select2-container--classic .select2-results__option--highlighted[aria-selected] {
    background: var(--sc-teal);
    color: #fff;
}
.select2-dropdown.lab-patient-dropdown {
    border-color: var(--sc-border);
    box-shadow: var(--shadow-md);
    z-index: 9999;
}
</style>
@endpush

@push('scripts')
<script>
$(function() {
    const $patientSelect = $('#patient-select');
    if ($patientSelect.data('select2')) {
        $patientSelect.select2('destroy');
    }
    $patientSelect.select2({
        theme: 'classic',
        width: '100%',
        placeholder: 'Search patient by name, JHC ID, or phone...',
        allowClear: true,
        dropdownParent: $('.lab-patient-card'),
        dropdownCssClass: 'lab-patient-dropdown'
    });
});
</script>
@endpush
