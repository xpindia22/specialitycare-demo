@extends('layouts.app')
@section('title','Locked Forum')
@section('page-title','Locked Forum')

@section('content')
<div style="max-width:440px;margin:40px auto;background:var(--sc-surface);border:1px solid var(--sc-border);border-radius:12px;padding:28px;text-align:center">
    <div style="font-size:40px;color:var(--sc-amber,#b45309);margin-bottom:10px"><i class="fa-solid fa-lock"></i></div>
    <h1 style="font-size:20px;margin-bottom:4px">{{ $forum->name }}</h1>
    <p class="text-small text-muted" style="margin-bottom:20px">This is a private forum. Enter your account password to unlock it for this session.</p>

    <form method="POST" action="{{ route('forum.unlock.submit', $forum) }}">
        @csrf
        <input type="password" name="password" required autofocus autocomplete="current-password"
               class="form-control" placeholder="Your account password"
               style="text-align:center">
        @error('password')
            <div class="text-small" style="color:var(--sc-red,#dc2626);margin-top:8px">{{ $message }}</div>
        @enderror
        <div style="margin-top:18px;display:flex;gap:8px;justify-content:center">
            <a href="{{ route('forum.index') }}" class="btn btn-outline">Cancel</a>
            <button class="btn btn-primary" type="submit"><i class="fa-solid fa-unlock"></i> Unlock</button>
        </div>
    </form>
</div>
@endsection
