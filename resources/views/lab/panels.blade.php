@extends('layouts.app')

@section('title', 'Lab Panels')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-1">Lab Panels</h4>
        <p class="text-muted mb-0">Test bundles with bundled pricing — <span id="panel-count">{{ $panels->count() }}</span> panels</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPanelModal">
        <i class="bi bi-plus-lg me-1"></i> Create Panel
    </button>
</div>

<div class="mb-4" style="max-width:360px">
    <div style="display:flex;align-items:center;gap:8px;background:#fff;border:1.5px solid var(--sc-border);border-radius:8px;padding:7px 14px">
        <i class="fa-solid fa-magnifying-glass" style="color:var(--sc-muted)"></i>
        <input type="text" id="panel-search" placeholder="Search panels or tests…"
               style="border:none;outline:none;font-size:13px;width:100%;font-family:inherit;background:transparent">
        <button id="panel-search-clear" onclick="clearPanelSearch()" style="display:none;background:none;border:none;cursor:pointer;color:var(--sc-muted);padding:0;line-height:1">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>
</div>

<div class="row g-4" id="panels-grid">
    @forelse($panels as $panel)
    <div class="col-md-6 col-xl-4 panel-col"
         data-search="{{ strtolower($panel->name . ' ' . $panel->short_code . ' ' . $panel->tests->pluck('name')->implode(' ')) }}"
    >
        <div class="card h-100 panel-card" id="panel-card-{{ $panel->id }}">
            <div class="card-header d-flex justify-content-between align-items-start">
                <div>
                    <h6 class="mb-1 fw-semibold">{{ $panel->name }}</h6>
                    @if($panel->short_code)
                        <span class="badge bg-light text-dark font-monospace">{{ $panel->short_code }}</span>
                    @endif
                </div>
                <div class="text-end">
                    <div id="panel-price-display-{{ $panel->id }}" style="display:flex;align-items:center;justify-content:flex-end;gap:6px">
                        <div class="h5 mb-0" style="color:var(--sc-teal)" id="panel-price-value-{{ $panel->id }}">₹{{ number_format($panel->price, 2) }}</div>
                        <button type="button" class="btn btn-sm btn-outline" style="padding:3px 7px" onclick="togglePanelPriceEdit({{ $panel->id }}, {{ $panel->price }})" title="Edit panel price">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                    </div>
                    <div id="panel-price-edit-{{ $panel->id }}" style="display:none;align-items:center;gap:4px;justify-content:flex-end">
                        <span style="font-weight:700;color:var(--sc-muted)">₹</span>
                        <input type="number" id="panel-price-input-{{ $panel->id }}" class="form-control" style="width:96px;padding:5px 8px" step="0.01" min="0" value="{{ $panel->price }}">
                        <button type="button" class="btn btn-sm btn-primary" style="padding:5px 8px" onclick="savePanelPrice({{ $panel->id }})"><i class="fa-solid fa-check"></i></button>
                        <button type="button" class="btn btn-sm btn-outline" style="padding:5px 8px" onclick="cancelPanelPriceEdit({{ $panel->id }})"><i class="fa-solid fa-xmark"></i></button>
                    </div>
                    @php
                        $sumPrice = $panel->tests->sum('price');
                        $savings = $sumPrice - $panel->price;
                    @endphp
                    @if($savings > 0)
                        <small class="text-success"><i class="bi bi-tag-fill"></i> Saves ₹{{ number_format($savings, 2) }}</small>
                    @endif
                </div>
            </div>
            <div class="card-body">
                @if($panel->description)
                    <p class="text-muted small mb-3">{{ $panel->description }}</p>
                @endif
                <div class="mb-2">
                    <small class="fw-semibold text-muted">INCLUDES {{ $panel->tests->count() }} TEST{{ $panel->tests->count() !== 1 ? 'S' : '' }}</small>
                </div>
                <div class="d-flex flex-wrap gap-1 mb-3">
                    @foreach($panel->tests as $test)
                        <span class="badge bg-light text-dark border">
                            {{ $test->name }}
                            <span class="text-muted">₹{{ number_format($test->price, 0) }}</span>
                        </span>
                    @endforeach
                </div>
                <div class="d-flex align-items-center gap-2">
                    @if($panel->turnaround_hours)
                        <small class="text-muted"><i class="bi bi-clock me-1"></i>{{ $panel->turnaround_hours }}h TAT</small>
                    @endif
                    <span class="badge bg-{{ $panel->is_active ? 'success' : 'secondary' }} ms-auto">
                        {{ $panel->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>
            </div>
            <div class="card-footer d-flex gap-2">
                <button class="btn btn-sm btn-outline-primary flex-fill" onclick="editPanel({{ $panel->id }})">
                    <i class="bi bi-pencil me-1"></i> Edit
                </button>
                <form method="POST" action="{{ route('lab.panels.toggle', $panel->id) }}" class="flex-fill">
                    @csrf @method('PATCH')
                    <button type="submit" class="btn btn-sm btn-outline-{{ $panel->is_active ? 'danger' : 'success' }} w-100">
                        <i class="bi bi-{{ $panel->is_active ? 'pause-circle' : 'play-circle' }} me-1"></i>
                        {{ $panel->is_active ? 'Deactivate' : 'Activate' }}
                    </button>
                </form>
                <form method="POST" action="{{ route('lab.panels.destroy', $panel) }}" class="flex-fill" onsubmit="return confirm('Delete panel {{ addslashes($panel->name) }}?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger w-100">
                        <i class="bi bi-trash me-1"></i> Delete
                    </button>
                </form>
            </div>
        </div>
    </div>
    @empty
    <div class="col-12">
        <div class="card">
            <div class="card-body text-center py-5 text-muted">
                <i class="bi bi-collection fs-1 d-block mb-3"></i>
                <h5>No panels yet</h5>
                <p>Create test panels to bundle related tests at a discounted price.</p>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPanelModal">
                    <i class="bi bi-plus-lg me-1"></i> Create First Panel
                </button>
            </div>
        </div>
    </div>
    @endforelse
</div>

{{-- Add / Edit Panel Modal --}}
<div class="modal fade" id="addPanelModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="panelModalTitle">Create Lab Panel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="panelForm" method="POST" action="{{ route('lab.panels.store') }}">
                @csrf
                <input type="hidden" name="_method" id="panelFormMethod" value="POST">
                <input type="hidden" name="panel_id" id="panelId">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Panel Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="panelName" class="form-control" required placeholder="e.g. Complete Blood Count">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Short Code</label>
                            <input type="text" name="short_code" id="panelCode" class="form-control" placeholder="CBC">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Turnaround (hrs)</label>
                            <input type="number" name="turnaround_hours" id="panelTurnaround" class="form-control" min="1">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Description</label>
                            <textarea name="description" id="panelDesc" class="form-control" rows="2"></textarea>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Select Tests <span class="text-danger">*</span></label>
                            <select name="test_ids[]" id="panelTests" class="form-select select2" multiple required>
                                @foreach($allTests as $test)
                                    <option value="{{ $test->id }}" data-price="{{ $test->price }}">
                                        {{ $test->name }} (₹{{ number_format($test->price, 2) }})
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">Sum of selected tests: <strong id="testSum">₹0.00</strong></small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Panel Price (₹) <span class="text-danger">*</span></label>
                            <input type="number" name="price" id="panelPrice" class="form-control" step="0.01" min="0" required>
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <button type="button" class="btn btn-outline-secondary mb-1" onclick="autoFillPrice()">
                                <i class="bi bi-calculator me-1"></i> Use sum as price
                            </button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Panel</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function togglePanelPriceEdit(id, current) {
    document.getElementById(`panel-price-display-${id}`).style.display = 'none';
    document.getElementById(`panel-price-edit-${id}`).style.display = 'flex';
    const input = document.getElementById(`panel-price-input-${id}`);
    input.value = parseFloat(current || input.value || 0).toFixed(2);
    input.focus();
    input.select();
}

function cancelPanelPriceEdit(id) {
    document.getElementById(`panel-price-edit-${id}`).style.display = 'none';
    document.getElementById(`panel-price-display-${id}`).style.display = 'flex';
}

function savePanelPrice(id) {
    const input = document.getElementById(`panel-price-input-${id}`);
    const price = input.value;
    fetch(`{{ url('/lab/panels') }}/${id}/price`, {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
        },
        body: JSON.stringify({ price }),
    }).then(r => {
        if (!r.ok) throw new Error('Failed to update panel price');
        return r.json();
    }).then(data => {
        if (data.success) {
            document.getElementById(`panel-price-value-${id}`).textContent = '₹' + parseFloat(price || 0).toFixed(2);
            cancelPanelPriceEdit(id);
        }
    }).catch(() => alert('Failed to update panel price'));
}

// Recalculate sum when tests selection changes
document.addEventListener('DOMContentLoaded', function() {
    const sel = document.getElementById('panelTests');
    $(sel).on('change', updateTestSum);
});

function updateTestSum() {
    const sel = document.getElementById('panelTests');
    let sum = 0;
    Array.from(sel.selectedOptions).forEach(opt => {
        sum += parseFloat(opt.dataset.price || 0);
    });
    document.getElementById('testSum').textContent = '₹' + sum.toFixed(2);
    window._testSum = sum;
}

function autoFillPrice() {
    document.getElementById('panelPrice').value = (window._testSum || 0).toFixed(2);
}

function editPanel(id) {
    fetch(`/lab/panels/${id}/info`)
        .then(r => r.json())
        .then(panel => {
            document.getElementById('panelModalTitle').textContent = 'Edit Panel';
            document.getElementById('panelFormMethod').value = 'PUT';
            document.getElementById('panelId').value = panel.id;
            document.getElementById('panelForm').action = `/lab/panels/${panel.id}`;
            document.getElementById('panelName').value = panel.name;
            document.getElementById('panelCode').value = panel.short_code ?? '';
            document.getElementById('panelDesc').value = panel.description ?? '';
            document.getElementById('panelTurnaround').value = panel.turnaround_hours ?? '';
            document.getElementById('panelPrice').value = panel.price;
            // Set selected tests
            const testIds = panel.tests.map(t => t.id.toString());
            $('#panelTests').val(testIds).trigger('change');
            new bootstrap.Modal(document.getElementById('addPanelModal')).show();
        });
}

// Panel search
const panelSearchInput = document.getElementById('panel-search');
const panelSearchClear = document.getElementById('panel-search-clear');

panelSearchInput.addEventListener('input', filterPanels);

function filterPanels() {
    const q = panelSearchInput.value.trim().toLowerCase();
    panelSearchClear.style.display = q ? 'block' : 'none';
    let visible = 0;
    document.querySelectorAll('.panel-col').forEach(col => {
        const match = !q || col.dataset.search.includes(q);
        col.style.display = match ? '' : 'none';
        if (match) visible++;
    });
    document.getElementById('panel-count').textContent = q ? visible : {{ $panels->count() }};
}

function clearPanelSearch() {
    panelSearchInput.value = '';
    filterPanels();
    panelSearchInput.focus();
}

document.getElementById('addPanelModal').addEventListener('show.bs.modal', function(e) {
    if (!e.relatedTarget) return;
    document.getElementById('panelModalTitle').textContent = 'Create Lab Panel';
    document.getElementById('panelFormMethod').value = 'POST';
    document.getElementById('panelForm').action = '{{ route("lab.panels.store") }}';
    document.getElementById('panelForm').reset();
    $('#panelTests').val(null).trigger('change');
    document.getElementById('testSum').textContent = '₹0.00';
});
</script>
@endpush
