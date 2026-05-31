@extends('layouts.app')
@section('title','New Encounter Template')
@section('page-title','New Encounter Template')
@section('content')

<div class="page-header">
    <div class="page-header-left"><h1>New Encounter Template</h1></div>
    <a href="{{ route('encounter-templates.index') }}" class="btn btn-outline">Back</a>
</div>

@if($errors->any())
<div class="alert alert-danger"><ul style="margin:0;padding-left:16px">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
@endif

<form method="POST" action="{{ route('encounter-templates.store') }}">
@csrf
<div class="grid" style="grid-template-columns:1fr 1fr;gap:20px;align-items:start">
    <div>
        <div class="card mb-3">
            <div class="card-header"><i class="fa-solid fa-info-circle"></i> Template Details</div>
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label">Template Name <span class="req">*</span></label>
                    <input type="text" name="name" class="form-control" required value="{{ old('name') }}" placeholder="e.g. Diabetic Follow-up, ANC Checkup">
                </div>
                <div class="form-group">
                    <label class="form-label">Specialization</label>
                    <input type="text" name="specialization" class="form-control" value="{{ old('specialization') }}" placeholder="e.g. General Medicine, Gynaecology">
                </div>
                <div class="form-group">
                    <label class="form-label">Default Chief Complaint</label>
                    <textarea name="chief_complaint" class="form-control" rows="2" placeholder="Pre-filled chief complaint…">{{ old('chief_complaint') }}</textarea>
                </div>
                <div class="form-group" style="margin:0">
                    <label class="form-label">Default Clinical Notes</label>
                    <textarea name="clinical_notes" class="form-control" rows="4" placeholder="Pre-filled clinical notes / SOAP format…">{{ old('clinical_notes') }}</textarea>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><i class="fa-solid fa-diagnoses"></i> Default Diagnoses
                <div class="card-actions"><button type="button" class="btn btn-sm btn-outline" onclick="addDiag()"><i class="fa-solid fa-plus"></i> Add</button></div>
            </div>
            <div class="card-body">
                <div id="diagContainer"></div>
                <div id="noDiag" class="text-muted text-small text-center" style="padding:16px">No diagnoses — click Add to include common diagnoses.</div>
            </div>
        </div>
    </div>

    <div>
        <div class="card mb-3">
            <div class="card-header"><i class="fa-solid fa-pills"></i> Default Prescription
                <div class="card-actions"><button type="button" class="btn btn-sm btn-outline" onclick="addRx()"><i class="fa-solid fa-plus"></i> Add Drug</button></div>
            </div>
            <div class="card-body">
                <div id="rxContainer"></div>
                <div id="noRx" class="text-muted text-small text-center" style="padding:16px">No drugs — click Add Drug to include default prescription.</div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><i class="fa-solid fa-flask-vial"></i> Default Lab Orders</div>
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label">Panels</label>
                    <select name="lab_panels[]" class="form-control select2-tpl" multiple style="width:100%">
                        @foreach($labPanels as $p)
                            <option value="{{ $p->name }}" {{ collect(old('lab_panels'))->contains($p->name) ? 'selected' : '' }}>
                                {{ $p->name }}@if($p->price) — ₹{{ number_format($p->price, 0) }}@endif
                            </option>
                        @endforeach
                    </select>
                    <div class="form-text" style="font-size:11px;color:var(--sc-muted)">Pick from existing panels — applied as a single line on the lab order.</div>
                </div>
                <div class="form-group" style="margin:0">
                    <label class="form-label">Individual Tests</label>
                    <select name="lab_tests[]" class="form-control select2-tpl" multiple style="width:100%">
                        @foreach($labTests as $t)
                            <option value="{{ $t->name }}" {{ collect(old('lab_tests'))->contains($t->name) ? 'selected' : '' }}>
                                {{ $t->name }}@if($t->price) — ₹{{ number_format($t->price, 0) }}@endif
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary" style="width:100%"><i class="fa-solid fa-floppy-disk"></i> Save Template</button>
    </div>
</div>
</form>

<script>
$(function() { $('.select2-tpl').select2({ placeholder: 'Search and select…', width: '100%', theme: 'classic' }); });
</script>

<script>
let diagIdx = 0, rxIdx = 0;
function addDiag() {
    document.getElementById('noDiag').style.display = 'none';
    const div = document.createElement('div');
    div.style.cssText = 'display:flex;gap:8px;align-items:center;margin-bottom:8px';
    div.innerHTML = `<input type="text" name="diagnoses[]" class="form-control" placeholder="Diagnosis name, e.g. Type 2 Diabetes">
        <button type="button" onclick="this.parentElement.remove()" class="btn btn-sm btn-outline" style="color:var(--sc-red);border-color:var(--sc-red);flex-shrink:0"><i class="fa-solid fa-trash"></i></button>`;
    document.getElementById('diagContainer').appendChild(div);
}
function addRx() {
    document.getElementById('noRx').style.display = 'none';
    const i = rxIdx++;
    const div = document.createElement('div');
    div.style.cssText = 'padding:10px;background:var(--sc-bg);border-radius:8px;border:1px solid var(--sc-border);margin-bottom:8px';
    div.innerHTML = `
        <div style="display:grid;grid-template-columns:2fr 1fr 1fr 1fr auto;gap:8px;align-items:flex-end">
            <div class="form-group" style="margin:0"><label class="form-label">Drug</label><input type="text" name="rx_drug[]" class="form-control" placeholder="Drug name" required></div>
            <div class="form-group" style="margin:0"><label class="form-label">Dose</label><input type="text" name="rx_dose[]" class="form-control" placeholder="e.g. 500mg"></div>
            <div class="form-group" style="margin:0"><label class="form-label">Route</label>
                <select name="rx_route[]" class="form-control"><option>Oral</option><option>IV</option><option>IM</option><option>SC</option><option>Topical</option><option>Inhaled</option></select></div>
            <div class="form-group" style="margin:0"><label class="form-label">Frequency</label>
                <select name="rx_frequency[]" class="form-control"><option>OD</option><option>BD</option><option>TDS</option><option>QID</option><option>SOS</option><option>STAT</option></select></div>
            <button type="button" onclick="this.closest('div[style]').remove()" class="btn btn-sm btn-outline" style="color:var(--sc-red);border-color:var(--sc-red);margin-bottom:0"><i class="fa-solid fa-trash"></i></button>
        </div>
        <div class="form-group" style="margin-top:8px;margin-bottom:0"><label class="form-label">Instructions</label><input type="text" name="rx_instructions[]" class="form-control" placeholder="e.g. After meals"></div>
    `;
    document.getElementById('rxContainer').appendChild(div);
}
</script>
@endsection
