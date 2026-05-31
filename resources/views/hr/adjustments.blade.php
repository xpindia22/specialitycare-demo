@extends('layouts.app')
@section('title','Pay Adjustments')
@section('page-title','Pay Adjustments')
@section('content')

@php
    $prevMonth = \Illuminate\Support\Carbon::createFromFormat('Y-m', $month)->subMonth()->format('Y-m');
    $nextMonth = \Illuminate\Support\Carbon::createFromFormat('Y-m', $month)->addMonth()->format('Y-m');

    $totals = ['credits' => 0, 'debits' => 0];
    foreach ($adjustments as $adj) {
        $sign = \App\Models\PayrollAdjustment::TYPES[$adj->type]['sign'] ?? 1;
        if ($sign > 0) $totals['credits'] += (float) $adj->amount;
        else           $totals['debits']  += (float) $adj->amount;
    }
    $totals['net'] = $totals['credits'] - $totals['debits'];
@endphp

<div class="page-header">
    <div class="page-header-left">
        <h1>Pay Adjustments</h1>
        <div class="breadcrumb">{{ $monthLabel }} &nbsp;·&nbsp; {{ $adjustments->count() }} {{ \Illuminate\Support\Str::plural('entry', $adjustments->count()) }}</div>
    </div>
    <div style="display:flex;gap:8px;align-items:center">
        <a href="?month={{ $prevMonth }}" class="btn btn-outline btn-sm" title="Previous month"><i class="fa-solid fa-chevron-left"></i></a>
        <form method="GET" style="margin:0">
            <input type="month" name="month" value="{{ $month }}" class="form-control" style="width:160px;display:inline-block" onchange="this.form.submit()">
        </form>
        <a href="?month={{ $nextMonth }}" class="btn btn-outline btn-sm" title="Next month"><i class="fa-solid fa-chevron-right"></i></a>
        <a href="{{ route('hr.attendance.index') }}" class="btn btn-outline"><i class="fa-solid fa-calendar-check"></i> Attendance</a>
        <a href="{{ route('hr.payroll.index', ['month'=>$month]) }}" class="btn btn-outline"><i class="fa-solid fa-money-check-dollar"></i> Payroll</a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> {{ session('success') }}</div>
@endif
@if($errors->any())
<div class="alert alert-danger">
    <i class="fa-solid fa-triangle-exclamation"></i>
    <div>@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
</div>
@endif

{{-- KPI row — credits / debits / net for the month --}}
<div class="grid grid-4 mb-4">
    <div class="stat-card">
        <div class="stat-icon green"><i class="fa-solid fa-arrow-up"></i></div>
        <div>
            <div class="stat-value">+₹{{ number_format($totals['credits'], 0) }}</div>
            <div class="stat-label">Credits</div>
            <div class="stat-sub text-muted">Overtime, bonus, allowance</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red"><i class="fa-solid fa-arrow-down"></i></div>
        <div>
            <div class="stat-value">−₹{{ number_format($totals['debits'], 0) }}</div>
            <div class="stat-label">Debits</div>
            <div class="stat-sub text-muted">Advances &amp; deductions</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon teal"><i class="fa-solid fa-scale-balanced"></i></div>
        <div>
            <div class="stat-value">{{ $totals['net'] >= 0 ? '+' : '−' }}₹{{ number_format(abs($totals['net']), 0) }}</div>
            <div class="stat-label">Net Impact</div>
            <div class="stat-sub text-muted">Applied to payroll</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon navy"><i class="fa-solid fa-list"></i></div>
        <div>
            <div class="stat-value">{{ $adjustments->count() }}</div>
            <div class="stat-label">Entries</div>
            <div class="stat-sub text-muted">This month</div>
        </div>
    </div>
</div>

<div class="grid grid-2" style="grid-template-columns: 360px 1fr; align-items:flex-start">
    {{-- Add new adjustment --}}
    <div class="card">
        <div class="card-header"><i class="fa-solid fa-plus-circle"></i> Record Adjustment</div>
        <div class="card-body">
            <form method="POST" action="{{ route('hr.adjustments.store') }}">
                @csrf
                <div class="form-group">
                    <label class="form-label">Person <span class="req">*</span></label>
                    <select name="person_key" class="form-control" required>
                        <option value="">Select staff or doctor…</option>
                        @foreach($people as $p)
                        <option value="{{ $p->key }}">{{ $p->name }} ({{ $p->code }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Type <span class="req">*</span></label>
                    <select name="type" class="form-control" required>
                        @foreach($types as $k => $meta)
                        <option value="{{ $k }}">
                            {{ $meta['label'] }} ({{ $meta['sign'] > 0 ? '+ Credit' : '− Debit' }})
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-2" style="gap:10px">
                    <div class="form-group">
                        <label class="form-label">Amount (₹) <span class="req">*</span></label>
                        <input type="number" name="amount" class="form-control" required min="0.01" step="0.01" max="1000000">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Date <span class="req">*</span></label>
                        <input type="date" name="date" class="form-control" required value="{{ today()->toDateString() }}">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Notes</label>
                    <input type="text" name="notes" maxlength="255" class="form-control" placeholder="e.g. Sunday duty, Diwali bonus, advance for medical bill">
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%"><i class="fa-solid fa-plus"></i> Record</button>
            </form>
        </div>
    </div>

    {{-- List of adjustments this month --}}
    <div class="card">
        <div class="card-header">
            <i class="fa-solid fa-list"></i> Adjustments — {{ $monthLabel }}
            <div class="card-actions">
                <span class="badge badge-navy">{{ $adjustments->count() }}</span>
            </div>
        </div>
        <div class="table-responsive">
            <table class="sc-table table" style="margin-bottom:0">
                <thead>
                <tr>
                    <th>Date</th>
                    <th>Staff</th>
                    <th>Type</th>
                    <th class="text-right">Amount</th>
                    <th>Notes</th>
                    <th style="width:50px"></th>
                </tr>
                </thead>
                <tbody>
                @forelse($adjustments as $adj)
                @php $meta = \App\Models\PayrollAdjustment::TYPES[$adj->type] ?? ['sign'=>1,'badge'=>'gray']; @endphp
                <tr>
                    <td class="mono">{{ $adj->date->format('d M') }}</td>
                    <td>
                        @php $subj = $adj->subject(); $isDoc = (bool) $adj->doctor_id; @endphp
                        <div class="fw-600">
                            @if($subj)
                                {{ $isDoc ? 'Dr. ' . trim($subj->first_name . ' ' . $subj->last_name) : trim($subj->first_name . ' ' . $subj->last_name) }}
                                @if($isDoc)
                                    <span class="badge" style="background:#e0f2fe;color:#0369a1;font-size:10px;padding:1px 6px;margin-left:4px">Doctor</span>
                                @endif
                            @else
                                —
                            @endif
                        </div>
                    </td>
                    <td><span class="badge badge-{{ $meta['badge'] ?? 'gray' }}">{{ $adj->type_label }}</span></td>
                    <td class="text-right mono fw-600" style="color:{{ $meta['sign'] > 0 ? 'var(--sc-green)' : 'var(--sc-red)' }}">
                        {{ $meta['sign'] > 0 ? '+' : '−' }}₹{{ number_format($adj->amount, 2) }}
                    </td>
                    <td class="text-small text-muted">{{ $adj->notes ?: '—' }}</td>
                    <td>
                        <form method="POST" action="{{ route('hr.adjustments.destroy', $adj) }}" onsubmit="return confirm('Delete this adjustment?')" style="margin:0">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline" style="color:var(--sc-red);border-color:#fecaca" title="Delete"><i class="fa-solid fa-trash-can"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-muted" style="padding:40px">
                    <i class="fa-solid fa-coins" style="font-size:24px;display:block;margin-bottom:8px;color:#cbd5e1"></i>
                    No adjustments recorded for {{ $monthLabel }} yet.
                </td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection
