@extends('layouts.app')
@section('title', 'Claim ' . ($claim->claim_no ?? '#'.$claim->id))
@section('content')
@php $col = $claim->status_color @endphp

<div class="flex items-center justify-between mb-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('insurance.claims.index') }}" class="text-gray-400 hover:text-gray-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Claim {{ $claim->claim_no ?? '#'.$claim->id }}</h1>
            <p class="text-sm text-gray-500">{{ $claim->insurer->name }} · {{ $claim->patient->full_name }}</p>
        </div>
    </div>
    <div class="flex items-center gap-3">
        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-{{ $col }}-100 text-{{ $col }}-700">{{ $claim->status_label }}</span>
        <a href="{{ route('insurance.claims.edit', $claim) }}" class="border border-gray-300 text-gray-700 px-3 py-1.5 rounded-lg text-sm hover:bg-gray-50">Edit</a>
    </div>
</div>

@if(session('success'))
<div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">{{ session('success') }}</div>
@endif

<div class="grid grid-cols-3 gap-5 mb-6">
    {{-- Claim info --}}
    <div class="col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <h2 class="font-semibold text-gray-700 mb-4">Claim Details</h2>
        <dl class="grid grid-cols-3 gap-x-6 gap-y-3 text-sm">
            <div><dt class="text-gray-500">IPD No.</dt><dd class="font-medium">{{ $claim->admission->ipd_no ?? '—' }}</dd></div>
            <div><dt class="text-gray-500">Patient</dt><dd class="font-medium">{{ $claim->patient->full_name }}</dd></div>
            <div><dt class="text-gray-500">Insurer / TPA</dt><dd class="font-medium">{{ $claim->insurer->name }}</dd></div>
            <div><dt class="text-gray-500">Policy No.</dt><dd class="font-medium font-mono">{{ $claim->policy_no ?? '—' }}</dd></div>
            <div><dt class="text-gray-500">Member ID</dt><dd class="font-medium font-mono">{{ $claim->member_id ?? '—' }}</dd></div>
            <div><dt class="text-gray-500">Policy Holder</dt><dd class="font-medium">{{ $claim->policy_holder_name ?? '—' }}</dd></div>
            <div><dt class="text-gray-500">Sum Insured</dt><dd class="font-medium">{{ $claim->sum_insured ? '₹'.number_format($claim->sum_insured,2) : '—' }}</dd></div>
            <div><dt class="text-gray-500">Approved</dt><dd class="font-medium text-teal-700">{{ $claim->approved_amount ? '₹'.number_format($claim->approved_amount,2) : '—' }}</dd></div>
            <div><dt class="text-gray-500">Settled</dt><dd class="font-medium text-green-700">{{ $claim->settled_amount ? '₹'.number_format($claim->settled_amount,2) : '—' }}</dd></div>
            <div><dt class="text-gray-500">Co-pay</dt><dd class="font-medium text-amber-700">{{ $claim->co_pay_amount ? '₹'.number_format($claim->co_pay_amount,2) : '—' }}</dd></div>
            <div><dt class="text-gray-500">Pending</dt><dd class="font-medium text-red-700">{{ $claim->pending_amount ? '₹'.number_format($claim->pending_amount,2) : '—' }}</dd></div>
            <div><dt class="text-gray-500">Policy Expiry</dt><dd class="font-medium">{{ $claim->policy_expiry?->format('d M Y') ?? '—' }}</dd></div>
            <div class="col-span-3"><dt class="text-gray-500">Diagnosis / ICD</dt><dd class="font-medium">{{ $claim->diagnosis_icd ?? '—' }}</dd></div>
            @if($claim->remarks)<div class="col-span-3"><dt class="text-gray-500">Remarks</dt><dd class="font-medium">{{ $claim->remarks }}</dd></div>@endif
            @if($claim->rejection_reason)<div class="col-span-3"><dt class="text-gray-500 text-red-600">Rejection Reason</dt><dd class="font-medium text-red-600">{{ $claim->rejection_reason }}</dd></div>@endif
        </dl>
    </div>

    {{-- Update status --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <h2 class="font-semibold text-gray-700 mb-4">Update Status</h2>
        <form method="POST" action="{{ route('insurance.claims.status', $claim) }}" class="space-y-3 text-sm">
            @csrf
            <div>
                <label class="block text-gray-600 mb-1">Status</label>
                <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @foreach(\App\Models\InsuranceClaim::STATUSES as $k => $s)
                    <option value="{{ $k }}" {{ $claim->status === $k ? 'selected' : '' }}>{{ $s['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-gray-600 mb-1">Approved Amount (₹)</label>
                <input type="number" name="approved_amount" step="0.01" min="0" value="{{ $claim->approved_amount }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-gray-600 mb-1">Settled Amount (₹)</label>
                <input type="number" name="settled_amount" step="0.01" min="0" value="{{ $claim->settled_amount }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-gray-600 mb-1">Remarks</label>
                <textarea name="remarks" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">{{ $claim->remarks }}</textarea>
            </div>
            <div>
                <label class="block text-gray-600 mb-1">Rejection Reason</label>
                <textarea name="rejection_reason" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">{{ $claim->rejection_reason }}</textarea>
            </div>
            <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-lg text-sm font-medium hover:bg-blue-700">Update Status</button>
        </form>
    </div>
</div>

{{-- Documents --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-5">
    <div class="flex items-center justify-between mb-4">
        <h2 class="font-semibold text-gray-700">Documents</h2>
        <button onclick="document.getElementById('uploadForm').classList.toggle('hidden')" class="text-sm text-blue-600 hover:underline">+ Upload</button>
    </div>

    <form id="uploadForm" method="POST" action="{{ route('insurance.documents.upload', $claim) }}" enctype="multipart/form-data"
          class="hidden bg-gray-50 rounded-lg p-4 mb-4 grid grid-cols-3 gap-4 items-end">
        @csrf
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Document Type</label>
            <select name="document_type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                @foreach(\App\Models\InsuranceDocument::DOCUMENT_TYPES as $k => $lbl)
                <option value="{{ $k }}">{{ $lbl }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">File (PDF / Image, max 10 MB)</label>
            <input type="file" name="document_file" accept=".pdf,.jpg,.jpeg,.png" class="w-full text-sm" required>
        </div>
        <div>
            <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-lg text-sm font-medium hover:bg-blue-700">Upload</button>
        </div>
    </form>

    @if($claim->documents->isEmpty())
    <p class="text-sm text-gray-400">No documents uploaded yet.</p>
    @else
    <table class="w-full text-sm">
        <thead class="text-xs text-gray-500 border-b border-gray-100">
            <tr>
                <th class="pb-2 text-left font-medium">Type</th>
                <th class="pb-2 text-left font-medium">File</th>
                <th class="pb-2 text-right font-medium">Size</th>
                <th class="pb-2 text-left font-medium">Uploaded By</th>
                <th class="pb-2 text-left font-medium">Date</th>
                <th></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @foreach($claim->documents as $doc)
            <tr>
                <td class="py-2 text-gray-700">{{ $doc->type_label }}</td>
                <td class="py-2"><a href="{{ asset('storage/'.$doc->file_path) }}" target="_blank" class="text-blue-600 hover:underline truncate max-w-xs inline-block">{{ $doc->original_name ?? basename($doc->file_path) }}</a></td>
                <td class="py-2 text-right text-gray-500">{{ $doc->file_size_human }}</td>
                <td class="py-2 text-gray-600">{{ $doc->uploadedBy?->name ?? '—' }}</td>
                <td class="py-2 text-gray-500 text-xs">{{ $doc->created_at->format('d M Y') }}</td>
                <td class="py-2 text-right">
                    <form method="POST" action="{{ route('insurance.documents.delete', [$claim, $doc]) }}" onsubmit="return confirm('Remove this document?')">
                        @csrf @method('DELETE')
                        <button class="text-red-500 hover:text-red-700 text-xs">Remove</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
</div>
@endsection
