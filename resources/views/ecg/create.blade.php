{{-- FILE: resources/views/ecg/create.blade.php --}}
@extends('layouts.app')
@section('title','New ECG')
@section('page-title','New ECG Record')
@section('content')
<div class="page-header"><div class="page-header-left"><h1>New ECG Record</h1></div></div>
<form method="POST" action="{{ route('ecg.store') }}" enctype="multipart/form-data">
@csrf
<div class="grid" style="grid-template-columns:1fr 300px;gap:20px;align-items:start">
<div>
    <div class="card mb-3">
        <div class="card-header"><i class="fa-solid fa-user"></i> Patient & Doctor</div>
        <div class="card-body">
            @if($patient)
                <div class="patient-strip" style="padding:12px"><div class="ps-avatar">{{ strtoupper(substr($patient->first_name,0,1)) }}</div>
                <div><div class="ps-name">{{ $patient->full_name }}</div><div class="ps-jhc_id">{{ $patient->jhc_id }}</div></div></div>
                <input type="hidden" name="patient_id" value="{{ $patient->id }}">
            @else
                <div style="position:relative">
                    <input type="text" id="pt-search" class="form-control" placeholder="Search patient...">
                    <div id="pt-results" style="position:absolute;top:100%;left:0;right:0;background:#fff;border:1px solid var(--sc-border);border-radius:8px;box-shadow:var(--shadow-md);z-index:200;display:none;max-height:200px;overflow-y:auto;margin-top:4px"></div>
                </div>
                <input type="hidden" name="patient_id" id="pt-id">
            @endif
            <div class="form-row cols-2 mt-3">
                <div class="form-group">
                    <label class="form-label">Doctor <span class="req">*</span></label>
                    <select name="doctor_id" class="form-control select2" required style="width:100%">
                        <option value="">Select Doctor</option>
                        @foreach($doctors as $d)
                            <option value="{{ $d->id }}" {{ $d->id == 1 ? 'selected' : '' }}>{{ $d->full_name }} — {{ $d->specialization }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Record Date <span class="req">*</span></label>
                    <input type="date" name="record_date" class="form-control" value="{{ today()->toDateString() }}" required>
                </div>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><i class="fa-solid fa-heart-pulse"></i> ECG Parameters</div>
        <div class="card-body">
            <div class="form-row cols-3">
                <div class="form-group"><label class="form-label">Heart Rate (/min)</label><input type="text" name="heart_rate" class="form-control" placeholder="e.g. 72"></div>
                <div class="form-group"><label class="form-label">Rhythm</label>
                    <select name="rhythm" class="form-control">
                        <option value="">Select</option>
                        @foreach(['Sinus Rhythm','Sinus Tachycardia','Sinus Bradycardia','Atrial Fibrillation','Atrial Flutter','Ventricular Tachycardia','Ventricular Fibrillation','Heart Block','Junctional Rhythm','Paced Rhythm'] as $r)
                            <option>{{ $r }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group"><label class="form-label">Axis</label>
                    <select name="axis" class="form-control">
                        <option value="">Select</option>
                        @foreach(['Normal Axis','Left Axis Deviation','Right Axis Deviation','Extreme Axis Deviation'] as $a)
                            <option>{{ $a }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group"><label class="form-label">PR Interval (ms)</label><input type="text" name="pr_interval" class="form-control" placeholder="e.g. 160ms"></div>
                <div class="form-group"><label class="form-label">QRS Duration (ms)</label><input type="text" name="qrs_duration" class="form-control" placeholder="e.g. 90ms"></div>
                <div class="form-group"><label class="form-label">QT/QTc (ms)</label><input type="text" name="qt_qtc" class="form-control" placeholder="e.g. 400/420ms"></div>
            </div>
            <div class="form-group">
                <label class="form-label">Interpretation / Findings</label>
                <textarea name="interpretation" class="form-control" rows="4" placeholder="ECG interpretation, findings, conclusion..."></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Upload ECG Report (optional)</label>
                <input type="file" name="ecg_file" class="form-control" accept="image/*,.pdf,.bmp,.tiff">
                <div style="font-size:12px;color:var(--sc-muted);margin-top:4px">Accepted: JPEG, PNG, PDF, BMP, TIFF — max 20 MB</div>
            </div>
        </div>
    </div>
</div>
<div>
    <div class="card">
        <div class="card-body">
            <div class="form-group"><label class="form-label">Price (₹) <span class="req">*</span></label><input type="number" name="price" class="form-control" value="{{ $price }}" required step="0.01" min="0"></div>
            <button type="submit" class="btn btn-primary btn-lg" style="width:100%;justify-content:center"><i class="fa-solid fa-save"></i> Save ECG Record</button>
        </div>
    </div>
</div>
</div>
</form>
@endsection
