{{-- FILE: resources/views/doctors/index.blade.php --}}
@extends('layouts.app')
@section('title','Doctors')
@section('page-title','Doctors')
@section('content')
<div class="page-header"><div class="page-header-left"><h1>Doctors</h1></div><a href="{{ route('doctors.create') }}" class="btn btn-primary"><i class="fa-solid fa-user-plus"></i> Register Doctor</a></div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
<div class="grid grid-3">
    @forelse($doctors as $doc)
    <div class="card">
        <div style="padding:20px;display:flex;gap:14px;align-items:flex-start">
            <div style="width:56px;height:56px;border-radius:50%;background:linear-gradient(135deg,var(--sc-teal),var(--sc-navy-light));display:flex;align-items:center;justify-content:center;color:#fff;font-size:20px;font-weight:700;flex-shrink:0;{{ $doc->user && !$doc->user->is_active ? 'filter:grayscale(1);opacity:.6' : '' }}">
                @if($doc->photo)<img src="{{ asset('storage/'.$doc->photo) }}" style="width:100%;height:100%;object-fit:cover;border-radius:50%">@else{{ strtoupper(substr($doc->first_name,0,1)) }}@endif
            </div>
            <div style="flex:1">
                <div style="font-size:15px;font-weight:700;color:var(--sc-navy);display:flex;align-items:center;gap:8px">
                    {{ $doc->full_name }}
                    @if($doc->user && !$doc->user->is_active)
                        <span class="badge badge-red" title="Login blocked">Blocked</span>
                    @endif
                </div>
                <div style="font-size:12px;color:var(--sc-teal);font-weight:600">{{ $doc->specialization }}</div>
                <div style="font-size:11.5px;color:var(--sc-muted);margin-top:4px">{{ $doc->qualification }}</div>
                @if($doc->department)<div style="font-size:11.5px;color:var(--sc-muted)">{{ $doc->department->name }}</div>@endif
                <div style="font-size:12px;margin-top:6px;color:var(--sc-navy)">₹{{ number_format($doc->consultation_fee) }} consultation</div>
            </div>
        </div>
        <div class="card-footer" style="display:flex;gap:8px;flex-wrap:wrap">
            <a href="{{ route('doctors.show', $doc) }}" class="btn btn-sm btn-outline" style="flex:1;justify-content:center">Profile</a>
            <a href="{{ route('doctors.edit', $doc) }}" class="btn btn-sm btn-outline"><i class="fa-solid fa-pen"></i></a>
            <a href="{{ route('appointments.create', ['doctor_id' => $doc->id]) }}" class="btn btn-sm btn-primary"><i class="fa-solid fa-calendar-plus"></i></a>
            @if($doc->user)
                @if($doc->user->id === auth()->id())
                    <button type="button" class="btn btn-sm btn-outline" disabled title="This is your own login — you cannot block yourself">
                        <i class="fa-solid fa-user-shield"></i>
                    </button>
                @else
                    <form method="POST" action="{{ route('users.toggle-active', $doc->user) }}" style="display:inline-block" onsubmit="return confirm('{{ $doc->user->is_active ? 'Block' : 'Unblock' }} login for {{ addslashes($doc->full_name) }}?')">
                        @csrf
                        <button type="submit" class="btn btn-sm {{ $doc->user->is_active ? 'btn-outline' : 'btn-primary' }}" title="{{ $doc->user->is_active ? 'Block login' : 'Unblock login' }}">
                            <i class="fa-solid {{ $doc->user->is_active ? 'fa-ban' : 'fa-unlock' }}"></i>
                        </button>
                    </form>
                @endif
            @else
                <button type="button" class="btn btn-sm btn-outline" disabled title="No login account linked to this doctor">
                    <i class="fa-solid fa-user-slash"></i>
                </button>
            @endif
        </div>
    </div>
    @empty
    <div class="card" style="grid-column:1/-1"><div class="card-body text-center text-muted" style="padding:40px">No doctors registered yet.</div></div>
    @endforelse
</div>
<div class="mt-3">{{ $doctors->links() }}</div>
@endsection
