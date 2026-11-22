{{-- FILE: resources/views/departments/index.blade.php --}}
@extends('layouts.app')
@section('title','Departments')
@section('page-title','Departments')
@section('content')

<div class="page-header">
    <div class="page-header-left">
        <h1>Departments &amp; Specialities</h1>
        <div class="breadcrumb">Shown in online booking, at reception and on the queue boards</div>
    </div>
    <button type="button" class="btn btn-primary" onclick="openDept(null)"><i class="fa-solid fa-plus"></i> Add Department</button>
</div>

<div class="card">
    <div class="card-header">
        <i class="fa-solid fa-hospital"></i> Departments
        <div class="card-actions">
            <span class="badge badge-navy">{{ $departments->where('is_active', true)->count() }} active</span>
        </div>
    </div>
    <div class="table-wrap">
        <table class="sc-table">
            <thead>
                <tr>
                    <th style="width:60px">Order</th>
                    <th>Department</th>
                    <th>Doctors</th>
                    <th>Room</th>
                    <th>Token Series</th>
                    <th>Online Booking</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @foreach($departments as $d)
            @php $docs = $doctorsByDept[$d->id]; @endphp
            <tr style="{{ $d->is_active ? '' : 'opacity:.55' }}">
                <td class="mono">{{ $d->sort_order }}</td>
                <td>
                    <div class="fw-600" style="display:flex;align-items:center;gap:8px">
                        <span style="width:28px;height:28px;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;background:{{ $d->color ?: '#64748b' }}1a;color:{{ $d->color ?: '#64748b' }}">
                            <i class="fa-solid {{ $d->icon ?: 'fa-hospital' }}"></i>
                        </span>
                        {{ $d->name }}
                    </div>
                    <div class="text-small text-muted mono">{{ $d->code }}</div>
                </td>
                <td style="font-size:12px">
                    @forelse($docs as $doc)
                        <div>Dr. {{ $doc->first_name }} {{ $doc->last_name }}{{ $doc->department_id != $d->id ? ' (also)' : '' }}</div>
                    @empty
                        <span class="text-muted">None yet</span>
                    @endforelse
                </td>
                <td>{{ $d->room ?: '—' }}</td>
                <td>
                    @if($d->token_prefix)
                        <span class="badge badge-teal mono">{{ $d->token_prefix }}-001…</span>
                    @else
                        <span class="text-small text-muted">Shared T-series</span>
                    @endif
                </td>
                <td>
                    @if(!$d->show_online)<span class="badge badge-grey">Hidden</span>
                    @elseif($docs->where('show_online', true)->isEmpty())<span class="text-small text-muted">No doctors</span>
                    @else<span class="badge badge-green">Shown</span>@endif
                </td>
                <td><span class="badge {{ $d->is_active ? 'badge-green' : 'badge-grey' }}">{{ $d->is_active ? 'Active' : 'Inactive' }}</span></td>
                <td style="white-space:nowrap">
                    <button type="button" class="btn btn-sm btn-outline" onclick='openDept(@json($d))'><i class="fa-solid fa-pen"></i> Edit</button>
                    <form method="POST" action="{{ route('departments.toggle', $d) }}" style="display:inline">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline" title="{{ $d->is_active ? 'Deactivate' : 'Activate' }}">
                            <i class="fa-solid {{ $d->is_active ? 'fa-eye-slash' : 'fa-eye' }}"></i>
                        </button>
                    </form>
                    <a href="{{ route('opd.public-board', ['dept' => $d->code]) }}" target="_blank" class="btn btn-sm btn-outline" title="TV queue board for this department"><i class="fa-solid fa-display"></i></a>
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <div class="card-footer text-small text-muted">
        <i class="fa-solid fa-info-circle"></i>
        Assign doctors to departments on each doctor's Edit page (one main department plus any extra ones).
        Give a department a <b>token prefix</b> (e.g. C for Cardiology) to number its OPD tokens separately: C-001, C-002…; without one it shares the T-series.
        Each department's TV board: the <i class="fa-solid fa-display"></i> button (link <span class="mono">/queue-board?dept=CODE</span>).
    </div>
</div>

{{-- Add / Edit modal --}}
<div id="dept-modal" class="modal-overlay">
    <div class="modal-box" style="max-width:560px">
        <div class="modal-header">
            <i class="fa-solid fa-hospital" style="color:var(--sc-teal)"></i>
            <h3 id="dept-modal-title">Add Department</h3>
            <button class="modal-close" type="button" onclick="closeDept()">&times;</button>
        </div>
        <form id="dept-form" method="POST" action="{{ route('departments.store') }}">
            @csrf
            <input type="hidden" name="_method" id="dept-method" value="POST">
            <input type="hidden" name="dept_id" id="dept-id" value="">
            @if($errors->any())
            <div class="alert alert-danger" style="margin:12px 16px 0"><i class="fa-solid fa-triangle-exclamation"></i> <div>Not saved: {{ implode(' ', $errors->all()) }}</div></div>
            @endif
            <div class="modal-body">
                <div class="form-row cols-2">
                    <div class="form-group"><label class="form-label">Name <span class="req">*</span></label><input type="text" name="name" id="f-name" class="form-control" required maxlength="100"></div>
                    <div class="form-group"><label class="form-label">Code <span class="req">*</span></label><input type="text" name="code" id="f-code" class="form-control mono" required maxlength="10" placeholder="e.g. CARD"></div>
                    <div class="form-group"><label class="form-label">Icon</label>
                        <div style="display:flex;gap:6px;align-items:center">
                            <span id="f-icon-preview" style="width:34px;height:34px;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;background:var(--sc-bg)"><i class="fa-solid fa-hospital"></i></span>
                            <input type="text" name="icon" id="f-icon" class="form-control mono" maxlength="40" placeholder="fa-heart-pulse" oninput="previewIcon()">
                        </div>
                    </div>
                    <div class="form-group"><label class="form-label">Colour</label><input type="color" name="color" id="f-color" class="form-control" value="#0f766e" style="height:38px;padding:3px" oninput="previewIcon()"></div>
                    <div class="form-group"><label class="form-label">Room / Area</label><input type="text" name="room" id="f-room" class="form-control" maxlength="60" placeholder="e.g. Room 3, 1st floor"></div>
                    <div class="form-group"><label class="form-label">Token Prefix</label><input type="text" name="token_prefix" id="f-prefix" class="form-control mono" maxlength="4" placeholder="blank = shared T-series"></div>
                    <div class="form-group"><label class="form-label">Display Order</label><input type="number" name="sort_order" id="f-order" class="form-control" min="0" max="9999" value="100"></div>
                    <div class="form-group" style="display:flex;align-items:flex-end">
                        <label style="display:flex;gap:8px;align-items:center;cursor:pointer;font-weight:normal;margin-bottom:8px">
                            <input type="checkbox" name="show_online" id="f-online" value="1" checked> Show in online booking
                        </label>
                    </div>
                </div>
                <div class="form-group"><label class="form-label">Description</label><textarea name="description" id="f-desc" class="form-control" rows="2" maxlength="1000" placeholder="Shown to patients when booking (optional)"></textarea></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeDept()">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Save</button>
            </div>
        </form>
    </div>
</div>

<script>
function openDept(d) {
    const f = document.getElementById('dept-form');
    document.getElementById('dept-modal-title').textContent = d?.id ? 'Edit ' + d.name : 'Add Department';
    f.action = d?.id ? '{{ url('/departments') }}/' + d.id : '{{ route('departments.store') }}';
    document.getElementById('dept-method').value = d?.id ? 'PUT' : 'POST';
    document.getElementById('dept-id').value = d?.id ?? '';
    document.getElementById('f-name').value   = d?.name ?? '';
    document.getElementById('f-code').value   = d?.code ?? '';
    document.getElementById('f-icon').value   = d?.icon ?? '';
    document.getElementById('f-color').value  = d?.color || '#0f766e';
    document.getElementById('f-room').value   = d?.room ?? '';
    document.getElementById('f-prefix').value = d?.token_prefix ?? '';
    document.getElementById('f-order').value  = d?.sort_order ?? 100;
    document.getElementById('f-online').checked = d ? !!d.show_online : true;
    document.getElementById('f-desc').value   = d?.description ?? '';
    previewIcon();
    document.getElementById('dept-modal').classList.add('open');
}
@if($errors->any())
// a save was refused: reopen the form with what was typed
document.addEventListener('DOMContentLoaded', () => openDept({
    id: @json(old('dept_id')) || null, name: @json(old('name')), code: @json(old('code')), icon: @json(old('icon')),
    color: @json(old('color')), room: @json(old('room')), token_prefix: @json(old('token_prefix')),
    sort_order: @json(old('sort_order')), show_online: @json((bool) old('show_online')), description: @json(old('description')),
}));
@endif
function closeDept() { document.getElementById('dept-modal').classList.remove('open'); }
function previewIcon() {
    const icon = document.getElementById('f-icon').value.trim() || 'fa-hospital';
    const color = document.getElementById('f-color').value;
    const p = document.getElementById('f-icon-preview');
    p.innerHTML = `<i class="fa-solid ${icon.replace(/[^a-z0-9-]/g, '')}"></i>`;
    p.style.color = color; p.style.background = color + '1a';
}
</script>
@endsection
