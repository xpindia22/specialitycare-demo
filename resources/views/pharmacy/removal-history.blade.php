@extends('layouts.app')
@section('title','Stock Removal History')
@section('page-title','Stock Removal History')
@section('content')

@include('pharmacy._toolbar')

<div class="page-header">
    <div class="page-header-left">
        <a href="{{ route('pharmacy.dashboard') }}" class="text-small"><i class="fa-solid fa-arrow-left"></i> Pharmacy</a>
        <h1><i class="fa-solid fa-clock-rotate-left"></i> Stock Removal History</h1>
        <div class="text-small text-muted">Every expired / removed stock row, with who did it and why. Audit trail.</div>
    </div>
</div>

<form method="GET" class="card" style="padding:10px;margin-bottom:14px;display:flex;gap:8px;align-items:end;flex-wrap:wrap">
    <div><label class="text-small">From</label><input type="date" name="from" value="{{ $from }}" class="form-control"></div>
    <div><label class="text-small">To</label><input type="date" name="to" value="{{ $to }}" class="form-control"></div>
    <div>
        <label class="text-small">Type</label>
        <select name="type" class="form-control">
            <option value="">All</option>
            <option value="expired" {{ $type === 'expired' ? 'selected' : '' }}>Expired</option>
            <option value="removed" {{ $type === 'removed' ? 'selected' : '' }}>Manual removal</option>
        </select>
    </div>
    <button class="btn btn-outline">Filter</button>
</form>

@if($txns->isEmpty())
<div class="card"><div class="card-body" style="text-align:center;padding:24px;color:var(--sc-muted)">No removals in this date range.</div></div>
@else
<div class="card">
    <div class="table-wrap">
        <table class="sc-table" style="margin:0">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Medicine</th>
                    <th>Batch</th>
                    <th class="text-right">Qty Removed</th>
                    <th>By</th>
                    <th>Reason / Method</th>
                </tr>
            </thead>
            <tbody>
            @foreach($txns as $t)
                @php
                    // Parse "Method: …" from the notes if present
                    preg_match('/Method:\s*([^.]+)/', (string) $t->notes, $mMethod);
                @endphp
                <tr>
                    <td class="text-small">{{ \Carbon\Carbon::parse($t->created_at)->format('d-M-Y H:i') }}</td>
                    <td>
                        <span class="badge {{ $t->type === 'expired' ? 'badge-amber' : 'badge-red' }}">{{ $t->type }}</span>
                    </td>
                    <td class="fw-600">
                        {{ $t->medicine?->name }}
                        @if($t->stock?->medicine && $t->stock->medicine_id !== $t->medicine_id)
                            <span class="text-small text-muted">(stock med: {{ $t->stock->medicine?->name }})</span>
                        @endif
                    </td>
                    <td class="mono text-small">{{ $t->batch_number ?: '—' }}</td>
                    <td class="text-right mono">{{ abs((int) $t->quantity) }}</td>
                    <td class="text-small">{{ $t->user?->name ?? '—' }}</td>
                    <td class="text-small">
                        {{ trim($mMethod[1] ?? '') ?: ($t->reference ?: '—') }}
                        @if($t->notes)
                            <div class="text-muted" style="font-size:11px">{{ \Illuminate\Support\Str::limit($t->notes, 180) }}</div>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

<div style="margin-top:14px">{{ $txns->links() }}</div>
@endif

@endsection
