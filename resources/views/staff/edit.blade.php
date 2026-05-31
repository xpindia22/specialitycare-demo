{{-- FILE: resources/views/staff/edit.blade.php --}}
@extends('layouts.app')
@section('title','Edit Staff')
@section('page-title','Edit Staff')
@section('content')
@php $roles = \App\Models\User::ROLES; @endphp

<div class="page-header">
    <div class="page-header-left">
        <h1>Edit — {{ $staff->first_name }} {{ $staff->last_name }}</h1>
        <div class="breadcrumb"><a href="{{ route('staff.index') }}">Staff</a> › {{ $staff->staff_id }}</div>
    </div>
</div>

@if($errors->any())
<div class="alert alert-danger"><ul style="margin:0;padding-left:16px">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
@endif

<form method="POST" action="{{ route('staff.update', $staff) }}">
@csrf @method('PUT')
<div class="grid" style="grid-template-columns:1fr 300px;gap:20px;align-items:start">
<div style="display:flex;flex-direction:column;gap:20px">

    <div class="card">
        <div class="card-header"><i class="fa-solid fa-user"></i> Personal Info</div>
        <div class="card-body">
            <div class="form-row cols-3">
                <div class="form-group"><label class="form-label">First Name <span class="req">*</span></label><input type="text" name="first_name" class="form-control" value="{{ old('first_name', $staff->first_name) }}" required></div>
                <div class="form-group"><label class="form-label">Last Name <span class="req">*</span></label><input type="text" name="last_name" class="form-control" value="{{ old('last_name', $staff->last_name) }}" required></div>
                <div class="form-group"><label class="form-label">Phone <span class="req">*</span></label><input type="tel" name="phone" class="form-control" value="{{ old('phone', $staff->phone) }}" required></div>
                <div class="form-group"><label class="form-label">Designation <span class="req">*</span></label>
                    <select name="designation" class="form-control" required>
                        @foreach(['Nurse','Head Nurse','Lab Technician','Radiologist','Pharmacist','Receptionist','Billing Staff','Ward Boy','Security','Administrator','IT Staff','Cleaner','Driver','Other'] as $d)
                        <option value="{{ $d }}" {{ old('designation', $staff->designation) === $d ? 'selected' : '' }}>{{ $d }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group"><label class="form-label">Department</label><input type="text" name="department" class="form-control" value="{{ old('department', $staff->department) }}"></div>
                <div class="form-group"><label class="form-label">Salary (₹)</label><input type="number" name="salary" class="form-control" value="{{ old('salary', $staff->salary) }}" min="0"></div>
            </div>
        </div>
    </div>

    @php $hasLogin = $staff->user && $staff->user->has_system_access; @endphp
    @if(!$hasLogin)
    <div class="card">
        <div class="card-header"><i class="fa-solid fa-lock"></i> System Access</div>
        <div class="card-body">
            <div style="padding:14px;background:#f1f5f9;border-radius:6px;color:#475569">
                <i class="fa-solid fa-circle-info"></i>
                This staff member has <strong>no system login</strong>. They appear in attendance, payroll and reports, but cannot sign in. (To grant login later, ask Robert to flip the flag in the DB — a self-service UI for converting is coming when needed.)
            </div>
        </div>
    </div>
    @else
    <div class="card">
        <div class="card-header"><i class="fa-solid fa-lock"></i> System Access</div>
        <div class="card-body">
            <div class="form-row cols-2">
                <div class="form-group">
                    <label class="form-label">Login Email</label>
                    <input type="email" class="form-control" value="{{ $staff->user->email }}" disabled style="background:var(--sc-bg);color:var(--sc-muted)">
                    <div class="text-small text-muted" style="margin-top:3px">Email cannot be changed here.</div>
                </div>
                <div class="form-group" style="grid-column:span 2">
                    <label class="form-label">System Roles <span class="req">*</span></label>
                    @php
                        $allRoles = \App\Models\Role::orderBy('sort_order')->get();
                        $current = old('roles', $staff->user->roles->pluck('name')->all());
                    @endphp
                    <div class="grid grid-3" style="gap:8px">
                        @foreach($allRoles as $r)
                        <label style="display:flex;align-items:center;gap:8px;padding:8px 12px;border:1.5px solid var(--sc-border);border-radius:6px;cursor:pointer;font-size:13px">
                            <input type="checkbox" name="roles[]" value="{{ $r->name }}" {{ in_array($r->name, $current) ? 'checked' : '' }}>
                            <span style="flex:1"><strong>{{ $r->label }}</strong> <span class="text-small text-muted">({{ count($r->permissions ?? []) }})</span></span>
                        </label>
                        @endforeach
                    </div>
                    <div class="text-small text-muted" style="margin-top:4px">Tick all roles this user holds. Effective permissions are the union.</div>
                </div>
                <div class="form-group">
                    <label class="form-label">Account Status</label>
                    <div style="display:flex;gap:16px;padding-top:8px">
                        <label style="display:flex;align-items:center;gap:6px;cursor:pointer">
                            <input type="radio" name="is_active" value="1" {{ old('is_active', $staff->user->is_active ? '1' : '0') === '1' ? 'checked' : '' }}> Active
                        </label>
                        <label style="display:flex;align-items:center;gap:6px;cursor:pointer;color:var(--sc-red)">
                            <input type="radio" name="is_active" value="0" {{ old('is_active', $staff->user->is_active ? '1' : '0') === '0' ? 'checked' : '' }}> Suspended
                        </label>
                    </div>
                </div>
            </div>
            <hr style="border:none;border-top:1px solid var(--sc-border);margin:16px 0">
            <div style="font-size:12px;font-weight:600;color:var(--sc-muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:12px">Reset Password (leave blank to keep current)</div>
            <div class="form-row cols-2">
                <div class="form-group"><label class="form-label">New Password</label><input type="password" name="password" class="form-control" minlength="6" placeholder="Min. 6 characters"></div>
                <div class="form-group"><label class="form-label">Confirm Password</label><input type="password" name="password_confirmation" class="form-control"></div>
            </div>
        </div>
    </div>
    @endif

    <div class="card">
        <div class="card-header"><i class="fa-solid fa-calendar-check"></i> Attendance</div>
        <div class="card-body">
            <div class="form-group" style="margin-bottom:0">
                <label class="form-label">Auto-Mark Present</label>
                <label style="display:flex;align-items:flex-start;gap:8px;padding:10px 12px;border:1.5px solid var(--sc-border);border-radius:6px;cursor:pointer;background:#f8fafc">
                    <input type="checkbox" name="auto_present" value="1" {{ old('auto_present', $staff->auto_present) ? 'checked' : '' }} style="margin-top:2px">
                    <span>
                        <strong>Mark this staff Present by default on every day</strong>
                        <div class="text-small text-muted" style="margin-top:2px">
                            System auto-creates a Present record (back to joining date and forward each day) for any day with no manual entry. Marking them Absent / Leave / Half-Day manually always takes precedence.
                        </div>
                    </span>
                </label>
            </div>
        </div>
    </div>

</div>
<div>
    <div class="card mb-3">
        <div class="card-body" style="padding:16px">
            <div class="form-label" style="margin-bottom:6px">Staff ID</div>
            <div class="fw-700 mono" style="font-size:18px">{{ $staff->staff_id }}</div>
            <div class="form-label" style="margin-top:12px;margin-bottom:6px">Joined</div>
            <div>{{ $staff->joining_date->format('d M Y') }}</div>
            @if($hasLogin)
                <div class="form-label" style="margin-top:12px;margin-bottom:6px">Login Role</div>
                <span class="badge badge-navy">{{ $roles[$staff->user->role] ?? $staff->user->role }}</span>
                <div class="form-label" style="margin-top:12px;margin-bottom:6px">Account</div>
                <span class="badge {{ $staff->user->is_active ? 'badge-green' : 'badge-red' }}">{{ $staff->user->is_active ? 'Active' : 'Suspended' }}</span>
            @else
                <div class="form-label" style="margin-top:12px;margin-bottom:6px">System Access</div>
                <span class="badge" style="background:#e2e8f0;color:#475569">No login</span>
            @endif
        </div>
    </div>
    <div class="card">
        <div class="card-body" style="display:flex;flex-direction:column;gap:10px;padding:16px">
            <button type="submit" class="btn btn-primary btn-lg" style="width:100%;justify-content:center"><i class="fa-solid fa-save"></i> Save Changes</button>
            <a href="{{ route('staff.index') }}" class="btn btn-outline" style="width:100%;justify-content:center">Cancel</a>
        </div>
    </div>
</div>
</div>
</form>
@endsection
