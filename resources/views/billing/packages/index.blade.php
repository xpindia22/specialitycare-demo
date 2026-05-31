@extends('layouts.app')
@section('title', 'Billing Packages')
@section('content')

<div class="page-header">
    <div class="page-header-left"><h1>Billing Packages</h1></div>
    <div class="page-header-right">
        <a href="{{ route('packages.create') }}" class="btn btn-primary"><i class="fa-solid fa-plus me-1"></i> New Package</a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="card">
    <div class="table-wrap">
        <table class="sc-table">
            <thead>
                <tr>
                    <th>Package Name</th>
                    <th>Category</th>
                    <th>Includes</th>
                    <th style="text-align:right">Price (₹)</th>
                    <th style="text-align:center">Max Days</th>
                    <th style="text-align:center">Used</th>
                    <th>Status</th>
                    <th style="width:100px"></th>
                </tr>
            </thead>
            <tbody>
            @forelse($packages as $pkg)
            <tr>
                <td class="fw-600">{{ $pkg->name }}</td>
                <td>{{ \App\Models\BillingPackage::CATEGORIES[$pkg->category] ?? ($pkg->category ?? '—') }}</td>
                <td style="font-size:12px;color:#666">{{ $pkg->includes_label }}</td>
                <td style="text-align:right" class="fw-600">{{ number_format($pkg->package_price, 0) }}</td>
                <td style="text-align:center">{{ $pkg->max_days ?? '—' }}</td>
                <td style="text-align:center">{{ $pkg->admissions_count }}</td>
                <td>
                    @if($pkg->is_active)
                        <span class="badge badge-green">Active</span>
                    @else
                        <span class="badge badge-grey">Inactive</span>
                    @endif
                </td>
                <td>
                    <div style="display:flex;gap:6px;align-items:center">
                        <a href="{{ route('packages.edit', $pkg) }}" class="btn btn-sm btn-outline"><i class="fa-solid fa-pen"></i></a>
                        <form method="POST" action="{{ route('packages.destroy', $pkg) }}" style="display:inline" onsubmit="return confirm('Deactivate this package?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline" style="color:#94a3b8"><i class="fa-solid fa-trash"></i></button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="8" class="text-center text-muted" style="padding:40px">No packages defined yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($packages->hasPages())
    <div style="padding:12px 16px;border-top:1px solid var(--sc-border)">{{ $packages->links() }}</div>
    @endif
</div>
@endsection
