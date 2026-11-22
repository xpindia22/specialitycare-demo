{{-- FILE: resources/views/opd/directory.blade.php — Find a Doctor (department-wise) --}}
@extends('layouts.app')
@section('title','Find a Doctor')
@section('page-title','Find a Doctor')

@push('styles')
<style>
    .dir-dept { margin-bottom:18px; }
    .dir-dept-head { display:flex; align-items:center; gap:10px; margin:0 0 8px; }
    .dir-dept-icon { width:34px; height:34px; border-radius:9px; display:inline-flex; align-items:center; justify-content:center; font-size:15px; }
    .dir-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(290px, 1fr)); gap:12px; }
    .dir-card { background:var(--sc-surface); border:1px solid var(--sc-border); border-radius:10px; padding:14px; display:flex; flex-direction:column; gap:8px; }
    .dir-top { display:flex; gap:10px; align-items:center; }
    .dir-avatar { width:46px; height:46px; border-radius:50%; background:var(--sc-bg); overflow:hidden; display:flex; align-items:center; justify-content:center; flex-shrink:0; color:var(--sc-muted); }
    .dir-avatar img { width:100%; height:100%; object-fit:cover; }
    .dir-meta { font-size:12px; color:var(--sc-muted); }
    .dir-status { font-size:12px; padding:6px 10px; border-radius:6px; }
    .dir-actions { display:flex; gap:6px; flex-wrap:wrap; margin-top:auto; }
</style>
@endpush

@section('content')
<div class="page-header">
    <div class="page-header-left">
        <h1>Find a Doctor</h1>
        <div class="breadcrumb">Specialists department-wise, with who is sitting on {{ $date->isToday() ? 'today' : $date->format('D, d M Y') }}</div>
    </div>
    @include('opd._toolbar', ['inline'=>true])
</div>

<div class="card mb-4">
    <div class="card-body" style="padding:14px 20px">
        <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end">
            <div style="flex:1;min-width:200px">
                <label class="form-label" style="margin-bottom:4px">Search</label>
                <input type="text" id="dir-search" class="form-control" placeholder="Doctor, speciality or language…" oninput="filterDir()" autocomplete="off">
            </div>
            <div>
                <label class="form-label" style="margin-bottom:4px">Speciality</label>
                <select id="dir-dept" class="form-control" onchange="filterDir()" style="min-width:180px">
                    <option value="">All specialities</option>
                    @foreach($departments as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach
                </select>
            </div>
            <div>
                <label class="form-label" style="margin-bottom:4px">Date</label>
                <input type="date" name="date" class="form-control" value="{{ $date->toDateString() }}" min="{{ today()->toDateString() }}" onchange="this.form.submit()" style="width:160px">
            </div>
            <label style="display:flex;gap:6px;align-items:center;cursor:pointer;margin-bottom:8px">
                <input type="checkbox" id="dir-sitting" onchange="filterDir()"> Only doctors sitting this day
            </label>
        </form>
    </div>
</div>

@forelse($departments as $d)
<div class="dir-dept" data-dept="{{ $d->id }}">
    <div class="dir-dept-head">
        <span class="dir-dept-icon" style="background:{{ $d->color ?: '#64748b' }}1a;color:{{ $d->color ?: '#64748b' }}"><i class="fa-solid {{ $d->icon ?: 'fa-hospital' }}"></i></span>
        <h3 style="margin:0;font-size:16px">{{ $d->name }}</h3>
        @if($d->room)<span class="badge badge-grey"><i class="fa-solid fa-door-open"></i> {{ $d->room }}</span>@endif
        @if($d->token_prefix)<span class="badge badge-teal mono">{{ $d->token_prefix }}-tokens</span>@endif
    </div>
    <div class="dir-grid">
        @foreach($d->opdDoctors as $doc)
        @php $i = $info[$d->id][$doc->id]; $sitting = !$i['leave'] && count($i['sessions']); @endphp
        <div class="dir-card" data-sitting="{{ $sitting ? 1 : 0 }}"
             data-search="{{ strtolower($doc->first_name.' '.$doc->last_name.' '.$doc->specialization.' '.$doc->qualification.' '.$doc->languages.' '.$d->name) }}">
            <div class="dir-top">
                <div class="dir-avatar">@if($doc->photo)<img src="{{ asset('storage/'.$doc->photo) }}" alt="">@else<i class="fa-solid fa-user-doctor fa-lg"></i>@endif</div>
                <div style="min-width:0">
                    <div class="fw-700">Dr. {{ $doc->first_name }} {{ $doc->last_name }}</div>
                    <div class="dir-meta">{{ $doc->qualification }}@if($doc->experience_years) &middot; {{ $doc->experience_years }} yrs @endif</div>
                    <div class="dir-meta">{{ $doc->specialization }}</div>
                </div>
            </div>
            <div class="dir-meta">
                @if($doc->consultation_fee > 0)<i class="fa-solid fa-indian-rupee-sign"></i> {{ number_format($doc->consultation_fee, 0) }} @endif
                @if($doc->languages) &middot; <i class="fa-solid fa-language"></i> {{ $doc->languages }} @endif
            </div>
            @if($i['timetable'])<div class="dir-meta"><i class="fa-regular fa-clock"></i> {{ $i['timetable'] }}</div>@endif

            @if($i['leave'])
                <div class="dir-status" style="background:#fef2f2;color:#991b1b"><i class="fa-solid fa-plane-departure"></i> On leave{{ $i['leave']->reason ? ': '.$i['leave']->reason : '' }} (till {{ $i['leave']->to_date->format('d M') }})</div>
            @elseif(!count($i['sessions']))
                <div class="dir-status" style="background:var(--sc-bg);color:var(--sc-muted)"><i class="fa-solid fa-moon"></i> No OPD this day</div>
            @else
                <div class="dir-status" style="background:#f0fdf4;color:#14532d">
                    <i class="fa-solid fa-circle-check"></i>
                    {{ collect($i['sessions'])->map(fn ($s) => substr($s['start'],0,5).'–'.substr($s['end'],0,5).($s['room'] ? ' ('.$s['room'].')' : ''))->implode(', ') }}
                    @if($i['full']) &middot; <b style="color:#b45309">Fully booked</b>
                    @elseif($i['next']) &middot; next free {{ $i['next'] }} ({{ $i['free'] }} free)
                    @endif
                    @if($date->isToday() && $i['waiting']) &middot; {{ $i['waiting'] }} waiting @endif
                </div>
            @endif

            <div class="dir-actions">
                <a href="{{ route('appointments.create', ['department_id' => $d->id, 'doctor_id' => $doc->id, 'date' => $date->toDateString()]) }}" class="btn btn-sm btn-primary"><i class="fa-solid fa-calendar-plus"></i> Book</a>
                <a href="{{ route('doctors.show', $doc) }}" class="btn btn-sm btn-outline"><i class="fa-solid fa-id-card"></i> Profile</a>
            </div>
        </div>
        @endforeach
    </div>
</div>
@empty
<div class="card"><div class="card-body text-center text-muted" style="padding:40px">No departments with doctors yet. Set them up under Administration → Departments and each doctor's Edit page.</div></div>
@endforelse

<div id="dir-none" class="card" style="display:none"><div class="card-body text-center text-muted" style="padding:30px">No doctor matches.</div></div>

<script>
function filterDir() {
    const q = document.getElementById('dir-search').value.trim().toLowerCase();
    const dep = document.getElementById('dir-dept').value;
    const sitting = document.getElementById('dir-sitting').checked;
    let any = false;
    document.querySelectorAll('.dir-dept').forEach(sec => {
        let shown = 0;
        sec.querySelectorAll('.dir-card').forEach(c => {
            const ok = (!dep || sec.dataset.dept === dep) && (!q || c.dataset.search.includes(q)) && (!sitting || c.dataset.sitting === '1');
            c.style.display = ok ? '' : 'none'; if (ok) shown++;
        });
        sec.style.display = shown ? '' : 'none'; if (shown) any = true;
    });
    document.getElementById('dir-none').style.display = any ? 'none' : '';
}
</script>
@endsection
