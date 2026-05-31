@extends('layouts.app')
@section('title','Forum Admin')
@section('page-title','Forum Admin')

@push('styles')
<style>
    .cat-card  { background:var(--sc-surface); border:1px solid var(--sc-border); border-radius:10px; margin-bottom:16px; overflow:hidden; }
    .cat-head  { padding:10px 14px; background:var(--sc-surface-2,#f6f8fb); border-bottom:1px solid var(--sc-border); display:flex; justify-content:space-between; align-items:center; }
    .cat-head h3 { margin:0; font-size:14px; }
    .forum-li  { display:flex; justify-content:space-between; align-items:center; padding:10px 14px; border-top:1px solid var(--sc-border); }
    .forum-li:first-child { border-top:0; }
    .forum-li .fl-name { font-weight:600; font-size:13.5px; }
    .forum-li .fl-meta { font-size:11.5px; color:var(--sc-muted); }
    .form-inline-row { display:flex; gap:8px; align-items:center; margin-top:8px; }
    .form-inline-row input { padding:6px 8px; border:1px solid var(--sc-border); border-radius:6px; font-size:13px; }
</style>
@endpush

@section('content')
<div class="page-header">
    <div class="page-header-left">
        <a href="{{ route('forum.index') }}" class="text-small" style="text-decoration:none"><i class="fa-solid fa-arrow-left"></i> Forum</a>
        <h1>Forum Admin</h1>
        <p class="text-small text-muted">Categories, forums and per-forum role permissions.</p>
    </div>
</div>

<div style="background:var(--sc-surface);border:1px solid var(--sc-border);border-radius:10px;padding:14px 16px;margin-bottom:18px">
    <strong>Add Category</strong>
    <form method="POST" action="{{ route('forum.admin.categories.store') }}" class="form-inline-row">
        @csrf
        <input type="text" name="name" placeholder="Category name" required maxlength="120" style="flex:1">
        <input type="text" name="description" placeholder="Description (optional)" maxlength="500" style="flex:2">
        <input type="number" name="sort_order" value="100" style="width:80px" title="Sort order">
        <button class="btn btn-primary"><i class="fa-solid fa-plus"></i> Add</button>
    </form>
</div>

@forelse($categories as $cat)
<div class="cat-card">
    <div class="cat-head">
        <h3>{{ $cat->name }} <span class="text-muted" style="font-size:11px;font-weight:normal">({{ $cat->forums->count() }} forums)</span></h3>
        <div style="display:flex;gap:6px">
            <a href="{{ route('forum.admin.forums.create', $cat) }}" class="btn btn-outline btn-sm"><i class="fa-solid fa-plus"></i> Add Forum</a>
            <form method="POST" action="{{ route('forum.admin.categories.destroy', $cat) }}" style="display:inline" onsubmit="return confirm('Delete category {{ $cat->name }} and all its forums?')">
                @csrf @method('DELETE')
                <button class="btn btn-outline btn-sm" style="color:#b91c1c"><i class="fa-solid fa-trash"></i></button>
            </form>
        </div>
    </div>
    @foreach($cat->forums as $f)
        <div class="forum-li">
            <div>
                <div class="fl-name">
                    <i class="fa-solid {{ $f->icon ?: 'fa-comments' }}" style="margin-right:6px;color:var(--sc-teal)"></i>
                    {{ $f->name }}
                    @if($f->is_announcements)<span style="color:#a16207;font-size:10px;font-weight:700;background:#fef3c7;border-radius:10px;padding:1px 6px;margin-left:6px">PUBLIC</span>@endif
                </div>
                <div class="fl-meta">
                    {{ $f->topics_count }} topics · {{ $f->posts_count }} posts ·
                    view: {{ implode(',', $f->allowed_roles['view'] ?? []) ?: '—' }} ·
                    post: {{ implode(',', $f->allowed_roles['post'] ?? []) ?: '—' }}
                </div>
            </div>
            <div style="display:flex;gap:6px">
                <a href="{{ route('forum.admin.forums.edit', $f) }}" class="btn btn-outline btn-sm">Edit</a>
                <form method="POST" action="{{ route('forum.admin.forums.destroy', $f) }}" onsubmit="return confirm('Delete forum {{ $f->name }} and ALL its topics/posts?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-outline btn-sm" style="color:#b91c1c"><i class="fa-solid fa-trash"></i></button>
                </form>
            </div>
        </div>
    @endforeach
</div>
@empty
<div style="text-align:center;color:var(--sc-muted);padding:30px">No categories yet — add one above.</div>
@endforelse
@endsection
