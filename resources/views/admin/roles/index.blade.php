@extends('layouts.app')
@section('title','Roles &amp; Permissions')
@section('page-title','Roles &amp; Permissions')
@section('content')

<div class="page-header">
    <div class="page-header-left">
        <h1>Roles &amp; Permissions</h1>
        <div class="breadcrumb">{{ $roles->count() }} roles · {{ collect($catalog)->flatMap(fn($g)=>array_keys($g['perms']??[]))->count() }} permissions</div>
    </div>
    <div>
        <a href="{{ route('admin.roles.create') }}" class="btn btn-primary"><i class="fa-solid fa-plus"></i> New Role</a>
    </div>
</div>

<div class="card">
    <div class="card-header"><i class="fa-solid fa-shield-halved"></i> All Roles</div>
    <div class="table-responsive">
        <table class="sc-table table" style="margin-bottom:0">
            <thead>
                <tr>
                    <th>Role</th>
                    <th>Key</th>
                    <th class="text-right">Permissions</th>
                    <th class="text-right">Users</th>
                    <th>Type</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($roles as $r)
                <tr>
                    <td>
                        <div class="fw-600">{{ $r->label }}</div>
                    </td>
                    <td><span class="mono text-small text-muted">{{ $r->name }}</span></td>
                    <td class="text-right"><span class="badge badge-teal">{{ count($r->permissions ?? []) }}</span></td>
                    <td class="text-right">{{ $r->users_count }}</td>
                    <td>
                        @if($r->is_system)
                            <span class="badge badge-navy"><i class="fa-solid fa-lock"></i> System</span>
                        @else
                            <span class="badge badge-amber">Custom</span>
                        @endif
                    </td>
                    <td class="text-right">
                        <a href="{{ route('admin.roles.edit', $r) }}" class="btn btn-sm btn-outline"><i class="fa-solid fa-pen-to-square"></i> Edit</a>
                        @if(!$r->is_system && $r->users_count == 0)
                            <form method="POST" action="{{ route('admin.roles.destroy', $r) }}" style="display:inline" onsubmit="return confirm('Delete role {{ $r->label }}?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline" style="color:var(--sc-red);border-color:#fecaca"><i class="fa-solid fa-trash-can"></i></button>
                            </form>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@endsection
