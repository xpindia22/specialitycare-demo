{{-- FILE: resources/views/patients/index.blade.php --}}
@extends('layouts.app')
@section('title','Patients')
@section('page-title','Patient Registry')
@section('content')
<div class="page-header">
    <div class="page-header-left">
        <h1>Patients</h1>
        <div class="breadcrumb">All registered patients</div>
    </div>
    <a href="{{ route('patients.create') }}" class="btn btn-primary"><i class="fa-solid fa-user-plus"></i> Register Patient</a>
</div>

<div class="card mb-3">
    <div class="card-body" style="padding:14px 20px">
        <form method="GET" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap">
            <div class="form-group" style="flex:1;min-width:200px;margin:0">
                <input type="text" name="search" class="form-control" placeholder="Search by name, JHC ID, phone…" value="{{ request('search') }}">
            </div>
            <div class="form-group" style="margin:0">
                <select name="gender" class="form-control" style="width:130px">
                    <option value="">All Gender</option>
                    <option value="Male" {{ request('gender')=='Male' ? 'selected':'' }}>Male</option>
                    <option value="Female" {{ request('gender')=='Female' ? 'selected':'' }}>Female</option>
                    <option value="Other" {{ request('gender')=='Other' ? 'selected':'' }}>Other</option>
                </select>
            </div>
            <div class="form-group" style="margin:0">
                <select name="blood_group" class="form-control" style="width:120px">
                    <option value="">All Blood</option>
                    @foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bg)
                        <option value="{{ $bg }}" {{ request('blood_group')==$bg ? 'selected':'' }}>{{ $bg }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group" style="margin:0">
                <select name="status" class="form-control" style="width:140px">
                    <option value="">All Status</option>
                    <option value="active" {{ request('status')=='active' ? 'selected':'' }}>Active only</option>
                    <option value="inactive" {{ request('status')=='inactive' ? 'selected':'' }}>Inactive only</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-search"></i> Search</button>
            @if(request()->hasAny(['search','gender','blood_group','status']))
                <a href="{{ route('patients.index') }}" class="btn btn-outline">Clear</a>
            @endif
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <i class="fa-solid fa-users"></i> Patient List
        <span class="badge badge-teal" style="margin-left:8px">{{ $patients->total() }}</span>
    </div>
    <style>
        /* Subtle zebra-stripe so consecutive patient rows are visually distinct.
           :not(:hover) keeps the existing teal hover from being overridden. */
        .sc-table.zebra tbody tr:nth-child(even):not(:hover) td { background: rgba(15, 31, 61, .035); }
        [data-theme="dark"] .sc-table.zebra tbody tr:nth-child(even):not(:hover) td { background: rgba(255, 255, 255, .035); }
    </style>
    <div class="table-wrap">
        @php
            // Inline sort-header helper. Each clickable header toggles asc/desc on
            // its column. URL is built from current request params (preserves
            // search/gender/blood_group/status filters but resets the page).
            $sortHeader = function (string $key, string $label) {
                $cur  = request('sort');
                $dir  = request('dir', 'desc');
                $nxt  = ($cur === $key && $dir === 'desc') ? 'asc' : 'desc';
                $arr  = $cur === $key ? ($dir === 'asc' ? ' &#x25B2;' : ' &#x25BC;') : ' <span style="opacity:.25">&#x2195;</span>';
                $qs   = http_build_query(array_merge(
                    request()->except(['sort','dir','page']),
                    ['sort' => $key, 'dir' => $nxt]
                ));
                return '<a href="?' . $qs . '" style="color:inherit;text-decoration:none;white-space:nowrap;cursor:pointer">'
                     . e($label) . $arr . '</a>';
            };
        @endphp
        <table class="sc-table zebra">
            <thead>
                <tr>
                    <th>{!! $sortHeader('jhc_id', 'JHC ID') !!}</th>
                    <th>{!! $sortHeader('patient_name', 'Patient Name') !!}</th>
                    <th>{!! $sortHeader('age', 'Age / Gender') !!}</th>
                    <th>{!! $sortHeader('address', 'Address') !!}</th>
                    <th>{!! $sortHeader('phone', 'Phone') !!}</th>
                    <th>{!! $sortHeader('city', 'City') !!}</th>
                    <th>{!! $sortHeader('created_at', 'Registered') !!}</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse($patients as $p)
            <tr>
                <td><span class="badge badge-navy mono">{{ $p->jhc_id }}</span>
                    @if($p->openemr_pid)<div class="text-small text-muted">OpenEMR: {{ $p->openemr_pid }}</div>@endif
                </td>
                <td>
                    <a href="{{ route('patients.show', $p) }}" style="font-weight:600;color:var(--sc-navy);text-decoration:none">
                        {{ $p->full_name }}
                    </a>
                    @if(!$p->is_active)<span class="badge badge-amber" style="margin-left:6px;font-size:10px">Inactive</span>@endif
                </td>
                <td>{{ $p->age }} / {{ $p->gender }}</td>
                <td>{{ $p->address ?: 'Port Blair' }}</td>
                <td>{{ $p->phone }}</td>
                <td>{{ $p->city ?: '—' }}</td>
                <td class="text-muted text-small">{{ $p->created_at->format('d M Y') }}</td>
                <td>
                    <div style="display:flex;gap:6px">
                        <a href="{{ route('patients.show', $p) }}" class="btn btn-sm btn-outline" title="View"><i class="fa-solid fa-eye"></i></a>
                        <a href="{{ route('appointments.create', ['patient_id' => $p->id]) }}" class="btn btn-sm btn-primary" title="Book Appointment"><i class="fa-solid fa-calendar-plus"></i></a>
                        <a href="{{ route('patients.edit', $p) }}" class="btn btn-sm btn-outline" title="Edit"><i class="fa-solid fa-pen"></i></a>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="8" style="padding:0">
                <x-empty-state
                    icon="fa-user-plus"
                    title="No patients yet"
                    message="Register your first patient to start creating encounters, prescriptions and bills."
                    action="Register patient"
                    :actionUrl="route('patients.create')" />
            </td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $patients->links() }}</div>
</div>
@endsection
