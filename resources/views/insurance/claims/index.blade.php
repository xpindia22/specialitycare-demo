@extends('layouts.app')
@section('title', 'Insurance Claims')
@section('page-title','Insurance / TPA')
@section('content')

@php $badgeMap = ['blue'=>'navy','indigo'=>'purple']; @endphp

<div class="page-header">
    <div class="page-header-left">
        <h1>Insurance Claims</h1>
        <div class="breadcrumb">Track TPA / insurance claim lifecycle</div>
    </div>
    @include('insurance._toolbar', ['inline'=>true])
</div>

@if(session('success'))<div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> {{ session('success') }}</div>@endif

{{-- Stats --}}
<div class="grid grid-4 mb-4">
    @foreach([['pending','Pending / Query','amber','fa-clock'],['approved','Approved','teal','fa-circle-check'],['settled','Settled','green','fa-indian-rupee-sign'],['rejected','Rejected','red','fa-circle-xmark']] as [$k,$lbl,$col,$icon])
    <div class="stat-card">
        <div class="stat-icon {{ $col }}"><i class="fa-solid {{ $icon }}"></i></div>
        <div><div class="stat-value">{{ $stats[$k] }}</div><div class="stat-label">{{ $lbl }}</div></div>
    </div>
    @endforeach
</div>

{{-- Filters --}}
<form method="GET" class="card" style="padding:12px;margin-bottom:14px;display:flex;gap:8px;flex-wrap:wrap;align-items:center">
    <input type="text" name="q" value="{{ $q }}" placeholder="Claim no / policy / patient name" class="form-control" style="flex:1;min-width:220px">
    <select name="status" class="form-control" style="min-width:170px">
        <option value="all" {{ $status==='all'?'selected':'' }}>All Statuses</option>
        @foreach(\App\Models\InsuranceClaim::STATUSES as $k => $s)
        <option value="{{ $k }}" {{ $status===$k?'selected':'' }}>{{ $s['label'] }}</option>
        @endforeach
    </select>
    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-filter"></i> Filter</button>
</form>

<div class="card">
    <div class="table-wrap">
        <table class="sc-table zebra">
            <thead>
                <tr>
                    <th>Patient</th>
                    <th>IPD No.</th>
                    <th>Insurer / TPA</th>
                    <th>Claim No.</th>
                    <th style="text-align:right">Approved</th>
                    <th style="text-align:right">Settled</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse($claims as $c)
                @php $col = $badgeMap[$c->status_color] ?? $c->status_color; @endphp
                <tr style="cursor:pointer" onclick="location='{{ route('insurance.claims.show', $c) }}'">
                    <td style="font-weight:600">{{ $c->patient->full_name }}</td>
                    <td class="mono text-small text-muted">{{ $c->admission->ipd_no ?? '—' }}</td>
                    <td>{{ $c->insurer->name }}</td>
                    <td class="mono text-small">{{ $c->claim_no ?? '—' }}</td>
                    <td style="text-align:right">{{ $c->approved_amount ? '₹'.number_format($c->approved_amount,2) : '—' }}</td>
                    <td style="text-align:right">{{ $c->settled_amount ? '₹'.number_format($c->settled_amount,2) : '—' }}</td>
                    <td><span class="badge badge-{{ $col }}">{{ $c->status_label }}</span></td>
                    <td class="text-small text-muted">{{ $c->created_at->format('d M Y') }}</td>
                </tr>
                @empty
                <tr><td colspan="8" style="text-align:center;padding:24px;color:var(--sc-muted)">No claims found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $claims->withQueryString()->links() }}</div>
</div>
@endsection
