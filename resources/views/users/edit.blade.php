@extends('layouts.app')
@section('title','Manage User')
@section('page-title','Manage User')

@push('styles')
<style>
    .role-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(160px,1fr)); gap:6px; margin-top:6px; }
    .role-grid label { background:var(--sc-surface-2,#f6f8fb); border:1px solid var(--sc-border); border-radius:6px; padding:6px 10px; font-size:12.5px; font-weight:normal; cursor:pointer; display:flex; align-items:center; gap:6px; }
</style>
@endpush

@section('content')
<div class="page-header">
    <div class="page-header-left">
        <a href="{{ route('users.index') }}" class="text-small" style="text-decoration:none"><i class="fa-solid fa-arrow-left"></i> Users</a>
        <h1>{{ $user->name }}</h1>
        <p class="text-small text-muted">{{ $user->email }}</p>
    </div>
    <div class="page-header-right">
        @if($user->id !== auth()->id())
        <form method="POST" action="{{ route('users.toggle-active', $user) }}" onsubmit="return confirm('{{ $user->is_active ? 'Block' : 'Unblock' }} this account?')">
            @csrf
            <button class="btn btn-outline" style="{{ $user->is_active ? 'color:#b91c1c' : '' }}">
                <i class="fa-solid {{ $user->is_active ? 'fa-ban' : 'fa-circle-check' }}"></i>
                {{ $user->is_active ? 'Block login' : 'Unblock login' }}
            </button>
        </form>
        @endif
    </div>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:16px;max-width:920px">
    <div style="background:var(--sc-surface);border:1px solid var(--sc-border);border-radius:10px;padding:18px">
        <div style="font-size:12px;font-weight:600;color:var(--sc-muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:12px">Reset Password</div>
        <form method="POST" action="{{ route('users.reset-password', $user) }}">
            @csrf
            <div class="form-group"><label class="form-label">New Password</label>
                <input type="password" name="password" required minlength="6" autocomplete="new-password" class="form-control" placeholder="Min. 6 characters"></div>
            <div class="form-group" style="margin-top:10px"><label class="form-label">Confirm Password</label>
                <input type="password" name="password_confirmation" required minlength="6" autocomplete="new-password" class="form-control"></div>
            <p class="text-small text-muted" style="margin-top:8px">No current-password needed — this is an admin reset. The user can change it themselves later under My Profile.</p>
            <div style="margin-top:12px;text-align:right">
                <button class="btn btn-primary"><i class="fa-solid fa-key"></i> Reset Password</button>
            </div>
        </form>
    </div>

    <div style="background:var(--sc-surface);border:1px solid var(--sc-border);border-radius:10px;padding:18px">
        <div style="font-size:12px;font-weight:600;color:var(--sc-muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:12px">Roles</div>
        <form method="POST" action="{{ route('users.roles', $user) }}">
            @csrf
            @php $cur = $user->roles->pluck('name')->all(); @endphp
            <div class="role-grid">
                @foreach($roles as $r)
                    <label>
                        <input type="checkbox" name="roles[]" value="{{ $r->name }}" {{ in_array($r->name, $cur, true) ? 'checked' : '' }}>
                        {{ $r->label }}
                    </label>
                @endforeach
            </div>
            <p class="text-small text-muted" style="margin-top:8px">The first ticked role is treated as the primary role.</p>
            <div style="margin-top:12px;text-align:right">
                <button class="btn btn-primary"><i class="fa-solid fa-user-shield"></i> Save Roles</button>
            </div>
        </form>
    </div>

    @if(auth()->user()?->isOwner())
    <div style="background:var(--sc-surface);border:1px solid var(--sc-border);border-radius:10px;padding:18px">
        <div style="font-size:12px;font-weight:600;color:var(--sc-muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:12px">Special Permissions</div>
        <form method="POST" action="{{ route('users.delete-visits', $user) }}">
            @csrf
            @if($user->isOwner())
                <p class="text-small"><i class="fa-solid fa-crown" style="color:#d97706"></i> Owner: can always delete visits and see Deleted Visits.</p>
            @else
            <label style="display:flex;gap:8px;align-items:flex-start;cursor:pointer;font-weight:normal">
                <input type="checkbox" name="can_delete_visits" value="1" {{ $user->can_delete_visits ? 'checked' : '' }} style="margin-top:3px">
                <span><b>Can delete visits</b><br>
                <span class="text-small text-muted">Shows a Delete Visit button on each visit. Deleted visits are hidden, not erased: only you can see, restore or permanently delete them under Deleted Visits.</span></span>
            </label>
            <div style="margin-top:12px;text-align:right">
                <button class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save</button>
            </div>
            @endif
        </form>
    </div>
    @endif
</div>
@endsection
