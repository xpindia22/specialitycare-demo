@extends('layouts.app')
@section('title','New Message')
@section('page-title','New Message')

@section('content')
<div class="page-header">
    <div class="page-header-left">
        <a href="{{ route('messages.inbox') }}" class="text-small" style="text-decoration:none"><i class="fa-solid fa-arrow-left"></i> Inbox</a>
        <h1>New Message</h1>
    </div>
</div>

<form method="POST" action="{{ route('messages.compose.store') }}"
      style="background:var(--sc-surface);border:1px solid var(--sc-border);border-radius:10px;padding:18px;max-width:100%">
    @csrf
    @if(!empty($patientId))
        <input type="hidden" name="patient_id" value="{{ $patientId }}">
        <div style="margin-bottom:14px;padding:8px 12px;background:var(--sc-teal-glow);border-radius:8px;font-size:13px;color:var(--sc-text)">
            <i class="fa-solid fa-user-injured" style="color:var(--sc-teal)"></i> This conversation will be linked to the selected patient.
        </div>
    @endif
    <div class="form-group">
        <label>Send to</label>
        <select name="recipient_id" required class="form-control">
            <option value="">— Pick someone —</option>
            @foreach($recipients as $u)
                <option value="{{ $u->id }}" {{ $preselected == $u->id ? 'selected' : '' }}>{{ $u->name }} ({{ $u->email }})</option>
            @endforeach
        </select>
    </div>
    <div class="form-group" style="margin-top:14px">
        <label>Message</label>
        <textarea name="body" required minlength="1" maxlength="10000" rows="8" class="form-control"
                  placeholder="Write your message…">{{ old('body') }}</textarea>
    </div>
    <div style="margin-top:16px;text-align:right">
        <a href="{{ route('messages.inbox') }}" class="btn btn-outline">Cancel</a>
        <button class="btn btn-primary"><i class="fa-solid fa-paper-plane"></i> Send</button>
    </div>
</form>
@endsection
