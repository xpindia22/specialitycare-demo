@extends('layouts.app')
@section('title','OT Register')
@section('page-title','OT Register')
@section('content')

<div class="page-header">
  <div class="page-header-left">
    <h1>Operation Theater Register</h1>
    <div class="breadcrumb">IPD &rsaquo; OT Register</div>
  </div>
  <div style="display:flex;gap:8px">
    <button type="button" id="btn-list-view" onclick="setView('list')" class="btn btn-outline btn-sm"><i class="fa-solid fa-list"></i> List</button>
    <button type="button" id="btn-cal-view"  onclick="setView('cal')"  class="btn btn-outline btn-sm"><i class="fa-solid fa-calendar-week"></i> Schedule</button>
    <a href="{{ route('ot.print') }}?from={{ request('date', today()->toDateString()) }}&to={{ request('date', today()->toDateString()) }}" class="btn btn-outline" target="_blank">
      <i class="fa-solid fa-print"></i> Print Register
    </a>
    <a href="{{ route('ot.create') }}" class="btn btn-primary"><i class="fa-solid fa-plus"></i> New Operation</a>
  </div>
</div>

<div class="card mb-4">
  <div class="card-body" style="padding:12px 20px">
    <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end">
      <input type="text" name="q" class="form-control" value="{{ request('q') }}" placeholder="Search operation no / patient..." style="width:220px">
      <input type="date" name="date" class="form-control" value="{{ request('date') }}" style="width:150px">
      <select name="surgeon" class="form-control" style="width:200px">
        <option value="">All Surgeons</option>
        @foreach($doctors as $d)
        <option value="{{ $d->id }}" {{ request('surgeon') == $d->id ? 'selected' : '' }}>Dr. {{ $d->first_name }} {{ $d->last_name }}</option>
        @endforeach
      </select>
      <select name="status" class="form-control" style="width:140px">
        <option value="">All Status</option>
        @foreach(['scheduled'=>'Scheduled','in_progress'=>'In Progress','completed'=>'Completed','cancelled'=>'Cancelled'] as $v => $l)
        <option value="{{ $v }}" {{ request('status') === $v ? 'selected' : '' }}>{{ $l }}</option>
        @endforeach
      </select>
      <button type="submit" class="btn btn-primary"><i class="fa-solid fa-filter"></i> Filter</button>
      <a href="{{ route('ot.index') }}" class="btn btn-outline">Reset</a>
    </form>
  </div>
</div>

{{-- Schedule / Calendar View --}}
<div id="view-cal" style="display:none">
@php
    $days = [];
    $cursor = $weekStart->copy();
    while ($cursor->lte($weekEnd)) { $days[] = $cursor->copy(); $cursor->addDay(); }
@endphp
<div style="display:grid;grid-template-columns:repeat(7,1fr);gap:10px;margin-bottom:20px">
    @foreach($days as $day)
    @php $key = $day->format('Y-m-d'); $opsForDay = $weekOps[$key] ?? collect(); @endphp
    <div style="border:1px solid var(--sc-border);border-radius:8px;min-height:140px;overflow:hidden">
        <div style="padding:6px 10px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;background:{{ $day->isToday()?'var(--sc-teal)':'var(--sc-surface)' }};color:{{ $day->isToday()?'#fff':'var(--sc-muted)' }}">
            {{ $day->format('D') }}<br><span style="font-size:14px;font-weight:800;color:{{ $day->isToday()?'#fff':'var(--sc-text)' }}">{{ $day->format('d') }}</span>
        </div>
        <div style="padding:6px">
            @forelse($opsForDay as $op)
            <a href="{{ route('ot.show', $op) }}" style="display:block;text-decoration:none;background:#f0fdfa;border:1px solid #99f6e4;border-radius:6px;padding:5px 7px;margin-bottom:4px;font-size:11px;color:var(--sc-text)">
                <div style="font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $op->patient->full_name }}</div>
                <div style="color:var(--sc-muted)">{{ $op->procedure_name }}</div>
                @if($op->start_time)<div style="color:var(--sc-teal)"><i class="fa-solid fa-clock"></i> {{ \Carbon\Carbon::parse($op->start_time)->format('h:i A') }}</div>@endif
                <div style="color:var(--sc-muted)">Dr. {{ $op->surgeon->last_name }}</div>
            </a>
            @empty
            <div style="color:var(--sc-border);font-size:11px;text-align:center;padding:10px">–</div>
            @endforelse
        </div>
    </div>
    @endforeach
</div>
<div style="font-size:12px;color:var(--sc-muted);margin-bottom:16px;text-align:center">
    Week of {{ $weekStart->format('d M') }} – {{ $weekEnd->format('d M Y') }}
</div>
</div>

<div id="view-list">
<div class="card">
  <div class="table-wrap">
    <table class="sc-table">
      <thead>
        <tr><th>Op. No.</th><th>Patient</th><th>Date</th><th>Procedure</th><th>Surgeon</th><th>Anesthesia</th><th>Outcome</th><th>Status</th><th></th></tr>
      </thead>
      <tbody>
      @forelse($operations as $op)
      <tr>
        <td class="mono fw-600">{{ $op->operation_no }}</td>
        <td>
          <div class="fw-600">{{ $op->patient->full_name }}</div>
          <div class="text-small text-muted">{{ $op->patient->jhc_id }}</div>
        </td>
        <td class="text-small">{{ $op->operation_date->format('d M Y') }}<br>
          @if($op->start_time)<span class="text-muted">{{ \Carbon\Carbon::parse($op->start_time)->format('h:i A') }}</span>@endif
        </td>
        <td>{{ $op->procedure_name }}<div class="text-small text-muted">{{ $op->diagnosis }}</div></td>
        <td class="text-small">Dr. {{ $op->surgeon->first_name }} {{ $op->surgeon->last_name }}</td>
        <td class="text-small">{{ ucfirst($op->anesthesia_type) }}</td>
        <td>
          @php $oc = ['successful'=>'green','complicated'=>'amber','abandoned'=>'red','patient_expired'=>'red']; @endphp
          <span class="badge badge-{{ $oc[$op->outcome] ?? 'gray' }}">{{ \App\Models\Operation::OUTCOME_LABELS[$op->outcome] ?? $op->outcome }}</span>
        </td>
        <td>
          <span class="badge badge-{{ $op->status_color }}">{{ ucfirst(str_replace('_',' ',$op->status)) }}</span>
        </td>
        <td>
          <a href="{{ route('ot.show', $op) }}" class="btn btn-sm btn-outline">View</a>
        </td>
      </tr>
      @empty
      <tr><td colspan="9" class="text-center text-muted" style="padding:40px">No operations found.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
  @if($operations->hasPages())
  <div class="card-footer">{{ $operations->links() }}</div>
  @endif
</div>

</div>{{-- end view-list --}}

@push('scripts')
<script>
function setView(v) {
    document.getElementById('view-list').style.display = v === 'list' ? '' : 'none';
    document.getElementById('view-cal').style.display  = v === 'cal'  ? '' : 'none';
    document.getElementById('btn-list-view').classList.toggle('btn-primary', v === 'list');
    document.getElementById('btn-cal-view').classList.toggle('btn-primary', v === 'cal');
}
setView('list');
</script>
@endpush
@endsection
