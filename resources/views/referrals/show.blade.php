@extends('layouts.app')
@section('title','Referral — ' . $source->name)
@section('page-title','Referral Source')
@section('content')

<div class="page-header">
  <div class="page-header-left">
    <h1>{{ $source->name }}</h1>
    <div class="breadcrumb"><a href="{{ route('referrals.index') }}">Referrals</a> &rsaquo; {{ $source->name }}</div>
  </div>
  <div style="display:flex;gap:8px">
    <a href="{{ route('referrals.edit', $source) }}" class="btn btn-outline"><i class="fa-solid fa-pencil"></i> Edit</a>
    <form method="POST" action="{{ route('referrals.destroy', $source) }}" onsubmit="return confirm('Delete {{ addslashes($source->name) }}? This cannot be undone.')">
        @csrf @method('DELETE')
        <button type="submit" class="btn btn-outline" style="color:var(--sc-red);border-color:var(--sc-red)">
            <i class="fa-solid fa-trash"></i> Delete
        </button>
    </form>
    <a href="{{ route('referrals.index') }}" class="btn btn-outline">Back</a>
  </div>
</div>

@if(session('success'))
<div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> {{ session('success') }}</div>
@endif

{{-- Stat Cards --}}
<div class="grid grid-4 mb-4">
  <div class="stat-card">
    <div class="stat-icon teal"><i class="fa-solid fa-users"></i></div>
    <div><div class="stat-value">{{ $stats['total_patients'] }}</div><div class="stat-label">Patients Referred</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon amber"><i class="fa-solid fa-indian-rupee-sign"></i></div>
    <div><div class="stat-value">₹{{ number_format($stats['total_earned'], 0) }}</div><div class="stat-label">Total Incentive</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon green"><i class="fa-solid fa-circle-check"></i></div>
    <div><div class="stat-value">₹{{ number_format($stats['total_paid'], 0) }}</div><div class="stat-label">Total Paid</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon red"><i class="fa-solid fa-clock"></i></div>
    <div><div class="stat-value">₹{{ number_format($stats['pending_balance'], 0) }}</div><div class="stat-label">Pending Balance</div></div>
  </div>
</div>

<div class="grid" style="grid-template-columns:1fr 340px;gap:20px;align-items:start">
  <div>
    {{-- Payments --}}
    <div class="card mb-4">
      <div class="card-header">
        <i class="fa-solid fa-money-bill-transfer"></i> Incentive Payments
        <div class="card-actions">
          <button class="btn btn-sm btn-primary" onclick="document.getElementById('payModal').classList.add('open')">
            <i class="fa-solid fa-plus"></i> Record Payment
          </button>
        </div>
      </div>
      @if($payments->isEmpty())
      <div class="card-body text-center text-muted" style="padding:40px">No payment records yet.</div>
      @else
      <div class="table-wrap">
        <table class="sc-table">
          <thead>
            <tr><th>Date</th><th>Patient</th><th>Incentive</th><th>Paid</th><th>Balance</th><th>Status</th><th></th></tr>
          </thead>
          <tbody>
          @foreach($payments as $p)
          @php $balance = $p->calculated_amount - $p->paid_amount; @endphp
          <tr>
            <td class="text-small">{{ $p->created_at->format('d M Y') }}</td>
            <td>{{ $p->patient->full_name }}<div class="text-small text-muted">{{ $p->patient->jhc_id }}</div></td>
            <td class="mono">₹{{ number_format($p->calculated_amount, 2) }}</td>
            <td class="mono text-muted">₹{{ number_format($p->paid_amount, 2) }}</td>
            <td class="mono fw-600" style="{{ $balance > 0 ? 'color:var(--sc-amber)' : 'color:var(--sc-green)' }}">
              ₹{{ number_format($balance, 2) }}
            </td>
            <td>
              @php $sc = ['pending'=>'amber','partial'=>'teal','paid'=>'green'][$p->status] ?? 'gray'; @endphp
              <span class="badge badge-{{ $sc }}">{{ ucfirst($p->status) }}</span>
            </td>
            <td>
              @if($p->status !== 'paid')
              <button class="btn btn-sm btn-outline" onclick="setPayRecord('{{ $p->id }}','{{ number_format($p->calculated_amount - $p->paid_amount, 2) }}')">Pay</button>
              @endif
            </td>
          </tr>
          @endforeach
          </tbody>
        </table>
      </div>
      @endif
    </div>

    {{-- Patients list --}}
    <div class="card">
      <div class="card-header"><i class="fa-solid fa-users"></i> Referred Patients</div>
      @if($source->patients->isEmpty())
      <div class="card-body text-center text-muted" style="padding:30px">No patients referred yet.</div>
      @else
      <div class="table-wrap">
        <table class="sc-table">
          <thead><tr><th>JHC ID</th><th>Name</th><th>Phone</th><th>Registered</th><th></th></tr></thead>
          <tbody>
          @foreach($source->patients as $pat)
          <tr>
            <td class="mono">{{ $pat->jhc_id }}</td>
            <td class="fw-600">{{ $pat->full_name }}</td>
            <td>{{ $pat->phone }}</td>
            <td class="text-small text-muted">{{ $pat->created_at->format('d M Y') }}</td>
            <td><a href="{{ route('patients.show', $pat) }}" class="btn btn-sm btn-outline">View</a></td>
          </tr>
          @endforeach
          </tbody>
        </table>
      </div>
      @endif
    </div>
  </div>

  {{-- Source info card --}}
  <div class="card">
    <div class="card-header"><i class="fa-solid fa-handshake"></i> Source Info</div>
    <div class="card-body" style="display:flex;flex-direction:column;gap:12px;font-size:13px">
      <div><div class="form-label">Type</div><span class="badge badge-teal">{{ ucfirst($source->type) }}</span></div>
      @if($source->contact_person)<div><div class="form-label">Contact</div><div>{{ $source->contact_person }}</div></div>@endif
      @if($source->phone)<div><div class="form-label">Phone</div><div>{{ $source->phone }}</div></div>@endif
      @if($source->email)<div><div class="form-label">Email</div><div>{{ $source->email }}</div></div>@endif
      @if($source->address)<div><div class="form-label">Address</div><div>{{ $source->address }}</div></div>@endif
      <div><div class="form-label">Rate</div>
        <div class="fw-600">
          @if($source->rate_type === 'percentage')
            {{ $source->rate_value }}% of bill
          @else
            ₹{{ number_format($source->rate_value, 2) }} per patient
          @endif
        </div>
      </div>
      <div><div class="form-label">Status</div>
        @if($source->is_active)<span class="badge badge-green">Active</span>@else<span class="badge badge-red">Inactive</span>@endif
      </div>
      @if($source->notes)<div><div class="form-label">Notes</div><div>{{ $source->notes }}</div></div>@endif
    </div>
  </div>
</div>

{{-- Record Payment Modal --}}
<div class="modal-overlay" id="payModal">
  <div class="modal-box" style="max-width:480px">
    <div class="modal-header">
      <h3><i class="fa-solid fa-money-bill"></i> Record Incentive Payment</h3>
      <button class="modal-close" onclick="document.getElementById('payModal').classList.remove('open')">&times;</button>
    </div>
    <form method="POST" action="{{ route('referrals.payment') }}">
    @csrf
    <input type="hidden" name="referral_payment_id" id="modalPayId">
    <div class="modal-body">
      <div class="form-row cols-2">
        <div class="form-group">
          <label class="form-label">Amount <span class="req">*</span></label>
          <input type="number" name="paid_amount" id="modalPayAmt" class="form-control" step="0.01" min="0.01" required>
        </div>
        <div class="form-group">
          <label class="form-label">Payment Date <span class="req">*</span></label>
          <input type="date" name="payment_date" class="form-control" value="{{ today()->toDateString() }}" required>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Payment Method <span class="req">*</span></label>
        <select name="payment_method" class="form-control" required>
          <option value="cash">Cash</option>
          <option value="bank_transfer">Bank Transfer</option>
          <option value="cheque">Cheque</option>
          <option value="upi">UPI</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Transaction Ref</label>
        <input type="text" name="transaction_ref" class="form-control" placeholder="Optional">
      </div>
      <div class="form-group">
        <label class="form-label">Notes</label>
        <textarea name="notes" class="form-control" rows="2"></textarea>
      </div>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-outline" onclick="document.getElementById('payModal').classList.remove('open')">Cancel</button>
      <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Record Payment</button>
    </div>
    </form>
  </div>
</div>

<script>
function setPayRecord(id, balance) {
    document.getElementById('modalPayId').value = id;
    document.getElementById('modalPayAmt').value = balance;
    document.getElementById('payModal').classList.add('open');
}
</script>

@endsection
