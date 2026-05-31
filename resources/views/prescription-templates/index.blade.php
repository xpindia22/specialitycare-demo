{{-- FILE: resources/views/prescription-templates/index.blade.php --}}
@extends('layouts.app')
@section('title','Prescription Templates')
@section('page-title','Prescription Templates')
@section('content')

<div class="page-header">
    <div class="page-header-left">
        <h1>Prescription Templates</h1>
        <div class="breadcrumb">Save common prescription sets for quick reuse</div>
    </div>
    <button class="btn btn-primary" onclick="openAddModal()">
        <i class="fa-solid fa-plus"></i> New Template
    </button>
</div>

@if(session('success'))
<div class="alert alert-success" style="margin-bottom:16px">{{ session('success') }}</div>
@endif

@if($templates->isNotEmpty())
<div style="margin-bottom:12px;display:flex;align-items:center;gap:10px">
    <div style="position:relative;flex:1;max-width:420px">
        <i class="fa-solid fa-magnifying-glass" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--sc-muted);font-size:13px"></i>
        <input type="text" id="rxtpl-search" placeholder="Search templates by name or medicine…"
               class="form-control" style="padding-left:34px" oninput="filterRxTemplateRows(this.value)" autofocus autocomplete="off">
    </div>
    <span id="rxtpl-search-count" style="font-size:12px;color:var(--sc-muted)"></span>
</div>
@endif

<div class="card">
    <div class="card-header"><i class="fa-solid fa-layer-group"></i> Saved Templates ({{ $templates->count() }})</div>
    <div class="table-wrap">
        <table class="sc-table">
            <thead><tr><th style="width:22%">Template</th><th>Medicines</th><th style="width:10%">Created</th><th style="width:120px"></th></tr></thead>
            <tbody>
            @forelse($templates as $t)
            <tr style="vertical-align:top">
                <td>
                    <div class="fw-600" style="font-size:13px">{{ $t->name }}</div>
                    @if($t->doctor)<div style="font-size:11px;color:var(--sc-muted);margin-top:2px">{{ $t->doctor->full_name }}</div>@endif
                </td>
                <td>
                    <div style="font-size:12px;line-height:1.9">
                    @foreach($t->items as $item)
                    @php
                        $parts = [];
                        if (!empty($item['dosage']))    $parts[] = $item['dosage'];
                        if (!empty($item['frequency'])) $parts[] = $item['frequency'];
                        if (!empty($item['duration']))  $parts[] = '× ' . $item['duration'] . ' days';
                    @endphp
                        <div>
                            <span class="fw-600">{{ $item['medicine_name'] }}</span>
                            @if(count($parts))
                            <span style="color:var(--sc-muted)"> — {{ implode(' ', $parts) }}</span>
                            @endif
                            @if(!empty($item['instructions']))
                            <span style="color:var(--sc-muted);font-size:11px"> · {{ $item['instructions'] }}</span>
                            @endif
                        </div>
                    @endforeach
                    </div>
                </td>
                <td class="text-muted text-small">{{ $t->created_at->format('d M Y') }}</td>
                <td style="white-space:nowrap">
                    <button type="button" class="btn btn-sm btn-outline" onclick="openEditModal({{ $t->id }}, {{ json_encode($t->name) }}, {{ $t->doctor_id ?? 'null' }}, {{ json_encode($t->items) }})">
                        <i class="fa-solid fa-pencil"></i>
                    </button>
                    <form method="POST" action="{{ route('prescription-templates.destroy', $t) }}" style="display:inline" onsubmit="return confirm('Delete this template?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline"><i class="fa-solid fa-trash"></i></button>
                    </form>
                </td>
            </tr>
            @empty
            <tr><td colspan="4" style="padding:0">
                <x-empty-state
                    icon="fa-prescription"
                    title="No prescription templates yet"
                    message="Save common drug regimens as templates so they're one-click to load on the Rx tab." />
            </td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ── ADD MODAL ───────────────────────────────────────────────────────────── --}}
<div id="add-template-modal" class="modal-overlay">
    <div class="modal-box modal-lg">
        <div class="modal-header">
            <i class="fa-solid fa-layer-group" style="color:var(--sc-teal)"></i>
            <h3>New Prescription Template</h3>
            <button class="modal-close" onclick="document.getElementById('add-template-modal').classList.remove('open')">&times;</button>
        </div>
        <form method="POST" action="{{ route('prescription-templates.store') }}">
            @csrf
            <div class="modal-body">
                <div class="form-row cols-2" style="margin-bottom:16px">
                    <div class="form-group" style="margin:0">
                        <label class="form-label">Template Name <span class="req">*</span></label>
                        <input type="text" name="name" class="form-control" required placeholder="e.g. Diabetes Management">
                    </div>
                    <div class="form-group" style="margin:0">
                        <label class="form-label">Doctor (optional)</label>
                        <select name="doctor_id" class="form-control">
                            <option value="">All / General</option>
                            @foreach($doctors as $d)
                            <option value="{{ $d->id }}">{{ $d->full_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div id="add-items-container"></div>
                <button type="button" class="btn btn-outline btn-sm" onclick="addItem('add-items-container')">
                    <i class="fa-solid fa-plus"></i> Add Medicine
                </button>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('add-template-modal').classList.remove('open')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Save Template</button>
            </div>
        </form>
    </div>
</div>

{{-- ── EDIT MODAL ──────────────────────────────────────────────────────────── --}}
<div id="edit-template-modal" class="modal-overlay">
    <div class="modal-box modal-lg">
        <div class="modal-header">
            <i class="fa-solid fa-pencil" style="color:var(--sc-teal)"></i>
            <h3>Edit Prescription Template</h3>
            <button class="modal-close" onclick="document.getElementById('edit-template-modal').classList.remove('open')">&times;</button>
        </div>
        <form id="edit-template-form" method="POST">
            @csrf @method('PUT')
            <div class="modal-body">
                <div class="form-row cols-2" style="margin-bottom:16px">
                    <div class="form-group" style="margin:0">
                        <label class="form-label">Template Name <span class="req">*</span></label>
                        <input type="text" id="edit-name" name="name" class="form-control" required>
                    </div>
                    <div class="form-group" style="margin:0">
                        <label class="form-label">Doctor (optional)</label>
                        <select id="edit-doctor" name="doctor_id" class="form-control">
                            <option value="">All / General</option>
                            @foreach($doctors as $d)
                            <option value="{{ $d->id }}">{{ $d->full_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div id="edit-items-container"></div>
                <button type="button" class="btn btn-outline btn-sm" onclick="addItem('edit-items-container')">
                    <i class="fa-solid fa-plus"></i> Add Medicine
                </button>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('edit-template-modal').classList.remove('open')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Save Changes</button>
            </div>
        </form>
    </div>
</div>

@endsection
@push('scripts')
<script>
const FREQ_OPTIONS  = ['OD','BD','TDS','QID','HS','SOS','STAT'];
const UPDATE_BASE   = '{{ url("/prescription-templates") }}';
const STOCK_MEDICINES = @json($medicines);   // array of medicine name strings

let itemCount = 0;

function escHtml(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function medSelectOpts(selected) {
    const selVal = selected || '';
    const inStock = STOCK_MEDICINES.includes(selVal);
    let opts = `<option value="">-- Select Medicine --</option>`;
    STOCK_MEDICINES.forEach(name => {
        opts += `<option value="${escHtml(name)}" ${name === selVal ? 'selected' : ''}>${escHtml(name)}</option>`;
    });
    opts += `<option value="__other__" ${!inStock && selVal ? 'selected' : ''}>— Other (not in stock, type below) —</option>`;
    return opts;
}

function itemRow(containerId, idx, data) {
    const m        = data || {};
    const freqOpts = FREQ_OPTIONS.map(f => `<option value="${f}" ${m.frequency===f?'selected':''}>${f}</option>`).join('');
    const inStock  = m.medicine_name && STOCK_MEDICINES.includes(m.medicine_name);
    const isOther  = m.medicine_name && !inStock;
    return `
    <div class="template-item" style="background:var(--sc-bg);border:1px solid var(--sc-border);border-radius:8px;padding:14px;margin-bottom:10px;position:relative">
        <button type="button" onclick="this.closest('.template-item').remove()" style="position:absolute;top:8px;right:10px;background:none;border:none;font-size:18px;cursor:pointer;color:#94a3b8;line-height:1">&times;</button>
        <input type="hidden" name="items[${idx}][medicine_name]" class="med-name-hidden" value="${escHtml(m.medicine_name||'')}">
        <div class="form-row cols-2" style="margin-bottom:8px">
            <div class="form-group" style="margin:0">
                <label class="form-label">Medicine <span class="req">*</span></label>
                <select class="form-control med-select" onchange="onMedSelect(this)">
                    ${medSelectOpts(m.medicine_name)}
                </select>
                <input type="text" class="form-control med-other-input" placeholder="Type medicine name…"
                    value="${isOther ? escHtml(m.medicine_name) : ''}"
                    style="margin-top:6px;display:${isOther ? 'block' : 'none'}"
                    oninput="this.closest('.template-item').querySelector('.med-name-hidden').value=this.value">
            </div>
            <div class="form-group" style="margin:0">
                <label class="form-label">Dosage</label>
                <input type="text" name="items[${idx}][dosage]" class="form-control" placeholder="e.g. 1 Tab" value="${escHtml(m.dosage||'')}">
            </div>
        </div>
        <div class="form-row" style="grid-template-columns:1fr 1fr 2fr;gap:10px">
            <div class="form-group" style="margin:0">
                <label class="form-label">Frequency</label>
                <select name="items[${idx}][frequency]" class="form-control">${freqOpts}</select>
            </div>
            <div class="form-group" style="margin:0">
                <label class="form-label">Duration (days)</label>
                <input type="text" name="items[${idx}][duration]" class="form-control" placeholder="e.g. 30" value="${escHtml(m.duration||'')}">
            </div>
            <div class="form-group" style="margin:0">
                <label class="form-label">Instructions</label>
                <input type="text" name="items[${idx}][instructions]" class="form-control" placeholder="e.g. After food" value="${escHtml(m.instructions||'')}">
            </div>
        </div>
    </div>`;
}

function onMedSelect(sel) {
    const item       = sel.closest('.template-item');
    const hidden     = item.querySelector('.med-name-hidden');
    const otherInput = item.querySelector('.med-other-input');
    if (sel.value === '__other__') {
        otherInput.style.display = 'block';
        otherInput.focus();
        hidden.value = otherInput.value;
    } else {
        otherInput.style.display = 'none';
        hidden.value = sel.value;
    }
}

function addItem(containerId, data) {
    const idx       = itemCount++;
    const container = document.getElementById(containerId);
    const div       = document.createElement('div');
    div.innerHTML   = itemRow(containerId, idx, data);
    container.appendChild(div.firstElementChild);
}

function openAddModal() {
    itemCount = 0;
    document.getElementById('add-items-container').innerHTML = '';
    addItem('add-items-container');
    document.getElementById('add-template-modal').classList.add('open');
}

function openEditModal(id, name, doctorId, items) {
    document.getElementById('edit-template-form').action = UPDATE_BASE + '/' + id;
    document.getElementById('edit-name').value = name;
    document.getElementById('edit-doctor').value = doctorId ? String(doctorId) : '';
    const container = document.getElementById('edit-items-container');
    container.innerHTML = '';
    items.forEach(item => addItem('edit-items-container', item));
    document.getElementById('edit-template-modal').classList.add('open');
}

function filterRxTemplateRows(q) {
    const s = (q || '').trim().toLowerCase();
    const rows = document.querySelectorAll('.sc-table tbody tr');
    let shown = 0, total = rows.length;
    rows.forEach(tr => {
        const match = !s || tr.textContent.toLowerCase().includes(s);
        tr.style.display = match ? '' : 'none';
        if (match) shown++;
    });
    const cnt = document.getElementById('rxtpl-search-count');
    if (cnt) cnt.textContent = s ? `${shown} of ${total}` : '';
}
</script>
@endpush
