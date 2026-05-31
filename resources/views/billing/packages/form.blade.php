@extends('layouts.app')
@section('title', isset($package->id) ? 'Edit Package' : 'New Billing Package')
@section('content')

<div class="page-header">
    <div class="page-header-left">
        <a href="{{ route('packages.index') }}" class="btn btn-outline btn-sm me-2"><i class="fa-solid fa-arrow-left"></i></a>
        <h1>{{ isset($package->id) ? 'Edit' : 'New' }} Billing Package</h1>
    </div>
</div>

@if($errors->any())
    <div class="alert alert-danger">
        <ul style="margin:0;padding-left:18px">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif

<form id="pkgForm" method="POST" action="{{ isset($package->id) ? route('packages.update', $package) : route('packages.store') }}">
    @csrf
    @if(isset($package->id)) @method('PUT') @endif

    {{-- Package Details --}}
    <div class="card mb-3">
        <div class="card-header"><i class="fa-solid fa-box me-1"></i> Package Details</div>
        <div class="card-body">
            <div class="form-group">
                <label class="form-label">Package Name <span class="text-danger">*</span></label>
                <input type="text" name="name" value="{{ old('name', $package->name) }}" class="form-control" required>
            </div>
            <div class="grid grid-2">
                <div class="form-group">
                    <label class="form-label">Category</label>
                    <select name="category" class="form-control">
                        <option value="">— None —</option>
                        @foreach(\App\Models\BillingPackage::CATEGORIES as $k => $lbl)
                        <option value="{{ $k }}" {{ old('category', $package->category) === $k ? 'selected' : '' }}>{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Package Price (₹) <span class="text-danger">*</span></label>
                    <input type="number" name="package_price" step="0.01" min="0" value="{{ old('package_price', $package->package_price) }}" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Max Days</label>
                    <input type="number" name="max_days" min="1" value="{{ old('max_days', $package->max_days) }}" class="form-control" placeholder="Leave blank for unlimited">
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <div style="padding-top:8px">
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $package->is_active ?? true) ? 'checked' : '' }}>
                            <span>Active</span>
                        </label>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="description" rows="2" class="form-control">{{ old('description', $package->description) }}</textarea>
            </div>
            <div class="form-group">
                <label class="form-label">What's Included</label>
                <div style="display:flex;flex-wrap:wrap;gap:20px;margin-top:6px">
                    @foreach([['includes_room','Room Charges'],['includes_medicines','Medicines'],['includes_investigations','Investigations'],['includes_ot','OT Charges']] as [$key,$lbl])
                    <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:13px">
                        <input type="hidden" name="{{ $key }}" value="0">
                        <input type="checkbox" name="{{ $key }}" value="1" {{ old($key, $package->$key ?? false) ? 'checked' : '' }}>
                        {{ $lbl }}
                    </label>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- Line items --}}
    <div class="card mb-3">
        <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
            <span><i class="fa-solid fa-list me-1"></i> Itemised Breakdown <small style="color:#999;font-weight:400">(optional)</small></span>
            <button type="button" onclick="addItemRow()" class="btn btn-outline btn-sm"><i class="fa-solid fa-plus me-1"></i> Add Item</button>
        </div>
        <div class="card-body">
            <div id="itemRows">
                @if(isset($package->id) && $package->items->count())
                @foreach($package->items as $i => $item)
                <div class="item-row" style="display:grid;grid-template-columns:3fr 1fr 1fr 80px 100px 30px;gap:8px;align-items:center;margin-bottom:8px">
                    <input type="text" name="items[{{ $i }}][item_name]" value="{{ $item->item_name }}" placeholder="Item name" class="form-control form-control-sm">
                    <select name="items[{{ $i }}][item_type]" class="form-control form-control-sm">
                        @foreach(['service'=>'Service','medicine'=>'Medicine','lab'=>'Lab','room'=>'Room'] as $tk => $tl)
                        <option value="{{ $tk }}" {{ $item->item_type===$tk?'selected':'' }}>{{ $tl }}</option>
                        @endforeach
                    </select>
                    <input type="number" name="items[{{ $i }}][unit_price]" value="{{ $item->unit_price }}" placeholder="Price" step="0.01" min="0" class="form-control form-control-sm">
                    <input type="number" name="items[{{ $i }}][quantity]" value="{{ $item->quantity }}" placeholder="Qty" step="0.01" min="0" class="form-control form-control-sm">
                    <label style="display:flex;align-items:center;gap:4px;font-size:12px;cursor:pointer">
                        <input type="hidden" name="items[{{ $i }}][is_included]" value="0">
                        <input type="checkbox" name="items[{{ $i }}][is_included]" value="1" {{ $item->is_included ? 'checked' : '' }}>
                        Included
                    </label>
                    <button type="button" onclick="this.closest('.item-row').remove()" class="btn btn-icon btn-sm btn-outline" style="color:#94a3b8"><i class="fa-solid fa-times"></i></button>
                </div>
                @endforeach
                @endif
            </div>
            <p style="font-size:11px;color:#999;margin-top:8px">Un-checked items are extra charges billed on top of the package price.</p>
        </div>
    </div>

    <div style="display:flex;gap:10px">
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-1"></i> {{ isset($package->id) ? 'Update' : 'Create' }} Package</button>
        <a href="{{ route('packages.index') }}" class="btn btn-outline">Cancel</a>
    </div>
</form>

@push('scripts')
<script>
let itemIdx = {{ isset($package->id) ? $package->items->count() : 0 }};
function addItemRow() {
    const i = itemIdx++;
    const html = `<div class="item-row" style="display:grid;grid-template-columns:3fr 1fr 1fr 80px 100px 30px;gap:8px;align-items:center;margin-bottom:8px">
        <input type="text" name="items[${i}][item_name]" placeholder="Item name" class="form-control form-control-sm">
        <select name="items[${i}][item_type]" class="form-control form-control-sm">
            <option value="service">Service</option><option value="medicine">Medicine</option>
            <option value="lab">Lab</option><option value="room">Room</option>
        </select>
        <input type="number" name="items[${i}][unit_price]" placeholder="Price" step="0.01" min="0" class="form-control form-control-sm">
        <input type="number" name="items[${i}][quantity]" placeholder="Qty" value="1" step="0.01" min="0" class="form-control form-control-sm">
        <label style="display:flex;align-items:center;gap:4px;font-size:12px;cursor:pointer">
            <input type="hidden" name="items[${i}][is_included]" value="0">
            <input type="checkbox" name="items[${i}][is_included]" value="1" checked> Included
        </label>
        <button type="button" onclick="this.closest('.item-row').remove()" class="btn btn-icon btn-sm btn-outline" style="color:#94a3b8"><i class="fa-solid fa-times"></i></button>
    </div>`;
    document.getElementById('itemRows').insertAdjacentHTML('beforeend', html);
}
</script>
@endpush
@endsection
