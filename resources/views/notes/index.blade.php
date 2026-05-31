{{-- FILE: resources/views/notes/index.blade.php --}}
@extends('layouts.app')
@section('title','Notes')
@section('page-title','Notes')
@section('content')

<div class="page-header">
    <div class="page-header-left"><h1>Notes</h1></div>
    <a href="{{ route('notes.create') }}" class="btn btn-primary"><i class="fa-solid fa-plus me-1"></i>Add Note</a>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="card">
    <div class="table-wrap">
        <table class="sc-table">
            <thead>
                <tr>
                    <th style="width:5%">#</th>
                    <th>Title</th>
                    <th>Note</th>
                    <th style="width:12%">By</th>
                    <th style="width:12%">Date</th>
                    <th style="width:7%">Status</th>
                    <th style="width:13%"></th>
                </tr>
            </thead>
            <tbody>
            @forelse($notes as $note)
            <tr>
                <td class="mono">{{ $note->id }}</td>
                <td class="fw-600">{{ $note->title }}</td>
                <td style="max-width:300px;font-size:12px;color:var(--sc-muted)">{{ Str::limit(strip_tags($note->body), 120) }}</td>
                <td style="font-size:12px">{{ $note->author?->name ?? '—' }}</td>
                <td style="font-size:12px">{{ $note->created_at->format('d M Y') }}</td>
                <td>
                    @if($note->is_locked)
                        <span class="badge badge-red"><i class="fa-solid fa-lock me-1"></i>Locked</span>
                    @else
                        <span class="badge badge-green">Open</span>
                    @endif
                </td>
                <td>
                    <div class="d-flex gap-1">
                        {{-- Lock / Unlock --}}
                        <form method="POST" action="{{ route('notes.toggle-lock', $note) }}">
                            @csrf @method('PATCH')
                            <button class="btn btn-sm btn-outline" title="{{ $note->is_locked ? 'Unlock' : 'Lock' }}">
                                <i class="fa-solid {{ $note->is_locked ? 'fa-lock-open' : 'fa-lock' }}"></i>
                            </button>
                        </form>
                        {{-- Edit --}}
                        @unless($note->is_locked)
                        <a href="{{ route('notes.edit', $note) }}" class="btn btn-sm btn-outline" title="Edit">
                            <i class="fa-solid fa-pen"></i>
                        </a>
                        {{-- Delete --}}
                        <form method="POST" action="{{ route('notes.destroy', $note) }}"
                              onsubmit="return confirm('Delete this note?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline" style="color:#c00" title="Delete">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </form>
                        @endunless
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="7" class="text-center text-muted" style="padding:40px">No notes yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $notes->links() }}</div>
</div>
@endsection
