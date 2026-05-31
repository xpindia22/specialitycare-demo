@extends('layouts.app')

@section('title', 'Medicines')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">Medicines</h4>
        <p class="text-muted mb-0">Manage drug formulary — {{ $medicines->total() }} medicines</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMedicineModal">
        <i class="bi bi-plus-lg me-1"></i> Add Medicine
    </button>
</div>

{{-- Search & Filters --}}
<div class="card mb-4">
    <div class="card-body py-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <input type="text" name="q" class="form-control" placeholder="Search name, generic, category…" value="{{ request('q') }}">
            </div>
            <div class="col-md-3">
                <select name="category" class="form-select">
                    <option value="">All Categories</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ request('category') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-outline-primary">Filter</button>
                <a href="{{ route('pharmacy.medicines') }}" class="btn btn-outline-secondary">Clear</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Medicine</th>
                    <th>Category</th>
                    <th>Form / Strength</th>
                    <th>Unit Price</th>
                    <th>Stock</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($medicines as $med)
                <tr>
                    <td>
                        <div class="fw-semibold">{{ $med->name }}</div>
                        @if($med->generic_name)
                            <small class="text-muted">{{ $med->generic_name }}</small>
                        @endif
                    </td>
                    <td>
                        <span class="badge" style="background:var(--sc-primary-light);color:var(--sc-primary)">
                            {{ $med->category->name ?? '—' }}
                        </span>
                    </td>
                    <td>
                        {{ $med->dosage_form }}
                        @if($med->strength) <span class="text-muted">/ {{ $med->strength }}</span> @endif
                    </td>
                    <td>₹{{ number_format($med->unit_price, 2) }}</td>
                    <td>
                        @php $stock = $med->total_stock; $status = $med->stock_status; @endphp
                        <span class="badge bg-{{ $status === 'adequate' ? 'success' : ($status === 'low' ? 'warning text-dark' : ($status === 'critical' ? 'danger' : 'secondary')) }}">
                            {{ $stock }} {{ $med->unit }}
                        </span>
                    </td>
                    <td>
                        <span class="badge bg-{{ $med->is_active ? 'success' : 'secondary' }}">
                            {{ $med->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-outline-info" onclick="viewMedicine({{ $med->id }})" title="View Details">
                            <i class="bi bi-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-primary" onclick="editMedicine({{ $med->id }})" title="Edit">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-success" onclick="addStock({{ $med->id }}, '{{ addslashes($med->name) }}')" title="Add Stock">
                            <i class="bi bi-plus-circle"></i>
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">No medicines found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($medicines->hasPages())
    <div class="card-footer">{{ $medicines->appends(request()->query())->links() }}</div>
    @endif
</div>

{{-- Add / Edit Medicine Modal --}}
<div class="modal fade" id="addMedicineModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="medicineModalTitle">Add Medicine</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="medicineForm" method="POST" action="{{ route('pharmacy.medicines.store') }}">
                @csrf
                <input type="hidden" name="_method" id="formMethod" value="POST">
                <input type="hidden" name="medicine_id" id="medicineId">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Brand Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="medName" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Generic Name</label>
                            <input type="text" name="generic_name" id="medGeneric" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Category <span class="text-danger">*</span></label>
                            <select name="medicine_category_id" id="medCategory" class="form-select" required>
                                <option value="">Select Category</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Dosage Form <span class="text-danger">*</span></label>
                            <select name="dosage_form" id="medForm" class="form-select" required>
                                <option value="">Select</option>
                                @foreach(['Tablet','Capsule','Syrup','Injection','Ointment','Cream','Drops','Inhaler','Patch','Powder','Suspension','Suppository'] as $form)
                                    <option value="{{ $form }}">{{ $form }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Strength</label>
                            <input type="text" name="strength" id="medStrength" class="form-control" placeholder="e.g. 500mg, 10mg/5ml">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">Unit</label>
                            <input type="text" name="unit" id="medUnit" class="form-control" placeholder="Tab / ml / g">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">Unit Price (₹)</label>
                            <input type="number" name="unit_price" id="medPrice" class="form-control" step="0.01" min="0" value="0">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">Reorder Level</label>
                            <input type="number" name="reorder_level" id="medReorder" class="form-control" min="0" value="10">
                        </div>

                        <div class="col-12"><hr class="my-1"></div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Indications</label>
                            <textarea name="indications" id="medIndications" class="form-control" rows="2" placeholder="Uses / indications…"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Side Effects</label>
                            <textarea name="side_effects" id="medSideEffects" class="form-control" rows="2" placeholder="Common side effects…"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Contraindications</label>
                            <textarea name="contraindications" id="medContra" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Drug Interactions</label>
                            <textarea name="drug_interactions" id="medInteractions" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Standard Dosage Instructions</label>
                            <textarea name="dosage_instructions" id="medDosage" class="form-control" rows="2" placeholder="Typical dosing info…"></textarea>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Manufacturer</label>
                            <input type="text" name="manufacturer" id="medManufacturer" class="form-control">
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="is_active" id="medActive" value="1" checked>
                                <label class="form-check-label" for="medActive">Active</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Medicine</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- View Medicine Detail Modal --}}
<div class="modal fade" id="viewMedicineModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewMedTitle">Medicine Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="viewMedBody">
                <div class="text-center py-4"><div class="spinner-border text-primary"></div></div>
            </div>
        </div>
    </div>
</div>

{{-- Add Stock Modal --}}
<div class="modal fade" id="addStockModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Stock — <span id="stockMedName"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('pharmacy.stock.add') }}">
                @csrf
                <input type="hidden" name="medicine_id" id="stockMedId">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Quantity <span class="text-danger">*</span></label>
                            <input type="number" name="quantity" class="form-control" min="1" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Unit Cost (₹)</label>
                            <input type="number" name="unit_cost" class="form-control" step="0.01" min="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Batch Number</label>
                            <input type="text" name="batch_number" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Expiry Date</label>
                            <input type="date" name="expiry_date" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Supplier</label>
                            <select name="supplier" class="form-control">
                                <option value="">— pick supplier —</option>
                                @foreach($suppliers as $sup)
                                    <option value="{{ $sup->name }}" {{ $sup->name === $defaultSupplierName ? 'selected' : '' }}>{{ $sup->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Add Stock</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const medicines = @json($medicines->items());

function viewMedicine(id) {
    const modal = new bootstrap.Modal(document.getElementById('viewMedicineModal'));
    modal.show();
    fetch(`/pharmacy/medicines/${id}/info`)
        .then(r => r.json())
        .then(med => {
            document.getElementById('viewMedTitle').textContent = med.name + (med.generic_name ? ` (${med.generic_name})` : '');
            document.getElementById('viewMedBody').innerHTML = `
                <div class="row g-3">
                    <div class="col-md-4"><strong>Category</strong><div>${med.category?.name ?? '—'}</div></div>
                    <div class="col-md-4"><strong>Form / Strength</strong><div>${med.dosage_form}${med.strength ? ' / ' + med.strength : ''}</div></div>
                    <div class="col-md-4"><strong>Unit Price</strong><div>₹${parseFloat(med.unit_price).toFixed(2)}</div></div>
                    <div class="col-md-4"><strong>Current Stock</strong><div>${med.total_stock ?? 0} ${med.unit ?? ''}</div></div>
                    <div class="col-md-4"><strong>Reorder Level</strong><div>${med.reorder_level}</div></div>
                    <div class="col-md-4"><strong>Manufacturer</strong><div>${med.manufacturer ?? '—'}</div></div>
                    ${med.indications ? `<div class="col-12"><strong>Indications</strong><div class="text-muted">${med.indications}</div></div>` : ''}
                    ${med.side_effects ? `<div class="col-12"><strong>Side Effects</strong><div class="text-muted">${med.side_effects}</div></div>` : ''}
                    ${med.contraindications ? `<div class="col-12"><strong>Contraindications</strong><div class="text-muted">${med.contraindications}</div></div>` : ''}
                    ${med.drug_interactions ? `<div class="col-12"><strong>Drug Interactions</strong><div class="text-muted">${med.drug_interactions}</div></div>` : ''}
                    ${med.dosage_instructions ? `<div class="col-12"><strong>Dosage Instructions</strong><div class="text-muted">${med.dosage_instructions}</div></div>` : ''}
                </div>`;
        });
}

function editMedicine(id) {
    fetch(`/pharmacy/medicines/${id}/info`)
        .then(r => r.json())
        .then(med => {
            document.getElementById('medicineModalTitle').textContent = 'Edit Medicine';
            document.getElementById('formMethod').value = 'PUT';
            document.getElementById('medicineId').value = med.id;
            document.getElementById('medicineForm').action = `/pharmacy/medicines/${med.id}`;
            document.getElementById('medName').value = med.name;
            document.getElementById('medGeneric').value = med.generic_name ?? '';
            document.getElementById('medCategory').value = med.medicine_category_id;
            document.getElementById('medForm').value = med.dosage_form;
            document.getElementById('medStrength').value = med.strength ?? '';
            document.getElementById('medUnit').value = med.unit ?? '';
            document.getElementById('medPrice').value = med.unit_price;
            document.getElementById('medReorder').value = med.reorder_level;
            document.getElementById('medIndications').value = med.indications ?? '';
            document.getElementById('medSideEffects').value = med.side_effects ?? '';
            document.getElementById('medContra').value = med.contraindications ?? '';
            document.getElementById('medInteractions').value = med.drug_interactions ?? '';
            document.getElementById('medDosage').value = med.dosage_instructions ?? '';
            document.getElementById('medManufacturer').value = med.manufacturer ?? '';
            document.getElementById('medActive').checked = med.is_active;
            new bootstrap.Modal(document.getElementById('addMedicineModal')).show();
        });
}

function addStock(id, name) {
    document.getElementById('stockMedId').value = id;
    document.getElementById('stockMedName').textContent = name;
    new bootstrap.Modal(document.getElementById('addStockModal')).show();
}

// Reset form when Add Medicine modal opens fresh
document.getElementById('addMedicineModal').addEventListener('show.bs.modal', function(e) {
    if (!e.relatedTarget) return; // opened programmatically (edit) — skip reset
    document.getElementById('medicineModalTitle').textContent = 'Add Medicine';
    document.getElementById('formMethod').value = 'POST';
    document.getElementById('medicineId').value = '';
    document.getElementById('medicineForm').action = '{{ route("pharmacy.medicines.store") }}';
    document.getElementById('medicineForm').reset();
});
</script>
@endpush
