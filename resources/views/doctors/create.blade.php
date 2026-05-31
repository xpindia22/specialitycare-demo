{{-- FILE: resources/views/doctors/create.blade.php --}}
@extends('layouts.app')
@section('title','Register Doctor')
@section('page-title','Register Doctor')
@section('content')
<div class="page-header"><div class="page-header-left"><h1>Register New Doctor</h1><div class="breadcrumb"><a href="{{ route('doctors.index') }}">Doctors</a> › Register</div></div></div>

<form method="POST" action="{{ route('doctors.store') }}" enctype="multipart/form-data">
@csrf
<div class="grid" style="grid-template-columns:1fr 300px;gap:20px;align-items:start">
<div style="display:flex;flex-direction:column;gap:20px">

    <div class="card">
        <div class="card-header"><i class="fa-solid fa-user-doctor"></i> Personal Information</div>
        <div class="card-body">
            <div class="form-row cols-3">
                <div class="form-group"><label class="form-label">First Name <span class="req">*</span></label><input type="text" name="first_name" class="form-control" value="{{ old('first_name') }}" required></div>
                <div class="form-group"><label class="form-label">Last Name <span class="req">*</span></label><input type="text" name="last_name" class="form-control" value="{{ old('last_name') }}" required></div>
                <div class="form-group"><label class="form-label">Gender <span class="req">*</span></label><select name="gender" class="form-control" required><option value="">Select</option>@foreach(['Male','Female','Other'] as $g)<option value="{{ $g }}" {{ old('gender')==$g?'selected':'' }}>{{ $g }}</option>@endforeach</select></div>
            </div>
            <div class="form-row cols-3">
                <div class="form-group"><label class="form-label">Date of Birth</label><input type="date" name="date_of_birth" class="form-control" value="{{ old('date_of_birth') }}"></div>
                <div class="form-group"><label class="form-label">Phone <span class="req">*</span></label><input type="tel" name="phone" class="form-control" value="{{ old('phone') }}" required></div>
                <div class="form-group"><label class="form-label">Email <span class="req">*</span></label><input type="email" name="email" class="form-control" value="{{ old('email') }}" required></div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><i class="fa-solid fa-stethoscope"></i> Professional Info</div>
        <div class="card-body">
            <div class="form-row cols-2">
                <div class="form-group"><label class="form-label">Specialization <span class="req">*</span></label><input type="text" name="specialization" class="form-control" value="{{ old('specialization') }}" required></div>
                <div class="form-group"><label class="form-label">Department</label><select name="department_id" class="form-control"><option value="">None</option>@foreach($departments as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach</select></div>
                <div class="form-group"><label class="form-label">Qualification <span class="req">*</span></label><input type="text" name="qualification" class="form-control" placeholder="MBBS, MD, DM..." value="{{ old('qualification') }}" required></div>
                <div class="form-group"><label class="form-label">Registration No. <span class="req">*</span></label><input type="text" name="registration_no" class="form-control" value="{{ old('registration_no') }}" required></div>
                <div class="form-group"><label class="form-label">Experience (years)</label><input type="number" name="experience_years" class="form-control" value="{{ old('experience_years') }}" min="0"></div>
                <div class="form-group"><label class="form-label">Consultation Fee (₹) <span class="req">*</span></label><input type="number" name="consultation_fee" class="form-control" value="{{ old('consultation_fee', 500) }}" required min="0"></div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><i class="fa-solid fa-clock"></i> Schedule</div>
        <div class="card-body">
            <div class="form-group"><label class="form-label">Available Days</label>
                <div style="display:flex;flex-wrap:wrap;gap:8px">
                    @foreach(['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'] as $day)
                    <label style="display:flex;align-items:center;gap:6px;padding:6px 12px;background:var(--sc-bg);border-radius:6px;border:1px solid var(--sc-border);cursor:pointer;font-size:12.5px">
                        <input type="checkbox" name="available_days[]" value="{{ $day }}"> {{ substr($day,0,3) }}
                    </label>
                    @endforeach
                </div>
            </div>
            <div class="form-row cols-3">
                <div class="form-group"><label class="form-label">Start Time</label><input type="time" name="start_time" class="form-control" value="09:00"></div>
                <div class="form-group"><label class="form-label">End Time</label><input type="time" name="end_time" class="form-control" value="17:00"></div>
                <div class="form-group"><label class="form-label">Slot Duration (min)</label><input type="number" name="slot_duration" class="form-control" value="15" min="5" max="60"></div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><i class="fa-solid fa-lock"></i> Login Credentials</div>
        <div class="card-body">
            <div class="form-row cols-2">
                <div class="form-group"><label class="form-label">Password <span class="req">*</span></label><input type="password" name="password" class="form-control" required></div>
                <div class="form-group"><label class="form-label">Confirm Password <span class="req">*</span></label><input type="password" name="password_confirmation" class="form-control" required></div>
            </div>
        </div>
    </div>
</div>

<div>
    <div class="card mb-3">
        <div class="card-header"><i class="fa-solid fa-camera"></i> Photo</div>
        <div class="card-body text-center">
            <div id="photo-preview" style="width:100px;height:100px;border-radius:50%;background:var(--sc-bg);border:2px dashed var(--sc-border);display:flex;align-items:center;justify-content:center;margin:0 auto 10px;overflow:hidden;cursor:pointer" onclick="document.getElementById('photo-input').click()">
                <i class="fa-solid fa-camera" style="font-size:24px;color:var(--sc-muted)"></i>
            </div>
            <input type="file" name="photo" id="photo-input" accept="image/*" style="display:none" onchange="previewPhoto(this)">
            <div class="text-small text-muted">Click to upload</div>
        </div>
    </div>
    <div class="card">
        <div class="card-body" style="display:flex;flex-direction:column;gap:10px">
            <button type="submit" class="btn btn-primary btn-lg" style="width:100%;justify-content:center"><i class="fa-solid fa-user-plus"></i> Register Doctor</button>
            <a href="{{ route('doctors.index') }}" class="btn btn-outline" style="width:100%;justify-content:center">Cancel</a>
        </div>
    </div>
</div>
</div>
</form>
@endsection
@push('scripts')
<script>function previewPhoto(input){if(input.files&&input.files[0]){const r=new FileReader();r.onload=e=>{const p=document.getElementById('photo-preview');p.innerHTML=`<img src="${e.target.result}" style="width:100%;height:100%;object-fit:cover">`};r.readAsDataURL(input.files[0])}}</script>
@endpush
