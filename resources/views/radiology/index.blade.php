@extends('layouts.app')
@section('title','Radiology Orders')
@section('page-title','Radiology')
@section('content')

<div class="page-header">
  <div class="page-header-left">
    <h1>Radiology Orders</h1>
    <div class="breadcrumb">Investigations &rsaquo; Radiology</div>
  </div>
  <a href="{{ route('radiology.create') }}" class="btn btn-primary"><i class="fa-solid fa-plus"></i> New Order</a>
</div>

<div class="card mb-4">
  <div class="card-body" style="padding:12px 20px">
    <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end">
      <input type="text" name="q" class="form-control" value="{{ request('q') }}" placeholder="Search order no / patient..." style="width:220px">
      <select name="modality" class="form-control" style="width:150px">
        <option value="">All Modalities</option>
        @foreach(\App\Models\RadiologyOrder::MODALITIES as $k => $m)
        <option value="{{ $k }}" {{ request('modality') === $k ? 'selected' : '' }}>{{ $m['label'] }}</option>
        @endforeach
      </select>
      <select name="status" class="form-control" style="width:140px">
        <option value="">All Status</option>
        @foreach(['ordered'=>'Ordered','scheduled'=>'Scheduled','completed'=>'Completed','reported'=>'Reported'] as $v => $l)
        <option value="{{ $v }}" {{ request('status') === $v ? 'selected' : '' }}>{{ $l }}</option>
        @endforeach
      </select>
      <input type="date" name="date" class="form-control" value="{{ request('date') }}" style="width:150px">
      <button type="submit" class="btn btn-primary"><i class="fa-solid fa-filter"></i> Filter</button>
      <a href="{{ route('radiology.index') }}" class="btn btn-outline">Reset</a>
    </form>
  </div>
</div>

<div class="card">
  <div class="table-wrap">
    <table class="sc-table">
      <thead>
        <tr><th>Order No.</th><th>Patient</th><th>Modality</th><th>Body Part</th><th>Doctor</th><th>Urgency</th><th>Date</th><th>Status</th><th></th></tr>
      </thead>
      <tbody>
      @forelse($orders as $order)
      <tr>
        <td class="mono fw-600">{{ $order->order_no }}</td>
        <td>
          <div class="fw-600">{{ $order->patient->full_name }}</div>
          <div class="text-small text-muted">{{ $order->patient->jhc_id }}</div>
        </td>
        <td>
          @php $m = \App\Models\RadiologyOrder::MODALITIES[$order->modality] ?? ['label'=>$order->modality,'icon'=>'fa-x-ray']; @endphp
          <span class="badge badge-navy"><i class="fa-solid {{ $m['icon'] }}"></i> {{ $m['label'] }}</span>
        </td>
        <td>{{ $order->body_part }}</td>
        <td class="text-small">{{ $order->orderedBy ? 'Dr. '.$order->orderedBy->first_name.' '.$order->orderedBy->last_name : '—' }}</td>
        <td>
          @php $uc = ['routine'=>'badge-gray','urgent'=>'badge-amber','emergency'=>'badge-red']; @endphp
          <span class="badge {{ $uc[$order->urgency] ?? 'badge-gray' }}">{{ ucfirst($order->urgency) }}</span>
        </td>
        <td class="text-small">{{ $order->created_at->format('d M Y') }}</td>
        <td>
          @php $sc = ['ordered'=>'badge-amber','scheduled'=>'badge-teal','completed'=>'badge-navy','reported'=>'badge-green']; @endphp
          <span class="badge {{ $sc[$order->status] ?? 'badge-gray' }}">{{ ucfirst($order->status) }}</span>
        </td>
        <td>
          <div style="display:flex;gap:4px">
            <a href="{{ route('radiology.show', $order) }}" class="btn btn-sm btn-outline">View</a>
            @if($order->status === 'reported')
            <a href="{{ route('radiology.print', $order) }}" class="btn btn-sm btn-outline" target="_blank"><i class="fa-solid fa-print"></i></a>
            @endif
          </div>
        </td>
      </tr>
      @empty
      <tr><td colspan="9" class="text-center text-muted" style="padding:40px">No radiology orders found.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
  @if($orders->hasPages())
  <div class="card-footer">{{ $orders->links() }}</div>
  @endif
</div>

@endsection
