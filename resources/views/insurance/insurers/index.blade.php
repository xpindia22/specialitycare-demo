@extends('layouts.app')
@section('title', 'Insurers & TPAs')
@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Insurers & TPAs</h1>
        <p class="text-sm text-gray-500">Manage insurance companies, TPAs and government schemes</p>
    </div>
    <a href="{{ route('insurers.create') }}" class="inline-flex items-center gap-2 bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Add Insurer / TPA
    </a>
</div>

@if(session('success'))
<div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">{{ session('success') }}</div>
@endif

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
                <th class="px-4 py-3 text-left font-medium text-gray-600">Name</th>
                <th class="px-4 py-3 text-left font-medium text-gray-600">Type</th>
                <th class="px-4 py-3 text-left font-medium text-gray-600">Code</th>
                <th class="px-4 py-3 text-left font-medium text-gray-600">Contact</th>
                <th class="px-4 py-3 text-left font-medium text-gray-600">Status</th>
                <th class="px-4 py-3 text-left font-medium text-gray-600">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($insurers as $ins)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 font-medium text-gray-900">{{ $ins->name }}</td>
                <td class="px-4 py-3 text-gray-600">{{ \App\Models\Insurer::TYPES[$ins->type] ?? $ins->type }}</td>
                <td class="px-4 py-3 text-gray-500 font-mono text-xs">{{ $ins->code ?? '—' }}</td>
                <td class="px-4 py-3 text-gray-600">
                    {{ $ins->contact_person ?? '' }}
                    @if($ins->contact_phone)<span class="block text-xs text-gray-400">{{ $ins->contact_phone }}</span>@endif
                </td>
                <td class="px-4 py-3">
                    @if($ins->is_active)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Active</span>
                    @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500">Inactive</span>
                    @endif
                </td>
                <td class="px-4 py-3">
                    <a href="{{ route('insurers.edit', $ins) }}" class="text-blue-600 hover:underline text-xs">Edit</a>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">No insurers added yet.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="px-4 py-3 border-t border-gray-100">{{ $insurers->links() }}</div>
</div>
@endsection
