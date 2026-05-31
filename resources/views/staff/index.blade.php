{{-- FILE: resources/views/staff/index.blade.php --}}
@extends('layouts.app')
@section('title','Staff & Doctors')
@section('page-title','Staff & Doctors')
@section('content')
@php $roles = \App\Models\User::ROLES; @endphp

<div class="page-header">
    <div class="page-header-left"><h1>Staff & User Management</h1>
        <div class="breadcrumb">{{ $people->count() }} {{ $filter ? "matching" : "active" }} record{{ $people->count() === 1 ? '' : 's' }}{{ $filter ? " · filter: {$filter}" : "" }}</div>
    </div>
    <div style="display:flex;gap:8px;align-items:center">
        <form method="GET" style="margin:0">
            <select name="designation" class="form-control form-control-sm" onchange="this.form.submit()" style="min-width:160px">
                <option value="">All designations</option>
                @foreach($designations as $d)
                    <option value="{{ $d }}" {{ $filter === $d ? 'selected' : '' }}>{{ $d }}</option>
                @endforeach
            </select>
        </form>
        @if($filter)
            <a href="{{ route('staff.index') }}" class="btn btn-outline btn-sm" title="Clear filter"><i class="fa-solid fa-xmark"></i></a>
        @endif
        <a href="{{ route('staff.duty-roster') }}" class="btn btn-outline"><i class="fa-solid fa-calendar-days"></i> Duty Roster</a>
        <a href="{{ route('doctors.create') }}" class="btn btn-outline"><i class="fa-solid fa-user-doctor"></i> Add Doctor</a>
        <a href="{{ route('staff.create') }}" class="btn btn-primary"><i class="fa-solid fa-user-plus"></i> Add Staff</a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="card">
    <div class="table-wrap">
        <table class="sc-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Designation</th>
                    <th>Login Email</th>
                    <th>System Role</th>
                    <th>Phone</th>
                    <th>Joined</th>
                    <th>Account</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @forelse($people as $p)
            @php
                $hasLogin = $p->user && $p->user->has_system_access;
                $isDoc = $p->kind === 'doctor';
            @endphp
            <tr>
                <td class="mono fw-600">{{ $p->code }}</td>
                <td>
                    <div class="fw-600">
                        {{ $p->display_name }}
                        @if($isDoc)
                            <span class="badge" style="background:#e0f2fe;color:#0369a1;font-size:10px;padding:1px 6px;margin-left:4px">Doctor</span>
                        @endif
                    </div>
                </td>
                <td>{{ $p->designation }}</td>
                <td class="mono text-small">
                    @if($hasLogin)
                        {{ $p->user->email }}
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </td>
                <td>
                    @if($hasLogin)
                        @php $roleKey = $p->user->role ?? 'staff'; @endphp
                        <span class="badge {{ $roleKey === 'admin' ? 'badge-red' : ($roleKey === 'doctor' ? 'badge-teal' : 'badge-navy') }}">
                            {{ $roles[$roleKey] ?? ucfirst($roleKey) }}
                        </span>
                    @else
                        <span class="badge" style="background:#e2e8f0;color:#475569">No login</span>
                    @endif
                </td>
                <td>{{ $p->phone }}</td>
                <td>{{ $p->joined ? \Illuminate\Support\Carbon::parse($p->joined)->format('d M Y') : '—' }}</td>
                <td>
                    @if($hasLogin)
                        <span class="badge {{ $p->user->is_active ? 'badge-green' : 'badge-red' }}">
                            {{ $p->user->is_active ? 'Active' : 'Blocked' }}
                        </span>
                    @else
                        <span class="badge" style="background:#e2e8f0;color:#475569">—</span>
                    @endif
                </td>
                <td style="white-space:nowrap">
                    <a href="{{ $p->edit_url }}" class="btn btn-sm btn-outline"><i class="fa-solid fa-pen"></i> Edit</a>
                    @if($hasLogin && auth()->user()->can('admin.users'))
                        <button type="button" class="btn btn-sm btn-outline"
                                onclick="openPwReset('{{ route('users.reset-password', $p->user) }}', '{{ addslashes($p->display_name) }}')"
                                title="Reset this user's password">
                            <i class="fa-solid fa-key"></i> Reset PW
                        </button>
                    @endif
                    @if($hasLogin)
                        @if($p->user->id === auth()->id())
                            <button type="button" class="btn btn-sm btn-outline" disabled title="This is your own login">
                                <i class="fa-solid fa-user-shield"></i> You
                            </button>
                        @else
                            <form method="POST" action="{{ route('users.toggle-active', $p->user) }}" style="display:inline" onsubmit="return confirm('{{ $p->user->is_active ? 'Block' : 'Unblock' }} login for {{ addslashes($p->display_name) }}?')">
                                @csrf
                                <button type="submit" class="btn btn-sm {{ $p->user->is_active ? 'btn-outline' : 'btn-primary' }}" title="{{ $p->user->is_active ? 'Block login' : 'Unblock login' }}">
                                    <i class="fa-solid {{ $p->user->is_active ? 'fa-ban' : 'fa-unlock' }}"></i>
                                    {{ $p->user->is_active ? 'Block' : 'Unblock' }}
                                </button>
                            </form>
                        @endif
                    @endif
                </td>
            </tr>
            @empty
            <tr><td colspan="9" class="text-center text-muted" style="padding:40px">
                @if($filter)
                    No matching records for designation <strong>"{{ $filter }}"</strong>. <a href="{{ route('staff.index') }}" style="color:var(--sc-teal)">Clear filter</a>.
                @else
                    No staff or doctors registered yet.
                @endif
            </td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

@if(auth()->user()->can('admin.users'))
{{-- Shared password-reset dialog. The "Reset PW" buttons set the form
     action to the right user's route and reveal this overlay. --}}
<div id="pwResetOverlay" style="display:none;position:fixed;inset:0;background:rgba(15,31,61,.45);z-index:1000;align-items:center;justify-content:center">
    <div style="background:var(--sc-surface,#fff);border-radius:12px;padding:22px;width:380px;max-width:92vw;box-shadow:0 12px 40px rgba(0,0,0,.25)">
        <h3 style="margin:0 0 2px;font-size:17px"><i class="fa-solid fa-key" style="color:var(--sc-amber,#b45309)"></i> Reset Password</h3>
        <p class="text-small text-muted" style="margin:0 0 14px">for <strong id="pwResetName">—</strong></p>
        <form method="POST" id="pwResetForm">
            @csrf
            <div class="form-group">
                <label class="form-label">New Password</label>
                <input type="password" name="password" id="pwResetPw" required minlength="6" autocomplete="new-password" class="form-control" placeholder="Min. 6 characters">
            </div>
            <div class="form-group" style="margin-top:10px">
                <label class="form-label">Confirm Password</label>
                <input type="password" name="password_confirmation" required minlength="6" autocomplete="new-password" class="form-control">
            </div>
            <p class="text-small text-muted" style="margin-top:8px">The user can change it themselves later under My Profile.</p>
            <div style="margin-top:16px;display:flex;gap:8px;justify-content:flex-end">
                <button type="button" class="btn btn-outline" onclick="closePwReset()">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-key"></i> Reset Password</button>
            </div>
        </form>
    </div>
</div>
<script>
function openPwReset(action, name) {
    var f = document.getElementById('pwResetForm');
    f.action = action;
    document.getElementById('pwResetName').textContent = name;
    document.getElementById('pwResetPw').value = '';
    document.getElementById('pwResetOverlay').style.display = 'flex';
    setTimeout(function(){ document.getElementById('pwResetPw').focus(); }, 50);
}
function closePwReset() {
    document.getElementById('pwResetOverlay').style.display = 'none';
}
document.getElementById('pwResetOverlay').addEventListener('click', function(e) {
    if (e.target === this) closePwReset();
});
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closePwReset();
});
</script>
@endif
@endsection
