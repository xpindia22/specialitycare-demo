{{-- FILE: resources/views/admissions/discharge.blade.php --}}
@extends('layouts.app')
@section('title','Discharge Patient')
@section('page-title','Discharge Patient')
@section('content')

<div class="page-header">
    <div class="page-header-left">
        <h1>Discharge Patient</h1>
        <div class="breadcrumb">
            <a href="{{ route('admissions.index') }}">Admissions</a> &rsaquo;
            <a href="{{ route('admissions.show', $admission) }}">{{ $admission->ipd_no }}</a> &rsaquo; Discharge
        </div>
    </div>
    <a href="{{ route('admissions.show', $admission) }}" class="btn btn-outline">Cancel</a>
</div>

{{-- Patient strip --}}
<div class="patient-strip mb-4">
    <div class="ps-avatar">{{ strtoupper(substr($admission->patient->first_name,0,1)) }}</div>
    <div>
        <div class="ps-name">{{ $admission->patient->full_name }}</div>
        <div class="ps-jhc_id">{{ $admission->ipd_no }} &nbsp;·&nbsp; {{ $admission->ward->name }}, Bed {{ $admission->bed->bed_number }}</div>
        <div class="ps-meta">
            <span><i class="fa-solid fa-calendar-days"></i> Admitted {{ $admission->admission_date->format('d M Y') }}</span>
            <span><i class="fa-solid fa-clock"></i> {{ $admission->days_admitted }} day{{ $admission->days_admitted != 1 ? 's' : '' }}</span>
            <span><i class="fa-solid fa-stethoscope"></i> {{ $admission->admittingDoctor->full_name }}</span>
        </div>
    </div>
</div>

{{-- checklist sits outside the discharge form (its sign-off buttons are forms of their own) --}}
@php $outstanding = \App\Services\DischargeChecklist::outstanding($admission); @endphp
@include('admissions._discharge-checklist')

<form method="POST" action="{{ route('admissions.process-discharge', $admission) }}">
@csrf @method('PUT')
<div class="grid" style="grid-template-columns:1fr 320px;gap:20px;align-items:start">
    <div>
        <div class="card mb-3">
            <div class="card-header"><i class="fa-solid fa-person-walking-arrow-right"></i> Discharge Information</div>
            <div class="card-body">
                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-label">Discharge Date &amp; Time <span class="req">*</span></label>
                        <input type="datetime-local" name="discharge_date" class="form-control @error('discharge_date') is-invalid @enderror"
                               value="{{ old('discharge_date', now()->format('Y-m-d\TH:i')) }}" required>
                        @error('discharge_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Discharge Condition <span class="req">*</span></label>
                        <select name="discharge_condition" class="form-control @error('discharge_condition') is-invalid @enderror" required>
                            <option value="">— Select —</option>
                            @foreach(\App\Models\Admission::DISCHARGE_CONDITIONS as $val => $label)
                            <option value="{{ $val }}" {{ old('discharge_condition', 'improved') === $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('discharge_condition')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Discharge Notes</label>
                    <textarea name="discharge_notes" class="form-control" rows="3"
                              placeholder="Brief clinical notes at time of discharge…">{{ old('discharge_notes') }}</textarea>
                </div>
                <div class="form-group">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px">
                        <label class="form-label" style="margin:0">Discharge Summary</label>
                        <button type="button" id="autoDraftBtn" onclick="autoDraftSummary()"
                            class="btn btn-sm btn-outline" style="font-size:11px">
                            <i class="fa-solid fa-wand-magic-sparkles"></i> Auto-fill from Record
                        </button>
                    </div>
                    <textarea name="discharge_summary" id="dischargeSummary" class="form-control" rows="12"
                              placeholder="Detailed discharge summary — hospital course, investigations, treatment given, follow-up instructions…">{{ old('discharge_summary', $admission->discharge_summary) }}</textarea>
                    <div class="form-text">Auto-fill pulls diagnoses, notes and active medicines from the IPD record. You can edit before saving.</div>
                </div>
            </div>
        </div>
    </div>

    <div>
        <div class="card mb-3">
            <div class="card-header"><i class="fa-solid fa-bed-pulse"></i> Admission Summary</div>
            <div class="card-body" style="font-size:13px;display:flex;flex-direction:column;gap:10px">
                <div><div class="form-label">IPD No.</div><div class="mono fw-600">{{ $admission->ipd_no }}</div></div>
                <div><div class="form-label">Admitted</div><div>{{ $admission->admission_date->format('d M Y, h:i A') }}</div></div>
                <div><div class="form-label">Days Admitted</div><div class="fw-700">{{ $admission->days_admitted }}</div></div>
                <div><div class="form-label">Ward / Bed</div><div>{{ $admission->ward->name }} — Bed {{ $admission->bed->bed_number }}</div></div>
                <div><div class="form-label">Doctor</div><div>{{ $admission->admittingDoctor->full_name }}</div></div>
                @if($admission->provisional_diagnosis)
                <div><div class="form-label">Diagnosis</div><div>{{ $admission->provisional_diagnosis }}</div></div>
                @endif
            </div>
        </div>

        {{-- Billing quick links --}}
        @php
            $totalCharges  = $admission->charges()->sum('amount')
                           + $admission->packages()->sum('quoted_price');
            $totalDeposits = $admission->deposits()->sum('amount')
                           + $admission->packages()->sum('paid_amount');
            $netBalance    = $totalCharges - $totalDeposits;
        @endphp
        <div class="card mb-3">
            <div class="card-header"><i class="fa-solid fa-file-invoice-dollar"></i> Billing</div>
            <div class="card-body" style="font-size:13px;display:flex;flex-direction:column;gap:10px">
                <div style="display:flex;justify-content:space-between">
                    <span class="form-label" style="margin:0">Total Charges</span>
                    <span class="fw-600">₹{{ number_format($totalCharges, 2) }}</span>
                </div>
                <div style="display:flex;justify-content:space-between">
                    <span class="form-label" style="margin:0">Total Paid</span>
                    <span class="fw-600" style="color:var(--sc-green)">₹{{ number_format($totalDeposits, 2) }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;border-top:1px solid var(--sc-border);padding-top:8px">
                    @if($netBalance > 0)
                        <span class="fw-700">Balance Due</span>
                        <span class="fw-700" style="color:#f59e0b">₹{{ number_format($netBalance, 2) }}</span>
                    @elseif($netBalance < 0)
                        <span class="fw-700">Refund to Patient</span>
                        <span class="fw-700" style="color:var(--sc-green)">₹{{ number_format(abs($netBalance), 2) }}</span>
                    @else
                        <span class="fw-700">Balance Due</span>
                        <span class="fw-700" style="color:var(--sc-green)">Settled ✓</span>
                    @endif
                </div>
                <div style="display:flex;gap:8px;margin-top:4px">
                    <a href="{{ route('admissions.show', $admission) }}#tab-billing"
                       class="btn btn-sm btn-outline" style="flex:1;justify-content:center">
                        <i class="fa-solid fa-plus"></i> Add Charges
                    </a>
                    <a href="{{ route('admissions.bill', $admission) }}" target="_blank"
                       class="btn btn-sm btn-outline" style="flex:1;justify-content:center">
                        <i class="fa-solid fa-print"></i> Print Bill
                    </a>
                </div>
            </div>
        </div>

        <div class="alert alert-warning">
            <i class="fa-solid fa-triangle-exclamation"></i>
            Bed <strong>{{ $admission->bed->bed_number }}</strong> will go to <b>Cleaning</b>; mark it clean on the Bed Board to make it available.
        </div>

        @if($outstanding)
            @if(auth()->user()->isAdmin())
            <div class="form-group">
                <label class="form-label" style="color:#b45309">Override — checklist not complete ({{ count($outstanding) }} item{{ count($outstanding) > 1 ? 's' : '' }})</label>
                <textarea name="discharge_override" id="discharge_override" class="form-control" rows="3" maxlength="300" placeholder="Type the reason here, e.g. LAMA, expired, bill on credit"
                          @if($errors->has('checklist')) autofocus style="border:2px solid var(--sc-red);box-shadow:0 0 0 4px rgba(232,76,76,.2)" @endif>{{ old('discharge_override') }}</textarea>
            </div>
            <button type="submit" class="btn btn-amber" style="width:100%">
                <i class="fa-solid fa-person-walking-arrow-right"></i> Discharge with override
            </button>
            @else
            <button type="button" class="btn btn-amber" style="width:100%;opacity:.5;cursor:not-allowed" disabled title="Complete the discharge checklist first">
                <i class="fa-solid fa-lock"></i> Complete the checklist to discharge
            </button>
            @endif
        @else
        <button type="submit" class="btn btn-amber" style="width:100%">
            <i class="fa-solid fa-person-walking-arrow-right"></i> Confirm Discharge
        </button>
        @endif
    </div>
@if($errors->has('checklist'))
<script>document.addEventListener('DOMContentLoaded', () => { const t = document.getElementById('discharge_override'); if (t) { t.scrollIntoView({block: 'center'}); t.focus(); } });</script>
@endif
</div>
</form>
<script>
function autoDraftSummary() {
    const btn = document.getElementById('autoDraftBtn');
    const ta  = document.getElementById('dischargeSummary');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Loading…';
    fetch('{{ route('admissions.draft-summary', $admission) }}')
        .then(r => r.json())
        .then(d => {
            ta.value = d.summary;
            btn.innerHTML = '<i class="fa-solid fa-check"></i> Done';
            setTimeout(() => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-wand-magic-sparkles"></i> Auto-fill from Record';
            }, 2000);
        })
        .catch(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-wand-magic-sparkles"></i> Auto-fill from Record';
            alert('Could not load draft. Please try again.');
        });
}
</script>
@endsection
