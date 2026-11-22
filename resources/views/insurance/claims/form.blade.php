@extends('layouts.app')
@section('title', isset($claim->id) ? 'Edit Claim' : 'New Insurance Claim')
@section('page-title', isset($claim->id) ? 'Edit Insurance Claim' : 'New Insurance Claim')
@section('content')

<div class="page-header">
    <div class="page-header-left">
        <h1>{{ isset($claim->id) ? 'Edit' : 'New' }} Insurance Claim</h1>
        <div class="breadcrumb"><a href="{{ route('insurance.claims.index') }}">Insurance / TPA</a> &rsaquo; {{ isset($claim->id) ? 'Edit' : 'New' }}</div>
    </div>
    @include('insurance._toolbar', ['inline'=>true])
</div>

@if($errors->any())
<div class="alert alert-danger"><ul style="margin:0;padding-left:18px">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
@endif

<form method="POST" action="{{ isset($claim->id) ? route('insurance.claims.update', $claim) : route('insurance.claims.store') }}" class="card" style="max-width:920px">
    @csrf
    @if(isset($claim->id)) @method('PUT') @endif
    <div class="card-body">
        {{-- Patient / Admission + Insurer --}}
        <div class="grid" style="grid-template-columns:1fr 1fr;gap:16px">
            <div class="form-group" style="position:relative">
                <label>Admission (IPD No.) <span style="color:var(--sc-red)">*</span></label>
                @if($admission)
                    <input type="hidden" name="admission_id" value="{{ $admission->id }}">
                    <input type="hidden" name="patient_id" value="{{ $admission->patient->id }}">
                    <div class="form-control" style="background:var(--sc-surface)">{{ $admission->ipd_no }} — {{ $admission->patient->full_name }}</div>
                @else
                    <input type="text" id="ipdSearch" placeholder="Search IPD No. or patient name…" class="form-control"
                           value="{{ isset($claim->admission) ? $claim->admission->ipd_no.' — '.$claim->admission->patient->full_name : old('admission_search') }}">
                    <input type="hidden" name="admission_id" id="admissionId" value="{{ old('admission_id', $claim->admission_id ?? '') }}" required>
                    <input type="hidden" name="patient_id"   id="patientId"   value="{{ old('patient_id',   $claim->patient_id   ?? '') }}" required>
                    <div id="ipdDropdown" style="display:none;position:absolute;z-index:20;background:#fff;border:1px solid var(--sc-border);border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,.12);margin-top:2px;max-height:200px;overflow-y:auto;width:100%;left:0"></div>
                @endif
            </div>
            <div class="form-group">
                <label>Insurer / TPA <span style="color:var(--sc-red)">*</span></label>
                <select name="insurer_id" class="form-control" required>
                    <option value="">— Select —</option>
                    @foreach($insurers as $ins)
                    <option value="{{ $ins->id }}" {{ old('insurer_id', $claim->insurer_id ?? '') == $ins->id ? 'selected' : '' }}>
                        {{ $ins->name }} ({{ \App\Models\Insurer::TYPES[$ins->type] ?? $ins->type }})
                    </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="grid" style="grid-template-columns:1fr 1fr 1fr;gap:16px;margin-top:4px">
            <div class="form-group">
                <label>Claim No.</label>
                <input type="text" name="claim_no" value="{{ old('claim_no', $claim->claim_no ?? '') }}" class="form-control">
            </div>
            <div class="form-group">
                <label>Policy No.</label>
                <input type="text" name="policy_no" value="{{ old('policy_no', $claim->policy_no ?? '') }}" class="form-control">
            </div>
            <div class="form-group">
                <label>Member ID</label>
                <input type="text" name="member_id" value="{{ old('member_id', $claim->member_id ?? '') }}" class="form-control">
            </div>
            <div class="form-group" style="grid-column:span 2">
                <label>Policy Holder Name</label>
                <input type="text" name="policy_holder_name" value="{{ old('policy_holder_name', $claim->policy_holder_name ?? '') }}" class="form-control">
            </div>
            <div class="form-group">
                <label>Policy Expiry</label>
                <input type="date" name="policy_expiry" value="{{ old('policy_expiry', isset($claim->policy_expiry) ? $claim->policy_expiry->format('Y-m-d') : '') }}" class="form-control">
            </div>
        </div>

        <div class="grid" style="grid-template-columns:repeat(4,1fr);gap:16px;margin-top:4px">
            <div class="form-group">
                <label>Sum Insured (₹)</label>
                <input type="number" name="sum_insured" step="0.01" min="0" value="{{ old('sum_insured', $claim->sum_insured ?? '') }}" class="form-control">
            </div>
            <div class="form-group">
                <label>Approved (₹)</label>
                <input type="number" name="approved_amount" step="0.01" min="0" value="{{ old('approved_amount', $claim->approved_amount ?? '') }}" class="form-control">
            </div>
            <div class="form-group">
                <label>Settled (₹)</label>
                <input type="number" name="settled_amount" step="0.01" min="0" value="{{ old('settled_amount', $claim->settled_amount ?? '') }}" class="form-control">
            </div>
            <div class="form-group">
                <label>Co-pay (₹)</label>
                <input type="number" name="co_pay_amount" step="0.01" min="0" value="{{ old('co_pay_amount', $claim->co_pay_amount ?? 0) }}" class="form-control">
            </div>
        </div>

        <div class="grid" style="grid-template-columns:1fr 1fr;gap:16px;margin-top:4px">
            <div class="form-group">
                <label>Status</label>
                <select name="status" class="form-control">
                    @foreach(\App\Models\InsuranceClaim::STATUSES as $k => $s)
                    <option value="{{ $k }}" {{ old('status', $claim->status ?? 'draft') === $k ? 'selected' : '' }}>{{ $s['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Submitted Date</label>
                <input type="date" name="submitted_date" value="{{ old('submitted_date', isset($claim->submitted_date) ? $claim->submitted_date->format('Y-m-d') : '') }}" class="form-control">
            </div>
            <div class="form-group" style="grid-column:span 2">
                <label>Diagnosis / ICD Codes</label>
                <input type="text" name="diagnosis_icd" value="{{ old('diagnosis_icd', $claim->diagnosis_icd ?? '') }}" class="form-control" placeholder="e.g. J18.9, K92.1">
            </div>
            <div class="form-group" style="grid-column:span 2">
                <label>Remarks</label>
                <textarea name="remarks" rows="2" class="form-control">{{ old('remarks', $claim->remarks ?? '') }}</textarea>
            </div>
        </div>
    </div>
    <div class="card-footer" style="display:flex;gap:10px">
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> {{ isset($claim->id) ? 'Update' : 'Create' }} Claim</button>
        <a href="{{ route('insurance.claims.index') }}" class="btn btn-outline">Cancel</a>
    </div>
</form>

@if(!isset($admission))
<script>
const ipdSearch = document.getElementById('ipdSearch');
const ipdDropdown = document.getElementById('ipdDropdown');
const admissionId = document.getElementById('admissionId');
const patientId   = document.getElementById('patientId');
let debounce;

ipdSearch.addEventListener('input', function() {
    clearTimeout(debounce);
    const q = this.value.trim();
    if (q.length < 2) { ipdDropdown.style.display = 'none'; return; }
    debounce = setTimeout(() => {
        fetch('/admissions?q=' + encodeURIComponent(q) + '&format=json')
            .then(r => r.json()).then(data => {
                ipdDropdown.innerHTML = '';
                if (!data.length) {
                    ipdDropdown.innerHTML = '<div style="padding:8px 12px;color:var(--sc-muted)">No results</div>';
                } else {
                    data.forEach(a => {
                        const d = document.createElement('div');
                        d.style.cssText = 'padding:8px 12px;cursor:pointer';
                        d.onmouseover = () => d.style.background = 'var(--sc-surface)';
                        d.onmouseout  = () => d.style.background = '';
                        d.textContent = a.ipd_no + ' — ' + a.patient_name;
                        d.addEventListener('click', () => {
                            ipdSearch.value = d.textContent;
                            admissionId.value = a.id;
                            patientId.value = a.patient_id;
                            ipdDropdown.style.display = 'none';
                        });
                        ipdDropdown.appendChild(d);
                    });
                }
                ipdDropdown.style.display = 'block';
            });
    }, 300);
});
document.addEventListener('click', e => {
    if (!ipdSearch.contains(e.target) && !ipdDropdown.contains(e.target))
        ipdDropdown.style.display = 'none';
});
</script>
@endif
@endsection
