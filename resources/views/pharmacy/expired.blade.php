@extends('layouts.app')
@section('title','Expired Stock')
@section('page-title','Expired Stock')
@section('content')

@include('pharmacy._toolbar')

<div class="page-header">
    <div class="page-header-left">
        <a href="{{ route('pharmacy.dashboard') }}" class="text-small"><i class="fa-solid fa-arrow-left"></i> Pharmacy</a>
        <h1><i class="fa-solid fa-triangle-exclamation" style="color:var(--sc-red)"></i> Expired Stock</h1>
        <div class="text-small text-muted">Select rows below and click <strong>Remove Selected</strong>. Removal is soft (quantity → 0, marked Inactive) and logged in the Stock Register as type=<code>expired</code> — fully reversible by reactivating the row in Edit Stock if needed.</div>
    </div>
</div>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

@if($expired->isEmpty())
<div class="card"><div class="card-body" style="text-align:center;padding:24px">
    <p style="color:var(--sc-green);font-weight:600"><i class="fa-solid fa-circle-check"></i> Nothing expired right now.</p>
    <p class="text-small text-muted">All active stock rows have a future expiry date.</p>
</div></div>
@else
<form method="POST" action="{{ route('pharmacy.expired.remove') }}" onsubmit="return confirm('Mark the selected expired stock rows as removed with the chosen destroy method? Reversible via Edit Stock.')">
    @csrf

    <div class="card" style="padding:14px;margin-bottom:14px">
        <h3 style="margin:0 0 10px 0;font-size:1rem"><i class="fa-solid fa-clipboard-check"></i> How was the stock destroyed?</h3>
        <div style="display:grid;grid-template-columns:2fr 1fr 1fr;gap:10px;align-items:end">
            <div>
                <label>Destroy Method *</label>
                <select name="destroy_method" class="form-control" required>
                    <option value="Destroyed per regulations (incineration / licensed disposal)" selected>Destroyed per regulations (incineration / licensed disposal)</option>
                    <option value="Returned to supplier for credit">Returned to supplier for credit</option>
                    <option value="Discarded as general waste (NOT recommended — regulatory risk)">Discarded as general waste (NOT recommended — regulatory risk)</option>
                    <option value="Drug Controller witnessed destruction">Drug Controller witnessed destruction</option>
                    <option value="Other (see notes)">Other (see notes)</option>
                </select>
                <div class="text-small text-muted" style="margin-top:4px">Default: proper incineration / licensed disposal per Schedule M / state pharmacy rules.</div>
            </div>
            <div>
                <label>Destroyed On *</label>
                <input type="date" name="destroyed_on" value="{{ today()->toDateString() }}" class="form-control" required>
            </div>
            <div>
                <label>Witness <span class="text-muted text-small">(optional)</span></label>
                <input name="destroy_witness" class="form-control" placeholder="Name of witness if any">
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
            <div>
                <strong>{{ $expired->count() }} expired row(s)</strong>
                <span class="text-small text-muted">— {{ $expired->sum('quantity') }} units total</span>
            </div>
            <div style="display:flex;gap:8px;align-items:center">
                <label style="display:flex;gap:6px;align-items:center;cursor:pointer">
                    <input type="checkbox" id="selectAll" onchange="document.querySelectorAll('.row-check').forEach(c=>c.checked=this.checked)">
                    <span class="text-small">Select all</span>
                </label>
                <button class="btn btn-primary" style="background:var(--sc-red);border-color:var(--sc-red)">
                    <i class="fa-solid fa-trash"></i> Remove Selected
                </button>
            </div>
        </div>

        <div class="table-wrap">
            <table class="sc-table" style="margin:0">
                <thead>
                    <tr>
                        <th style="width:40px"></th>
                        <th>Medicine</th>
                        <th>Batch</th>
                        <th>Expiry</th>
                        <th class="text-right">Qty</th>
                        <th class="text-right">Unit Cost</th>
                        <th class="text-right">Value</th>
                        <th>Supplier</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($expired as $s)
                    @php
                        $daysAgo = $s->expiry_date ? today()->diffInDays($s->expiry_date) : null;
                        $value = (float) $s->quantity * (float) $s->unit_cost;
                    @endphp
                    <tr>
                        <td><input type="checkbox" class="row-check" name="stock_ids[]" value="{{ $s->id }}"></td>
                        <td class="fw-600">{{ $s->medicine?->name }} <span class="text-small text-muted">{{ $s->medicine?->strength }}</span></td>
                        <td class="mono text-small">{{ $s->batch_number ?: '—' }}</td>
                        <td class="text-small" style="color:{{ $daysAgo > 90 ? 'var(--sc-red)' : 'var(--sc-amber)' }}">
                            {{ $s->expiry_date->format('d-M-Y') }}
                            <div class="text-muted" style="font-size:11px">{{ $daysAgo }} days ago</div>
                        </td>
                        <td class="text-right mono">{{ rtrim(rtrim(number_format((float) $s->quantity, 2), '0'), '.') }}</td>
                        <td class="text-right mono">₹{{ number_format((float) $s->unit_cost, 2) }}</td>
                        <td class="text-right mono">{{ $value > 0 ? '₹' . number_format($value, 2) : '—' }}</td>
                        <td class="text-small">{{ $s->supplier ?: '—' }}</td>
                    </tr>
                @endforeach
                </tbody>
                <tfoot>
                    <tr style="background:#f0f0f0">
                        <td colspan="4" class="fw-700 text-right">Total stock value to write off</td>
                        <td class="text-right mono fw-700">{{ $expired->sum('quantity') }}</td>
                        <td></td>
                        <td class="text-right mono fw-700">₹{{ number_format($expired->sum(fn($s) => (float) $s->quantity * (float) $s->unit_cost), 2) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</form>

<div class="text-small text-muted" style="margin-top:14px;padding:10px;background:#fafaf6;border-radius:6px">
    <i class="fa-solid fa-info-circle"></i> <strong>What "remove" does:</strong> sets the row's quantity to 0 and marks it Inactive — it stops counting in stock totals but the row stays in the DB. Each removal writes a <code>stock_transactions</code> entry (type=<em>expired</em>) so the write-off is visible in the Stock Register and recoverable if a row was removed by mistake (Edit Stock → set quantity back, re-activate). <strong>Note:</strong> this does <em>not</em> post an accounting voucher — if you want the write-off booked to a "Stock Write-off" expense ledger, tell me and I'll add that step.
</div>
@endif

@endsection
