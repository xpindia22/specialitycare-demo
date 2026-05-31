{{-- FILE: resources/views/wards/edit.blade.php --}}
@extends('layouts.app')
@section('title','Edit Ward')
@section('page-title','Edit Ward')
@section('content')

<div class="page-header">
    <div class="page-header-left"><h1>Edit Ward — {{ $ward->name }}</h1></div>
    <a href="{{ route('wards.show', $ward) }}" class="btn btn-outline">Back</a>
</div>

<div class="card" style="max-width:560px">
    <div class="card-body" style="padding:20px">
        <form method="POST" action="{{ route('wards.update', $ward) }}">
            @csrf @method('PUT')
            <div class="form-group">
                <label class="form-label">Ward Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $ward->name) }}" required>
            </div>
            <div class="form-group mt-3">
                <label class="form-label">Ward Type <span class="text-danger">*</span></label>
                <select name="type" class="form-control" required>
                    @foreach(\App\Models\Ward::TYPES as $val => $label)
                        <option value="{{ $val }}" {{ old('type', $ward->type) === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group mt-3">
                <label class="form-label">Floor / Location</label>
                <input type="text" name="floor" class="form-control" value="{{ old('floor', $ward->floor) }}">
            </div>
            <div class="form-group mt-3">
                <label class="form-label">Description</label>
                <input type="text" name="description" class="form-control" value="{{ old('description', $ward->description) }}">
            </div>
            <div class="form-group mt-3">
                <label class="d-flex align-items-center gap-2" style="cursor:pointer">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $ward->is_active) ? 'checked' : '' }}>
                    <span class="form-label mb-0">Ward is Active</span>
                </label>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary">Update Ward</button>
                <a href="{{ route('wards.show', $ward) }}" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
