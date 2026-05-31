{{-- FILE: resources/views/patients/timeline.blade.php --}}
@extends('layouts.app')
@section('title','Patient Timeline')
@section('page-title','Patient Timeline')
@section('content')
<div class="page-header">
    <div class="page-header-left">
        <h1>Timeline — {{ $patient->full_name }}</h1>
        <div class="breadcrumb"><a href="{{ route('patients.index') }}">Patients</a> › <a href="{{ route('patients.show', $patient) }}">{{ $patient->jhc_id }}</a> › Timeline</div>
    </div>
    <div style="display:flex;gap:8px">
        <button onclick="window.print()" class="btn btn-outline no-print"><i class="fa-solid fa-print"></i> Print</button>
        <a href="{{ route('patients.show', $patient) }}" class="btn btn-outline no-print"><i class="fa-solid fa-arrow-left"></i> Back to Profile</a>
    </div>
</div>

<div class="patient-strip mb-4">
    <div class="ps-avatar">{{ strtoupper(substr($patient->first_name,0,1)) }}</div>
    <div><div class="ps-name">{{ $patient->full_name }}</div><div class="ps-jhc_id">{{ $patient->jhc_id }}</div>
    <div class="ps-meta"><span>{{ $patient->age }}</span><span>{{ $patient->gender }}</span><span>{{ $patient->phone }}</span></div></div>
</div>

@if($timeline->isEmpty())
<div class="card"><div class="card-body text-center text-muted" style="padding:60px">No activity recorded yet for this patient.</div></div>
@else

{{-- ── Summary strip ───────────────────────────────────────────── --}}
<div class="tl-summary mb-4">
    <div class="tl-stat"><div class="tl-stat-val">{{ $summary['visits'] }}</div><div class="tl-stat-lbl">Visits</div></div>
    <div class="tl-stat"><div class="tl-stat-val">{{ $summary['last_visit'] ? \Carbon\Carbon::parse($summary['last_visit'])->diffForHumans(null, true) : '—' }}</div><div class="tl-stat-lbl">Last visit ago</div></div>
    <div class="tl-stat"><div class="tl-stat-val">{{ $summary['labs'] }}</div><div class="tl-stat-lbl">Lab orders</div></div>
    <div class="tl-stat"><div class="tl-stat-val">{{ $summary['cardiac'] }}</div><div class="tl-stat-lbl">Cardiac studies</div></div>
    <div class="tl-stat {{ $summary['outstanding'] > 0 ? 'tl-stat-due' : '' }}"><div class="tl-stat-val">₹{{ number_format($summary['outstanding'],0) }}</div><div class="tl-stat-lbl">Outstanding</div></div>
</div>

{{-- ── Filter chips ────────────────────────────────────────────── --}}
@php
    $counts = $timeline->countBy('type');
    $filters = [
        'all'          => ['All',          'fa-layer-group',                'var(--sc-navy)'],
        'encounter'    => ['Visits',       'fa-stethoscope',                'var(--sc-teal)'],
        'prescription' => ['Prescriptions','fa-prescription',               '#8b5cf6'],
        'lab'          => ['Labs',         'fa-flask-vial',                 'var(--sc-amber)'],
        'ecg'          => ['ECG',          'fa-heart-circle-bolt',          '#7c3aed'],
        'echo'         => ['Echo',         'fa-wave-square',                '#0369a1'],
        'bill'         => ['Bills',        'fa-file-invoice-dollar',        'var(--sc-navy)'],
        'appointment'  => ['Appointments', 'fa-calendar-check',             '#0891b2'],
        'admission'    => ['IPD',          'fa-bed-pulse',                  'var(--sc-green)'],
    ];
@endphp
<div class="tl-filters mb-4 no-print">
    @foreach($filters as $key => $f)
        @php
            if ($key === 'all') { $c = $timeline->count(); }
            elseif ($key === 'admission') { $c = ($counts['admission'] ?? 0) + ($counts['discharge'] ?? 0); }
            else { $c = $counts[$key] ?? 0; }
        @endphp
        @if($c > 0)
        <button type="button" class="tl-chip {{ $key === 'all' ? 'active' : '' }}" data-filter="{{ $key }}" style="--chip:{{ $f[2] }}">
            <i class="fa-solid {{ $f[1] }}"></i> {{ $f[0] }} <span class="tl-chip-count">{{ $c }}</span>
        </button>
        @endif
    @endforeach
</div>

<div class="timeline" id="tl-list">
    @php $lastYear = null; @endphp
    @foreach($timeline as $event)
        @php
            $year = \Carbon\Carbon::parse($event['date'])->format('Y');
            // group admission+discharge under the 'admission' filter bucket
            $bucket = in_array($event['type'], ['admission','discharge']) ? 'admission' : $event['type'];
        @endphp

        @if($year !== $lastYear)
            <div class="tl-year" data-year="{{ $year }}">{{ $year }}</div>
            @php $lastYear = $year; @endphp
        @endif

    <div class="timeline-item tl-row" data-type="{{ $bucket }}">
        <div class="timeline-dot {{ in_array($event['type'],['encounter','ecg','echo']) ? '' : 'gray' }}"></div>
        <div class="tl-date">
            <span>{{ \Carbon\Carbon::parse($event['date'])->format('d M Y') }}</span>
            <span class="tl-ago">{{ \Carbon\Carbon::parse($event['date'])->diffForHumans() }}</span>
        </div>

        @if($event['type'] === 'encounter')
            @php $enc = $event['data']; @endphp
            <div class="card" style="border-left:4px solid var(--sc-teal)">
                <div class="card-body" style="padding:12px 16px">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start">
                        <div>
                            <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px">
                                <i class="fa-solid fa-stethoscope" style="color:var(--sc-teal)"></i>
                                <span class="fw-600">Clinical Visit</span>
                                <span class="badge {{ $enc->visit_type === 'New Visit' ? 'badge-teal' : 'badge-navy' }}">{{ $enc->visit_type }}</span>
                                <span class="mono text-muted text-small">{{ $enc->encounter_no }}</span>
                            </div>
                            <div class="text-small text-muted">Dr. {{ $enc->doctor->full_name }}</div>
                            @if($enc->chief_complaint)<div class="text-small mt-1">{{ Str::limit(strip_tags($enc->chief_complaint), 80) }}</div>@endif
                            @if($enc->diagnoses->count())
                            <div style="margin-top:6px;display:flex;flex-wrap:wrap;gap:4px">
                                @foreach($enc->diagnoses as $d)<span class="badge badge-teal" style="font-size:10px">{{ $d->diagnosis_name }}</span>@endforeach
                            </div>
                            @endif
                        </div>
                        <a href="{{ route('encounters.show', $enc) }}" class="btn btn-sm btn-outline no-print">View</a>
                    </div>
                </div>
            </div>

        @elseif($event['type'] === 'prescription')
            @php $rx = $event['data']; @endphp
            <div class="card" style="border-left:4px solid #8b5cf6">
                <div class="card-body" style="padding:12px 16px">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start">
                        <div>
                            <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px">
                                <i class="fa-solid fa-prescription" style="color:#8b5cf6"></i>
                                <span class="fw-600">Prescription</span>
                                <span class="mono text-muted text-small">{{ $rx->prescription_no }}</span>
                            </div>
                            <div class="text-small text-muted">Dr. {{ $rx->doctor->full_name ?? '—' }} · {{ $rx->items->count() }} medicine(s)</div>
                            @if($rx->items->count())
                            <div style="margin-top:6px;display:flex;flex-wrap:wrap;gap:4px">
                                @foreach($rx->items->take(6) as $it)<span class="badge" style="font-size:10px;background:rgba(139,92,246,.12);color:#7c3aed">{{ $it->medicine->name ?? $it->generic_name ?? '—' }}</span>@endforeach
                                @if($rx->items->count() > 6)<span class="text-small text-muted">+{{ $rx->items->count() - 6 }} more</span>@endif
                            </div>
                            @endif
                        </div>
                        <a href="{{ route('prescriptions.print', $rx) }}" target="_blank" class="btn btn-sm btn-outline no-print">Print</a>
                    </div>
                </div>
            </div>

        @elseif($event['type'] === 'lab')
            @php $lo = $event['data']; @endphp
            <div class="card" style="border-left:4px solid var(--sc-navy)">
                <div class="card-body" style="padding:12px 16px">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start">
                        <div>
                            <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px">
                                <i class="fa-solid fa-flask-vial" style="color:var(--sc-navy)"></i>
                                <span class="fw-600">Lab Order</span>
                                <span class="badge {{ $lo->status === 'Completed' ? 'badge-green' : 'badge-amber' }}">{{ $lo->status }}</span>
                                <span class="mono text-muted text-small">{{ $lo->order_no }}</span>
                            </div>
                            <div style="margin-top:4px;display:flex;flex-wrap:wrap;gap:4px">
                                @foreach($lo->items as $item)<span class="badge badge-navy" style="font-size:10px">{{ $item->test?->name ?? $item->panel?->name ?? '—' }}</span>@endforeach
                            </div>
                        </div>
                        <a href="{{ route('lab.order', $lo) }}" class="btn btn-sm btn-outline no-print">View</a>
                    </div>
                </div>
            </div>

        @elseif($event['type'] === 'ecg')
            @php $ecg = $event['data']; @endphp
            <div class="card" style="border-left:4px solid #7c3aed">
                <div class="card-body" style="padding:12px 16px">
                    <div style="display:flex;justify-content:space-between">
                        <div>
                            <div style="display:flex;align-items:center;gap:8px"><i class="fa-solid fa-heart-circle-bolt" style="color:#7c3aed"></i><span class="fw-600">ECG</span><span class="mono text-muted text-small">{{ $ecg->record_no }}</span></div>
                            @if($ecg->rhythm)
                            <div class="text-small mt-1">{{ $ecg->rhythm }}{{ $ecg->heart_rate ? ' · '.$ecg->heart_rate.' bpm' : '' }}</div>
                            @endif
                        </div>
                        <a href="{{ route('ecg.show', $ecg) }}" class="btn btn-sm btn-outline no-print">View</a>
                    </div>
                </div>
            </div>

        @elseif($event['type'] === 'echo')
            @php $echo = $event['data']; @endphp
            <div class="card" style="border-left:4px solid #0369a1">
                <div class="card-body" style="padding:12px 16px">
                    <div style="display:flex;justify-content:space-between">
                        <div>
                            <div style="display:flex;align-items:center;gap:8px"><i class="fa-solid fa-wave-square" style="color:#0369a1"></i><span class="fw-600">Echocardiography</span><span class="mono text-muted text-small">{{ $echo->record_no }}</span></div>
                            @if($echo->ef_percent)<div class="text-small mt-1">EF: {{ $echo->ef_percent }}%</div>@endif
                        </div>
                        <a href="{{ route('echo.show', $echo) }}" class="btn btn-sm btn-outline no-print">View</a>
                    </div>
                </div>
            </div>

        @elseif($event['type'] === 'bill')
            @php $bill = $event['data']; $paid = $bill->status === 'Paid'; @endphp
            <div class="card" style="border-left:4px solid {{ $paid ? 'var(--sc-green)' : 'var(--sc-amber)' }}">
                <div class="card-body" style="padding:12px 16px">
                    <div style="display:flex;justify-content:space-between">
                        <div>
                            <div style="display:flex;align-items:center;gap:8px"><i class="fa-solid fa-file-invoice-dollar" style="color:{{ $paid ? 'var(--sc-green)' : 'var(--sc-amber)' }}"></i><span class="fw-600">Bill</span><span class="mono text-muted text-small">{{ $bill->bill_no }}</span></div>
                            <div class="text-small mt-1">₹{{ number_format($bill->total_amount,2) }}
                                <span class="badge {{ $paid ? 'badge-green' : ($bill->status === 'Partial' ? 'badge-amber' : 'badge-red') }}" style="font-size:10px;margin-left:4px">{{ $bill->status }}</span>
                                @if(!$paid && $bill->due_amount > 0)<span class="text-small" style="color:var(--sc-red);margin-left:4px">₹{{ number_format($bill->due_amount,0) }} due</span>@endif
                            </div>
                        </div>
                        <a href="{{ route('billing.show', $bill) }}" class="btn btn-sm btn-outline no-print">View</a>
                    </div>
                </div>
            </div>

        @elseif($event['type'] === 'appointment')
            @php
                $appt = $event['data'];
                $isFuture = \Carbon\Carbon::parse($appt->appointment_date)->isFuture();
                $apptColor = match($appt->status) {
                    'Cancelled','No-Show' => 'var(--sc-muted)',
                    'Completed'           => 'var(--sc-green)',
                    default               => '#0891b2',
                };
            @endphp
            <div class="card" style="border-left:4px solid {{ $apptColor }}">
                <div class="card-body" style="padding:12px 16px">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start">
                        <div>
                            <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px">
                                <i class="fa-solid fa-calendar-check" style="color:{{ $apptColor }}"></i>
                                <span class="fw-600">{{ $isFuture ? 'Upcoming Appointment' : 'Appointment' }}</span>
                                <span class="badge" style="font-size:10px;background:rgba(8,145,178,.12);color:{{ $apptColor }}">{{ $appt->status }}</span>
                                <span class="mono text-muted text-small">{{ $appt->appointment_no }}</span>
                            </div>
                            <div class="text-small text-muted">
                                {{ \Carbon\Carbon::parse($appt->appointment_time)->format('h:i A') }} · Dr. {{ $appt->doctor->full_name ?? '—' }}
                                @if($appt->type) · {{ $appt->type }}@endif
                            </div>
                            @if($appt->chief_complaint)<div class="text-small mt-1">{{ Str::limit(strip_tags($appt->chief_complaint), 80) }}</div>@endif
                        </div>
                        <a href="{{ route('appointments.show', $appt) }}" class="btn btn-sm btn-outline no-print">View</a>
                    </div>
                </div>
            </div>

        @elseif($event['type'] === 'admission')
            @php $adm = $event['data']; @endphp
            <div class="card" style="border-left:4px solid var(--sc-teal)">
                <div class="card-body" style="padding:12px 16px">
                    <div style="display:flex;justify-content:space-between;align-items:center">
                        <div>
                            <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px">
                                <i class="fa-solid fa-bed-pulse" style="color:var(--sc-teal)"></i>
                                <span class="fw-600">IPD Admission</span>
                                <span class="badge badge-teal">{{ \App\Models\Admission::ADMISSION_TYPES[$adm->admission_type] ?? $adm->admission_type }}</span>
                                <span class="mono text-muted text-small">{{ $adm->ipd_no }}</span>
                            </div>
                            <div class="text-small text-muted">{{ $adm->ward->name }} &nbsp;·&nbsp; Dr. {{ $adm->admittingDoctor->full_name }}</div>
                            @if($adm->provisional_diagnosis)<div class="text-small mt-1">Dx: {{ $adm->provisional_diagnosis }}</div>@endif
                        </div>
                        <a href="{{ route('admissions.show', $adm) }}" class="btn btn-sm btn-outline no-print">View</a>
                    </div>
                </div>
            </div>

        @elseif($event['type'] === 'discharge')
            @php $adm = $event['data']; @endphp
            <div class="card" style="border-left:4px solid var(--sc-green)">
                <div class="card-body" style="padding:12px 16px">
                    <div style="display:flex;justify-content:space-between;align-items:center">
                        <div>
                            <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px">
                                <i class="fa-solid fa-person-walking-arrow-right" style="color:var(--sc-green)"></i>
                                <span class="fw-600">Discharged</span>
                                <span class="mono text-muted text-small">{{ $adm->ipd_no }}</span>
                            </div>
                            <div class="text-small text-muted">{{ $adm->ward->name }} &nbsp;·&nbsp; Stay: {{ $adm->days_admitted }} day(s)
                                @if($adm->discharge_condition) &nbsp;·&nbsp; {{ \App\Models\Admission::DISCHARGE_CONDITIONS[$adm->discharge_condition] ?? '' }}@endif
                            </div>
                        </div>
                        <a href="{{ route('admissions.show', $adm) }}" class="btn btn-sm btn-outline no-print">View</a>
                    </div>
                </div>
            </div>
        @endif
    </div>
    @endforeach
</div>

<div id="tl-empty" class="card" style="display:none"><div class="card-body text-center text-muted" style="padding:40px">No events of this type.</div></div>
@endif

@push('styles')
<style>
.tl-summary{display:grid;grid-template-columns:repeat(5,1fr);gap:12px}
.tl-stat{background:#fff;border:1px solid var(--sc-border);border-radius:10px;padding:14px 16px;text-align:center}
.tl-stat-val{font-size:20px;font-weight:700;color:var(--sc-navy);line-height:1.1}
.tl-stat-lbl{font-size:11px;color:var(--sc-muted);margin-top:4px;text-transform:uppercase;letter-spacing:.04em}
.tl-stat-due .tl-stat-val{color:var(--sc-red)}
.tl-filters{display:flex;flex-wrap:wrap;gap:8px}
.tl-chip{display:inline-flex;align-items:center;gap:6px;padding:6px 12px;border:1px solid var(--sc-border);background:#fff;border-radius:999px;font-size:12px;font-weight:600;color:var(--sc-muted);cursor:pointer;transition:all .12s}
.tl-chip i{color:var(--chip)}
.tl-chip:hover{border-color:var(--chip);color:var(--chip)}
.tl-chip.active{background:var(--chip);border-color:var(--chip);color:#fff}
.tl-chip.active i{color:#fff}
.tl-chip-count{background:rgba(0,0,0,.08);border-radius:999px;padding:1px 7px;font-size:11px}
.tl-chip.active .tl-chip-count{background:rgba(255,255,255,.25)}
.tl-year{position:sticky;top:0;z-index:5;background:var(--sc-bg);font-size:13px;font-weight:700;color:var(--sc-navy);padding:8px 0 8px 44px;margin:6px 0;border-bottom:1px solid var(--sc-border)}
.tl-date{display:flex;align-items:baseline;gap:8px;font-size:11px;color:var(--sc-muted);margin-bottom:4px}
.tl-ago{font-size:10px;opacity:.75}
@media print{.no-print{display:none!important}.tl-year{position:static}}
@media (max-width:720px){.tl-summary{grid-template-columns:repeat(2,1fr)}}
</style>
@endpush

@push('scripts')
<script>
(function(){
    const chips = document.querySelectorAll('.tl-chip');
    const rows  = document.querySelectorAll('.tl-row');
    const years = document.querySelectorAll('.tl-year');
    const empty = document.getElementById('tl-empty');

    function apply(filter){
        let shown = 0;
        rows.forEach(r => {
            const ok = filter === 'all' || r.dataset.type === filter;
            r.style.display = ok ? '' : 'none';
            if(ok) shown++;
        });
        // hide a year header if no visible rows follow it before the next header
        years.forEach(y => {
            let n = y.nextElementSibling, visible = false;
            while(n && !n.classList.contains('tl-year')){
                if(n.classList.contains('tl-row') && n.style.display !== 'none'){ visible = true; break; }
                n = n.nextElementSibling;
            }
            y.style.display = visible ? '' : 'none';
        });
        if(empty) empty.style.display = shown === 0 ? '' : 'none';
    }

    chips.forEach(c => c.addEventListener('click', () => {
        chips.forEach(x => x.classList.remove('active'));
        c.classList.add('active');
        apply(c.dataset.filter);
    }));
})();
</script>
@endpush
@endsection
