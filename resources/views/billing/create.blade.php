{{-- FILE: resources/views/billing/create.blade.php --}}
@extends('layouts.app')
@section('title','Create Bill')
@section('page-title','Create Bill')
@section('content')
<div class="page-header">
    <div class="page-header-left"><h1>Create New Bill</h1></div>
</div>

<form method="POST" action="{{ route('billing.store') }}" id="bill-form">
@csrf
@if($patient)
<input type="hidden" name="patient_id" value="{{ $patient->id }}">
@endif
@if($encounter ?? null)
<input type="hidden" name="encounter_id" value="{{ $encounter->id }}">
@endif

@if($encounter ?? null)
<div class="alert alert-info mb-3" style="display:flex;align-items:center;gap:10px">
    <i class="fa-solid fa-link"></i>
    <span>Billing for encounter <strong class="mono">{{ $encounter->encounter_no }}</strong> — {{ $encounter->encounter_date->format('d M Y') }} &nbsp;·&nbsp; {{ $encounter->visit_type }}.
    Consultation and lab fees have been pre-filled.</span>
    <a href="{{ route('encounters.show', $encounter) }}" class="btn btn-sm btn-outline" style="margin-left:auto">View Encounter</a>
</div>
@endif

<div class="grid" style="grid-template-columns:1fr 340px;gap:20px;align-items:start">
<div>

<!-- Patient Selection -->
@if(!$patient)
<div class="card mb-3">
    <div class="card-header"><i class="fa-solid fa-user-search"></i> Select Patient</div>
    <div class="card-body">
        <select name="patient_id" id="patient-select" class="form-control select2" required style="width:100%">
            <option value="">— Search by name, JHC ID or phone —</option>
            @foreach($patients as $p)
            <option value="{{ $p->id }}"
                data-jhc="{{ $p->jhc_id }}"
                data-phone="{{ $p->phone }}"
                {{ old('patient_id') == $p->id ? 'selected' : '' }}>
                {{ $p->first_name }} {{ $p->last_name }} · {{ $p->jhc_id }} · {{ $p->phone }}
            </option>
            @endforeach
        </select>
        @error('patient_id')<div class="text-danger" style="font-size:12px;margin-top:4px">{{ $message }}</div>@enderror
    </div>
</div>
@else
<div class="patient-strip mb-3">
    <div class="ps-avatar">{{ strtoupper(substr($patient->first_name,0,1)) }}</div>
    <div><div class="ps-name">{{ $patient->full_name }}</div><div class="ps-jhc_id">{{ $patient->jhc_id }}</div>
    <div class="ps-meta"><span><i class="fa-solid fa-calendar"></i> {{ $patient->age }}</span><span>{{ $patient->gender }}</span></div></div>
</div>
@endif

<!-- Bill Items -->
<div class="card mb-3">
    <div class="card-header">
        <i class="fa-solid fa-list"></i> Bill Items
        <div class="card-actions">
            <button type="button" class="btn btn-sm btn-primary" onclick="openPackageModal()">
                <i class="fa-solid fa-box"></i> Apply Package
            </button>
            <button type="button" class="btn btn-sm btn-outline" onclick="addBillItem()"><i class="fa-solid fa-plus"></i> Add Item</button>
        </div>
    </div>
    <input type="hidden" name="billing_package_id" id="billing-package-id" value="">
    <div id="package-applied-banner" style="display:none;padding:10px 16px;background:rgba(10,180,180,.08);border-bottom:1px solid var(--sc-border);font-size:12px;display:none;align-items:center;gap:10px">
        <i class="fa-solid fa-box" style="color:var(--sc-teal)"></i>
        <span>Package applied: <strong id="package-applied-name">—</strong> at <strong id="package-applied-price">—</strong></span>
        <button type="button" class="btn btn-icon btn-sm btn-outline" style="margin-left:auto;color:var(--sc-red);border-color:var(--sc-red)" onclick="clearPackage()" title="Remove package — keeps items, just untags the bill">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>
    <div class="card-body">
        <!-- Quick Add Buttons -->
        <div style="margin-bottom:16px">
            <div style="font-size:11px;font-weight:700;color:var(--sc-muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px">Quick Add:</div>
            <div style="display:flex;flex-wrap:wrap;gap:6px">
                @foreach($services as $category => $items)
                <div style="display:flex;flex-wrap:wrap;gap:4px">
                    @foreach($items->take(4) as $svc)
                    <button type="button" class="btn btn-outline btn-sm" onclick="quickAddService('{{ addslashes($svc->name) }}', '{{ $svc->category }}', {{ $svc->price }})">
                        {{ $svc->name }} (₹{{ number_format($svc->price) }})
                    </button>
                    @endforeach
                </div>
                @endforeach
            </div>
        </div>

        <div id="bill-items-container">
            <!-- Items will be added here -->
        </div>
        <div id="no-items" class="text-center text-muted" style="padding:20px;background:var(--sc-bg);border-radius:8px">
            No items added yet. Click "Add Item" or use Quick Add buttons.
        </div>
    </div>
</div>
</div>

<!-- SUMMARY SIDEBAR -->
<div>
    <div class="card" style="position:sticky;top:84px">
        <div class="card-header"><i class="fa-solid fa-calculator"></i> Bill Summary</div>
        <div class="card-body">
            <table style="width:100%;border-collapse:collapse;margin-bottom:16px">
                <tr><td style="padding:6px 0;color:var(--sc-muted)">Subtotal</td><td style="text-align:right;font-weight:600" id="subtotal-display">₹0.00</td></tr>
                <tr>
                    <td style="padding:6px 0;color:var(--sc-muted)">Discount %</td>
                    <td style="text-align:right">
                        <input type="number" name="discount_percent" id="discount-pct" class="form-control" style="width:80px;text-align:right;padding:4px 8px" value="0" min="0" max="100" step="0.5" oninput="recalculate()">
                    </td>
                </tr>
                <tr><td style="padding:6px 0;color:var(--sc-red)">Discount Amt</td><td style="text-align:right;color:var(--sc-red)" id="discount-amt-display">-₹0.00</td></tr>
                <tr style="border-top:2px solid var(--sc-border)">
                    <td style="padding:10px 0;font-weight:700;font-size:16px">Total</td>
                    <td style="text-align:right;font-weight:700;font-size:16px;color:var(--sc-navy)" id="total-display">₹0.00</td>
                </tr>
            </table>

            {{-- Insurance / TPA --}}
            <div class="form-group">
                <label class="form-label">Insurance Company <span style="font-size:11px;color:var(--sc-muted)">(if applicable)</span></label>
                <input type="text" name="insurance_company" class="form-control" value="{{ old('insurance_company', $patient?->insurance_company) }}" placeholder="e.g. Star Health">
            </div>
            <div class="form-group">
                <label class="form-label">TPA Ref / Claim No.</label>
                <input type="text" name="tpa_ref" class="form-control" value="{{ old('tpa_ref') }}" placeholder="Claim or pre-auth number">
            </div>
            <div class="form-group">
                <label class="form-label">Insurance Amount (₹) <span style="font-size:11px;color:var(--sc-muted)">(covered by insurer)</span></label>
                <input type="number" name="insurance_amount" class="form-control" value="{{ old('insurance_amount', 0) }}" step="0.01" min="0">
            </div>

            <div class="form-group">
                <label class="form-label">Payment Method</label>
                <select name="payment_method" class="form-control">
                    <option value="">Select Method</option>
                    <option value="Cash">Cash</option>
                    <option value="Card">Card (Debit/Credit)</option>
                    <option value="UPI">UPI</option>
                    <option value="NEFT">NEFT / Bank Transfer</option>
                    <option value="Cheque">Cheque</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Amount Paid (₹)</label>
                <input type="number" name="paid_amount" id="paid-amount" class="form-control" value="0" step="0.01" min="0" oninput="recalculate()">
            </div>

            <div style="background:var(--sc-bg);border-radius:8px;padding:12px;margin-bottom:16px">
                <div style="display:flex;justify-content:space-between;font-size:13px">
                    <span>Due Amount</span>
                    <strong id="due-display" style="color:var(--sc-red)">₹0.00</strong>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-control" rows="2"></textarea>
            </div>

            <button type="submit" class="btn btn-primary btn-lg" style="width:100%;justify-content:center"><i class="fa-solid fa-file-invoice"></i> Generate Bill</button>
        </div>
    </div>
</div>
</div>
</form>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('#patient-select').select2({
        theme: 'classic',
        placeholder: '— Search by name, JHC ID or phone —',
        allowClear: true,
        width: '100%',
    });
});

let itemCount = 0;
const billItems = [];

function addBillItem(name='', type='Service', price=0) {
    itemCount++;
    const idx = itemCount;
    document.getElementById('no-items').style.display = 'none';
    const c = document.getElementById('bill-items-container');
    const div = document.createElement('div');
    div.id = `bill-row-${idx}`;
    div.style.cssText = 'display:grid;grid-template-columns:2fr 1fr 1fr 1fr auto;gap:8px;align-items:end;padding:10px;background:var(--sc-bg);border-radius:8px;margin-bottom:8px;border:1px solid var(--sc-border)';
    div.innerHTML = `
        <div>
            <label style="font-size:11px;font-weight:600;color:var(--sc-muted);display:block;margin-bottom:3px">Item Name</label>
            <input type="text" name="items[${idx}][name]" class="form-control" value="${name}" placeholder="Service / Medicine name..." required>
            <input type="hidden" name="items[${idx}][type]" value="${type}">
        </div>
        <div>
            <label style="font-size:11px;font-weight:600;color:var(--sc-muted);display:block;margin-bottom:3px">Qty</label>
            <input type="number" name="items[${idx}][qty]" class="form-control" value="1" min="1" onchange="recalculate()" oninput="recalculate()">
        </div>
        <div>
            <label style="font-size:11px;font-weight:600;color:var(--sc-muted);display:block;margin-bottom:3px">Unit Price (₹)</label>
            <input type="number" name="items[${idx}][price]" class="form-control" value="${price}" step="0.01" min="0" onchange="recalculate()" oninput="recalculate()">
        </div>
        <div>
            <label style="font-size:11px;font-weight:600;color:var(--sc-muted);display:block;margin-bottom:3px">Discount (₹)</label>
            <input type="number" name="items[${idx}][discount]" class="form-control" value="0" step="0.01" min="0" onchange="recalculate()" oninput="recalculate()">
        </div>
        <div style="padding-bottom:1px">
            <button type="button" onclick="document.getElementById('bill-row-${idx}').remove();recalculate();" class="btn btn-danger btn-icon"><i class="fa-solid fa-trash"></i></button>
        </div>
    `;
    c.appendChild(div);
    recalculate();
}

function quickAddService(name, type, price) {
    addBillItem(name, type, price);
}

function recalculate() {
    let subtotal = 0;
    document.querySelectorAll('[id^="bill-row-"]').forEach(row => {
        const qty = parseFloat(row.querySelector('[name$="[qty]"]')?.value || 1);
        const price = parseFloat(row.querySelector('[name$="[price]"]')?.value || 0);
        const disc = parseFloat(row.querySelector('[name$="[discount]"]')?.value || 0);
        subtotal += (qty * price) - disc;
    });

    const discPct = parseFloat(document.getElementById('discount-pct').value || 0);
    const discAmt = subtotal * discPct / 100;
    const total = Math.max(0, subtotal - discAmt);
    const paid = parseFloat(document.getElementById('paid-amount').value || 0);
    const due = Math.max(0, total - paid);

    document.getElementById('subtotal-display').textContent = '₹' + subtotal.toFixed(2);
    document.getElementById('discount-amt-display').textContent = '-₹' + discAmt.toFixed(2);
    document.getElementById('total-display').textContent = '₹' + total.toFixed(2);
    document.getElementById('due-display').textContent = '₹' + due.toFixed(2);
    document.getElementById('due-display').style.color = due > 0 ? 'var(--sc-red)' : 'var(--sc-green)';
}


@if(!empty($encounterItems))
// Auto-populate bill items from encounter
document.addEventListener('DOMContentLoaded', function() {
    const items = @json($encounterItems);
    items.forEach(item => addBillItem(item.name, item.type, item.price));
});
@endif

// ── PACKAGE BILLING ──
function openPackageModal() {
    document.getElementById('package-modal').classList.add('open');
    fetchPackages();
}
function closePackageModal() { document.getElementById('package-modal').classList.remove('open'); }

function fetchPackages() {
    const list = document.getElementById('package-list');
    list.innerHTML = '<div style="padding:24px;text-align:center;color:var(--sc-muted)"><i class="fa-solid fa-spinner fa-spin"></i> Loading packages…</div>';
    $.get('{{ route("packages.api") }}', {}, function(data) {
        if (!data.length) {
            list.innerHTML = '<div style="padding:30px;text-align:center;color:var(--sc-muted)">No active packages. <a href="{{ route("packages.create") }}" style="color:var(--sc-teal)">Create one →</a></div>';
            return;
        }
        list.innerHTML = '';
        data.forEach(p => {
            const incBits = [];
            if (p.includes_room) incBits.push('Room');
            if (p.includes_medicines) incBits.push('Medicines');
            if (p.includes_investigations) incBits.push('Investigations');
            if (p.includes_ot) incBits.push('OT');
            const card = document.createElement('div');
            card.style.cssText = 'border:1px solid var(--sc-border);border-radius:8px;padding:14px;margin-bottom:10px;cursor:pointer;transition:all .15s';
            card.onmouseenter = () => { card.style.borderColor = 'var(--sc-teal)'; card.style.background = 'rgba(10,180,180,.04)'; };
            card.onmouseleave = () => { card.style.borderColor = 'var(--sc-border)'; card.style.background = ''; };
            card.innerHTML = `
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px">
                    <div style="font-weight:700;font-size:14px">${escapeHtml(p.name)}${p.category ? ` <span class="badge badge-outline" style="margin-left:6px">${escapeHtml(p.category)}</span>` : ''}</div>
                    <div style="font-weight:700;color:var(--sc-navy);font-size:14px">₹${Number(p.package_price).toLocaleString('en-IN')}</div>
                </div>
                <div style="font-size:11.5px;color:var(--sc-muted)">
                    ${incBits.length ? `Includes: ${incBits.join(', ')}` : 'See itemised breakdown'}${p.max_days ? ` &middot; max ${p.max_days} day${p.max_days>1?'s':''}` : ''}
                </div>
            `;
            card.addEventListener('click', () => applyPackage(p));
            list.appendChild(card);
        });
    });
}

function applyPackage(pkg) {
    if (!confirm(`Apply package "${pkg.name}" at ₹${Number(pkg.package_price).toLocaleString('en-IN')}?\n\nThis replaces the current bill items with the package contents.`)) return;

    // Clear existing items
    document.querySelectorAll('[id^="bill-row-"]').forEach(el => el.remove());
    document.getElementById('no-items').style.display = '';

    fetch('{{ url("/packages") }}/' + pkg.id + '/items', {
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
    }).then(r => r.json()).then(data => {
        const items = (data.items || []);
        const includedTotal = items.filter(i => i.is_included).reduce((s, i) => s + (i.unit_price * i.quantity), 0);
        const headlinePrice = parseFloat(data.package_price) || 0;
        // Adjustment line so the included items net to the package's headline price (in case item line totals don't sum exactly).
        const includedAdjust = headlinePrice - includedTotal;

        if (!items.length) {
            // Package has no itemised breakdown — single summary line at the headline price.
            addBillItem(pkg.name + ' (Package)', 'Package', headlinePrice);
        } else {
            // Add itemised lines. Included items as-is; un-included as extras.
            items.forEach(i => {
                const label = i.item_name + (i.is_included ? '' : ' (Extra)');
                const type  = (i.item_type || 'service').charAt(0).toUpperCase() + (i.item_type || 'service').slice(1);
                addBillItemFull({
                    name:     label,
                    type:     type,
                    qty:      i.quantity || 1,
                    price:    i.unit_price || 0,
                    discount: 0,
                });
            });
            // Apply package adjustment to align with headline price (positive or negative)
            if (Math.abs(includedAdjust) > 0.005) {
                addBillItemFull({
                    name:     pkg.name + ' — Package Adjustment',
                    type:     'Package',
                    qty:      1,
                    price:    includedAdjust,
                    discount: 0,
                });
            }
        }
        document.getElementById('billing-package-id').value = pkg.id;
        const banner = document.getElementById('package-applied-banner');
        banner.style.display = 'flex';
        document.getElementById('package-applied-name').textContent = pkg.name;
        document.getElementById('package-applied-price').textContent = '₹' + headlinePrice.toLocaleString('en-IN');
        closePackageModal();
        recalculate();
    }).catch(() => {
        alert('Failed to load package details. Please try again.');
    });
}

function addBillItemFull(o) {
    addBillItem(o.name || '', o.type || 'Service', o.price || 0);
    const last = document.querySelectorAll('[id^="bill-row-"]');
    const row  = last[last.length - 1];
    if (!row) return;
    row.querySelector('[name$="[qty]"]').value      = o.qty      || 1;
    row.querySelector('[name$="[discount]"]').value = o.discount || 0;
    recalculate();
}

function clearPackage() {
    document.getElementById('billing-package-id').value = '';
    document.getElementById('package-applied-banner').style.display = 'none';
}

function escapeHtml(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}
</script>

{{-- Package picker modal --}}
<div id="package-modal" class="modal-overlay">
    <div class="modal-box" style="max-width:560px">
        <div class="modal-header">
            <i class="fa-solid fa-box" style="color:var(--sc-teal)"></i>
            <h3>Apply a Billing Package</h3>
            <button class="modal-close" onclick="closePackageModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div style="font-size:12px;color:var(--sc-muted);margin-bottom:10px">
                Pick a package to apply its quoted price to this bill. The bill is tagged with the package for reporting; you can still add extra line items afterwards.
            </div>
            <div id="package-list"></div>
        </div>
    </div>
</div>
@endpush
