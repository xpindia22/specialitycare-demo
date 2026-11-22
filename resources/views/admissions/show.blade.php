{{-- FILE: resources/views/admissions/show.blade.php --}}
@extends('layouts.app')
@section('title','Admission — ' . $admission->ipd_no)
@section('page-title','IPD Record')
@push('styles')
<style>
.rx-drop-item:hover { background:#f0f9ff; }
/* Prevent right-pane grids from overflowing the content area */
.ipd-two-col { display:grid; gap:20px; align-items:start;
    grid-template-columns: minmax(0,1fr) clamp(260px, 30%, 340px); }
@media (max-width: 1100px) {
    .ipd-two-col { grid-template-columns: 1fr; }
}
</style>
@endpush
@section('content')

@php
$u = auth()->user();
$notes         = $admission->notes()->with('writtenBy')->get();
$vitals        = $admission->vitals()->with('recordedBy')->get();
$charges       = $admission->charges()->with('createdBy')->get();
$prescriptions = $admission->prescriptions()->with(['logs' => fn($q) => $q->whereDate('log_date', today())])->get();
$latestVitals  = $vitals->first();
$totalCharges  = $charges->sum('amount') + $admissionPackages->sum('quoted_price');
$totalPkgPaid  = $admissionPackages->sum('paid_amount');
@endphp

<div class="page-header">
    <div class="page-header-left">
        <h1 class="mono">{{ $admission->ipd_no }}</h1>
        <div class="breadcrumb"><a href="{{ route('admissions.index') }}">Admissions</a> &rsaquo; {{ $admission->ipd_no }}</div>
    </div>
    <div style="display:flex;gap:8px">
        <a href="{{ route('admissions.slip', $admission) }}" class="btn btn-outline" target="_blank">
            <i class="fa-solid fa-print"></i> Admission Slip
        </a>
        <a href="{{ route('admissions.bill', $admission) }}" class="btn btn-outline" target="_blank">
            <i class="fa-solid fa-file-invoice-dollar"></i> IPD Bill
        </a>
        @if($admission->status === 'admitted')
        <button type="button" class="btn btn-outline" onclick="openTransferModal()">
            <i class="fa-solid fa-right-left"></i> Transfer
        </button>
        <a href="{{ route('admissions.discharge', $admission) }}" class="btn btn-amber">
            <i class="fa-solid fa-person-walking-arrow-right"></i> Discharge
        </a>
        @endif
        @if($admission->status === 'discharged')
        <a href="{{ route('admissions.summary', $admission) }}" class="btn btn-navy" target="_blank">
            <i class="fa-solid fa-print"></i> Discharge Summary
        </a>
        @endif
        <a href="{{ route('admissions.referral-letter', $admission) }}" class="btn btn-outline" target="_blank">
            <i class="fa-solid fa-share-nodes"></i> Referral Letter
        </a>
        <a href="{{ route('admissions.index') }}" class="btn btn-outline">Back</a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> {{ session('success') }}</div>
@endif
@if(session('error'))
<div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation"></i> {{ session('error') }}</div>
@endif

{{-- Patient strip --}}
<div class="patient-strip">
    <div class="ps-avatar">
        @if($admission->patient->photo)
            <img src="{{ asset('storage/'.$admission->patient->photo) }}" style="width:100%;height:100%;object-fit:cover;border-radius:50%">
        @else
            {{ strtoupper(substr($admission->patient->first_name,0,1)) }}
        @endif
    </div>
    <div>
        <div class="ps-name">{{ $admission->patient->full_name }}</div>
        <div class="ps-jhc_id">{{ $admission->patient->jhc_id }}</div>
        <div class="ps-meta">
            <span><i class="fa-solid fa-venus-mars"></i> {{ ucfirst($admission->patient->gender) }}</span>
            <span><i class="fa-solid fa-cake-candles"></i> {{ $admission->patient->age }}</span>
            @if($admission->patient->blood_group)
            <span><i class="fa-solid fa-droplet"></i> {{ $admission->patient->blood_group }}</span>
            @endif
            <span><i class="fa-solid fa-phone"></i> {{ $admission->patient->phone }}</span>
            <span><i class="fa-solid fa-bed"></i> {{ $admission->ward->name }} — Bed {{ $admission->bed->bed_number }}</span>
        </div>
    </div>
    <div class="ps-actions">
        <span class="badge badge-{{ $admission->status_color }}" style="font-size:12px;padding:5px 12px">{{ $admission->status_label }}</span>
        <a href="{{ route('patients.show', $admission->patient) }}" class="btn btn-sm btn-outline" style="color:#fff;border-color:rgba(255,255,255,.3)">
            Patient Record
        </a>
        @if($u->hasRole(['admin','doctor','nurse']))
        <a href="{{ route('icu.chart', $admission) }}" class="btn btn-sm" style="background:#b91c1c;color:#fff;border-color:#b91c1c">
            <i class="fa-solid fa-heart-pulse"></i> ICU Chart
        </a>
        @endif
    </div>
</div>

{{-- Allergy Alert --}}
@if($admission->patient->allergies)
<div style="background:#fef2f2;border:1px solid #fca5a5;border-radius:8px;padding:12px 18px;margin-bottom:16px;display:flex;align-items:center;gap:12px">
    <i class="fa-solid fa-triangle-exclamation" style="font-size:1.4rem;color:#dc2626;flex-shrink:0"></i>
    <div>
        <span style="font-weight:700;color:#991b1b;font-size:13px;text-transform:uppercase;letter-spacing:.5px">ALLERGY ALERT</span>
        <span style="color:#7f1d1d;font-size:14px;margin-left:10px">{{ $admission->patient->allergies }}</span>
    </div>
</div>
@endif

{{-- Quick vitals strip (latest) --}}
@if($latestVitals)
<div style="display:grid;grid-template-columns:repeat(8,minmax(0,1fr));gap:10px;margin-bottom:20px">
    @foreach([
        ['label'=>'Temp','val'=> $latestVitals->temperature ? $latestVitals->temperature.'°F' : '—','icon'=>'fa-thermometer-half'],
        ['label'=>'Pulse','val'=> $latestVitals->pulse ? $latestVitals->pulse.' bpm' : '—','icon'=>'fa-heart-pulse'],
        ['label'=>'BP','val'=> $latestVitals->bp ?? '—','icon'=>'fa-gauge'],
        ['label'=>'RR','val'=> $latestVitals->respiration_rate ? $latestVitals->respiration_rate.' /min' : '—','icon'=>'fa-lungs'],
        ['label'=>'SpO₂','val'=> $latestVitals->spo2 ? $latestVitals->spo2.'%' : '—','icon'=>'fa-circle-dot'],
        ['label'=>'Glucose','val'=> $latestVitals->blood_glucose ? $latestVitals->blood_glucose.' mg/dL' : '—','icon'=>'fa-droplet'],
        ['label'=>'Weight','val'=> $latestVitals->weight ? $latestVitals->weight.' kg' : '—','icon'=>'fa-weight-scale'],
    ] as $v)
    <div class="vital-box">
        <div class="vb-val" style="font-size:14px">{{ $v['val'] }}</div>
        <div class="vb-label"><i class="fa-solid {{ $v['icon'] }}"></i> {{ $v['label'] }}</div>
    </div>
    @endforeach
    @php $nl = $latestVitals->news2 !== null ? \App\Services\EarlyWarning::LEVELS[$latestVitals->news_level] : null; @endphp
    <div class="vital-box" @if($nl) style="background:{{ $nl['bg'] }};color:{{ $nl['color'] }}" title="{{ $nl['label'] }} risk: {{ $nl['action'] }}" @endif>
        <div class="vb-val" style="font-size:14px;{{ $nl ? 'color:'.$nl['color'] : '' }}">{{ $latestVitals->news2 ?? '—' }}{{ $latestVitals->news2_partial ? '*' : '' }}</div>
        <div class="vb-label"><i class="fa-solid fa-triangle-exclamation"></i> NEWS2</div>
    </div>
</div>
<div style="font-size:11px;color:var(--sc-muted);margin-top:-14px;margin-bottom:16px;text-align:right">
    Last vitals: {{ $latestVitals->recorded_at->format('d M Y, h:i A') }} by {{ $latestVitals->recordedBy->name }}
</div>
@endif

{{-- Tabs --}}
<div class="tab-nav" id="mainTabs">
    <button class="tab-btn active" data-tab="overview"><i class="fa-solid fa-clipboard-list"></i> Overview</button>
    <button class="tab-btn" data-tab="notes"><i class="fa-solid fa-notes-medical"></i> Notes <span class="badge badge-navy" style="font-size:10px">{{ $notes->count() }}</span></button>
    <button class="tab-btn" data-tab="vitals"><i class="fa-solid fa-heart-pulse"></i> Vitals <span class="badge badge-navy" style="font-size:10px">{{ $vitals->count() }}</span></button>
    <button class="tab-btn" data-tab="mar"><i class="fa-solid fa-pills"></i> Medicines <span class="badge badge-navy" style="font-size:10px">{{ $prescriptions->count() }}</span></button>
    <button class="tab-btn" data-tab="nursing"><i class="fa-solid fa-user-nurse"></i> Nursing <span class="badge badge-green" style="font-size:10px">{{ $nursingToday->count() }}/3</span></button>
    <button class="tab-btn" data-tab="io"><i class="fa-solid fa-droplet"></i> I/O Chart <span class="badge {{ $ioTotalIn >= $ioTotalOut ? 'badge-teal' : 'badge-amber' }}" style="font-size:10px">{{ $ioToday->count() }}</span></button>
    <button class="tab-btn" data-tab="labs"><i class="fa-solid fa-flask"></i> Lab <span class="badge badge-navy" style="font-size:10px">{{ $labOrders->count() }}</span></button>
    <button class="tab-btn" data-tab="radiology"><i class="fa-solid fa-radiation"></i> Radiology <span class="badge badge-navy" style="font-size:10px">{{ $radiologyOrders->count() }}</span></button>
    <button class="tab-btn" data-tab="ot"><i class="fa-solid fa-syringe"></i> OT <span class="badge badge-navy" style="font-size:10px">{{ $operations->count() }}</span></button>
    <button class="tab-btn" data-tab="consents"><i class="fa-solid fa-file-signature"></i> Consents <span class="badge {{ $consents->where('is_obtained',true)->count() === count(\App\Models\ConsentForm::TYPES) ? 'badge-green' : 'badge-amber' }}" style="font-size:10px">{{ $consents->where('is_obtained',true)->count() }}/{{ count(\App\Models\ConsentForm::TYPES) }}</span></button>
    @php $billingBadgeBalance = $totalCharges - $deposits->sum('amount') - $totalPkgPaid; @endphp
    <button class="tab-btn" data-tab="billing"><i class="fa-solid fa-file-invoice-dollar"></i> Billing
        <span class="badge {{ $billingBadgeBalance > 0 ? 'badge-amber' : 'badge-green' }}" style="font-size:10px">
            {{ $billingBadgeBalance > 0 ? '₹'.number_format($billingBadgeBalance,0).' due' : ($billingBadgeBalance < 0 ? '₹'.number_format(abs($billingBadgeBalance),0).' refund' : 'settled') }}
        </span>
    </button>
    @if($admission->status === 'discharged')
    <button class="tab-btn" data-tab="discharge"><i class="fa-solid fa-person-walking-arrow-right"></i> Discharge</button>
    @endif
    <button class="tab-btn" data-tab="tasks"><i class="fa-solid fa-list-check"></i> Tasks <span class="badge {{ isset($nursingTasks) && $nursingTasks->where('completed_at',null)->count() ? 'badge-amber' : 'badge-green' }}" style="font-size:10px">{{ isset($nursingTasks) ? $nursingTasks->where('completed_at',null)->count() : 0 }} pending</span></button>
    <button class="tab-btn" data-tab="diet"><i class="fa-solid fa-utensils"></i> Diet</button>
    <button class="tab-btn" data-tab="handover"><i class="fa-solid fa-clipboard-list"></i> Handover <span class="badge badge-navy" style="font-size:10px">{{ $handoverNotes->count() }}</span></button>
    <button class="tab-btn" data-tab="preauth"><i class="fa-solid fa-shield-halved"></i> Pre-Auth <span class="badge {{ $preauths->where('status','approved')->count() ? 'badge-teal' : 'badge-amber' }}" style="font-size:10px">{{ $preauths->count() }}</span></button>
</div>

{{-- ── TAB: OVERVIEW ── --}}
<div class="tab-pane active" id="tab-overview">
    <div class="ipd-two-col">
        <div class="card">
            <div class="card-header"><i class="fa-solid fa-bed-pulse"></i> Admission Details</div>
            <div class="card-body">
                @if($admission->is_mlc)
                <div class="alert" style="background:#fef2f2;border:1px solid #fca5a5;color:#991b1b;margin-bottom:14px">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <div>
                        <strong>MLC Case</strong>
                        @if($admission->mlc_no) — MLC No: <strong>{{ $admission->mlc_no }}</strong>@endif
                        @if($admission->mlc_police_station) | Police Station: {{ $admission->mlc_police_station }}@endif
                    </div>
                    <span class="badge badge-red" style="margin-left:auto;font-size:13px;padding:4px 12px">MLC</span>
                </div>
                @endif
                <div class="grid" style="grid-template-columns:1fr 1fr 1fr;gap:16px;margin-bottom:16px">
                    <div>
                        <div class="form-label">IPD Number</div>
                        <div class="fw-700 mono">{{ $admission->ipd_no }}</div>
                    </div>
                    <div>
                        <div class="form-label">Admission Type</div>
                        <div>{{ \App\Models\Admission::ADMISSION_TYPES[$admission->admission_type] ?? $admission->admission_type }}</div>
                    </div>
                    <div>
                        <div class="form-label">Days Admitted</div>
                        <div class="fw-700">{{ $admission->days_admitted }} day{{ $admission->days_admitted != 1 ? 's' : '' }}</div>
                    </div>
                    <div>
                        <div class="form-label">Admission Date</div>
                        <div>{{ $admission->admission_date->format('d M Y, h:i A') }}</div>
                    </div>
                    <div>
                        <div class="form-label">Admitting Doctor</div>
                        <div>{{ $admission->admittingDoctor->full_name }}</div>
                    </div>
                    <div>
                        <div class="form-label">Referred By</div>
                        <div>
                            @if($admission->referralSource)
                                <span style="font-weight:600">{{ $admission->referralSource->name }}</span>
                                <span style="font-size:11px;color:#888;margin-left:6px">
                                    (Incentive: @if($admission->referralSource->rate_type==='fixed') ₹{{ number_format($admission->referralSource->rate_value,0) }}/pt @else {{ $admission->referralSource->rate_value }}% of bill @endif)
                                </span>
                            @else
                                {{ $admission->referred_by ?: '—' }}
                            @endif
                        </div>
                    </div>
                </div>
                @if($admission->provisional_diagnosis)
                <div style="padding-top:14px;border-top:1px solid var(--sc-border)">
                    <div class="form-label">Provisional Diagnosis</div>
                    <div style="font-size:14px">{{ $admission->provisional_diagnosis }}</div>
                </div>
                @endif
                @if($admission->notes)
                <div style="margin-top:12px">
                    <div class="form-label">Admission Notes</div>
                    <div style="white-space:pre-wrap">{{ $admission->notes }}</div>
                </div>
                @endif
            </div>
        </div>
        <div>
            @if($admission->status === 'admitted')
            <div class="card mb-3">
                <div class="card-header"><i class="fa-solid fa-bolt"></i> Quick Actions</div>
                <div class="card-body" style="display:flex;flex-direction:column;gap:8px">
                    <a href="#tab-notes" onclick="switchTabByName('notes');return false;" class="btn btn-sm btn-outline" style="width:100%;justify-content:center">
                        <i class="fa-solid fa-notes-medical"></i> Add Progress Note
                    </a>
                    <a href="#tab-vitals" onclick="switchTabByName('vitals');return false;" class="btn btn-sm btn-outline" style="width:100%;justify-content:center">
                        <i class="fa-solid fa-heart-pulse"></i> Record Vitals
                    </a>
                    <a href="#tab-billing" onclick="switchTabByName('billing');return false;" class="btn btn-sm btn-outline" style="width:100%;justify-content:center">
                        <i class="fa-solid fa-file-invoice-dollar"></i> Add Charge / Record Payment
                    </a>
                    <a href="#tab-mar" onclick="switchTabByName('mar');return false;" class="btn btn-sm btn-outline" style="width:100%;justify-content:center">
                        <i class="fa-solid fa-pills"></i> Prescribe / Medicine Chart
                    </a>
                    <a href="#tab-labs" onclick="switchTabByName('labs');return false;" class="btn btn-sm btn-outline" style="width:100%;justify-content:center">
                        <i class="fa-solid fa-flask"></i> Order Lab Tests
                    </a>
                    <a href="#tab-radiology" onclick="switchTabByName('radiology');return false;" class="btn btn-sm btn-outline" style="width:100%;justify-content:center">
                        <i class="fa-solid fa-radiation"></i> Order Radiology
                    </a>
                    <a href="#tab-ot" onclick="switchTabByName('ot');return false;" class="btn btn-sm btn-outline" style="width:100%;justify-content:center">
                        <i class="fa-solid fa-syringe"></i> Schedule OT
                    </a>
                </div>
            </div>
            @endif
            <div class="card mb-3">
                <div class="card-header"><i class="fa-solid fa-bed"></i> Ward &amp; Bed</div>
                <div class="card-body" style="display:flex;flex-direction:column;gap:12px">
                    <div>
                        <div class="form-label">Ward</div>
                        <div class="fw-600">{{ $admission->ward->name }}</div>
                        <div class="text-small text-muted">{{ $admission->ward->type_label }}</div>
                    </div>
                    <div>
                        <div class="form-label">Bed</div>
                        <div class="fw-600">Bed {{ $admission->bed->bed_number }}</div>
                        <div class="text-small text-muted">{{ ucfirst($admission->bed->type) }}</div>
                        @if($admission->status === 'admitted')
                        <span class="badge badge-red" style="margin-top:4px">Occupied</span>
                        @else
                        <span class="badge badge-green" style="margin-top:4px">Released</span>
                        @endif
                    </div>
                    @if($admission->status === 'admitted')
                    <button type="button" class="btn btn-sm btn-outline" style="width:100%;justify-content:center" onclick="openTransferModal()">
                        <i class="fa-solid fa-right-left"></i> Transfer Ward/Bed
                    </button>
                    @endif
                </div>
            </div>
            @if($admission->status === 'admitted')
            @include('admissions._discharge-checklist')
            @endif
            <div class="card">
                <div class="card-header"><i class="fa-solid fa-circle-info"></i> Record</div>
                <div class="card-body" style="font-size:12.5px;display:flex;flex-direction:column;gap:10px">
                    <div><div class="form-label">Created By</div><div>{{ $admission->createdBy?->name ?? '—' }}</div></div>
                    <div><div class="form-label">Created At</div><div>{{ $admission->created_at->format('d M Y, h:i A') }}</div></div>
                </div>
            </div>
        </div>

</div>
    {{-- Ward & bed history --}}
    @php $rent = new \App\Services\RoomRent(); $stays = \App\Services\RoomRent::stays($admission); @endphp
    <div class="card" style="margin-top:20px">
        <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
            <span><i class="fa-solid fa-route"></i> Ward &amp; Bed History <span class="badge badge-navy" style="font-size:10px">{{ $admission->bedTransfers->count() }} transfer{{ $admission->bedTransfers->count() === 1 ? '' : 's' }}</span></span>
            <span class="text-small text-muted">On a transfer day the higher room rent is charged</span>
        </div>
        <div class="table-wrap">
            <table class="sc-table">
                <thead><tr><th>Ward / Bed</th><th>From</th><th>To</th><th>Duration</th><th>Room rent / day</th><th>Moved in because</th><th>By</th></tr></thead>
                <tbody>
                @foreach($stays as $i => $st)
                    @php
                        $t = $i > 0 ? $admission->bedTransfers[$i - 1] : null;
                        $end = $st['to'] ?? now();
                        $mins = (int) $st['from']->diffInMinutes($end);
                        $dur = $mins >= 1440 ? intdiv($mins, 1440) . 'd ' . intdiv($mins % 1440, 60) . 'h' : intdiv($mins, 60) . 'h ' . ($mins % 60) . 'm';
                    @endphp
                    <tr @if(!$st['to'] && $admission->status === 'admitted') style="background:var(--sc-teal-glow)" @endif>
                        <td><span class="fw-700">{{ $st['ward']->name ?? '—' }}</span> · Bed {{ $st['bed']->bed_number ?? '—' }}
                            @if($st['ward'])<span class="badge badge-gray" style="font-size:10px">{{ $st['ward']->type_label }}</span>@endif
                            @if(!$st['to'] && $admission->status === 'admitted')<span class="badge badge-teal" style="font-size:10px">now</span>@endif</td>
                        <td class="text-small" style="white-space:nowrap">{{ $st['from']->format('d M Y, h:i A') }}</td>
                        <td class="text-small" style="white-space:nowrap">{{ $st['to'] ? $st['to']->format('d M Y, h:i A') : ($admission->status === 'admitted' ? '—' : '') }}</td>
                        <td>{{ $dur }}</td>
                        <td>{{ $rent->rate($st['ward']) > 0 ? '₹' . number_format($rent->rate($st['ward'])) : '—' }}</td>
                        <td class="text-small">{{ $t ? ($t->reason ?: '—') : 'Admission' }}</td>
                        <td class="text-small text-muted">{{ $t ? ($t->user->name ?? '—') : ($admission->createdBy->name ?? '') }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>{{-- /tab-overview --}}


{{-- ── TAB: PROGRESS NOTES ── --}}
<div class="tab-pane" id="tab-notes">
    <div class="ipd-two-col">

        {{-- Notes list --}}
        <div>
            @if($notes->isEmpty())
            <div class="card"><div class="card-body text-center text-muted" style="padding:40px">No progress notes yet.</div></div>
            @else
            <div style="display:flex;flex-direction:column;gap:12px">
            @foreach($notes as $note)
            <div class="card">
                <div class="card-header" style="padding:12px 16px">
                    <span class="badge badge-{{ $note->type_color }}"><i class="fa-solid {{ $note->type_icon }}"></i> {{ $note->type_label }}</span>
                    <span style="margin-left:8px;font-weight:400;font-size:12.5px;color:var(--sc-muted)">
                        {{ $note->noted_at->format('d M Y, h:i A') }} &mdash; {{ $note->writtenBy->name }}
                    </span>
                    <div class="card-actions">
                        @if($note->written_by === $u->id || $u->isAdmin())
                        <form method="POST" action="{{ route('admissions.notes.destroy', [$admission, $note]) }}"
                              onsubmit="return confirm('Delete this note?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline" style="color:var(--sc-red);border-color:var(--sc-red)">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </form>
                        @endif
                    </div>
                </div>
                <div class="card-body" style="white-space:pre-wrap;line-height:1.6">{{ $note->note }}</div>
            </div>
            @endforeach
            </div>
            @endif
        </div>

        {{-- Add note form --}}
        @if($admission->status === 'admitted' || $u->isAdmin())
        <div class="card" style="position:sticky;top:80px">
            <div class="card-header"><i class="fa-solid fa-plus"></i> Add Note</div>
            <div class="card-body">
                <form method="POST" action="{{ route('admissions.notes.store', $admission) }}">
                    @csrf
                    <div class="form-group">
                        <label class="form-label">Note Type <span class="req">*</span></label>
                        <select name="note_type" class="form-control" required>
                            @foreach(\App\Models\IpdNote::TYPES as $val => $meta)
                            <option value="{{ $val }}" {{ old('note_type','doctor_round') === $val ? 'selected' : '' }}>{{ $meta['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Date &amp; Time <span class="req">*</span></label>
                        <input type="datetime-local" name="noted_at" class="form-control"
                               value="{{ old('noted_at', now()->format('Y-m-d\TH:i')) }}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Note <span class="req">*</span></label>
                        <textarea name="note" class="form-control" rows="7" required
                                  placeholder="Enter clinical note…">{{ old('note') }}</textarea>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%">
                        <i class="fa-solid fa-floppy-disk"></i> Save Note
                    </button>
                </form>
            </div>
        </div>
        @endif
    </div>
</div>

{{-- ── TAB: VITALS ── --}}
<div class="tab-pane" id="tab-vitals">
    <div class="ipd-two-col">

        {{-- Vitals list --}}
        <div>
            {{-- Trend Chart --}}
            @if($vitals->count() > 1)
            <div style="margin-bottom:12px">
                <button type="button" class="btn btn-sm btn-outline" id="trendToggle" onclick="toggleTrend()">
                    <i class="fa-solid fa-chart-line"></i> Show Trend Chart
                </button>
            </div>
            <div class="card mb-3" id="trendCard" style="display:none">
                <div class="card-header"><i class="fa-solid fa-chart-line"></i> Vitals Trend</div>
                <div class="card-body">
                    <canvas id="vitalsChart" height="100"></canvas>
                </div>
            </div>
            @endif
            <div class="card">
                <div class="table-wrap">
                    <table class="sc-table">
                        <thead>
                            <tr>
                                <th>Date / Time</th>
                                <th>Temp</th>
                                <th>Pulse</th>
                                <th>BP</th>
                                <th>RR</th>
                                <th>SpO₂</th>
                                <th>Glucose</th>
                                <th>Weight</th>
                                <th>NEWS2</th>
                                <th>By</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($vitals as $v)
                            <tr>
                                <td style="white-space:nowrap">{{ $v->recorded_at->format('d M Y') }}<br><span class="text-small text-muted">{{ $v->recorded_at->format('h:i A') }}</span></td>
                                <td>{{ $v->temperature ? $v->temperature.'°F' : '—' }}</td>
                                <td>{{ $v->pulse ? $v->pulse.' bpm' : '—' }}</td>
                                <td style="white-space:nowrap">{{ $v->bp ?? '—' }}</td>
                                <td>{{ $v->respiration_rate ? $v->respiration_rate.'/min' : '—' }}</td>
                                <td>{{ $v->spo2 ? $v->spo2.'%' : '—' }}</td>
                                <td>{{ $v->blood_glucose ? $v->blood_glucose.' mg/dL' : '—' }}</td>
                                <td>{{ $v->weight ? $v->weight.' kg' : '—' }}</td>
                                <td>@if($v->news2 !== null) @php $nl = \App\Services\EarlyWarning::LEVELS[$v->news_level]; @endphp<span class="badge" style="background:{{ $nl['bg'] }};color:{{ $nl['color'] }}" title="{{ $nl['label'] }} risk{{ $v->news2_partial ? ' (partial)' : '' }}">{{ $v->news2 }}{{ $v->news2_partial ? '*' : '' }}</span>@else — @endif</td>
                                <td class="text-small text-muted">{{ $v->recordedBy->name }}</td>
                                <td>
                                    @if($v->recorded_by === $u->id || $u->isAdmin())
                                    <div style="display:flex;gap:4px">
                                        <button type="button" class="btn btn-sm btn-outline"
                                            onclick="openVitalsEdit({{ $v->id }}, '{{ $v->recorded_at->format('Y-m-d\TH:i') }}', '{{ $v->temperature }}', '{{ $v->pulse }}', '{{ $v->bp_systolic }}', '{{ $v->bp_diastolic }}', '{{ $v->respiration_rate }}', '{{ $v->spo2 }}', '{{ $v->blood_glucose }}', '{{ $v->weight }}', '{{ addslashes($v->urine_output ?? '') }}', '{{ addslashes($v->notes ?? '') }}')">
                                            <i class="fa-solid fa-pen"></i>
                                        </button>
                                        <form method="POST" action="{{ route('admissions.vitals.destroy', [$admission, $v]) }}"
                                              onsubmit="return confirm('Delete this vitals entry?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline" style="color:var(--sc-red);border-color:var(--sc-red)">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                    @endif
                                </td>
                            </tr>
                            @if($v->notes)
                            <tr>
                                <td colspan="11" style="padding-top:0;padding-bottom:8px;font-size:12px;color:var(--sc-muted)">
                                    <i class="fa-solid fa-comment"></i> {{ $v->notes }}
                                </td>
                            </tr>
                            @endif
                            @empty
                            <tr><td colspan="11" class="text-center text-muted" style="padding:40px">No vitals recorded yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Record vitals form --}}
        @if($admission->status === 'admitted' || $u->isAdmin())
        <div class="card" style="position:sticky;top:80px">
            <div class="card-header"><i class="fa-solid fa-heart-pulse"></i> Record Vitals</div>
            <div class="card-body">
                <form method="POST" action="{{ route('admissions.vitals.store', $admission) }}">
                    @csrf
                    <div class="form-group">
                        <label class="form-label">Date &amp; Time <span class="req">*</span></label>
                        <input type="datetime-local" name="recorded_at" class="form-control"
                               value="{{ old('recorded_at', now()->format('Y-m-d\TH:i')) }}" required>
                    </div>
                    <div class="form-row cols-2">
                        <div class="form-group">
                            <label class="form-label">Temperature (°F)</label>
                            <input type="number" name="temperature" class="form-control" step="0.1" min="90" max="115" value="{{ old('temperature', '98.6') }}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Pulse (bpm)</label>
                            <input type="number" name="pulse" class="form-control" min="20" max="300" value="{{ old('pulse', '72') }}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">BP Systolic</label>
                            <input type="number" name="bp_systolic" class="form-control" min="50" max="300" value="{{ old('bp_systolic', '120') }}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">BP Diastolic</label>
                            <input type="number" name="bp_diastolic" class="form-control" min="20" max="200" value="{{ old('bp_diastolic', '80') }}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Resp. Rate (/min)</label>
                            <input type="number" name="respiration_rate" class="form-control" min="5" max="60" value="{{ old('respiration_rate', '16') }}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">SpO₂ (%)</label>
                            <input type="number" name="spo2" class="form-control" min="50" max="100" value="{{ old('spo2', '99') }}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Blood Glucose (mg/dL)</label>
                            <input type="number" name="blood_glucose" class="form-control" step="0.1" value="{{ old('blood_glucose') }}" placeholder="e.g. 100">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Weight (kg)</label>
                            <input type="number" name="weight" class="form-control" step="0.1" value="{{ old('weight') }}" placeholder="e.g. 70.0">
                        </div>
                    </div>
                    <div class="form-row cols-2">
                        <div class="form-group">
                            <label class="form-label">Consciousness</label>
                            <select name="avpu" class="form-control">
                                @foreach(\App\Models\IpdVitals::AVPU as $k => $l)<option value="{{ $k }}" @selected(old('avpu', 'A') === $k)>{{ $k }} — {{ $l }}</option>@endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Oxygen</label>
                            <select name="o2_device" class="form-control">
                                @foreach(\App\Models\IpdVitals::O2_DEVICES as $k => $l)<option value="{{ $k }}" @selected(old('o2_device', $latestVitals->o2_device ?? 'air') === $k)>{{ $l }}</option>@endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Urine Output</label>
                        <input type="text" name="urine_output" class="form-control" value="{{ old('urine_output') }}" placeholder="e.g. 400 mL / 8h">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Notes</label>
                        <input type="text" name="notes" class="form-control" value="{{ old('notes') }}" placeholder="Any observation…">
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%">
                        <i class="fa-solid fa-floppy-disk"></i> Save Vitals
                    </button>
                </form>
            </div>
        </div>
        @endif
    </div>
</div>

{{-- Vitals edit modal --}}
<div id="vitalsEditModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center;overflow-y:auto">
    <div style="background:#fff;border-radius:10px;padding:28px;width:560px;max-width:96vw;margin:20px auto">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px">
            <div style="font-size:16px;font-weight:700;color:var(--sc-navy)"><i class="fa-solid fa-pen"></i> Edit Vitals</div>
            <button type="button" onclick="closeVitalsEdit()" style="background:none;border:none;font-size:20px;cursor:pointer;color:#999">&times;</button>
        </div>
        <form method="POST" id="vitalsEditForm">
            @csrf @method('PUT')
            <div class="form-row cols-2">
                <div class="form-group" style="grid-column:1/-1">
                    <label class="form-label">Date &amp; Time <span class="req">*</span></label>
                    <input type="datetime-local" name="recorded_at" id="ve_recorded_at" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Temperature (°F)</label>
                    <input type="number" name="temperature" id="ve_temperature" class="form-control" step="0.1" min="90" max="115">
                </div>
                <div class="form-group">
                    <label class="form-label">Pulse (bpm)</label>
                    <input type="number" name="pulse" id="ve_pulse" class="form-control" min="20" max="300">
                </div>
                <div class="form-group">
                    <label class="form-label">BP Systolic</label>
                    <input type="number" name="bp_systolic" id="ve_bp_systolic" class="form-control" min="50" max="300">
                </div>
                <div class="form-group">
                    <label class="form-label">BP Diastolic</label>
                    <input type="number" name="bp_diastolic" id="ve_bp_diastolic" class="form-control" min="20" max="200">
                </div>
                <div class="form-group">
                    <label class="form-label">Resp. Rate (/min)</label>
                    <input type="number" name="respiration_rate" id="ve_respiration_rate" class="form-control" min="5" max="60">
                </div>
                <div class="form-group">
                    <label class="form-label">SpO₂ (%)</label>
                    <input type="number" name="spo2" id="ve_spo2" class="form-control" min="50" max="100">
                </div>
                <div class="form-group">
                    <label class="form-label">Blood Glucose (mg/dL)</label>
                    <input type="number" name="blood_glucose" id="ve_blood_glucose" class="form-control" step="0.1">
                </div>
                <div class="form-group">
                    <label class="form-label">Weight (kg)</label>
                    <input type="number" name="weight" id="ve_weight" class="form-control" step="0.1">
                </div>
                <div class="form-group" style="grid-column:1/-1">
                    <label class="form-label">Urine Output</label>
                    <input type="text" name="urine_output" id="ve_urine_output" class="form-control">
                </div>
                <div class="form-group" style="grid-column:1/-1">
                    <label class="form-label">Notes</label>
                    <input type="text" name="notes" id="ve_notes" class="form-control">
                </div>
            </div>
            <div style="display:flex;gap:10px;margin-top:8px">
                <button type="submit" class="btn btn-primary" style="flex:1"><i class="fa-solid fa-floppy-disk"></i> Save Changes</button>
                <button type="button" onclick="closeVitalsEdit()" class="btn btn-outline">Cancel</button>
            </div>
        </form>
    </div>
</div>

{{-- ── TAB: Medicine Chart ── --}}
<div class="tab-pane" id="tab-mar">
    <div class="ipd-two-col">

        {{-- Active medication list + today's Medicine Chart --}}
        <div>
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
                <div class="fw-600" style="font-size:14px">Today's Administration — {{ today()->format('d M Y') }}</div>
                <a href="{{ route('admissions.mar', $admission) }}" class="btn btn-sm btn-outline" target="_blank">
                    <i class="fa-solid fa-print"></i> Print Medicine Chart
                </a>
            </div>

            @if($prescriptions->isEmpty())
            <div class="card"><div class="card-body text-center text-muted" style="padding:40px">No medications prescribed yet.</div></div>
            @else
            @foreach($prescriptions as $rx)
            @php
                $times = $rx->effective_times;
                $todayLogs = $rx->logs->keyBy('scheduled_time');
                $isActive  = $rx->is_active;
            @endphp
            <div class="card mb-3" style="{{ !$isActive ? 'opacity:.6' : '' }}">
                <div class="card-header" style="padding:10px 16px">
                    <div>
                        <span class="fw-700" style="font-size:14px">{{ $rx->drug_name }}</span>
                        @if($rx->generic_name)
                        <span style="font-size:11px;color:#64748b;margin-left:6px"><i class="fa-solid fa-flask" style="font-size:10px"></i> <em>{{ ucfirst($rx->generic_name) }}</em></span>
                        @endif
                        <span class="text-muted" style="font-size:12px;margin-left:8px">{{ $rx->dose }} · {{ $rx->route }} · {{ $rx->frequency_label }}</span>
                    </div>
                    <div class="card-actions">
                        @if(!$isActive)
                        <span class="badge badge-red">Stopped</span>
                        @else
                        <button type="button" class="btn btn-sm btn-outline" title="Edit"
                            onclick="openIpdEditModal({{ $rx->id }}, '{{ addslashes($rx->drug_name) }}', '{{ addslashes($rx->dose) }}', '{{ addslashes($rx->route) }}', '{{ addslashes($rx->frequency) }}', '{{ $rx->end_date?->toDateString() ?? '' }}', '{{ addslashes($rx->instructions ?? '') }}')">
                            <i class="fa-solid fa-pen"></i> Edit
                        </button>
                        <form method="POST" action="{{ route('admissions.prescriptions.stop', [$admission, $rx]) }}" onsubmit="return confirm('Stop this medication?')">
                            @csrf @method('PATCH')
                            <button type="submit" class="btn btn-sm btn-outline" style="color:var(--sc-red);border-color:var(--sc-red)"><i class="fa-solid fa-stop"></i> Stop</button>
                        </form>
                        @endif
                    </div>
                </div>
                <div class="card-body" style="padding:12px 16px">
                    @if($rx->instructions)
                    <div style="font-size:11.5px;color:var(--sc-muted);margin-bottom:10px"><i class="fa-solid fa-circle-info"></i> {{ $rx->instructions }}</div>
                    @endif

                    @if(empty($times))
                    <div class="text-muted text-small">{{ $rx->frequency === 'STAT' ? 'Given as single stat dose.' : 'SOS — given as needed.' }}</div>
                    @else
                    <div style="display:flex;gap:8px;flex-wrap:wrap">
                        @foreach($times as $t)
                        @php
                            $log = $todayLogs->get($t);
                            $statusColors = ['given'=>'green','missed'=>'red','held'=>'amber','refused'=>'navy'];
                            $statusIcons  = ['given'=>'fa-circle-check','missed'=>'fa-circle-xmark','held'=>'fa-circle-pause','refused'=>'fa-circle-minus'];
                            $sc = $log ? ($statusColors[$log->status] ?? 'gray') : 'gray';
                            $si = $log ? ($statusIcons[$log->status]  ?? 'fa-circle') : 'fa-circle';
                        @endphp
                        <div class="mar-dose-cell" style="border:1.5px solid var(--sc-border);border-radius:8px;padding:8px 12px;min-width:80px;text-align:center;cursor:{{ $isActive ? 'pointer' : 'default' }};background:{{ $log ? 'var(--sc-bg)' : '#fff' }}"
                             @if($isActive) onclick="openDoseModal({{ $rx->id }}, '{{ $t }}', '{{ today()->toDateString() }}', '{{ $log?->status ?? '' }}')" @endif>
                            <div style="font-size:11px;font-weight:700;color:var(--sc-muted)">{{ \Carbon\Carbon::createFromFormat('H:i',$t)->format('h:i A') }}</div>
                            <div style="margin-top:4px">
                                <i class="fa-solid {{ $si }} badge-{{ $sc }}" style="font-size:20px;color:var(--sc-{{ $sc === 'navy' ? 'navy' : ($sc === 'green' ? 'green' : ($sc === 'red' ? 'red' : ($sc === 'amber' ? 'amber' : 'muted'))) }})"></i>
                            </div>
                            @if($log)
                            <div style="font-size:10px;margin-top:3px;color:var(--sc-muted)">{{ ucfirst($log->status) }}</div>
                            @endif
                        </div>
                        @endforeach
                    </div>
                    @endif

                    <div style="margin-top:8px;font-size:11px;color:var(--sc-muted)">
                        Started {{ $rx->start_date->format('d M Y') }}
                        @if($rx->end_date) &mdash; Ends {{ $rx->end_date->format('d M Y') }} @endif
                        &nbsp;·&nbsp; Prescribed by {{ $rx->prescribedBy->name }}
                    </div>
                </div>
            </div>
            @endforeach
            @endif
        </div>

        {{-- Add prescription form --}}
        @if($admission->status === 'admitted' || $u->isAdmin())
        <div class="card" style="position:sticky;top:80px">
            <div class="card-header"><i class="fa-solid fa-prescription-bottle-medical"></i> Add Medication</div>
            <div class="card-body">
                <form method="POST" action="{{ route('admissions.prescriptions.store', $admission) }}" id="rxForm">
                    @csrf
                    <div class="form-group">
                        <label class="form-label">Drug Name <span class="req">*</span></label>
                        <div style="position:relative" id="rxDrugWrap">
                            <input type="text" id="rxDrugInput" class="form-control" autocomplete="off" required
                                   placeholder="Search stock or type new name…"
                                   oninput="rxDrugSearch(this.value)" onblur="setTimeout(rxDrugHide,200)">
                            <input type="hidden" name="drug_name" id="rxDrugName">
                            <input type="hidden" name="medicine_id" id="rxMedicineId">
                            <input type="hidden" name="generic_name" id="rxGenericName">
                            <div id="rxDrugDrop" style="display:none;position:absolute;z-index:999;top:100%;left:0;right:0;background:#fff;border:1px solid #d0d5dd;border-top:none;border-radius:0 0 6px 6px;max-height:220px;overflow-y:auto;box-shadow:0 4px 12px rgba(0,0,0,.12)"></div>
                        </div>
                        <div class="form-text" id="rxDrugHint" style="display:none;color:var(--sc-amber)"><i class="fa-solid fa-triangle-exclamation"></i> Not in stock — will be recorded as external medicine.</div>
                        <div id="rxGenericDisplay" style="display:none;font-size:12px;color:#64748b;padding:3px 0"></div>
                        <div id="rxAllergyWarn" style="display:none;margin-top:6px;padding:8px 12px;background:#fef2f2;border:1.5px solid #fca5a5;border-radius:6px;color:#991b1b;font-size:13px;font-weight:600">
                            <i class="fa-solid fa-circle-exclamation"></i> <strong>ALLERGY ALERT:</strong> <span id="rxAllergyMsg"></span>
                        </div>
                        <div id="rxDuplicateWarn" style="display:none;margin-top:6px;padding:8px 12px;background:#fffbeb;border:1.5px solid #fcd34d;border-radius:6px;color:#92400e;font-size:13px;font-weight:600">
                            <i class="fa-solid fa-triangle-exclamation"></i> <strong>DUPLICATE:</strong> This drug is already on the active medicines list.
                        </div>
                        <div id="rxInteractionBox" style="display:none;margin-top:8px;background:#fef2f2;border:1.5px solid #fca5a5;border-radius:8px;padding:12px 14px;font-size:12px"></div>
                    </div>
                    <div class="form-row cols-2">
                        <div class="form-group">
                            <label class="form-label">Dose <span class="req">*</span></label>
                            <input type="text" name="dose" class="form-control" required placeholder="e.g. 500mg">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Route <span class="req">*</span></label>
                            <select name="route" class="form-control" required>
                                @foreach(\App\Models\IpdPrescription::ROUTES as $r)
                                <option>{{ $r }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Frequency <span class="req">*</span></label>
                        <select name="frequency" class="form-control" required id="rxFreq">
                            @foreach(\App\Models\IpdPrescription::FREQUENCIES as $val => $meta)
                            <option value="{{ $val }}" data-times="{{ implode(',', $meta['times']) }}">{{ $meta['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group" id="timesGroup">
                        <label class="form-label">Schedule Times</label>
                        <div id="timesPreview" style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:8px"></div>
                        <div class="form-text">Auto-set by frequency. Edit if needed.</div>
                        <div id="timesInputs"></div>
                    </div>
                    <div class="form-row cols-2">
                        <div class="form-group">
                            <label class="form-label">Start Date <span class="req">*</span></label>
                            <input type="date" name="start_date" class="form-control" value="{{ today()->toDateString() }}" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">End Date</label>
                            <input type="date" name="end_date" class="form-control">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Instructions</label>
                        <input type="text" name="instructions" class="form-control" placeholder="e.g. After meals, with water">
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%">
                        <i class="fa-solid fa-plus"></i> Add to Medicine Chart
                    </button>
                </form>
            </div>
        </div>
        @endif
    </div>
</div>

{{-- Dose logging modal --}}
<div class="modal-overlay" id="doseModal">
    <div class="modal-box" style="max-width:380px">
        <div class="modal-header">
            <h3><i class="fa-solid fa-pills"></i> Record Dose</h3>
            <button class="modal-close" onclick="closeDoseModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div id="doseModalInfo" style="margin-bottom:16px;font-size:13px;color:var(--sc-muted)"></div>
            <div class="form-group">
                <label class="form-label">Status</label>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px" id="statusBtns">
                    <button type="button" class="btn btn-outline dose-status-btn" data-status="given"   onclick="setStatus(this)"><i class="fa-solid fa-circle-check"></i> Given</button>
                    <button type="button" class="btn btn-outline dose-status-btn" data-status="missed"  onclick="setStatus(this)"><i class="fa-solid fa-circle-xmark"></i> Missed</button>
                    <button type="button" class="btn btn-outline dose-status-btn" data-status="held"    onclick="setStatus(this)"><i class="fa-solid fa-circle-pause"></i> Held</button>
                    <button type="button" class="btn btn-outline dose-status-btn" data-status="refused" onclick="setStatus(this)"><i class="fa-solid fa-circle-minus"></i> Refused</button>
                </div>
            </div>
            <div class="form-group" id="actualTimeGroup">
                <label class="form-label">Actual Time Given</label>
                <input type="time" id="actualTimeInput" class="form-control" value="{{ now()->format('H:i') }}">
            </div>
            <div class="form-group">
                <label class="form-label">Remarks</label>
                <input type="text" id="remarksInput" class="form-control" placeholder="Optional note">
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeDoseModal()">Cancel</button>
            <button class="btn btn-primary" onclick="saveDose()"><i class="fa-solid fa-floppy-disk"></i> Save</button>
        </div>
    </div>
</div>

<script>
// ── Frequency → times auto-fill
document.getElementById('rxFreq')?.addEventListener('change', function() {
    const times = this.options[this.selectedIndex].dataset.times;
    renderTimes(times ? times.split(',').filter(Boolean) : []);
});
function renderTimes(times) {
    const preview = document.getElementById('timesPreview');
    const inputs  = document.getElementById('timesInputs');
    if (!preview || !inputs) return;
    preview.innerHTML = times.map(t => `<span class="badge badge-teal">${t}</span>`).join('');
    inputs.innerHTML  = times.map(t => `<input type="hidden" name="schedule_times[]" value="${t}">`).join('');
}
// Init on load
document.addEventListener('DOMContentLoaded', () => {
    const freq = document.getElementById('rxFreq');
    if (freq) {
        const times = freq.options[freq.selectedIndex]?.dataset.times;
        renderTimes(times ? times.split(',').filter(Boolean) : []);
    }
});

// ── Drug autocomplete
@php
$rxMedData = $stockMedicines->map(function($m) {
    return ['id'=>$m->id,'name'=>$m->name,'form'=>$m->dosage_form??'','strength'=>$m->strength??'','generic'=>$m->resolved_generic??''];
})->values();
@endphp
const RX_MEDICINES = @json($rxMedData);

function rxDrugSearch(val) {
    const drop = document.getElementById('rxDrugDrop');
    const hidden = document.getElementById('rxDrugName');
    const idInput = document.getElementById('rxMedicineId');
    const hint = document.getElementById('rxDrugHint');

    // clear selection when user types
    hidden.value = val;
    idInput.value = '';
    hint.style.display = 'none';

    const q = val.trim().toLowerCase();
    if (!q) { drop.style.display = 'none'; return; }

    const matches = RX_MEDICINES.filter(m =>
        m.name.toLowerCase().includes(q) ||
        (m.generic && m.generic.toLowerCase().includes(q))
    ).slice(0, 30);

    if (!matches.length) {
        drop.innerHTML = '<div style="padding:10px 12px;font-size:12px;color:#888">Not in stock — type full name to add manually</div>';
        hint.style.display = '';
    } else {
        drop.innerHTML = matches.map(m => {
            const label = [m.form, m.name, m.strength].filter(Boolean).join(' ');
            const sub   = m.generic ? `<div style="font-size:11px;color:#888">${m.generic}</div>` : '';
            return `<div class="rx-drop-item" style="padding:8px 12px;cursor:pointer;border-bottom:1px solid #f0f0f0"
                        onmousedown="rxDrugSelect(${m.id},'${label.replace(/'/g,"\\'")}','${(m.strength||'').replace(/'/g,"\\'")}')">
                        <div style="font-size:13px;font-weight:600">${label}</div>${sub}
                    </div>`;
        }).join('');
        hint.style.display = 'none';
    }
    drop.style.display = '';
}

function rxDrugSelect(id, label, strength) {
    document.getElementById('rxDrugInput').value  = label;
    document.getElementById('rxDrugName').value   = label;
    document.getElementById('rxMedicineId').value = id;
    document.getElementById('rxDrugDrop').style.display = 'none';
    document.getElementById('rxDrugHint').style.display = 'none';
    const doseInput = document.querySelector('[name="dose"]');
    if (doseInput && !doseInput.value && strength) doseInput.value = strength;

    // Show + store generic name
    const med = RX_MEDICINES.find(m => String(m.id) === String(id));
    const generic = med?.generic || '';
    document.getElementById('rxGenericName').value = generic;
    const gd = document.getElementById('rxGenericDisplay');
    if (generic) {
        gd.innerHTML = `<i class="fa-solid fa-flask" style="font-size:10px"></i> <em>Generic: <strong>${escIpd(generic)}</strong></em>`;
        gd.style.display = '';
    } else {
        gd.style.display = 'none';
    }

    checkDrugAllergy(label);
    checkIpdDrugInteractions(label, id);
}

function checkIpdDrugInteractions(newDrug, medId) {
    const ixBox = document.getElementById('rxInteractionBox');
    if (!ixBox) return;

    // Collect all currently active drug names + the new one
    const allNames = [...ACTIVE_DRUGS, newDrug.toLowerCase()];
    const med = RX_MEDICINES.find(m => String(m.id) === String(medId));
    if (med?.generic) allNames.push(med.generic.toLowerCase());

    if (allNames.length < 2) { ixBox.innerHTML = ''; ixBox.style.display = 'none'; return; }

    $.post('{{ route("pharmacy.interactions.check") }}', {
        names: [...new Set(allNames)],
        _token: document.querySelector('meta[name=csrf-token]').content
    }, function(data) {
        if (!data.interactions.length) { ixBox.innerHTML = ''; ixBox.style.display = 'none'; return; }

        let html = '<div style="font-weight:700;font-size:12px;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;color:#7f1d1d"><i class="fa-solid fa-pills"></i> Drug–Drug Interaction Alerts</div>';
        data.interactions.forEach(i => {
            html += `<div style="background:${i.bg};border:1px solid ${i.border};border-radius:6px;padding:10px 12px;margin-bottom:6px">
                <div style="display:flex;gap:6px;align-items:center;margin-bottom:4px">
                    <span style="background:${i.bg};border:1.5px solid ${i.border};color:${i.color};font-weight:700;font-size:10px;padding:1px 6px;border-radius:3px;text-transform:uppercase">${escIpd(i.label)}</span>
                    <span style="font-weight:600;font-size:13px;color:${i.color}">${escIpd(i.matched1)} ↔ ${escIpd(i.matched2)}</span>
                </div>
                <div style="font-size:12px;color:#374151">${escIpd(i.description)}</div>
                ${i.management ? `<div style="font-size:11px;color:#6b7280;margin-top:4px"><strong>Action:</strong> ${escIpd(i.management)}</div>` : ''}
            </div>`;
        });
        ixBox.innerHTML = html;
        ixBox.style.display = 'block';
    });
}

function escIpd(s) { return String(s||'').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c])); }

function rxDrugHide() {
    document.getElementById('rxDrugDrop').style.display = 'none';
}

// Allergy + duplicate drug alerts
const PATIENT_ALLERGIES = @json($patientAllergies);
const ACTIVE_DRUGS = @json($prescriptions->where('is_active',true)->pluck('drug_name')->map(fn($d)=>strtolower($d))->values());

function checkDrugAllergy(drugName) {
    const warn = document.getElementById('rxAllergyWarn');
    const msg  = document.getElementById('rxAllergyMsg');
    const dupWarn = document.getElementById('rxDuplicateWarn');
    if (!drugName) { warn.style.display = 'none'; if(dupWarn) dupWarn.style.display='none'; return; }
    const dn = drugName.toLowerCase();

    // Allergy check
    const allergyHits = PATIENT_ALLERGIES.filter(a => a && (dn.includes(a.toLowerCase()) || a.toLowerCase().includes(dn)));
    if (allergyHits.length) {
        msg.textContent = 'Patient is allergic to: ' + allergyHits.join(', ');
        warn.style.display = '';
    } else {
        warn.style.display = 'none';
    }

    // Duplicate active drug check
    if (dupWarn) {
        const isDup = ACTIVE_DRUGS.some(d => d && (dn.includes(d) || d.includes(dn)));
        dupWarn.style.display = isDup ? '' : 'none';
    }
}

// on form submit, ensure hidden drug_name has value from visible input if user typed freely
document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('rxForm')?.addEventListener('submit', function(e) {
        const visible = document.getElementById('rxDrugInput').value.trim();
        const hidden  = document.getElementById('rxDrugName');
        if (!hidden.value && visible) hidden.value = visible;
        checkDrugAllergy(hidden.value || visible);
        const warn = document.getElementById('rxAllergyWarn');
        if (warn.style.display !== 'none') {
            if (!confirm('ALLERGY ALERT!\n' + document.getElementById('rxAllergyMsg').textContent + '\n\nPrescribe anyway?')) {
                e.preventDefault();
            }
        }

        // Block contraindicated combinations on IPD submit
        const ixBox = document.getElementById('rxInteractionBox');
        if (ixBox && ixBox.style.display !== 'none' && ixBox.innerHTML.includes('Contraindicated')) {
            if (!confirm('🚫 CONTRAINDICATED drug interaction detected (see warning above).\n\nPrescribe anyway?')) {
                e.preventDefault();
            }
        }
    });
    document.getElementById('rxDrugInput')?.addEventListener('input', function() {
        checkDrugAllergy(this.value);
    });
});

// ── Dose modal
let currentPrescriptionId = null, currentScheduledTime = null, currentLogDate = null, selectedStatus = null;
function openDoseModal(rxId, time, date, currentStatus) {
    currentPrescriptionId = rxId;
    currentScheduledTime  = time;
    currentLogDate        = date;
    selectedStatus        = currentStatus || null;

    document.getElementById('doseModalInfo').textContent = `Scheduled: ${time} on ${date}`;
    document.querySelectorAll('.dose-status-btn').forEach(b => {
        b.classList.toggle('btn-primary', b.dataset.status === currentStatus);
        b.classList.toggle('btn-outline',  b.dataset.status !== currentStatus);
    });
    if (currentStatus) selectedStatus = currentStatus;
    document.getElementById('doseModal').classList.add('open');
}
function closeDoseModal() { document.getElementById('doseModal').classList.remove('open'); }
function setStatus(btn) {
    selectedStatus = btn.dataset.status;
    document.querySelectorAll('.dose-status-btn').forEach(b => {
        b.classList.toggle('btn-primary', b === btn);
        b.classList.toggle('btn-outline',  b !== btn);
    });
}
function saveDose() {
    if (!selectedStatus) { alert('Please select a status.'); return; }
    const body = new URLSearchParams({
        _token: document.querySelector('meta[name=csrf-token]').content,
        log_date: currentLogDate,
        scheduled_time: currentScheduledTime,
        status: selectedStatus,
        actual_time: document.getElementById('actualTimeInput').value,
        remarks: document.getElementById('remarksInput').value,
    });
    fetch(`{{ url('/admissions') }}/{{ $admission->id }}/prescriptions/${currentPrescriptionId}/log`, {
        method: 'POST', body, headers: {'Content-Type':'application/x-www-form-urlencoded'}
    }).then(r => r.json()).then(() => location.reload());
}

// ── IPD EDIT MEDICATION MODAL
let _ipdEditRxId = null;
function openIpdEditModal(rxId, drugName, dose, route, freq, endDate, instructions) {
    _ipdEditRxId = rxId;
    document.getElementById('ipdEditDrugName').textContent = drugName;
    document.getElementById('ipdEditDose').value = dose;
    document.getElementById('ipdEditRoute').value = route;
    document.getElementById('ipdEditFreq').value = freq;
    document.getElementById('ipdEditEndDate').value = endDate;
    document.getElementById('ipdEditInstr').value = instructions;
    document.getElementById('ipdEditModal').classList.add('open');
}
function closeIpdEditModal() {
    document.getElementById('ipdEditModal').classList.remove('open');
    _ipdEditRxId = null;
}
function saveIpdEdit() {
    if (!_ipdEditRxId) return;
    const freq = document.getElementById('ipdEditFreq').value;
    const times = @json(\App\Models\IpdPrescription::FREQUENCIES).hasOwnProperty(freq)
        ? @json(\App\Models\IpdPrescription::FREQUENCIES)[freq].times
        : [];
    const body = new URLSearchParams({
        _token: document.querySelector('meta[name=csrf-token]').content,
        _method: 'PUT',
        dose: document.getElementById('ipdEditDose').value,
        route: document.getElementById('ipdEditRoute').value,
        frequency: freq,
        end_date: document.getElementById('ipdEditEndDate').value,
        instructions: document.getElementById('ipdEditInstr').value,
    });
    times.forEach(t => body.append('schedule_times[]', t));
    fetch(`{{ url('/admissions') }}/{{ $admission->id }}/prescriptions/${_ipdEditRxId}`, {
        method: 'POST', body, headers: {'Content-Type':'application/x-www-form-urlencoded'}
    }).then(r => r.json()).then(d => {
        if (d.success) { closeIpdEditModal(); location.reload(); }
        else alert('Update failed.');
    });
}
</script>

{{-- IPD Edit Medication Modal --}}
<div class="modal-overlay" id="ipdEditModal">
    <div class="modal-box" style="max-width:480px">
        <div class="modal-header">
            <h3><i class="fa-solid fa-pen"></i> Edit Medication</h3>
            <button class="modal-close" onclick="closeIpdEditModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div style="font-size:13px;font-weight:600;color:var(--sc-navy);margin-bottom:14px" id="ipdEditDrugName"></div>
            <div class="form-row cols-2">
                <div class="form-group">
                    <label class="form-label">Dose</label>
                    <input type="text" id="ipdEditDose" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">Route</label>
                    <select id="ipdEditRoute" class="form-control">
                        @foreach(\App\Models\IpdPrescription::ROUTES as $r)
                        <option>{{ $r }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Frequency</label>
                <select id="ipdEditFreq" class="form-control">
                    @foreach(\App\Models\IpdPrescription::FREQUENCIES as $val => $meta)
                    <option value="{{ $val }}">{{ $meta['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-row cols-2">
                <div class="form-group">
                    <label class="form-label">End Date</label>
                    <input type="date" id="ipdEditEndDate" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">Instructions</label>
                    <input type="text" id="ipdEditInstr" class="form-control" placeholder="e.g. After meals">
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeIpdEditModal()">Cancel</button>
            <button class="btn btn-primary" onclick="saveIpdEdit()"><i class="fa-solid fa-floppy-disk"></i> Save Changes</button>
        </div>
    </div>
</div>

{{-- ── TAB: NURSING SHIFT NOTES ── --}}
<div class="tab-pane" id="tab-nursing">
@php $shiftDefs = \App\Models\NursingShift::SHIFTS; $condDefs = \App\Models\NursingShift::CONDITIONS; @endphp

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
    <div style="font-size:13px;color:var(--sc-muted)">Shift notes for <strong>{{ today()->format('d M Y') }}</strong></div>
    <a href="{{ route('admissions.nursing-handover', $admission) }}" class="btn btn-sm btn-outline" target="_blank"><i class="fa-solid fa-print"></i> Print Handover Sheet</a>
</div>

@foreach(['morning','evening','night'] as $shiftKey)
@php $shift = $nursingToday[$shiftKey] ?? null; $sd = $shiftDefs[$shiftKey]; @endphp
<div class="card mb-3">
    <div class="card-header" style="background:var(--sc-bg)">
        <div style="display:flex;align-items:center;gap:10px">
            <i class="fa-solid {{ $sd['icon'] }}" style="color:{{ $sd['color'] }}"></i>
            <strong>{{ $sd['label'] }} Shift</strong>
            <span style="font-size:11px;color:var(--sc-muted)">{{ $sd['time'] }}</span>
            @if($shift)
                <span class="badge {{ $shift->condition_badge }}" style="margin-left:4px">{{ $shift->condition_label }}</span>
                <span style="font-size:11px;color:var(--sc-muted);margin-left:auto">by {{ $shift->writtenBy->name ?? '—' }}</span>
            @else
                <span class="badge badge-gray" style="margin-left:4px">Not recorded</span>
            @endif
        </div>
        <div class="card-actions">
            <button class="btn btn-sm btn-outline" onclick="toggleShiftForm('{{ $shiftKey }}')">
                <i class="fa-solid fa-pen"></i> {{ $shift ? 'Edit' : 'Add Note' }}
            </button>
            @if($shift)
            <form method="POST" action="{{ route('admissions.nursing.destroy', [$admission, $shift]) }}" onsubmit="return confirm('Delete this shift note?')" style="display:inline">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-sm btn-outline" style="color:var(--sc-red);border-color:var(--sc-red)"><i class="fa-solid fa-trash"></i></button>
            </form>
            @endif
        </div>
    </div>

    {{-- Saved note preview --}}
    @if($shift)
    <div class="card-body" style="padding:12px 16px">
        <div class="grid" style="grid-template-columns:1fr 1fr;gap:12px;font-size:13px">
            @if($shift->assessment)<div><div style="font-size:10px;text-transform:uppercase;font-weight:700;color:var(--sc-muted);margin-bottom:3px">Assessment</div><div>{{ $shift->assessment }}</div></div>@endif
            @if($shift->care_given)<div><div style="font-size:10px;text-transform:uppercase;font-weight:700;color:var(--sc-muted);margin-bottom:3px">Care Given</div><div>{{ $shift->care_given }}</div></div>@endif
            @if($shift->medications_given)<div><div style="font-size:10px;text-transform:uppercase;font-weight:700;color:var(--sc-muted);margin-bottom:3px">Medications</div><div>{{ $shift->medications_given }}</div></div>@endif
            @if($shift->iv_lines)<div><div style="font-size:10px;text-transform:uppercase;font-weight:700;color:var(--sc-muted);margin-bottom:3px">IV Lines</div><div>{{ $shift->iv_lines }}</div></div>@endif
            @if($shift->pending_tasks)<div style="grid-column:span 2"><div style="font-size:10px;text-transform:uppercase;font-weight:700;color:var(--sc-amber);margin-bottom:3px">⚠ Pending / Handover</div><div>{{ $shift->pending_tasks }}</div></div>@endif
            @if($shift->special_instructions)<div style="grid-column:span 2"><div style="font-size:10px;text-transform:uppercase;font-weight:700;color:var(--sc-muted);margin-bottom:3px">Special Instructions</div><div>{{ $shift->special_instructions }}</div></div>@endif
        </div>
    </div>
    @endif

    {{-- Form (hidden by default) --}}
    <div id="shiftForm_{{ $shiftKey }}" style="{{ $shift ? 'display:none' : '' }}">
        <div class="card-body" style="border-top:1px solid var(--sc-border);padding-top:16px">
        <form method="POST" action="{{ route('admissions.nursing.store', $admission) }}">
            @csrf
            <input type="hidden" name="shift" value="{{ $shiftKey }}">
            <input type="hidden" name="shift_date" value="{{ today()->toDateString() }}">
            <div class="grid" style="grid-template-columns:1fr 1fr;gap:12px">
                <div class="form-group" style="margin:0">
                    <label class="form-label">Patient Condition</label>
                    <select name="patient_condition" class="form-control">
                        <option value="">— Select —</option>
                        @foreach($condDefs as $ck => $cv)
                        <option value="{{ $ck }}" {{ ($shift?->patient_condition === $ck) ? 'selected' : '' }}>{{ $cv['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group" style="margin:0">
                    <label class="form-label">IV Lines / Sites</label>
                    <input type="text" name="iv_lines" class="form-control" value="{{ old('iv_lines', $shift?->iv_lines) }}" placeholder="e.g. Right forearm 18G, patent">
                </div>
                <div class="form-group" style="margin:0">
                    <label class="form-label">Assessment</label>
                    <textarea name="assessment" class="form-control" rows="3" placeholder="Conscious, oriented, breathing easy…">{{ old('assessment', $shift?->assessment) }}</textarea>
                </div>
                <div class="form-group" style="margin:0">
                    <label class="form-label">Care Given</label>
                    <textarea name="care_given" class="form-control" rows="3" placeholder="Dressing changed, patient turned, nebulization given…">{{ old('care_given', $shift?->care_given) }}</textarea>
                </div>
                <div class="form-group" style="margin:0">
                    <label class="form-label">Medications Given</label>
                    <textarea name="medications_given" class="form-control" rows="2" placeholder="Tab X 500mg given at 08:00, Inj Y IV at 10:00…">{{ old('medications_given', $shift?->medications_given) }}</textarea>
                </div>
                <div class="form-group" style="margin:0">
                    <label class="form-label">Special Instructions</label>
                    <textarea name="special_instructions" class="form-control" rows="2" placeholder="Nil by mouth, strict bed rest…">{{ old('special_instructions', $shift?->special_instructions) }}</textarea>
                </div>
                <div class="form-group" style="margin:0;grid-column:span 2">
                    <label class="form-label" style="color:var(--sc-amber)"><i class="fa-solid fa-triangle-exclamation"></i> Pending Tasks / Handover Points</label>
                    <textarea name="pending_tasks" class="form-control" rows="2" placeholder="Lab results pending, wound dressing due, doctor review at 16:00…">{{ old('pending_tasks', $shift?->pending_tasks) }}</textarea>
                </div>
            </div>
            <div style="display:flex;gap:10px;margin-top:12px">
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save Shift Note</button>
                <button type="button" class="btn btn-outline" onclick="toggleShiftForm('{{ $shiftKey }}')">Cancel</button>
            </div>
        </form>
        </div>
    </div>
</div>
@endforeach

<script>
function toggleShiftForm(shift) {
    const f = document.getElementById('shiftForm_' + shift);
    f.style.display = f.style.display === 'none' ? '' : 'none';
}
</script>
</div>

{{-- ── TAB: I/O CHART ── --}}
<div class="tab-pane" id="tab-io">
@php
    $inputCats  = \App\Models\IoEntry::INPUT_CATEGORIES;
    $outputCats = \App\Models\IoEntry::OUTPUT_CATEGORIES;
@endphp

<div class="grid" style="grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px">
    {{-- Balance summary --}}
    <div class="card">
        <div class="card-header"><i class="fa-solid fa-scale-balanced"></i> Today's Fluid Balance</div>
        <div class="card-body" style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;text-align:center">
            <div>
                <div style="font-size:11px;text-transform:uppercase;color:var(--sc-muted);font-weight:700;letter-spacing:.8px">Total Input</div>
                <div style="font-size:1.6rem;font-weight:800;color:var(--sc-teal)">{{ number_format($ioTotalIn) }} <span style="font-size:.9rem">mL</span></div>
            </div>
            <div>
                <div style="font-size:11px;text-transform:uppercase;color:var(--sc-muted);font-weight:700;letter-spacing:.8px">Total Output</div>
                <div style="font-size:1.6rem;font-weight:800;color:var(--sc-amber)">{{ number_format($ioTotalOut) }} <span style="font-size:.9rem">mL</span></div>
            </div>
            <div>
                <div style="font-size:11px;text-transform:uppercase;color:var(--sc-muted);font-weight:700;letter-spacing:.8px">Net Balance</div>
                @php $ioBalance = $ioTotalIn - $ioTotalOut; @endphp
                <div style="font-size:1.6rem;font-weight:800;color:{{ $ioBalance >= 0 ? 'var(--sc-green)' : 'var(--sc-red)' }}">
                    {{ $ioBalance >= 0 ? '+' : '' }}{{ number_format($ioBalance) }} <span style="font-size:.9rem">mL</span>
                </div>
            </div>
        </div>
        <div style="padding:0 16px 12px;display:flex;gap:10px">
            <a href="{{ route('admissions.io-chart', $admission) }}" class="btn btn-sm btn-outline" target="_blank"><i class="fa-solid fa-print"></i> Print I/O Chart</a>
            <span style="font-size:12px;color:var(--sc-muted);align-self:center">Showing today · <a href="{{ route('admissions.io-chart', $admission) }}" target="_blank" style="color:var(--sc-teal)">View all dates</a></span>
        </div>
    </div>

    {{-- Shift breakdown --}}
    <div class="card" style="grid-column:1/-1">
        <div class="card-header"><i class="fa-solid fa-clock-rotate-left"></i> Shift-wise Fluid Balance</div>
        <div class="card-body">
        @php
            $shifts = [
                'Morning'  => ['label'=>'Morning (06:00–13:59)',  'entries'=>$ioToday->filter(fn($e)=>$e->recorded_at->hour>=6  && $e->recorded_at->hour<=13)],
                'Evening'  => ['label'=>'Evening (14:00–21:59)',  'entries'=>$ioToday->filter(fn($e)=>$e->recorded_at->hour>=14 && $e->recorded_at->hour<=21)],
                'Night'    => ['label'=>'Night (22:00–05:59)',    'entries'=>$ioToday->filter(fn($e)=>$e->recorded_at->hour>=22 || $e->recorded_at->hour<=5)],
            ];
        @endphp
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px">
            @foreach($shifts as $shiftName => $s)
            @php
                $sIn  = $s['entries']->where('io_type','input')->sum('amount_ml');
                $sOut = $s['entries']->where('io_type','output')->sum('amount_ml');
                $sNet = $sIn - $sOut;
            @endphp
            <div style="border:1px solid var(--sc-border);border-radius:8px;padding:14px;text-align:center">
                <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:var(--sc-muted);margin-bottom:10px">{{ $s['label'] }}</div>
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px">
                    <div>
                        <div style="font-size:10px;color:var(--sc-muted)">Input</div>
                        <div style="font-weight:700;color:var(--sc-teal)">{{ number_format($sIn) }}<span style="font-size:10px"> mL</span></div>
                    </div>
                    <div>
                        <div style="font-size:10px;color:var(--sc-muted)">Output</div>
                        <div style="font-weight:700;color:var(--sc-amber)">{{ number_format($sOut) }}<span style="font-size:10px"> mL</span></div>
                    </div>
                    <div>
                        <div style="font-size:10px;color:var(--sc-muted)">Net</div>
                        <div style="font-weight:700;color:{{ $sNet>=0?'var(--sc-green)':'var(--sc-red)' }}">{{ $sNet>=0?'+':'' }}{{ number_format($sNet) }}<span style="font-size:10px"> mL</span></div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        </div>
    </div>

    {{-- Add entry form --}}
    <div class="card">
        <div class="card-header"><i class="fa-solid fa-plus"></i> Record Entry</div>
        <div class="card-body">
            @if(session('success') && str_contains(session('success'), 'Entry'))
            <div class="alert alert-success" style="margin-bottom:12px;padding:8px 12px;font-size:13px">{{ session('success') }}</div>
            @endif
            <form method="POST" action="{{ route('admissions.io.store', $admission) }}">
                @csrf
                <div class="grid" style="grid-template-columns:1fr 1fr;gap:10px">
                    <div class="form-group" style="margin:0">
                        <label class="form-label">Type</label>
                        <select name="io_type" id="ioType" class="form-control" onchange="updateIoCats()">
                            <option value="input">Input</option>
                            <option value="output">Output</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin:0">
                        <label class="form-label">Category</label>
                        <select name="category" id="ioCat" class="form-control"></select>
                    </div>
                    <div class="form-group" style="margin:0">
                        <label class="form-label">Amount (mL)</label>
                        <input type="number" name="amount_ml" class="form-control" min="1" max="9999" placeholder="e.g. 250" required>
                    </div>
                    <div class="form-group" style="margin:0">
                        <label class="form-label">Time</label>
                        <input type="datetime-local" name="recorded_at" class="form-control" value="{{ now()->format('Y-m-d\TH:i') }}" required>
                    </div>
                </div>
                <div class="form-group" style="margin:10px 0 0">
                    <label class="form-label">Remarks (optional)</label>
                    <input type="text" name="remarks" class="form-control" placeholder="e.g. NS 500mL @ 60 mL/hr">
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;margin-top:12px"><i class="fa-solid fa-plus"></i> Add Entry</button>
            </form>
        </div>
    </div>
</div>

{{-- Entries table --}}
<div class="card">
    <div class="card-header"><i class="fa-solid fa-table-list"></i> Today's Entries</div>
    @if($ioToday->isEmpty())
    <div class="card-body text-center text-muted" style="padding:32px">No I/O entries recorded for today.</div>
    @else
    <div class="table-wrap">
        <table class="sc-table">
            <thead>
                <tr>
                    <th>Time</th>
                    <th>Type</th>
                    <th>Category</th>
                    <th style="text-align:right">Amount (mL)</th>
                    <th>Remarks</th>
                    <th>By</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @foreach($ioToday as $entry)
            <tr>
                <td class="mono text-small">{{ $entry->recorded_at->format('H:i') }}</td>
                <td>
                    <span class="badge {{ $entry->io_type === 'input' ? 'badge-teal' : 'badge-amber' }}">
                        {{ ucfirst($entry->io_type) }}
                    </span>
                </td>
                <td>{{ $entry->category_label }}</td>
                <td style="text-align:right;font-weight:700">{{ number_format($entry->amount_ml) }}</td>
                <td class="text-small text-muted">{{ $entry->remarks ?? '—' }}</td>
                <td class="text-small text-muted">{{ $entry->recordedBy->name ?? '—' }}</td>
                <td>
                    <form method="POST" action="{{ route('admissions.io.destroy', [$admission, $entry]) }}" onsubmit="return confirm('Remove this entry?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline" style="color:var(--sc-red);border-color:var(--sc-red)"><i class="fa-solid fa-trash"></i></button>
                    </form>
                </td>
            </tr>
            @endforeach
            </tbody>
            <tfoot>
                <tr style="background:var(--sc-bg);font-weight:700">
                    <td colspan="3" style="text-align:right;padding:8px 12px">Totals</td>
                    <td style="text-align:right;padding:8px 12px">
                        <span style="color:var(--sc-teal)">+{{ number_format($ioTotalIn) }}</span> /
                        <span style="color:var(--sc-amber)">−{{ number_format($ioTotalOut) }}</span>
                    </td>
                    <td colspan="3" style="padding:8px 12px">
                        Net: <strong style="color:{{ ($ioTotalIn-$ioTotalOut)>=0?'var(--sc-green)':'var(--sc-red)' }}">{{ ($ioTotalIn-$ioTotalOut)>=0?'+':'' }}{{ number_format($ioTotalIn-$ioTotalOut) }} mL</strong>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
    @endif
</div>

<script>
const IO_CATS = {
    input:  @json(\App\Models\IoEntry::INPUT_CATEGORIES),
    output: @json(\App\Models\IoEntry::OUTPUT_CATEGORIES),
};
function updateIoCats() {
    const type = document.getElementById('ioType').value;
    const sel  = document.getElementById('ioCat');
    sel.innerHTML = '';
    Object.entries(IO_CATS[type]).forEach(([k,v]) => {
        const o = document.createElement('option');
        o.value = k; o.textContent = v;
        sel.appendChild(o);
    });
}
updateIoCats();
</script>
</div>

{{-- ── TAB: LAB TESTS ── --}}
<div class="tab-pane" id="tab-labs">
    <div class="card">
        <div class="card-header">
            <i class="fa-solid fa-flask"></i> Lab Orders for this Admission
            <div class="card-actions">
                <a href="{{ route('lab.create-order') }}?patient_id={{ $admission->patient_id }}&admission_id={{ $admission->id }}" class="btn btn-sm btn-primary">
                    <i class="fa-solid fa-plus"></i> Order Tests
                </a>
            </div>
        </div>
        @if($labOrders->isEmpty())
        <div class="card-body text-center text-muted" style="padding:40px">
            <i class="fa-solid fa-flask" style="font-size:2rem;opacity:.3;margin-bottom:12px;display:block"></i>
            No lab orders for this admission.
            <div style="margin-top:12px">
                <a href="{{ route('lab.create-order') }}?patient_id={{ $admission->patient_id }}&admission_id={{ $admission->id }}" class="btn btn-primary btn-sm">Order Lab Tests</a>
            </div>
        </div>
        @else
        <div class="table-wrap">
            <table class="sc-table">
                <thead>
                    <tr><th>Order No.</th><th>Date</th><th>Tests / Panels</th><th>Status</th><th class="text-right">Amount</th><th></th></tr>
                </thead>
                <tbody>
                @foreach($labOrders as $lo)
                <tr>
                    <td class="mono fw-600">{{ $lo->order_no }}</td>
                    <td>{{ $lo->ordered_date->format('d M Y') }}</td>
                    <td>
                        @foreach($lo->items as $item)
                        <span class="badge badge-navy" style="margin-right:3px;margin-bottom:3px">
                            {{ $item->test?->name ?? $item->panel?->name ?? $item->item_name ?? '—' }}
                            @if($item->status === 'Completed') <i class="fa-solid fa-check" style="color:#4ade80;margin-left:3px"></i> @endif
                        </span>
                        @endforeach
                    </td>
                    <td>
                        @php $labSc=['Ordered'=>'badge-amber','Collected'=>'badge-teal','Processing'=>'badge-navy','Completed'=>'badge-green','Cancelled'=>'badge-red']; @endphp
                        <span class="badge {{ $labSc[$lo->status] ?? 'badge-gray' }}">{{ $lo->status }}</span>
                    </td>
                    <td class="text-right">₹{{ number_format($lo->net_amount, 2) }}</td>
                    <td>
                        <a href="{{ route('lab.order', $lo) }}" class="btn btn-sm btn-outline">
                            @if($lo->status === 'Completed') View Results @else Manage @endif
                        </a>
                    </td>
                </tr>
                @if($lo->status === 'Completed')
                {{-- Show result values inline for completed orders --}}
                @foreach($lo->items->where('status','Completed') as $item)
                @if($item->result_value)
                <tr style="background:#f0fdf4">
                    <td colspan="2" style="padding:4px 14px;font-size:11px;color:var(--sc-muted)">Result</td>
                    <td colspan="4" style="padding:4px 14px;font-size:11.5px">
                        <strong>{{ $item->test?->name ?? '—' }}</strong>:
                        {{ $item->result_value }}{{ $item->test?->unit ? ' '.$item->test->unit : '' }}
                        @if($item->abnormal_flag) <span class="badge {{ $item->abnormal_flag==='H'?'badge-red':($item->abnormal_flag==='L'?'badge-amber':'badge-gray') }}" style="font-size:9px">{{ $item->abnormal_flag }}</span> @endif
                    </td>
                </tr>
                @endif
                @endforeach
                @endif
                @endforeach
                </tbody>
                <tfoot>
                    <tr style="background:var(--sc-bg)">
                        <td colspan="4" class="fw-700" style="padding:10px 14px">Total</td>
                        <td class="text-right fw-700" style="padding:10px 14px">₹{{ number_format($labOrders->sum('net_amount'), 2) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        @endif
    </div>
</div>

{{-- ── TAB: RADIOLOGY ── --}}
<div class="tab-pane" id="tab-radiology">
    <div class="card">
        <div class="card-header">
            <i class="fa-solid fa-radiation"></i> Radiology Orders
            <div class="card-actions">
                <a href="{{ route('radiology.create') }}?patient_id={{ $admission->patient_id }}&admission_id={{ $admission->id }}" class="btn btn-sm btn-primary">
                    <i class="fa-solid fa-plus"></i> Order Imaging
                </a>
            </div>
        </div>
        @if($radiologyOrders->isEmpty())
        <div class="card-body text-center text-muted" style="padding:40px">No radiology orders for this admission.</div>
        @else
        <div class="table-wrap">
            <table class="sc-table">
                <thead>
                    <tr><th>Order No.</th><th>Modality</th><th>Body Part</th><th>Urgency</th><th>Doctor</th><th>Status</th><th>Date</th><th></th></tr>
                </thead>
                <tbody>
                @foreach($radiologyOrders as $ro)
                <tr>
                    <td class="mono fw-600">{{ $ro->order_no }}</td>
                    <td>
                        @php $rm = \App\Models\RadiologyOrder::MODALITIES[$ro->modality] ?? ['label'=>$ro->modality,'icon'=>'fa-x-ray']; @endphp
                        <span class="badge badge-navy"><i class="fa-solid {{ $rm['icon'] }}"></i> {{ $rm['label'] }}</span>
                    </td>
                    <td>{{ $ro->body_part }}</td>
                    <td>
                        @php $ruc = ['routine'=>'badge-gray','urgent'=>'badge-amber','emergency'=>'badge-red']; @endphp
                        <span class="badge {{ $ruc[$ro->urgency] ?? 'badge-gray' }}">{{ ucfirst($ro->urgency) }}</span>
                    </td>
                    <td class="text-small">{{ $ro->orderedBy ? 'Dr. '.$ro->orderedBy->first_name.' '.$ro->orderedBy->last_name : '—' }}</td>
                    <td>
                        @php $rsc = ['ordered'=>'badge-amber','scheduled'=>'badge-teal','completed'=>'badge-navy','reported'=>'badge-green']; @endphp
                        <span class="badge {{ $rsc[$ro->status] ?? 'badge-gray' }}">{{ ucfirst($ro->status) }}</span>
                    </td>
                    <td class="text-small">{{ $ro->created_at->format('d M Y') }}</td>
                    <td>
                        <div style="display:flex;gap:4px">
                            <a href="{{ route('radiology.show', $ro) }}" class="btn btn-sm btn-outline">View</a>
                            @if($ro->status === 'reported')
                            <a href="{{ route('radiology.print', $ro) }}" class="btn btn-sm btn-outline" target="_blank"><i class="fa-solid fa-print"></i></a>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>

{{-- ── TAB: OT ── --}}
<div class="tab-pane" id="tab-ot">
    <div class="card">
        <div class="card-header">
            <i class="fa-solid fa-syringe"></i> Operations
            <div class="card-actions">
                <a href="{{ route('ot.create') }}?patient_id={{ $admission->patient_id }}&admission_id={{ $admission->id }}" class="btn btn-sm btn-primary">
                    <i class="fa-solid fa-plus"></i> Record Operation
                </a>
            </div>
        </div>
        @if($operations->isEmpty())
        <div class="card-body text-center text-muted" style="padding:40px">No operations recorded for this admission.</div>
        @else
        <div class="table-wrap">
            <table class="sc-table">
                <thead>
                    <tr><th>Op. No.</th><th>Date</th><th>Procedure</th><th>Surgeon</th><th>Outcome</th><th>Status</th><th></th></tr>
                </thead>
                <tbody>
                @foreach($operations as $op)
                <tr>
                    <td class="mono fw-600">{{ $op->operation_no }}</td>
                    <td class="text-small">{{ $op->operation_date->format('d M Y') }}</td>
                    <td>{{ $op->procedure_name }}</td>
                    <td class="text-small">Dr. {{ $op->surgeon->first_name }} {{ $op->surgeon->last_name }}</td>
                    <td>
                        @php $oc = ['successful'=>'green','complicated'=>'amber','abandoned'=>'red','patient_expired'=>'red']; @endphp
                        <span class="badge badge-{{ $oc[$op->outcome] ?? 'gray' }}">{{ \App\Models\Operation::OUTCOME_LABELS[$op->outcome] ?? $op->outcome }}</span>
                    </td>
                    <td><span class="badge badge-{{ $op->status_color }}">{{ ucfirst(str_replace('_',' ',$op->status)) }}</span></td>
                    <td><a href="{{ route('ot.show', $op) }}" class="btn btn-sm btn-outline">View</a></td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>

{{-- ── TAB: CONSENTS ── --}}
<div class="tab-pane" id="tab-consents">
@php $consentTypes = \App\Models\ConsentForm::TYPES; @endphp

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
    <div style="font-size:13px;color:var(--sc-muted)">Track and print consent forms for this admission</div>
</div>

<div class="grid" style="grid-template-columns:1fr 1fr;gap:16px">
@foreach($consentTypes as $typeKey => $typeDef)
@php $c = $consents[$typeKey] ?? null; @endphp
<div class="card">
    <div class="card-header" style="gap:10px">
        <i class="fa-solid {{ $typeDef['icon'] }}"></i>
        <span>{{ $typeDef['label'] }}</span>
        @if($c && $c->is_obtained)
            <span class="badge badge-green" style="margin-left:auto"><i class="fa-solid fa-check"></i> Obtained</span>
        @elseif($c)
            <span class="badge badge-amber" style="margin-left:auto">Pending</span>
        @else
            <span class="badge badge-gray" style="margin-left:auto">Not recorded</span>
        @endif
        <div class="card-actions">
            <a href="{{ route('admissions.consents.print', [$admission, $typeKey]) }}" class="btn btn-sm btn-outline" target="_blank"><i class="fa-solid fa-print"></i> Print</a>
        </div>
    </div>
    <div class="card-body">
        @if($c && $c->is_obtained)
        <div style="font-size:12px;color:var(--sc-muted);margin-bottom:10px">
            Signed by <strong>{{ $c->patient_or_guardian ?: $admission->patient->full_name }}</strong>
            @if($c->relationship) ({{ $c->relationship }}) @endif
            &nbsp;·&nbsp; {{ $c->obtained_at?->format('d M Y, H:i') }}
            @if($c->witness_name) &nbsp;·&nbsp; Witness: {{ $c->witness_name }} @endif
        </div>
        @endif

        <form method="POST" action="{{ route('admissions.consents.store', $admission) }}">
            @csrf
            <input type="hidden" name="consent_type" value="{{ $typeKey }}">
            @if(in_array($typeKey, ['surgical','procedure']))
            <div class="form-group" style="margin-bottom:8px">
                <label class="form-label" style="font-size:11px">Procedure / Operation Name</label>
                <input type="text" name="procedure_name" class="form-control form-control-sm" value="{{ $c?->procedure_name }}" placeholder="e.g. Appendicectomy">
            </div>
            @endif
            <div class="grid" style="grid-template-columns:1fr 1fr;gap:8px;margin-bottom:8px">
                <div class="form-group" style="margin:0">
                    <label class="form-label" style="font-size:11px">Patient / Guardian Name</label>
                    <input type="text" name="patient_or_guardian" class="form-control form-control-sm" value="{{ $c?->patient_or_guardian ?? $admission->patient->full_name }}" placeholder="Name of signatory">
                </div>
                <div class="form-group" style="margin:0">
                    <label class="form-label" style="font-size:11px">Relationship</label>
                    <select name="relationship" class="form-control form-control-sm">
                        <option value="self" {{ ($c?->relationship ?? 'self')==='self'?'selected':'' }}>Self</option>
                        <option value="spouse" {{ ($c?->relationship)==='spouse'?'selected':'' }}>Spouse</option>
                        <option value="parent" {{ ($c?->relationship)==='parent'?'selected':'' }}>Parent</option>
                        <option value="child" {{ ($c?->relationship)==='child'?'selected':'' }}>Child</option>
                        <option value="guardian" {{ ($c?->relationship)==='guardian'?'selected':'' }}>Guardian</option>
                    </select>
                </div>
            </div>
            <div class="form-group" style="margin-bottom:8px">
                <label class="form-label" style="font-size:11px">Witness Name</label>
                <input type="text" name="witness_name" class="form-control form-control-sm" value="{{ $c?->witness_name }}" placeholder="Staff witness name">
            </div>
            <div class="form-group" style="margin-bottom:10px">
                <label class="form-label" style="font-size:11px">Notes</label>
                <input type="text" name="notes" class="form-control form-control-sm" value="{{ $c?->notes }}" placeholder="e.g. patient refused, verbal consent">
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px">
                    <input type="checkbox" name="is_obtained" value="1" {{ ($c?->is_obtained) ? 'checked' : '' }}>
                    <span>Mark as Obtained</span>
                </label>
                <button type="submit" class="btn btn-sm btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save</button>
            </div>
        </form>

        @if($c)
        <div style="margin-top:8px;text-align:right">
            <form method="POST" action="{{ route('admissions.consents.destroy', [$admission, $c]) }}" onsubmit="return confirm('Remove this consent record?')" style="display:inline">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-sm btn-outline" style="color:var(--sc-red);border-color:var(--sc-red)"><i class="fa-solid fa-trash"></i> Remove</button>
            </form>
        </div>
        @endif
    </div>
</div>
@endforeach
</div>
</div>

{{-- ── TAB: BILLING ── --}}
<div class="tab-pane" id="tab-billing">
<div class="ipd-two-col">

    {{-- LEFT: data tables --}}
    <div style="display:flex;flex-direction:column;gap:20px">

        {{-- Charges by date --}}
        <div class="card">
            <div class="card-header">
                <i class="fa-solid fa-receipt"></i> Charges
                <div class="card-actions">
                    <span class="fw-700" style="color:var(--sc-navy)">₹{{ number_format($totalCharges,2) }}</span>
                </div>
            </div>
            <div class="card-body" style="padding:0">
                @if($charges->isEmpty())
                <div style="padding:32px;text-align:center;color:var(--sc-muted)">No charges added yet.</div>
                @else
                @php $chargesByDate3 = $charges->groupBy(fn($c) => $c->charge_date->format('Y-m-d')); @endphp
                @foreach($chargesByDate3 as $cDate => $dayCharges)
                <div style="border-bottom:1px solid var(--sc-border)">
                    <div style="padding:8px 16px;background:var(--sc-bg);font-size:11px;font-weight:600;color:var(--sc-muted);text-transform:uppercase;display:flex;justify-content:space-between">
                        <span>{{ \Carbon\Carbon::parse($cDate)->format('d M Y, D') }}</span>
                        <span>₹{{ number_format($dayCharges->sum('amount'),2) }}</span>
                    </div>
                    <div class="table-wrap">
                        <table class="sc-table">
                            <thead><tr>
                                <th>Category</th><th>Description</th>
                                <th class="text-right">Unit</th><th class="text-right">Qty</th>
                                <th class="text-right">Amount</th><th></th>
                            </tr></thead>
                            <tbody>
                            @foreach($dayCharges as $charge)
                            <tr>
                                <td><span class="badge badge-{{ $charge->category_color }}"><i class="fa-solid {{ $charge->category_icon }}"></i> {{ $charge->category_label }}</span></td>
                                <td>{{ $charge->description }}@if($charge->notes)<div class="text-small text-muted">{{ $charge->notes }}</div>@endif</td>
                                <td class="text-right mono">₹{{ number_format($charge->unit_price,2) }}</td>
                                <td class="text-right">{{ rtrim(rtrim(number_format($charge->quantity,2,'.',','),'0'),'.') }}</td>
                                <td class="text-right mono fw-600">₹{{ number_format($charge->amount,2) }}</td>
                                <td>@if($charge->created_by===$u->id||$u->isAdmin())
                                    <form method="POST" action="{{ route('admissions.charges.destroy',[$admission,$charge]) }}" onsubmit="return confirm('Delete this charge?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline" style="color:var(--sc-red);border-color:var(--sc-red)"><i class="fa-solid fa-trash"></i></button>
                                    </form>
                                @endif</td>
                            </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endforeach
                @endif
            </div>
        </div>

        {{-- Packages --}}
        <div class="card">
            <div class="card-header">
                <i class="fa-solid fa-box-open"></i> Packages
                <div class="card-actions">
                    <span class="text-small text-muted">{{ $admissionPackages->count() }} assigned</span>
                </div>
            </div>
            <div class="card-body" style="padding:0">
                @if($admissionPackages->isEmpty())
                <div style="padding:24px;text-align:center;color:var(--sc-muted)">No packages assigned.</div>
                @else
                <div class="table-wrap">
                    <table class="sc-table">
                        <thead><tr>
                            <th>Package</th><th>Category</th>
                            <th class="text-right">Quoted</th><th class="text-right">Paid</th><th class="text-right">Balance</th>
                            <th>Status</th><th></th>
                        </tr></thead>
                        <tbody>
                        @foreach($admissionPackages as $ap)
                        <tr>
                            <td class="fw-600">{{ $ap->package->name }}</td>
                            <td class="text-small text-muted">{{ \App\Models\BillingPackage::CATEGORIES[$ap->package->category] ?? '—' }}</td>
                            <td class="text-right">₹{{ number_format($ap->quoted_price,0) }}</td>
                            <td class="text-right">₹{{ number_format($ap->paid_amount,0) }}</td>
                            <td class="text-right fw-600" style="color:{{ $ap->balance>0?'#dc2626':'#16a34a' }}">₹{{ number_format($ap->balance,0) }}</td>
                            <td>
                                <form method="POST" action="{{ route('packages.status',[$admission,$ap]) }}" style="display:flex;gap:6px;align-items:center">
                                    @csrf
                                    <select name="status" class="form-control" style="padding:3px 6px;font-size:12px;width:110px">
                                        @foreach(['active'=>'Active','settled'=>'Settled','cancelled'=>'Cancelled'] as $k=>$l)
                                        <option value="{{ $k }}" {{ $ap->status===$k?'selected':'' }}>{{ $l }}</option>
                                        @endforeach
                                    </select>
                                    <input type="number" name="paid_amount" value="{{ $ap->paid_amount }}" step="0.01" min="0" class="form-control" style="width:85px;padding:3px 6px;font-size:12px">
                                    <button type="submit" class="btn btn-xs btn-outline">Save</button>
                                </form>
                            </td>
                            <td>
                                <form method="POST" action="{{ route('packages.remove',[$admission,$ap]) }}" onsubmit="return confirm('Remove?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-xs btn-outline" style="color:var(--sc-red);border-color:var(--sc-red)"><i class="fa-solid fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        </div>

        {{-- Insurance claims --}}
        <div class="card">
            <div class="card-header">
                <i class="fa-solid fa-shield-halved"></i> Insurance / TPA
                <div class="card-actions">
                    <a href="{{ route('insurance.claims.create', ['admission_id' => $admission->id]) }}" class="btn btn-sm btn-primary">
                        <i class="fa-solid fa-plus"></i> New Claim
                    </a>
                </div>
            </div>
            <div class="card-body" style="padding:0">
                @if($insuranceClaims->isEmpty())
                <div style="padding:24px;text-align:center;color:var(--sc-muted)">
                    <i class="fa-solid fa-shield-halved" style="font-size:1.8rem;display:block;margin-bottom:8px;opacity:.3"></i>
                    No insurance claims.
                </div>
                @else
                <div class="table-wrap">
                    <table class="sc-table">
                        <thead><tr>
                            <th>Claim No.</th><th>Insurer/TPA</th><th>Policy No.</th>
                            <th class="text-right">Approved</th><th class="text-right">Settled</th>
                            <th>Status</th><th></th>
                        </tr></thead>
                        <tbody>
                        @foreach($insuranceClaims as $ic)
                        <tr>
                            <td class="fw-600">{{ $ic->claim_no ?? '—' }}</td>
                            <td>{{ $ic->insurer->name ?? '—' }}</td>
                            <td style="font-family:monospace;font-size:12px">{{ $ic->policy_no ?? '—' }}</td>
                            <td class="text-right">{{ $ic->approved_amount?'₹'.number_format($ic->approved_amount,0):'—' }}</td>
                            <td class="text-right">{{ $ic->settled_amount?'₹'.number_format($ic->settled_amount,0):'—' }}</td>
                            <td><span class="badge badge-{{ $ic->status_color }}" style="font-size:11px">{{ $ic->status_label }}</span></td>
                            <td><a href="{{ route('insurance.claims.show',$ic) }}" class="btn btn-xs btn-outline">View</a></td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        </div>

    </div>

    {{-- RIGHT: sticky forms + balance --}}
    <div style="position:sticky;top:80px;display:flex;flex-direction:column;gap:16px">

        {{-- Balance summary --}}
        <div style="background:var(--sc-navy);color:#fff;padding:16px 20px;border-radius:var(--radius)">
            @php
                $catTotals3    = $charges->groupBy('category')->map(fn($g) => $g->sum('amount'));
                $totalDep3     = $deposits->sum('amount') + $totalPkgPaid;
                $netBalance3   = $totalCharges - $totalDep3;
            @endphp
            @foreach($catTotals3 as $cat => $amt)
            <div style="display:flex;justify-content:space-between;font-size:12px;opacity:.75;margin-bottom:3px">
                <span>{{ \App\Models\IpdCharge::CATEGORIES[$cat]['label'] ?? $cat }}</span>
                <span>₹{{ number_format($amt,2) }}</span>
            </div>
            @endforeach
            @if($admissionPackages->count())
            <div style="display:flex;justify-content:space-between;font-size:12px;opacity:.75;margin-bottom:3px">
                <span>Packages ({{ $admissionPackages->count() }})</span>
                <span>₹{{ number_format($admissionPackages->sum('quoted_price'),2) }}</span>
            </div>
            @endif
            <div style="border-top:1px solid rgba(255,255,255,.2);margin-top:8px;padding-top:8px;display:flex;justify-content:space-between;font-weight:700;font-size:15px">
                <span>Total Charges</span><span>₹{{ number_format($totalCharges,2) }}</span>
            </div>
            <div style="display:flex;justify-content:space-between;font-size:13px;opacity:.8;margin-top:4px">
                <span>Deposits</span>
                <span style="color:#4ade80">− ₹{{ number_format($deposits->sum('amount'),2) }}</span>
            </div>
            @if($totalPkgPaid > 0)
            <div style="display:flex;justify-content:space-between;font-size:13px;opacity:.8;margin-top:2px">
                <span>Package Paid</span>
                <span style="color:#4ade80">− ₹{{ number_format($totalPkgPaid,2) }}</span>
            </div>
            @endif
            <div style="border-top:1px solid rgba(255,255,255,.2);margin-top:8px;padding-top:8px;display:flex;justify-content:space-between;font-weight:700;font-size:16px;color:{{ $netBalance3 > 0 ? '#fbbf24' : '#4ade80' }}">
                @if($netBalance3 > 0)
                <span>Balance Due</span><span>₹{{ number_format($netBalance3,2) }}</span>
                @elseif($netBalance3 < 0)
                <span>Refund to Patient</span><span>₹{{ number_format(abs($netBalance3),2) }}</span>
                @else
                <span>Balance Due</span><span>Settled ✓</span>
                @endif
            </div>
            @if($netBalance3 > 0)
            <button type="button" onclick="openPayNowModal({{ $netBalance3 }})"
                style="margin-top:10px;width:100%;padding:9px;background:#22c55e;color:#fff;border:none;border-radius:6px;font-size:13px;font-weight:700;cursor:pointer">
                <i class="fa-solid fa-circle-dollar-to-slot"></i> Pay Now
            </button>
            @endif
            <div style="margin-top:10px">
                <a href="{{ route('admissions.bill', $admission) }}" target="_blank"
                   style="display:block;text-align:center;background:rgba(255,255,255,.12);color:#fff;padding:8px;border-radius:6px;font-size:12px;font-weight:600;text-decoration:none">
                    <i class="fa-solid fa-print"></i> Print IPD Bill
                </a>
            </div>
        </div>

        {{-- Add Charge --}}
        <div class="card">
            <div class="card-header">
                <i class="fa-solid fa-plus"></i> Add Charge
                <div class="card-actions">
                    <button type="button" class="btn btn-sm btn-outline" onclick="openTemplateModal()">
                        <i class="fa-solid fa-layer-group"></i> Template
                    </button>
                </div>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admissions.charges.store', $admission) }}" id="chargeForm">
                    @csrf
                    <div class="form-group">
                        <label class="form-label">Date <span class="req">*</span></label>
                        <input type="date" name="charge_date" class="form-control" value="{{ today()->toDateString() }}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Category <span class="req">*</span></label>
                        <select name="category" class="form-control" required id="chargeCategory">
                            @foreach(\App\Models\IpdCharge::CATEGORIES as $val => $meta)
                            <option value="{{ $val }}">{{ $meta['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Description <span class="req">*</span></label>
                        <input type="text" name="description" class="form-control" id="chargeDesc" required placeholder="e.g. Ward per day">
                    </div>
                    <div class="form-row cols-2">
                        <div class="form-group">
                            <label class="form-label">Unit Price (₹) <span class="req">*</span></label>
                            <input type="number" name="unit_price" class="form-control" id="unitPrice" step="0.01" min="0" required placeholder="0.00">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Qty <span class="req">*</span></label>
                            <input type="number" name="quantity" class="form-control" id="chargeQty" value="1" step="0.01" min="0.01" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Amount</label>
                        <div id="chargeTotal" style="font-size:18px;font-weight:700;color:var(--sc-teal);padding:6px 0">₹0.00</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Notes</label>
                        <input type="text" name="notes" class="form-control" placeholder="Optional">
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%">
                        <i class="fa-solid fa-plus"></i> Add Charge
                    </button>
                </form>
            </div>
        </div>

        {{-- Record Payment --}}
        <div class="card">
            <div class="card-header"><i class="fa-solid fa-money-bill-transfer"></i> Record Payment</div>
            <div class="card-body">
                <form method="POST" action="{{ route('admissions.deposits.store', $admission) }}">
                    @csrf
                    <div class="form-group">
                        <label class="form-label">Amount (₹) <span class="req">*</span></label>
                        <input type="number" name="amount" class="form-control" step="0.01" min="0.01" required placeholder="0.00">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Method <span class="req">*</span></label>
                        <select name="payment_method" class="form-control" required>
                            @foreach(['cash'=>'Cash','card'=>'Card','upi'=>'UPI','cheque'=>'Cheque','other'=>'Other'] as $v=>$l)
                            <option value="{{ $v }}">{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Payment Date <span class="req">*</span></label>
                        <input type="date" name="payment_date" class="form-control" value="{{ today()->toDateString() }}" max="{{ today()->toDateString() }}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Transaction Ref</label>
                        <input type="text" name="transaction_ref" class="form-control" placeholder="Optional">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Notes</label>
                        <input type="text" name="notes" class="form-control" placeholder="Optional">
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%">
                        <i class="fa-solid fa-plus"></i> Record Payment
                    </button>
                </form>

                @if($deposits->count())
                <div style="margin-top:14px;border-top:1px solid var(--sc-border);padding-top:12px">
                    <div style="font-size:12px;font-weight:600;color:var(--sc-muted);margin-bottom:8px">Payment History</div>
                    @foreach($deposits as $dep)
                    <div style="display:flex;justify-content:space-between;align-items:center;padding:5px 0;border-bottom:1px solid var(--sc-border);font-size:12.5px">
                        <div>
                            <span class="fw-600" style="color:var(--sc-green)">₹{{ number_format($dep->amount,2) }}</span>
                            <span class="badge badge-teal" style="font-size:10px;margin-left:4px">{{ strtoupper($dep->payment_method) }}</span>
                            <div style="font-size:11px;color:var(--sc-muted)">{{ $dep->created_at->format('d M Y, H:i') }}</div>
                        </div>
                        @if($u->isAdmin())
                        <form method="POST" action="{{ route('admissions.deposits.destroy',[$admission,$dep]) }}" onsubmit="return confirm('Delete?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline" style="color:var(--sc-red);border-color:var(--sc-red);padding:3px 7px"><i class="fa-solid fa-trash"></i></button>
                        </form>
                        @endif
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>

        {{-- Assign Package --}}
        @if($availablePackages->count())
        <div class="card">
            <div class="card-header"><i class="fa-solid fa-box-open"></i> Assign Package</div>
            <div class="card-body">
                <form method="POST" action="{{ route('packages.assign', $admission) }}">
                    @csrf
                    <div class="form-group">
                        <label class="form-label">Package</label>
                        <select name="package_id" id="pkgSelect" onchange="fillPkgPrice(this)" class="form-control" required>
                            <option value="">— Select —</option>
                            @foreach($availablePackages as $pkg)
                            <option value="{{ $pkg->id }}" data-price="{{ $pkg->package_price }}">{{ $pkg->name }} (₹{{ number_format($pkg->package_price,0) }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Quoted Price (₹)</label>
                        <input type="number" name="quoted_price" id="pkgPrice" step="0.01" min="0" class="form-control" required placeholder="0.00">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Notes</label>
                        <input type="text" name="notes" class="form-control" placeholder="Optional">
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%">Assign Package</button>
                </form>
            </div>
        </div>
        @endif

    </div>
</div>
</div>

{{-- ── TAB: DISCHARGE ── --}}
@if($admission->status === 'discharged')
<div class="tab-pane" id="tab-discharge">
    <div class="card">
        <div class="card-header"><i class="fa-solid fa-person-walking-arrow-right"></i> Discharge Details</div>
        <div class="card-body">
            <div class="grid" style="grid-template-columns:1fr 1fr 1fr;gap:16px;margin-bottom:16px">
                <div>
                    <div class="form-label">Discharge Date</div>
                    <div>{{ $admission->discharge_date->format('d M Y, h:i A') }}</div>
                </div>
                <div>
                    <div class="form-label">Discharge Condition</div>
                    <div>{{ \App\Models\Admission::DISCHARGE_CONDITIONS[$admission->discharge_condition] ?? $admission->discharge_condition }}</div>
                </div>
                <div>
                    <div class="form-label">Total Stay</div>
                    <div class="fw-700">{{ $admission->days_admitted }} day{{ $admission->days_admitted != 1 ? 's' : '' }}</div>
                </div>
            </div>
            @if($admission->discharge_notes)
            <div style="margin-bottom:12px">
                <div class="form-label">Discharge Notes</div>
                <div>{{ $admission->discharge_notes }}</div>
            </div>
            @endif
            @if($admission->discharge_summary)
            <div>
                <div class="form-label">Discharge Summary</div>
                <div style="white-space:pre-wrap;line-height:1.6">{{ $admission->discharge_summary }}</div>
            </div>
            @endif
            <div style="margin-top:16px;display:flex;gap:10px;flex-wrap:wrap">
                <a href="{{ route('admissions.summary', $admission) }}" class="btn btn-navy" target="_blank">
                    <i class="fa-solid fa-print"></i> Print Discharge Summary
                </a>
                <a href="{{ route('admissions.summary-pdf', $admission) }}" class="btn btn-primary">
                    <i class="fa-solid fa-file-pdf"></i> Download PDF
                </a>
                <a href="{{ route('admissions.discharge-instructions', $admission) }}" class="btn btn-outline" target="_blank">
                    <i class="fa-solid fa-file-medical"></i> Discharge Instructions
                </a>
            </div>
        </div>
    </div>
</div>
@endif

{{-- Apply Charge Template modal --}}
<div class="modal-overlay" id="templateModal">
    <div class="modal-box" style="max-width:520px">
        <div class="modal-header">
            <h3><i class="fa-solid fa-layer-group"></i> Apply Charge Template</h3>
            <button class="modal-close" onclick="closeTemplateModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label">Select Template</label>
                <select id="templateSelect" class="form-control" onchange="previewTemplate(this.value)">
                    <option value="">— Choose a template —</option>
                </select>
            </div>
            <div id="templatePreview" style="display:none">
                <div class="form-label" style="margin-top:12px">Items in this template:</div>
                <div id="templateItems" style="font-size:13px;display:flex;flex-direction:column;gap:6px;margin-top:6px;padding:12px;background:var(--sc-bg);border-radius:8px;border:1px solid var(--sc-border)"></div>
                <div style="display:flex;justify-content:space-between;font-weight:700;margin-top:8px;padding:0 4px">
                    <span>Package Total</span><span id="templateTotal" class="mono"></span>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" onclick="closeTemplateModal()">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="applyTemplate()"><i class="fa-solid fa-check"></i> Apply All Charges</button>
        </div>
    </div>
</div>
<script>
let tplData = [];
let selectedTpl = null;

async function openTemplateModal() {
    if (!tplData.length) {
        const res = await fetch('{{ route("charge-templates.list") }}');
        tplData = await res.json();
        const sel = document.getElementById('templateSelect');
        tplData.forEach(t => {
            const opt = new Option(t.name, t.id);
            sel.add(opt);
        });
    }
    document.getElementById('templateModal').classList.add('open');
}
function closeTemplateModal() { document.getElementById('templateModal').classList.remove('open'); }

function previewTemplate(id) {
    selectedTpl = tplData.find(t => t.id == id);
    const prev = document.getElementById('templatePreview');
    if (!selectedTpl) { prev.style.display = 'none'; return; }
    let total = 0;
    document.getElementById('templateItems').innerHTML = selectedTpl.items.map(item => {
        const amt = item.unit_price * item.quantity;
        total += amt;
        return `<div style="display:flex;justify-content:space-between">
            <span>${item.description} <span class="text-muted text-small">(×${item.quantity})</span></span>
            <span class="mono">₹${amt.toLocaleString('en-IN',{minimumFractionDigits:2})}</span>
        </div>`;
    }).join('');
    document.getElementById('templateTotal').textContent = '₹' + total.toLocaleString('en-IN',{minimumFractionDigits:2});
    prev.style.display = 'block';
}

async function applyTemplate() {
    if (!selectedTpl) { alert('Please select a template.'); return; }
    const today = new Date().toISOString().slice(0,10);
    const token = document.querySelector('meta[name=csrf-token]').content;
    let ok = 0;
    for (const item of selectedTpl.items) {
        const body = new URLSearchParams({
            _token: token,
            charge_date: today,
            category: item.category,
            description: item.description,
            unit_price: item.unit_price,
            quantity: item.quantity,
            notes: 'Applied from template: ' + selectedTpl.name,
        });
        const r = await fetch('{{ route("admissions.charges.store", $admission) }}', {
            method: 'POST', body, headers: {'Content-Type':'application/x-www-form-urlencoded', 'X-Requested-With':'XMLHttpRequest'}
        });
        if (r.ok || r.redirected) ok++;
    }
    closeTemplateModal();
    location.reload();
}
</script>

{{-- Pay Now modal --}}
<div class="modal-overlay" id="payNowModal">
    <div class="modal-box" style="max-width:440px">
        <div class="modal-header">
            <h3><i class="fa-solid fa-circle-dollar-to-slot"></i> Record Payment</h3>
            <button class="modal-close" onclick="closePayNowModal()">&times;</button>
        </div>
        <form method="POST" action="{{ route('admissions.deposits.store', $admission) }}">
            @csrf
            <div class="modal-body">
                <div id="payNowBalanceInfo" style="background:var(--sc-bg);border:1px solid var(--sc-border);border-radius:8px;padding:12px 16px;margin-bottom:16px;font-size:13px;display:flex;justify-content:space-between;align-items:center">
                    <span style="color:var(--sc-muted)">Net Balance Due</span>
                    <span style="font-size:16px;font-weight:700;color:#fbbf24" id="payNowBalanceAmt"></span>
                </div>
                <div class="form-group">
                    <label class="form-label">Amount (₹) <span class="req">*</span></label>
                    <input type="number" name="amount" id="payNowAmount" class="form-control" step="0.01" min="0.01" required placeholder="0.00">
                </div>
                <div class="form-group">
                    <label class="form-label">Payment Method <span class="req">*</span></label>
                    <select name="payment_method" class="form-control" required>
                        @foreach(['cash'=>'Cash','card'=>'Card','upi'=>'UPI','cheque'=>'Cheque','other'=>'Other'] as $v => $l)
                        <option value="{{ $v }}">{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Payment Date <span class="req">*</span></label>
                    <input type="date" name="payment_date" class="form-control" value="{{ today()->toDateString() }}" max="{{ today()->toDateString() }}" required>
                </div>
                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-label">Transaction Ref</label>
                        <input type="text" name="transaction_ref" class="form-control" placeholder="Optional">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Notes</label>
                        <input type="text" name="notes" class="form-control" placeholder="Optional">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closePayNowModal()">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Record Payment</button>
            </div>
        </form>
    </div>
</div>
<script>
function openPayNowModal(netBalance) {
    document.getElementById('payNowAmount').value = netBalance.toFixed(2);
    document.getElementById('payNowBalanceAmt').textContent = '₹' + netBalance.toLocaleString('en-IN', {minimumFractionDigits:2, maximumFractionDigits:2});
    document.getElementById('payNowModal').classList.add('open');
}
function closePayNowModal() {
    document.getElementById('payNowModal').classList.remove('open');
}
</script>

{{-- Transfer modal --}}
@if($admission->status === 'admitted')
@php $allWards = \App\Models\Ward::where('is_active', true)->orderBy('name')->get(); @endphp
<div class="modal-overlay" id="transferModal">
    <div class="modal-box" style="max-width:480px">
        <div class="modal-header">
            <h3><i class="fa-solid fa-right-left"></i> Transfer Ward / Bed</h3>
            <button class="modal-close" onclick="closeTransferModal()">&times;</button>
        </div>
        <form method="POST" action="{{ route('admissions.transfer', $admission) }}" id="transferForm">
            @csrf @method('PATCH')
            <div class="modal-body">
                <div class="alert" style="background:var(--sc-bg);border:1px solid var(--sc-border);padding:10px 14px;border-radius:8px;margin-bottom:16px;font-size:13px">
                    <i class="fa-solid fa-bed"></i> Current: <strong>{{ $admission->ward->name }}</strong> — Bed <strong>{{ $admission->bed->bed_number }}</strong>
                </div>
                <div class="form-group">
                    <label class="form-label">New Ward <span class="req">*</span></label>
                    <select name="new_ward_id" class="form-control" required id="transferWard" onchange="loadTransferBeds(this.value)">
                        <option value="">— Select ward —</option>
                        @foreach($allWards as $w)
                        <option value="{{ $w->id }}">{{ $w->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">New Bed <span class="req">*</span></label>
                    <select name="new_bed_id" class="form-control" required id="transferBed" disabled>
                        <option value="">— Select ward first —</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Reason</label>
                    <input type="text" name="reason" class="form-control" placeholder="Optional reason for transfer" maxlength="300">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeTransferModal()">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-right-left"></i> Transfer</button>
            </div>
        </form>
    </div>
</div>
<script>
function openTransferModal()  { document.getElementById('transferModal').classList.add('open'); }
function closeTransferModal() { document.getElementById('transferModal').classList.remove('open'); }
function loadTransferBeds(wardId) {
    const bedSel = document.getElementById('transferBed');
    bedSel.innerHTML = '<option value="">Loading…</option>';
    bedSel.disabled = true;
    if (!wardId) { bedSel.innerHTML = '<option value="">— Select ward first —</option>'; return; }
    fetch(`{{ route('admissions.beds') }}?ward_id=${wardId}`)
        .then(r => r.ok ? r.json() : Promise.reject(r.status))
        .then(beds => {
            const available = beds.filter(b => b.status === 'available');
            if (!available.length) {
                bedSel.innerHTML = '<option value="">No available beds in this ward</option>';
            } else {
                bedSel.innerHTML = '<option value="">— Select bed —</option>' +
                    available.map(b => `<option value="${b.id}">Bed ${b.bed_number} (${b.type})</option>`).join('');
                bedSel.disabled = false;
            }
        })
        .catch(() => { bedSel.innerHTML = '<option value="">Error loading beds</option>'; });
}
</script>
@endif

<script>
function switchTabByName(name) {
    const btn = document.querySelector('.tab-btn[data-tab="' + name + '"]');
    if (btn) {
        btn.click();
        btn.scrollIntoView({behavior:'smooth', block:'nearest'});
    }
}

function openVitalsEdit(id, recorded_at, temp, pulse, bp_sys, bp_dia, rr, spo2, glucose, weight, urine, notes) {
    const base = '{{ route("admissions.vitals.update", [$admission, "__ID__"]) }}';
    document.getElementById('vitalsEditForm').action = base.replace('__ID__', id);
    document.getElementById('ve_recorded_at').value    = recorded_at;
    document.getElementById('ve_temperature').value    = temp || '';
    document.getElementById('ve_pulse').value          = pulse || '';
    document.getElementById('ve_bp_systolic').value    = bp_sys || '';
    document.getElementById('ve_bp_diastolic').value   = bp_dia || '';
    document.getElementById('ve_respiration_rate').value = rr || '';
    document.getElementById('ve_spo2').value           = spo2 || '';
    document.getElementById('ve_blood_glucose').value  = glucose || '';
    document.getElementById('ve_weight').value         = weight || '';
    document.getElementById('ve_urine_output').value   = urine;
    document.getElementById('ve_notes').value          = notes;
    document.getElementById('vitalsEditModal').style.display = 'flex';
}
function closeVitalsEdit() {
    document.getElementById('vitalsEditModal').style.display = 'none';
}

// Restore tab from URL hash
window.addEventListener('DOMContentLoaded', () => {
    const hash = location.hash.replace('#tab-', '');
    if (hash && hash !== location.hash) switchTabByName(hash);

    // Live charge total
    const unitPrice = document.getElementById('unitPrice');
    const qty       = document.getElementById('chargeQty');
    const total     = document.getElementById('chargeTotal');
    if (unitPrice && qty && total) {
        const recalc = () => {
            const amt = (parseFloat(unitPrice.value) || 0) * (parseFloat(qty.value) || 0);
            total.textContent = '₹' + amt.toLocaleString('en-IN', {minimumFractionDigits:2, maximumFractionDigits:2});
        };
        unitPrice.addEventListener('input', recalc);
        qty.addEventListener('input', recalc);
        recalc();
    }

    // Category → Description auto-fill
    const catSel  = document.getElementById('chargeCategory');
    const descBox = document.getElementById('chargeDesc');
    if (catSel && descBox) {
        const fillDesc = () => {
            descBox.value = catSel.options[catSel.selectedIndex].text.trim();
        };
        catSel.addEventListener('change', fillDesc);
        fillDesc(); // pre-fill on page load
    }
});

// Vitals trend chart toggle
function toggleTrend() {
    const card = document.getElementById('trendCard');
    const btn  = document.getElementById('trendToggle');
    if (card.style.display === 'none') {
        card.style.display = 'block';
        btn.innerHTML = '<i class="fa-solid fa-chart-line"></i> Hide Trend Chart';
        if (!window.vitalsChartInited) initVitalsChart();
    } else {
        card.style.display = 'none';
        btn.innerHTML = '<i class="fa-solid fa-chart-line"></i> Show Trend Chart';
    }
}

@php
$vitalsChartData = $vitals->map(function($v) {
    return [
        'label'  => $v->recorded_at->format('d M, h:i A'),
        'pulse'  => $v->pulse,
        'bp_sys' => $v->bp ? (int)explode('/', $v->bp)[0] : null,
        'spo2'   => $v->spo2,
        'temp'   => $v->temperature,
    ];
})->reverse()->values();
@endphp
window.vitalsChartInited = false;
function initVitalsChart() {
    const vitalsData = @json($vitalsChartData);

    const labels = vitalsData.map(v => v.label);
    const ctx = document.getElementById('vitalsChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Pulse (bpm)',
                    data: vitalsData.map(v => v.pulse),
                    borderColor: '#e84c4c', backgroundColor: 'rgba(232,76,76,0.1)',
                    tension: 0.3, pointRadius: 4, spanGaps: true,
                },
                {
                    label: 'BP Systolic (mmHg)',
                    data: vitalsData.map(v => v.bp_sys),
                    borderColor: '#0ab4b4', backgroundColor: 'rgba(10,180,180,0.1)',
                    tension: 0.3, pointRadius: 4, spanGaps: true,
                },
                {
                    label: 'SpO₂ (%)',
                    data: vitalsData.map(v => v.spo2),
                    borderColor: '#27c47a', backgroundColor: 'rgba(39,196,122,0.1)',
                    tension: 0.3, pointRadius: 4, spanGaps: true,
                },
                {
                    label: 'Temp (°F)',
                    data: vitalsData.map(v => v.temp),
                    borderColor: '#f5a623', backgroundColor: 'rgba(245,166,35,0.1)',
                    tension: 0.3, pointRadius: 4, spanGaps: true,
                },
            ],
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'top' } },
            scales: {
                x: { ticks: { maxRotation: 45, font: { size: 10 } } },
                y: { beginAtZero: false },
            },
        },
    });
    window.vitalsChartInited = true;
}
</script>
<script>
function fillPkgPrice(sel) {
    const opt = sel.options[sel.selectedIndex];
    const price = opt ? (opt.dataset.price || '') : '';
    document.getElementById('pkgPrice').value = price;
}


</script>
{{-- ── TAB: NURSING TASKS ── --}}
<div class="tab-pane" id="tab-tasks">
    <div class="ipd-two-col">
        <div>
            {{-- Pending tasks --}}
            @if(isset($nursingTasks) && $nursingTasks->where('completed_at', null)->count())
            <div class="card mb-3">
                <div class="card-header"><i class="fa-solid fa-clock" style="color:var(--sc-amber)"></i> Pending Tasks</div>
                <div class="card-body" style="padding:0">
                    @foreach($nursingTasks->where('completed_at', null) as $task)
                    <div style="display:flex;align-items:center;gap:12px;padding:12px 16px;border-bottom:1px solid var(--sc-border){{ $task->due_at && $task->due_at->isPast() ? ';background:rgba(232,76,76,.04)' : '' }}">
                        <form method="POST" action="{{ route('admissions.tasks.complete', [$admission, $task]) }}" style="flex-shrink:0">
                            @csrf @method('PATCH')
                            <button type="submit" class="btn btn-sm btn-outline" style="width:32px;height:32px;padding:0;border-radius:50%" title="Mark complete">
                                <i class="fa-regular fa-circle"></i>
                            </button>
                        </form>
                        <div style="flex:1">
                            <div style="font-weight:600;font-size:13px">{{ $task->task }}</div>
                            <div style="font-size:11.5px;color:var(--sc-muted);margin-top:2px">
                                Added by {{ $task->createdBy?->name ?? '—' }} · {{ $task->created_at->diffForHumans() }}
                                @if($task->due_at)
                                    · Due: <span style="{{ $task->due_at->isPast() ? 'color:var(--sc-red);font-weight:600' : 'color:var(--sc-amber)' }}">{{ $task->due_at->format('d M Y, h:i A') }}</span>
                                @endif
                            </div>
                        </div>
                        @if($admission->status === 'admitted' || auth()->user()->isAdmin())
                        <form method="POST" action="{{ route('admissions.tasks.destroy', [$admission, $task]) }}" onsubmit="return confirm('Delete task?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline" style="color:var(--sc-red);border-color:var(--sc-red)"><i class="fa-solid fa-trash"></i></button>
                        </form>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Completed tasks --}}
            @if(isset($nursingTasks) && $nursingTasks->where('completed_at', '!=', null)->count())
            <div class="card">
                <div class="card-header"><i class="fa-solid fa-circle-check" style="color:var(--sc-green)"></i> Completed Tasks</div>
                <div class="card-body" style="padding:0">
                    @foreach($nursingTasks->where('completed_at', '!=', null) as $task)
                    <div style="display:flex;align-items:center;gap:12px;padding:10px 16px;border-bottom:1px solid var(--sc-border);opacity:.65">
                        <div style="width:32px;height:32px;border-radius:50%;background:rgba(39,196,122,.12);color:var(--sc-green);display:flex;align-items:center;justify-content:center;flex-shrink:0">
                            <i class="fa-solid fa-check"></i>
                        </div>
                        <div style="flex:1">
                            <div style="font-weight:600;font-size:13px;text-decoration:line-through;color:var(--sc-muted)">{{ $task->task }}</div>
                            <div style="font-size:11px;color:var(--sc-muted)">Completed {{ $task->completed_at->format('d M Y, h:i A') }}</div>
                        </div>
                        <form method="POST" action="{{ route('admissions.tasks.destroy', [$admission, $task]) }}" onsubmit="return confirm('Delete task?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline" style="color:var(--sc-muted)"><i class="fa-solid fa-trash"></i></button>
                        </form>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            @if(!isset($nursingTasks) || $nursingTasks->isEmpty())
            <div class="card"><div class="card-body text-center text-muted" style="padding:40px">No tasks yet. Add a task using the form.</div></div>
            @endif
        </div>

        {{-- Add task form --}}
        @if($admission->status === 'admitted' || auth()->user()->isAdmin())
        <div class="card" style="position:sticky;top:80px">
            <div class="card-header"><i class="fa-solid fa-plus"></i> Add Task</div>
            <div class="card-body">
                <form method="POST" action="{{ route('admissions.tasks.store', $admission) }}">
                    @csrf
                    <div class="form-group">
                        <label class="form-label">Task <span class="req">*</span></label>
                        <textarea name="task" class="form-control" rows="3" required placeholder="e.g. Check BP at 6pm, Change IV line..."></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Due Date &amp; Time</label>
                        <input type="datetime-local" name="due_at" class="form-control" value="{{ old('due_at') }}">
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%">
                        <i class="fa-solid fa-plus"></i> Add Task
                    </button>
                </form>
            </div>
        </div>
        @endif
    </div>
</div>

{{-- ── TAB: DIET CHART ── --}}
<div class="tab-pane" id="tab-diet">
    <div style="display:flex;align-items:center;justify-content:flex-end;margin-bottom:14px;gap:8px">
        <a href="{{ route('diet-prescriptions.create') }}?patient_id={{ $admission->patient_id }}" class="btn btn-primary btn-sm">
            <i class="fa-solid fa-utensils"></i> Create Diet Prescription
        </a>
        <a href="{{ route('diet-prescriptions.index') }}?patient_id={{ $admission->patient_id }}" class="btn btn-outline btn-sm">
            <i class="fa-solid fa-list"></i> View Diet Prescriptions
        </a>
    </div>
    <div class="ipd-two-col">
        <div>
            @php
                $mealIcons = ['morning'=>'fa-sun','afternoon'=>'fa-cloud-sun','evening'=>'fa-moon','night'=>'fa-star'];
                $mealLabels = ['morning'=>'Morning','afternoon'=>'Afternoon','evening'=>'Evening','night'=>'Night'];
            @endphp
            @if(isset($dietCharts) && $dietCharts->count())
                @foreach($dietCharts->groupBy('date') as $date => $meals)
                <div class="card mb-3">
                    <div class="card-header">
                        <i class="fa-solid fa-calendar-day" style="color:var(--sc-teal)"></i>
                        {{ \Carbon\Carbon::parse($date)->format('D, d M Y') }}
                        @if(\Carbon\Carbon::parse($date)->isToday())<span class="badge badge-teal" style="margin-left:8px">Today</span>@endif
                    </div>
                    <div class="card-body" style="padding:0">
                        @foreach($meals as $meal)
                        <div style="display:flex;align-items:flex-start;gap:14px;padding:12px 16px;border-bottom:1px solid var(--sc-border)">
                            <div style="width:36px;height:36px;border-radius:50%;background:var(--sc-teal-glow);color:var(--sc-teal);display:flex;align-items:center;justify-content:center;flex-shrink:0">
                                <i class="fa-solid {{ $mealIcons[$meal->meal_time] ?? 'fa-utensils' }}"></i>
                            </div>
                            <div style="flex:1">
                                <div style="font-weight:600;font-size:12px;text-transform:uppercase;letter-spacing:.5px;color:var(--sc-muted);margin-bottom:3px">{{ $mealLabels[$meal->meal_time] ?? $meal->meal_time }}</div>
                                <div>{{ $meal->instructions }}</div>
                                <div style="font-size:11px;color:var(--sc-muted);margin-top:3px">By {{ $meal->createdBy?->name ?? '—' }}</div>
                            </div>
                            <form method="POST" action="{{ route('admissions.diet.destroy', [$admission, $meal]) }}" onsubmit="return confirm('Delete this diet entry?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline" style="color:var(--sc-red);border-color:var(--sc-red)"><i class="fa-solid fa-trash"></i></button>
                            </form>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endforeach
            @else
            <div class="card"><div class="card-body text-center text-muted" style="padding:40px">No diet chart entries yet.</div></div>
            @endif
        </div>

        {{-- Add diet entry form --}}
        @if($admission->status === 'admitted' || auth()->user()->isAdmin())
        <div class="card" style="position:sticky;top:80px">
            <div class="card-header"><i class="fa-solid fa-plus"></i> Add Diet Entry</div>
            <div class="card-body">
                <form method="POST" action="{{ route('admissions.diet.store', $admission) }}">
                    @csrf
                    <div class="form-group">
                        <label class="form-label">Date <span class="req">*</span></label>
                        <input type="date" name="date" class="form-control" value="{{ old('date', today()->toDateString()) }}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Meal Time <span class="req">*</span></label>
                        <select name="meal_time" class="form-control" required>
                            <option value="morning">Morning</option>
                            <option value="afternoon">Afternoon</option>
                            <option value="evening">Evening</option>
                            <option value="night">Night</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Instructions <span class="req">*</span></label>
                        <textarea name="instructions" class="form-control" rows="4" required placeholder="e.g. Soft diet, No salt, 200ml warm milk..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%">
                        <i class="fa-solid fa-plus"></i> Add Entry
                    </button>
                </form>
            </div>
        </div>
        @endif
    </div>
</div>

{{-- ── TAB: PRE-AUTHORIZATION ── --}}
<div class="tab-pane" id="tab-preauth">
<div class="grid" style="grid-template-columns:1fr 2fr;gap:20px">
    <div class="card">
        <div class="card-header"><i class="fa-solid fa-plus"></i> Request Pre-Authorization</div>
        <div class="card-body">
            <form method="POST" action="{{ route('admissions.preauth.store', $admission) }}">
                @csrf
                <div class="form-group">
                    <label class="form-label">Insurer Name</label>
                    <input type="text" name="insurer_name" class="form-control" required placeholder="e.g. Star Health, ICICI Lombard">
                </div>
                <div class="form-group">
                    <label class="form-label">Auth / TPA Reference No.</label>
                    <input type="text" name="auth_number" class="form-control" placeholder="Optional">
                </div>
                <div class="form-group">
                    <label class="form-label">Request Date</label>
                    <input type="date" name="requested_at" class="form-control" value="{{ today()->toDateString() }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="3" placeholder="Procedure requested, amount, etc."></textarea>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%"><i class="fa-solid fa-paper-plane"></i> Submit Request</button>
            </form>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><i class="fa-solid fa-list"></i> Pre-Authorization Records</div>
        @if($preauths->isEmpty())
        <div class="card-body text-center text-muted" style="padding:32px">No pre-authorization records.</div>
        @else
        @foreach($preauths as $pa)
        @php
            $sc = ['pending'=>'badge-amber','approved'=>'badge-teal','rejected'=>'badge-red','partial'=>'badge-navy'];
        @endphp
        <div style="padding:14px 16px;border-bottom:1px solid var(--sc-border)">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:8px">
                <div>
                    <span style="font-weight:700;font-size:14px">{{ $pa->insurer_name }}</span>
                    @if($pa->auth_number)<span class="mono text-muted" style="font-size:12px;margin-left:8px">{{ $pa->auth_number }}</span>@endif
                    <span class="badge {{ $sc[$pa->status] ?? 'badge-gray' }}" style="margin-left:8px">{{ ucfirst($pa->status) }}</span>
                </div>
                <form method="POST" action="{{ route('admissions.preauth.destroy', [$admission, $pa]) }}" onsubmit="return confirm('Delete?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline" style="color:var(--sc-red);border-color:var(--sc-red)"><i class="fa-solid fa-trash"></i></button>
                </form>
            </div>
            <div style="font-size:12px;color:var(--sc-muted);margin-bottom:8px">
                Requested: {{ $pa->requested_at->format('d M Y') }}
                @if($pa->approved_at) · Responded: {{ $pa->approved_at->format('d M Y') }}@endif
                @if($pa->approved_amount) · Approved: Rs {{ number_format($pa->approved_amount, 2) }}@endif
            </div>
            @if($pa->notes)<div style="font-size:13px;margin-bottom:10px">{{ $pa->notes }}</div>@endif
            <form method="POST" action="{{ route('admissions.preauth.update', [$admission, $pa]) }}" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
                @csrf @method('PATCH')
                <select name="status" class="form-control" style="width:130px">
                    @foreach(['pending','approved','rejected','partial'] as $s)
                    <option value="{{ $s }}" {{ $pa->status===$s?'selected':'' }}>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
                <input type="number" name="approved_amount" class="form-control" style="width:130px" placeholder="Approved Rs" value="{{ $pa->approved_amount }}" step="0.01" min="0">
                <input type="date" name="approved_at" class="form-control" style="width:140px" value="{{ $pa->approved_at?->toDateString() }}">
                <button type="submit" class="btn btn-sm btn-primary">Update</button>
            </form>
        </div>
        @endforeach
        @endif
    </div>
</div>
</div>

{{-- ── TAB: HANDOVER NOTES ── --}}
<div class="tab-pane" id="tab-handover">
<div class="grid" style="grid-template-columns:1fr 2fr;gap:20px">
    <div class="card">
        <div class="card-header"><i class="fa-solid fa-plus"></i> Add Handover Note</div>
        <div class="card-body">
            <form method="POST" action="{{ route('admissions.handover.store', $admission) }}">
                @csrf
                <div class="form-group">
                    <label class="form-label">Shift</label>
                    <select name="shift" class="form-control" required>
                        <option value="morning">Morning</option>
                        <option value="evening">Evening</option>
                        <option value="night">Night</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="6" required placeholder="Patient status, pending orders, concerns..."></textarea>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%"><i class="fa-solid fa-floppy-disk"></i> Save Note</button>
            </form>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><i class="fa-solid fa-list"></i> Handover History</div>
        @if($handoverNotes->isEmpty())
        <div class="card-body text-center text-muted" style="padding:32px">No handover notes recorded.</div>
        @else
        <div class="card-body" style="padding:0">
            @foreach($handoverNotes->groupBy('shift') as $shift => $notes)
            <div style="padding:10px 16px 4px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:var(--sc-muted);border-bottom:1px solid var(--sc-border)">
                <i class="fa-solid fa-circle" style="font-size:8px;margin-right:4px;color:{{ $shift==='morning'?'#f59e0b':($shift==='evening'?'#6366f1':'#334155') }}"></i>{{ ucfirst($shift) }}
            </div>
            @foreach($notes as $hn)
            <div style="padding:12px 16px;border-bottom:1px solid var(--sc-border)">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:6px">
                    <div>
                        <span style="font-weight:600;font-size:13px">{{ $hn->doctor->full_name ?? $hn->doctor->name }}</span>
                        <span class="text-muted" style="font-size:11px;margin-left:8px">{{ $hn->created_at->format('d M Y H:i') }}</span>
                    </div>
                    <form method="POST" action="{{ route('admissions.handover.destroy', [$admission, $hn]) }}" onsubmit="return confirm('Delete this note?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline" style="color:var(--sc-red);border-color:var(--sc-red)"><i class="fa-solid fa-trash"></i></button>
                    </form>
                </div>
                <div style="font-size:13px;white-space:pre-wrap;line-height:1.6">{{ $hn->notes }}</div>
            </div>
            @endforeach
            @endforeach
        </div>
        @endif
    </div>
</div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endpush
@endsection
