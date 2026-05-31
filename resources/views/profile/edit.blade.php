@extends('layouts.app')
@section('title','My Profile')
@section('page-title','My Profile')

@section('content')
<div class="page-header">
    <div class="page-header-left">
        <h1>My Profile</h1>
        <p class="text-small text-muted">Your account and password.</p>
    </div>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:16px;max-width:860px">
    <div style="background:var(--sc-surface);border:1px solid var(--sc-border);border-radius:10px;padding:18px">
        <div style="font-size:12px;font-weight:600;color:var(--sc-muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:12px">Account</div>
        <div class="form-group"><label class="form-label">Name</label><input type="text" class="form-control" value="{{ $user->name }}" disabled></div>
        <div class="form-group" style="margin-top:10px"><label class="form-label">Email</label><input type="text" class="form-control" value="{{ $user->email }}" disabled></div>
        <div class="form-group" style="margin-top:10px"><label class="form-label">Roles</label>
            <input type="text" class="form-control" value="{{ $user->roles->pluck('label')->implode(', ') ?: ucfirst($user->role) }}" disabled></div>
        <p class="text-small text-muted" style="margin-top:8px">Name, email and roles are managed by an administrator under Administration → Users.</p>
    </div>

    <div style="background:var(--sc-surface);border:1px solid var(--sc-border);border-radius:10px;padding:18px">
        <div style="font-size:12px;font-weight:600;color:var(--sc-muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:12px">Change Password</div>
        <form method="POST" action="{{ route('profile.password') }}">
            @csrf
            <div class="form-group"><label class="form-label">Current Password</label>
                <input type="password" name="current_password" required autocomplete="current-password" class="form-control"></div>
            <div class="form-group" style="margin-top:10px"><label class="form-label">New Password</label>
                <input type="password" name="password" required minlength="6" autocomplete="new-password" class="form-control" placeholder="Min. 6 characters"></div>
            <div class="form-group" style="margin-top:10px"><label class="form-label">Confirm New Password</label>
                <input type="password" name="password_confirmation" required minlength="6" autocomplete="new-password" class="form-control"></div>
            <div style="margin-top:14px;text-align:right">
                <button class="btn btn-primary" type="submit"><i class="fa-solid fa-key"></i> Update Password</button>
            </div>
        </form>
    </div>
</div>
@endsection
