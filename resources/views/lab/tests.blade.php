@extends('layouts.app')

@section('title', 'Lab Tests')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">Lab Tests</h4>
        <p class="text-muted mb-0">{{ $tests->total() }} tests across {{ $categories->count() }} categories</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTestModal">
        <i class="bi bi-plus-lg me-1"></i> Add Test
    </button>
</div>

{{-- Filters --}}
<div class="card mb-4">
    <div class="card-body py-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <input type="text" name="q" class="form-control" placeholder="Search test name…" value="{{ request('q') }}">
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
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-outline-primary">Filter</button>
                <a href="{{ route('lab.tests') }}" class="btn btn-outline-secondary">Clear</a>
            </div>
        </form>
    </div>
</div>

{{-- Quick bulk price update --}}
<div class="card mb-4 border-warning">
    <div class="card-body py-2">
        <form method="POST" action="{{ route('lab.tests.bulk-price') }}" class="row g-2 align-items-end">
            @csrf
            <div class="col-auto">
                <label class="form-label small mb-1 text-warning fw-semibold"><i class="bi bi-lightning-fill me-1"></i>Bulk Price Update</label>
            </div>
            <div class="col-md-3">
                <select name="category_id" class="form-select form-select-sm">
                    <option value="">All Categories</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <select name="adjustment_type" class="form-select form-select-sm">
                    <option value="increase_pct">Increase by %</option>
                    <option value="decrease_pct">Decrease by %</option>
                    <option value="set_fixed">Set Fixed Price</option>
                </select>
            </div>
            <div class="col-md-2">
                <input type="number" name="amount" class="form-control form-control-sm" placeholder="Amount" step="0.01" min="0" required>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-warning btn-sm" onclick="return confirm('This will update prices for matching tests. Continue?')">Apply</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead>
                <tr>
                    <th>Test Name</th>
                    <th>Category</th>
                    <th>Sample Type</th>
                    <th>Turnaround</th>
                    <th>Price (₹)</th>
                    <th>Normal Range</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tests as $test)
                <tr id="test-row-{{ $test->id }}">
                    <td>
                        <div class="fw-semibold">{{ $test->name }}</div>
                        @if($test->short_code)
                            <small class="text-muted font-monospace">{{ $test->short_code }}</small>
                        @endif
                    </td>
                    <td>
                        <span class="badge" style="background:var(--sc-primary-light);color:var(--sc-primary)">
                            {{ $test->category->name ?? '—' }}
                        </span>
                    </td>
                    <td><small>{{ $test->sample_type ?? '—' }}</small></td>
                    <td><small>{{ $test->turnaround_hours ? $test->turnaround_hours . 'h' : '—' }}</small></td>
                    <td>
                        {{-- Inline editable price --}}
                        <div class="d-flex align-items-center gap-1">
                            <span class="price-display-{{ $test->id }}">₹{{ number_format($test->price, 2) }}</span>
                            <button class="btn btn-sm btn-link p-0 ms-1 text-muted" onclick="togglePriceEdit({{ $test->id }}, {{ $test->price }})" title="Edit price">
                                <i class="bi bi-pencil-fill" style="font-size:.7rem"></i>
                            </button>
                        </div>
                        <div class="price-edit-{{ $test->id }} d-none">
                            <div class="input-group input-group-sm" style="width:120px">
                                <span class="input-group-text">₹</span>
                                <input type="number" id="price-input-{{ $test->id }}" class="form-control" step="0.01" min="0" value="{{ $test->price }}">
                                <button class="btn btn-success btn-sm" onclick="savePrice({{ $test->id }})"><i class="bi bi-check"></i></button>
                                <button class="btn btn-outline-secondary btn-sm" onclick="cancelPriceEdit({{ $test->id }})"><i class="bi bi-x"></i></button>
                            </div>
                        </div>
                    </td>
                    <td><small class="text-muted">{{ $test->normal_range ?? '—' }}</small></td>
                    <td>
                        <span class="badge bg-{{ $test->is_active ? 'success' : 'secondary' }}">
                            {{ $test->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-outline-primary" onclick="editTest({{ $test->id }})" title="Edit">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <form method="POST" action="{{ route('lab.tests.toggle', $test->id) }}" class="d-inline">
                            @csrf @method('PATCH')
                            <button type="submit" class="btn btn-sm btn-outline-{{ $test->is_active ? 'danger' : 'success' }}" title="{{ $test->is_active ? 'Deactivate' : 'Activate' }}">
                                <i class="bi bi-{{ $test->is_active ? 'pause-circle' : 'play-circle' }}"></i>
                            </button>
                        </form>
                        <form method="POST" action="{{ route('lab.tests.destroy', $test) }}" class="d-inline" onsubmit="return confirm('Delete test {{ addslashes($test->name) }}?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center text-muted py-5">No tests found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($tests->hasPages())
    <div class="card-footer">{{ $tests->appends(request()->query())->links() }}</div>
    @endif
</div>

{{-- Add / Edit Test Modal --}}
<div class="modal fade" id="addTestModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="testModalTitle">Add Lab Test</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="testForm" method="POST" action="{{ route('lab.tests.store') }}">
                @csrf
                <input type="hidden" name="_method" id="testFormMethod" value="POST">
                <input type="hidden" name="test_id" id="testId">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Test Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="testName" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Short Code</label>
                            <input type="text" name="short_code" id="testCode" class="form-control" placeholder="e.g. CBC, LFT">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Category <span class="text-danger">*</span></label>
                            <select name="lab_category_id" id="testCategory" class="form-select" required>
                                <option value="">Select</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Sample Type</label>
                            <select name="sample_type" id="testSample" class="form-select">
                                <option value="">Select</option>
                                @foreach(['Blood','Urine','Stool','Sputum','Swab','Tissue','CSF','Other'] as $s)
                                    <option value="{{ $s }}">{{ $s }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Price (₹) <span class="text-danger">*</span></label>
                            <input type="number" name="price" id="testPrice" class="form-control" step="0.01" min="0" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Turnaround (hours)</label>
                            <input type="number" name="turnaround_hours" id="testTurnaround" class="form-control" min="1">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Unit of Result</label>
                            <input type="text" name="unit" id="testUnit" class="form-control" placeholder="mg/dL, g/L, %">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Normal Range <small class="text-muted fw-normal">(reference)</small></label>
                            <input type="text" name="normal_range" id="testNormal" class="form-control" placeholder="e.g. 13-17">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Default Normal Value <small class="text-muted fw-normal">(pre-fill on result entry)</small></label>
                            <input type="text" name="normal_value" id="testNormalValue" class="form-control" placeholder="e.g. 15">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Description / Method</label>
                            <textarea name="description" id="testDesc" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Special Instructions</label>
                            <input type="text" name="instructions" id="testInstructions" class="form-control" placeholder="e.g. Fasting required, Collect at 8 AM">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Test</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function togglePriceEdit(id, current) {
    document.querySelector(`.price-display-${id}`).closest('.d-flex').classList.add('d-none');
    document.querySelector(`.price-edit-${id}`).classList.remove('d-none');
    document.getElementById(`price-input-${id}`).focus();
}

function cancelPriceEdit(id) {
    document.querySelector(`.price-edit-${id}`).classList.add('d-none');
    document.querySelector(`.price-display-${id}`).closest('.d-flex').classList.remove('d-none');
}

function savePrice(id) {
    const price = document.getElementById(`price-input-${id}`).value;
    fetch(`/lab/tests/${id}/price`, {
        method: 'PATCH',
        headers: {'Content-Type':'application/json','X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content},
        body: JSON.stringify({price})
    }).then(r => r.json()).then(data => {
        if (data.success) {
            document.querySelector(`.price-display-${id}`).textContent = '₹' + parseFloat(price).toFixed(2);
            cancelPriceEdit(id);
        }
    });
}

function editTest(id) {
    fetch(`/lab/tests/${id}/info`)
        .then(r => r.json())
        .then(test => {
            document.getElementById('testModalTitle').textContent = 'Edit Lab Test';
            document.getElementById('testFormMethod').value = 'PUT';
            document.getElementById('testId').value = test.id;
            document.getElementById('testForm').action = `/lab/tests/${test.id}`;
            document.getElementById('testName').value = test.name;
            document.getElementById('testCode').value = test.short_code ?? '';
            document.getElementById('testCategory').value = test.lab_category_id;
            document.getElementById('testSample').value = test.sample_type ?? '';
            document.getElementById('testPrice').value = test.price;
            document.getElementById('testTurnaround').value = test.turnaround_hours ?? '';
            document.getElementById('testUnit').value = test.unit ?? '';
            document.getElementById('testNormal').value = test.normal_range ?? '';
            document.getElementById('testNormalValue').value = test.normal_value ?? '';
            document.getElementById('testDesc').value = test.description ?? '';
            document.getElementById('testInstructions').value = test.instructions ?? '';
            new bootstrap.Modal(document.getElementById('addTestModal')).show();
        });
}

document.getElementById('addTestModal').addEventListener('show.bs.modal', function(e) {
    if (!e.relatedTarget) return;
    document.getElementById('testModalTitle').textContent = 'Add Lab Test';
    document.getElementById('testFormMethod').value = 'POST';
    document.getElementById('testForm').action = '{{ route("lab.tests.store") }}';
    document.getElementById('testForm').reset();
});
</script>
@endpush
