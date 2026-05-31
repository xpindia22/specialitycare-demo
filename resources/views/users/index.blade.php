@extends('layouts.app')
@section('title','Users')
@section('page-title','Users')

@push('styles')
<style>
    .user-tbl { width:100%; border-collapse:collapse; background:var(--sc-surface); border:1px solid var(--sc-border); border-radius:10px; overflow:hidden; }
    .user-tbl th, .user-tbl td { padding:11px 14px; text-align:left; font-size:13px; border-bottom:1px solid var(--sc-border); }
    .user-tbl th { background:var(--sc-surface-2,#f6f8fb); font-size:11px; text-transform:uppercase; letter-spacing:.4px; color:var(--sc-muted); }
    .user-tbl tr:last-child td { border-bottom:0; }
    .badge-role { display:inline-block; font-size:10.5px; font-weight:600; background:#e0e7ff; color:#3730a3; border-radius:8px; padding:1px 7px; margin:1px 2px 1px 0; }
    .badge-on   { color:#15693a; font-weight:700; }
    .badge-off  { color:#922020; font-weight:700; }
    .badge-nostaff { font-size:10px; color:#a16207; background:#fef3c7; border-radius:8px; padding:1px 6px; }
</style>
@endpush

@section('content')
<div class="page-header">
    <div class="page-header-left">
        <h1>Users</h1>
        <p class="text-small text-muted">Every login account, including ones without a staff record.</p>
    </div>
</div>

<table class="user-tbl">
    <thead>
        <tr><th>Name</th><th>Email</th><th>Roles</th><th>Status</th><th style="text-align:right">Actions</th></tr>
    </thead>
    <tbody>
        @foreach($users as $user)
            <tr>
                <td>
                    {{ $user->name }}
                    @php $hasStaff = \App\Models\Staff::where('user_id',$user->id)->exists(); @endphp
                    @unless($hasStaff)<span class="badge-nostaff" title="No linked staff record">no staff record</span>@endunless
                </td>
                <td>{{ $user->email }}</td>
                <td>
                    @forelse($user->roles as $r)<span class="badge-role">{{ $r->label }}</span>@empty<span class="text-muted">{{ ucfirst($user->role) }}</span>@endforelse
                </td>
                <td>@if($user->is_active)<span class="badge-on">Active</span>@else<span class="badge-off">Blocked</span>@endif</td>
                <td style="text-align:right">
                    <a href="{{ route('users.edit', $user) }}" class="btn btn-outline btn-sm"><i class="fa-solid fa-key"></i> Manage</a>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
@endsection
