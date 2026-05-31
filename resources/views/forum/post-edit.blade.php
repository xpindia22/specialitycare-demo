@extends('layouts.app')
@section('title','Edit Post')
@section('page-title','Edit Post')

@section('content')
<div class="page-header">
    <div class="page-header-left">
        <a href="{{ route('forum.topic', [$forum, $topic]) }}" class="text-small" style="text-decoration:none"><i class="fa-solid fa-arrow-left"></i> {{ $topic->title }}</a>
        <h1>Edit Post</h1>
    </div>
</div>

<div style="background:var(--sc-surface);border:1px solid var(--sc-border);border-radius:10px;padding:18px;max-width:100%">
    <form method="POST" action="{{ route('forum.post.update', [$forum, $topic, $post]) }}">
        @csrf @method('PUT')
        <div class="form-group">
            <label>Body</label>
            <textarea name="body" required minlength="2" maxlength="20000" rows="12" class="form-control">{{ old('body', $post->body) }}</textarea>
        </div>
        <div style="margin-top:16px;text-align:right">
            <a href="{{ route('forum.topic', [$forum, $topic]) }}" class="btn btn-outline">Cancel</a>
            <button class="btn btn-primary" type="submit"><i class="fa-solid fa-save"></i> Save</button>
        </div>
    </form>
</div>
@endsection
