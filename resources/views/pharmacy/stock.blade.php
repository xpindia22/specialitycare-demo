{{-- FILE: resources/views/pharmacy/stock.blade.php --}}
@extends('layouts.app')
@section('title','Stock Management')
@section('page-title','Pharmacy Stock')
@section('content')

@include('pharmacy._toolbar')
<div class="page-header">
    <div class="page-header-left"><h1>Stock Management</h1><div class="breadcrumb">Current inventory levels</div></div>
    <button class="btn btn-outline" onclick="document.getElementById('add-medicine-modal').classList.add('open')"><i class="fa-solid fa-pills"></i> Add Medicine</button>
    <button class="btn btn-primary" onclick="document.getElementById('add-stock-modal').classList.add('open')"><i class="fa-solid fa-plus"></i> Add Stock</button>
</div>

{{-- Expiry Alerts --}}
@if(isset($expiredItems) && $expiredItems->count())
<div class="alert alert-danger mb-3">
    <i class="fa-solid fa-circle-xmark"></i>
    <div>
        <strong>{{ $expiredItems->count() }} item(s) EXPIRED</strong> — these should not be dispensed:
        <span>{{ $expiredItems->map(fn($s) => $s->medicine->name.' (batch '.$s->batch_number.', exp '.$s->expiry_date->format('M Y').')')->implode(', ') }}</span>
    </div>
</div>
@endif
@if(isset($expiring30) && $expiring30->count())
<div class="alert alert-danger mb-3" style="background:rgba(232,76,76,.08);border-color:rgba(232,76,76,.2);color:#922020">
    <i class="fa-solid fa-triangle-exclamation"></i>
    <div>
        <strong>{{ $expiring30->count() }} item(s) expiring within 30 days:</strong>
        <span>{{ $expiring30->map(fn($s) => $s->medicine->name.' ('.$s->expiry_date->format('d M Y').')')->implode(', ') }}</span>
    </div>
</div>
@endif
@if(isset($expiring90) && $expiring90->count())
<div class="alert alert-warning mb-3">
    <i class="fa-solid fa-clock"></i>
    <div>
        <strong>{{ $expiring90->count() }} item(s) expiring within 90 days:</strong>
        <span>{{ $expiring90->map(fn($s) => $s->medicine->name.' ('.$s->expiry_date->format('d M Y').')')->implode(', ') }}</span>
    </div>
</div>
@endif

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="form-row" style="grid-template-columns:minmax(240px,1fr) auto auto;gap:10px;align-items:end">
            <div class="form-group" style="margin:0">
                <label class="form-label">Search Stock</label>
                <input type="text" name="q" class="form-control" value="{{ request('q') }}" placeholder="Medicine, generic, batch, supplier...">
            </div>
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
            <a href="{{ route('pharmacy.stock') }}" class="btn btn-outline">Clear</a>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header"><i class="fa-solid fa-boxes-stacked"></i> Current Stock</div>
    <div class="table-wrap">
        <table class="sc-table">
            <thead>
                <tr><th>Medicine</th><th>Batch</th><th>Expiry</th><th>Qty</th><th>Min Level</th><th>Status</th><th>Selling Price</th><th>Supplier</th><th style="text-align:right">Actions</th></tr>
            </thead>
            <tbody>
            @forelse($stocks as $s)
            <tr>
                <td>
                    <div class="fw-600">{{ $s->medicine->name }}</div>
                    <div class="text-small text-muted">{{ $s->medicine->generic_name }} · {{ $s->medicine->formulation }}</div>
                </td>
                <td class="mono">{{ $s->batch_no }}</td>
                <td>
                    {{ $s->expiry_date ? $s->expiry_date->format('M Y') : '—' }}
                    @if($s->isExpired())<span class="badge badge-red" style="font-size:10px">Expired</span>
                    @elseif($s->isNearExpiry())<span class="badge badge-amber" style="font-size:10px">Near Expiry</span>@endif
                </td>
                <td>
                    <span style="font-size:18px;font-weight:700;color:{{ $s->quantity == 0 ? 'var(--sc-red)' : ($s->quantity <= $s->min_stock_level ? 'var(--sc-amber)' : 'var(--sc-green)') }}">{{ $s->quantity }}</span>
                </td>
                <td>{{ $s->min_stock_level }}</td>
                <td>
                    @if($s->quantity == 0)<span class="badge badge-red">Out of Stock</span>
                    @elseif($s->quantity <= 5)<span class="badge badge-red">Critical</span>
                    @elseif($s->quantity <= $s->min_stock_level)<span class="badge badge-amber">Low</span>
                    @else<span class="badge badge-green">Adequate</span>@endif
                </td>
                <td>₹{{ number_format($s->selling_price, 2) }}</td>
                <td class="text-muted">{{ $s->supplier ?: '—' }}</td>
                <td style="text-align:right">
                    <button type="button" class="btn btn-sm btn-outline"
                        onclick="openEditStockModal(this)"
                        data-id="{{ $s->id }}"
                        data-medicine="{{ e($s->medicine->name) }}"
                        data-batch="{{ e($s->batch_number) }}"
                        data-expiry="{{ $s->expiry_date ? $s->expiry_date->toDateString() : '' }}"
                        data-quantity="{{ $s->quantity }}"
                        data-min-stock="{{ $s->min_stock_level }}"
                        data-unit-cost="{{ $s->unit_cost }}"
                        data-supplier="{{ e($s->supplier) }}"
                        data-notes="{{ e($s->notes) }}">
                        <i class="fa-solid fa-pen"></i> Edit
                    </button>
                </td>
            </tr>
            @empty
            <tr><td colspan="9" class="text-center text-muted" style="padding:30px">{{ request('q') ? 'No stock records match your search.' : 'No stock records found.' }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $stocks->links() }}</div>
</div>

<!-- Add Medicine Modal -->
<div class="modal-overlay" id="add-medicine-modal">
    <div class="modal-box modal-lg">
        <div class="modal-header">
            <i class="fa-solid fa-pills" style="color:var(--sc-teal)"></i>
            <h3>Add New Medicine</h3>
            <button class="modal-close" onclick="document.getElementById('add-medicine-modal').classList.remove('open')">&times;</button>
        </div>
        <form method="POST" action="{{ route('pharmacy.medicines.store') }}">
            @csrf
            <input type="hidden" name="is_active" value="1">
            <div class="modal-body">
                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-label">Brand Name <span class="req">*</span></label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Generic Name</label>
                        <input type="text" name="generic_name" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Category <span class="req">*</span></label>
                        <select name="medicine_category_id" class="form-control" required>
                            <option value="">Select category...</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Dosage Form <span class="req">*</span></label>
                        <select name="dosage_form" class="form-control" required>
                            <option value="">Select form...</option>
                            @foreach(['Tablet','Capsule','Syrup','Injection','Ointment','Cream','Drops','Inhaler','Patch','Powder','Suspension','Suppository'] as $form)
                                <option value="{{ $form }}">{{ $form }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Strength</label>
                        <input type="text" name="strength" class="form-control" placeholder="e.g. 500mg">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Unit</label>
                        <input type="text" name="unit" class="form-control" value="Tab">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Unit Price (₹)</label>
                        <input type="number" name="unit_price" class="form-control" step="0.01" min="0" value="0">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Reorder Level</label>
                        <input type="number" name="reorder_level" class="form-control" min="0" value="10">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Manufacturer</label>
                        <input type="text" name="manufacturer" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Dosage Instructions</label>
                        <input type="text" name="dosage_instructions" class="form-control" placeholder="Typical dosing info">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('add-medicine-modal').classList.remove('open')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Add Medicine</button>
            </div>
        </form>
    </div>
</div>

<!-- Add Stock Modal -->
<div class="modal-overlay" id="add-stock-modal">
    <div class="modal-box">
        <div class="modal-header">
            <i class="fa-solid fa-boxes-stacked" style="color:var(--sc-teal)"></i>
            <h3>Add Stock Entry</h3>
            <button class="modal-close" onclick="document.getElementById('add-stock-modal').classList.remove('open')">&times;</button>
        </div>
        <form method="POST" action="{{ route('pharmacy.stock.add') }}">
            @csrf
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Medicine <span class="req">*</span></label>
                    <select name="medicine_id" id="add-stock-medicine" class="form-control select2" required style="width:100%">
                        <option value="">Select Medicine...</option>
                        @foreach($medicines as $m)
                            <option value="{{ $m->id }}" {{ request('medicine_id') == $m->id ? 'selected' : '' }}>{{ $m->name }} ({{ $m->generic_name }}) — {{ $m->formulation }} {{ $m->strength }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-row cols-2">
                    <div class="form-group"><label class="form-label">Batch No. <span class="req">*</span></label><input type="text" name="batch_no" class="form-control" required></div>
                    <div class="form-group"><label class="form-label">Received Date <span class="req">*</span></label><input type="date" name="received_date" class="form-control" value="{{ today()->toDateString() }}" required></div>
                    <div class="form-group"><label class="form-label">Manufacture Date</label><input type="date" name="manufacture_date" class="form-control"></div>
                    <div class="form-group"><label class="form-label">Expiry Date <span class="req">*</span></label><input type="date" name="expiry_date" class="form-control" required></div>
                    <div class="form-group"><label class="form-label">Quantity <span class="req">*</span></label><input type="number" name="quantity" id="add-stock-quantity" class="form-control" min="1" value="{{ request('quantity') }}" required></div>
                    <div class="form-group"><label class="form-label">Min Stock Level</label><input type="number" name="min_stock_level" class="form-control" value="10" min="0"></div>
                    <div class="form-group"><label class="form-label">Purchase Price (₹) <span class="req">*</span></label><input type="number" name="purchase_price" class="form-control" step="0.01" min="0" required></div>
                    <div class="form-group"><label class="form-label">Selling Price (₹) <span class="req">*</span></label><input type="number" name="selling_price" class="form-control" step="0.01" min="0" required></div>
                    <div class="form-group"><label class="form-label">MRP (₹)</label><input type="number" name="mrp" class="form-control" step="0.01" min="0"></div>
                    <div class="form-group">
                        <label class="form-label">Supplier</label>
                        <select name="supplier" class="form-control">
                            <option value="">— pick supplier —</option>
                            @foreach($suppliers as $sup)
                                <option value="{{ $sup->name }}" {{ $sup->name === $defaultSupplierName ? 'selected' : '' }}>{{ $sup->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('add-stock-modal').classList.remove('open')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Add Stock</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Stock Modal -->
<div class="modal-overlay" id="edit-stock-modal">
    <div class="modal-box">
        <div class="modal-header">
            <i class="fa-solid fa-pen" style="color:var(--sc-teal)"></i>
            <h3>Edit Stock - <span id="edit-stock-title"></span></h3>
            <button class="modal-close" onclick="document.getElementById('edit-stock-modal').classList.remove('open')">&times;</button>
        </div>
        <form method="POST" id="edit-stock-form">
            @csrf
            @method('PUT')
            <div class="modal-body">
                <div class="form-row cols-2">
                    <div class="form-group"><label class="form-label">Batch No.</label><input type="text" name="batch_number" id="edit-batch" class="form-control"></div>
                    <div class="form-group"><label class="form-label">Expiry Date</label><input type="date" name="expiry_date" id="edit-expiry" class="form-control"></div>
                    <div class="form-group"><label class="form-label">Quantity <span class="req">*</span></label><input type="number" name="quantity" id="edit-quantity" class="form-control" min="0" required></div>
                    <div class="form-group"><label class="form-label">Min Stock Level</label><input type="number" name="min_stock_level" id="edit-min-stock" class="form-control" min="0"></div>
                    <div class="form-group"><label class="form-label">Unit Cost (₹)</label><input type="number" name="unit_cost" id="edit-unit-cost" class="form-control" step="0.01" min="0"></div>
                    <div class="form-group">
                        <label class="form-label">Supplier</label>
                        <select name="supplier" id="edit-supplier" class="form-control">
                            <option value="">— pick supplier —</option>
                            @foreach($suppliers as $sup)
                                <option value="{{ $sup->name }}">{{ $sup->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" id="edit-notes" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer" style="justify-content:space-between">
                <button type="button" class="btn btn-outline" style="color:var(--sc-red);border-color:var(--sc-red)" onclick="openDeleteStockDialog()"><i class="fa-solid fa-trash"></i> Delete this stock</button>
                <div style="display:flex;gap:8px">
                    <button type="button" class="btn btn-outline" onclick="document.getElementById('edit-stock-modal').classList.remove('open')">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Save Changes</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Delete Stock confirmation dialog (uses native <dialog>) --}}
<dialog id="deleteStockDialog" style="border:1px solid #ddd;border-radius:8px;padding:0;max-width:560px;width:90vw">
    <form id="deleteStockForm" method="POST" style="padding:18px">
        @csrf
        @method('DELETE')
        <h3 style="margin:0 0 12px 0;color:var(--sc-red)"><i class="fa-solid fa-trash"></i> Delete this stock row</h3>
        <p class="text-small text-muted">Soft delete — sets quantity to 0 and marks Inactive. Logged in Removal History. Reversible by reactivating from Edit Stock.</p>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:10px">
            <div style="grid-column:span 2">
                <label>Reason for deletion *</label>
                <select name="destroy_method" class="form-control" required>
                    <option value="Damaged / unusable" selected>Damaged / unusable</option>
                    <option value="Wrong batch entered (correction)">Wrong batch entered (correction)</option>
                    <option value="Returned to supplier">Returned to supplier</option>
                    <option value="Destroyed per regulations (incineration / licensed disposal)">Destroyed per regulations (incineration / licensed disposal)</option>
                    <option value="Other (see notes)">Other (see notes)</option>
                </select>
            </div>
            <div>
                <label>Date</label>
                <input type="date" name="destroyed_on" value="{{ today()->toDateString() }}" class="form-control">
            </div>
            <div>
                <label>Witness (optional)</label>
                <input name="destroy_witness" class="form-control">
            </div>
            <div style="grid-column:span 2">
                <label>Notes (optional)</label>
                <textarea name="reason" rows="2" class="form-control" placeholder="Any extra context"></textarea>
            </div>
        </div>
        <div style="margin-top:14px;display:flex;gap:8px;justify-content:flex-end">
            <button type="button" class="btn btn-outline" onclick="document.getElementById('deleteStockDialog').close()">Cancel</button>
            <button type="submit" class="btn btn-primary" style="background:var(--sc-red);border-color:var(--sc-red)"><i class="fa-solid fa-trash"></i> Confirm Delete</button>
        </div>
    </form>
</dialog>
<script>
function openDeleteStockDialog() {
    const editFormAction = document.getElementById('edit-stock-form').action;
    // editFormAction is /pharmacy/stock/{id} (PUT) — swap to /pharmacy/stock/{id}/delete (DELETE)
    const id = editFormAction.split('/').pop();
    document.getElementById('deleteStockForm').action = `{{ url('/pharmacy/stock') }}/${id}/delete`;
    document.getElementById('edit-stock-modal').classList.remove('open');
    const dlg = document.getElementById('deleteStockDialog');
    if (typeof dlg.showModal === 'function') dlg.showModal(); else dlg.setAttribute('open','');
}
</script>

@push('scripts')
<script>
function openEditStockModal(button) {
    const modal = document.getElementById('edit-stock-modal');
    document.getElementById('edit-stock-title').textContent = button.dataset.medicine || 'Stock';
    document.getElementById('edit-stock-form').action = `{{ url('/pharmacy/stock') }}/${button.dataset.id}`;
    document.getElementById('edit-batch').value = button.dataset.batch || '';
    document.getElementById('edit-expiry').value = button.dataset.expiry || '';
    document.getElementById('edit-quantity').value = button.dataset.quantity || 0;
    document.getElementById('edit-min-stock').value = button.dataset.minStock || 10;
    document.getElementById('edit-unit-cost').value = button.dataset.unitCost || 0;
    document.getElementById('edit-supplier').value = button.dataset.supplier || '';
    document.getElementById('edit-notes').value = button.dataset.notes || '';
    modal.classList.add('open');
}

document.addEventListener('DOMContentLoaded', function () {
    @if(request('add_stock') && request('medicine_id'))
        const modal = document.getElementById('add-stock-modal');
        const medicineSelect = document.getElementById('add-stock-medicine');
        const quantityInput = document.getElementById('add-stock-quantity');

        if (medicineSelect) {
            medicineSelect.value = '{{ request('medicine_id') }}';
            if (window.jQuery) {
                window.jQuery(medicineSelect).trigger('change');
            }
        }

        if (quantityInput && !quantityInput.value) {
            quantityInput.value = '{{ request('quantity', 1) }}';
        }

        if (modal) {
            modal.classList.add('open');
        }
    @endif
});
</script>
@endpush
@endsection
