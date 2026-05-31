@extends('layouts.app')
@section('title', isset($claim->id) ? 'Edit Claim' : 'New Insurance Claim')
@section('content')
<div class="max-w-3xl">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('insurance.claims.index') }}" class="text-gray-400 hover:text-gray-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <h1 class="text-2xl font-bold text-gray-800">{{ isset($claim->id) ? 'Edit' : 'New' }} Insurance Claim</h1>
    </div>

    @if($errors->any())
    <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
        <ul class="list-disc list-inside space-y-1">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
    @endif

    <form method="POST" action="{{ isset($claim->id) ? route('insurance.claims.update', $claim) : route('insurance.claims.store') }}"
          class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-5">
        @csrf
        @if(isset($claim->id)) @method('PUT') @endif

        {{-- Patient / Admission --}}
        <div class="grid grid-cols-2 gap-5">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Admission (IPD No.) <span class="text-red-500">*</span></label>
                @if($admission)
                    <input type="hidden" name="admission_id" value="{{ $admission->id }}">
                    <input type="hidden" name="patient_id" value="{{ $admission->patient->id }}">
                    <div class="border border-gray-200 rounded-lg px-3 py-2 text-sm bg-gray-50">
                        {{ $admission->ipd_no }} — {{ $admission->patient->full_name }}
                    </div>
                @else
                    <input type="text" id="ipdSearch" placeholder="Search IPD No. or patient name…"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                           value="{{ isset($claim->admission) ? $claim->admission->ipd_no.' — '.$claim->admission->patient->full_name : old('admission_search') }}">
                    <input type="hidden" name="admission_id" id="admissionId" value="{{ old('admission_id', $claim->admission_id ?? '') }}" required>
                    <input type="hidden" name="patient_id"   id="patientId"   value="{{ old('patient_id',   $claim->patient_id   ?? '') }}" required>
                    <div id="ipdDropdown" class="hidden absolute z-20 bg-white border border-gray-200 rounded-lg shadow-lg mt-1 max-h-48 overflow-y-auto text-sm w-80"></div>
                @endif
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Insurer / TPA <span class="text-red-500">*</span></label>
                <select name="insurer_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    <option value="">— Select —</option>
                    @foreach($insurers as $ins)
                    <option value="{{ $ins->id }}" {{ old('insurer_id', $claim->insurer_id ?? '') == $ins->id ? 'selected' : '' }}>
                        {{ $ins->name }} ({{ \App\Models\Insurer::TYPES[$ins->type] ?? $ins->type }})
                    </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="grid grid-cols-3 gap-5">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Claim No.</label>
                <input type="text" name="claim_no" value="{{ old('claim_no', $claim->claim_no ?? '') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Policy No.</label>
                <input type="text" name="policy_no" value="{{ old('policy_no', $claim->policy_no ?? '') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Member ID</label>
                <input type="text" name="member_id" value="{{ old('member_id', $claim->member_id ?? '') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Policy Holder Name</label>
                <input type="text" name="policy_holder_name" value="{{ old('policy_holder_name', $claim->policy_holder_name ?? '') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Policy Expiry</label>
                <input type="date" name="policy_expiry" value="{{ old('policy_expiry', isset($claim->policy_expiry) ? $claim->policy_expiry->format('Y-m-d') : '') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
        </div>

        <div class="grid grid-cols-4 gap-5">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Sum Insured (₹)</label>
                <input type="number" name="sum_insured" step="0.01" min="0" value="{{ old('sum_insured', $claim->sum_insured ?? '') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Approved (₹)</label>
                <input type="number" name="approved_amount" step="0.01" min="0" value="{{ old('approved_amount', $claim->approved_amount ?? '') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Settled (₹)</label>
                <input type="number" name="settled_amount" step="0.01" min="0" value="{{ old('settled_amount', $claim->settled_amount ?? '') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Co-pay (₹)</label>
                <input type="number" name="co_pay_amount" step="0.01" min="0" value="{{ old('co_pay_amount', $claim->co_pay_amount ?? 0) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
        </div>

        <div class="grid grid-cols-2 gap-5">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @foreach(\App\Models\InsuranceClaim::STATUSES as $k => $s)
                    <option value="{{ $k }}" {{ old('status', $claim->status ?? 'draft') === $k ? 'selected' : '' }}>{{ $s['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Submitted Date</label>
                <input type="date" name="submitted_date" value="{{ old('submitted_date', isset($claim->submitted_date) ? $claim->submitted_date->format('Y-m-d') : '') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Diagnosis / ICD Codes</label>
                <input type="text" name="diagnosis_icd" value="{{ old('diagnosis_icd', $claim->diagnosis_icd ?? '') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="e.g. J18.9, K92.1">
            </div>
            <div class="col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Remarks</label>
                <textarea name="remarks" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('remarks', $claim->remarks ?? '') }}</textarea>
            </div>
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg text-sm font-medium hover:bg-blue-700">
                {{ isset($claim->id) ? 'Update' : 'Create' }} Claim
            </button>
            <a href="{{ route('insurance.claims.index') }}" class="border border-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm hover:bg-gray-50">Cancel</a>
        </div>
    </form>
</div>

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
    if (q.length < 2) { ipdDropdown.classList.add('hidden'); return; }
    debounce = setTimeout(() => {
        fetch('/admissions?q=' + encodeURIComponent(q) + '&format=json')
            .then(r => r.json()).then(data => {
                ipdDropdown.innerHTML = '';
                if (!data.length) {
                    ipdDropdown.innerHTML = '<div class="px-3 py-2 text-gray-400">No results</div>';
                } else {
                    data.forEach(a => {
                        const d = document.createElement('div');
                        d.className = 'px-3 py-2 hover:bg-gray-50 cursor-pointer';
                        d.textContent = a.ipd_no + ' — ' + a.patient_name;
                        d.addEventListener('click', () => {
                            ipdSearch.value = d.textContent;
                            admissionId.value = a.id;
                            patientId.value = a.patient_id;
                            ipdDropdown.classList.add('hidden');
                        });
                        ipdDropdown.appendChild(d);
                    });
                }
                ipdDropdown.classList.remove('hidden');
            });
    }, 300);
});
document.addEventListener('click', e => {
    if (!ipdSearch.contains(e.target) && !ipdDropdown.contains(e.target))
        ipdDropdown.classList.add('hidden');
});
</script>
@endif
@endsection
