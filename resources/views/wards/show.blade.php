{{-- FILE: resources/views/wards/show.blade.php --}}
@extends('layouts.app')
@section('title', $ward->name)
@section('page-title','Ward Detail')
@section('content')

<div class="page-header">
    <div class="page-header-left">
        <h1>{{ $ward->name }}
            <span class="ward-type-badge wt-{{ $ward->type }}">{{ $ward->type_label }}</span>
            @if($ward->floor) <span style="font-size:13px;font-weight:400;color:#888"><i class="fa-solid fa-stairs"></i> {{ $ward->floor }}</span>@endif
        </h1>
    </div>
    <div class="d-flex gap-2">
        @if(auth()->user()->isAdmin())
        <a href="{{ route('wards.edit', $ward) }}" class="btn btn-outline"><i class="fa-solid fa-pen me-1"></i>Edit Ward</a>
        @endif
        <a href="{{ route('wards.index') }}" class="btn btn-outline">Back</a>
    </div>
</div>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

{{-- Stats row --}}
<div class="bed-summary-bar mb-4">
    <div class="bs-item bs-total">    <div class="bs-num">{{ $ward->total_beds_count }}</div>     <div class="bs-lbl">Total Beds</div></div>
    <div class="bs-item bs-available"><div class="bs-num">{{ $ward->available_beds_count }}</div>  <div class="bs-lbl">Available</div></div>
    <div class="bs-item bs-occupied"> <div class="bs-num">{{ $ward->occupied_beds_count }}</div>   <div class="bs-lbl">Occupied</div></div>
    <div class="bs-item bs-reserved"> <div class="bs-num">{{ $ward->beds->where('status','reserved')->count() }}</div><div class="bs-lbl">Reserved</div></div>
    <div class="bs-item bs-maint">    <div class="bs-num">{{ $ward->beds->where('status','maintenance')->count() }}</div><div class="bs-lbl">Maintenance</div></div>
</div>

<div class="ward-detail-grid">

    {{-- Bed Grid --}}
    <div class="card">
        <div class="card-header d-flex align-items-center gap-2">
            <strong>Bed Map</strong>
            <div class="bed-legend">
                <span class="bl-item bl-available">Available</span>
                <span class="bl-item bl-occupied">Occupied</span>
                <span class="bl-item bl-reserved">Reserved</span>
                <span class="bl-item bl-maintenance">Maintenance</span>
            </div>
        </div>
        <div class="card-body">
            @if($ward->beds->where('is_active',true)->count())
            <div class="bed-grid">
                @foreach($ward->beds->where('is_active',true)->sortBy('bed_number') as $bed)
                @php $occupant = $occupants[$bed->id] ?? null; @endphp
                @if($occupant)
                <a href="{{ route('admissions.show', $occupant) }}" class="bed-card bed-card-{{ $bed->status }}" style="text-decoration:none" title="Click to view IPD record">
                    <div class="bc-number">{{ $bed->bed_number }}</div>
                    <div class="bc-patient">{{ $occupant->patient->first_name }} {{ substr($occupant->patient->last_name,0,1) }}.</div>
                    <div class="bc-status" style="font-size:9px">{{ $occupant->days_admitted }}d</div>
                </a>
                @else
                <div class="bed-card bed-card-{{ $bed->status }}" onclick="openBedModal({{ $bed->id }}, '{{ $bed->bed_number }}', '{{ $bed->status }}', '{{ addslashes($bed->notes ?? '') }}')">
                    <div class="bc-number">{{ $bed->bed_number }}</div>
                    <div class="bc-status">{{ $bed->status_label }}</div>
                    @if($bed->type !== 'general')<div class="bc-type">{{ ucfirst(str_replace('_',' ',$bed->type)) }}</div>@endif
                </div>
                @endif
                @endforeach
            </div>
            @else
            <div class="text-center text-muted" style="padding:40px">No active beds. Add beds below.</div>
            @endif
        </div>
    </div>

    {{-- Sidebar: Add Beds + Bed Table --}}
    <div>
        {{-- Add Beds --}}
        @if(auth()->user()->isAdmin())
        <div class="card mb-3">
            <div class="card-header"><strong><i class="fa-solid fa-bed me-1"></i>Add Beds</strong></div>
            <div class="card-body">
                <form method="POST" action="{{ route('wards.beds.add', $ward) }}">
                    @csrf
                    <div class="form-group">
                        <label class="form-label">Prefix <span style="font-size:11px;color:#888">(optional, e.g. G, ICU)</span></label>
                        <input type="text" name="prefix" class="form-control" placeholder="e.g. G" maxlength="10" style="text-transform:uppercase">
                    </div>
                    <div class="form-group mt-2">
                        <label class="form-label">Bed Type</label>
                        <select name="bed_type" class="form-control">
                            <option value="general">General</option>
                            <option value="semi_private">Semi-Private</option>
                            <option value="private">Private</option>
                            <option value="icu">ICU</option>
                            <option value="hdu">HDU</option>
                        </select>
                    </div>
                    <div class="d-flex gap-2 mt-2">
                        <div class="form-group flex-1">
                            <label class="form-label">Start No.</label>
                            <input type="number" name="start" class="form-control" value="1" min="1">
                        </div>
                        <div class="form-group flex-1">
                            <label class="form-label">Count</label>
                            <input type="number" name="count" class="form-control" value="5" min="1" max="50">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 mt-3">
                        <i class="fa-solid fa-plus me-1"></i>Add Beds
                    </button>
                </form>
            </div>
        </div>
        @endif

        {{-- All Beds List --}}
        <div class="card">
            <div class="card-header"><strong>All Beds ({{ $ward->beds->count() }})</strong></div>
            <div class="table-wrap">
                <table class="sc-table" style="font-size:12px">
                    <thead><tr><th>Bed</th><th>Type</th><th>Status</th><th>Active</th></tr></thead>
                    <tbody>
                    @forelse($ward->beds->sortBy('bed_number') as $bed)
                    <tr class="{{ !$bed->is_active ? 'text-muted' : '' }}">
                        <td class="mono fw-600">{{ $bed->bed_number }}</td>
                        <td>{{ ucfirst(str_replace('_',' ',$bed->type)) }}</td>
                        <td><span class="bed-status-pill bs-pill-{{ $bed->status }}">{{ $bed->status_label }}</span></td>
                        <td>
                            @if(auth()->user()->isAdmin())
                            <form method="POST" action="{{ route('wards.beds.toggle', $bed) }}">
                                @csrf @method('PATCH')
                                <button class="btn btn-sm btn-outline" style="font-size:10px;padding:1px 7px">
                                    {{ $bed->is_active ? 'Deactivate' : 'Activate' }}
                                </button>
                            </form>
                            @else
                                {{ $bed->is_active ? 'Yes' : 'No' }}
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="text-center text-muted" style="padding:20px">No beds yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

{{-- Bed Status Modal --}}
<div id="bedModal" class="modal-overlay" style="display:none" onclick="closeBedModal(event)">
    <div class="modal-box" onclick="event.stopPropagation()">
        <div class="modal-header">
            <strong id="modalTitle">Update Bed</strong>
            <button onclick="closeBedModal()" class="modal-close">&times;</button>
        </div>
        <form method="POST" id="bedStatusForm">
            @csrf @method('PATCH')
            <div class="form-group mt-3">
                <label class="form-label">Status</label>
                <div class="bed-status-options">
                    <label class="bso bso-available">  <input type="radio" name="status" value="available">   <span>Available</span></label>
                    <label class="bso bso-occupied">   <input type="radio" name="status" value="occupied">    <span>Occupied</span></label>
                    <label class="bso bso-reserved">   <input type="radio" name="status" value="reserved">    <span>Reserved</span></label>
                    <label class="bso bso-maintenance"><input type="radio" name="status" value="maintenance"> <span>Maintenance</span></label>
                </div>
            </div>
            <div class="form-group mt-3">
                <label class="form-label">Notes <span style="font-size:11px;color:#888">(optional)</span></label>
                <input type="text" name="notes" id="modalNotes" class="form-control" placeholder="e.g. Cleaning, Reserved for patient…">
            </div>
            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary">Update Bed</button>
                <button type="button" onclick="closeBedModal()" class="btn btn-outline">Cancel</button>
            </div>
        </form>
    </div>
</div>

@push('styles')
<style>
.ward-detail-grid { display:grid; grid-template-columns:1fr 280px; gap:16px; align-items:start; }
@media(max-width:900px){ .ward-detail-grid { grid-template-columns:1fr; } }

.card-header { background:#f8f9fa; padding:10px 16px; border-bottom:1px solid #e0e0e0; display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
.card-body   { padding:16px; }

/* Bed grid */
.bed-grid { display:flex; flex-wrap:wrap; gap:10px; }
.bed-card { width:80px; height:70px; border-radius:6px; display:flex; flex-direction:column; align-items:center; justify-content:center; cursor:pointer; border:2px solid transparent; transition:.15s; }
.bed-card:hover { transform:translateY(-2px); box-shadow:0 4px 10px rgba(0,0,0,.12); }
.bc-number  { font-size:14px; font-weight:800; }
.bc-status  { font-size:9.5px; margin-top:2px; text-transform:uppercase; letter-spacing:.3px; font-weight:600; }
.bc-type    { font-size:9px; color:#888; margin-top:1px; }
.bc-patient { font-size:10px; margin-top:1px; font-weight:600; max-width:72px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.bed-card-available   { background:#dcfce7; color:#14532d; border-color:#86efac; }
.bed-card-occupied    { background:#fee2e2; color:#7f1d1d; border-color:#fca5a5; }
.bed-card-reserved    { background:#fef9c3; color:#713f12; border-color:#fde047; }
.bed-card-maintenance { background:#f3f4f6; color:#9ca3af; border-color:#d1d5db; }

/* Legend */
.bed-legend { display:flex; gap:8px; flex-wrap:wrap; margin-left:auto; }
.bl-item { font-size:10px; font-weight:600; padding:2px 8px; border-radius:10px; }
.bl-available   { background:#dcfce7; color:#14532d; }
.bl-occupied    { background:#fee2e2; color:#7f1d1d; }
.bl-reserved    { background:#fef9c3; color:#713f12; }
.bl-maintenance { background:#f3f4f6; color:#9ca3af; }

/* Status pills */
.bed-status-pill { display:inline-block; padding:1px 8px; border-radius:10px; font-size:10.5px; font-weight:600; }
.bs-pill-available   { background:#dcfce7; color:#14532d; }
.bs-pill-occupied    { background:#fee2e2; color:#7f1d1d; }
.bs-pill-reserved    { background:#fef9c3; color:#713f12; }
.bs-pill-maintenance { background:#f3f4f6; color:#9ca3af; }

/* Summary bar */
.bed-summary-bar { display:flex; gap:10px; flex-wrap:wrap; }
.bs-item { background:#fff; border:1px solid #ddd; border-radius:6px; padding:10px 18px; text-align:center; }
.bs-num { font-size:26px; font-weight:800; line-height:1; }
.bs-lbl { font-size:10.5px; color:#666; margin-top:2px; }
.bs-total .bs-num { color:#333; } .bs-available .bs-num { color:#16a34a; } .bs-occupied .bs-num { color:#dc2626; } .bs-reserved .bs-num { color:#d97706; } .bs-maint .bs-num { color:#9ca3af; }

/* Ward type badges */
.ward-type-badge { display:inline-block; padding:2px 10px; border-radius:10px; font-size:11px; font-weight:700; text-transform:uppercase; vertical-align:middle; }
.wt-general { background:#dbeafe; color:#1e40af; } .wt-semi_private { background:#ede9fe; color:#5b21b6; }
.wt-private { background:#fef9c3; color:#854d0e; } .wt-icu { background:#fee2e2; color:#7f1d1d; }
.wt-hdu { background:#fce7f3; color:#831843; } .wt-emergency { background:#ffedd5; color:#7c2d12; }

/* Modal */
.modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:2000; display:flex; align-items:center; justify-content:center; }
.modal-box { background:#fff; border-radius:10px; padding:20px; width:380px; max-width:95vw; }
.modal-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:4px; }
.modal-close { background:none; border:none; font-size:22px; cursor:pointer; color:#888; line-height:1; }

.bed-status-options { display:grid; grid-template-columns:1fr 1fr; gap:8px; }
.bso { border:2px solid #e0e0e0; border-radius:6px; padding:10px; text-align:center; cursor:pointer; font-size:13px; font-weight:600; }
.bso input { display:none; }
.bso-available:has(input:checked)   { border-color:#16a34a; background:#f0fdf4; color:#14532d; }
.bso-occupied:has(input:checked)    { border-color:#dc2626; background:#fff5f5; color:#7f1d1d; }
.bso-reserved:has(input:checked)    { border-color:#d97706; background:#fffbeb; color:#713f12; }
.bso-maintenance:has(input:checked) { border-color:#9ca3af; background:#f9fafb; color:#6b7280; }
</style>
@endpush

@push('scripts')
<script>
function openBedModal(id, number, status, notes) {
    document.getElementById('modalTitle').textContent = 'Bed ' + number;
    document.getElementById('bedStatusForm').action = '/specialitycare/wards/beds/' + id;
    document.getElementById('modalNotes').value = notes;
    document.querySelectorAll('.bso input').forEach(r => r.checked = (r.value === status));
    document.getElementById('bedModal').style.display = 'flex';
}
function closeBedModal(e) {
    if (!e || e.target === document.getElementById('bedModal')) {
        document.getElementById('bedModal').style.display = 'none';
    }
}
</script>
@endpush
@endsection
