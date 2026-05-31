@extends('layouts.app')
@section('title','New Topic')
@section('page-title','New Topic')

@section('content')
<div class="page-header">
    <div class="page-header-left">
        <a href="{{ route('forum.show', $forum) }}" class="text-small" style="text-decoration:none"><i class="fa-solid fa-arrow-left"></i> {{ $forum->name }}</a>
        <h1>New Topic</h1>
    </div>
</div>

<div style="background:var(--sc-surface);border:1px solid var(--sc-border);border-radius:10px;padding:18px;max-width:100%">
    <form method="POST" action="{{ route('forum.topic.store', $forum) }}">
        @csrf
        <div class="form-group">
            <label>Title</label>
            <input type="text" name="title" required minlength="3" maxlength="200"
                   placeholder="Short, specific subject"
                   value="{{ old('title') }}" class="form-control">
        </div>
        <div class="form-group" style="margin-top:14px">
            <label>Body</label>
            <textarea name="body" required minlength="2" maxlength="20000" rows="10" class="form-control"
                      placeholder="Write your post…">{{ old('body') }}</textarea>
        </div>
        <div class="form-group" style="margin-top:14px">
            <label style="display:flex;align-items:center;gap:8px;font-weight:normal">
                <input type="checkbox" name="subscribe" value="1" checked> Subscribe to replies
            </label>
            @if(!$forum->is_announcements && (auth()->user()->can('forum.admin') || auth()->user()->can('forum.moderate')))
            <label style="display:flex;align-items:center;gap:8px;font-weight:normal;margin-top:6px">
                <input type="checkbox" name="is_public" value="1"> Also publish on the public /announcements page
            </label>
            @endif
            @if($forum->is_announcements)
            <div class="alert alert-info" style="margin-top:8px">
                <i class="fa-solid fa-bullhorn"></i> <div>This is an announcements forum — posts are automatically published on the public /announcements page.</div>
            </div>
            @endif
        </div>
        <div style="margin-top:16px;text-align:right">
            <a href="{{ route('forum.show', $forum) }}" class="btn btn-outline">Cancel</a>
            <button class="btn btn-primary" type="submit"><i class="fa-solid fa-paper-plane"></i> Post Topic</button>
        </div>
    </form>
</div>
@endsection
