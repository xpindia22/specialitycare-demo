@extends('layouts.app')
@section('title','Operation — ' . $operation->operation_no)
@section('page-title','Operation Detail')
@section('content')

<div class="page-header">
  <div class="page-header-left">
    <h1 class="mono">{{ $operation->operation_no }}</h1>
    <div class="breadcrumb"><a href="{{ route('ot.index') }}">OT Register</a> &rsaquo; {{ $operation->operation_no }}</div>
  </div>
  <div style="display:flex;gap:8px">
    <a href="{{ route('ot.index') }}" class="btn btn-outline">Back</a>
  </div>
</div>

@if(session('success'))
<div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> {{ session('success') }}</div>
@endif

{{-- Patient strip --}}
<div class="patient-strip" style="margin-bottom:20px">
  <div class="ps-avatar">{{ strtoupper(substr($operation->patient->first_name,0,1)) }}</div>
  <div>
    <div class="ps-name">{{ $operation->patient->full_name }}</div>
    <div class="ps-jhc_id">{{ $operation->patient->jhc_id }}</div>
    <div class="ps-meta">
      <span><i class="fa-solid fa-syringe"></i> {{ $operation->procedure_name }}</span>
      <span><i class="fa-solid fa-calendar"></i> {{ $operation->operation_date->format('d M Y') }}</span>
    </div>
  </div>
  <div class="ps-actions">
    <span class="badge badge-{{ $operation->status_color }}" style="font-size:12px;padding:6px 14px">{{ ucfirst(str_replace('_',' ',$operation->status)) }}</span>
  </div>
</div>

<div class="grid" style="grid-template-columns:1fr 320px;gap:20px;align-items:start">
  <div>
    <div class="card mb-4">
      <div class="card-header"><i class="fa-solid fa-file-medical"></i> Operation Details</div>
      <div class="card-body">
        <div class="grid" style="grid-template-columns:1fr 1fr 1fr;gap:16px;margin-bottom:16px">
          <div><div class="form-label">Procedure</div><div class="fw-600">{{ $operation->procedure_name }}</div></div>
          <div><div class="form-label">Diagnosis</div><div>{{ $operation->diagnosis }}</div></div>
          <div><div class="form-label">Anesthesia</div><div>{{ ucfirst($operation->anesthesia_type) }}</div></div>
          <div><div class="form-label">Surgeon</div><div>Dr. {{ $operation->surgeon->first_name }} {{ $operation->surgeon->last_name }}</div></div>
          <div><div class="form-label">Anesthetist</div><div>{{ $operation->anesthetist_name ?: '—' }}</div></div>
          <div><div class="form-label">Outcome</div>
            @php $oc = ['successful'=>'green','complicated'=>'amber','abandoned'=>'red','patient_expired'=>'red']; @endphp
            <span class="badge badge-{{ $oc[$operation->outcome] ?? 'gray' }}">{{ \App\Models\Operation::OUTCOME_LABELS[$operation->outcome] ?? $operation->outcome }}</span>
          </div>
          @if($operation->start_time)
          <div><div class="form-label">Start Time</div><div>{{ \Carbon\Carbon::parse($operation->start_time)->format('h:i A') }}</div></div>
          @endif
          @if($operation->end_time)
          <div><div class="form-label">End Time</div><div>{{ \Carbon\Carbon::parse($operation->end_time)->format('h:i A') }}</div></div>
          @endif
          @if($operation->blood_transfused)
          <div><div class="form-label">Blood Transfused</div><div class="fw-600">Yes — {{ $operation->blood_units }} unit(s)</div></div>
          @endif
        </div>
        @if($operation->pre_op_diagnosis)
        <div style="margin-top:10px"><div class="form-label">Pre-Op Diagnosis</div><div>{{ $operation->pre_op_diagnosis }}</div></div>
        @endif
        @if($operation->post_op_diagnosis)
        <div style="margin-top:10px"><div class="form-label">Post-Op Diagnosis</div><div>{{ $operation->post_op_diagnosis }}</div></div>
        @endif
        @if($operation->findings)
        <div style="margin-top:10px"><div class="form-label">Findings</div><div style="white-space:pre-wrap">{{ $operation->findings }}</div></div>
        @endif
        @if($operation->complications)
        <div style="margin-top:10px"><div class="form-label">Complications</div><div>{{ $operation->complications }}</div></div>
        @endif
      </div>
    </div>

    <div class="card">
      <div class="card-header"><i class="fa-solid fa-pencil"></i> Update Operation</div>
      <div class="card-body">
        <form method="POST" action="{{ route('ot.update', $operation) }}">
        @csrf @method('PUT')
        <div class="form-row cols-2">
          <div class="form-group">
            <label class="form-label">Status</label>
            <select name="status" class="form-control">
              @foreach(['scheduled'=>'Scheduled','in_progress'=>'In Progress','completed'=>'Completed','cancelled'=>'Cancelled'] as $v => $l)
              <option value="{{ $v }}" {{ $operation->status === $v ? 'selected' : '' }}>{{ $l }}</option>
              @endforeach
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Outcome</label>
            <select name="outcome" class="form-control">
              @foreach(\App\Models\Operation::OUTCOME_LABELS as $v => $l)
              <option value="{{ $v }}" {{ $operation->outcome === $v ? 'selected' : '' }}>{{ $l }}</option>
              @endforeach
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Start Time</label>
            <input type="time" name="start_time" class="form-control" value="{{ $operation->start_time }}">
          </div>
          <div class="form-group">
            <label class="form-label">End Time</label>
            <input type="time" name="end_time" class="form-control" value="{{ $operation->end_time }}">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Post-Op Diagnosis</label>
          <textarea name="post_op_diagnosis" class="form-control" rows="2">{{ $operation->post_op_diagnosis }}</textarea>
        </div>
        <div class="form-group">
          <label class="form-label">Findings</label>
          <textarea name="findings" class="form-control" rows="3">{{ $operation->findings }}</textarea>
        </div>
        <div class="form-group">
          <label class="form-label">Complications</label>
          <textarea name="complications" class="form-control" rows="2">{{ $operation->complications }}</textarea>
        </div>
        <div class="form-row cols-2">
          <div class="form-group">
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;margin-top:22px">
              <input type="checkbox" name="blood_transfused" value="1" {{ $operation->blood_transfused ? 'checked' : '' }}>
              <span class="form-label" style="margin:0">Blood Transfused</span>
            </label>
          </div>
          <div class="form-group">
            <label class="form-label">Blood Units</label>
            <input type="number" name="blood_units" class="form-control" value="{{ $operation->blood_units }}" min="0">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Notes</label>
          <textarea name="notes" class="form-control" rows="2">{{ $operation->notes }}</textarea>
        </div>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Update</button>
        </form>
      </div>
    </div>
  </div>

  <div style="display:flex;flex-direction:column;gap:16px">
    @if($operation->admission)
    <div class="card">
      <div class="card-header"><i class="fa-solid fa-bed-pulse"></i> IPD Record</div>
      <div class="card-body" style="font-size:13px">
        <div class="fw-600">{{ $operation->admission->ipd_no }}</div>
        <a href="{{ route('admissions.show', $operation->admission) }}" class="btn btn-sm btn-outline mt-2">View Admission</a>
      </div>
    </div>
    @endif
    <div class="card">
      <div class="card-header"><i class="fa-solid fa-circle-info"></i> Record Info</div>
      <div class="card-body" style="font-size:12.5px;display:flex;flex-direction:column;gap:8px">
        <div><div class="form-label">Scrub Nurse</div><div>{{ $operation->scrub_nurse ?: '—' }}</div></div>
        <div><div class="form-label">Circulating Nurse</div><div>{{ $operation->circulating_nurse ?: '—' }}</div></div>
        <div><div class="form-label">ICD Code</div><div>{{ $operation->icd_code ?: '—' }}</div></div>
        <div><div class="form-label">Created By</div><div>{{ $operation->createdBy?->name ?? '—' }}</div></div>
        <div><div class="form-label">Created At</div><div>{{ $operation->created_at->format('d M Y') }}</div></div>
      </div>
    </div>
  </div>
</div>

@endsection
