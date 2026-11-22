{{-- FILE: resources/views/lab/upload-report.blade.php --}}
@extends('layouts.app')
@section('title','Upload Lab Report')
@section('page-title','Upload Lab Report')
@section('content')
<div class="page-header">
    <div class="page-header-left">
        <h1>Upload Lab Report</h1>
        <div class="breadcrumb"><a href="{{ route('lab.index') }}">Laboratory</a> &rsaquo; Upload Report</div>
    </div>
    <a href="{{ route('lab.index') }}" class="btn btn-outline"><i class="fa-solid fa-arrow-left"></i> Back</a>
</div>

@if($errors->any())
<div class="alert" style="background:#fef2f2;border:1px solid #fecaca;color:#991b1b;border-radius:8px;padding:10px 16px;margin-bottom:16px">
    {{ $errors->first() }}
</div>
@endif

<form method="POST" action="{{ route('lab.report.parse') }}" enctype="multipart/form-data">
@csrf
@if(isset($encounter_id) && $encounter_id)<input type="hidden" name="encounter_id" value="{{ $encounter_id }}">@endif

<div class="card" style="max-width:640px">
    <div class="card-header"><i class="fa-solid fa-file-pdf"></i> Report Details</div>
    <div class="card-body">
        <div class="form-group mb-3">
            <label class="form-label">Patient <span style="color:#dc2626">*</span></label>
            @if(isset($patient) && $patient)
                <div class="patient-strip" style="padding:12px">
                    <div class="ps-avatar">{{ strtoupper(substr($patient->first_name,0,1)) }}</div>
                    <div><div class="ps-name">{{ $patient->full_name }}</div><div class="ps-jhc_id">{{ $patient->jhc_id }}</div></div>
                </div>
                <input type="hidden" name="patient_id" value="{{ $patient->id }}">
            @else
                <select name="patient_id" class="form-control select2" required style="width:100%">
                    <option value="">Search patient by name, JHC ID, or phone...</option>
                    @foreach($patients as $p)
                        <option value="{{ $p->id }}" {{ old('patient_id') == $p->id ? 'selected' : '' }}>
                            {{ $p->full_name }}{{ $p->jhc_id ? ' - '.$p->jhc_id : '' }}{{ $p->phone ? ' - '.$p->phone : '' }}
                        </option>
                    @endforeach
                </select>
            @endif
        </div>

        <div class="form-group mb-3">
            <label class="form-label">Report PDF <span style="color:#dc2626">*</span></label>
            <input type="file" name="report" accept="application/pdf" class="form-control" required>
            <div class="text-small text-muted" style="margin-top:6px">
                <i class="fa-solid fa-circle-info"></i>
                The results and reference ranges are read from the PDF. You'll review and confirm them on the next screen before anything is saved.
            </div>
        </div>

        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-magnifying-glass"></i> Read Report</button>
    </div>
</div>
</form>
@endsection

@push('scripts')
<script>
  if (window.jQuery && jQuery.fn.select2) { jQuery('.select2').select2({ width: '100%' }); }
</script>
@endpush
