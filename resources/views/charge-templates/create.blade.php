@extends('layouts.app')
@section('title','New Charge Template')
@section('page-title','New Charge Template')
@section('content')

<div class="page-header">
    <div class="page-header-left"><h1>New Charge Template</h1></div>
    <a href="{{ route('charge-templates.index') }}" class="btn btn-outline">Back</a>
    ('templates._toolbar', ['inline'=>true])
</div>

@if($errors->any())
<div class="alert alert-danger"><ul style="margin:0;padding-left:16px">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
@endif

<form method="POST" action="{{ route('charge-templates.store') }}">
@csrf
<div class="grid" style="grid-template-columns:1fr 300px;gap:20px;align-items:start">
    <div>
        <div class="card mb-3">
            <div class="card-header"><i class="fa-solid fa-info-circle"></i> Template Info</div>
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label">Template Name <span class="req">*</span></label>
                    <input type="text" name="name" class="form-control" value="{{ old('name') }}" required placeholder="e.g. Normal Delivery Package, Appendicectomy">
                </div>
                <div class="form-group" style="margin:0">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="2" placeholder="Brief description of this package">{{ old('description') }}</textarea>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <i class="fa-solid fa-list"></i> Charge Items
                <div class="card-actions">
                    <button type="button" class="btn btn-sm btn-primary" onclick="addItem()"><i class="fa-solid fa-plus"></i> Add Item</button>
                </div>
            </div>
            <div class="card-body">
                <div id="itemsContainer"></div>
                <div id="noItems" class="text-center text-muted" style="padding:30px">Click "Add Item" to start building the template.</div>
            </div>
        </div>
    </div>

    <div>
        <div class="card mb-3">
            <div class="card-header"><i class="fa-solid fa-calculator"></i> Summary</div>
            <div class="card-body">
                <div id="summaryList" style="font-size:13px;display:flex;flex-direction:column;gap:6px"></div>
                <div style="border-top:1px solid var(--sc-border);margin-top:10px;padding-top:10px;display:flex;justify-content:space-between;font-weight:700">
                    <span>Total</span>
                    <span id="summaryTotal" class="mono">₹0.00</span>
                </div>
            </div>
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%"><i class="fa-solid fa-floppy-disk"></i> Save Template</button>
    </div>
</div>
</form>

<script>
const CATEGORIES = @json($categories);
let itemCount = 0;

function addItem() {
    document.getElementById('noItems').style.display = 'none';
    const i = itemCount++;
    const div = document.createElement('div');
    div.id = `item_${i}`;
    div.style.cssText = 'display:grid;grid-template-columns:1fr 2fr 1fr 1fr auto;gap:10px;align-items:flex-end;margin-bottom:12px;padding:12px;background:var(--sc-bg);border-radius:8px;border:1px solid var(--sc-border)';
    div.innerHTML = `
        <div class="form-group" style="margin:0">
            <label class="form-label">Category</label>
            <select name="items[${i}][category]" class="form-control" required onchange="recalc()">
                ${Object.entries(CATEGORIES).map(([v,m])=>`<option value="${v}">${m.label}</option>`).join('')}
            </select>
        </div>
        <div class="form-group" style="margin:0">
            <label class="form-label">Description</label>
            <input type="text" name="items[${i}][description]" class="form-control" required placeholder="e.g. Delivery charges" oninput="recalc()">
        </div>
        <div class="form-group" style="margin:0">
            <label class="form-label">Unit Price</label>
            <input type="number" name="items[${i}][unit_price]" class="form-control" required step="0.01" min="0" placeholder="0.00" oninput="recalc()">
        </div>
        <div class="form-group" style="margin:0">
            <label class="form-label">Qty</label>
            <input type="number" name="items[${i}][quantity]" class="form-control" required step="0.01" min="0.01" value="1" oninput="recalc()">
        </div>
        <button type="button" onclick="this.closest('[id^=item_]').remove();recalc()" class="btn btn-sm btn-outline" style="color:var(--sc-red);border-color:var(--sc-red);margin-bottom:0"><i class="fa-solid fa-trash"></i></button>
    `;
    document.getElementById('itemsContainer').appendChild(div);
    recalc();
}

function recalc() {
    let total = 0;
    const rows = document.querySelectorAll('#itemsContainer [id^=item_]');
    const summary = document.getElementById('summaryList');
    summary.innerHTML = '';
    rows.forEach(row => {
        const desc = row.querySelector('[name$="[description]"]')?.value || '';
        const price = parseFloat(row.querySelector('[name$="[unit_price]"]')?.value) || 0;
        const qty   = parseFloat(row.querySelector('[name$="[quantity]"]')?.value) || 0;
        const amt   = price * qty;
        total += amt;
        if (desc) summary.innerHTML += `<div style="display:flex;justify-content:space-between"><span>${desc}</span><span class="mono">₹${amt.toLocaleString('en-IN',{minimumFractionDigits:2})}</span></div>`;
    });
    document.getElementById('summaryTotal').textContent = '₹' + total.toLocaleString('en-IN',{minimumFractionDigits:2});
    document.getElementById('noItems').style.display = rows.length ? 'none' : '';
}

addItem(); // start with one row
</script>
@endsection
