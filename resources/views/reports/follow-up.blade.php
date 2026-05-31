@extends('layouts.app')
@section('title', 'Follow-Up Tracker')
@section('content')

<div class="page-header">
    <div class="page-header-left">
        <h1>Follow-Up Tracker</h1>
        <div class="breadcrumb">Reports &rsaquo; Follow-Up Tracker</div>
    </div>
    <a href="{{ route('reports.follow-up.export', request()->query()) }}" class="btn btn-outline">
        <i class="fa-solid fa-file-csv"></i> Export CSV
    </a>
</div>

{{-- Filter tabs --}}
<div style="display:flex;gap:0;margin-bottom:20px;background:var(--sc-surface);border:1px solid var(--sc-border);border-radius:var(--radius);overflow:hidden;width:fit-content">
    @foreach(['overdue' => ['label'=>'Overdue','color'=>'var(--sc-red)'], 'today' => ['label'=>'Due Today','color'=>'var(--sc-amber)'], 'upcoming' => ['label'=>'Next 7 Days','color'=>'var(--sc-teal)']] as $key => $cfg)
    <a href="{{ route('reports.follow-up', array_merge(request()->query(), ['filter'=>$key])) }}"
       style="padding:10px 20px;font-size:13px;font-weight:600;text-decoration:none;border-right:1px solid var(--sc-border);
              {{ $filter === $key ? 'background:'.$cfg['color'].';color:#fff;' : 'color:var(--sc-muted);' }}">
        {{ $cfg['label'] }}
        <span style="margin-left:6px;background:{{ $filter === $key ? 'rgba(255,255,255,.25)' : 'var(--sc-border)' }};color:{{ $filter === $key ? '#fff' : 'var(--sc-navy)' }};padding:1px 7px;border-radius:10px;font-size:11px">
            {{ $counts[$key] }}
        </span>
    </a>
    @endforeach
</div>

{{-- Search / doctor filter --}}
<form method="GET" class="card mb-4">
    <div class="card-body" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;padding:14px 20px">
        <input type="hidden" name="filter" value="{{ $filter }}">
        <div class="form-group" style="margin:0;flex:1;min-width:200px">
            <label class="form-label">Search Patient</label>
            <input type="text" name="q" value="{{ $q }}" class="form-control" placeholder="Name or JHC ID">
        </div>
        <div class="form-group" style="margin:0;min-width:180px">
            <label class="form-label">Doctor</label>
            <select name="doctor_id" class="form-control">
                <option value="">All Doctors</option>
                @foreach($doctors as $doc)
                <option value="{{ $doc->id }}" {{ $doctorId == $doc->id ? 'selected' : '' }}>{{ $doc->full_name }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-filter"></i> Filter</button>
        <a href="{{ route('reports.follow-up', ['filter' => $filter]) }}" class="btn btn-outline">Reset</a>
    </div>
</form>

{{-- Table --}}
<div class="card">
    <div class="card-header">
        <i class="fa-solid fa-calendar-check"></i>
        {{ ['overdue'=>'Overdue Follow-Ups','today'=>'Due Today','upcoming'=>'Upcoming (Next 7 Days)'][$filter] }}
        <div class="card-actions"><span class="badge badge-navy">{{ $encounters->count() }}</span></div>
    </div>
    <div class="table-wrap">
        <table class="sc-table">
            <thead>
                <tr>
                    <th>Patient</th>
                    <th>JHC ID</th>
                    <th>Doctor</th>
                    <th>Encounter Date</th>
                    <th>Follow-Up Date</th>
                    <th>Days {{ $filter === 'overdue' ? 'Overdue' : 'Remaining' }}</th>
                    <th>Diagnosis</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($encounters as $enc)
                @php
                    $days = (int) now()->diffInDays($enc->follow_up_date, false);
                    $isOverdue = $days < 0;
                    $dayColor = $isOverdue ? 'var(--sc-red)' : ($days === 0 ? 'var(--sc-amber)' : 'var(--sc-teal)');
                    $diagnosis = $enc->diagnoses->first()?->icd_description ?? $enc->impression ?? '—';
                @endphp
                <tr>
                    <td class="fw-600">{{ $enc->patient->full_name }}</td>
                    <td class="mono text-small text-muted">{{ $enc->patient->jhc_id }}</td>
                    <td class="text-small">{{ $enc->doctor->full_name }}</td>
                    <td class="text-small text-muted">{{ $enc->encounter_date->format('d M Y') }}</td>
                    <td class="fw-600" style="color:{{ $dayColor }}">{{ $enc->follow_up_date->format('d M Y') }}</td>
                    <td class="fw-700 text-center" style="color:{{ $dayColor }}">
                        {{ $isOverdue ? abs($days).' days ago' : ($days === 0 ? 'Today' : $days.' days') }}
                    </td>
                    <td class="text-small text-muted">{{ Str::limit($diagnosis, 40) }}</td>
                    <td style="white-space:nowrap">
                        <button type="button" class="btn btn-sm btn-outline" title="Book follow-up appointment" onclick="scBookFollowUp({{ $enc->id }}, this)"><i class="fa-solid fa-calendar-plus"></i> Book</button>
                        <a href="{{ route('encounters.show', $enc) }}" class="btn btn-sm btn-outline">View</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" style="text-align:center;padding:48px;color:var(--sc-muted)">
                        <i class="fa-solid fa-calendar-check" style="font-size:2rem;margin-bottom:8px;display:block;opacity:.3"></i>
                        No follow-ups found for this filter.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
