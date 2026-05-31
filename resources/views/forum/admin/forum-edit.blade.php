@extends('layouts.app')
@section('title', $forum->exists ? 'Edit Forum' : 'New Forum')
@section('page-title', $forum->exists ? 'Edit Forum' : 'New Forum')

@push('styles')
<style>
    .role-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(140px,1fr)); gap:6px; margin-top:6px; }
    .role-grid label { background:var(--sc-surface-2,#f6f8fb); border:1px solid var(--sc-border); border-radius:6px; padding:6px 10px; font-size:12.5px; font-weight:normal; cursor:pointer; display:flex; align-items:center; gap:6px; }
    .role-grid input { margin:0; }
    .role-section { margin-top:14px; }
    .role-section > strong { font-size:13px; }
    .role-section > p { margin:0; font-size:11.5px; color:var(--sc-muted); }
</style>
@endpush

@section('content')
<div class="page-header">
    <div class="page-header-left">
        <a href="{{ route('forum.admin.index') }}" class="text-small" style="text-decoration:none"><i class="fa-solid fa-arrow-left"></i> Forum Admin</a>
        <h1>{{ $forum->exists ? 'Edit Forum' : 'New Forum in '.$category->name }}</h1>
    </div>
</div>

<form method="POST" action="{{ $forum->exists ? route('forum.admin.forums.update', $forum) : route('forum.admin.forums.store', $category) }}"
      style="background:var(--sc-surface);border:1px solid var(--sc-border);border-radius:10px;padding:18px;max-width:100%">
    @csrf
    @if($forum->exists) @method('PUT') @endif

    <div class="form-group">
        <label>Name</label>
        <input type="text" name="name" required maxlength="160" class="form-control" value="{{ old('name', $forum->name) }}">
    </div>
    <div class="form-group" style="margin-top:10px">
        <label>Description</label>
        <input type="text" name="description" maxlength="800" class="form-control" value="{{ old('description', $forum->description) }}">
    </div>
    <div class="form-group" style="margin-top:10px;display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px">
        <div>
            <label>Icon (FontAwesome class)</label>
            <input type="text" name="icon" maxlength="60" class="form-control" value="{{ old('icon', $forum->icon) }}" placeholder="fa-comments">
        </div>
        <div>
            <label>Sort order</label>
            <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', $forum->sort_order ?: 100) }}">
        </div>
        <div>
            <label>Public announcements forum</label>
            <label style="display:flex;align-items:center;gap:8px;font-weight:normal;margin-top:8px">
                <input type="checkbox" name="is_announcements" value="1" {{ old('is_announcements', $forum->is_announcements) ? 'checked' : '' }}>
                Yes — all topics here also show on /announcements
            </label>
        </div>
    </div>

    <div class="form-group" style="margin-top:14px;border:1px solid var(--sc-amber,#f5a623);border-radius:8px;padding:12px 14px;background:rgba(245,166,35,.06)">
        <label style="display:flex;align-items:center;gap:8px;font-weight:600">
            <input type="checkbox" name="is_private" value="1" {{ old('is_private', $forum->is_private) ? 'checked' : '' }}>
            <i class="fa-solid fa-lock" style="color:var(--sc-amber,#b45309)"></i> Private / personal forum
        </label>
        <p style="margin:6px 0 0;font-size:12px;color:var(--sc-muted)">
            Only <strong>you</strong> can see or open this forum — it's hidden from everyone else, including other admins.
            To open it you re-enter <strong>your own account password</strong> (the unlock lasts for your login session).
            Private forums can never be published to patients, and the role lists below are ignored while it's private.
        </p>
    </div>

    @php
        $rolesView     = $forum->allowed_roles['view']     ?? [];
        $rolesPost     = $forum->allowed_roles['post']     ?? [];
        $rolesReply    = $forum->allowed_roles['reply']    ?? [];
        $rolesModerate = $forum->allowed_roles['moderate'] ?? ['admin'];
    @endphp

    @foreach([
        ['view',     'Roles that can VIEW this forum',        $rolesView],
        ['post',     'Roles that can START new topics',       $rolesPost],
        ['reply',    'Roles that can REPLY in existing topics', $rolesReply],
        ['moderate', 'Roles that can MODERATE (pin/lock/delete)', $rolesModerate],
    ] as [$key, $label, $cur])
        <div class="role-section">
            <strong>{{ $label }}</strong>
            @if($key === 'moderate')
                <p>"admin" is always added even if you untick it — it's the safety net role.</p>
            @endif
            <div class="role-grid">
                @foreach($roleNames as $rname => $rlabel)
                    <label>
                        <input type="checkbox" name="roles_{{ $key }}[]" value="{{ $rname }}" {{ in_array($rname, $cur, true) ? 'checked' : '' }}>
                        {{ $rlabel }}
                    </label>
                @endforeach
            </div>
        </div>
    @endforeach

    <div style="margin-top:18px;text-align:right">
        <a href="{{ route('forum.admin.index') }}" class="btn btn-outline">Cancel</a>
        <button class="btn btn-primary"><i class="fa-solid fa-save"></i> {{ $forum->exists ? 'Save' : 'Create Forum' }}</button>
    </div>
</form>
@endsection
