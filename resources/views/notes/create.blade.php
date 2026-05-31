{{-- FILE: resources/views/notes/create.blade.php --}}
@extends('layouts.app')
@section('title','Add Note')
@section('page-title','Add Note')
@section('content')

<div class="page-header">
    <div class="page-header-left"><h1>Add Note</h1></div>
    <a href="{{ route('notes.index') }}" class="btn btn-outline">Back</a>
</div>

<div class="card" style="max-width:720px">
    <div class="card-body">
        <form method="POST" action="{{ route('notes.store') }}" id="note-create-form">
            @csrf
            <div class="form-group">
                <label class="form-label">Title <span class="text-danger">*</span></label>
                <input type="text" name="title" class="form-control @error('title') is-invalid @enderror"
                       value="{{ old('title') }}" required>
                @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="form-group mt-3">
                <label class="form-label">Note</label>
                <input type="hidden" name="body" id="body-input">
                <div id="note-body-quill" style="min-height:220px;background:#fff"></div>
                @error('body')<div class="invalid-feedback" style="display:block">{{ $message }}</div>@enderror
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary">Save Note</button>
                <a href="{{ route('notes.index') }}" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.snow.css">
<style>
#note-body-quill { border:1px solid var(--sc-border);border-radius:8px;overflow:hidden; }
#note-body-quill .ql-toolbar { border:none;border-bottom:1px solid var(--sc-border);background:var(--sc-bg);padding:5px 10px; }
#note-body-quill .ql-container { border:none;font-family:'DM Sans',sans-serif;font-size:13px;color:var(--sc-navy); }
#note-body-quill .ql-editor { padding:10px 12px; }
#note-body-quill .ql-editor.ql-blank::before { color:#94a3b8;font-style:normal; }
.ql-snow .ql-stroke { stroke:var(--sc-navy) }
.ql-snow .ql-fill { fill:var(--sc-navy) }
.ql-snow.ql-toolbar button:hover .ql-stroke { stroke:var(--sc-teal) }
.ql-snow.ql-toolbar button.ql-active .ql-stroke { stroke:var(--sc-teal) }
.ql-snow.ql-toolbar button:hover .ql-fill { fill:var(--sc-teal) }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.min.js"></script>
<script>
(function () {
    const toolbar = [
        ['bold', 'italic', 'underline'],
        [{ list: 'ordered' }, { list: 'bullet' }],
        ['clean']
    ];
    const quill = new Quill('#note-body-quill', {
        theme: 'snow',
        placeholder: 'Write your note…',
        modules: { toolbar }
    });

    @if(old('body'))
    quill.root.innerHTML = {!! json_encode(old('body')) !!};
    @endif

    document.getElementById('note-create-form').addEventListener('submit', function () {
        document.getElementById('body-input').value = quill.root.innerHTML;
    });
})();
</script>
@endpush
