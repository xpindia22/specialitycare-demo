@extends('layouts.app')
@section('title','Broadcast to staff')
@section('page-title','Broadcast to staff')

@section('content')
<div class="page-header">
    <div class="page-header-left">
        <h1><i class="fa-solid fa-bullhorn"></i> Broadcast to all staff</h1>
        <p class="text-small text-muted">Sends one message to every active staff member as a group conversation.</p>
    </div>
    <div class="page-header-right">
        <a href="{{ route('messages.inbox') }}" class="btn btn-outline"><i class="fa-solid fa-arrow-left"></i> Back</a>
    </div>
</div>

<div style="max-width:680px;background:var(--sc-surface);border:1px solid var(--sc-border);border-radius:10px;padding:20px">
    @if($errors->any())
        <div class="alert alert-danger" style="margin-bottom:14px">{{ $errors->first() }}</div>
    @endif
    <p class="text-small text-muted" style="margin-bottom:16px">
        This will reach <strong>{{ $count }}</strong> staff member(s). They'll receive it in a new group conversation (and the usual email/WhatsApp notification, if enabled).
    </p>
    <form method="POST" action="{{ route('messages.broadcast.store') }}">
        @csrf
        <div class="form-group" style="margin-bottom:14px">
            <label class="form-label">Title <span class="text-muted">(optional)</span></label>
            <input type="text" name="subject" maxlength="200" class="form-control" placeholder="e.g. Clinic closed Friday" value="{{ old('subject') }}">
        </div>
        <div class="form-group" style="margin-bottom:18px">
            <label class="form-label">Message</label>
            <textarea name="body" rows="5" maxlength="10000" required class="form-control" placeholder="Type your announcement…">{{ old('body') }}</textarea>
        </div>
        <button type="submit" class="btn btn-primary" onclick="return confirm('Send this broadcast to all {{ $count }} staff?')">
            <i class="fa-solid fa-paper-plane"></i> Send broadcast
        </button>
    </form>
</div>
@endsection
