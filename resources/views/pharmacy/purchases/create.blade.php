@extends('layouts.app')
@section('title','New Stock Receipt')
@section('page-title','New Stock Receipt')
@section('content')

@include('pharmacy._toolbar')

<div class="page-header">
    <div class="page-header-left">
        <a href="{{ route('pharmacy.purchases.index') }}" class="text-small"><i class="fa-solid fa-arrow-left"></i> Purchases</a>
        <h1><i class="fa-solid fa-truck-ramp-box"></i> New Stock Receipt</h1>
        <div class="text-small text-muted">Add medicines you received. Tax is calculated automatically. Stock updates + Accounts voucher posts on save.</div>
    </div>
</div>

@if($errors->any())<div class="alert alert-error"><ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

<form method="POST" action="{{ route('pharmacy.purchases.store') }}" id="receiptForm">
    @csrf

    <div class="card" style="padding:16px;margin-bottom:14px">
        <div style="display:grid;grid-template-columns:2fr 1fr 1fr 1fr;gap:12px">
            <div>
                <label>Supplier *</label>
                <select name="supplier_ledger_id" class="form-control" required>
                    <option value="">— select supplier —</option>
                    @foreach($suppliers as $s)
                        <option value="{{ $s->id }}" data-state="{{ $s->state_code }}" {{ (int) old('supplier_ledger_id', $defaultSupplierId) === $s->id ? 'selected' : '' }}>{{ $s->name }} @if($s->state_code)({{ $s->state_code }})@endif</option>
                    @endforeach
                </select>
                <div class="text-small text-muted" style="margin-top:4px">Don't see your supplier? <a href="{{ route('accounts.ledgers.create') }}" target="_blank">Add one</a> (Party Type = Supplier).</div>
            </div>
            <div>
                <label>Invoice #</label>
                <input name="invoice_no" value="{{ old('invoice_no') }}" class="form-control" placeholder="optional">
            </div>
            <div>
                <label>Invoice Date *</label>
                <input type="date" name="invoice_date" value="{{ old('invoice_date', $today) }}" class="form-control" required>
            </div>
            <div>
                <label>Payment Mode *</label>
                <select name="payment_mode" class="form-control" required>
                    <option value="Credit" {{ old('payment_mode')==='Credit'?'selected':'' }}>Credit (owe supplier)</option>
                    <option value="Cash"   {{ old('payment_mode')==='Cash'  ?'selected':'' }}>Cash (paid)</option>
                    <option value="Bank"   {{ old('payment_mode')==='Bank'  ?'selected':'' }}>Bank (paid)</option>
                </select>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
            <strong>Line items</strong>
            <button type="button" class="btn btn-sm btn-outline" id="addLine"><i class="fa-solid fa-plus"></i> Add line</button>
        </div>
        <div class="table-wrap">
            <table class="sc-table" id="itemsTbl" style="margin:0">
                <thead>
                    <tr>
                        <th>Medicine *</th>
                        <th style="width:90px">Batch</th>
                        <th style="width:130px">Expiry</th>
                        <th style="width:90px;text-align:right">Qty *</th>
                        <th style="width:110px;text-align:right">Rate *</th>
                        <th style="width:140px">HSN</th>
                        <th style="width:110px;text-align:right">GST %</th>
                        <th style="width:120px;text-align:right">Line Total</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="itemsBody"></tbody>
                <tfoot>
                    <tr>
                        <td colspan="7" class="text-right fw-600">Subtotal</td>
                        <td class="text-right mono" id="totSubtotal">₹0.00</td><td></td>
                    </tr>
                    <tr>
                        <td colspan="7" class="text-right fw-600">CGST + SGST <span class="text-muted text-small" id="taxLabel">(intra-state)</span></td>
                        <td class="text-right mono" id="totGst">₹0.00</td><td></td>
                    </tr>
                    <tr style="background:#f0f0f0">
                        <td colspan="7" class="text-right fw-700">GRAND TOTAL</td>
                        <td class="text-right mono fw-700" id="totGrand">₹0.00</td><td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div style="margin-top:14px;display:flex;gap:8px">
        <button class="btn btn-primary"><i class="fa-solid fa-check"></i> Save Receipt &amp; Post to Accounts</button>
        <a href="{{ route('pharmacy.purchases.index') }}" class="btn btn-outline">Cancel</a>
    </div>
</form>

{{-- Inline "add medicine" modal: opens when user clicks + next to a medicine dropdown --}}
<dialog id="addMedDialog" style="border:1px solid #ddd;border-radius:8px;padding:0;max-width:520px;width:90vw">
    <form id="addMedForm" style="padding:18px">
        <h3 style="margin:0 0 12px 0"><i class="fa-solid fa-pills"></i> Add Medicine</h3>
        <div class="text-small text-muted" style="margin-bottom:10px">Saves with HSN <code>30049099</code> + GST 12% defaults. You can refine later from <strong>Pharmacy → Fix HSN/GST</strong>.</div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
            <div style="grid-column:span 2">
                <label>Name *</label>
                <input name="name" class="form-control" required autofocus placeholder="e.g., Crocin">
            </div>
            <div>
                <label>Strength</label>
                <input name="strength" class="form-control" placeholder="e.g., 500mg">
            </div>
            <div>
                <label>Form *</label>
                <select name="dosage_form" class="form-control" required>
                    @foreach(['Tab','Cap','Syrup','Inj','Drops','Cream','Ointment','Powder','Other'] as $f)
                        <option value="{{ $f }}">{{ $f }}</option>
                    @endforeach
                </select>
            </div>
            <div style="grid-column:span 2">
                <label>Category</label>
                <select name="medicine_category_id" class="form-control">
                    <option value="">— optional —</option>
                    @foreach($categories as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
                </select>
            </div>
            <div style="grid-column:span 2">
                <label>Generic name (optional)</label>
                <input name="generic_name" class="form-control" placeholder="e.g., Paracetamol">
            </div>
        </div>
        <div id="addMedError" class="alert alert-error" style="display:none;margin-top:10px"></div>
        <div style="margin-top:14px;display:flex;gap:8px;justify-content:flex-end">
            <button type="button" class="btn btn-outline" id="addMedCancel">Cancel</button>
            <button type="submit" class="btn btn-primary" id="addMedSubmit"><i class="fa-solid fa-plus"></i> Add &amp; Use</button>
        </div>
    </form>
</dialog>

@php
    // Trimmed payload for the JS template + medicine→HSN/GST lookup
    $meds = $medicines->map(fn($m) => [
        'id'   => $m->id,
        'name' => $m->name . ($m->strength ? ' '.$m->strength : ''),
        'hsn'  => $m->hsn_code ?: '30049099',
        'gst'  => (float) ($m->gstRate->rate ?? 0),
    ]);
    $clinicState = setting('clinic_state_code');
@endphp

<script>
    const MEDS = @json($meds);
    const CLINIC_STATE = @json($clinicState);

    function fmtINR(n) { return '₹' + (Math.round(n*100)/100).toFixed(2); }
    let lineIdx = 0;

    function medicineOptionsHtml() {
        return '<option value="">— pick medicine —</option>'
             + MEDS.map(m => `<option value="${m.id}" data-hsn="${m.hsn}" data-gst="${m.gst}">${m.name}</option>`).join('')
             ;
    }

    function refreshAllDropdowns(autoSelectId) {
        // Append the latest options to every existing medicine dropdown, preserving each row's current selection
        document.querySelectorAll('.medicine-select').forEach(sel => {
            const prev = sel.value;
            sel.innerHTML = medicineOptionsHtml();
            if (autoSelectId && sel === window.__addMedTargetSelect) {
                sel.value = String(autoSelectId);
                sel.dispatchEvent(new Event('change'));
            } else if (prev && prev !== '__ADD__') {
                sel.value = prev;
            }
        });
    }

    function addLine() {
        const tr = document.createElement('tr');
        const i = lineIdx++;
        tr.innerHTML = `
            <td>
                <select name="items[${i}][medicine_id]" class="form-control medicine-select" required>
                    ${medicineOptionsHtml()}
                </select>
                <div class="text-small text-muted" style="margin-top:4px">Don't see the medicine? <a href="#" class="addMedLink">Add one</a></div>
            </td>
            <td><input name="items[${i}][batch_number]" class="form-control mono"></td>
            <td><input type="date" name="items[${i}][expiry_date]" class="form-control"></td>
            <td><input type="number" step="0.01" min="0.01" name="items[${i}][quantity]" class="form-control mono text-right qty" required></td>
            <td><input type="number" step="0.01" min="0" name="items[${i}][unit_cost]" class="form-control mono text-right rate" required></td>
            <td><input name="items[${i}][hsn_code]" class="form-control mono hsn" value="30049099"></td>
            <td><input type="number" step="0.01" min="0" max="50" name="items[${i}][gst_rate]" class="form-control mono text-right gst" value="0"></td>
            <td class="text-right mono lineTotal">₹0.00</td>
            <td><button type="button" class="btn btn-sm btn-outline removeLine" style="color:var(--sc-red);border-color:var(--sc-red)"><i class="fa-solid fa-times"></i></button></td>
        `;
        document.getElementById('itemsBody').appendChild(tr);
        wireRow(tr);
    }

    function wireRow(tr) {
        const sel = tr.querySelector('.medicine-select');
        const qty = tr.querySelector('.qty');
        const rate = tr.querySelector('.rate');
        const hsn = tr.querySelector('.hsn');
        const gst = tr.querySelector('.gst');

        sel.addEventListener('change', () => {
            const opt = sel.options[sel.selectedIndex];
            if (opt && opt.dataset.hsn) hsn.value = opt.dataset.hsn;
            if (opt && opt.dataset.gst !== undefined) gst.value = opt.dataset.gst;
            recalc();
        });
        [qty, rate, gst].forEach(el => el.addEventListener('input', recalc));
        tr.querySelector('.removeLine').addEventListener('click', () => { tr.remove(); recalc(); });
        tr.querySelector('.addMedLink').addEventListener('click', (e) => {
            e.preventDefault();
            window.__addMedTargetSelect = sel;
            openAddMedDialog();
        });
    }

    function recalc() {
        const supplierSel = document.querySelector('select[name="supplier_ledger_id"]');
        const supplierState = supplierSel.selectedOptions[0]?.dataset.state || null;
        const intra = !supplierState || (CLINIC_STATE && supplierState.trim() === CLINIC_STATE.trim());
        document.getElementById('taxLabel').textContent = intra ? '(intra-state CGST + SGST)' : '(inter-state IGST)';

        let sub = 0, tax = 0;
        document.querySelectorAll('#itemsBody tr').forEach(tr => {
            const q = parseFloat(tr.querySelector('.qty').value) || 0;
            const r = parseFloat(tr.querySelector('.rate').value) || 0;
            const g = parseFloat(tr.querySelector('.gst').value) || 0;
            const taxable = q * r;
            const t = taxable * (g / 100);
            sub += taxable;
            tax += t;
            tr.querySelector('.lineTotal').textContent = fmtINR(taxable + t);
        });
        document.getElementById('totSubtotal').textContent = fmtINR(sub);
        document.getElementById('totGst').textContent      = fmtINR(tax);
        document.getElementById('totGrand').textContent    = fmtINR(sub + tax);
    }

    document.getElementById('addLine').addEventListener('click', addLine);
    document.querySelector('select[name="supplier_ledger_id"]').addEventListener('change', recalc);
    addLine(); // start with one empty row

    // ── Inline "add medicine" modal wiring ──────────────────────────────
    const dlg     = document.getElementById('addMedDialog');
    const dlgForm = document.getElementById('addMedForm');
    const dlgErr  = document.getElementById('addMedError');
    const dlgBtn  = document.getElementById('addMedSubmit');
    const QUICK_CREATE_URL = @json(route('pharmacy.medicines.quick-create'));
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content
              || document.querySelector('input[name="_token"]')?.value;

    function openAddMedDialog() {
        dlgErr.style.display = 'none';
        dlgForm.reset();
        if (typeof dlg.showModal === 'function') dlg.showModal();
        else dlg.setAttribute('open', '');
        setTimeout(() => dlgForm.querySelector('[name="name"]').focus(), 50);
    }
    function closeAddMedDialog() {
        if (typeof dlg.close === 'function') dlg.close();
        else dlg.removeAttribute('open');
        window.__addMedTargetSelect = null;
    }

    document.getElementById('addMedCancel').addEventListener('click', closeAddMedDialog);
    dlg.addEventListener('cancel', closeAddMedDialog);

    dlgForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        dlgErr.style.display = 'none';
        dlgBtn.disabled = true;
        dlgBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving…';

        const fd = new FormData(dlgForm);
        try {
            const resp = await fetch(QUICK_CREATE_URL, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: fd,
            });
            const data = await resp.json();
            if (!resp.ok) {
                const msg = data.message || Object.values(data.errors || {}).flat().join(' ') || 'Save failed';
                throw new Error(msg);
            }
            // Append to global MEDS so future rows include it too
            MEDS.push({ id: data.id, name: data.name, hsn: data.hsn, gst: data.gst });
            refreshAllDropdowns(data.id);
            closeAddMedDialog();
        } catch (err) {
            dlgErr.textContent = err.message;
            dlgErr.style.display = 'block';
        } finally {
            dlgBtn.disabled = false;
            dlgBtn.innerHTML = '<i class="fa-solid fa-plus"></i> Add &amp; Use';
        }
    });
</script>

@endsection
