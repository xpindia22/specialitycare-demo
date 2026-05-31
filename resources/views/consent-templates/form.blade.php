@extends('layouts.app')
@section('title', isset($template->id) ? 'Edit Consent Template' : 'New Consent Template')
@section('content')

<div class="page-header">
    <div class="page-header-left">
        <h1>{{ isset($template->id) ? 'Edit' : 'New' }} Consent Template</h1>
    </div>
    <a href="{{ route('consent-templates.index') }}" class="btn btn-outline"><i class="fa-solid fa-arrow-left"></i> Back</a>
</div>

@if($errors->any())<div class="alert alert-danger"><ul style="margin:0;padding-left:18px">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

<form method="POST" action="{{ isset($template->id) ? route('consent-templates.update', $template) : route('consent-templates.store') }}">
    @csrf
    @if(isset($template->id)) @method('PUT') @endif

    <div class="card mb-3">
        <div class="card-header"><i class="fa-solid fa-file-signature"></i> Template Details</div>
        <div class="card-body">
            <div class="form-row cols-2">
                <div class="form-group">
                    <label class="form-label">Name <span class="req">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $template->name) }}" class="form-control" required placeholder="e.g. Anaesthesia Consent — General">
                </div>
                <div class="form-group">
                    <label class="form-label">Category</label>
                    <select name="category" class="form-control">
                        <option value="">— None —</option>
                        @foreach(\App\Models\ConsentTemplate::CATEGORIES as $k => $lbl)
                            <option value="{{ $k }}" {{ old('category', $template->category) === $k ? 'selected' : '' }}>{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $template->is_active ?? true) ? 'checked' : '' }}>
                    <span>Active (available for use)</span>
                </label>
            </div>
            <div class="form-group" style="margin:0">
                <label class="form-label">Body <span class="req">*</span> <span class="text-muted" style="font-size:11px;font-weight:400">— supports HTML and placeholders {patient_name} {jhc_id} {age} {gender} {procedure} {date}</span></label>
                <textarea name="body_html" rows="14" class="form-control" required placeholder="I, {patient_name} (JHC ID: {jhc_id}), aged {age}, hereby authorise…">{{ old('body_html', $template->body_html ?? '') }}</textarea>
            </div>
        </div>
    </div>

    <div style="display:flex;gap:10px">
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> {{ isset($template->id) ? 'Update' : 'Create' }} Template</button>
        <a href="{{ route('consent-templates.index') }}" class="btn btn-outline">Cancel</a>
    </div>
</form>
@endsection
