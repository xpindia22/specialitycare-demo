@extends('layouts.app')
@section('title', 'Insurance Claims')
@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Insurance Claims</h1>
        <p class="text-sm text-gray-500">Track TPA / insurance claim lifecycle</p>
    </div>
    <a href="{{ route('insurance.claims.create') }}" class="inline-flex items-center gap-2 bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        New Claim
    </a>
</div>

{{-- Stats --}}
<div class="grid grid-cols-4 gap-4 mb-6">
    @foreach([['pending','Pending / Query','amber'],['approved','Approved','teal'],['settled','Settled','green'],['rejected','Rejected','red']] as [$k,$lbl,$col])
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm px-5 py-4">
        <div class="text-2xl font-bold text-{{ $col }}-600">{{ $stats[$k] }}</div>
        <div class="text-xs text-gray-500 mt-1">{{ $lbl }}</div>
    </div>
    @endforeach
</div>

{{-- Filters --}}
<form method="GET" class="flex gap-3 mb-5 flex-wrap">
    <input type="text" name="q" value="{{ $q }}" placeholder="Claim no / policy / patient name" class="border border-gray-300 rounded-lg px-3 py-2 text-sm flex-1 min-w-48 focus:outline-none focus:ring-2 focus:ring-blue-500">
    <select name="status" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        <option value="all" {{ $status==='all'?'selected':'' }}>All Statuses</option>
        @foreach(\App\Models\InsuranceClaim::STATUSES as $k => $s)
        <option value="{{ $k }}" {{ $status===$k?'selected':'' }}>{{ $s['label'] }}</option>
        @endforeach
    </select>
    <button type="submit" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg text-sm hover:bg-gray-200">Filter</button>
</form>

@if(session('success'))
<div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">{{ session('success') }}</div>
@endif

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
                <th class="px-4 py-3 text-left font-medium text-gray-600">Patient</th>
                <th class="px-4 py-3 text-left font-medium text-gray-600">IPD No.</th>
                <th class="px-4 py-3 text-left font-medium text-gray-600">Insurer/TPA</th>
                <th class="px-4 py-3 text-left font-medium text-gray-600">Claim No.</th>
                <th class="px-4 py-3 text-right font-medium text-gray-600">Approved</th>
                <th class="px-4 py-3 text-right font-medium text-gray-600">Settled</th>
                <th class="px-4 py-3 text-left font-medium text-gray-600">Status</th>
                <th class="px-4 py-3 text-left font-medium text-gray-600">Date</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($claims as $c)
            @php $col = $c->status_color @endphp
            <tr class="hover:bg-gray-50 cursor-pointer" onclick="location='{{ route('insurance.claims.show', $c) }}'">
                <td class="px-4 py-3 font-medium text-gray-900">{{ $c->patient->full_name }}</td>
                <td class="px-4 py-3 text-gray-600 font-mono text-xs">{{ $c->admission->ipd_no ?? '—' }}</td>
                <td class="px-4 py-3 text-gray-600">{{ $c->insurer->name }}</td>
                <td class="px-4 py-3 text-gray-600 font-mono text-xs">{{ $c->claim_no ?? '—' }}</td>
                <td class="px-4 py-3 text-right text-gray-700">{{ $c->approved_amount ? '₹'.number_format($c->approved_amount,2) : '—' }}</td>
                <td class="px-4 py-3 text-right text-gray-700">{{ $c->settled_amount ? '₹'.number_format($c->settled_amount,2) : '—' }}</td>
                <td class="px-4 py-3">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-{{ $col }}-100 text-{{ $col }}-700">{{ $c->status_label }}</span>
                </td>
                <td class="px-4 py-3 text-gray-500 text-xs">{{ $c->created_at->format('d M Y') }}</td>
            </tr>
            @empty
            <tr><td colspan="8" class="px-4 py-8 text-center text-gray-400">No claims found.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="px-4 py-3 border-t border-gray-100">{{ $claims->withQueryString()->links() }}</div>
</div>
@endsection
