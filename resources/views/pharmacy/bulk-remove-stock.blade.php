@extends('layouts.app')
@section('title','Bulk Remove Stock')
@section('page-title','Bulk Remove Stock')
@section('content')

<div class="page-header">
    <div class="page-header-left">
        <a href="{{ route('pharmacy.dashboard') }}" class="text-small"><i class="fa-solid fa-arrow-left"></i> Pharmacy</a>
        <h1><i class="fa-solid fa-trash-can"></i> Bulk Remove Stock</h1>
        <div class="text-small text-muted">Select rows to remove. Soft-delete with reason + audit log. Reversible from Edit Stock.</div>
    </div>
    <a href="{{ route('pharmacy.stock.removal-history') }}" class="btn btn-outline"><i class="fa-solid fa-clock-rotate-left"></i> Removal History</a>
</div>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-error">{{ $errors->first() }}</div>@endif

<form method="GET" class="card" style="padding:10px;margin-bottom:14px;display:flex;gap:8px">
    <input name="q" value="{{ $q }}" class="form-control" placeholder="Search by medicine, batch, supplier…" style="flex:1">
    <button class="btn btn-outline">Filter</button>
</form>

@if($stocks->isEmpty())
<div class="card"><div class="card-body" style="text-align:center;padding:24px;color:var(--sc-muted)">No active stock matching this filter.</div></div>
@else
<form method="POST" action="{{ route('pharmacy.stock.bulk-remove.do') }}" onsubmit="return confirm('Soft-remove the selected stock rows with the chosen reason? Reversible via Edit Stock.')">
    @csrf

    <div class="card" style="padding:14px;margin-bottom:14px">
        <h3 style="margin:0 0 10px 0;font-size:1rem"><i class="fa-solid fa-clipboard-check"></i> Removal details</h3>
        <div style="display:grid;grid-template-columns:2fr 1fr 1fr;gap:10px;align-items:end">
            <div>
                <label>Reason / Method *</label>
                <select name="destroy_method" class="form-control" required>
                    <option value="Damaged / unusable" selected>Damaged / unusable</option>
                    <option value="Wrong batch entered (correction)">Wrong batch entered (correction)</option>
                    <option value="Returned to supplier">Returned to supplier</option>
                    <option value="Destroyed per regulations (incineration / licensed disposal)">Destroyed per regulations (incineration / licensed disposal)</option>
                    <option value="Other (see notes)">Other (see notes)</option>
                </select>
            </div>
            <div>
                <label>Date *</label>
                <input type="date" name="destroyed_on" value="{{ today()->toDateString() }}" class="form-control" required>
            </div>
            <div>
                <label>Witness (optional)</label>
                <input name="destroy_witness" class="form-control">
            </div>
        </div>
        <div style="margin-top:8px">
            <label>Notes (optional)</label>
            <textarea name="reason" rows="2" class="form-control" placeholder="Any extra context that should be in the audit log"></textarea>
        </div>
    </div>

    <div class="card">
        <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
            <div>
                <strong>{{ $stocks->total() }} active stock row(s)</strong>
                <span class="text-small text-muted">— page {{ $stocks->currentPage() }} of {{ $stocks->lastPage() }}</span>
            </div>
            <div style="display:flex;gap:8px;align-items:center">
                <label style="display:flex;gap:6px;align-items:center;cursor:pointer">
                    <input type="checkbox" id="selectAllPage" onchange="document.querySelectorAll('.row-check').forEach(c=>c.checked=this.checked)">
                    <span class="text-small">Select all on this page</span>
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
                        <th>Supplier</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($stocks as $s)
                    <tr>
                        <td><input type="checkbox" class="row-check" name="stock_ids[]" value="{{ $s->id }}"></td>
                        <td class="fw-600">{{ $s->medicine?->name }} <span class="text-small text-muted">{{ $s->medicine?->strength }}</span></td>
                        <td class="mono text-small">{{ $s->batch_number ?: '—' }}</td>
                        <td class="text-small">{{ $s->expiry_date?->format('d-M-Y') ?: '—' }}</td>
                        <td class="text-right mono">{{ rtrim(rtrim(number_format((float) $s->quantity, 2), '0'), '.') }}</td>
                        <td class="text-right mono">{{ (float) $s->unit_cost > 0 ? '₹' . number_format((float) $s->unit_cost, 2) : '—' }}</td>
                        <td class="text-small">{{ $s->supplier ?: '—' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</form>

<div style="margin-top:14px">{{ $stocks->links() }}</div>
@endif

@endsection
