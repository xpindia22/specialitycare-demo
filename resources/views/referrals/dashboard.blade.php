@extends('layouts.app')
@section('title','Referral Dashboard')
@section('page-title','Referral Dashboard')
@section('content')

<div class="page-header">
  <div class="page-header-left">
    <h1>Referral Dashboard</h1>
    <div class="breadcrumb"><a href="{{ route('referrals.index') }}">Referrals</a> &rsaquo; Dashboard</div>
  </div>
  <a href="{{ route('referrals.index') }}" class="btn btn-outline">All Sources</a>
</div>

<div class="grid grid-3 mb-4">
  <div class="stat-card">
    <div class="stat-icon red"><i class="fa-solid fa-clock"></i></div>
    <div><div class="stat-value">₹{{ number_format($totalPending, 0) }}</div><div class="stat-label">Total Pending Incentives</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon amber"><i class="fa-solid fa-indian-rupee-sign"></i></div>
    <div><div class="stat-value">₹{{ number_format($thisMonthEarned, 0) }}</div><div class="stat-label">This Month Earned</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon teal"><i class="fa-solid fa-handshake"></i></div>
    <div><div class="stat-value">{{ $topReferrers->count() }}</div><div class="stat-label">Active Referrers</div></div>
  </div>
</div>

<div class="grid" style="grid-template-columns:1fr 1fr;gap:20px">
  <div class="card">
    <div class="card-header"><i class="fa-solid fa-trophy"></i> Top Referrers</div>
    <div class="table-wrap">
      <table class="sc-table">
        <thead><tr><th>#</th><th>Source</th><th>Patients</th><th>Earned</th></tr></thead>
        <tbody>
        @foreach($topReferrers as $i => $s)
        <tr>
          <td class="mono fw-700" style="color:var(--sc-{{ $i < 3 ? 'gold' : 'muted' }})">{{ $i+1 }}</td>
          <td><a href="{{ route('referrals.show', $s) }}" class="fw-600">{{ $s->name }}</a><div class="text-small text-muted">{{ ucfirst($s->type) }}</div></td>
          <td class="text-center">{{ $s->patients_count }}</td>
          <td class="mono">₹{{ number_format($s->total_earned ?? 0, 0) }}</td>
        </tr>
        @endforeach
        </tbody>
      </table>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><i class="fa-solid fa-receipt"></i> Recent Payments</div>
    @if($recentPayments->isEmpty())
    <div class="card-body text-center text-muted" style="padding:30px">No payment records.</div>
    @else
    <div class="table-wrap">
      <table class="sc-table">
        <thead><tr><th>Date</th><th>Patient</th><th>Source</th><th>Amount</th><th>Status</th></tr></thead>
        <tbody>
        @foreach($recentPayments as $rp)
        <tr>
          <td class="text-small">{{ $rp->created_at->format('d M') }}</td>
          <td class="fw-600">{{ $rp->patient->full_name }}</td>
          <td class="text-small text-muted">{{ $rp->referralSource->name }}</td>
          <td class="mono">₹{{ number_format($rp->calculated_amount, 0) }}</td>
          <td>
            @php $sc = ['pending'=>'amber','partial'=>'teal','paid'=>'green'][$rp->status] ?? 'gray'; @endphp
            <span class="badge badge-{{ $sc }}">{{ ucfirst($rp->status) }}</span>
          </td>
        </tr>
        @endforeach
        </tbody>
      </table>
    </div>
    @endif
  </div>
</div>

@endsection
