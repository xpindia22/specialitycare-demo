@extends('layouts.app')
@section('title', 'Insurers & TPAs')
@section('page-title','Insurers & TPAs')
@section('content')

<div class="page-header">
    <div class="page-header-left">
        <h1>Insurers &amp; TPAs</h1>
        <div class="breadcrumb">Manage insurance companies, TPAs and government schemes</div>
    </div>
    @include('insurance._toolbar', ['inline'=>true])
</div>

@if(session('success'))<div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> {{ session('success') }}</div>@endif

<div class="card">
    <div class="table-wrap">
        <table class="sc-table zebra">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Code</th>
                    <th>Contact</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($insurers as $ins)
                <tr>
                    <td style="font-weight:600">{{ $ins->name }}</td>
                    <td>{{ \App\Models\Insurer::TYPES[$ins->type] ?? $ins->type }}</td>
                    <td class="mono text-small text-muted">{{ $ins->code ?? '—' }}</td>
                    <td>
                        {{ $ins->contact_person ?? '' }}
                        @if($ins->contact_phone)<span class="text-small text-muted" style="display:block">{{ $ins->contact_phone }}</span>@endif
                    </td>
                    <td>
                        @if($ins->is_active)
                            <span class="badge badge-green">Active</span>
                        @else
                            <span class="badge badge-gray">Inactive</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('insurers.edit', $ins) }}" class="btn btn-outline btn-sm"><i class="fa-solid fa-pen"></i> Edit</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" style="text-align:center;padding:24px;color:var(--sc-muted)">No insurers added yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $insurers->links() }}</div>
</div>
@endsection
