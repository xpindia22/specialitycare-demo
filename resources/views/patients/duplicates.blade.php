@extends('layouts.app')
@section('title', 'Duplicate Patients')
@section('page-title', 'Duplicate Patients')
@section('content')

<div class="page-header">
    <div class="page-header-left">
        <h1>Possible Duplicate Patients</h1>
        <div class="breadcrumb">Tools &rsaquo; Find Duplicate Patients</div>
    </div>
    <a href="{{ route('patients.index') }}" class="btn btn-outline btn-sm"><i class="fa-solid fa-users"></i> Patients</a>
</div>

@if(session('success'))<div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> {{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger"><i class="fa-solid fa-circle-xmark"></i> {{ session('error') }}</div>@endif

<div class="alert" style="background:#fffbeb;border:1px solid #fcd34d;color:#92400e;border-radius:8px;padding:12px 16px;margin-bottom:16px;display:flex;gap:10px;align-items:flex-start">
    <i class="fa-solid fa-triangle-exclamation" style="color:#f59e0b;margin-top:2px"></i>
    <div style="font-size:13px;line-height:1.5">
        Merging is <strong>permanent</strong>. The "merge from" record's encounters, bills, labs, prescriptions and other data are re-pointed to the chosen "merge into" patient, then the duplicate row is deleted. Always verify both records carefully — including phone, DOB, gender — before merging.
    </div>
</div>

@forelse($groups as $g)
<div class="card mb-3">
    <div class="card-header" style="font-size:13px;font-weight:600;display:flex;justify-content:space-between;align-items:center">
        <span><i class="fa-solid fa-clone"></i> Match — {{ $g['key'] }}</span>
        <span class="badge badge-amber">{{ count($g['rows']) }} records</span>
    </div>
    <div class="table-wrap">
        <table class="sc-table">
            <thead>
                <tr>
                    <th>JHC ID</th>
                    <th>Name</th>
                    <th>DOB / Age</th>
                    <th>Gender</th>
                    <th>Phone</th>
                    <th style="text-align:center">Encounters</th>
                    <th style="text-align:center">Bills</th>
                    <th style="text-align:center">Labs</th>
                    <th>Created</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @foreach($g['rows'] as $p)
            <tr>
                <td class="mono">{{ $p->jhc_id }}</td>
                <td class="fw-600">
                    <a href="{{ route('patients.show', $p) }}" target="_blank" style="color:inherit">{{ $p->full_name }}</a>
                </td>
                <td class="text-small">{{ optional($p->date_of_birth)->format('d M Y') ?: '—' }}<div class="text-muted">{{ $p->age }}</div></td>
                <td>{{ $p->gender ?: '—' }}</td>
                <td class="mono text-small">{{ $p->phone ?: '—' }}</td>
                <td style="text-align:center">{{ $p->encounters_count }}</td>
                <td style="text-align:center">{{ $p->bills_count }}</td>
                <td style="text-align:center">{{ $p->lab_orders_count }}</td>
                <td class="text-small text-muted">{{ optional($p->created_at)->format('d M Y') }}</td>
                <td>
                    {{-- Build merge form: this patient is "from", offer dropdown of others as "into" --}}
                    <form method="POST" action="{{ route('tools.duplicates.merge', $p) }}" onsubmit="return confirmMerge(this)" style="display:flex;gap:4px;align-items:center">
                        @csrf
                        <select name="merge_into" class="form-control" style="font-size:11px;padding:4px 6px;width:auto" required>
                            <option value="">Merge into…</option>
                            @foreach($g['rows'] as $other)
                                @if($other->id !== $p->id)
                                    <option value="{{ $other->id }}">{{ $other->jhc_id }} · {{ $other->full_name }}</option>
                                @endif
                            @endforeach
                        </select>
                        <button type="submit" class="btn btn-sm btn-outline" style="color:var(--sc-red);border-color:var(--sc-red)" title="Merge this record into the selected patient">
                            <i class="fa-solid fa-code-merge"></i>
                        </button>
                    </form>
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@empty
<x-empty-state
    icon="fa-circle-check"
    title="No duplicates detected"
    message="No two patients share the same phone or the same name + date-of-birth. Run again later as new patients are added." />
@endforelse

<script>
function confirmMerge(form) {
    const sel = form.querySelector('[name="merge_into"]');
    if (!sel.value) { alert('Choose which patient to merge into.'); return false; }
    const opt = sel.options[sel.selectedIndex];
    return confirm('This will permanently merge this record into ' + opt.text + '.\n\nAll encounters, bills, labs and prescriptions from this duplicate will be re-pointed to the chosen patient, and the duplicate row deleted.\n\nProceed?');
}
</script>
@endsection
