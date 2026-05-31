{{-- FILE: resources/views/echo/edit.blade.php --}}
@extends('layouts.app')
@section('title','Edit Echo '.$echo->record_no)
@section('page-title','Edit Echo Record')
@section('content')

<div class="page-header">
    <div class="page-header-left">
        <h1>Edit Echo — <span class="mono">{{ $echo->record_no }}</span></h1>
        <div class="breadcrumb"><a href="{{ route('echo.index') }}">Echo</a> &rsaquo; <a href="{{ route('echo.show',$echo) }}">{{ $echo->record_no }}</a> &rsaquo; Edit</div>
    </div>
</div>

<form method="POST" action="{{ route('echo.update', $echo) }}" enctype="multipart/form-data">
@csrf
@method('PUT')
<div class="row g-4">
<div class="col-lg-9">
    @include('echo._form', ['isEdit' => true, 'echo' => $echo, 'doctors' => $doctors])
</div>
<div class="col-lg-3">
    <div class="card" style="position:sticky;top:80px">
        <div class="card-body">
            <label class="form-label fw-semibold">Study Fee (INR)</label>
            <input type="number" name="price" class="form-control mb-3" value="{{ old('price', $echo->price) }}" required step="0.01">
            <button type="submit" class="btn btn-primary w-100 mb-2"><i class="fa-solid fa-save me-2"></i>Update Echo Report</button>
            <a href="{{ route('echo.show', $echo) }}" class="btn btn-outline-secondary w-100">Cancel</a>
        </div>
        <div class="card-footer p-3">
            <div class="fw-semibold text-muted small mb-2">NORMAL REFERENCE</div>
            <table class="table table-sm table-borderless mb-0" style="font-size:11px">
                <tr><td>RV WT</td><td class="text-end text-muted">&lt;5 mm</td></tr>
                <tr><td>RVID</td><td class="text-end text-muted">7-23 mm</td></tr>
                <tr><td>TAPSE</td><td class="text-end text-muted">&gt;17 mm</td></tr>
                <tr><td>IVC</td><td class="text-end text-muted">&lt;20 mm</td></tr>
                <tr><td>IVS</td><td class="text-end text-muted">6-12 mm</td></tr>
                <tr><td>LVPW</td><td class="text-end text-muted">6-11 mm</td></tr>
                <tr><td>LVIDD</td><td class="text-end text-muted">35-55 mm</td></tr>
                <tr><td>LVIDS</td><td class="text-end text-muted">24-42 mm</td></tr>
                <tr><td>EPSS</td><td class="text-end text-muted">&lt;7 mm</td></tr>
                <tr><td>MAPSE</td><td class="text-end text-muted">&gt;12 mm</td></tr>
                <tr><td>LA</td><td class="text-end text-muted">19-40 mm</td></tr>
                <tr><td>AO Root</td><td class="text-end text-muted">25-37 mm</td></tr>
                <tr><td>EF</td><td class="text-end text-muted">55-70 %</td></tr>
                <tr><td>FS</td><td class="text-end text-muted">28-41 %</td></tr>
                <tr><td>E/e'</td><td class="text-end text-muted">N &lt;8</td></tr>
                <tr><td>PAP</td><td class="text-end text-muted">&lt;20 mmHg</td></tr>
            </table>
        </div>
    </div>
</div>
</div>
</form>
@endsection
