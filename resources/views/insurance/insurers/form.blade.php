@extends('layouts.app')
@section('title', isset($insurer->id) ? 'Edit Insurer/TPA' : 'Add Insurer/TPA')
@section('page-title', isset($insurer->id) ? 'Edit Insurer / TPA' : 'Add Insurer / TPA')
@section('content')

<div class="page-header">
    <div class="page-header-left">
        <h1>{{ isset($insurer->id) ? 'Edit' : 'Add' }} Insurer / TPA</h1>
        <div class="breadcrumb"><a href="{{ route('insurers.index') }}">Insurers &amp; TPAs</a> &rsaquo; {{ isset($insurer->id) ? 'Edit' : 'New' }}</div>
    </div>
    @include('insurance._toolbar', ['inline'=>true])
</div>

@if($errors->any())
<div class="alert alert-danger"><ul style="margin:0;padding-left:18px">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
@endif

<form method="POST" action="{{ isset($insurer->id) ? route('insurers.update', $insurer) : route('insurers.store') }}" class="card" style="max-width:760px">
    @csrf
    @if(isset($insurer->id)) @method('PUT') @endif
    <div class="card-body">
        <div class="grid" style="grid-template-columns:1fr 1fr;gap:16px">
            <div class="form-group" style="grid-column:1 / -1">
                <label>Name <span style="color:var(--sc-red)">*</span></label>
                <input type="text" name="name" value="{{ old('name', $insurer->name) }}" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Type <span style="color:var(--sc-red)">*</span></label>
                <select name="type" class="form-control">
                    @foreach(\App\Models\Insurer::TYPES as $key => $label)
                    <option value="{{ $key }}" {{ old('type', $insurer->type) === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Code / Short Name</label>
                <input type="text" name="code" value="{{ old('code', $insurer->code) }}" class="form-control" placeholder="e.g. STAR, NIACL">
            </div>
            <div class="form-group">
                <label>Contact Person</label>
                <input type="text" name="contact_person" value="{{ old('contact_person', $insurer->contact_person) }}" class="form-control">
            </div>
            <div class="form-group">
                <label>Phone</label>
                <input type="text" name="contact_phone" value="{{ old('contact_phone', $insurer->contact_phone) }}" class="form-control">
            </div>
            <div class="form-group" style="grid-column:1 / -1">
                <label>Email</label>
                <input type="email" name="contact_email" value="{{ old('contact_email', $insurer->contact_email) }}" class="form-control">
            </div>
            <div class="form-group">
                <label>GST No.</label>
                <input type="text" name="gst_no" value="{{ old('gst_no', $insurer->gst_no) }}" class="form-control">
            </div>
            <div class="form-group" style="display:flex;align-items:flex-end">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;margin:0">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $insurer->is_active ?? true) ? 'checked' : '' }}>
                    <span>Active</span>
                </label>
            </div>
            <div class="form-group" style="grid-column:1 / -1">
                <label>Address</label>
                <textarea name="address" rows="2" class="form-control">{{ old('address', $insurer->address) }}</textarea>
            </div>
        </div>
    </div>
    <div class="card-footer" style="display:flex;gap:10px">
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> {{ isset($insurer->id) ? 'Update' : 'Save' }} Insurer</button>
        <a href="{{ route('insurers.index') }}" class="btn btn-outline">Cancel</a>
    </div>
</form>
@endsection
