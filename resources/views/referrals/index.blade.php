@extends('layouts.app')
@section('title','Referral Sources')
@section('page-title','Referral Incentive Management')
@section('content')

<div class="page-header">
  <div class="page-header-left">
    <h1>Referral Sources</h1>
    <div class="breadcrumb">Administration &rsaquo; Referrals</div>
  </div>
  <div style="display:flex;gap:8px">
    <a href="{{ route('referrals.dashboard') }}" class="btn btn-outline"><i class="fa-solid fa-chart-bar"></i> Dashboard</a>
    <a href="{{ route('referrals.create') }}" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Add Source</a>
  </div>
</div>

<div class="card">
  <div class="table-wrap">
    <table class="sc-table">
      <thead>
        <tr>
          <th>Name</th>
          <th>Type</th>
          <th>Rate</th>
          <th>Patients</th>
          <th class="text-right">Total Earned</th>
          <th class="text-right">Paid</th>
          <th class="text-right">Pending</th>
          <th>Status</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
      @forelse($sources as $s)
      @php
        $pending = max(0, ($s->total_earned ?? 0) - ($s->total_paid ?? 0));
      @endphp
      <tr>
        <td>
          <div class="fw-600">{{ $s->name }}</div>
          @if($s->contact_person)<div class="text-small text-muted">{{ $s->contact_person }}</div>@endif
        </td>
        <td>
          @php $tColors = ['doctor'=>'teal','hospital'=>'navy','clinic'=>'green','other'=>'gray']; @endphp
          <span class="badge badge-{{ $tColors[$s->type] ?? 'gray' }}">{{ ucfirst($s->type) }}</span>
        </td>
        <td>
          @if($s->rate_type === 'percentage')
            {{ $s->rate_value }}%
          @else
            ₹{{ number_format($s->rate_value, 0) }} fixed
          @endif
        </td>
        <td class="text-center">{{ $s->patients_count }}</td>
        <td class="text-right mono">₹{{ number_format($s->total_earned ?? 0, 2) }}</td>
        <td class="text-right mono text-muted">₹{{ number_format($s->total_paid ?? 0, 2) }}</td>
        <td class="text-right mono fw-600" style="{{ $pending > 0 ? 'color:var(--sc-amber)' : '' }}">
          ₹{{ number_format($pending, 2) }}
        </td>
        <td>
          @if($s->is_active)
            <span class="badge badge-green">Active</span>
          @else
            <span class="badge badge-red">Inactive</span>
          @endif
        </td>
        <td>
          <div style="display:flex;gap:6px">
            <a href="{{ route('referrals.show', $s) }}" class="btn btn-sm btn-outline">View</a>
            <a href="{{ route('referrals.edit', $s) }}" class="btn btn-sm btn-outline"><i class="fa-solid fa-pencil"></i></a>
            <form method="POST" action="{{ route('referrals.destroy', $s) }}" onsubmit="return confirm('Delete {{ addslashes($s->name) }}?')">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-sm btn-outline" style="color:var(--sc-red);border-color:var(--sc-red)"><i class="fa-solid fa-trash"></i></button>
            </form>
          </div>
        </td>
      </tr>
      @empty
      <tr><td colspan="9" class="text-center text-muted" style="padding:40px">No referral sources yet. <a href="{{ route('referrals.create') }}">Add one</a>.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
</div>

@endsection
