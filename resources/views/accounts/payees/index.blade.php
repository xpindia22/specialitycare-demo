@extends('layouts.app')
@section('title','Staff & Payees')
@section('page-title','Staff & Payees')
@section('content')

@include('accounts._toolbar', ['extra' => [
    ['url' => route('accounts.payees.create'), 'icon' => 'fa-user-plus', 'label' => 'Add Payee', 'bg' => '#0d9488'],
]])

<div class="page-header">
    <div class="page-header-left">
        <h1><i class="fa-solid fa-people-group"></i> Staff &amp; Payees</h1>
        <div class="text-small text-muted">Define each person you pay regularly. Each gets their own expense ledger so the P&amp;L shows per-person totals.</div>
    </div>
</div>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

@if($payees->isEmpty())
<div class="card"><div class="card-body" style="padding:0">
    <x-empty-state
        icon="fa-people-group"
        title="No payees yet"
        message="Add Anushika, your maid, cleaner, driver — anyone you pay a monthly amount to. Each gets a per-person ledger so the P&L breaks down who's costing what."
        action="Add your first payee"
        :actionUrl="route('accounts.payees.create')" />
</div></div>
@else
<div class="card">
    <div class="table-wrap">
        <table class="sc-table">
            <thead>
                <tr><th>Name</th><th>Role</th><th class="text-right">Monthly</th><th>Expense Ledger</th><th>Paid From</th><th>HR Staff</th><th>Phone</th><th>Status</th><th></th></tr>
            </thead>
            <tbody>
            @foreach($payees as $p)
                <tr style="{{ $p->is_active ? '' : 'opacity:0.55' }}">
                    <td class="fw-600">{{ $p->name }}</td>
                    <td><span class="badge badge-navy">{{ $p->role ?: '—' }}</span></td>
                    <td class="text-right mono fw-600">₹{{ number_format((float)$p->monthly_amount,2) }}</td>
                    <td class="text-small">
                        @if($p->ledger)
                            <a href="{{ route('accounts.ledgers.show', $p->ledger) }}">{{ $p->ledger->name }}</a>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="text-small">{{ $p->payFromLedger?->name }}</td>
                    <td class="text-small">{{ $p->hrStaff ? trim(($p->hrStaff->first_name ?? '') . ' ' . ($p->hrStaff->last_name ?? '')) : '—' }}</td>
                    <td class="text-small">{{ $p->contact_phone ?: '—' }}</td>
                    <td><span class="badge {{ $p->is_active ? 'badge-green' : 'badge-gray' }}">{{ $p->is_active ? 'Active' : 'Inactive' }}</span></td>
                    <td>
                        <div style="display:flex;gap:4px">
                            <a href="{{ route('accounts.expenses.salary', ['payee' => $p->id]) }}" class="btn btn-sm btn-outline" title="Pay {{ $p->name }}"><i class="fa-solid fa-money-bill"></i></a>
                            <a href="{{ route('accounts.payees.edit', $p) }}" class="btn btn-sm btn-outline"><i class="fa-solid fa-pen"></i></a>
                            <form method="POST" action="{{ route('accounts.payees.destroy', $p) }}" onsubmit="return confirm('Remove this payee? (If they have past payments, they will be marked inactive instead of deleted.)')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline" style="color:var(--sc-red);border-color:var(--sc-red)"><i class="fa-solid fa-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
            @endforeach
            </tbody>
            <tfoot>
                <tr style="background:#f0f0f0">
                    <td colspan="2" class="fw-700">Total monthly outflow ({{ $payees->where('is_active',true)->count() }} active)</td>
                    <td class="text-right mono fw-700">₹{{ number_format((float)$payees->where('is_active',true)->sum('monthly_amount'),2) }}</td>
                    <td colspan="6"></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endif

@endsection
