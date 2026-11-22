@extends('layouts.app')
@section('title','New Radiology Order')
@section('page-title','New Radiology Order')
@section('content')

<div class="page-header">
  <div class="page-header-left">
    <h1>New Radiology Order</h1>
    <div class="breadcrumb"><a href="{{ route('radiology.index') }}">Radiology</a> &rsaquo; New Order</div>
  </div>
  @include('diagnostics._toolbar', ['inline'=>true])
</div>

<form method="POST" action="{{ route('radiology.store') }}">
@csrf
<div class="grid" style="grid-template-columns:1fr 320px;gap:20px;align-items:start">
  <div class="card">
    <div class="card-header"><i class="fa-solid fa-x-ray"></i> Order Details</div>
    <div class="card-body">
      <div class="form-group">
        <label class="form-label">Patient <span class="req">*</span></label>
        @if($patient)
        <input type="hidden" name="patient_id" value="{{ $patient->id }}">
        <div style="background:var(--sc-bg);border:1px solid var(--sc-border);border-radius:7px;padding:10px 14px;font-size:13px">
          <strong>{{ $patient->full_name }}</strong> — {{ $patient->jhc_id }}
        </div>
        @else
        <select name="patient_id" class="form-control select2" required>
          <option value="">— Search patient —</option>
          @foreach($patients as $p)
          <option value="{{ $p->id }}" {{ old('patient_id') == $p->id ? 'selected' : '' }}>
            {{ $p->full_name }} ({{ $p->jhc_id }})
          </option>
          @endforeach
        </select>
        @endif
      </div>
      @if($admission)
      <input type="hidden" name="admission_id" value="{{ $admission->id }}">
      @endif
      <div class="form-row cols-2">
        <div class="form-group">
          <label class="form-label">Modality <span class="req">*</span></label>
          <select name="modality" class="form-control" required>
            @foreach(\App\Models\RadiologyOrder::MODALITIES as $k => $m)
            <option value="{{ $k }}" {{ old('modality') === $k ? 'selected' : '' }}>{{ $m['label'] }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Urgency <span class="req">*</span></label>
          <select name="urgency" class="form-control" required>
            <option value="routine" {{ old('urgency','routine') === 'routine' ? 'selected' : '' }}>Routine</option>
            <option value="urgent"  {{ old('urgency') === 'urgent' ? 'selected' : '' }}>Urgent</option>
            <option value="emergency" {{ old('urgency') === 'emergency' ? 'selected' : '' }}>Emergency</option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Body Part <span class="req">*</span></label>
        <input type="text" name="body_part" class="form-control" value="{{ old('body_part') }}" required placeholder="e.g. Chest, Abdomen, Left Knee...">
      </div>
      <div class="form-group">
        <label class="form-label">Clinical Indication</label>
        <textarea name="clinical_indication" class="form-control" rows="3" placeholder="Reason for ordering this investigation...">{{ old('clinical_indication') }}</textarea>
      </div>
      <div class="form-group">
        <label class="form-label">Scheduled At</label>
        <input type="datetime-local" name="scheduled_at" class="form-control" value="{{ old('scheduled_at') }}">
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><i class="fa-solid fa-user-doctor"></i> Ordering Doctor</div>
    <div class="card-body">
      <div class="form-group">
        <label class="form-label">Doctor <span class="req">*</span></label>
        <select name="ordered_by" class="form-control select2" required>
          <option value="">— Select Doctor —</option>
          @foreach($doctors as $d)
          <option value="{{ $d->id }}" {{ old('ordered_by') == $d->id ? 'selected' : '' }}>
            Dr. {{ $d->first_name }} {{ $d->last_name }}
          </option>
          @endforeach
        </select>
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%">
        <i class="fa-solid fa-plus"></i> Create Order
      </button>
      <a href="{{ route('radiology.index') }}" class="btn btn-outline mt-2" style="width:100%;justify-content:center">Cancel</a>
    </div>
  </div>
</div>
</form>
@endsection
