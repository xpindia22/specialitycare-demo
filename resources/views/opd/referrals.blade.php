@extends('layouts.app')
@section('title', 'Department Referrals')
@section('page-title', 'Department Referrals')
@section('content')
@php
    $badge = ['pending' => 'badge-amber', 'queued' => 'badge-navy', 'booked' => 'badge-purple', 'seen' => 'badge-green', 'cancelled' => 'badge-gray'];
    $tabs = ['open' => 'Open', 'seen' => 'Seen', 'cancelled' => 'Cancelled', 'all' => 'All (60 days)'];
@endphp

<div class="page-header">
    <div class="page-header-left">
        <h1>Department Referrals</h1>
        <div class="breadcrumb"><a href="{{ route('opd.index') }}">OPD</a> &rsaquo; Referrals</div>
    </div>
    @include('opd._toolbar', ['inline' => true])
</div>

<div class="alert alert-keep" style="background:#f5f3ff;border:1px solid #ddd6fe;color:#3b0764;border-radius:8px;padding:10px 16px;margin-bottom:14px;font-size:12.5px">
    <i class="fa-solid fa-circle-info"></i>
    A doctor refers a patient from the visit page with the purple <b>Refer</b> button.
    <b>Today</b> referrals go straight into the other department's queue with a new token.
    <b>Later</b> referrals wait here: <b>Book</b> an appointment, or press <b>Token now</b> when the patient arrives.
    A referral shows as <b>Seen</b> once the other doctor opens the visit.
</div>

<form method="GET" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-bottom:12px">
    <div class="ref-tabs">
        @foreach($tabs as $k => $l)
        <a href="{{ route('opd.referrals.index', array_filter(['status' => $k, 'department_id' => $deptId])) }}" class="{{ $status === $k ? 'active' : '' }}">
            {{ $l }}@if($k === 'open' && $pending) <span class="nav-badge amber" style="margin-left:4px">{{ $pending }}</span>@endif
        </a>
        @endforeach
    </div>
    <input type="hidden" name="status" value="{{ $status }}">
    <select name="department_id" class="form-control" style="width:auto;min-width:200px" onchange="this.form.submit()">
        <option value="">All departments</option>
        @foreach($departments as $d)<option value="{{ $d->id }}" @selected($deptId == $d->id)>{{ $d->name }}</option>@endforeach
    </select>
</form>

<div class="card">
    <div class="table-wrap">
        <table class="sc-table">
            <thead>
                <tr>
                    <th>Date</th><th>Patient</th><th>From</th><th>To</th><th>Reason</th><th>Status</th><th style="width:1%"></th>
                </tr>
            </thead>
            <tbody>
            @forelse($referrals as $r)
            <tr class="{{ $r->priority === 'urgent' && in_array($r->status, ['pending', 'queued', 'booked']) ? 'ref-urgent' : '' }}">
                <td style="font-size:12px;white-space:nowrap">
                    {{ $r->created_at->format('d M, h:i A') }}
                    @if($r->when === 'later' && $r->preferred_date)<div class="text-muted" style="font-size:11px">wanted {{ $r->preferred_date->format('d M') }}</div>@endif
                </td>
                <td>
                    <a href="{{ route('patients.show', $r->patient) }}" class="fw-600">{{ $r->patient->full_name }}</a>
                    <div style="font-size:11px;color:#666">{{ $r->patient->jhc_id }}{{ $r->patient->phone ? ' · '.$r->patient->phone : '' }}</div>
                </td>
                <td style="font-size:12px">
                    {{ $r->fromDoctor?->full_name ?? '—' }}
                    @if($r->fromDepartment)<div class="text-muted" style="font-size:11px">{{ $r->fromDepartment->name }}</div>@endif
                </td>
                <td style="font-size:12px">
                    <span style="font-weight:600;color:{{ $r->toDepartment?->color ?: 'inherit' }}"><i class="fa-solid {{ $r->toDepartment?->icon ?: 'fa-hospital' }}"></i> {{ $r->toDepartment?->name }}</span>
                    <div class="text-muted" style="font-size:11px">{{ $r->toDoctor?->full_name ?? 'Any doctor' }}</div>
                </td>
                <td style="font-size:12px;max-width:280px">
                    @if($r->priority === 'urgent')<span class="badge badge-red">Urgent</span>@endif
                    {{ $r->reason }}
                </td>
                <td style="font-size:12px;white-space:nowrap">
                    <span class="badge {{ $badge[$r->status] ?? 'badge-gray' }}">{{ $r->status_label }}</span>
                    @if($r->token)<div class="mono fw-600" style="margin-top:2px">{{ $r->token->display }} <span class="text-muted" style="font-weight:400">{{ str_replace('_', ' ', $r->token->status) }}</span></div>@endif
                    @if($r->appointment)<div style="font-size:11px;margin-top:2px"><a href="{{ route('appointments.show', $r->appointment) }}">{{ $r->appointment->appointment_no }}</a> · {{ \Carbon\Carbon::parse($r->appointment->appointment_date)->format('d M') }}</div>@endif
                </td>
                <td style="white-space:nowrap">
                    <div style="display:flex;gap:4px;justify-content:flex-end">
                    @if(in_array($r->status, ['pending', 'booked']))
                        <form method="POST" action="{{ route('opd.referrals.token', $r) }}">@csrf
                            <button class="btn btn-sm btn-primary" title="Patient is here: put them in the {{ $r->toDepartment?->name }} queue"><i class="fa-solid fa-ticket"></i> Token now</button>
                        </form>
                    @endif
                    @if($r->status === 'pending')
                        <a class="btn btn-sm btn-outline" href="{{ route('appointments.create', array_filter([
                            'patient_id' => $r->patient_id, 'department_id' => $r->to_department_id, 'doctor_id' => $r->to_doctor_id,
                            'date' => ($r->preferred_date && $r->preferred_date->gte(today()) ? $r->preferred_date : today())->toDateString(),
                            'notes' => 'Referred by ' . $r->from_label . ': ' . $r->reason, 'referral_id' => $r->id,
                        ])) }}"><i class="fa-solid fa-calendar-plus"></i> Book</a>
                    @endif
                    @if($r->token && $r->status === 'queued')
                        <a class="btn btn-sm btn-outline" target="_blank" href="{{ route('opd.slip', $r->token) }}" title="Token slip"><i class="fa-solid fa-print"></i></a>
                    @endif
                    @if($r->toEncounter)
                        <a class="btn btn-sm btn-outline" href="{{ route('encounters.show', $r->toEncounter) }}" title="Open the visit in {{ $r->toDepartment?->name }}"><i class="fa-solid fa-notes-medical"></i> Visit</a>
                    @endif
                        <a class="btn btn-sm btn-outline" target="_blank" href="{{ route('opd.referrals.print', $r) }}" title="Referral letter"><i class="fa-solid fa-file-lines"></i></a>
                    @if(in_array($r->status, ['pending', 'queued', 'booked']))
                        <form method="POST" action="{{ route('opd.referrals.cancel', $r) }}" onsubmit="return confirm('Cancel this referral?{{ $r->token && in_array($r->token->status, ['waiting','called']) ? ' Token '.$r->token->display.' will be taken off the queue.' : '' }}')">@csrf
                            <button class="btn btn-sm btn-outline" style="color:var(--sc-red);border-color:var(--sc-red)" title="Cancel referral"><i class="fa-solid fa-xmark"></i></button>
                        </form>
                    @endif
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="7" class="text-center text-muted" style="padding:40px">No {{ $status === 'all' ? '' : strtolower($tabs[$status] ?? '') }} referrals{{ $deptId ? ' for this department' : '' }}.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

@push('styles')
<style>
.ref-tabs { display:flex; border:1px solid #ddd; border-radius:6px; overflow:hidden; }
.ref-tabs a { padding:6px 14px; font-size:12.5px; color:#334155; text-decoration:none; border-right:1px solid #ddd; background:#fff; display:flex; align-items:center; }
.ref-tabs a:last-child { border-right:0; }
.ref-tabs a.active { background:#7c3aed; color:#fff; }
.ref-urgent { background:#fff7f7 !important; }
</style>
@endpush
@endsection
