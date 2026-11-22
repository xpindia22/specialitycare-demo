@extends('layouts.app')
@section('title','New Operation')
@section('page-title','New Operation')
@section('content')

<div class="page-header">
  <div class="page-header-left">
    <h1>Record New Operation</h1>
    <div class="breadcrumb"><a href="{{ route('ot.index') }}">OT Register</a> &rsaquo; New</div>
  </div>
  @include('diagnostics._toolbar', ['inline'=>true])
</div>

<form method="POST" action="{{ route('ot.store') }}">
@csrf
<div class="grid" style="grid-template-columns:1fr 1fr;gap:20px">
  <div class="card">
    <div class="card-header"><i class="fa-solid fa-user"></i> Patient &amp; Operation Details</div>
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
          <option value="{{ $p->id }}" {{ old('patient_id') == $p->id ? 'selected' : '' }}>{{ $p->full_name }} ({{ $p->jhc_id }})</option>
          @endforeach
        </select>
        @endif
      </div>
      @if($admission)
      <input type="hidden" name="admission_id" value="{{ $admission->id }}">
      @endif
      <div class="form-row cols-2">
        <div class="form-group">
          <label class="form-label">Operation Date <span class="req">*</span></label>
          <input type="date" name="operation_date" class="form-control" value="{{ old('operation_date', today()->toDateString()) }}" required>
        </div>
        <div class="form-group">
          <label class="form-label">Status <span class="req">*</span></label>
          <select name="status" class="form-control" required>
            @foreach(['scheduled'=>'Scheduled','in_progress'=>'In Progress','completed'=>'Completed','cancelled'=>'Cancelled'] as $v => $l)
            <option value="{{ $v }}" {{ old('status','completed') === $v ? 'selected' : '' }}>{{ $l }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Start Time</label>
          <input type="time" name="start_time" class="form-control" value="{{ old('start_time') }}">
        </div>
        <div class="form-group">
          <label class="form-label">End Time</label>
          <input type="time" name="end_time" class="form-control" value="{{ old('end_time') }}">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Procedure Name <span class="req">*</span></label>
        <input type="text" name="procedure_name" class="form-control" value="{{ old('procedure_name') }}" required placeholder="e.g. Appendectomy, Cholecystectomy...">
      </div>
      <div class="form-row cols-2">
        <div class="form-group">
          <label class="form-label">ICD Code</label>
          <input type="text" name="icd_code" class="form-control" value="{{ old('icd_code') }}" placeholder="e.g. K35.9">
        </div>
        <div class="form-group">
          <label class="form-label">Anesthesia Type <span class="req">*</span></label>
          <select name="anesthesia_type" class="form-control" required>
            @foreach(['general'=>'General','spinal'=>'Spinal','epidural'=>'Epidural','local'=>'Local','regional'=>'Regional','none'=>'None'] as $v => $l)
            <option value="{{ $v }}" {{ old('anesthesia_type','general') === $v ? 'selected' : '' }}>{{ $l }}</option>
            @endforeach
          </select>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Diagnosis <span class="req">*</span></label>
        <input type="text" name="diagnosis" class="form-control" value="{{ old('diagnosis') }}" required>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><i class="fa-solid fa-user-doctor"></i> Team &amp; Details</div>
    <div class="card-body">
      <div class="form-group">
        <label class="form-label">Surgeon <span class="req">*</span></label>
        <select name="surgeon_id" class="form-control select2" required>
          <option value="">— Select Surgeon —</option>
          @foreach($doctors as $d)
          <option value="{{ $d->id }}" {{ old('surgeon_id') == $d->id ? 'selected' : '' }}>Dr. {{ $d->first_name }} {{ $d->last_name }}</option>
          @endforeach
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Anesthetist</label>
        <input type="text" name="anesthetist_name" class="form-control" value="{{ old('anesthetist_name') }}" placeholder="Dr. Name">
      </div>
      <div class="form-row cols-2">
        <div class="form-group">
          <label class="form-label">Scrub Nurse</label>
          <input type="text" name="scrub_nurse" class="form-control" value="{{ old('scrub_nurse') }}">
        </div>
        <div class="form-group">
          <label class="form-label">Circulating Nurse</label>
          <input type="text" name="circulating_nurse" class="form-control" value="{{ old('circulating_nurse') }}">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Outcome <span class="req">*</span></label>
        <select name="outcome" class="form-control" required>
          @foreach(\App\Models\Operation::OUTCOME_LABELS as $v => $l)
          <option value="{{ $v }}" {{ old('outcome','successful') === $v ? 'selected' : '' }}>{{ $l }}</option>
          @endforeach
        </select>
      </div>
      <div class="form-row cols-2">
        <div class="form-group">
          <label style="display:flex;align-items:center;gap:8px;cursor:pointer;margin-top:22px">
            <input type="checkbox" name="blood_transfused" value="1" {{ old('blood_transfused') ? 'checked' : '' }}>
            <span class="form-label" style="margin:0">Blood Transfused</span>
          </label>
        </div>
        <div class="form-group">
          <label class="form-label">Blood Units</label>
          <input type="number" name="blood_units" class="form-control" value="{{ old('blood_units', 0) }}" min="0" max="20">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Pre-Op Diagnosis</label>
        <textarea name="pre_op_diagnosis" class="form-control" rows="2">{{ old('pre_op_diagnosis') }}</textarea>
      </div>
      <div class="form-group">
        <label class="form-label">Post-Op Diagnosis</label>
        <textarea name="post_op_diagnosis" class="form-control" rows="2">{{ old('post_op_diagnosis') }}</textarea>
      </div>
      <div class="form-group">
        <label class="form-label">Findings &amp; Complications</label>
        <textarea name="findings" class="form-control" rows="2">{{ old('findings') }}</textarea>
      </div>
      <div class="form-group">
        <label class="form-label">Notes</label>
        <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%">
        <i class="fa-solid fa-floppy-disk"></i> Save Operation Record
      </button>
    </div>
  </div>
</div>
</form>
@endsection
