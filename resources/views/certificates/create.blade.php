@extends('layouts.app')
@section('title', 'New Certificate')
@section('page-title', 'New Certificate')

@section('content')
<div class="page-header">
    <div class="page-header-left">
        <h1>Issue Certificate</h1>
        <div class="breadcrumb">
            <a href="{{ route('certificates.index') }}">Certificates</a> &rsaquo; New
        </div>
    </div>
    <a href="{{ route('certificates.index') }}" class="btn btn-outline">
        <i class="fa-solid fa-arrow-left"></i> Back
    </a>
</div>

<div class="card">
    <div class="card-header">
        <i class="fa-solid fa-file-certificate"></i> Certificate Details
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('certificates.store') }}">
            @csrf

            {{-- Row 1: Patient + Type --}}
            <div class="form-row cols-2">
                <div class="form-group">
                    <label class="form-label" for="patient_id">
                        Patient <span class="req">*</span>
                    </label>
                    <select name="patient_id" id="patient_id"
                            class="form-control select2 @error('patient_id') is-invalid @enderror"
                            required>
                        <option value="">— Select Patient —</option>
                        @foreach($patients as $patient)
                            <option value="{{ $patient->id }}"
                                {{ old('patient_id') == $patient->id ? 'selected' : '' }}>
                                {{ $patient->jhc_id }} — {{ trim($patient->first_name . ' ' . $patient->middle_name . ' ' . $patient->last_name) }}
                            </option>
                        @endforeach
                    </select>
                    @error('patient_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="certificate_type">
                        Certificate Type <span class="req">*</span>
                    </label>
                    <select name="certificate_type" id="certificate_type"
                            class="form-control @error('certificate_type') is-invalid @enderror"
                            required>
                        <option value="">— Select Type —</option>
                        @foreach($types as $key => $label)
                            <option value="{{ $key }}"
                                {{ old('certificate_type') == $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    @error('certificate_type')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            {{-- Row 2: Issued Date + Doctor --}}
            <div class="form-row cols-2">
                <div class="form-group">
                    <label class="form-label" for="issued_date">
                        Issued Date <span class="req">*</span>
                    </label>
                    <input type="date" name="issued_date" id="issued_date"
                           class="form-control @error('issued_date') is-invalid @enderror"
                           value="{{ old('issued_date', date('Y-m-d')) }}" required>
                    @error('issued_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="doctor_id">Certifying Doctor</label>
                    <select name="doctor_id" id="doctor_id"
                            class="form-control select2 @error('doctor_id') is-invalid @enderror">
                        <option value="">— None / Not Applicable —</option>
                        @foreach($doctors as $doctor)
                            <option value="{{ $doctor->id }}"
                                {{ old('doctor_id') == $doctor->id ? 'selected' : '' }}>
                                Dr. {{ $doctor->first_name }} {{ $doctor->last_name }}
                                @if($doctor->specialization) — {{ $doctor->specialization }} @endif
                            </option>
                        @endforeach
                    </select>
                    @error('doctor_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            {{-- Row 3: Purpose + Diagnosis --}}
            <div class="form-row cols-2">
                <div class="form-group">
                    <label class="form-label" for="purpose">Purpose</label>
                    <input type="text" name="purpose" id="purpose"
                           class="form-control @error('purpose') is-invalid @enderror"
                           placeholder="e.g. For employment, For school, For insurance…"
                           value="{{ old('purpose') }}">
                    @error('purpose')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="diagnosis">Diagnosis</label>
                    <input type="text" name="diagnosis" id="diagnosis"
                           class="form-control @error('diagnosis') is-invalid @enderror"
                           placeholder="e.g. Acute febrile illness"
                           value="{{ old('diagnosis') }}">
                    @error('diagnosis')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            {{-- Rest period — shown only for medical_leave --}}
            <div id="rest-period-row" class="form-row cols-2" style="display:none">
                <div class="form-group">
                    <label class="form-label" for="rest_from">Rest From</label>
                    <input type="date" name="rest_from" id="rest_from"
                           class="form-control @error('rest_from') is-invalid @enderror"
                           value="{{ old('rest_from') }}">
                    @error('rest_from')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="rest_to">Rest To</label>
                    <input type="date" name="rest_to" id="rest_to"
                           class="form-control @error('rest_to') is-invalid @enderror"
                           value="{{ old('rest_to') }}">
                    @error('rest_to')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            {{-- Remarks --}}
            <div class="form-group">
                <label class="form-label" for="remarks">Remarks</label>
                <input type="hidden" name="remarks" id="remarks-input">
                <div id="remarks-quill" style="min-height:120px;background:#fff"></div>
                @error('remarks')
                    <div class="invalid-feedback" style="display:block">{{ $message }}</div>
                @enderror
            </div>

            <div style="display:flex;gap:12px;justify-content:flex-end;margin-top:8px">
                <a href="{{ route('certificates.index') }}" class="btn btn-outline">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-file-certificate"></i> Issue Certificate
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.snow.css">
<style>
#remarks-quill { border:1px solid var(--sc-border);border-radius:8px;overflow:hidden; }
#remarks-quill .ql-toolbar { border:none;border-bottom:1px solid var(--sc-border);background:var(--sc-bg);padding:5px 10px; }
#remarks-quill .ql-container { border:none;font-family:'DM Sans',sans-serif;font-size:13px;color:var(--sc-navy); }
#remarks-quill .ql-editor { padding:10px 12px; }
#remarks-quill .ql-editor.ql-blank::before { color:#94a3b8;font-style:normal; }
.ql-snow .ql-stroke { stroke:var(--sc-navy) }
.ql-snow .ql-fill { fill:var(--sc-navy) }
.ql-snow.ql-toolbar button:hover .ql-stroke { stroke:var(--sc-teal) }
.ql-snow.ql-toolbar button.ql-active .ql-stroke { stroke:var(--sc-teal) }
.ql-snow.ql-toolbar button:hover .ql-fill { fill:var(--sc-teal) }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.min.js"></script>
<script>
(function () {
    const typeSelect   = document.getElementById('certificate_type');
    const restRow      = document.getElementById('rest-period-row');
    const restFrom     = document.getElementById('rest_from');
    const restTo       = document.getElementById('rest_to');

    function toggleRestPeriod() {
        const show = typeSelect.value === 'medical_leave';
        restRow.style.display = show ? '' : 'none';
        restFrom.required = show;
        restTo.required   = show;
    }

    typeSelect.addEventListener('change', toggleRestPeriod);
    toggleRestPeriod();

    const toolbar = [
        ['bold', 'italic', 'underline'],
        [{ list: 'ordered' }, { list: 'bullet' }],
        ['clean']
    ];
    const remarksQuill = new Quill('#remarks-quill', {
        theme: 'snow',
        placeholder: 'Any additional notes or instructions…',
        modules: { toolbar }
    });

    @if(old('remarks'))
    remarksQuill.root.innerHTML = {!! json_encode(old('remarks')) !!};
    @endif

    document.querySelector('form').addEventListener('submit', function () {
        document.getElementById('remarks-input').value = remarksQuill.root.innerHTML;
    });
})();
</script>
@endpush
