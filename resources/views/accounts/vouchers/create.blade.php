@extends('layouts.app')
@section('title','New ' . $type->name . ' Voucher')
@section('page-title','New Voucher')
@section('content')

@include('accounts._toolbar')

<div class="page-header">
    <div class="page-header-left">
        <a href="{{ route('accounts.vouchers.index') }}" class="text-small"><i class="fa-solid fa-arrow-left"></i> Vouchers</a>
        <h1>New {{ $type->name }}</h1>
    </div>
    <div style="display:flex;gap:6px;flex-wrap:wrap">
        @foreach($types as $t)
            <a href="{{ route('accounts.vouchers.create',$t->code) }}" class="btn btn-sm {{ $t->id===$type->id?'btn-primary':'btn-outline' }}">{{ $t->code }}</a>
        @endforeach
    </div>
</div>

@if($errors->any())<div class="alert alert-error"><ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

<form method="POST" action="{{ route('accounts.vouchers.store') }}" class="card" style="padding:16px" id="voucherForm">
    @csrf
    <input type="hidden" name="type" value="{{ $type->code }}">

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin-bottom:12px">
        <div>
            <label>Date *</label>
            <input type="date" name="date" value="{{ old('date', $date->toDateString()) }}" class="form-control" required>
        </div>
        <div>
            <label>Party</label>
            <select name="party_ledger" class="form-control">
                <option value="">—</option>
                @foreach($ledgers as $l)
                    @if(in_array($l->party_type, ['Customer','Supplier','Employee','Other'], true))
                        <option value="{{ $l->id }}" {{ (int)old('party_ledger')===$l->id?'selected':'' }}>{{ $l->name }}</option>
                    @endif
                @endforeach
            </select>
        </div>
        <div>
            <label>Reference No.</label>
            <input name="reference_no" value="{{ old('reference_no') }}" class="form-control" placeholder="External invoice / cheque no.">
        </div>
        <div>
            <label>Reference Date</label>
            <input type="date" name="reference_date" value="{{ old('reference_date') }}" class="form-control">
        </div>
    </div>

    <label>Narration</label>
    <textarea name="narration" rows="2" class="form-control" style="margin-bottom:14px" placeholder="e.g., May electricity bill, Pharmacy purchase from XYZ Distributors">{{ old('narration') }}</textarea>

    <div style="display:flex;justify-content:space-between;align-items:center;margin:8px 0">
        <h3 style="margin:0">Entries</h3>
        <button type="button" class="btn btn-sm btn-outline" onclick="addRow()"><i class="fa-solid fa-plus"></i> Add row</button>
    </div>

    <div class="table-wrap">
        <table class="sc-table" id="entriesTable">
            <thead>
                <tr>
                    <th style="width:38%">Ledger</th>
                    <th class="text-right" style="width:13%">Dr</th>
                    <th class="text-right" style="width:13%">Cr</th>
                    <th>HSN/SAC</th>
                    <th class="text-right">Taxable</th>
                    <th class="text-right">GST %</th>
                    <th>Note</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="entriesBody"></tbody>
            <tfoot>
                <tr>
                    <td class="text-right fw-700">Totals</td>
                    <td class="text-right mono fw-700" id="totalDr">0.00</td>
                    <td class="text-right mono fw-700" id="totalCr">0.00</td>
                    <td colspan="5"><span id="balanceMsg" class="text-small"></span></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div style="margin-top:14px;display:flex;gap:8px">
        <button class="btn btn-primary" id="postBtn"><i class="fa-solid fa-check"></i> Post Voucher</button>
        <a href="{{ route('accounts.vouchers.index') }}" class="btn btn-outline">Cancel</a>
    </div>
</form>

<template id="rowTpl">
<tr class="entry-row">
    <td>
        <select name="entries[__I__][ledger]" class="form-control entry-ledger" required>
            <option value="">— ledger —</option>
            @foreach($ledgers as $l)<option value="{{ $l->id }}" data-group="{{ $l->group?->name }}">{{ $l->name }} ({{ $l->group?->name }})</option>@endforeach
        </select>
    </td>
    <td><input type="number" step="0.01" min="0" name="entries[__I__][dr_amount]" class="form-control mono text-right entry-dr" oninput="onAmount(this,'dr')"></td>
    <td><input type="number" step="0.01" min="0" name="entries[__I__][cr_amount]" class="form-control mono text-right entry-cr" oninput="onAmount(this,'cr')"></td>
    <td><input name="entries[__I__][hsn_sac]" class="form-control mono" maxlength="16"></td>
    <td><input type="number" step="0.01" min="0" name="entries[__I__][taxable_amount]" class="form-control mono text-right"></td>
    <td><input type="number" step="0.01" min="0" max="100" name="entries[__I__][gst_rate]" class="form-control mono text-right"></td>
    <td><input name="entries[__I__][narration]" class="form-control" maxlength="500"></td>
    <td><button type="button" class="btn btn-sm btn-outline" onclick="this.closest('tr').remove();recalc()" style="color:var(--sc-red);border-color:var(--sc-red)"><i class="fa-solid fa-xmark"></i></button></td>
</tr>
</template>

<script>
let rowIdx = 0;
function addRow() {
    const tpl = document.getElementById('rowTpl').innerHTML.replaceAll('__I__', rowIdx++);
    document.getElementById('entriesBody').insertAdjacentHTML('beforeend', tpl);
    recalc();
}
function onAmount(input, side) {
    // If user types Dr, blank the Cr in same row (and vice-versa) to avoid both-sided rows
    const row = input.closest('tr');
    if (side === 'dr' && parseFloat(input.value || 0) > 0) row.querySelector('.entry-cr').value = '';
    if (side === 'cr' && parseFloat(input.value || 0) > 0) row.querySelector('.entry-dr').value = '';
    recalc();
}
function recalc() {
    let dr = 0, cr = 0;
    document.querySelectorAll('.entry-row').forEach(tr => {
        dr += parseFloat(tr.querySelector('.entry-dr').value || 0);
        cr += parseFloat(tr.querySelector('.entry-cr').value || 0);
    });
    document.getElementById('totalDr').textContent = dr.toFixed(2);
    document.getElementById('totalCr').textContent = cr.toFixed(2);
    const balanced = Math.abs(dr - cr) < 0.005 && dr > 0;
    const diff = (dr - cr).toFixed(2);
    const msg = document.getElementById('balanceMsg');
    if (dr === 0 && cr === 0) { msg.textContent = ''; }
    else if (balanced) { msg.innerHTML = '<span style="color:var(--sc-green,#1f8b4c)"><i class="fa-solid fa-check"></i> Balanced</span>'; }
    else { msg.innerHTML = `<span style="color:var(--sc-red,#b54344)"><i class="fa-solid fa-triangle-exclamation"></i> Off by ₹${diff}</span>`; }
    document.getElementById('postBtn').disabled = !balanced;
}
// Seed two rows by default
addRow(); addRow();
</script>

@endsection
