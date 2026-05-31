{{-- FILE: resources/views/wards/create.blade.php --}}
@extends('layouts.app')
@section('title','Add Ward')
@section('page-title','Add Ward')
@section('content')

<div class="page-header">
    <div class="page-header-left"><h1>Add Ward</h1></div>
    <a href="{{ route('wards.index') }}" class="btn btn-outline">Back</a>
</div>

<div class="card" style="max-width:560px">
    <div class="card-body" style="padding:20px">
        <form method="POST" action="{{ route('wards.store') }}">
            @csrf
            <div class="form-group">
                <label class="form-label">Ward Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                    value="{{ old('name') }}" placeholder="e.g. General Ward A, Male Surgical, ICU" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="form-group mt-3">
                <label class="form-label">Ward Type <span class="text-danger">*</span></label>
                <select name="type" class="form-control @error('type') is-invalid @enderror" required>
                    @foreach(\App\Models\Ward::TYPES as $val => $label)
                        <option value="{{ $val }}" {{ old('type') === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="form-group mt-3">
                <label class="form-label">Floor / Location</label>
                <input type="text" name="floor" class="form-control" value="{{ old('floor') }}" placeholder="e.g. Ground Floor, 1st Floor, Wing B">
            </div>
            <div class="form-group mt-3">
                <label class="form-label">Description</label>
                <input type="text" name="description" class="form-control" value="{{ old('description') }}" placeholder="Optional note about this ward">
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary">Create Ward</button>
                <a href="{{ route('wards.index') }}" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
