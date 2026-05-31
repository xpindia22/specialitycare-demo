@extends('layouts.app')
@section('title','Edit Encounter Template')
@section('page-title','Edit Encounter Template')
@section('content')

<div class="page-header">
    <div class="page-header-left"><h1>Edit: {{ $encounterTemplate->name }}</h1></div>
    <a href="{{ route('encounter-templates.index') }}" class="btn btn-outline">Back</a>
</div>

@if($errors->any())
<div class="alert alert-danger"><ul style="margin:0;padding-left:16px">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
@endif

<form method="POST" action="{{ route('encounter-templates.update', $encounterTemplate) }}">
@csrf @method('PUT')
<div class="grid" style="grid-template-columns:1fr 1fr;gap:20px;align-items:start">
    <div>
        <div class="card mb-3">
            <div class="card-header"><i class="fa-solid fa-info-circle"></i> Template Details</div>
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label">Template Name <span class="req">*</span></label>
                    <input type="text" name="name" class="form-control" required value="{{ old('name', $encounterTemplate->name) }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Specialization</label>
                    <input type="text" name="specialization" class="form-control" value="{{ old('specialization', $encounterTemplate->specialization) }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Default Chief Complaint</label>
                    <textarea name="chief_complaint" class="form-control" rows="2">{{ old('chief_complaint', $encounterTemplate->chief_complaint) }}</textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Default Clinical Notes</label>
                    <textarea name="clinical_notes" class="form-control" rows="4">{{ old('clinical_notes', $encounterTemplate->clinical_notes) }}</textarea>
                </div>
                <div class="form-group" style="margin:0">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                        <input type="checkbox" name="is_active" value="1" {{ $encounterTemplate->is_active ? 'checked' : '' }}>
                        <span>Active (available for use)</span>
                    </label>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><i class="fa-solid fa-diagnoses"></i> Default Diagnoses
                <div class="card-actions"><button type="button" class="btn btn-sm btn-outline" onclick="addDiag()"><i class="fa-solid fa-plus"></i> Add</button></div>
            </div>
            <div class="card-body">
                <div id="diagContainer">
                    @foreach($encounterTemplate->diagnoses ?? [] as $d)
                    <div style="display:flex;gap:8px;align-items:center;margin-bottom:8px">
                        <input type="text" name="diagnoses[]" class="form-control" value="{{ $d }}">
                        <button type="button" onclick="this.parentElement.remove()" class="btn btn-sm btn-outline" style="color:var(--sc-red);border-color:var(--sc-red);flex-shrink:0"><i class="fa-solid fa-trash"></i></button>
                    </div>
                    @endforeach
                </div>
                <div id="noDiag" class="text-muted text-small text-center" style="padding:16px;{{ count($encounterTemplate->diagnoses ?? []) ? 'display:none' : '' }}">No diagnoses.</div>
            </div>
        </div>
    </div>

    <div>
        <div class="card mb-3">
            <div class="card-header"><i class="fa-solid fa-pills"></i> Default Prescription
                <div class="card-actions"><button type="button" class="btn btn-sm btn-outline" onclick="addRx()"><i class="fa-solid fa-plus"></i> Add Drug</button></div>
            </div>
            <div class="card-body">
                <div id="rxContainer">
                @foreach($encounterTemplate->prescription_template ?? [] as $rx)
                <div style="padding:10px;background:var(--sc-bg);border-radius:8px;border:1px solid var(--sc-border);margin-bottom:8px">
                    <div style="display:grid;grid-template-columns:2fr 1fr 1fr 1fr auto;gap:8px;align-items:flex-end">
                        <div class="form-group" style="margin:0"><label class="form-label">Drug</label><input type="text" name="rx_drug[]" class="form-control" value="{{ $rx['drug_name'] ?? '' }}"></div>
                        <div class="form-group" style="margin:0"><label class="form-label">Dose</label><input type="text" name="rx_dose[]" class="form-control" value="{{ $rx['dose'] ?? '' }}"></div>
                        <div class="form-group" style="margin:0"><label class="form-label">Route</label>
                            <select name="rx_route[]" class="form-control">@foreach(['Oral','IV','IM','SC','Topical','Inhaled'] as $r)<option {{ ($rx['route']??'')===$r?'selected':'' }}>{{ $r }}</option>@endforeach</select></div>
                        <div class="form-group" style="margin:0"><label class="form-label">Frequency</label>
                            <select name="rx_frequency[]" class="form-control">@foreach(['OD','BD','TDS','QID','SOS','STAT'] as $f)<option {{ ($rx['frequency']??'')===$f?'selected':'' }}>{{ $f }}</option>@endforeach</select></div>
                        <button type="button" onclick="this.closest('div[style]').remove()" class="btn btn-sm btn-outline" style="color:var(--sc-red);border-color:var(--sc-red);margin-bottom:0"><i class="fa-solid fa-trash"></i></button>
                    </div>
                    <div class="form-group" style="margin-top:8px;margin-bottom:0"><label class="form-label">Instructions</label><input type="text" name="rx_instructions[]" class="form-control" value="{{ $rx['instructions'] ?? '' }}"></div>
                </div>
                @endforeach
                </div>
                <div id="noRx" class="text-muted text-small text-center" style="padding:16px;{{ count($encounterTemplate->prescription_template ?? []) ? 'display:none' : '' }}">No drugs.</div>
            </div>
        </div>

        @php
            $existingLabs   = $encounterTemplate->lab_orders ?? ['tests' => [], 'panels' => []];
            $existingTests  = $existingLabs['tests']  ?? [];
            $existingPanels = $existingLabs['panels'] ?? [];
        @endphp
        <div class="card mb-3">
            <div class="card-header"><i class="fa-solid fa-flask-vial"></i> Default Lab Orders</div>
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label">Panels</label>
                    <select name="lab_panels[]" class="form-control select2-tpl" multiple style="width:100%">
                        @foreach($labPanels as $p)
                            <option value="{{ $p->name }}" {{ in_array($p->name, $existingPanels, true) ? 'selected' : '' }}>
                                {{ $p->name }}@if($p->price) — ₹{{ number_format($p->price, 0) }}@endif
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group" style="margin:0">
                    <label class="form-label">Individual Tests</label>
                    <select name="lab_tests[]" class="form-control select2-tpl" multiple style="width:100%">
                        @foreach($labTests as $t)
                            <option value="{{ $t->name }}" {{ in_array($t->name, $existingTests, true) ? 'selected' : '' }}>
                                {{ $t->name }}@if($t->price) — ₹{{ number_format($t->price, 0) }}@endif
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary" style="width:100%"><i class="fa-solid fa-floppy-disk"></i> Save Changes</button>
    </div>
</div>
</form>

<script>
$(function() { $('.select2-tpl').select2({ placeholder: 'Search and select…', width: '100%', theme: 'classic' }); });
</script>

<script>
function addDiag() {
    document.getElementById('noDiag').style.display = 'none';
    const div = document.createElement('div');
    div.style.cssText = 'display:flex;gap:8px;align-items:center;margin-bottom:8px';
    div.innerHTML = `<input type="text" name="diagnoses[]" class="form-control" placeholder="Diagnosis name">
        <button type="button" onclick="this.parentElement.remove()" class="btn btn-sm btn-outline" style="color:var(--sc-red);border-color:var(--sc-red);flex-shrink:0"><i class="fa-solid fa-trash"></i></button>`;
    document.getElementById('diagContainer').appendChild(div);
}
function addRx() {
    document.getElementById('noRx').style.display = 'none';
    const div = document.createElement('div');
    div.style.cssText = 'padding:10px;background:var(--sc-bg);border-radius:8px;border:1px solid var(--sc-border);margin-bottom:8px';
    div.innerHTML = `
        <div style="display:grid;grid-template-columns:2fr 1fr 1fr 1fr auto;gap:8px;align-items:flex-end">
            <div class="form-group" style="margin:0"><label class="form-label">Drug</label><input type="text" name="rx_drug[]" class="form-control" placeholder="Drug name"></div>
            <div class="form-group" style="margin:0"><label class="form-label">Dose</label><input type="text" name="rx_dose[]" class="form-control"></div>
            <div class="form-group" style="margin:0"><label class="form-label">Route</label>
                <select name="rx_route[]" class="form-control"><option>Oral</option><option>IV</option><option>IM</option><option>SC</option><option>Topical</option><option>Inhaled</option></select></div>
            <div class="form-group" style="margin:0"><label class="form-label">Frequency</label>
                <select name="rx_frequency[]" class="form-control"><option>OD</option><option>BD</option><option>TDS</option><option>QID</option><option>SOS</option><option>STAT</option></select></div>
            <button type="button" onclick="this.closest('div[style]').remove()" class="btn btn-sm btn-outline" style="color:var(--sc-red);border-color:var(--sc-red);margin-bottom:0"><i class="fa-solid fa-trash"></i></button>
        </div>
        <div class="form-group" style="margin-top:8px;margin-bottom:0"><label class="form-label">Instructions</label><input type="text" name="rx_instructions[]" class="form-control"></div>
    `;
    document.getElementById('rxContainer').appendChild(div);
}
</script>
@endsection
