@extends('layouts.app')
@section('title','Radiology — ' . $order->order_no)
@section('page-title','Radiology Order')
@section('content')

<div class="page-header">
  <div class="page-header-left">
    <h1 class="mono">{{ $order->order_no }}</h1>
    <div class="breadcrumb"><a href="{{ route('radiology.index') }}">Radiology</a> &rsaquo; {{ $order->order_no }}</div>
  </div>
  <div style="display:flex;gap:8px">
    @if($order->status === 'reported')
    <a href="{{ route('radiology.print', $order) }}" class="btn btn-outline" target="_blank">
      <i class="fa-solid fa-print"></i> Print Report
    </a>
    @endif
    <a href="{{ route('radiology.index') }}" class="btn btn-outline">Back</a>
  </div>
</div>

@if(session('success'))
<div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> {{ session('success') }}</div>
@endif

{{-- Patient strip --}}
<div class="patient-strip" style="margin-bottom:20px">
  <div class="ps-avatar">{{ strtoupper(substr($order->patient->first_name,0,1)) }}</div>
  <div>
    <div class="ps-name">{{ $order->patient->full_name }}</div>
    <div class="ps-jhc_id">{{ $order->patient->jhc_id }}</div>
    <div class="ps-meta">
      <span><i class="fa-solid fa-venus-mars"></i> {{ $order->patient->gender }}</span>
      <span><i class="fa-solid fa-cake-candles"></i> {{ $order->patient->age }}</span>
    </div>
  </div>
  <div class="ps-actions">
    @php $sc = ['ordered'=>'badge-amber','scheduled'=>'badge-teal','completed'=>'badge-navy','reported'=>'badge-green']; @endphp
    <span class="badge {{ $sc[$order->status] ?? 'badge-gray' }}" style="font-size:12px;padding:6px 14px">{{ ucfirst($order->status) }}</span>
  </div>
</div>

<div class="grid" style="grid-template-columns:1fr 320px;gap:20px;align-items:start">
  <div>
    {{-- Order info --}}
    <div class="card mb-4">
      <div class="card-header"><i class="fa-solid fa-x-ray"></i> Order Information</div>
      <div class="card-body">
        <div class="grid" style="grid-template-columns:1fr 1fr 1fr;gap:16px">
          <div><div class="form-label">Modality</div>
            @php $m = \App\Models\RadiologyOrder::MODALITIES[$order->modality] ?? ['label'=>$order->modality,'icon'=>'fa-x-ray']; @endphp
            <span class="badge badge-navy"><i class="fa-solid {{ $m['icon'] }}"></i> {{ $m['label'] }}</span>
          </div>
          <div><div class="form-label">Body Part</div><div class="fw-600">{{ $order->body_part }}</div></div>
          <div><div class="form-label">Urgency</div>
            @php $uc = ['routine'=>'badge-gray','urgent'=>'badge-amber','emergency'=>'badge-red']; @endphp
            <span class="badge {{ $uc[$order->urgency] ?? 'badge-gray' }}">{{ ucfirst($order->urgency) }}</span>
          </div>
          <div><div class="form-label">Ordered By</div><div>{{ $order->orderedBy ? 'Dr. '.$order->orderedBy->first_name.' '.$order->orderedBy->last_name : '—' }}</div></div>
          <div><div class="form-label">Ordered Date</div><div>{{ $order->created_at->format('d M Y, h:i A') }}</div></div>
          @if($order->scheduled_at)
          <div><div class="form-label">Scheduled At</div><div>{{ $order->scheduled_at->format('d M Y, h:i A') }}</div></div>
          @endif
        </div>
        @if($order->clinical_indication)
        <div style="margin-top:14px;padding-top:14px;border-top:1px solid var(--sc-border)">
          <div class="form-label">Clinical Indication</div>
          <div style="white-space:pre-wrap">{{ $order->clinical_indication }}</div>
        </div>
        @endif
      </div>
    </div>

    {{-- Update form --}}
    <div class="card">
      <div class="card-header"><i class="fa-solid fa-file-medical"></i> Report &amp; Update</div>
      <div class="card-body">
        <form method="POST" action="{{ route('radiology.update', $order) }}">
        @csrf @method('PUT')
        <div class="form-row cols-2">
          <div class="form-group">
            <label class="form-label">Status <span class="req">*</span></label>
            <select name="status" class="form-control" required>
              @foreach(['ordered'=>'Ordered','scheduled'=>'Scheduled','completed'=>'Completed','reported'=>'Reported'] as $v => $l)
              <option value="{{ $v }}" {{ $order->status === $v ? 'selected' : '' }}>{{ $l }}</option>
              @endforeach
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Reported By</label>
            <input type="text" name="reported_by" class="form-control" value="{{ old('reported_by', $order->reported_by) }}" placeholder="Radiologist name">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Scheduled At</label>
          <input type="datetime-local" name="scheduled_at" class="form-control" value="{{ old('scheduled_at', $order->scheduled_at?->format('Y-m-d\TH:i')) }}">
        </div>
        <div class="form-group">
          <label class="form-label">Findings</label>
          <textarea name="findings" class="form-control" rows="5" placeholder="Detailed radiological findings...">{{ old('findings', $order->findings) }}</textarea>
        </div>
        <div class="form-group">
          <label class="form-label">Impression / Conclusion</label>
          <textarea name="impression" class="form-control" rows="3" placeholder="Summary impression...">{{ old('impression', $order->impression) }}</textarea>
        </div>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save Report</button>
        </form>
      </div>
    </div>
  </div>

  {{-- Sidebar --}}
  <div style="display:flex;flex-direction:column;gap:16px">
    @if($order->admission)
    <div class="card">
      <div class="card-header"><i class="fa-solid fa-bed-pulse"></i> IPD Admission</div>
      <div class="card-body" style="font-size:13px">
        <div class="fw-600">{{ $order->admission->ipd_no }}</div>
        <div class="text-small text-muted">{{ $order->admission->ward->name }} — Bed {{ $order->admission->bed->bed_number }}</div>
        <a href="{{ route('admissions.show', $order->admission) }}" class="btn btn-sm btn-outline mt-2">View Admission</a>
      </div>
    </div>
    @endif
    <div class="card">
      <div class="card-header"><i class="fa-solid fa-circle-info"></i> Record</div>
      <div class="card-body" style="font-size:12.5px;display:flex;flex-direction:column;gap:8px">
        <div><div class="form-label">Created By</div><div>{{ $order->createdBy?->name ?? '—' }}</div></div>
        <div><div class="form-label">Created At</div><div>{{ $order->created_at->format('d M Y, h:i A') }}</div></div>
        @if($order->completed_at)
        <div><div class="form-label">Completed At</div><div>{{ $order->completed_at->format('d M Y, h:i A') }}</div></div>
        @endif
      </div>
    </div>
  </div>
</div>

@endsection
