{{-- FILE: resources/views/staff/create.blade.php --}}
@extends('layouts.app')
@section('title','Add Staff')
@section('page-title','Add Staff')
@section('content')
<div class="page-header"><div class="page-header-left"><h1>Register Staff Member</h1></div></div>
<form method="POST" action="{{ route('staff.store') }}" enctype="multipart/form-data">
@csrf
<div class="grid" style="grid-template-columns:1fr 300px;gap:20px;align-items:start">
<div style="display:flex;flex-direction:column;gap:20px">
    <div class="card">
        <div class="card-header"><i class="fa-solid fa-user"></i> Personal Info</div>
        <div class="card-body">
            <div class="form-row cols-3">
                <div class="form-group"><label class="form-label">First Name <span class="req">*</span></label><input type="text" name="first_name" class="form-control" value="{{ old('first_name') }}" required></div>
                <div class="form-group"><label class="form-label">Last Name <span class="req">*</span></label><input type="text" name="last_name" class="form-control" value="{{ old('last_name') }}" required></div>
                <div class="form-group"><label class="form-label">Gender <span class="req">*</span></label><select name="gender" class="form-control" required><option value="">Select</option>@foreach(['Male','Female','Other'] as $g)<option value="{{ $g }}" {{ old('gender')==$g?'selected':'' }}>{{ $g }}</option>@endforeach</select></div>
                <div class="form-group"><label class="form-label">Date of Birth</label><input type="date" name="date_of_birth" class="form-control" value="{{ old('date_of_birth') }}"></div>
                <div class="form-group"><label class="form-label">Phone <span class="req">*</span></label><input type="tel" name="phone" class="form-control" value="{{ old('phone') }}" required></div>
                <div class="form-group js-login-required"><label class="form-label">Email <span class="req">*</span></label><input type="email" name="email" class="form-control js-login-input" value="{{ old('email') }}" required></div>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><i class="fa-solid fa-id-badge"></i> Employment Details</div>
        <div class="card-body">
            <div class="form-row cols-2">
                <div class="form-group"><label class="form-label">Designation <span class="req">*</span></label>
                    <select name="designation" class="form-control" required>
                        <option value="">Select Role</option>
                        @foreach(['Nurse','Head Nurse','Lab Technician','Radiologist','Pharmacist','Receptionist','Billing Staff','Ward Boy','Security','Administrator','IT Staff','Cleaner','Driver','Other'] as $d)
                            <option value="{{ $d }}" {{ old('designation')==$d?'selected':'' }}>{{ $d }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group"><label class="form-label">Department</label><input type="text" name="department" class="form-control" value="{{ old('department') }}" placeholder="e.g. OPD, Lab, Pharmacy"></div>
                <div class="form-group"><label class="form-label">Joining Date <span class="req">*</span></label><input type="date" name="joining_date" class="form-control" value="{{ old('joining_date', today()->toDateString()) }}" required></div>
                <div class="form-group"><label class="form-label">Salary (₹)</label><input type="number" name="salary" class="form-control" value="{{ old('salary') }}" min="0"></div>
                <div class="form-group"><label class="form-label">Aadhar No.</label><input type="text" name="aadhar_no" class="form-control" value="{{ old('aadhar_no') }}" maxlength="20"></div>
                <div class="form-group"><label class="form-label">Qualification</label><input type="text" name="qualification" class="form-control" value="{{ old('qualification') }}"></div>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><i class="fa-solid fa-lock"></i> System Access</div>
        <div class="card-body">
            <div class="form-group" style="margin-bottom:14px">
                <label style="display:flex;align-items:flex-start;gap:10px;padding:12px;border:1.5px solid var(--sc-border);border-radius:6px;cursor:pointer;background:#f8fafc">
                    <input type="checkbox" name="grant_login" id="grant_login" value="1" {{ old('grant_login', '1') ? 'checked' : '' }} style="margin-top:2px">
                    <span>
                        <strong>This staff member will log in to the system</strong>
                        <div class="text-small text-muted" style="margin-top:2px">
                            Untick for cleaners, drivers, ward boys etc. who only need attendance / payroll tracking, no login. Email, roles, and password become optional.
                        </div>
                    </span>
                </label>
            </div>
            <div id="login-fields">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">System Roles <span class="req js-login-required-mark">*</span></label>
                        @php $allRoles = \App\Models\Role::orderBy('sort_order')->get(); $picked = old('roles', []); @endphp
                        <div class="grid grid-3" style="gap:8px">
                            @foreach($allRoles as $r)
                            <label style="display:flex;align-items:center;gap:8px;padding:8px 12px;border:1.5px solid var(--sc-border);border-radius:6px;cursor:pointer;font-size:13px">
                                <input type="checkbox" name="roles[]" value="{{ $r->name }}" {{ in_array($r->name, $picked) ? 'checked' : '' }}>
                                <span style="flex:1"><strong>{{ $r->label }}</strong> <span class="text-small text-muted">({{ count($r->permissions ?? []) }} perms)</span></span>
                            </label>
                            @endforeach
                        </div>
                        <div class="text-small text-muted" style="margin-top:4px">Tick one or more. The first one becomes the user's primary role for display.</div>
                    </div>
                </div>
                <div class="form-row cols-2">
                    <div class="form-group"><label class="form-label">Password <span class="req">*</span></label><input type="password" name="password" class="form-control js-login-input" required minlength="6"></div>
                    <div class="form-group"><label class="form-label">Confirm Password <span class="req">*</span></label><input type="password" name="password_confirmation" class="form-control js-login-input" required></div>
                </div>
            </div>
        </div>
    </div>
</div>
<div>
    <div class="card mb-3">
        <div class="card-header"><i class="fa-solid fa-camera"></i> Photo</div>
        <div class="card-body text-center">
            <div style="width:100px;height:100px;border-radius:50%;background:var(--sc-bg);border:2px dashed var(--sc-border);display:flex;align-items:center;justify-content:center;margin:0 auto 10px;cursor:pointer" onclick="document.getElementById('photo-input').click()">
                <i class="fa-solid fa-camera" style="font-size:24px;color:var(--sc-muted)"></i>
            </div>
            <input type="file" name="photo" id="photo-input" accept="image/*" style="display:none">
            <div class="text-small text-muted">Click to upload</div>
        </div>
    </div>
    <div class="card">
        <div class="card-body" style="display:flex;flex-direction:column;gap:10px">
            <button type="submit" class="btn btn-primary btn-lg" style="width:100%;justify-content:center"><i class="fa-solid fa-user-plus"></i> Add Staff</button>
            <a href="{{ route('staff.index') }}" class="btn btn-outline" style="width:100%;justify-content:center">Cancel</a>
        </div>
    </div>
</div>
</div>
</form>
@push('scripts')
<script>
(function () {
    const toggle = document.getElementById('grant_login');
    const block  = document.getElementById('login-fields');
    const emailWrap = document.querySelector('.js-login-required');
    function apply() {
        const grant = toggle.checked;
        block.style.display    = grant ? '' : 'none';
        emailWrap.style.display = grant ? '' : 'none';
        document.querySelectorAll('.js-login-input').forEach(el => {
            if (grant) el.setAttribute('required', 'required');
            else       el.removeAttribute('required');
        });
        document.querySelectorAll('.js-login-required-mark').forEach(el => {
            el.style.visibility = grant ? '' : 'hidden';
        });
    }
    toggle.addEventListener('change', apply);
    apply();
})();
</script>
@endpush
@endsection
