@extends('layouts.app')
@section('title', $role->exists ? 'Edit Role' : 'New Role')
@section('page-title', $role->exists ? 'Edit Role' : 'New Role')
@section('content')

@php
    $isAdmin     = $role->name === 'admin';
    $isSystem    = $role->is_system;
    $rolePerms   = array_flip($role->permissions ?? []);
@endphp

<div class="page-header">
    <div class="page-header-left">
        <h1>{{ $role->exists ? 'Edit '.$role->label : 'New Role' }}</h1>
        <div class="breadcrumb"><a href="{{ route('admin.roles.index') }}">Roles &amp; Permissions</a> · {{ $role->exists ? 'Edit' : 'Create' }}</div>
    </div>
    <div>
        <a href="{{ route('admin.roles.index') }}" class="btn btn-outline btn-sm"><i class="fa-solid fa-arrow-left"></i> Back</a>
    </div>
</div>

@if($isAdmin)
<div class="alert alert-warning">
    <i class="fa-solid fa-lock"></i>
    <div>The <strong>Administrator</strong> role always has every permission and cannot be modified — this protects you from accidentally locking yourself out.</div>
</div>
@endif

<form method="POST" action="{{ $role->exists ? route('admin.roles.update', $role) : route('admin.roles.store') }}">
    @csrf
    @if($role->exists)@method('PUT')@endif

    <div class="card">
        <div class="card-header"><i class="fa-solid fa-id-card"></i> Role Details</div>
        <div class="card-body">
            <div class="grid grid-3" style="gap:14px">
                <div class="form-group">
                    <label class="form-label">Display Label <span class="req">*</span></label>
                    <input type="text" name="label" required maxlength="80"
                           class="form-control" value="{{ old('label', $role->label) }}"
                           placeholder="e.g. Pharmacist">
                </div>
                <div class="form-group">
                    <label class="form-label">Internal Key {{ $role->exists ? '' : '(lowercase, no spaces)' }}</label>
                    @if($isAdmin || $isSystem)
                        <input type="text" class="form-control" value="{{ $role->name }}" disabled>
                        <div class="text-small text-muted" style="margin-top:4px">System roles can't be renamed.</div>
                    @else
                        <input type="text" name="name" maxlength="40" pattern="^[a-z][a-z0-9_]*$"
                               class="form-control" value="{{ old('name', $role->name) }}"
                               placeholder="e.g. clinic_lead">
                        <div class="text-small text-muted" style="margin-top:4px">Lowercase letters, digits, and underscores. Used internally.</div>
                    @endif
                </div>
                <div class="form-group">
                    <label class="form-label">Sort Order</label>
                    <input type="number" name="sort_order" min="1" max="9999"
                           class="form-control" value="{{ old('sort_order', $role->sort_order ?? 100) }}">
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <i class="fa-solid fa-grip-vertical"></i> Permissions
            <div class="card-actions">
                @unless($isAdmin)
                <button type="button" class="btn btn-sm btn-outline" onclick="togglePerms(true)"><i class="fa-solid fa-square-check"></i> All</button>
                <button type="button" class="btn btn-sm btn-outline" onclick="togglePerms(false)"><i class="fa-regular fa-square"></i> None</button>
                @endunless
            </div>
        </div>
        <div class="card-body">
            <div class="grid grid-2" style="gap:14px">
                @foreach($catalog as $section => $g)
                <div style="border:1px solid var(--sc-border);border-radius:8px;padding:14px">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;padding-bottom:8px;border-bottom:1px solid var(--sc-border)">
                        <div class="fw-600" style="font-size:13px;color:var(--sc-navy)">
                            <i class="fa-solid {{ $g['icon'] ?? 'fa-folder' }}" style="color:var(--sc-teal);margin-right:6px"></i>
                            {{ $g['label'] }}
                        </div>
                        @unless($isAdmin)
                        <label style="font-size:11px;color:var(--sc-muted);cursor:pointer">
                            <input type="checkbox" class="perm-section-toggle" data-section="{{ $section }}" onchange="toggleSection(this)">
                            select all
                        </label>
                        @endunless
                    </div>
                    @foreach($g['perms'] ?? [] as $key => $label)
                    <label class="perm-row" style="display:flex;align-items:flex-start;gap:8px;padding:6px 4px;border-radius:4px;cursor:pointer">
                        <input type="checkbox" name="permissions[]" value="{{ $key }}" class="perm-cb" data-section="{{ $section }}"
                               {{ isset($rolePerms[$key]) ? 'checked' : '' }}
                               {{ $isAdmin ? 'disabled checked' : '' }}>
                        <span style="flex:1">
                            <div style="font-size:13px;color:var(--sc-navy)">{{ $label }}</div>
                            <div class="mono text-small text-muted">{{ $key }}</div>
                        </span>
                    </label>
                    @endforeach
                </div>
                @endforeach
            </div>
        </div>
        <div style="padding:12px 16px;border-top:1px solid var(--sc-border);text-align:right">
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save Role</button>
        </div>
    </div>
</form>

@if($isAdmin)
{{-- For admin role, the disabled checkboxes don't submit. Send hidden inputs so the controller never sees an empty perms list. --}}
<form id="hidden-admin-perms"></form>
@endif

@push('scripts')
<script>
function togglePerms(state) {
    document.querySelectorAll('.perm-cb:not([disabled])').forEach(cb => cb.checked = state);
    document.querySelectorAll('.perm-section-toggle').forEach(cb => cb.checked = state);
}
function toggleSection(toggle) {
    const sec = toggle.dataset.section;
    document.querySelectorAll('.perm-cb[data-section="' + sec + '"]:not([disabled])').forEach(cb => cb.checked = toggle.checked);
}
// Sync section toggle states based on currently checked items
document.querySelectorAll('.perm-section-toggle').forEach(toggle => {
    const sec = toggle.dataset.section;
    const all = document.querySelectorAll('.perm-cb[data-section="' + sec + '"]');
    const checked = document.querySelectorAll('.perm-cb[data-section="' + sec + '"]:checked');
    toggle.checked = all.length > 0 && all.length === checked.length;
});
</script>
@endpush
@endsection
