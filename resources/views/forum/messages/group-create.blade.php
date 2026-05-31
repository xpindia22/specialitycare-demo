@extends('layouts.app')
@section('title','New Group')
@section('page-title','New Group Conversation')

@push('styles')
<style>
    .user-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(180px,1fr)); gap:8px; margin-top:6px; }
    .user-grid label { background:var(--sc-surface-2,#f6f8fb); border:1px solid var(--sc-border); border-radius:6px; padding:8px 10px; font-size:12.5px; font-weight:normal; cursor:pointer; display:flex; align-items:center; gap:8px; }
</style>
@endpush

@section('content')
<div class="page-header">
    <div class="page-header-left">
        <a href="{{ route('messages.inbox') }}" class="text-small" style="text-decoration:none"><i class="fa-solid fa-arrow-left"></i> Inbox</a>
        <h1>New Group Conversation</h1>
    </div>
</div>

<form method="POST" action="{{ route('messages.group.store') }}"
      style="background:var(--sc-surface);border:1px solid var(--sc-border);border-radius:10px;padding:18px;max-width:100%">
    @csrf
    <div class="form-group">
        <label>Subject</label>
        <input type="text" name="subject" required maxlength="200" class="form-control" placeholder="What's this group about?" value="{{ old('subject') }}">
    </div>
    <div class="form-group" style="margin-top:14px">
        <label>Participants</label>
        <div class="user-grid">
            @foreach($users as $u)
                <label>
                    <input type="checkbox" name="participant_ids[]" value="{{ $u->id }}">
                    {{ $u->name }}
                </label>
            @endforeach
        </div>
    </div>
    <div class="form-group" style="margin-top:14px">
        <label>First message</label>
        <textarea name="body" required minlength="1" maxlength="10000" rows="6" class="form-control" placeholder="Start the conversation…">{{ old('body') }}</textarea>
    </div>
    <div style="margin-top:16px;text-align:right">
        <a href="{{ route('messages.inbox') }}" class="btn btn-outline">Cancel</a>
        <button class="btn btn-primary"><i class="fa-solid fa-paper-plane"></i> Create Group</button>
    </div>
</form>
@endsection
