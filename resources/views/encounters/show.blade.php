{{-- FILE: resources/views/encounters/show.blade.php --}}
@extends('layouts.app')
@section('title', 'Encounter '.$encounter->encounter_no)
@section('page-title', 'Clinical Visit')
@section('patient-context')
@include('partials._patient_ctx', ['patient' => $encounter->patient, 'active' => 'encounter'])
@endsection
@section('content')

<div class="page-header" style="flex-wrap:wrap;gap:8px;align-items:center">
    <div class="page-header-left" style="flex:1;min-width:200px">
        <h1>Clinical Visit — <span class="mono">{{ $encounter->encounter_no }}</span></h1>
        <div class="breadcrumb">
            <a href="{{ route('patients.show', $encounter->patient) }}">{{ $encounter->patient->full_name }}</a>
             › Visit on {{ $encounter->encounter_date->format('d M Y') }}
        </div>
    </div>
    {{-- OPD nav --}}
    <a href="{{ route('opd.index') }}"    class="btn btn-sm" style="background:#0369a1;color:#fff;border-color:#0369a1;white-space:nowrap"><i class="fa-solid fa-users-line"></i> Reception Queue</a>
    <a href="{{ route('opd.doctor') }}"   class="btn btn-sm" style="background:#15803d;color:#fff;border-color:#15803d;white-space:nowrap"><i class="fa-solid fa-stethoscope"></i> Doctor View</a>
    <a href="{{ route('opd.board') }}"    class="btn btn-sm" style="background:#6d28d9;color:#fff;border-color:#6d28d9;white-space:nowrap"><i class="fa-solid fa-display"></i> Queue Board</a>
    <a href="{{ route('opd.register') }}" class="btn btn-sm" style="background:#334155;color:#fff;border-color:#334155;white-space:nowrap"><i class="fa-solid fa-book-open"></i> Daily Register</a>
    <a href="{{ route('glucose.index', $encounter->patient) }}" class="btn btn-sm" style="background:#0891b2;color:#fff;border-color:#0891b2;white-space:nowrap"><i class="fa-solid fa-droplet"></i> Diabetes Diary</a>
    <a href="{{ route('bp.index', $encounter->patient) }}"      class="btn btn-sm" style="background:#dc2626;color:#fff;border-color:#dc2626;white-space:nowrap"><i class="fa-solid fa-heart-pulse"></i> BP Diary</a>
    <a href="{{ route('health.index', $encounter->patient) }}"  class="btn btn-sm" style="background:#059669;color:#fff;border-color:#059669;white-space:nowrap"><i class="fa-solid fa-heart"></i> Health Tracker</a>
    {{-- Visit actions --}}
    @if($encounter->status === 'Open')
    <a href="{{ route('admissions.create', ['patient_id' => $encounter->patient_id, 'doctor_id' => $encounter->doctor_id]) }}" class="btn btn-sm btn-amber">
        <i class="fa-solid fa-bed-pulse"></i> Admit
    </a>
    <form method="POST" action="{{ route('encounters.complete', $encounter) }}" style="display:inline">
        @csrf
        <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Mark this encounter as complete?')">
            <i class="fa-solid fa-circle-check"></i> Complete
        </button>
    </form>
    @endif
    @if($encounter->bill)
        <a href="{{ route('billing.show', $encounter->bill) }}" class="btn btn-sm btn-amber">
            <i class="fa-solid fa-file-invoice"></i> View Bill
        </a>
    @else
        <button type="button" class="btn btn-sm btn-amber" onclick="openQuickBill()">
            <i class="fa-solid fa-file-invoice-dollar"></i> Quick Bill
        </button>
        <a href="{{ route('billing.create', ['patient_id' => $encounter->patient_id, 'encounter_id' => $encounter->id]) }}" class="btn btn-sm btn-outline" title="Full billing form">
            <i class="fa-solid fa-arrow-up-right-from-square"></i>
        </a>
    @endif
    @php
        $followUpQuery = array_filter([
            'patient_id'      => $encounter->patient_id,
            'doctor_id'       => $encounter->doctor_id,
            'type'            => 'Follow-up',
            'date'            => $encounter->follow_up_date
                                    ? ($encounter->follow_up_date->isPast() ? today()->toDateString() : $encounter->follow_up_date->toDateString())
                                    : null,
            'chief_complaint' => $encounter->follow_up_instructions ?: null,
            'from_encounter'  => $encounter->id,
        ]);
    @endphp
    <a href="{{ route('appointments.create', $followUpQuery) }}" class="btn btn-sm btn-primary" title="Book a follow-up appointment for this patient">
        <i class="fa-solid fa-calendar-plus"></i> Follow-up
    </a>
    @unless($encounter->trashed())
    <button type="button" class="btn btn-sm" style="background:#7c3aed;color:#fff;border-color:#7c3aed;white-space:nowrap" title="Refer to another department or doctor"
            onclick="document.getElementById('refer-modal').classList.add('open');refFilterDoctors()">
        <i class="fa-solid fa-share-from-square"></i> Refer
    </button>
    @endunless
    <span class="badge {{ $encounter->status === 'Completed' ? 'badge-green' : 'badge-amber' }}" style="font-size:12px;padding:6px 12px">{{ $encounter->status }}</span>
    @if(!$encounter->trashed() && auth()->user()?->canDeleteVisits())
    <button type="button" class="btn btn-sm btn-outline" style="color:var(--sc-red);border-color:var(--sc-red);white-space:nowrap"
            onclick="document.getElementById('delete-visit-modal').classList.add('open')" title="Delete this visit">
        <i class="fa-solid fa-trash"></i> Delete Visit
    </button>
    @endif
</div>

@include('encounters._referral')

@if($encounter->trashed())
{{-- Deleted visit: owner-only view --}}
<div class="alert alert-keep" style="background:#fef2f2;border:2px solid var(--sc-red);color:#7f1d1d;border-radius:8px;padding:14px 20px;margin-bottom:16px;display:flex;gap:16px;align-items:center;flex-wrap:wrap">
    <div><i class="fa-solid fa-trash" style="font-size:1.5rem;color:var(--sc-red)"></i></div>
    <div style="flex:1;min-width:220px">
        <div class="fw-700" style="font-size:15px">DELETED VISIT: hidden from all lists and reports</div>
        <div style="font-size:13px;margin-top:2px">
            Deleted by {{ $encounter->deletedBy?->name ?? 'unknown' }} on {{ $encounter->deleted_at->timezone('Asia/Kolkata')->format('d M Y, h:i A') }}
            @if($encounter->delete_reason) &middot; Reason: <b>{{ $encounter->delete_reason }}</b>@endif
        </div>
        <div style="font-size:12px;margin-top:2px">Changes cannot be saved on a deleted visit. Restore it first to edit.</div>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
        <form method="POST" action="{{ route('encounters.restore', $encounter) }}" onsubmit="return confirm('Restore visit {{ $encounter->encounter_no }}?')">
            @csrf
            <button type="submit" class="btn btn-sm btn-success"><i class="fa-solid fa-rotate-left"></i> Restore</button>
        </form>
        <button type="button" class="btn btn-sm" style="background:var(--sc-red);border-color:var(--sc-red);color:#fff"
                onclick="document.getElementById('purge-visit-modal').classList.add('open')">
            <i class="fa-solid fa-trash-can"></i> Delete permanently
        </button>
        <a href="{{ route('encounters.deleted') }}" class="btn btn-sm btn-outline"><i class="fa-solid fa-list"></i> Deleted Visits</a>
    </div>
</div>

{{-- Permanent delete modal (owner only, deleted visits only) --}}
<div id="purge-visit-modal" class="modal-overlay">
    <div class="modal-box" style="max-width:520px">
        <div class="modal-header">
            <i class="fa-solid fa-trash-can" style="color:var(--sc-red)"></i>
            <h3>Permanently delete {{ $encounter->encounter_no }}</h3>
            <button class="modal-close" onclick="document.getElementById('purge-visit-modal').classList.remove('open')">&times;</button>
        </div>
        <form method="POST" action="{{ route('encounters.force-destroy', $encounter) }}"
              onsubmit="return confirm('PERMANENTLY delete visit {{ $encounter->encounter_no }} of {{ addslashes($encounter->patient->full_name) }}?\n\nIt can no longer be restored.')">
            @csrf @method('DELETE')
            <div class="modal-body" style="display:flex;flex-direction:column;gap:12px;font-size:13px">
                <div>The visit and its diagnoses, prescriptions, notes and uploaded reports are erased for good. Vitals readings stay in the patient's history.</div>
                @if($encounter->appointment || $encounter->opd_token_id)
                <div style="display:flex;flex-direction:column;gap:6px">
                    @if($encounter->appointment)
                    <label style="display:flex;gap:8px;align-items:center;cursor:pointer">
                        <input type="checkbox" name="delete_appointment" value="1" checked>
                        Also delete appointment {{ $encounter->appointment->appointment_no }} this visit came from
                    </label>
                    @endif
                    @if($encounter->opd_token_id)
                    <label style="display:flex;gap:8px;align-items:center;cursor:pointer">
                        <input type="checkbox" name="delete_token" value="1" checked>
                        Also delete its OPD token
                    </label>
                    @endif
                </div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('purge-visit-modal').classList.remove('open')">Cancel</button>
                <button type="submit" class="btn" style="background:var(--sc-red);border-color:var(--sc-red);color:#fff"><i class="fa-solid fa-trash-can"></i> Delete permanently</button>
            </div>
        </form>
    </div>
</div>
@elseif(auth()->user()?->canDeleteVisits())
@php
    $delBlockers = \App\Http\Controllers\EncounterController::deleteBlockers($encounter);
    $delFollowUp = \App\Http\Controllers\EncounterController::autoFollowUpOf($encounter);
    $delSourceAppt = \App\Http\Controllers\EncounterController::sourceAppointmentOf($encounter);
@endphp
{{-- Delete Visit Modal --}}
<div id="delete-visit-modal" class="modal-overlay">
    <div class="modal-box" style="max-width:520px">
        <div class="modal-header">
            <i class="fa-solid fa-trash" style="color:var(--sc-red)"></i>
            <h3>Delete Visit {{ $encounter->encounter_no }}</h3>
            <button class="modal-close" onclick="document.getElementById('delete-visit-modal').classList.remove('open')">&times;</button>
        </div>
        <form method="POST" action="{{ route('encounters.destroy', $encounter) }}"
              onsubmit="return confirm('Delete visit {{ $encounter->encounter_no }} of {{ addslashes($encounter->patient->full_name) }}?')">
            @csrf @method('DELETE')
            <div class="modal-body" style="display:flex;flex-direction:column;gap:12px;font-size:13px">
                <div>{{ $encounter->patient->full_name }} &middot; {{ $encounter->encounter_date->format('d M Y') }} &middot; {{ $encounter->status }}</div>
                @if($delBlockers)
                    <div class="alert alert-danger" style="margin:0">
                        <i class="fa-solid fa-circle-xmark"></i>
                        <div>This visit has {{ implode(', ', $delBlockers) }}. Delete or move {{ count($delBlockers) > 1 ? 'them' : 'it' }} first, then delete the visit.</div>
                    </div>
                @else
                    <div>The visit, with its notes, diagnoses and prescriptions, disappears from all lists and reports. The clinic owner can still see and restore it.</div>
                    <div class="form-group" style="margin:0">
                        <label class="form-label">Reason <span class="req">*</span></label>
                        <input type="text" name="reason" class="form-control" required maxlength="255" placeholder="e.g. duplicate visit, wrong patient">
                    </div>
                    @if($delSourceAppt)
                    <label style="display:flex;gap:8px;align-items:center;cursor:pointer">
                        <input type="checkbox" name="cancel_appointment" value="1" checked>
                        Also remove appointment {{ $delSourceAppt->appointment_no }} ({{ $delSourceAppt->appointment_date->format('d M Y') }}, {{ $delSourceAppt->status }}) from the appointments list
                    </label>
                    @endif
                    @if($delFollowUp)
                    <label style="display:flex;gap:8px;align-items:center;cursor:pointer">
                        <input type="checkbox" name="cancel_follow_up" value="1" checked>
                        Cancel follow-up {{ $delFollowUp->appointment_no }} booked for {{ $delFollowUp->appointment_date->format('d M Y') }}
                    </label>
                    @endif
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('delete-visit-modal').classList.remove('open')">Cancel</button>
                @unless($delBlockers)
                <button type="submit" class="btn" style="background:var(--sc-red);border-color:var(--sc-red);color:#fff"><i class="fa-solid fa-trash"></i> Delete Visit</button>
                @endunless
            </div>
        </form>
    </div>
</div>
@endif

@if(session('encounter_completed'))
<div class="alert alert-keep" style="background:#f0fdf4;border:1px solid #86efac;color:#14532d;border-radius:8px;padding:14px 20px;margin-bottom:16px;display:flex;gap:16px;align-items:center;flex-wrap:wrap">
    <div><i class="fa-solid fa-circle-check" style="font-size:1.5rem"></i></div>
    <div style="flex:1">
        <div class="fw-700" style="font-size:15px">Visit completed</div>
        <div style="font-size:13px;margin-top:2px">What would you like to do next?</div>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
        <a href="{{ route('prescriptions.print', $encounter->id) }}" class="btn btn-outline btn-sm" target="_blank">
            <i class="fa-solid fa-print"></i> Print Prescription
        </a>
        @if(!$encounter->bill)
        <button type="button" class="btn btn-amber btn-sm" onclick="openQuickBill()">
            <i class="fa-solid fa-file-invoice-dollar"></i> Quick Bill
        </button>
        @endif
        <a href="{{ route('appointments.create', ['patient_id' => $encounter->patient_id]) }}" class="btn btn-primary btn-sm">
            <i class="fa-solid fa-calendar-plus"></i> Book Follow-up
        </a>
        <a href="{{ route('patients.show', $encounter->patient) }}" class="btn btn-outline btn-sm">
            <i class="fa-solid fa-user"></i> Patient Profile
        </a>
    </div>
</div>
@endif

{{-- Allergy Alert --}}
@if($patientAllergyRecords->isNotEmpty())
<div style="background:#fef2f2;border:1px solid #fca5a5;border-radius:8px;padding:12px 18px;margin-bottom:16px;display:flex;align-items:center;gap:12px">
    <i class="fa-solid fa-triangle-exclamation" style="font-size:1.4rem;color:#dc2626;flex-shrink:0"></i>
    <div>
        <span style="font-weight:700;color:#991b1b;font-size:13px;text-transform:uppercase;letter-spacing:.5px">ALLERGY ALERT</span>
        @foreach($patientAllergyRecords as $pa)
        <span style="display:inline-block;margin-left:8px;padding:2px 10px;border-radius:12px;font-size:12px;font-weight:600;
                     background:{{ $pa->severity==='severe'?'#fef2f2':($pa->severity==='moderate'?'#fffbeb':'#f0fdf4') }};
                     color:{{ $pa->severity==='severe'?'#991b1b':($pa->severity==='moderate'?'#92400e':'#166534') }};
                     border:1px solid {{ $pa->severity==='severe'?'#fca5a5':($pa->severity==='moderate'?'#fcd34d':'#86efac') }}">
            {{ $pa->allergen }}@if($pa->severity !== 'unknown') <span style="opacity:.7">({{ $pa->severity }})</span>@endif
        </span>
        @endforeach
    </div>
</div>
@endif

<!-- Patient Strip (sticky — keeps allergies/age in view while scrolling tabs) -->
<div class="patient-strip sticky" style="margin-bottom:20px">
    <div class="ps-avatar">
        @if($encounter->patient->photo)
            <img src="{{ asset('storage/'.$encounter->patient->photo) }}" style="width:100%;height:100%;object-fit:cover;border-radius:50%">
        @else
            {{ strtoupper(substr($encounter->patient->first_name,0,1)) }}
        @endif
    </div>
    <div>
        <div class="ps-name">{{ $encounter->patient->full_name }}</div>
        <div class="ps-jhcid">{{ $encounter->patient->jhcid }}</div>
        <div class="ps-meta">
            <span><i class="fa-solid fa-calendar"></i> {{ $encounter->patient->age }}</span>
            <span><i class="fa-solid fa-venus-mars"></i> {{ $encounter->patient->gender }}</span>
            @if($encounter->patient->blood_group)<span><i class="fa-solid fa-droplet"></i> {{ $encounter->patient->blood_group }}</span>@endif
            <span><i class="fa-solid fa-user-doctor"></i> {{ $encounter->doctor->full_name }}</span>
            <span class="badge badge-teal" style="background:rgba(10,180,180,.2)">{{ $encounter->visit_type }}</span>
        </div>
    </div>
    @if($encounter->allergies)
    <div style="background:rgba(232,76,76,.2);border:1px solid rgba(232,76,76,.4);border-radius:8px;padding:8px 14px;color:#fff">
        <i class="fa-solid fa-triangle-exclamation"></i> <strong>Allergies:</strong> {{ $encounter->allergies }}
    </div>
    @endif
</div>

<!-- VITALS ROW -->
<div class="card mb-3" id="vitals-card">
    <div class="card-header">
        <i class="fa-solid fa-heart-pulse"></i> Vital Signs
        <div class="card-actions">
            <button class="btn btn-sm btn-outline" onclick="document.getElementById('vitals-form').classList.toggle('hidden')">
                <i class="fa-solid fa-pen"></i> Edit Vitals
            </button>
        </div>
    </div>
    <div class="card-body">
        <div style="display:grid;grid-template-columns:repeat(8,1fr);gap:12px;margin-bottom:12px">
            @php $vitals = [
                ['BP', ($encounter->bp_systolic && $encounter->bp_diastolic) ? $encounter->bp_systolic.'/'.$encounter->bp_diastolic : '—', 'mmHg'],
                ['Pulse', $encounter->pulse ?? '—', '/min'],
                ['Temp', $encounter->temperature ?? '—', '°C'],
                ['SpO₂', $encounter->spo2 ? $encounter->spo2.'%' : '—', ''],
                ['Weight', $encounter->weight ?? '—', 'kg'],
                ['Height', $encounter->height ?? '—', 'cm'],
                ['BMI', $encounter->bmi ?? '—', 'kg/m²'],
                ['RBS', $encounter->blood_glucose ?? '—', 'mg/dL'],
            ]; @endphp
            @foreach($vitals as [$lbl,$val,$unit])
            <div class="vital-box">
                <div class="vb-val">{{ $val }}</div>
                <div class="vb-unit">{{ $unit }}</div>
                <div class="vb-label">{{ $lbl }}</div>
            </div>
            @endforeach
        </div>

        <!-- Vitals Edit Form -->
        <form id="vitals-form" class="hidden" onsubmit="saveVitals(event)">
            <div class="form-row" style="grid-template-columns:repeat(4,1fr)">
                <div class="form-group">
                    <label class="form-label">BP Systolic <span style="color:var(--sc-muted);font-weight:400">(mmHg)</span></label>
                    <select name="bp_systolic" class="form-control" id="v-bps">
                        <option value="">Select</option>
                        @php $bpSys = array_merge(range(70,200,5)); @endphp
                        @foreach($bpSys as $v)
                            <option value="{{ $v }}" {{ $encounter->bp_systolic == $v ? 'selected' : ($encounter->bp_systolic ? '' : ($v == 120 ? 'selected' : '')) }}>{{ $v }} mmHg{{ $v==120 ? ' (Normal)' : ($v==90?' (Low)':($v==140?' (Stage 1 HTN)':($v==160?' (Stage 2 HTN)':''))) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">BP Diastolic <span style="color:var(--sc-muted);font-weight:400">(mmHg)</span></label>
                    <select name="bp_diastolic" class="form-control" id="v-bpd">
                        <option value="">Select</option>
                        @php $bpDia = array_merge(range(40,130,5)); @endphp
                        @foreach($bpDia as $v)
                            <option value="{{ $v }}" {{ $encounter->bp_diastolic == $v ? 'selected' : ($encounter->bp_diastolic ? '' : ($v == 80 ? 'selected' : '')) }}>{{ $v }} mmHg{{ $v==80 ? ' (Normal)' : ($v==60?' (Low)':($v==90?' (Stage 1 HTN)':($v==100?' (Stage 2 HTN)':''))) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Pulse <span style="color:var(--sc-muted);font-weight:400">(/min)</span></label>
                    <select name="pulse" class="form-control" id="v-pulse">
                        <option value="">Select</option>
                        @foreach(range(30,200,2) as $v)
                            <option value="{{ $v }}" {{ $encounter->pulse == $v ? 'selected' : ($encounter->pulse ? '' : ($v == 72 ? 'selected' : '')) }}>{{ $v }}/min{{ $v==72 ? ' (Normal)' : ($v==60?' (Bradycardia threshold)':($v==100?' (Tachycardia threshold)':'')) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Temperature <span style="color:var(--sc-muted);font-weight:400">(°F)</span></label>
                    <select name="temperature" class="form-control">
                        <option value="">Select</option>
                        @foreach([95.0,96.0,96.8,97.0,97.5,98.0,98.6,99.0,99.5,100.0,100.4,101.0,101.5,102.0,102.5,103.0,103.5,104.0,104.5,105.0,106.0,107.0] as $v)
                            <option value="{{ $v }}" {{ $encounter->temperature == $v ? 'selected' : ($encounter->temperature ? '' : ($v == 98.6 ? 'selected' : '')) }}>{{ $v }}°F{{ $v==98.6 ? ' (Normal)' : ($v==96.8?' (Low normal)':($v==99.0?' (Low-grade fever)':($v==100.4?' (Fever threshold)':($v==102.0?' (Moderate fever)':($v==104.0?' (High fever)':($v==106.0?' (Very high fever)':($v==107.0?' (Hyperpyrexia)':''))))))) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">SpO₂ <span style="color:var(--sc-muted);font-weight:400">(%)</span></label>
                    <select name="spo2" class="form-control" id="v-spo2">
                        <option value="">Select</option>
                        @foreach(range(70,100,1) as $v)
                            <option value="{{ $v }}" {{ $encounter->spo2 == $v ? 'selected' : ($encounter->spo2 ? '' : ($v == 99 ? 'selected' : '')) }}>{{ $v }}%{{ $v==99||$v==100 ? ' (Normal)' : ($v==98?' (Normal)':($v==95?' (Low normal)':($v==94?' (Borderline)':($v==90?' (Hypoxia)':($v==85?' (Severe Hypoxia)':($v<85?' (Critical)':'')))))) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Respiratory Rate <span style="color:var(--sc-muted);font-weight:400">(/min)</span></label>
                    <select name="respiratory_rate" class="form-control">
                        <option value="">Select</option>
                        @foreach(range(8,40,1) as $v)
                            <option value="{{ $v }}" {{ $encounter->respiratory_rate == $v ? 'selected' : ($encounter->respiratory_rate ? '' : ($v == 16 ? 'selected' : '')) }}>{{ $v }}/min{{ $v==16 ? ' (Normal)' : ($v==12?' (Bradypnoea threshold)':($v==20?' (Tachypnoea threshold)':($v==30?' (Severe tachypnoea)':''))) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Weight <span style="color:var(--sc-muted);font-weight:400">(kg)</span></label>
                    <input type="number" name="weight" id="v-weight" class="form-control" value="{{ $encounter->weight }}" step="0.1" placeholder="kg" oninput="calcBMI()">
                </div>
                <div class="form-group">
                    <label class="form-label">Height <span style="color:var(--sc-muted);font-weight:400">(cm)</span></label>
                    <input type="number" name="height" id="v-height" class="form-control" value="{{ $encounter->height }}" step="0.1" placeholder="cm" oninput="calcBMI()">
                </div>
                <div class="form-group">
                    <label class="form-label">Blood Glucose <span style="color:var(--sc-muted);font-weight:400">(mg/dL)</span></label>
                    <select name="blood_glucose" class="form-control">
                        <option value="">Select</option>
                        @foreach(array_merge(range(40,400,5)) as $v)
                            @php
                                $bgSelected = $encounter->blood_glucose == $v ? 'selected' : ($encounter->blood_glucose ? '' : ($v == 100 ? 'selected' : ''));
                                $bgLabel = $v==70 ? ' (Hypoglycaemia)' : ($v==100 ? ' (Normal fasting)' : ($v==140 ? ' (Post-meal limit)' : ($v==200 ? ' (Diabetes threshold)' : ($v==300 ? ' (Very High)' : ($v==400 ? ' (Critical)' : '')))));
                            @endphp
                            <option value="{{ $v }}" {{ $bgSelected }}>{{ $v }} mg/dL{{ $bgLabel }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div style="display:flex;gap:10px;align-items:center;margin-top:4px">
                <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-save"></i> Save Vitals</button>
                <button type="button" class="btn btn-outline btn-sm" onclick="document.getElementById('vitals-form').classList.add('hidden')">Cancel</button>
                <span id="bmi-display" style="align-self:center;font-size:12px;color:var(--sc-muted)"></span>
                <button type="button" class="btn btn-outline btn-sm" onclick="resetVitalsNormal()" style="margin-left:auto"><i class="fa-solid fa-rotate-left"></i> Reset to Normal</button>
            </div>
        </form>
    </div>
</div>

<!-- CLINICAL REMINDERS (CDS) POPUP -->
<div id="cds-popup" class="modal-overlay">
    <div class="modal-box" style="max-width:560px">
        <div class="modal-header" style="gap:10px">
            <i class="fa-solid fa-bell" style="color:#f59e0b;font-size:18px"></i>
            <h3 style="margin:0">Clinical Reminders</h3>
            <span id="cds-popup-count" class="badge" style="background:#f59e0b;color:#fff"></span>
            <button type="button" class="modal-close" onclick="cdsClosePopup()">&times;</button>
        </div>
        <div class="modal-body" style="padding:14px 20px">
            <div style="font-size:11.5px;color:var(--sc-muted);margin-bottom:10px">
                Based on this patient's age/sex, vitals and what you've charted. Advisory only — use clinical judgement.
            </div>
            <div id="cds-popup-list"></div>
        </div>
        <div style="padding:12px 20px;border-top:1px solid var(--sc-border);display:flex;justify-content:flex-end;gap:8px">
            <button type="button" class="btn btn-primary btn-sm" onclick="cdsClosePopup()"><i class="fa-solid fa-check"></i> Got it</button>
        </div>
    </div>
</div>

<!-- MAIN CLINICAL TABS -->
<div class="tab-container" id="enc-tabs">
    <div class="tab-nav">
        <button class="tab-btn active" data-tab="clinical"><i class="fa-solid fa-notes-medical"></i> Clinical Notes</button>
        @php
            $specForms = \App\Services\OpdForms::all();
            $specShown = \App\Services\OpdForms::forEncounter($encounter);
            $specSaved = $encounter->forms->keyBy('form');
        @endphp
        @foreach($specForms as $code => $def)
        <button class="tab-btn spec-tab" data-tab="form-{{ $code }}" @unless(in_array($code, $specShown)) style="display:none" @endunless>
            <i class="fa-solid {{ $def['icon'] }}"></i> {{ $def['short'] }}
            @if($specSaved->has($code))<span class="badge badge-teal" style="font-size:10px">{{ count($specSaved[$code]->data ?? []) }}</span>@endif
        </button>
        @endforeach
        <button class="tab-btn" data-tab="diagnosis"><i class="fa-solid fa-diagnoses"></i> Diagnosis</button>
        <button class="tab-btn" data-tab="rx"><i class="fa-solid fa-prescription"></i> Prescriptions
            @php $rxCount = $encounter->prescriptions->count(); @endphp
            @if($rxCount > 0)<span class="badge badge-teal" style="font-size:10px">{{ $rxCount }}</span>@endif
        </button>
        <button class="tab-btn" data-tab="labs"><i class="fa-solid fa-flask-vial"></i> Lab Orders</button>
        <button class="tab-btn" data-tab="reports"><i class="fa-solid fa-file-arrow-up"></i> Reports
            @php $totalDocsCount = $encounter->reports->count() + $patientEcgRecords->count() + $patientEchoRecords->count(); @endphp
            @if($totalDocsCount > 0)<span class="badge badge-teal" style="font-size:10px">{{ $totalDocsCount }}</span>@endif
        </button>
        <a href="{{ route('ecg.index') }}?patient_id={{ $encounter->patient_id }}" class="tab-btn" style="text-decoration:none"><i class="fa-solid fa-wave-square"></i> ECG
            @php $ptEcgCount = \App\Models\EcgRecord::where('patient_id',$encounter->patient_id)->count(); @endphp
            @if($ptEcgCount)<span class="badge badge-navy" style="font-size:10px">{{ $ptEcgCount }}</span>@endif
        </a>
        <a href="{{ route('echo.index') }}?patient_id={{ $encounter->patient_id }}" class="tab-btn" style="text-decoration:none"><i class="fa-solid fa-heart-circle-check"></i> Echo
            @php $ptEchoCount = \App\Models\EchoRecord::where('patient_id',$encounter->patient_id)->count(); @endphp
            @if($ptEchoCount)<span class="badge badge-navy" style="font-size:10px">{{ $ptEchoCount }}</span>@endif
        </a>
        <button class="tab-btn" data-tab="notes"><i class="fa-solid fa-note-sticky"></i> Notes
            @if($encounter->encounterNotes->count())<span class="badge badge-teal" style="font-size:10px">{{ $encounter->encounterNotes->count() }}</span>@endif
        </button>
        @if(count($specShown) < count($specForms) && !$encounter->trashed())
        <select id="spec-add" class="form-control" style="width:auto;height:30px;padding:2px 8px;font-size:12px;margin:auto 0 auto 8px" onchange="addSpecForm(this)" title="Open another department's visit form">
            <option value="">+ Speciality form</option>
            @foreach($specForms as $code => $def)@unless(in_array($code, $specShown))<option value="{{ $code }}">{{ $def['title'] }}</option>@endunless @endforeach
        </select>
        @endif
    </div>

    <!-- CLINICAL NOTES TAB -->
    <div id="tab-clinical" class="tab-pane active">
        <!-- Clinical reminders (CDS) live panel -->
        <div id="cds-panel" class="card mb-3" style="display:none;border-left:4px solid #f59e0b">
            <div class="card-header" style="cursor:pointer;gap:8px" onclick="document.getElementById('cds-panel-body').classList.toggle('hidden')">
                <i class="fa-solid fa-bell" style="color:#f59e0b"></i>
                Clinical Reminders
                <span id="cds-panel-count" class="badge" style="background:#f59e0b;color:#fff"></span>
                <span style="margin-left:auto;display:flex;gap:10px;align-items:center">
                    <button type="button" class="btn btn-sm btn-outline" style="font-size:11px;padding:3px 8px" onclick="event.stopPropagation();cdsOpenPopup()"><i class="fa-regular fa-window-restore"></i> Popup</button>
                    <i class="fa-solid fa-chevron-down" style="font-size:11px"></i>
                </span>
            </div>
            <div id="cds-panel-body" class="card-body" style="padding:10px 14px">
                <div id="cds-panel-list"></div>
            </div>
        </div>
        <div class="card">
            <div class="card-header">
                <i class="fa-solid fa-notes-medical"></i> Clinical History & Examination
                <div class="card-actions">
                    <button type="button" class="btn btn-sm btn-outline" onclick="openLastVisitModal()" title="Pull SOAP, diagnoses and Rx from this patient's previous encounter">
                        <i class="fa-solid fa-clone"></i> Copy from Last Visit
                    </button>
                    <button type="button" class="btn btn-sm btn-primary" onclick="openEncTemplateModal()">
                        <i class="fa-solid fa-layer-group"></i> Apply Template
                    </button>
                    <div style="position:relative;display:inline-block">
                        <button type="button" class="btn btn-sm btn-outline" onclick="toggleClinicalPrintMenu(event)" title="Print all or some of these sections">
                            <i class="fa-solid fa-print"></i> Print <i class="fa-solid fa-caret-down" style="font-size:10px"></i>
                        </button>
                        <div id="clinical-print-menu" class="ch-print-menu" style="display:none">
                            @php $ptVisitCount = \App\Models\Encounter::where('patient_id', $encounter->patient_id)->count(); @endphp
                            <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--sc-muted);margin-bottom:2px">Print</div>
                            <label><input type="radio" name="cp-scope" value="this" checked> This visit only</label>
                            <label><input type="radio" name="cp-scope" value="all"> All visits, date-wise ({{ $ptVisitCount }})</label>
                            <label style="padding-left:22px;font-size:12px;color:var(--sc-muted)">
                                <select id="cp-order" style="border:1px solid var(--sc-border);border-radius:5px;padding:2px 4px;font-size:12px">
                                    <option value="asc">Oldest first</option>
                                    <option value="desc">Newest first</option>
                                </select>
                            </label>
                            <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--sc-muted);margin:8px 0 2px;border-top:1px solid var(--sc-border);padding-top:8px">Sections</div>
                            <label style="font-weight:700;border-bottom:1px solid var(--sc-border);padding-bottom:6px;margin-bottom:4px">
                                <input type="checkbox" id="cp-all" checked onchange="document.querySelectorAll('.cp-sec').forEach(c => c.checked = this.checked)"> All sections
                            </label>
                            @foreach(\App\Http\Controllers\EncounterController::CLINICAL_PRINT_SECTIONS as $code => $label)
                            <label><input type="checkbox" class="cp-sec" value="{{ $code }}" checked onchange="document.getElementById('cp-all').checked = [...document.querySelectorAll('.cp-sec')].every(c => c.checked)"> {{ $label }}</label>
                            @endforeach
                            <button type="button" class="btn btn-sm btn-primary" style="margin-top:8px;width:100%" onclick="printClinicalSections()"><i class="fa-solid fa-print"></i> Print selected</button>
                        </div>
                    </div>
                </div>
            </div>
            <div id="clinical-draft-banner" style="display:none;padding:10px 16px;background:rgba(245,166,35,.1);border-bottom:1px solid var(--sc-border);font-size:12.5px;display:none;align-items:center;gap:12px">
                <i class="fa-solid fa-rotate-left" style="color:var(--sc-amber);font-size:15px"></i>
                <div style="flex:1">
                    <strong>Unsaved draft from <span id="clinical-draft-when">—</span></strong>
                    — your tab closed before this was saved. Restore it?
                </div>
                <button type="button" class="btn btn-sm btn-primary" onclick="restoreClinicalDraft()">Restore</button>
                <button type="button" class="btn btn-sm btn-outline" onclick="discardClinicalDraft()">Discard</button>
            </div>
            <div class="card-body">
                <form onsubmit="saveClinical(event)">

                    {{-- Topics stacked one below the other; each title is a link that opens its form. --}}
                    <div class="ch-topic-tools">
                        <a href="#" onclick="return setAllChTopics(true)">Open all</a> · <a href="#" onclick="return setAllChTopics(false)">Close all</a>
                    </div>
                    <div class="ch-group"><i class="fa-solid fa-clock-rotate-left" style="color:var(--sc-teal)"></i> History</div>
                    <div class="form-group ch-topic" data-topic="chief_complaint">
                        <a href="#" class="form-label ch-topic-link" onclick="return toggleChTopic(this)">
                            <i class="fa-solid fa-chevron-right ch-caret"></i>
                            <span class="ch-topic-title">Chief Complaint</span>
                            <span class="ch-topic-preview"></span>
                        </a>
                        <div class="ch-topic-body">
                            <div id="cq-chief-complaint" class="clinical-quill"></div>
                        </div>
                    </div>
                    <div class="form-group ch-topic" data-topic="history_of_present_illness">
                        <a href="#" class="form-label ch-topic-link" onclick="return toggleChTopic(this)">
                            <i class="fa-solid fa-chevron-right ch-caret"></i>
                            <span class="ch-topic-title">History of Present Illness</span>
                            <span class="ch-topic-preview"></span>
                        </a>
                        <div class="ch-topic-body">
                            <div id="cq-hopi" class="clinical-quill"></div>
                        </div>
                    </div>
                    <div class="form-group ch-topic" data-topic="past_medical_history">
                        <a href="#" class="form-label ch-topic-link" onclick="return toggleChTopic(this)">
                            <i class="fa-solid fa-chevron-right ch-caret"></i>
                            <span class="ch-topic-title">Past Medical History</span>
                            <span class="ch-topic-preview"></span>
                        </a>
                        <div class="ch-topic-body">
                            <div id="cq-pmh" class="clinical-quill"></div>
                        </div>
                    </div>
                    <div class="form-group ch-topic" data-topic="family_history">
                        <a href="#" class="form-label ch-topic-link" onclick="return toggleChTopic(this)">
                            <i class="fa-solid fa-chevron-right ch-caret"></i>
                            <span class="ch-topic-title">Family History</span>
                            <span class="ch-topic-preview"></span>
                        </a>
                        <div class="ch-topic-body">
                            <div id="cq-fh" class="clinical-quill"></div>
                        </div>
                    </div>
                    <div class="form-group ch-topic" data-topic="allergies">
                        <a href="#" class="form-label ch-topic-link" onclick="return toggleChTopic(this)">
                            <i class="fa-solid fa-chevron-right ch-caret"></i>
                            <span class="ch-topic-title">Allergies</span>
                            <span class="ch-topic-preview"></span>
                            <span style="font-size:10px;color:var(--sc-muted);font-weight:400;margin-left:auto">(saved to patient record)</span>
                        </a>
                        <div class="ch-topic-body">
                            <div id="allergy-tags-wrap" style="border:1px solid var(--sc-border);border-radius:8px;padding:8px;background:#fff;min-height:42px">
                                <div id="allergy-tags" style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:6px">
                                    @foreach($patientAllergyRecords as $pa)
                                    <span class="allergy-tag" data-id="{{ $pa->id }}"
                                          style="display:inline-flex;align-items:center;gap:5px;padding:3px 10px 3px 10px;border-radius:20px;font-size:12px;font-weight:600;
                                                 background:{{ $pa->severity==='severe'?'#fef2f2':($pa->severity==='moderate'?'#fffbeb':'#f0fdf4') }};
                                                 color:{{ $pa->severity==='severe'?'#991b1b':($pa->severity==='moderate'?'#92400e':'#166534') }};
                                                 border:1px solid {{ $pa->severity==='severe'?'#fca5a5':($pa->severity==='moderate'?'#fcd34d':'#86efac') }}">
                                        {{ $pa->allergen }}
                                        <span style="font-size:10px;opacity:.7">({{ $pa->severity }})</span>
                                        <button type="button" onclick="removeAllergy({{ $pa->id }},this)"
                                                style="background:none;border:none;cursor:pointer;padding:0 0 0 3px;color:inherit;opacity:.7;font-size:14px;line-height:1">&times;</button>
                                    </span>
                                    @endforeach
                                </div>
                                <div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap">
                                    <input type="text" id="new-allergen-input" list="common-allergens-dl"
                                           placeholder="Type or pick allergen…"
                                           style="flex:1;min-width:160px;border:1px solid var(--sc-border);border-radius:6px;padding:5px 9px;font-size:12px;outline:none">
                                    <datalist id="common-allergens-dl">
                                        @foreach($commonAllergens as $al)
                                        <option value="{{ $al }}">
                                        @endforeach
                                    </datalist>
                                    <select id="new-allergen-severity"
                                            style="border:1px solid var(--sc-border);border-radius:6px;padding:5px 8px;font-size:12px;background:#fff">
                                        <option value="unknown">Severity</option>
                                        <option value="mild">Mild</option>
                                        <option value="moderate">Moderate</option>
                                        <option value="severe">Severe</option>
                                    </select>
                                    <button type="button" onclick="addAllergy()" class="btn btn-sm btn-outline"
                                            style="padding:5px 12px;font-size:12px"><i class="fa-solid fa-plus"></i> Add</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group ch-topic" data-topic="social_history">
                        <a href="#" class="form-label ch-topic-link" onclick="return toggleChTopic(this)">
                            <i class="fa-solid fa-chevron-right ch-caret"></i>
                            <span class="ch-topic-title">Social History</span>
                            <span class="ch-topic-preview"></span>
                        </a>
                        <div class="ch-topic-body">
                            <div id="cq-sh" class="clinical-quill"></div>
                        </div>
                    </div>
                    <div class="ch-group"><i class="fa-solid fa-stethoscope" style="color:var(--sc-teal)"></i> Examination</div>
                    <div class="form-group ch-topic" data-topic="examination_findings">
                        <a href="#" class="form-label ch-topic-link" onclick="return toggleChTopic(this)">
                            <i class="fa-solid fa-chevron-right ch-caret"></i>
                            <span class="ch-topic-title">Examination</span>
                            <span class="ch-topic-preview"></span>
                        </a>
                        <div class="ch-topic-body">
                            <div id="cq-exam" class="clinical-quill" style="min-height:140px"></div>
                        </div>
                    </div>
                    <div class="ch-group"><i class="fa-solid fa-lightbulb" style="color:var(--sc-teal)"></i> Assessment &amp; Plan</div>
                    <div class="form-group ch-topic" data-topic="impression">
                        <a href="#" class="form-label ch-topic-link" onclick="return toggleChTopic(this)">
                            <i class="fa-solid fa-chevron-right ch-caret"></i>
                            <span class="ch-topic-title">Impression / Differential Diagnosis</span>
                            <span class="ch-topic-preview"></span>
                        </a>
                        <div class="ch-topic-body">
                            <div id="cq-impression" class="clinical-quill"></div>
                        </div>
                    </div>
                    <div class="form-group ch-topic" data-topic="plan">
                        <a href="#" class="form-label ch-topic-link" onclick="return toggleChTopic(this)">
                            <i class="fa-solid fa-chevron-right ch-caret"></i>
                            <span class="ch-topic-title">Plan</span>
                            <span class="ch-topic-preview"></span>
                        </a>
                        <div class="ch-topic-body">
                            <div id="cq-plan" class="clinical-quill"></div>
                        </div>
                    </div>
                    <div class="ch-group"><i class="fa-solid fa-calendar-check" style="color:var(--sc-teal)"></i> Follow-up</div>
                    <div class="form-group ch-topic" data-topic="follow_up">
                        <a href="#" class="form-label ch-topic-link" onclick="return toggleChTopic(this)">
                            <i class="fa-solid fa-chevron-right ch-caret"></i>
                            <span class="ch-topic-title">Follow-up</span>
                            <span class="ch-topic-preview"></span>
                        </a>
                        <div class="ch-topic-body">
                            <div style="display:flex;flex-wrap:wrap;align-items:flex-start;gap:16px">
                    <div style="display:flex;flex-wrap:wrap;align-items:flex-start;gap:16px;margin-bottom:20px">
                        <div class="form-group" style="flex:1;min-width:280px;margin-bottom:0">
                            <label class="form-label" style="font-weight:500">Follow-up Instructions</label>
                            <textarea name="follow_up_instructions" class="form-control" rows="3">{{ $encounter->follow_up_instructions }}</textarea>
                        </div>
                        <div class="form-group" style="flex:1;min-width:280px;margin-bottom:0">
                            <label class="form-label" style="font-weight:500">Follow-up After (Days)</label>
                            <input type="number" name="follow_up_days" class="form-control" value="{{ $encounter->follow_up_days ?? 7 }}" min="0" placeholder="e.g. 7">
                            {{-- Default follow-up date = encounter date + 7 days (override here if needed). --}}
                            <div class="form-text" style="margin-top:8px">Specific date: <input type="date" name="follow_up_date" class="form-control" style="margin-top:4px" value="{{ ($encounter->follow_up_date ?: ($encounter->encounter_date ? $encounter->encounter_date->copy()->addDays(7) : now()->addDays(7)))->toDateString() }}"></div>
                        </div>
                            </div>
                        </div>
                    </div>

                    {{-- MLC Tagging --}}
                    <div class="card" style="margin-top:16px;border:1px solid #fca5a5;background:#fff7f7">
                        <div class="card-header" style="background:#fee2e2;color:#991b1b;font-size:13px;padding:10px 16px">
                            <i class="fa-solid fa-shield-halved"></i> Medico-Legal Case (MLC)
                        </div>
                        <div class="card-body" style="padding:14px 16px">
                            <div class="form-group" style="margin-bottom:8px">
                                <label style="display:flex;align-items:center;gap:10px;cursor:pointer;font-weight:600;font-size:13px">
                                    <input type="checkbox" name="is_mlc" value="1" id="encMlcCheck" {{ $encounter->is_mlc ? 'checked' : '' }}
                                           style="width:16px;height:16px;accent-color:#dc2626">
                                    <span>Tag this encounter as Medico-Legal Case</span>
                                </label>
                            </div>
                            <div id="encMlcFields" style="{{ $encounter->is_mlc ? '' : 'display:none' }}">
                                <div class="form-row cols-2">
                                    <div class="form-group" style="margin-bottom:0">
                                        <label class="form-label">MLC Number</label>
                                        <input type="text" name="mlc_no" class="form-control" value="{{ $encounter->mlc_no }}" placeholder="e.g. MLC/2024/001">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="clinical-form-actions" style="display:flex;gap:8px;margin-top:20px;padding-top:16px;border-top:1px solid var(--sc-border)">
                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Save Clinical Notes</button>
                        <button type="button" class="btn btn-outline" onclick="cancelClinicalEdit()"><i class="fa-solid fa-rotate-left"></i> Discard Changes</button>
                        <label style="display:flex;align-items:center;gap:6px;margin-left:auto;font-size:12.5px;cursor:pointer" title="Saves Clinical notes, Vitals, Diagnoses and speciality forms to the server every 3 seconds while you type">
                            <input type="checkbox" id="clinical-autosave-toggle" onchange="setClinicalAutoSave(this.checked)"> Auto-save every 3 s
                        </label>
                        <span id="clinical-autosave-status" style="align-self:center;font-size:12px;color:var(--sc-muted);min-width:120px"></span>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- DIAGNOSIS TAB -->
    <div id="tab-diagnosis" class="tab-pane">
        <div class="card">
            <div class="card-header"><i class="fa-solid fa-diagnoses"></i> Diagnoses
                <div class="card-actions">
                    <button id="btn-diag-edit" class="btn btn-sm btn-outline" type="button" onclick="enterDiagEditMode()"><i class="fa-solid fa-pen"></i> Edit</button>
                    <button id="btn-diag-add" class="btn btn-sm btn-primary" style="display:none" onclick="addDiagnosisRow()"><i class="fa-solid fa-plus"></i> Add</button>
                </div>
            </div>
            <div class="card-body">
                {{-- Read-only view (populated by JS on load) --}}
                <div id="diag-read-view"></div>
                {{-- Edit form (hidden until Edit clicked) --}}
                <div id="diagnoses-container" style="display:none">
                    @forelse($encounter->diagnoses as $d)
                    <div class="diagnosis-row" style="display:grid;grid-template-columns:2fr 1fr 1fr 1fr auto;gap:10px;align-items:end;margin-bottom:10px;padding:12px;background:var(--sc-bg);border-radius:8px;border:1px solid var(--sc-border)">
                        <div class="form-group" style="margin:0;position:relative">
                            <label class="form-label">Diagnosis Name</label>
                            <input type="text" name="diagnoses[name][]" class="form-control" value="{{ $d->diagnosis_name }}" placeholder="Type to search ICD-10..." autocomplete="off">
                        </div>
                        <div class="form-group" style="margin:0">
                            <label class="form-label">ICD Code</label>
                            <input type="text" name="diagnoses[icd_code][]" class="form-control" value="{{ $d->icd_code }}" placeholder="e.g. J06.9">
                        </div>
                        <div class="form-group" style="margin:0">
                            <label class="form-label">Type</label>
                            <select name="diagnoses[type][]" class="form-control">
                                @foreach(['Primary','Secondary','Differential'] as $t)
                                    <option value="{{ $t }}" {{ $d->type === $t ? 'selected':'' }}>{{ $t }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group" style="margin:0">
                            <label class="form-label">Status</label>
                            <select name="diagnoses[status][]" class="form-control">
                                @foreach(['Active','Resolved','Chronic'] as $s)
                                    <option value="{{ $s }}" {{ $d->status === $s ? 'selected':'' }}>{{ $s }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="button" onclick="this.closest('.diagnosis-row').remove()" class="btn btn-icon btn-sm btn-outline" style="color:#94a3b8;border-color:#e2e8f0;margin-bottom:0"><i class="fa-solid fa-trash"></i></button>
                    </div>
                    @empty
                    <div id="no-diag" class="text-muted text-center" style="padding:20px">No diagnoses added yet. Click "Add" to add a diagnosis.</div>
                    @endforelse
                </div>
                <div id="diag-form-actions" style="display:none;gap:8px;margin-top:12px">
                    <button type="button" class="btn btn-primary" id="diag-save-btn" onclick="saveDiagnoses()"><i class="fa-solid fa-save"></i> Save Diagnoses</button>
                    <button type="button" class="btn btn-outline" onclick="cancelDiagEdit()"><i class="fa-solid fa-xmark"></i> Cancel</button>
                </div>
            </div>
        </div>
    </div>

    <!-- PRESCRIPTION TAB -->
    <div id="tab-rx" class="tab-pane">
        @foreach($encounter->prescriptions as $rx)
        <div class="card mb-3 rx-card" id="rx-card-{{ $rx->id }}" data-rx="{{ $rx->id }}"
             @if($rx->status !== 'Dispensed') draggable="true" ondragstart="rxDragStart(event,{{ $rx->id }})" ondragover="rxDragOver(event)" ondragleave="rxDragLeave(event)" ondrop="rxDrop(event,{{ $rx->id }})" @endif>
            <div class="card-header">
                @if($rx->status !== 'Dispensed' && $encounter->prescriptions->where('status','!=','Dispensed')->count() > 1)<i class="fa-solid fa-grip-vertical" title="Drag onto another prescription to merge" style="cursor:grab;color:#cbd5e1;margin-right:4px"></i>@endif
                <i class="fa-solid fa-prescription"></i> <span class="mono">{{ $rx->prescription_no }}</span>
                <span class="badge {{ $rx->status === 'Dispensed' ? 'badge-green' : 'badge-amber' }}">{{ $rx->status }}</span>
                <div class="card-actions">
                    <a href="{{ route('prescriptions.print', $rx) }}" class="btn btn-sm btn-outline" target="_blank"><i class="fa-solid fa-print"></i> Print</a>
                    <button type="button" class="btn btn-sm btn-outline" onclick="openEmailRxModal({{ $rx->id }})"><i class="fa-solid fa-envelope"></i> Email</button>
                    <button type="button" class="btn btn-sm btn-outline" onclick="openWhatsAppRxModal({{ $rx->id }})"><i class="fa-brands fa-whatsapp"></i> WhatsApp</button>
                    @if($rx->status !== 'Dispensed')
                    <button type="button" class="btn btn-sm btn-outline" title="Edit the whole prescription" onclick="editPrescription({{ $rx->id }})"><i class="fa-solid fa-pen-to-square"></i> Edit Prescription</button>
                    @if($encounter->prescriptions->where('status','!=','Dispensed')->count() > 1)
                    <button type="button" class="btn btn-sm btn-outline" title="Merge this into another prescription" onclick="mergePrescription({{ $rx->id }})"><i class="fa-solid fa-code-merge"></i> Merge</button>
                    @endif
                    <button type="button" class="btn btn-icon btn-sm btn-outline" title="Edit notes/advice only"
                        onclick="openEditRxModal({{ $rx->id }}, '{{ addslashes($rx->notes ?? '') }}', '{{ addslashes($rx->advice ?? '') }}')">
                        <i class="fa-solid fa-pen"></i>
                    </button>
                    <button type="button" class="btn btn-icon btn-sm btn-outline" style="color:#94a3b8;border-color:#e2e8f0" title="Delete prescription" onclick="deleteRx({{ $rx->id }})"><i class="fa-solid fa-trash"></i></button>
                    @endif
                </div>
            </div>
            <div class="card-body" style="padding:12px 16px">
                @foreach($rx->items as $item)
                @php $resolvedGeneric = $item->generic_name ?: $item->medicine?->resolved_generic; @endphp
                <div class="rx-item" id="rx-item-row-{{ $item->id }}">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start">
                        <div>
                            <div class="rx-name">{{ $item->medicine->name }}</div>
                            @if($resolvedGeneric)
                            <div style="font-size:11px;color:#64748b;margin-top:1px"><i class="fa-solid fa-flask" style="font-size:10px"></i> <em>{{ ucfirst($resolvedGeneric) }}</em></div>
                            @endif
                        </div>
                        <div style="display:flex;gap:6px;align-items:center">
                            @if(!$item->is_in_stock)<span class="badge badge-amber"><i class="fa-solid fa-exclamation-triangle"></i> Not in Stock</span>@else<span class="badge badge-green"><i class="fa-solid fa-check"></i> In Stock</span>@endif
                            @if($item->is_dispensed)<span class="badge badge-green">Dispensed</span>@endif
                            @if(!$item->is_dispensed)
                            <button type="button" class="btn btn-icon btn-sm btn-outline" title="Edit"
                                onclick="openEditItemModal({{ $item->id }}, '{{ addslashes($item->medicine->name) }}', '{{ addslashes($item->dose) }}', '{{ addslashes($item->frequency) }}', '{{ addslashes($item->route) }}', {{ $item->duration_days }}, '{{ addslashes($item->instructions ?? '') }}')">
                                <i class="fa-solid fa-pen"></i>
                            </button>
                            <button type="button" class="btn btn-icon btn-sm btn-outline" style="color:#94a3b8;border-color:#e2e8f0" title="Delete" onclick="deleteRxItem({{ $item->id }})">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                            @endif
                        </div>
                    </div>
                    <div class="rx-details">
                        <strong>{{ $item->dose }}</strong> &nbsp;·&nbsp; {{ $item->frequency }} &nbsp;·&nbsp; {{ $item->route }} &nbsp;·&nbsp; {{ $item->duration_days }} days &nbsp;·&nbsp; Qty: {{ $item->quantity }}
                        @if($item->instructions)<br><span style="color:var(--sc-muted)">{{ $item->instructions }}</span>@endif
                    </div>
                    @if($item->medicine->side_effects)
                    <div class="rx-side-effects"><i class="fa-solid fa-triangle-exclamation"></i> <strong>Side Effects:</strong> {{ $item->medicine->side_effects }}</div>
                    @endif
                    @if($item->medicine->contraindications)
                    <div style="font-size:11px;color:#b45309;background:rgba(232,76,76,.08);padding:4px 8px;border-radius:4px;margin-top:4px"><i class="fa-solid fa-ban"></i> <strong>Contraindications:</strong> {{ $item->medicine->contraindications }}</div>
                    @endif
                </div>
                @endforeach
                @if($rx->advice)<div style="margin-top:8px;padding:10px;background:var(--sc-bg);border-radius:6px;font-size:13px"><strong>Advice:</strong> {{ $rx->advice }}</div>@endif
            </div>
        </div>
        @endforeach

        <div class="card" id="rx-form-card">
            <div class="card-header">
                <i class="fa-solid fa-plus-circle"></i> <span id="rx-form-title">Add New Prescription</span>
                <div class="card-actions">
                    <button type="button" class="btn btn-sm btn-outline" onclick="openTemplateModal()">
                        <i class="fa-solid fa-layer-group"></i> Load Template
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div id="rx-edit-banner" style="display:none;background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:9px 13px;margin-bottom:12px;font-size:13px">
                    <i class="fa-solid fa-pen-to-square" style="color:#2563eb"></i> Editing <strong id="rx-edit-no"></strong> — change anything below, then Update.
                    <button type="button" class="btn btn-sm btn-outline" style="margin-left:10px" onclick="cancelEditRx()">Cancel edit</button>
                </div>
                <div id="rx-items-container"></div>
                <button type="button" class="btn btn-outline btn-sm mb-3" onclick="addRxItem()"><i class="fa-solid fa-plus"></i> Add Medicine</button>

                {{-- Drug Interaction Alert Box --}}
                <div id="rx-interaction-box" style="display:none;background:#fef2f2;border:1.5px solid #fca5a5;border-radius:8px;padding:14px 16px;margin-bottom:14px"></div>

                <div class="grid grid-2">
                    <div class="form-group">
                        <label class="form-label">Notes</label>
                        <textarea id="rx-notes" class="form-control" rows="2" placeholder="Additional notes..."></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Advice to Patient</label>
                        <textarea id="rx-advice" class="form-control" rows="2" placeholder="Diet, lifestyle, activity..."></textarea>
                    </div>
                </div>
                <button class="btn btn-primary" id="rx-save-btn" onclick="savePrescription()"><i class="fa-solid fa-save"></i> Save Prescription</button>
            </div>
        </div>
    </div>

    <!-- LAB ORDERS TAB -->
    <div id="tab-labs" class="tab-pane">
        {{-- Suggested lab order from applied encounter template (populated by JS) --}}
        <div id="suggested-lab-order" class="card mb-3" style="display:none;border:1.5px dashed var(--sc-teal);background:rgba(10,180,180,.04)">
            <div class="card-header">
                <i class="fa-solid fa-layer-group" style="color:var(--sc-teal)"></i> Suggested Lab Order — <span id="suggested-lab-order-title">from template</span>
                <div class="card-actions">
                    <button type="button" class="btn btn-sm btn-outline" onclick="document.getElementById('suggested-lab-order').style.display='none'">
                        <i class="fa-solid fa-times"></i> Dismiss
                    </button>
                </div>
            </div>
            <div class="card-body">
                {{-- Non-lab procedures (ECG/Echo/Chest X-ray) — links to that module's create
                     form, not part of the lab order below since they're separate records --}}
                <div id="suggested-lab-order-procedures" style="display:none;flex-wrap:wrap;gap:6px;margin-bottom:14px"></div>
                <div id="suggested-lab-order-list" style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:14px"></div>
                <form id="suggested-lab-order-form" method="POST" action="{{ route('lab.store-order') }}">
                    @csrf
                    <input type="hidden" name="patient_id"   value="{{ $encounter->patient_id }}">
                    <input type="hidden" name="encounter_id" value="{{ $encounter->id }}">
                    <input type="hidden" name="doctor_id"    value="{{ $encounter->doctor_id }}">
                    <input type="hidden" name="return_to_encounter" value="1">
                    <input type="hidden" name="clinical_info" id="suggested-lab-clinical" value="">
                    <div id="suggested-lab-order-fields"></div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-flask-vial"></i> Place Lab Order
                    </button>
                </form>
            </div>
        </div>

        @foreach($encounter->labOrders as $lo)
        <div class="card mb-3">
            <div class="card-header">
                <i class="fa-solid fa-flask"></i> <span class="mono">{{ $lo->order_no }}</span>
                <span class="badge {{ $lo->status === 'Completed' ? 'badge-green' : 'badge-amber' }}">{{ $lo->status }}</span>
                <div class="card-actions"><a href="{{ route('lab.order', $lo) }}" class="btn btn-sm btn-outline">View Order</a></div>
            </div>
            <div class="card-body" style="padding:0">
                <table style="width:100%;border-collapse:collapse;font-size:13px">
                    <thead>
                        <tr style="background:var(--sc-bg);border-bottom:1px solid var(--sc-border)">
                            <th style="padding:8px 14px;text-align:left;font-weight:600;color:var(--sc-muted)">Test</th>
                            <th style="padding:8px 14px;text-align:left;font-weight:600;color:var(--sc-muted)">Result</th>
                            <th style="padding:8px 14px;text-align:left;font-weight:600;color:var(--sc-muted)">Unit</th>
                            <th style="padding:8px 14px;text-align:left;font-weight:600;color:var(--sc-muted)">Normal Range</th>
                            <th style="padding:8px 14px;text-align:left;font-weight:600;color:var(--sc-muted)">Flag</th>
                            <th style="padding:8px 14px;text-align:left;font-weight:600;color:var(--sc-muted)">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    @php
                        // Group this order's items by panel: a shaded panel-title row,
                        // then its member test rows; loose tests last.
                        $itemGroups  = $lo->items->groupBy(fn($it) => (int) ($it->lab_panel_id ?: 0));
                        $orderedKeys = $itemGroups->keys()->sortBy(fn($k) => $k === 0 ? 1 : 0)->values();
                    @endphp
                    @foreach($orderedKeys as $gKey)
                    @php $gItems = $itemGroups[$gKey]; $gPanel = $gKey ? optional($gItems->first())->panel : null; @endphp
                    @if($gPanel)
                        <tr><td colspan="6" style="background:#d6deea;color:#1f3a5f;font-weight:700;text-transform:uppercase;letter-spacing:.3px;font-size:11.5px;padding:7px 14px;border-bottom:1px solid #aebdd3">{{ $gPanel->name }}{{ $gPanel->short_code ? ' · '.$gPanel->short_code : '' }}</td></tr>
                    @elseif($orderedKeys->count() > 1)
                        <tr><td colspan="6" style="background:#eceff4;color:#475569;font-weight:700;text-transform:uppercase;letter-spacing:.3px;font-size:11px;padding:6px 14px;border-bottom:1px solid var(--sc-border)">Individual tests</td></tr>
                    @endif
                    @foreach($gItems as $item)
                        @php
                            $flagColors = ['N'=>'#22c55e','NEG'=>'#22c55e','H'=>'#f59e0b','L'=>'#f59e0b','ABN'=>'#f59e0b','HH'=>'#ef4444','LL'=>'#ef4444','POS'=>'#ef4444'];
                            $flagLabels = ['N'=>'Normal','NEG'=>'Negative','H'=>'High','L'=>'Low','ABN'=>'Abnormal','HH'=>'Critical High','LL'=>'Critical Low','POS'=>'Positive'];
                            $flag = $item->abnormal_flag ?: 'N';
                            $flagColor = $flagColors[$flag] ?? '#f59e0b';
                            $isAbnormal = !in_array($flag, ['N','NEG'], true);
                            $res   = $item->results->first();
                            $name  = $item->test?->name ?? ($res->parameter_name ?? $item->panel?->name ?? '—');
                            $unit  = $item->test?->unit ?: ($res->unit ?? null);
                            $range = $item->test?->normal_range ?: ($res->normal_range ?? null);
                        @endphp
                        <tr style="border-bottom:1px solid var(--sc-border);{{ $isAbnormal ? 'background:rgba(232,76,76,.05)' : '' }}">
                            <td style="padding:9px 14px 9px 24px;font-weight:500">{{ $name }}</td>
                            <td style="padding:9px 14px;font-weight:700;color:{{ $isAbnormal ? $flagColor : 'var(--sc-navy)' }}">
                                {{ $item->result_value ?? '—' }}
                            </td>
                            <td style="padding:9px 14px;color:var(--sc-muted)">{{ $unit ?? '—' }}</td>
                            <td style="padding:9px 14px;color:var(--sc-muted)">{{ $range ?? '—' }}</td>
                            <td style="padding:9px 14px">
                                @if($isAbnormal)
                                    <span style="background:{{ $flagColor }};color:#fff;padding:2px 8px;border-radius:10px;font-size:11px;font-weight:600">{{ $flagLabels[$flag] }}</span>
                                @else
                                    <span style="color:var(--sc-green);font-size:12px">{{ $flagLabels[$flag] ?? 'Normal' }}</span>
                                @endif
                            </td>
                            <td style="padding:9px 14px">
                                <span class="badge {{ $item->status === 'Completed' ? 'badge-green' : 'badge-amber' }}">{{ $item->result_status ?? $item->status }}</span>
                            </td>
                        </tr>
                    @endforeach
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endforeach
        <a href="{{ route('lab.create-order', ['patient_id' => $encounter->patient_id, 'encounter_id' => $encounter->id]) }}" class="btn btn-primary">
            <i class="fa-solid fa-flask-vial"></i> Create Lab Order
        </a>
        <a href="{{ route('lab.report.upload', ['patient_id' => $encounter->patient_id, 'encounter_id' => $encounter->id]) }}" class="btn btn-primary" style="background:#7c3aed;border-color:#7c3aed">
            <i class="fa-solid fa-file-arrow-up"></i> Upload Lab Report (auto-read)
        </a>
    </div>

    <!-- REPORTS TAB -->
    <div id="tab-reports" class="tab-pane">

        {{-- Upload form --}}
        <div class="card" style="margin-bottom:16px">
            <div class="card-header"><i class="fa-solid fa-file-arrow-up"></i> Upload File</div>
            <div class="card-body">
                <form method="POST" action="{{ route('encounters.reports.upload', $encounter) }}" enctype="multipart/form-data">
                    @csrf
                    <div style="display:grid;grid-template-columns:1.5fr 1fr 1fr auto;gap:12px;align-items:end">
                        <div class="form-group" style="margin:0">
                            <label class="form-label">File <span class="req">*</span></label>
                            <input type="file" name="report_file" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx" required>
                        </div>
                        <div class="form-group" style="margin:0">
                            <label class="form-label">Title</label>
                            <input type="text" name="title" class="form-control" placeholder="e.g. CBC result">
                        </div>
                        <div class="form-group" style="margin:0">
                            <label class="form-label">Type</label>
                            <select name="report_type" class="form-control">
                                <option value="">General</option>
                                <option>Lab Report</option>
                                <option>ECG Trace</option>
                                <option>Echo File</option>
                                <option>Imaging</option>
                                <option>Discharge Summary</option>
                                <option>Prescription</option>
                                <option>Referral Letter</option>
                                <option>Insurance Document</option>
                                <option>Other</option>
                            </select>
                        </div>
                        <div><button type="submit" class="btn btn-primary" style="white-space:nowrap"><i class="fa-solid fa-upload"></i> Upload</button></div>
                    </div>
                    <div class="form-group" style="margin-top:10px;margin-bottom:0">
                        <textarea name="notes" class="form-control" rows="1" placeholder="Notes (optional)..."></textarea>
                    </div>
                </form>
            </div>
        </div>

        @php
            $allReports   = $encounter->reports->sortByDesc('created_at');
            $ecgUploads   = $allReports->filter(fn($r) => $r->report_type === 'ECG Trace');
            $echoUploads  = $allReports->filter(fn($r) => $r->report_type === 'Echo File');
            $labUploads   = $allReports->filter(fn($r) => $r->report_type === 'Lab Report');
            $otherUploads = $allReports->filter(fn($r) => !in_array($r->report_type, ['ECG Trace','Echo File','Lab Report']));
        @endphp

        {{-- ECG Section --}}
        @if($patientEcgRecords->count() || $ecgUploads->count())
        <div class="card" style="margin-bottom:16px">
            <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;background:var(--sc-navy);color:#fff">
                <span><i class="fa-solid fa-wave-square" style="color:var(--sc-teal)"></i> ECG Records
                    @if($patientEcgRecords->count())<span class="badge badge-teal" style="font-size:10px;margin-left:6px">{{ $patientEcgRecords->count() }}</span>@endif
                </span>
                <a href="{{ route('ecg.create') }}?patient_id={{ $encounter->patient_id }}" class="btn btn-sm btn-outline" style="color:#fff;border-color:rgba(255,255,255,.4)"><i class="fa-solid fa-plus"></i> New ECG</a>
            </div>
            @if($patientEcgRecords->count())
            <div class="table-wrap">
                <table class="sc-table" style="font-size:12px">
                    <thead><tr><th>Record No</th><th>Date</th><th>Heart Rate</th><th>Rhythm</th><th>Doctor</th><th></th></tr></thead>
                    <tbody>
                    @foreach($patientEcgRecords as $ecgRec)
                    <tr>
                        <td class="mono fw-600">{{ $ecgRec->record_no }}</td>
                        <td>{{ $ecgRec->record_date->format('d M Y') }}</td>
                        <td>{{ $ecgRec->heart_rate ?: '—' }}</td>
                        <td>{{ $ecgRec->rhythm ?: '—' }}</td>
                        <td>{{ $ecgRec->doctor->full_name }}</td>
                        <td style="white-space:nowrap">
                            <a href="{{ route('ecg.show', $ecgRec) }}" class="btn btn-sm btn-outline" title="View"><i class="fa-solid fa-eye"></i></a>
                            @if($ecgRec->ecg_file)
                            @php $eUrl = str_contains($ecgRec->ecg_file,'/') ? asset('storage/'.$ecgRec->ecg_file) : asset('storage/ecg_files/'.$ecgRec->ecg_file); @endphp
                            <a href="{{ $eUrl }}" download="{{ basename($ecgRec->ecg_file) }}" class="btn btn-sm btn-outline" title="Download"><i class="fa-solid fa-download"></i></a>
                            @endif
                            <a href="{{ route('ecg.edit', $ecgRec) }}" class="btn btn-sm btn-outline" title="Edit"><i class="fa-solid fa-pencil"></i></a>
                        </td>
                    </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @endif
            @if($ecgUploads->count())
            <div class="card-body" style="border-top:1px solid var(--sc-border)">
                <div style="font-size:11px;font-weight:700;color:var(--sc-muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px">Uploaded ECG Traces</div>
                @foreach($ecgUploads as $report)
                    @include('encounters._report_row')
                @endforeach
            </div>
            @endif
        </div>
        @endif

        {{-- Echo Section --}}
        @if($patientEchoRecords->count() || $echoUploads->count())
        <div class="card" style="margin-bottom:16px">
            <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;background:var(--sc-navy);color:#fff">
                <span><i class="fa-solid fa-heart-pulse" style="color:#f87171"></i> Echo Records
                    @if($patientEchoRecords->count())<span class="badge badge-teal" style="font-size:10px;margin-left:6px">{{ $patientEchoRecords->count() }}</span>@endif
                </span>
                <a href="{{ route('echo.create') }}?patient_id={{ $encounter->patient_id }}" class="btn btn-sm btn-outline" style="color:#fff;border-color:rgba(255,255,255,.4)"><i class="fa-solid fa-plus"></i> New Echo</a>
            </div>
            @if($patientEchoRecords->count())
            <div class="table-wrap">
                <table class="sc-table" style="font-size:12px">
                    <thead><tr><th>Record No</th><th>Date</th><th>EF%</th><th>LV EDD</th><th>Doctor</th><th></th></tr></thead>
                    <tbody>
                    @foreach($patientEchoRecords as $echoRec)
                    @php $efVal = $echoRec->ef_percent; $efVal = $efVal && !str_contains($efVal,'%') ? $efVal.'%' : $efVal; @endphp
                    <tr>
                        <td class="mono fw-600">{{ $echoRec->record_no }}</td>
                        <td>{{ $echoRec->record_date->format('d M Y') }}</td>
                        <td>{{ $efVal ?: '—' }}</td>
                        <td>{{ $echoRec->lv_edd ?: '—' }}</td>
                        <td>{{ $echoRec->doctor->full_name }}</td>
                        <td style="white-space:nowrap">
                            <a href="{{ route('echo.show', $echoRec) }}" class="btn btn-sm btn-outline" title="View"><i class="fa-solid fa-eye"></i></a>
                            @if($echoRec->echo_file)
                            @php $eUrl2 = str_contains($echoRec->echo_file,'/') ? asset('storage/'.$echoRec->echo_file) : asset('storage/echo_files/'.$echoRec->echo_file); @endphp
                            <a href="{{ $eUrl2 }}" download="{{ basename($echoRec->echo_file) }}" class="btn btn-sm btn-outline" title="Download"><i class="fa-solid fa-download"></i></a>
                            @endif
                            <a href="{{ route('echo.edit', $echoRec) }}" class="btn btn-sm btn-outline" title="Edit"><i class="fa-solid fa-pencil"></i></a>
                        </td>
                    </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @endif
            @if($echoUploads->count())
            <div class="card-body" style="border-top:1px solid var(--sc-border)">
                <div style="font-size:11px;font-weight:700;color:var(--sc-muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px">Uploaded Echo Files</div>
                @foreach($echoUploads as $report)
                    @include('encounters._report_row')
                @endforeach
            </div>
            @endif
        </div>
        @endif

        {{-- Lab Reports --}}
        @if($labUploads->count())
        <div class="card" style="margin-bottom:16px">
            <div class="card-header" style="background:var(--sc-navy);color:#fff"><i class="fa-solid fa-flask-vial" style="color:#fbbf24"></i> Lab Reports
                <span class="badge badge-teal" style="font-size:10px;margin-left:6px">{{ $labUploads->count() }}</span>
            </div>
            <div class="card-body">
                @foreach($labUploads as $report)
                    @include('encounters._report_row')
                @endforeach
            </div>
        </div>
        @endif

        {{-- Other Documents --}}
        <div class="card">
            <div class="card-header" style="background:var(--sc-navy);color:#fff"><i class="fa-solid fa-folder-open" style="color:var(--sc-teal)"></i> Documents &amp; Reports
                @if($otherUploads->count())<span class="badge badge-teal" style="font-size:10px;margin-left:6px">{{ $otherUploads->count() }}</span>@endif
            </div>
            <div class="card-body">
                @forelse($otherUploads as $report)
                    @include('encounters._report_row')
                @empty
                <div class="text-center text-muted" style="padding:24px 16px">
                    <i class="fa-solid fa-folder-open" style="font-size:28px;margin-bottom:8px;opacity:.45"></i>
                    <div>No documents uploaded for this visit.</div>
                </div>
                @endforelse
            </div>
        </div>

    </div>

    <!-- SPECIALITY FORMS (config/opd_forms.php) -->
    @foreach($specForms as $code => $def)
        @if(in_array($code, $specShown))
            @include('encounters._speciality-form', ['code' => $code, 'def' => $def, 'saved' => $specSaved->get($code), 'prev' => \App\Services\OpdForms::previous($encounter, $code)])
        @else
            <template id="spec-tpl-{{ $code }}">@include('encounters._speciality-form', ['code' => $code, 'def' => $def, 'saved' => null, 'prev' => \App\Services\OpdForms::previous($encounter, $code)])</template>
        @endif
    @endforeach

    <!-- NOTES TAB -->
    <div id="tab-notes" class="tab-pane">
        {{-- Existing notes --}}
        <div id="notes-list">
            @forelse($encounter->encounterNotes as $note)
            <div class="note-card" id="note-{{ $note->id }}">
                <div class="note-card-header">
                    <div style="display:flex;align-items:center;gap:8px">
                        <i class="fa-solid fa-note-sticky" style="color:var(--sc-teal)"></i>
                        <span style="font-size:11px;color:var(--sc-muted)">{{ $note->created_by }} &middot; {{ $note->created_at->format('d M Y, h:i A') }}</span>
                    </div>
                    <div style="display:flex;gap:6px">
                        <button type="button" class="btn btn-icon btn-sm btn-outline" title="Edit" onclick="openNoteEdit({{ $note->id }})"><i class="fa-solid fa-pen"></i></button>
                        <button type="button" class="btn btn-icon btn-sm btn-outline" style="color:#94a3b8;border-color:#e2e8f0" title="Delete" onclick="deleteNote({{ $note->id }})"><i class="fa-solid fa-trash"></i></button>
                    </div>
                </div>
                <div class="note-body ql-editor" id="note-body-{{ $note->id }}" style="padding:0">{!! $note->body !!}</div>
                <div class="note-edit-form" id="note-edit-{{ $note->id }}" style="display:none">
                    <div id="note-edit-quill-{{ $note->id }}" class="note-quill-edit" data-content="{{ e($note->body) }}" style="min-height:120px;background:#fff"></div>
                    <div style="display:flex;gap:8px;margin-top:8px">
                        <button type="button" class="btn btn-primary btn-sm" id="note-edit-save-btn-{{ $note->id }}" onclick="saveNoteEdit({{ $note->id }})"><i class="fa-solid fa-check"></i> Save</button>
                        <button type="button" class="btn btn-outline btn-sm" onclick="cancelNoteEdit({{ $note->id }})">Cancel</button>
                    </div>
                </div>
            </div>
            @empty
            <div id="notes-empty" style="text-align:center;padding:40px;color:var(--sc-muted)">
                <i class="fa-solid fa-note-sticky" style="font-size:2rem;margin-bottom:12px;opacity:.3"></i>
                <div style="font-size:14px">No notes yet. Add your first note below.</div>
            </div>
            @endforelse
        </div>

        {{-- Add note --}}
        <div class="card" style="margin-top:16px">
            <div class="card-header"><i class="fa-solid fa-plus-circle"></i> Add Note</div>
            <div class="card-body">
                <div class="form-group" style="margin-bottom:12px">
                    <div id="new-note-quill" style="min-height:140px;background:#fff"></div>
                </div>
                <button type="button" class="btn btn-primary" id="note-save-btn" onclick="saveNewNote()"><i class="fa-solid fa-save"></i> Save Note</button>
            </div>
        </div>
    </div>
</div>
{{-- ── QUICK BILL MODAL ── --}}
@if(!$encounter->bill)
<div id="quick-bill-modal" class="modal-overlay">
    <div class="modal-box modal-lg" style="max-width:720px">
        <div class="modal-header">
            <i class="fa-solid fa-file-invoice-dollar" style="color:var(--sc-teal)"></i>
            <h3>Quick Bill — {{ $encounter->patient->full_name }}</h3>
            <button class="modal-close" onclick="document.getElementById('quick-bill-modal').classList.remove('open')">&times;</button>
        </div>
        <form method="POST" action="{{ route('billing.store') }}" id="quick-bill-form">
            @csrf
            <input type="hidden" name="patient_id"   value="{{ $encounter->patient_id }}">
            <input type="hidden" name="encounter_id" value="{{ $encounter->id }}">

            <div class="modal-body" style="padding:18px 22px">
                @include('opd._fee-note', ['q' => $feeQuote ?? null])
                {{-- Encounter context strip --}}
                <div style="background:var(--sc-bg);border-radius:8px;padding:10px 14px;margin-bottom:16px;display:flex;gap:16px;font-size:12.5px;color:var(--sc-muted);flex-wrap:wrap">
                    <span><i class="fa-solid fa-stethoscope" style="color:var(--sc-teal);margin-right:4px"></i>{{ $encounter->encounter_no }}</span>
                    <span><i class="fa-solid fa-calendar" style="margin-right:4px"></i>{{ $encounter->encounter_date->format('d M Y') }}</span>
                    <span><i class="fa-solid fa-user-doctor" style="margin-right:4px"></i>Dr. {{ $encounter->doctor->full_name ?? '—' }}</span>
                    @if($encounter->visit_type)<span class="badge badge-teal">{{ $encounter->visit_type }}</span>@endif
                </div>

                {{-- Items table --}}
                <div style="margin-bottom:14px">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
                        <span style="font-size:12.5px;font-weight:700;color:var(--sc-navy)">Bill Items</span>
                        <button type="button" class="btn btn-outline btn-sm" onclick="qbAddRow()">
                            <i class="fa-solid fa-plus"></i> Add Item
                        </button>
                    </div>
                    <div style="border:1px solid var(--sc-border);border-radius:8px;overflow:hidden">
                        <table style="width:100%;border-collapse:collapse;font-size:13px">
                            <thead>
                                <tr style="background:var(--sc-bg)">
                                    <th style="padding:8px 12px;text-align:left;font-size:11px;font-weight:700;color:var(--sc-muted);text-transform:uppercase">Description</th>
                                    <th style="padding:8px 12px;text-align:left;font-size:11px;font-weight:700;color:var(--sc-muted);text-transform:uppercase;width:120px">Type</th>
                                    <th style="padding:8px 12px;text-align:center;font-size:11px;font-weight:700;color:var(--sc-muted);text-transform:uppercase;width:60px">Qty</th>
                                    <th style="padding:8px 12px;text-align:right;font-size:11px;font-weight:700;color:var(--sc-muted);text-transform:uppercase;width:100px">Price (₹)</th>
                                    <th style="padding:8px 12px;text-align:right;font-size:11px;font-weight:700;color:var(--sc-muted);text-transform:uppercase;width:90px">Total</th>
                                    <th style="width:36px"></th>
                                </tr>
                            </thead>
                            <tbody id="qb-items-body">
                                {{-- rows injected by JS --}}
                            </tbody>
                        </table>
                    </div>

                    {{-- Service charge quick-add --}}
                    <div style="margin-top:10px;display:flex;gap:8px;align-items:center">
                        <select id="qb-service-picker" class="form-control" style="flex:1;font-size:12.5px">
                            <option value="">— Add from service catalogue —</option>
                            @foreach($services->groupBy('category') as $cat => $svcs)
                            <optgroup label="{{ $cat }}">
                                @foreach($svcs as $svc)
                                <option value="{{ $svc->id }}" data-name="{{ $svc->name }}" data-price="{{ $svc->price }}" data-type="{{ $svc->category }}">{{ $svc->name }} — ₹{{ number_format($svc->price,0) }}</option>
                                @endforeach
                            </optgroup>
                            @endforeach
                        </select>
                        <button type="button" class="btn btn-outline btn-sm" onclick="qbAddFromCatalogue()" style="white-space:nowrap">
                            <i class="fa-solid fa-plus"></i> Add
                        </button>
                    </div>
                </div>

                {{-- Totals + payment --}}
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
                    <div>
                        <div class="form-group">
                            <label class="form-label">Bill Date</label>
                            <input type="date" name="bill_date" id="qb-bill-date" class="form-control" value="{{ today()->toDateString() }}" max="{{ today()->toDateString() }}" onchange="var p=document.getElementById('qb-pay-date');if(p&&!p.dataset.touched)p.value=this.value">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Discount %</label>
                            <input type="number" name="discount_percent" id="qb-discount" class="form-control" value="0" min="0" max="100" step="0.5" oninput="qbRecalc()">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Payment Method</label>
                            <select name="payment_method" class="form-control">
                                <option value="Cash">Cash</option>
                                <option value="Card">Card</option>
                                <option value="UPI">UPI</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                                <option value="Insurance">Insurance</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Payment Date</label>
                            <input type="date" name="payment_date" id="qb-pay-date" class="form-control" value="{{ today()->toDateString() }}" max="{{ today()->toDateString() }}" oninput="this.dataset.touched=1">
                        </div>
                        <div class="form-group" style="margin-bottom:0">
                            <label class="form-label">Amount Paid (₹)</label>
                            <input type="number" name="paid_amount" id="qb-paid" class="form-control" value="0" min="0" step="0.01" oninput="qbRecalc()">
                        </div>
                    </div>
                    <div style="background:var(--sc-bg);border-radius:10px;padding:16px;display:flex;flex-direction:column;gap:8px">
                        <div style="display:flex;justify-content:space-between;font-size:13px">
                            <span style="color:var(--sc-muted)">Subtotal</span>
                            <span id="qb-subtotal" style="font-weight:600">₹0</span>
                        </div>
                        <div style="display:flex;justify-content:space-between;font-size:13px">
                            <span style="color:var(--sc-muted)">Discount</span>
                            <span id="qb-discount-amt" style="color:var(--sc-green)">−₹0</span>
                        </div>
                        <div style="border-top:2px solid var(--sc-border);padding-top:8px;display:flex;justify-content:space-between">
                            <span style="font-weight:700;font-size:14px">Total</span>
                            <span id="qb-total" style="font-weight:700;font-size:18px;color:var(--sc-navy)">₹0</span>
                        </div>
                        <div style="display:flex;justify-content:space-between;font-size:13px">
                            <span style="color:var(--sc-muted)">Paid</span>
                            <span id="qb-paid-disp" style="color:var(--sc-green);font-weight:600">₹0</span>
                        </div>
                        <div style="display:flex;justify-content:space-between;font-size:14px;font-weight:700">
                            <span>Balance Due</span>
                            <span id="qb-due" style="color:var(--sc-red)">₹0</span>
                        </div>
                        <button type="button" class="btn btn-outline btn-sm" style="margin-top:4px" onclick="qbPayFull()">
                            <i class="fa-solid fa-check-double"></i> Mark Fully Paid
                        </button>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="position:sticky;bottom:0;background:var(--sc-surface,#fff);z-index:2;align-items:center;flex-wrap:wrap">
                <input type="text" name="notes" class="form-control" placeholder="Notes (optional)…" style="flex:1 1 180px;max-width:320px;margin-right:auto">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('quick-bill-modal').classList.remove('open')">Cancel</button>
                <button type="submit" class="btn btn-primary" id="qb-submit-btn">
                    <i class="fa-solid fa-file-invoice-dollar"></i> Create Bill
                </button>
            </div>
        </form>
    </div>
</div>
@endif

@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.snow.css">
<style>
/* Save buttons confirm in place (markSaved): green with a check mark, "Saved ..." */
/* Speciality forms (eye, gynae, ortho, skin) */
.spec-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 14px; }
.spec-card { margin: 0; }
.spec-wide { grid-column: 1 / -1; }
.spec-table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
.spec-table th { font-size: 11px; font-weight: 600; color: var(--sc-muted); text-align: left; padding: 2px 4px; }
.spec-table td { padding: 3px 4px; vertical-align: middle; }
.spec-table .spec-label { font-size: 12.5px; font-weight: 500; white-space: nowrap; width: 1%; padding-right: 10px; }
.spec-table .spec-eye { font-weight: 700; color: var(--sc-navy); width: 22px; }
.spec-in { height: 32px; padding: 4px 8px; font-size: 13px; }
textarea.spec-in { height: auto; }
.spec-in.spec-warn { border-color: var(--sc-red); background: rgba(232,76,76,.06); }
.spec-fields { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 0 12px; }
.spec-fields .spec-full { grid-column: 1 / -1; }
.spec-checks { display: flex; flex-wrap: wrap; gap: 4px 12px; }
.spec-check { font-size: 12.5px; display: inline-flex; gap: 4px; align-items: center; cursor: pointer; }
@media (max-width: 640px) { .spec-grid { grid-template-columns: 1fr; } .spec-table .spec-label { white-space: normal; } }
.btn.btn-saved, .btn.btn-saved:hover { background: var(--sc-green); border-color: var(--sc-green); color: #fff; }
/* ── Single-scroll consult: stack all tab panes, keep tab-nav sticky as scroll-spy ── */
#enc-tabs .tab-pane { display: block !important; scroll-margin-top: 90px; }
#enc-tabs .tab-pane + .tab-pane { margin-top: 24px; padding-top: 18px; border-top: 2px dashed var(--sc-border); }
#enc-tabs .tab-nav {
    position: sticky;
    top: 0;
    z-index: 25;
    background: var(--sc-bg);
    margin: 0 calc(-1 * var(--sc-content-pad, 16px));
    padding: 8px var(--sc-content-pad, 16px) 0;
    box-shadow: 0 2px 6px rgba(0,0,0,.04);
}
[data-theme="dark"] #enc-tabs .tab-nav { background: var(--sc-bg); }
@media print {
    #enc-tabs .tab-nav { display: none; }
    #enc-tabs .tab-pane + .tab-pane { border-top: none; }
}

.rx-row { background:var(--sc-bg); border-radius:8px; padding:12px; margin-bottom:10px; border:1px solid var(--sc-border); }
.rx-combo-back { border:1px solid var(--sc-border); background:#fff; color:#64748b; border-radius:6px; cursor:pointer; font-size:13px; line-height:1; padding:0 7px; margin-top:4px; }
.stock-badge { display:inline-flex;align-items:center;gap:4px;padding:3px 8px;border-radius:20px;font-size:10px;font-weight:600 }
.stock-badge.in-stock { background:rgba(39,196,122,.15);color:#1b9e5c }
.stock-badge.out-stock { background:rgba(245,166,35,.15);color:#c47e0a }
.stock-badge.low-stock { background:rgba(245,166,35,.15);color:#c47e0a }
.diag-read-row { display:flex;justify-content:space-between;align-items:center;padding:10px 14px;border:1px solid var(--sc-border);border-radius:8px;margin-bottom:8px;background:var(--sc-bg); }
.diag-read-name { font-size:14px;font-weight:600;color:var(--sc-navy); }
.diag-read-code { font-size:11px;color:var(--sc-muted);font-family:'DM Mono',monospace;margin-left:8px;font-weight:400; }
.note-card { background:#fff;border:1px solid var(--sc-border);border-radius:10px;padding:14px 16px;margin-bottom:12px; }
.note-card-header { display:flex;justify-content:space-between;align-items:center;margin-bottom:10px; }
.note-body { font-size:14px;color:var(--sc-navy);line-height:1.6; }
.note-body p { margin:0 0 6px }
.note-body ul, .note-body ol { padding-left:18px; margin:0 0 6px }
/* Quill editor appearance to match app */
#new-note-quill, .note-quill-edit { border:1px solid var(--sc-border);border-radius:8px;overflow:hidden;background:#fff; }
.clinical-quill { border:1px solid var(--sc-border);border-radius:8px;background:#fff;overflow:visible; }
.ch-topic-tools { text-align:right;font-size:12px;margin:-4px 0 6px; }
.ch-topic-tools a { color:var(--sc-teal);text-decoration:none; }
.ch-group { font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--sc-muted);margin:14px 0 4px;padding-bottom:6px;border-bottom:1px solid var(--sc-border); }
.ch-topic { margin-bottom:0;border-bottom:1px solid var(--sc-border); }
.ch-topic-link { display:flex;align-items:center;gap:8px;padding:9px 4px;margin:0;color:var(--sc-teal);text-decoration:none;cursor:pointer;font-size:13.5px; }
.ch-topic-link:hover .ch-topic-title { text-decoration:underline; }
.ch-topic-title { font-weight:600;white-space:nowrap; }
.ch-caret { font-size:10px;width:10px;transition:transform .15s;color:var(--sc-muted); }
.ch-topic.open .ch-caret { transform:rotate(90deg); }
.ch-topic-preview { flex:1;min-width:0;color:var(--sc-muted);font-weight:400;font-size:12px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis; }
.ch-topic-preview.empty { font-style:italic;opacity:.7; }
.ch-topic-body { display:none;padding:0 0 14px 18px; }
.ch-topic.open .ch-topic-body { display:block; }
.ch-print-menu { position:absolute;right:0;top:100%;margin-top:4px;z-index:50;background:#fff;border:1px solid var(--sc-border);border-radius:8px;box-shadow:0 6px 18px rgba(0,0,0,.12);padding:10px 12px;min-width:250px;text-align:left;font-weight:400;text-transform:none;letter-spacing:0; }
.ch-print-menu label { display:flex;align-items:center;gap:8px;font-size:12.5px;color:var(--sc-text);padding:3px 0;cursor:pointer;white-space:nowrap; }
/* Quick-pick chips above each clinical Quill */
.cq-chips { display:flex;flex-wrap:wrap;gap:5px;margin:4px 0 6px; }
.cq-chip { font-size:11px;padding:2px 9px;border-radius:14px;border:1px solid var(--sc-border);background:#fff;color:var(--sc-text);cursor:pointer;transition:all .15s;font-family:inherit; }
.cq-chip:hover { border-color:var(--sc-teal);background:rgba(10,180,180,.08);color:var(--sc-teal); }
/* Voice mic button next to label */
.cq-mic-btn { margin-left:8px;background:transparent;border:1px solid var(--sc-border);color:var(--sc-muted);width:22px;height:22px;border-radius:50%;cursor:pointer;font-size:10px;padding:0;line-height:1;transition:all .15s; }
.cq-mic-btn:hover { color:var(--sc-teal);border-color:var(--sc-teal); }
.cq-mic-btn.listening { background:var(--sc-red);border-color:var(--sc-red);color:#fff;animation:micpulse 1.2s infinite; }
@keyframes micpulse { 0%,100%{box-shadow:0 0 0 0 rgba(232,76,76,.55);} 50%{box-shadow:0 0 0 6px rgba(232,76,76,0);} }
#new-note-quill .ql-toolbar, .note-quill-edit .ql-toolbar { border:none;border-bottom:1px solid var(--sc-border);background:var(--sc-bg);padding:5px 10px; }
#new-note-quill .ql-container, .note-quill-edit .ql-container, .clinical-quill .ql-container { border:none;font-family:'DM Sans',sans-serif;font-size:13px;color:var(--sc-navy); }
#new-note-quill .ql-editor, .note-quill-edit .ql-editor, .clinical-quill .ql-editor { padding:9px 11px; }
/* Clinical quill — compact toolbar, always auto-height */
.clinical-quill .ql-toolbar { border:none;border-bottom:1px solid var(--sc-border);background:var(--sc-bg);padding:3px 8px; }
.clinical-quill .ql-toolbar .ql-formats { margin-right:8px; }
.clinical-quill .ql-toolbar button { width:22px;height:22px;padding:2px; }
.clinical-quill .ql-container { box-sizing:border-box;height:auto !important;min-height:0 !important; }
.clinical-quill .ql-editor { min-height:88px !important;height:auto !important; }
#new-note-quill .ql-editor.ql-blank::before, .note-quill-edit .ql-editor.ql-blank::before, .clinical-quill .ql-editor.ql-blank::before { color:#94a3b8;font-style:normal; }
/* Clinical notes — read-only mode */
.clinical-quill.read-only { border-color:transparent !important; background:transparent !important; border-radius:0; }
.clinical-quill.read-only .ql-toolbar { display:none !important; }
.clinical-quill.read-only .ql-container.ql-snow { border:none; background:transparent; }
.clinical-quill.read-only .ql-editor { padding:2px 0; min-height:0 !important; cursor:default; }
.clinical-quill.read-only .ql-editor.ql-blank::before { display:none; }
.clinical-quill.read-only .ql-editor p { margin:0 0 3px; }
.clinical-quill.read-only .ql-editor ul, .clinical-quill.read-only .ql-editor ol { padding-left:16px; margin:0 0 3px; }
.ql-snow .ql-stroke { stroke:var(--sc-navy) }
.ql-snow .ql-fill { fill:var(--sc-navy) }
.ql-snow.ql-toolbar button:hover .ql-stroke, .ql-snow .ql-toolbar button:hover .ql-stroke { stroke:var(--sc-teal) }
.ql-snow.ql-toolbar button.ql-active .ql-stroke { stroke:var(--sc-teal) }
.ql-snow.ql-toolbar button:hover .ql-fill { fill:var(--sc-teal) }
.report-row { display:flex;gap:12px;align-items:flex-start;padding:12px;border:1px solid var(--sc-border);border-radius:8px;background:var(--sc-bg);margin-bottom:10px; }
.report-icon { width:38px;height:38px;border-radius:8px;background:#fff;border:1px solid var(--sc-border);display:flex;align-items:center;justify-content:center;color:var(--sc-teal);flex:0 0 auto; }
.report-title { font-weight:700;font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis; }
.report-meta, .report-file-name { font-size:11px;color:var(--sc-muted);margin-top:2px;word-break:break-word; }
.report-actions { display:flex;gap:6px;align-items:center;flex:0 0 auto; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.min.js"></script>
<script>
const encounterId = {{ $encounter->id }};
const medicines = {!! json_encode($medicines) !!};
const PATIENT_ALLERGIES_ENC = @json($patientAllergies);
const activePrescribedMeds = {!! json_encode($activePrescribedMeds) !!};

// ---- VITALS ----
function resetVitalsNormal() {
    const setVal = (name, val) => { const el = document.querySelector('[name="' + name + '"]'); if (el) el.value = val; };
    setVal('bp_systolic', 120); setVal('bp_diastolic', 80); setVal('pulse', 72);
    setVal('temperature', 98.6); setVal('spo2', 99); setVal('respiratory_rate', 16);
    setVal('blood_glucose', 100);
}
function calcBMI() {
    const w = parseFloat(document.getElementById('v-weight').value);
    const h = parseFloat(document.getElementById('v-height').value);
    const d = document.getElementById('bmi-display');
    if (w && h) {
        const bmi = (w / ((h/100)**2)).toFixed(1);
        let cat = bmi < 18.5 ? 'Underweight' : bmi < 25 ? 'Normal' : bmi < 30 ? 'Overweight' : 'Obese';
        d.textContent = `BMI: ${bmi} (${cat})`;
    }
}

function saveVitals(e) {
    e.preventDefault();
    const data = {};
    new FormData(e.target).forEach((v,k) => data[k] = v);
    $.post(`{{ url("/encounters") }}/${encounterId}/vitals`, data, function(res) {
        if (res.success) {
            markSaved(e.target.querySelector('[type="submit"]'), 'Saved Vitals');
            setTimeout(() => location.reload(), 800);
        }
    }).fail(() => alert('Failed to save vitals'));
}

// ---- CLINICAL ----
let clinicalSnapshot = {};

function enterClinicalEditMode() {
    clinicalSnapshot = {};
    CLINICAL_QUILL_FIELDS.forEach(({ id, key }) => {
        clinicalSnapshot[key] = clinicalQuills[key].root.innerHTML;
        clinicalQuills[key].enable(true);
        document.getElementById(id).classList.remove('read-only');
    });
    document.getElementById('btn-clinical-edit').style.display = 'none';
    document.getElementById('clinical-form-actions').style.display = 'flex';
}

function cancelClinicalEdit() {
    CLINICAL_QUILL_FIELDS.forEach(({ id, key }) => {
        if (clinicalSnapshot[key] !== undefined) clinicalQuills[key].root.innerHTML = clinicalSnapshot[key];
    });
    // take a fresh snapshot after discard
    CLINICAL_QUILL_FIELDS.forEach(({ key }) => { clinicalSnapshot[key] = clinicalQuills[key].root.innerHTML; });
}

function collectClinicalData(form) {
    const data = {};
    new FormData(form).forEach((v,k) => data[k] = v);
    if (!form.querySelector('[name="is_mlc"]').checked) data['is_mlc'] = 0;
    Object.entries(clinicalQuills).forEach(([key, q]) => { data[key] = q.root.innerHTML; });
    return data;
}

function clinicalForm() { return document.querySelector('#tab-clinical form'); }

function saveClinical(e) {
    e.preventDefault();
    const data = collectClinicalData(e.target);
    const sent = JSON.stringify(data);
    $.post(`{{ url("/encounters") }}/${encounterId}/clinical`, data, function(res) {
        if (res.success) {
            markSaved(e.target.querySelector('[type="submit"]'), 'Saved Clinical Notes');
            clinicalAfterSave(sent);
        }
    }).fail(function() {
        showToast('Failed to save clinical notes. Please try again.', 'error');
    });
}

function clinicalAfterSave(sent) {
    _clinicalLastSaved = sent;
    // refresh snapshot so Discard reverts to this saved state
    CLINICAL_QUILL_FIELDS.forEach(({ key }) => { clinicalSnapshot[key] = clinicalQuills[key].root.innerHTML; });
    clearClinicalDraft();
    setClinicalAutoSaveStatus('Saved ' + new Date().toLocaleTimeString([], {hour:'2-digit', minute:'2-digit', second:'2-digit'}));
}

// ---- CLINICAL: auto-save to the server every 3 s while typing (toggle remembered per browser) ----
const CLINICAL_AUTOSAVE_MS = 3000;
let _clinicalLastSaved = null;     // JSON of the last data the server has
let _clinicalAutoSaveOn = true;
let _clinicalAutoSaveBusy = false;

function setClinicalAutoSaveStatus(text, isError) {
    const el = document.getElementById('clinical-autosave-status');
    if (!el) return;
    el.textContent = text;
    el.style.color = isError ? 'var(--sc-red)' : 'var(--sc-muted)';
}

function setClinicalAutoSave(on) {
    _clinicalAutoSaveOn = !!on;
    try { localStorage.setItem('clinical_autosave', on ? '1' : '0'); } catch (e) {}
    setClinicalAutoSaveStatus(on ? '' : 'Auto-save off');
}

function clinicalAutoSaveTick() {
    if (!_clinicalAutoSaveOn || _clinicalAutoSaveBusy || _clinicalLastSaved === null) return;
    const form = clinicalForm();
    if (!form) return;
    const data = collectClinicalData(form);
    const sent = JSON.stringify(data);
    if (sent === _clinicalLastSaved) return;
    _clinicalAutoSaveBusy = true;
    setClinicalAutoSaveStatus('Saving…');
    data.autosave = 1;
    $.post(`{{ url("/encounters") }}/${encounterId}/clinical`, data, function(res) {
        if (res.success) clinicalAfterSave(sent);
        else setClinicalAutoSaveStatus('Auto-save failed', true);
    }).fail(function(xhr) {
        setClinicalAutoSaveStatus(xhr.status === 419 ? 'Session expired — reload page' : 'Auto-save failed, retrying…', true);
    }).always(function() { _clinicalAutoSaveBusy = false; });
}

function initClinicalAutoSave() {
    try { _clinicalAutoSaveOn = localStorage.getItem('clinical_autosave') !== '0'; } catch (e) {}
    const t = document.getElementById('clinical-autosave-toggle');
    if (t) t.checked = _clinicalAutoSaveOn;
    if (!_clinicalAutoSaveOn) setClinicalAutoSaveStatus('Auto-save off');
    @if(!$encounter->trashed())
    // Baseline after Quill has normalised the loaded HTML, so an untouched page never posts.
    setTimeout(function () {
        const form = clinicalForm();
        if (form) _clinicalLastSaved = JSON.stringify(collectClinicalData(form));
        setInterval(clinicalAutoSaveTick, CLINICAL_AUTOSAVE_MS);
        initSectionAutoSave();
    }, 600);
    @endif
}

// ---- Auto-save for Vitals, Diagnoses and speciality forms: same 3 s timer and on/off switch as Clinical ----
// Each saver: collect() -> payload string (null = nothing to save now), send(payload) -> jqXHR,
// status element next to its Save button. The first payload seen is the baseline, so opening a form never posts.
const _sectionSavers = [];

function addSectionSaver(saver, saveBtn) {
    saver.last = null;
    saver.busy = false;
    if (saveBtn) {
        saver.statusEl = document.createElement('span');
        saver.statusEl.style.cssText = 'align-self:center;margin:0 10px;font-size:12px;color:var(--sc-muted)';
        saveBtn.after(saver.statusEl);
    }
    _sectionSavers.push(saver);
    return saver;
}

function sectionSaverStatus(saver, text, isError) {
    if (!saver.statusEl) return;
    saver.statusEl.textContent = text;
    saver.statusEl.style.color = isError ? 'var(--sc-red)' : 'var(--sc-muted)';
}

// After a manual Save, the saved payload becomes the new baseline (no duplicate auto-save).
function sectionSaverSynced(saver) { if (saver) { const p = saver.collect(); if (p !== null) saver.last = p; } }

function sectionSaversTick() {
    document.querySelectorAll('form.spec-form').forEach(registerSpecFormSaver);   // forms added later
    if (!_clinicalAutoSaveOn) return;
    _sectionSavers.forEach(saver => {
        if (saver.busy) return;
        const payload = saver.collect();
        if (payload === null) return;
        if (saver.last === null) { saver.last = payload; return; }
        if (payload === saver.last) return;
        saver.busy = true;
        sectionSaverStatus(saver, 'Saving…');
        saver.send(payload).done(res => {
            if (res && res.success) {
                saver.last = payload;
                sectionSaverStatus(saver, 'Auto-saved ' + new Date().toLocaleTimeString([], {hour:'2-digit', minute:'2-digit', second:'2-digit'}));
                saver.after?.(res);
            } else sectionSaverStatus(saver, 'Auto-save failed', true);
        }).fail(xhr => {
            sectionSaverStatus(saver, xhr.status === 419 ? 'Session expired — reload page' : (xhr.status === 422 ? 'Not saved — check the values' : 'Auto-save failed, retrying…'), true);
        }).always(() => { saver.busy = false; });
    });
}

let _vitalsSaver = null, _diagSaver = null;

function initSectionAutoSave() {
    const vf = document.getElementById('vitals-form');
    if (vf) _vitalsSaver = addSectionSaver({
        collect: () => $(vf).serialize(),
        send: payload => $.post(`{{ url("/encounters") }}/${encounterId}/vitals`, payload + '&autosave=1'),
        after: () => refreshVitalBoxes(vf),
    }, vf.querySelector('[type="submit"]'));

    const dc = document.getElementById('diagnoses-container');
    if (dc) _diagSaver = addSectionSaver({
        // only while editing, and only rows that have a name (the server needs at least one)
        collect: () => {
            if (dc.style.display === 'none') return null;
            const rows = collectDiagnosisRows();
            return rows.length ? JSON.stringify(rows) : null;
        },
        send: payload => $.post(`{{ url("/encounters") }}/${encounterId}/diagnoses`, { diagnoses: JSON.parse(payload), autosave: 1 }),
        after: () => freezeDiagSnapshot(),
    }, document.getElementById('diag-save-btn'));

    document.querySelectorAll('form.spec-form').forEach(registerSpecFormSaver);
    setInterval(sectionSaversTick, CLINICAL_AUTOSAVE_MS);
}

function registerSpecFormSaver(form) {
    if (form._saver) return;
    const btn = form.querySelector('[type="submit"]');
    if (!btn) { form._saver = true; return; }          // read-only form
    form._saver = addSectionSaver({
        collect: () => $(form).serialize(),
        send: payload => $.post(`{{ url('/encounters') }}/${encounterId}/forms/${form.dataset.form}`, payload + '&autosave=1'),
    }, btn);
}

// Vitals: show the auto-saved values in the boxes above the form (Save Vitals still reloads the page).
function refreshVitalBoxes(form) {
    const v = n => (form.querySelector(`[name="${n}"]`)?.value || '').trim();
    const boxes = document.querySelectorAll('#vitals-card .vital-box .vb-val');
    if (boxes.length < 8) return;
    const w = parseFloat(v('weight')), h = parseFloat(v('height'));
    const vals = [
        v('bp_systolic') && v('bp_diastolic') ? v('bp_systolic') + '/' + v('bp_diastolic') : '—',
        v('pulse') || '—', v('temperature') || '—', v('spo2') ? v('spo2') + '%' : '—',
        v('weight') || '—', v('height') || '—', (w && h) ? (w / ((h / 100) ** 2)).toFixed(2) : '—', v('blood_glucose') || '—',
    ];
    boxes.forEach((b, i) => { b.textContent = vals[i]; });
}

// Diagnoses: Cancel reverts to diagSnapshot (container HTML), so write the typed values into the
// HTML attributes and re-take the snapshot: Cancel then goes back to the last auto-saved state.
function freezeDiagSnapshot() {
    const dc = document.getElementById('diagnoses-container');
    dc.querySelectorAll('input').forEach(i => i.setAttribute('value', i.value));
    dc.querySelectorAll('select').forEach(sel => [...sel.options].forEach(o => o.toggleAttribute('selected', o.selected)));
    diagSnapshot = dc.innerHTML;
}

// ---- CLINICAL: topic links (one below the other; click opens the form) ----
function toggleChTopic(link) {
    const t = link.closest('.ch-topic');
    t.classList.toggle('open');
    if (t.classList.contains('open')) {
        const q = clinicalQuills[t.dataset.topic];
        if (q) setTimeout(() => q.focus(), 0);
    }
    return false;
}

function setAllChTopics(open) {
    document.querySelectorAll('#tab-clinical .ch-topic').forEach(t => t.classList.toggle('open', open));
    return false;
}

function updateChPreview(key) {
    const t = document.querySelector(`#tab-clinical .ch-topic[data-topic="${key}"]`);
    if (!t) return;
    let text = '';
    if (clinicalQuills[key]) {
        text = clinicalQuills[key].getText().replace(/\s+/g, ' ').trim();
    } else if (key === 'allergies') {
        text = [...t.querySelectorAll('.allergy-tag')].map(a => a.firstChild.textContent.trim()).filter(Boolean).join(', ');
    } else if (key === 'follow_up') {
        const f = clinicalForm();
        const ins = (f.querySelector('[name="follow_up_instructions"]').value || '').replace(/\s+/g, ' ').trim();
        const d = f.querySelector('[name="follow_up_date"]').value;
        text = [d ? 'on ' + new Date(d + 'T00:00').toLocaleDateString([], {day:'2-digit', month:'short', year:'numeric'}) : '', ins].filter(Boolean).join(' — ');
    }
    const el = t.querySelector('.ch-topic-preview');
    el.textContent = text ? (text.length > 140 ? text.slice(0, 140) + '…' : text) : 'not filled';
    el.classList.toggle('empty', !text);
}

function updateAllChPreviews() {
    document.querySelectorAll('#tab-clinical .ch-topic').forEach(t => updateChPreview(t.dataset.topic));
}

// ---- CLINICAL: print all or some sections ----
function toggleClinicalPrintMenu(ev) {
    ev.stopPropagation();
    const m = document.getElementById('clinical-print-menu');
    m.style.display = m.style.display === 'none' ? 'block' : 'none';
}
document.addEventListener('click', function (ev) {
    const m = document.getElementById('clinical-print-menu');
    if (m && m.style.display !== 'none' && !m.contains(ev.target)) m.style.display = 'none';
});

function printClinicalSections() {
    const secs = [...document.querySelectorAll('.cp-sec:checked')].map(c => c.value);
    if (!secs.length) { showToast('Tick at least one section to print.', 'error'); return; }
    let url = `{{ route('encounters.clinical-print', $encounter) }}?s=` + secs.join(',');
    if (document.querySelector('input[name="cp-scope"]:checked')?.value === 'all') {
        url += '&all=1&order=' + document.getElementById('cp-order').value;
    }
    const win = window.open('about:blank', '_blank');   // open now, so the popup isn't blocked
    const go = () => { if (win) win.location = url; else location.href = url; };
    document.getElementById('clinical-print-menu').style.display = 'none';
    // Print what is on screen: save unsaved edits first.
    const form = clinicalForm();
    const data = collectClinicalData(form);
    const sent = JSON.stringify(data);
    if (sent === _clinicalLastSaved) { go(); return; }
    data.autosave = 1;
    $.post(`{{ url("/encounters") }}/${encounterId}/clinical`, data, function (res) {
        if (res.success) clinicalAfterSave(sent);
        go();
    }).fail(function () {
        showToast('Could not save latest edits — printing the last saved version.', 'error');
        go();
    });
}

// ── CLINICAL NOTES — VOICE / AUTO-SAVE / CHIPS ─────────────────────────────
const _clinicalDraftKey = 'enc_draft_' + {{ $encounter->id }};
let _clinicalAutoSaveTimer = null;
let _voiceRecognition = null;
let _voiceTargetKey = null;

function voiceSupported() {
    return typeof window.SpeechRecognition !== 'undefined' || typeof window.webkitSpeechRecognition !== 'undefined';
}

function toggleVoice(key, btn) {
    if (!voiceSupported()) return;
    if (_voiceRecognition && _voiceTargetKey === key) {
        _voiceRecognition.stop();
        return;
    }
    if (_voiceRecognition) { try { _voiceRecognition.stop(); } catch(e){} }
    const Rec = window.SpeechRecognition || window.webkitSpeechRecognition;
    const r = new Rec();
    r.continuous = true;
    r.interimResults = false;
    r.lang = 'en-IN';
    _voiceTargetKey = key;
    _voiceRecognition = r;
    document.querySelectorAll('.cq-mic-btn').forEach(b => b.classList.remove('listening'));
    btn.classList.add('listening');

    r.onresult = function (e) {
        const q = clinicalQuills[key];
        if (!q) return;
        let chunk = '';
        for (let i = e.resultIndex; i < e.results.length; i++) {
            if (e.results[i].isFinal) chunk += e.results[i][0].transcript + ' ';
        }
        if (chunk) insertIntoQuill(q, chunk.trim());
    };
    r.onend = function () {
        btn.classList.remove('listening');
        if (_voiceTargetKey === key) { _voiceRecognition = null; _voiceTargetKey = null; }
    };
    r.onerror = function (ev) {
        btn.classList.remove('listening');
        if (ev.error === 'not-allowed' || ev.error === 'service-not-allowed') {
            showToast('Microphone access denied — enable in browser settings.', 'error');
        }
    };
    try { r.start(); } catch (e) { btn.classList.remove('listening'); }
}

function insertIntoQuill(q, text) {
    if (!q || !text) return;
    q.focus();
    const sel = q.getSelection();
    const at  = sel ? sel.index : q.getLength();
    // For every clinical field (chief complaint, HPI, PMH, FH, SH, examination,
    // impression, plan), each chip click should land on its own line with a
    // separating comma — so consecutive clicks auto-format as
    //   GC: fair, conscious, oriented,
    //   CVS: S1 S2 normal, no murmur,
    //   RS: clear, equal air entry
    // …instead of running into one long line. Non-clinical Quills (anywhere
    // else) keep the original "join with a space" behaviour.
    const isClinical = (typeof clinicalQuills !== 'undefined') && clinicalQuills
        && Object.values(clinicalQuills).some(qq => qq === q);
    if (isClinical) {
        const before = at > 0 ? q.getText(0, at) : '';
        const trimmed = before.replace(/\s+$/, '');
        let prefix = '';
        if (trimmed.length > 0) {
            const lastCh = trimmed.slice(-1);
            // Comma + newline if the previous line doesn't already end on punctuation; else just newline.
            prefix = /[,.;:!?\n]/.test(lastCh) ? '\n' : ',\n';
        }
        q.insertText(at, prefix + text);
        q.setSelection(at + prefix.length + text.length, 0);
        return;
    }
    const cur = q.getText(Math.max(0, at - 1), 1);
    const prefix = cur && !/\s/.test(cur) ? ' ' : '';
    q.insertText(at, prefix + text + ' ');
    q.setSelection(at + prefix.length + text.length + 1, 0);
}

function saveClinicalDraft() {
    try {
        const snapshot = {};
        Object.entries(clinicalQuills).forEach(([k, q]) => { snapshot[k] = q.root.innerHTML; });
        sessionStorage.setItem(_clinicalDraftKey, JSON.stringify({
            saved_at: Date.now(),
            content: snapshot,
        }));
    } catch (e) { /* sessionStorage full or disabled — ignore */ }
}

function clearClinicalDraft() { try { sessionStorage.removeItem(_clinicalDraftKey); } catch(e) {} }

function maybeOfferDraftRecovery() {
    try {
        const raw = sessionStorage.getItem(_clinicalDraftKey);
        if (!raw) return;
        const draft = JSON.parse(raw);
        if (!draft || !draft.content) return;

        // Only show recovery if at least one field differs from the rendered (server) state.
        let differs = false;
        Object.entries(draft.content).forEach(([k, html]) => {
            const q = clinicalQuills[k];
            if (q && (q.root.innerHTML || '').trim() !== (html || '').trim()) differs = true;
        });
        if (!differs) return;

        const banner = document.getElementById('clinical-draft-banner');
        const ts = new Date(draft.saved_at).toLocaleString();
        document.getElementById('clinical-draft-when').textContent = ts;
        banner.style.display = '';
        banner.dataset.draft = JSON.stringify(draft.content);
    } catch(e) {}
}

function restoreClinicalDraft() {
    const banner = document.getElementById('clinical-draft-banner');
    try {
        const content = JSON.parse(banner.dataset.draft || '{}');
        Object.entries(content).forEach(([k, html]) => {
            const q = clinicalQuills[k];
            if (q && typeof html === 'string') q.root.innerHTML = html;
        });
        showToast('Draft restored. Remember to Save Clinical Notes.', 'success');
    } catch(e) {}
    banner.style.display = 'none';
}

function discardClinicalDraft() {
    document.getElementById('clinical-draft-banner').style.display = 'none';
    clearClinicalDraft();
}

// MLC toggle
document.getElementById('encMlcCheck')?.addEventListener('change', function() {
    document.getElementById('encMlcFields').style.display = this.checked ? '' : 'none';
});

// ---- DIAGNOSES (DB-backed ICD-10 autocomplete; catalog lives in icd_codes table) ----
function icdDropHTML() {
    return `<div class="icd-drop" style="display:none;position:absolute;z-index:999;top:100%;left:0;right:0;background:#fff;border:1px solid #d0d5dd;border-top:none;border-radius:0 0 6px 6px;max-height:200px;overflow-y:auto;box-shadow:0 4px 12px rgba(0,0,0,.12)"></div>`;
}

const ICD_SEARCH_URL = @json(route('icd.search'));
function attachIcdAC(nameInput, codeInput) {
    nameInput.parentElement.style.position = 'relative';
    if (!nameInput.parentElement.querySelector('.icd-drop')) {
        nameInput.insertAdjacentHTML('afterend', icdDropHTML());
    }
    const drop = nameInput.parentElement.querySelector('.icd-drop');
    let activeAbort = null;
    let debounceTimer = null;
    nameInput.addEventListener('input', function() {
        const q = this.value.trim();
        if (debounceTimer) clearTimeout(debounceTimer);
        if (!q || q.length < 2) { drop.style.display = 'none'; return; }
        // Debounce 150 ms so we don't spam the server on fast typing.
        debounceTimer = setTimeout(async () => {
            if (activeAbort) activeAbort.abort();
            activeAbort = new AbortController();
            try {
                const resp = await fetch(ICD_SEARCH_URL + '?q=' + encodeURIComponent(q), { signal: activeAbort.signal, headers: { 'Accept': 'application/json' } });
                if (!resp.ok) { drop.style.display = 'none'; return; }
                const matches = await resp.json();
                if (!Array.isArray(matches) || !matches.length) { drop.style.display = 'none'; return; }
                drop.innerHTML = matches.map(x =>
                    `<div style="padding:7px 12px;cursor:pointer;border-bottom:1px solid #f0f0f0;font-size:13px" class="icd-item"
                          onmousedown="selectIcd(this,'${x.c}','${(x.d||'').replace(/'/g,"\\'")}')">
                        <span style="font-weight:600;color:var(--sc-navy)">${x.c}</span> — ${x.d}
                        ${x.cat ? `<span style="font-size:10px;color:#94a3b8;margin-left:6px">${x.cat}</span>` : ''}
                     </div>`
                ).join('');
                drop.style.display = 'block';
            } catch (e) {
                if (e.name !== 'AbortError') console.error('ICD search failed', e);
            }
        }, 150);
    });
    nameInput.addEventListener('blur', () => setTimeout(() => drop.style.display = 'none', 200));
    nameInput._codeInput = codeInput;
}

function selectIcd(el, code, desc) {
    const wrap = el.closest('.form-group');
    const nameIn = wrap.querySelector('[name="diagnoses[name][]"]');
    if (nameIn) { nameIn.value = desc; if (nameIn._codeInput) nameIn._codeInput.value = code; }
    el.closest('.icd-drop').style.display = 'none';
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.diagnosis-row').forEach(row => {
        const n = row.querySelector('[name="diagnoses[name][]"]');
        const c = row.querySelector('[name="diagnoses[icd_code][]"]');
        if (n && c) attachIcdAC(n, c);
    });
    enterDiagReadMode();
});

function addDiagnosisRow() {
    document.getElementById('no-diag')?.remove();
    const c = document.getElementById('diagnoses-container');
    const row = document.createElement('div');
    row.className = 'diagnosis-row';
    row.style.cssText = 'display:grid;grid-template-columns:2fr 1fr 1fr 1fr auto;gap:10px;align-items:end;margin-bottom:10px;padding:12px;background:var(--sc-bg);border-radius:8px;border:1px solid var(--sc-border)';
    row.innerHTML = `
        <div class="form-group" style="margin:0;position:relative"><label class="form-label">Diagnosis Name</label><input type="text" name="diagnoses[name][]" class="form-control" placeholder="Type to search ICD-10..." autocomplete="off">${icdDropHTML()}</div>
        <div class="form-group" style="margin:0"><label class="form-label">ICD Code</label><input type="text" name="diagnoses[icd_code][]" class="form-control" placeholder="e.g. J06.9"></div>
        <div class="form-group" style="margin:0"><label class="form-label">Type</label><select name="diagnoses[type][]" class="form-control"><option>Primary</option><option>Secondary</option><option>Differential</option></select></div>
        <div class="form-group" style="margin:0"><label class="form-label">Status</label><select name="diagnoses[status][]" class="form-control"><option>Active</option><option>Resolved</option><option>Chronic</option></select></div>
        <button type="button" onclick="this.closest('.diagnosis-row').remove()" class="btn btn-icon btn-sm btn-outline" style="color:#94a3b8;border-color:#e2e8f0"><i class="fa-solid fa-trash"></i></button>
    `;
    c.appendChild(row);
    const nameIn = row.querySelector('[name="diagnoses[name][]"]');
    const codeIn = row.querySelector('[name="diagnoses[icd_code][]"]');
    attachIcdAC(nameIn, codeIn);
    nameIn.focus();
}

// ---- DIAGNOSIS mode switching ----
const DIAG_TYPE_BADGE  = { Primary:'badge-teal', Secondary:'badge-amber', Differential:'badge-outline' };
const DIAG_STATUS_BADGE = { Active:'badge-green', Resolved:'badge-outline', Chronic:'badge-amber' };
let diagSnapshot = '';

function enterDiagReadMode() {
    const container = document.getElementById('diagnoses-container');
    const readView  = document.getElementById('diag-read-view');
    const rows = container.querySelectorAll('.diagnosis-row');
    if (!rows.length) {
        readView.innerHTML = '<div style="padding:16px;color:var(--sc-muted);text-align:center;font-size:13px">No diagnoses recorded yet.</div>';
    } else {
        let html = '';
        rows.forEach(row => {
            const name   = row.querySelector('[name="diagnoses[name][]"]')?.value || '';
            const code   = row.querySelector('[name="diagnoses[icd_code][]"]')?.value || '';
            const type   = row.querySelector('[name="diagnoses[type][]"]')?.value || '';
            const status = row.querySelector('[name="diagnoses[status][]"]')?.value || '';
            if (!name) return;
            html += `<div class="diag-read-row">
                <div><span class="diag-read-name">${escapeHtml(name)}</span>${code ? `<span class="diag-read-code">${escapeHtml(code)}</span>` : ''}</div>
                <div style="display:flex;gap:6px">
                    <span class="badge ${DIAG_TYPE_BADGE[type]||'badge-outline'}">${escapeHtml(type)}</span>
                    <span class="badge ${DIAG_STATUS_BADGE[status]||'badge-outline'}">${escapeHtml(status)}</span>
                </div>
            </div>`;
        });
        readView.innerHTML = html || '<div style="padding:16px;color:var(--sc-muted);text-align:center;font-size:13px">No diagnoses recorded yet.</div>';
    }
    readView.style.display  = 'block';
    container.style.display = 'none';
    document.getElementById('btn-diag-edit').style.display  = '';
    document.getElementById('btn-diag-add').style.display   = 'none';
    document.getElementById('diag-form-actions').style.display = 'none';
}

function enterDiagEditMode() {
    diagSnapshot = document.getElementById('diagnoses-container').innerHTML;
    document.getElementById('diag-read-view').style.display  = 'none';
    document.getElementById('diagnoses-container').style.display = 'block';
    document.getElementById('btn-diag-edit').style.display  = 'none';
    document.getElementById('btn-diag-add').style.display   = '';
    document.getElementById('diag-form-actions').style.display = 'flex';
    // Re-attach ICD autocomplete to rows already in the container
    document.querySelectorAll('#diagnoses-container .diagnosis-row').forEach(row => {
        const n = row.querySelector('[name="diagnoses[name][]"]');
        const c = row.querySelector('[name="diagnoses[icd_code][]"]');
        if (n && c && !n._codeInput) attachIcdAC(n, c);
    });
}

function cancelDiagEdit() {
    document.getElementById('diagnoses-container').innerHTML = diagSnapshot;
    document.querySelectorAll('#diagnoses-container .diagnosis-row').forEach(row => {
        const n = row.querySelector('[name="diagnoses[name][]"]');
        const c = row.querySelector('[name="diagnoses[icd_code][]"]');
        if (n && c) attachIcdAC(n, c);
    });
    enterDiagReadMode();
}

function collectDiagnosisRows() {
    const diagnoses = [];
    document.querySelectorAll('#diagnoses-container .diagnosis-row').forEach(row => {
        const name = row.querySelector('[name="diagnoses[name][]"]')?.value;
        if (name) diagnoses.push({
            name,
            icd_code: row.querySelector('[name="diagnoses[icd_code][]"]')?.value || '',
            type: row.querySelector('[name="diagnoses[type][]"]')?.value,
            status: row.querySelector('[name="diagnoses[status][]"]')?.value,
        });
    });
    return diagnoses;
}

function saveDiagnoses() {
    const rows = document.querySelectorAll('.diagnosis-row');
    if (!rows.length) { alert('Add at least one diagnosis'); return; }
    const diagnoses = collectDiagnosisRows();
    $.post(`{{ url("/encounters") }}/${encounterId}/diagnoses`, { diagnoses }, function(res) {
        if (res.success) {
            sectionSaverSynced(_diagSaver);
            markSaved(document.getElementById('diag-save-btn'), 'Saved Diagnoses');
            setTimeout(enterDiagReadMode, 1200);
            fetchInvestigationSuggestions();
        }
    }).fail(() => alert('Failed to save diagnoses'));
}

// ---- PRESCRIPTION ----
let rxCount = 0;
function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, char => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;',
    }[char]));
}

// DB-backed prescription dropdowns. RX_OPTS is keyed by DB field; "➕ Add new…"
// prompts for a value, persists it (so it returns next time) and selects it.
const RX_OPTS = @json($rxOptions ?? new \stdClass);   // {dose:[{value,label}], frequency:[…], …}
const RX_OPTIONS_URL = @json(route('rx-options.store'));
const RX_NEWMED_URL  = @json(route('pharmacy.medicines.quick-create'));
const RX_CSRF        = @json(csrf_token());
const RX_DEF = {
    dose:  { db: 'dose',        label: 'dose' },
    freq:  { db: 'frequency',   label: 'frequency' },
    route: { db: 'route',       label: 'route' },
    dur:   { db: 'duration',    label: 'duration (days)' },
    instr: { db: 'instruction', label: 'instruction' },
};
function rxSelectHtml(base, idx) {
    const db = RX_DEF[base].db;
    const def = (base === 'dur') ? '5' : null;
    const list = RX_OPTS[db] || [];
    let opts = (base === 'dose' || base === 'instr') ? '<option value="">—</option>' : '';
    opts += list.map(o => `<option value="${escapeHtml(o.value)}" ${def !== null && String(o.value) === def ? 'selected' : ''}>${escapeHtml(o.label)}</option>`).join('');
    return `<select id="${base}-${idx}" class="form-control" onchange="rxAddNew('${base}',${idx})">${opts}<option value="__add__">➕ Add new…</option></select>`;
}
// Set a select's value, adding a temporary option if the value isn't listed
// (e.g. the dose auto-filled from the medicine, or a saved custom value).
function rxSetVal(id, val) {
    const el = document.getElementById(id);
    if (!el) return;
    val = (val == null) ? '' : String(val);
    if (el.tagName === 'SELECT') {
        if (val !== '' && ![...el.options].some(o => o.value === val)) {
            el.add(new Option(val, val, true, true), el.querySelector('option[value="__add__"]'));
        }
        el.value = val;
    } else { el.value = val; }
}
function rxAddNew(base, idx) {
    const sel = document.getElementById(`${base}-${idx}`);
    if (!sel || sel.value !== '__add__') return;
    const val = (prompt('Add a new ' + RX_DEF[base].label + ' (saved for next time):') || '').trim();
    sel.value = '';
    if (!val) return;
    if (base === 'dur' && !/^\d+$/.test(val)) { alert('Duration must be a whole number of days.'); return; }
    rxSetVal(`${base}-${idx}`, val);
    const db = RX_DEF[base].db;
    if (!(RX_OPTS[db] || []).some(o => String(o.value) === val)) (RX_OPTS[db] = RX_OPTS[db] || []).push({ value: val, label: val });
    fetch(RX_OPTIONS_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': RX_CSRF, 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify({ field: db, value: val })
    }).catch(() => {});
}
// "➕ Add new medicine (not in stock)…" — quick-creates a Medicine (saved to
// the DB, so it appears in the list next time) and selects it.
function rxNewMedicine(idx) {
    const $sel = $(`#med-sel-${idx}`);
    const name = (prompt('New medicine name (not in stock):') || '').trim();
    if (!name) { $sel.val('').trigger('change'); return; }
    const form = (prompt('Dosage form (Tablet / Syrup / Injection / etc.):', 'Tablet') || 'Tablet').trim();
    fetch(RX_NEWMED_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': RX_CSRF, 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify({ name: name, dosage_form: form })
    }).then(r => r.ok ? r.json() : Promise.reject(r)).then(m => {
        const selEl = document.getElementById(`med-sel-${idx}`);
        const opt = new Option(m.name, m.id, true, true);
        Object.assign(opt.dataset, { stock: '0', stockCount: '0', stockStatus: 'out_of_stock', side: '', contra: '', interactions: '', generic: '', unit: 'Unit', formulation: form, strength: '' });
        selEl.appendChild(opt);
        $sel.trigger('change');
    }).catch(() => { alert('Could not add the medicine (is the Pharmacy module enabled?). You can add it via Pharmacy → Medicines.'); $sel.val('').trigger('change'); });
}

function addRxItem() {
    rxCount++;
    const idx = rxCount;
    const c = document.getElementById('rx-items-container');
    const div = document.createElement('div');
    div.id = `rx-item-${idx}`;
    div.className = 'rx-row';
    div.innerHTML = `
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
            <strong style="font-size:13px">Medicine #${idx}</strong>
            <button type="button" onclick="document.getElementById('rx-item-${idx}').remove()" class="btn btn-icon btn-sm btn-outline" style="color:#94a3b8;border-color:#e2e8f0"><i class="fa-solid fa-times"></i></button>
        </div>
        <div class="form-row cols-2" style="margin-bottom:8px">
            <div class="form-group" style="margin:0">
                <label class="form-label">Medicine <span class="req">*</span></label>
                <select id="med-sel-${idx}" class="form-control select2" style="width:100%" onchange="onMedSelect(${idx})">
                    <option value="">Search & Select Medicine...</option>
                    ${medicines.map(m => `<option value="${m.id}"
                        data-stock="${m.in_stock ? 1 : 0}"
                        data-stock-count="${m.stock ?? m.quantity ?? 0}"
                        data-stock-status="${escapeHtml(m.stock_status || 'out_of_stock')}"
                        data-side="${escapeHtml(m.side_effects)}"
                        data-contra="${escapeHtml(m.contraindications)}"
                        data-interactions="${escapeHtml(m.drug_interactions)}"
                        data-generic="${escapeHtml(m.generic_name)}"
                        data-unit="${escapeHtml(m.unit || 'Unit')}"
                        data-formulation="${escapeHtml(m.dosage_form)}"
                        data-strength="${escapeHtml(m.strength)}">${escapeHtml(m.name || "Unknown")}${m.strength ? " - " + escapeHtml(m.strength) : ""}${m.generic_name ? " [" + escapeHtml(m.generic_name) + "]" : ""}</option>`).join('')}
                    <option value="__newmed__">➕ Add new medicine (not in stock)…</option>
                </select>
            </div>
            <div class="form-group" style="margin:0">
                <label class="form-label">Dose</label>
                ${rxSelectHtml('dose', idx)}
            </div>
        </div>
        <div class="form-row" style="grid-template-columns:1fr 1fr 1fr 1fr;gap:10px;margin-bottom:8px">
            <div class="form-group" style="margin:0">
                <label class="form-label">Frequency</label>
                ${rxSelectHtml('freq', idx)}
            </div>
            <div class="form-group" style="margin:0">
                <label class="form-label">Route</label>
                ${rxSelectHtml('route', idx)}
            </div>
            <div class="form-group" style="margin:0">
                <label class="form-label">Duration (Days)</label>
                ${rxSelectHtml('dur', idx)}
            </div>
            <div class="form-group" style="margin:0">
                <label class="form-label">Instructions</label>
                ${rxSelectHtml('instr', idx)}
            </div>
        </div>
        <div id="generic-name-${idx}" style="display:none;font-size:12px;color:#64748b;padding:4px 0 6px 2px"></div>
        <div id="stock-info-${idx}" style="display:none"></div>
        <div id="side-effects-${idx}" style="display:none"></div>
    `;
    c.appendChild(div);
    $(`#med-sel-${idx}`).select2({ placeholder: 'Search & Select Medicine...', width: '100%' });
    $(`#med-sel-${idx}`).on('change', function() { onMedSelect(idx); });
}

function onMedSelect(idx) {
    const sel = document.getElementById(`med-sel-${idx}`);
    if (sel && sel.value === '__newmed__') { rxNewMedicine(idx); return; }
    const opt = sel.options[sel.selectedIndex];
    if (!opt.value) return;

    const stockCount = parseInt(opt.dataset.stockCount || '0', 10);
    const stockStatus = opt.dataset.stockStatus;
    const sideEffects = escapeHtml(opt.dataset.side);
    const contra = escapeHtml(opt.dataset.contra);
    const interactions = escapeHtml(opt.dataset.interactions);
    const generic = opt.dataset.generic || '';
    const unit = opt.dataset.unit || 'Unit';
    const formulation = opt.dataset.formulation || '';
    const strength = opt.dataset.strength || '';

    // Show generic name
    const gnDiv = document.getElementById(`generic-name-${idx}`);
    if (generic) {
        gnDiv.innerHTML = `<i class="fa-solid fa-flask" style="font-size:10px"></i> <em>Generic: <strong>${escapeHtml(generic)}</strong></em>`;
        gnDiv.style.display = 'block';
    } else {
        gnDiv.style.display = 'none';
    }

    // Set default dose from strength (dose is a select — add as option if needed)
    rxSetVal(`dose-${idx}`, `1 ${unit}`);

    // Stock status
    const stockDiv = document.getElementById(`stock-info-${idx}`);
    if (stockStatus === 'out_of_stock') {
        stockDiv.innerHTML = `<div style="background:rgba(245,166,35,.1);border:1px solid rgba(245,166,35,.3);border-radius:6px;padding:8px 12px;margin-bottom:8px;font-size:12px;color:#c47e0a"><i class="fa-solid fa-exclamation-triangle"></i> <strong>Not in pharmacy stock</strong> — Prescription will still be saved. Inform patient to procure externally.</div>`;
    } else if (stockStatus === 'critical') {
        stockDiv.innerHTML = `<div style="background:rgba(232,76,76,.1);border:1px solid rgba(232,76,76,.3);border-radius:6px;padding:8px 12px;margin-bottom:8px;font-size:12px;color:#922020"><i class="fa-solid fa-warning"></i> <strong>Critical stock: ${stockCount} ${unit} remaining</strong> — Replenish urgently!</div>`;
    } else if (stockStatus === 'low') {
        stockDiv.innerHTML = `<div style="background:rgba(245,166,35,.1);border:1px solid rgba(245,166,35,.3);border-radius:6px;padding:8px 12px;margin-bottom:8px;font-size:12px;color:#c47e0a"><i class="fa-solid fa-battery-low"></i> <strong>Low stock: ${stockCount} ${unit}</strong> — Consider restocking soon.</div>`;
    } else {
        stockDiv.innerHTML = `<div style="background:rgba(39,196,122,.1);border:1px solid rgba(39,196,122,.3);border-radius:6px;padding:8px 12px;margin-bottom:8px;font-size:12px;color:#1b9e5c"><i class="fa-solid fa-check-circle"></i> In stock: ${stockCount} ${unit} available</div>`;
    }
    stockDiv.style.display = 'block';

    // Allergy alert
    const drugName = (opt.textContent || '').toLowerCase();
    const allergyHits = PATIENT_ALLERGIES_ENC.filter(a => a && (drugName.includes(a.toLowerCase()) || a.toLowerCase().includes(drugName.split(' ')[0])));
    let html = '';
    if (allergyHits.length) {
        html += `<div style="font-size:12px;color:#991b1b;background:#fef2f2;border:1.5px solid #fca5a5;padding:8px 12px;border-radius:6px;margin-bottom:5px;font-weight:600"><i class="fa-solid fa-circle-exclamation"></i> ALLERGY ALERT: Patient is allergic to ${allergyHits.join(', ')}</div>`;
    }

    // Side effects & contraindications
    const seDiv = document.getElementById(`side-effects-${idx}`);
    if (sideEffects) html += `<div style="font-size:11px;color:#b45309;background:rgba(245,166,35,.08);padding:6px 10px;border-radius:5px;margin-bottom:5px"><i class="fa-solid fa-triangle-exclamation"></i> <strong>Side Effects:</strong> ${sideEffects}</div>`;
    if (contra) html += `<div style="font-size:11px;color:#922020;background:rgba(232,76,76,.08);padding:6px 10px;border-radius:5px;margin-bottom:5px"><i class="fa-solid fa-ban"></i> <strong>Contraindications:</strong> ${contra}</div>`;
    if (interactions) html += `<div style="font-size:11px;color:#065e5e;background:rgba(10,180,180,.08);padding:6px 10px;border-radius:5px;margin-bottom:5px"><i class="fa-solid fa-circle-info"></i> <strong>Drug Interactions (note):</strong> ${interactions}</div>`;
    seDiv.innerHTML = html;
    seDiv.style.display = html ? 'block' : 'none';

    // Check cross-drug interactions from the database
    checkAllRxInteractions();
}

function getSelectedDrugNames(includeActive = false) {
    const names = [];
    document.querySelectorAll('.rx-row').forEach(row => {
        const idx = row.id.replace('rx-item-', '');
        const sel = document.getElementById(`med-sel-${idx}`);
        if (!sel?.value) return;
        const opt = sel.options[sel.selectedIndex];
        const med = medicines.find(m => String(m.id) === String(sel.value));
        if (med?.generic_name) names.push(med.generic_name.toLowerCase());
        names.push((opt.text || '').split(' - ')[0].toLowerCase());
    });
    if (includeActive) {
        activePrescribedMeds.forEach(m => {
            if (m.name) names.push(m.name.toLowerCase());
            if (m.generic_name) names.push(m.generic_name.toLowerCase());
        });
    }
    return [...new Set(names)];
}

let _ixCheckTimer = null;
function checkAllRxInteractions() {
    clearTimeout(_ixCheckTimer);
    _ixCheckTimer = setTimeout(() => {
        const newNames = getSelectedDrugNames(false);
        const allNames = getSelectedDrugNames(true);
        const box = document.getElementById('rx-interaction-box');
        if (!box) return;
        if (newNames.length === 0 || allNames.length < 2) { box.innerHTML = ''; box.style.display = 'none'; return; }

        // Build a lookup of active prescribed names for annotation
        const activeNameTokens = [];
        activePrescribedMeds.forEach(m => {
            if (m.name) activeNameTokens.push({ token: m.name.toLowerCase(), label: m.name, info: m.prescribed_on + ', ' + m.days_left + 'd left' });
            if (m.generic_name && m.generic_name !== m.name) activeNameTokens.push({ token: m.generic_name.toLowerCase(), label: m.generic_name, info: m.prescribed_on + ', ' + m.days_left + 'd left' });
        });
        function findActiveMatch(matched) {
            const m = matched.toLowerCase();
            return activeNameTokens.find(a => m.includes(a.token) || a.token.includes(m));
        }

        $.post('{{ route("pharmacy.interactions.check") }}', { names: allNames, _token: '{{ csrf_token() }}' }, function(data) {
            if (!data.interactions.length) { box.innerHTML = ''; box.style.display = 'none'; return; }
            let html = '<div style="font-weight:700;font-size:12px;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px;color:#7f1d1d"><i class="fa-solid fa-pills"></i> Drug–Drug Interaction Alerts</div>';
            if (activePrescribedMeds.length > 0) {
                html += `<div style="font-size:11px;color:#64748b;background:#f8fafc;border-radius:5px;padding:5px 8px;margin-bottom:8px"><i class="fa-solid fa-circle-info"></i> Includes check against <strong>${activePrescribedMeds.length}</strong> active medication(s) already prescribed in this visit.</div>`;
            }
            data.interactions.forEach(i => {
                const active1 = findActiveMatch(i.matched1);
                const active2 = findActiveMatch(i.matched2);
                const activeNote = active1
                    ? `<span style="font-size:10px;background:#e0f2fe;color:#0369a1;border-radius:3px;padding:1px 5px;margin-left:4px">already prescribed · ${escapeHtml(active1.info)}</span>`
                    : active2
                    ? `<span style="font-size:10px;background:#e0f2fe;color:#0369a1;border-radius:3px;padding:1px 5px;margin-left:4px">already prescribed · ${escapeHtml(active2.info)}</span>`
                    : '';
                html += `<div style="background:${i.bg};border:1px solid ${i.border};border-radius:6px;padding:10px 12px;margin-bottom:6px">
                    <div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;margin-bottom:4px">
                        <span style="background:${i.bg};border:1.5px solid ${i.border};color:${i.color};font-weight:700;font-size:10px;padding:1px 6px;border-radius:3px;text-transform:uppercase">${escapeHtml(i.label)}</span>
                        <span style="font-weight:600;font-size:13px;color:${i.color}">${escapeHtml(i.matched1)} ↔ ${escapeHtml(i.matched2)}</span>${activeNote}
                    </div>
                    <div style="font-size:12px;color:#374151">${escapeHtml(i.description)}</div>
                    ${i.management ? `<div style="font-size:11px;color:#6b7280;margin-top:4px"><strong>Action:</strong> ${escapeHtml(i.management)}</div>` : ''}
                </div>`;
            });
            box.innerHTML = html;
            box.style.display = 'block';
        });
    }, 400);
}

function savePrescription() {
    const items = [];
    const stockWarnings = [];

    document.querySelectorAll('.rx-row').forEach(row => {
        const idx = row.id.replace('rx-item-', '');
        const medId = document.getElementById(`med-sel-${idx}`)?.value;
        if (!medId || medId === '__newmed__') return;

        // Collect stock warnings per medicine
        const sel = document.getElementById(`med-sel-${idx}`);
        const opt = sel.options[sel.selectedIndex];
        const stockStatus = opt.dataset.stockStatus;
        const stockCount  = parseInt(opt.dataset.stockCount || '0', 10);
        const medName     = opt.text.split(' - ')[0];

        if (stockStatus === 'out_of_stock') {
            stockWarnings.push(`• ${medName}: OUT OF STOCK — please add to pharmacy stock!`);
        } else if (stockStatus === 'critical') {
            stockWarnings.push(`• ${medName}: CRITICAL STOCK (${stockCount} left) — replenish urgently!`);
        } else if (stockStatus === 'low') {
            stockWarnings.push(`• ${medName}: LOW STOCK (${stockCount} left) — consider restocking soon.`);
        }

        items.push({
            medicine_id:   medId,
            dose:          document.getElementById(`dose-${idx}`)?.value || '1',
            frequency:     document.getElementById(`freq-${idx}`)?.value || 'OD',
            route:         document.getElementById(`route-${idx}`)?.value || 'Oral',
            duration_days: document.getElementById(`dur-${idx}`)?.value || 5,
            instructions:  document.getElementById(`instr-${idx}`)?.value || '',
        });
    });

    if (!items.length) { alert('Add at least one medicine'); return; }

    // Allergy check across all selected drugs
    const allergyAlerts = [];
    document.querySelectorAll('.rx-row').forEach(row => {
        const idx = row.id.replace('rx-item-', '');
        const sel = document.getElementById(`med-sel-${idx}`);
        if (!sel?.value) return;
        const drugName = (sel.options[sel.selectedIndex].textContent || '').toLowerCase();
        const hits = PATIENT_ALLERGIES_ENC.filter(a => a && (drugName.includes(a.toLowerCase()) || a.toLowerCase().includes(drugName.split(' ')[0])));
        if (hits.length) allergyAlerts.push(`${sel.options[sel.selectedIndex].text.split(' - ')[0]}: allergic to ${hits.join(', ')}`);
    });
    if (allergyAlerts.length) {
        if (!confirm('⚠️ ALLERGY ALERT!\n\n' + allergyAlerts.join('\n') + '\n\nPrescribe anyway?')) return;
    }

    if (stockWarnings.length) {
        alert('⚠️ Stock Alert — please inform pharmacy to restock:\n\n' + stockWarnings.join('\n') + '\n\nThe prescription will still be saved.');
    }

    const data = {
        medicines: items,
        notes:  document.getElementById('rx-notes')?.value || '',
        advice: document.getElementById('rx-advice')?.value || '',
    };

    // Drug interaction check before final save — includes active meds already prescribed in this visit
    const allNames = getSelectedDrugNames(true);
    if (allNames.length >= 2) {
        $.post('{{ route("pharmacy.interactions.check") }}', { names: allNames, _token: '{{ csrf_token() }}' }, function(ixData) {
            const contraindicated = ixData.interactions.filter(i => i.severity === 'contraindicated');
            const major = ixData.interactions.filter(i => i.severity === 'major');

            if (contraindicated.length) {
                const pairs = contraindicated.map(i => `• ${i.matched1} + ${i.matched2}: ${i.description}`).join('\n');
                if (!confirm('🚫 CONTRAINDICATED COMBINATION!\n\n' + pairs + '\n\nThis combination is contraindicated. Prescribe anyway?')) return;
            } else if (major.length) {
                const pairs = major.map(i => `• ${i.matched1} + ${i.matched2}: ${i.description}`).join('\n');
                if (!confirm('⚠️ MAJOR DRUG INTERACTION!\n\n' + pairs + '\n\nPrescribe anyway?')) return;
            }
            doSavePrescription(data);
        }).fail(() => doSavePrescription(data));
    } else {
        doSavePrescription(data);
    }
}

function doSavePrescription(data) {
    if (editingRxId) {
        $.ajax({
            url: `{{ url("/encounters") }}/${encounterId}/prescriptions/${editingRxId}`,
            method: 'POST',
            data: { ...data, _method: 'PUT', _token: '{{ csrf_token() }}' },
            success: function(res) {
                if (res.success) { markSaved(document.getElementById('rx-save-btn'), 'Saved Prescription'); setTimeout(() => location.reload(), 800); }
            },
            error: function() { alert('Failed to update prescription'); }
        });
        return;
    }
    $.post(`{{ url("/encounters") }}/${encounterId}/prescription`, data, function(res) {
        if (res.success) {
            markSaved(document.getElementById('rx-save-btn'), 'Saved Prescription');
            setTimeout(() => location.reload(), 1000);
        }
    }).fail(() => alert('Failed to save prescription'));
}

// ── Full prescription edit / merge / drag-merge ──
@php
    $rxData = $encounter->prescriptions->mapWithKeys(fn ($rx) => [$rx->id => [
        'no'     => $rx->prescription_no,
        'notes'  => $rx->notes,
        'advice' => $rx->advice,
        'items'  => $rx->items->map(fn ($it) => [
            'medicine_id'    => $it->medicine_id,
            'medicine_label' => $it->medicine->name . ($it->medicine->strength ? ' - ' . $it->medicine->strength : ''),
            'dose'           => $it->dose,
            'frequency'      => $it->frequency,
            'route'          => $it->route,
            'duration_days'  => $it->duration_days,
            'instructions'   => $it->instructions,
        ])->values(),
    ]]);
@endphp
const RX_DATA = @json($rxData);
let editingRxId = null;

function editPrescription(rxId) {
    const d = RX_DATA[rxId];
    if (!d) return;
    document.getElementById('rx-items-container').innerHTML = '';
    rxCount = 0;
    d.items.forEach(it => {
        addRxItem();
        const idx = rxCount;
        const medSel = document.getElementById(`med-sel-${idx}`);
        if (![...medSel.options].some(o => o.value === String(it.medicine_id))) {
            medSel.appendChild(new Option(it.medicine_label, String(it.medicine_id), true, true));
        }
        $(`#med-sel-${idx}`).val(String(it.medicine_id)).trigger('change');   // runs onMedSelect (default dose)
        rxSetVal(`dose-${idx}`,  it.dose);
        rxSetVal(`freq-${idx}`,  it.frequency);
        rxSetVal(`route-${idx}`, it.route);
        rxSetVal(`dur-${idx}`,   it.duration_days);
        rxSetVal(`instr-${idx}`, it.instructions || '');
    });
    document.getElementById('rx-notes').value  = d.notes  || '';
    document.getElementById('rx-advice').value = d.advice || '';
    editingRxId = rxId;
    document.getElementById('rx-form-title').textContent = 'Edit Prescription ' + d.no;
    document.getElementById('rx-edit-no').textContent = d.no;
    document.getElementById('rx-edit-banner').style.display = 'block';
    document.getElementById('rx-save-btn').innerHTML = '<i class="fa-solid fa-save"></i> Update Prescription';
    document.getElementById('rx-form-card').scrollIntoView({ behavior: 'smooth', block: 'start' });
}
function cancelEditRx() {
    editingRxId = null;
    document.getElementById('rx-items-container').innerHTML = '';
    rxCount = 0;
    document.getElementById('rx-notes').value = '';
    document.getElementById('rx-advice').value = '';
    document.getElementById('rx-form-title').textContent = 'Add New Prescription';
    document.getElementById('rx-edit-banner').style.display = 'none';
    document.getElementById('rx-save-btn').innerHTML = '<i class="fa-solid fa-save"></i> Save Prescription';
}

function doMerge(sourceId, targetId) {
    if (sourceId === targetId) return;
    if (!confirm('Merge ' + (RX_DATA[sourceId]?.no || '#' + sourceId) + ' into ' + (RX_DATA[targetId]?.no || '#' + targetId) + '? The two will become one prescription.')) return;
    $.ajax({
        url: `{{ url("/encounters") }}/${encounterId}/prescriptions/${sourceId}/merge/${targetId}`,
        method: 'POST',
        data: { _token: '{{ csrf_token() }}' },
        success: function(res) { if (res.success) { showToast('Prescriptions merged', 'success'); setTimeout(() => location.reload(), 700); } },
        error: function(xhr) { alert(xhr.responseJSON?.message || 'Could not merge.'); }
    });
}
function mergePrescription(sourceId) {
    const others = Object.keys(RX_DATA).map(Number).filter(id => id !== sourceId);
    if (!others.length) { alert('There is no other prescription to merge into.'); return; }
    if (others.length === 1) { doMerge(sourceId, others[0]); return; }
    const list = others.map((id, i) => `${i + 1}) ${RX_DATA[id].no}`).join('\n');
    const pick = prompt('Merge ' + RX_DATA[sourceId].no + ' into which prescription?\n\n' + list + '\n\nEnter a number:');
    const n = parseInt(pick, 10);
    if (n >= 1 && n <= others.length) doMerge(sourceId, others[n - 1]);
}

// Drag a prescription card onto another to merge.
let rxDragId = null;
function rxDragStart(e, id) { rxDragId = id; e.dataTransfer.effectAllowed = 'move'; e.dataTransfer.setData('text/plain', String(id)); }
function rxDragOver(e) { e.preventDefault(); e.currentTarget.style.outline = '2px dashed var(--sc-teal)'; }
function rxDragLeave(e) { e.currentTarget.style.outline = ''; }
function rxDrop(e, targetId) {
    e.preventDefault();
    e.currentTarget.style.outline = '';
    const src = rxDragId || parseInt(e.dataTransfer.getData('text/plain'), 10);
    rxDragId = null;
    if (src && src !== targetId) doMerge(src, targetId);
}

// ── Speciality forms (eye, gynae, ortho, skin) ─────────────────────────────
function saveSpecForm(e) {
    e.preventDefault();
    const form = e.target, code = form.dataset.form;
    const btns = form.querySelectorAll('[type="submit"]');
    $.post(`{{ url('/encounters') }}/${encounterId}/forms/${code}`, $(form).serialize(), function (res) {
        if (res.success) { btns.forEach(b => markSaved(b, 'Saved')); if (form._saver && form._saver !== true) sectionSaverSynced(form._saver); }
    }).fail(() => showToast('Could not save the form. Please try again.', 'error'));
}

// Fill only the empty boxes from the last visit's form
function copySpecForm(btn, data) {
    const form = btn.closest('form');
    let n = 0;
    Object.entries(data).forEach(([k, v]) => {
        if (Array.isArray(v)) {
            form.querySelectorAll(`[name="f[${k}][]"]`).forEach(c => { if (v.includes(c.value) && !c.checked) { c.checked = true; n++; } });
            return;
        }
        const el = form.querySelector(`[name="f[${k}]"]`);
        if (el && !el.value) { el.value = v; n++; }
    });
    form.dispatchEvent(new Event('input', { bubbles: true }));
    specWarnAll(form);
    showToast(n ? `Copied ${n} value(s) from the last visit — check and save` : 'Nothing to copy (boxes already filled)', n ? 'success' : 'info');
}

function copyRefraction(form, from, to) {
    ['r', 'l'].forEach(eye => ['sph', 'cyl', 'axis', 'add', 'va'].forEach(p => {
        const src = form.querySelector(`[name="f[${from}_${eye}_${p}]"]`), dst = form.querySelector(`[name="f[${to}_${eye}_${p}]"]`);
        if (src && dst && src.value !== '') dst.value = src.value;
    }));
    form.dispatchEvent(new Event('input', { bubbles: true }));
}

function addSpecForm(sel) {
    const code = sel.value;
    if (!code) return;
    const tpl = document.getElementById('spec-tpl-' + code);
    if (tpl) {
        document.getElementById('tab-notes').before(tpl.content.cloneNode(true));
        tpl.remove();
        const pane = document.getElementById('tab-form-' + code);
        specInit(pane);
        window.specSpyObserve?.(pane);
    }
    const btn = document.querySelector(`#enc-tabs .tab-btn[data-tab="form-${code}"]`);
    if (btn) { btn.style.display = ''; btn.click(); }
    sel.querySelector(`option[value="${code}"]`)?.remove();
    sel.value = '';
    if (sel.options.length <= 1) sel.remove();
}

// Dioptres shown as +1.25 / -0.75; out-of-range numbers outlined red
function specFmtDioptre(el) {
    const v = el.value.trim().replace(',', '.');
    if (v === '' || /^(pl|plano)$/i.test(v)) { if (v) el.value = 'Plano'; return; }
    const n = parseFloat(v);
    if (isNaN(n)) return;
    const q = Math.round(n * 4) / 4;
    el.value = (q > 0 ? '+' : q < 0 ? '-' : '') + Math.abs(q).toFixed(2);
    if (q === 0) el.value = el.dataset.part === 'sph' ? 'Plano' : '0.00';
}
function specWarn(el) {
    const n = parseFloat(el.value);
    const hi = el.dataset.warnAbove, lo = el.dataset.warnBelow;
    el.classList.toggle('spec-warn', !isNaN(n) && ((hi !== undefined && n > +hi) || (lo !== undefined && n < +lo)));
}
function specWarnAll(root) { root.querySelectorAll('[data-warn-above],[data-warn-below]').forEach(specWarn); }

// Gynae: EDD (LMP + 280 days) and gestational age from LMP
function specLmp(form) {
    const lmp = form.querySelector('[name="f[lmp]"]'), edd = form.querySelector('[name="f[edd]"]'), ga = form.querySelector('[name="f[ga]"]');
    if (!lmp || !lmp.value) return;
    const d = new Date(lmp.value + 'T00:00:00');
    if (edd && (!edd.value || edd.dataset.auto === '1')) {
        const e = new Date(d.getTime() + 280 * 864e5);
        edd.value = e.toISOString().slice(0, 10); edd.dataset.auto = '1';
    }
    if (ga) {
        const days = Math.floor((Date.now() - d.getTime()) / 864e5);
        if (days >= 0 && days < 320) ga.value = `${Math.floor(days / 7)} wk ${days % 7} d`;
    }
}

function specInit(root) {
    root.querySelectorAll('.spec-dioptre').forEach(el => el.addEventListener('blur', () => specFmtDioptre(el)));
    root.querySelectorAll('[data-warn-above],[data-warn-below]').forEach(el => el.addEventListener('input', () => specWarn(el)));
    root.querySelectorAll('[name="f[edd]"]').forEach(el => el.addEventListener('input', () => { el.dataset.auto = '0'; }));
    root.querySelectorAll('[name="f[lmp]"]').forEach(el => el.addEventListener('change', () => specLmp(el.form)));
    specWarnAll(root);
}
document.querySelectorAll('.spec-form').forEach(f => specInit(f));

// Save buttons confirm in place instead of a toast: green check + "Saved ...". The button
// goes back to its "Save ..." text as soon as anything in its section is edited again.
function markSaved(btn, label) {
    if (!btn) return;
    if (!btn.dataset.saveHtml) btn.dataset.saveHtml = btn.innerHTML;
    btn.innerHTML = `<i class="fa-solid fa-circle-check"></i> ${label}`;
    btn.classList.add('btn-saved');
    const scope = btn.closest('form, .modal, .note-card, .card, .tab-pane') || document;
    if (btn._savedReset) btn._savedReset();
    btn._savedReset = () => {
        btn.innerHTML = btn.dataset.saveHtml;
        btn.classList.remove('btn-saved');
        scope.removeEventListener('input', btn._savedReset, true);
        scope.removeEventListener('change', btn._savedReset, true);
        btn._savedReset = null;
    };
    scope.addEventListener('input', btn._savedReset, true);
    scope.addEventListener('change', btn._savedReset, true);
}

// Toast function
function showToast(msg, type='success') {
    const t = document.createElement('div');
    t.style.cssText = `position:fixed;bottom:24px;right:24px;background:${type==='success'?'var(--sc-green)':'var(--sc-red)'};color:#fff;padding:12px 20px;border-radius:8px;font-size:13px;font-weight:600;z-index:9999;box-shadow:0 4px 16px rgba(0,0,0,.2)`;
    t.innerHTML = `<i class="fa-solid fa-${type==='success'?'check':'times'}-circle"></i> ${msg}`;
    document.body.appendChild(t);
    setTimeout(() => t.remove(), 3000);
}

// Single-scroll consult: all panes are visible, tab-nav acts as a sticky scroll-spy.
// Clicking a nav button jumps to that section; scrolling highlights the section in view.
// Replaces the prior tab-toggle behavior (lines retained for ctrl+S save logic which
// relies on `.tab-pane.active` being set on the in-view section by scroll-spy below).
const TAB_KEY = 'enc_tab_{{ $encounter->id }}';

document.querySelectorAll('#enc-tabs .tab-btn[data-tab]').forEach(btn => {
    btn.addEventListener('click', (e) => {
        e.preventDefault();
        const pane = document.getElementById('tab-' + btn.dataset.tab);
        if (pane) {
            pane.scrollIntoView({ behavior: 'smooth', block: 'start' });
            history.replaceState(null, '', '#' + btn.dataset.tab);
        }
    });
});

// Scroll-spy: mark whichever pane is most visible as ".active" — so the existing
// Ctrl+S save handler (which keys off `.tab-pane.active`) keeps working.
(function () {
    const panes = [...document.querySelectorAll('#enc-tabs .tab-pane[id^="tab-"]')];
    if (!panes.length) return;
    const setActive = (id) => {
        document.querySelectorAll('#enc-tabs .tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('#enc-tabs .tab-pane').forEach(p => p.classList.remove('active'));
        document.querySelector(`#enc-tabs .tab-btn[data-tab="${id}"]`)?.classList.add('active');
        document.getElementById('tab-' + id)?.classList.add('active');
        localStorage.setItem(TAB_KEY, id);
    };
    const io = new IntersectionObserver((entries) => {
        let best = null;
        for (const e of entries) {
            if (!e.isIntersecting) continue;
            if (!best || e.intersectionRatio > best.intersectionRatio) best = e;
        }
        if (best) setActive(best.target.id.replace(/^tab-/, ''));
    }, { rootMargin: '-30% 0px -55% 0px', threshold: [0, 0.25, 0.5, 0.75, 1] });
    panes.forEach(p => io.observe(p));
    window.specSpyObserve = (p) => io.observe(p);   // speciality forms added later

    // On first load, restore the previously-active section (preserves muscle memory).
    const saved = localStorage.getItem(TAB_KEY);
    const target = (location.hash ? location.hash.slice(1) : saved);
    if (target && document.getElementById('tab-' + target)) {
        requestAnimationFrame(() => {
            document.getElementById('tab-' + target).scrollIntoView({ block: 'start' });
        });
    }
})();

// ── DELETE PRESCRIPTION (whole block)
function deleteRx(rxId) {
    if (!confirm('Delete this entire prescription? This cannot be undone.')) return;
    $.ajax({
        url: `{{ url('/encounters') }}/${encounterId}/prescriptions/${rxId}`,
        method: 'DELETE',
        data: { _token: '{{ csrf_token() }}' },
        success: function(res) {
            if (res.success) {
                document.getElementById('rx-card-' + rxId)?.remove();
                showToast('Prescription deleted.', 'success');
            }
        },
        error: function(xhr) {
            alert(xhr.responseJSON?.message || 'Cannot delete this prescription.');
        }
    });
}

// ── EDIT PRESCRIPTION (notes/advice)
let _editingRxId = null;
function openEditRxModal(rxId, notes, advice) {
    _editingRxId = rxId;
    document.getElementById('editRxNotes').value = notes;
    document.getElementById('editRxAdvice').value = advice;
    document.getElementById('editRxModal').classList.add('open');
}
function closeEditRxModal() {
    document.getElementById('editRxModal').classList.remove('open');
    _editingRxId = null;
}
function saveEditRx() {
    if (!_editingRxId) return;
    $.ajax({
        url: `{{ url('/encounters') }}/${encounterId}/prescriptions/${_editingRxId}`,
        method: 'POST',
        data: {
            _token:  '{{ csrf_token() }}',
            _method: 'PUT',
            notes:   document.getElementById('editRxNotes').value,
            advice:  document.getElementById('editRxAdvice').value,
        },
        success: function(res) {
            if (res.success) {
                markSaved(document.getElementById('edit-rx-save-btn'), 'Saved Changes');
                setTimeout(() => { closeEditRxModal(); location.reload(); }, 800);
            }
        },
        error: function(xhr) {
            alert(xhr.responseJSON?.message || 'Failed to update prescription.');
        }
    });
}

// ── DELETE SINGLE PRESCRIPTION ITEM
function deleteRxItem(itemId) {
    if (!confirm('Remove this medicine from the prescription?')) return;
    $.ajax({
        url: `{{ url('/encounters') }}/${encounterId}/prescription-items/${itemId}`,
        method: 'DELETE',
        data: { _token: '{{ csrf_token() }}' },
        success: function(res) {
            if (res.success) {
                document.getElementById('rx-item-row-' + itemId)?.remove();
                showToast('Medicine removed.', 'success');
            }
        },
        error: function(xhr) {
            alert(xhr.responseJSON?.message || 'Cannot delete this item.');
        }
    });
}

// ── EDIT PRESCRIPTION ITEM MODAL
let _editingItemId = null;
function openEditItemModal(itemId, medName, dose, freq, route, dur, instructions) {
    _editingItemId = itemId;
    document.getElementById('editItemMedName').textContent = medName;
    document.getElementById('editItemDose').value = dose;
    document.getElementById('editItemFreq').value = freq;
    document.getElementById('editItemRoute').value = route;
    document.getElementById('editItemDur').value = dur;
    document.getElementById('editItemInstr').value = instructions;
    document.getElementById('editItemModal').classList.add('open');
}
function closeEditItemModal() {
    document.getElementById('editItemModal').classList.remove('open');
    _editingItemId = null;
}
function saveEditItem() {
    if (!_editingItemId) return;
    const data = {
        _token:        '{{ csrf_token() }}',
        _method:       'PUT',
        dose:          document.getElementById('editItemDose').value,
        frequency:     document.getElementById('editItemFreq').value,
        route:         document.getElementById('editItemRoute').value,
        duration_days: document.getElementById('editItemDur').value,
        instructions:  document.getElementById('editItemInstr').value,
    };
    $.post(`{{ url('/encounters') }}/${encounterId}/prescription-items/${_editingItemId}`, data, function(res) {
        if (res.success) {
            markSaved(document.getElementById('edit-item-save-btn'), 'Saved Changes');
            setTimeout(() => { closeEditItemModal(); location.reload(); }, 800);
        }
    }).fail(() => alert('Failed to update.'));
}

// ── ENCOUNTER NOTES (Quill WYSIWYG) ─────────────────────────────────────────
const QUILL_TOOLBAR = [
    ['bold', 'italic', 'underline'],
    [{ list: 'ordered' }, { list: 'bullet' }],
    ['clean']
];
let newQuill = null;
const editQuills = {};

const clinicalQuills = {};

// Quick-pick chips per clinical field — admin-managed in Settings → Clinical Chips.
const CLINICAL_QUILL_CHIPS = @json($clinicalChips);

const CLINICAL_QUILL_FIELDS = [
    { id: 'cq-chief-complaint', key: 'chief_complaint',            content: @json($encounter->chief_complaint) },
    { id: 'cq-hopi',            key: 'history_of_present_illness', content: @json($encounter->history_of_present_illness) },
    { id: 'cq-pmh',             key: 'past_medical_history',       content: @json($encounter->past_medical_history) },
    { id: 'cq-fh',              key: 'family_history',             content: @json($encounter->family_history) },
    { id: 'cq-sh',              key: 'social_history',             content: @json($encounter->social_history) },
    { id: 'cq-exam',            key: 'examination_findings',       content: @json($encounter->examination_findings) },
    { id: 'cq-impression',      key: 'impression',                 content: @json($encounter->impression) },
    { id: 'cq-plan',            key: 'plan',                       content: @json($encounter->plan) },
];

document.addEventListener('DOMContentLoaded', function () {
    // Notes tab editor
    newQuill = new Quill('#new-note-quill', {
        theme: 'snow',
        placeholder: 'Write any personal notes, observations, or reminders about this visit…',
        modules: { toolbar: QUILL_TOOLBAR }
    });
    // Clinical notes editors — minimal toolbar, always editable
    const CLINICAL_TOOLBAR = [['bold','italic'],[{list:'bullet'}]];
    CLINICAL_QUILL_FIELDS.forEach(({ id, key, content }) => {
        const q = new Quill('#' + id, {
            theme: 'snow',
            placeholder: 'Type here…',
            modules: { toolbar: CLINICAL_TOOLBAR }
        });
        if (content) q.root.innerHTML = content;
        // Force auto-height via inline style — overrides Quill's height:100% regardless of CSS cascade
        const qlCont = q.container.querySelector('.ql-container');
        if (qlCont) { qlCont.style.height = 'auto'; qlCont.style.minHeight = '0'; }
        q.root.style.minHeight = '88px';
        q.root.style.height    = 'auto';
        clinicalQuills[key] = q;
        clinicalSnapshot[key] = q.root.innerHTML;

        // Decorate the Quill: voice mic button + quick-pick chips
        const wrap = document.getElementById(id)?.closest('.form-group');
        if (wrap) {
            const label = wrap.querySelector('.form-label');
            if (label && voiceSupported()) {
                const mic = document.createElement('button');
                mic.type = 'button';
                mic.className = 'cq-mic-btn';
                mic.title = 'Dictate (voice → text)';
                mic.innerHTML = '<i class="fa-solid fa-microphone"></i>';
                mic.onclick = (ev) => { ev.preventDefault(); ev.stopPropagation(); toggleVoice(key, mic); };
                label.appendChild(mic);
            }
            const chips = CLINICAL_QUILL_CHIPS[key];
            if (chips && chips.length) {
                const chipsWrap = document.createElement('div');
                chipsWrap.className = 'cq-chips';
                chips.forEach(text => {
                    const c = document.createElement('button');
                    c.type = 'button';
                    c.className = 'cq-chip';
                    c.textContent = text;
                    c.onclick = () => insertIntoQuill(q, text);
                    chipsWrap.appendChild(c);
                });
                const anchor = wrap.querySelector('.ql-toolbar') || wrap.querySelector('#' + id);
                anchor.parentNode.insertBefore(chipsWrap, anchor);
            }
        }

        // Auto-save: debounce on text-change
        q.on('text-change', function () {
            clearTimeout(_clinicalAutoSaveTimer);
            _clinicalAutoSaveTimer = setTimeout(saveClinicalDraft, 800);
            updateChPreview(key);
        });
    });

    // Topic previews (follow-up and allergies are plain inputs, not Quills)
    updateAllChPreviews();
    clinicalForm()?.addEventListener('input', () => updateChPreview('follow_up'));
    const allergyTags = document.getElementById('allergy-tags');
    if (allergyTags) new MutationObserver(() => updateChPreview('allergies')).observe(allergyTags, { childList: true });
    initClinicalAutoSave();

    // Recovery: if a draft from after the encounter's last update exists, prompt to restore.
    maybeOfferDraftRecovery();
});

function saveNewNote() {
    const html = newQuill.root.innerHTML;
    if (newQuill.getText().trim() === '') { alert('Please write something before saving.'); return; }
    $.post('{{ route("encounters.notes.store", $encounter) }}', { body: html, _token: '{{ csrf_token() }}' }, function(res) {
        if (!res.success) return;
        document.getElementById('notes-empty')?.remove();
        const n = res.note;
        const card = document.createElement('div');
        card.className = 'note-card';
        card.id = 'note-' + n.id;
        card.innerHTML = `
            <div class="note-card-header">
                <div style="display:flex;align-items:center;gap:8px">
                    <i class="fa-solid fa-note-sticky" style="color:var(--sc-teal)"></i>
                    <span style="font-size:11px;color:var(--sc-muted)">${escapeHtml(n.created_by)} &middot; ${escapeHtml(n.created_at)}</span>
                </div>
                <div style="display:flex;gap:6px">
                    <button type="button" class="btn btn-icon btn-sm btn-outline" title="Edit" onclick="openNoteEdit(${n.id})"><i class="fa-solid fa-pen"></i></button>
                    <button type="button" class="btn btn-icon btn-sm btn-outline" style="color:#94a3b8;border-color:#e2e8f0" title="Delete" onclick="deleteNote(${n.id})"><i class="fa-solid fa-trash"></i></button>
                </div>
            </div>
            <div class="note-body ql-editor" id="note-body-${n.id}" style="padding:0"></div>
            <div class="note-edit-form" id="note-edit-${n.id}" style="display:none">
                <div id="note-edit-quill-${n.id}" class="note-quill-edit" style="min-height:120px;background:#fff"></div>
                <div style="display:flex;gap:8px;margin-top:8px">
                    <button type="button" class="btn btn-primary btn-sm" id="note-edit-save-btn-${n.id}" onclick="saveNoteEdit(${n.id})"><i class="fa-solid fa-check"></i> Save</button>
                    <button type="button" class="btn btn-outline btn-sm" onclick="cancelNoteEdit(${n.id})">Cancel</button>
                </div>
            </div>`;
        document.getElementById('note-body-' + n.id).innerHTML = n.body;
        document.getElementById('notes-list').prepend(card);
        newQuill.setContents([], 'silent');
        markSaved(document.getElementById('note-save-btn'), 'Saved Note');
    }).fail(() => alert('Failed to save note.'));
}

function openNoteEdit(id) {
    document.getElementById('note-body-' + id).style.display = 'none';
    const editDiv = document.getElementById('note-edit-' + id);
    editDiv.style.display = 'block';
    if (!editQuills[id]) {
        editQuills[id] = new Quill('#note-edit-quill-' + id, {
            theme: 'snow',
            modules: { toolbar: QUILL_TOOLBAR }
        });
        // Load existing HTML content
        const bodyEl = document.getElementById('note-body-' + id);
        editQuills[id].root.innerHTML = bodyEl.innerHTML;
    }
    editQuills[id].focus();
}

function cancelNoteEdit(id) {
    document.getElementById('note-body-' + id).style.display = 'block';
    document.getElementById('note-edit-' + id).style.display = 'none';
}

function saveNoteEdit(id) {
    const q = editQuills[id];
    if (!q || q.getText().trim() === '') { alert('Note cannot be empty.'); return; }
    const body = q.root.innerHTML;
    $.ajax({
        url: `{{ url("/encounters") }}/{{ $encounter->id }}/notes/${id}`,
        method: 'PUT',
        data: { body, _token: '{{ csrf_token() }}' },
        success(res) {
            if (!res.success) return;
            document.getElementById('note-body-' + id).innerHTML = res.body;
            markSaved(document.getElementById('note-edit-save-btn-' + id), 'Saved');
            setTimeout(() => cancelNoteEdit(id), 1000);
        },
        error() { alert('Failed to update note.'); }
    });
}

function deleteNote(id) {
    if (!confirm('Delete this note?')) return;
    $.ajax({
        url: `{{ url("/encounters") }}/{{ $encounter->id }}/notes/${id}`,
        method: 'DELETE',
        data: { _token: '{{ csrf_token() }}' },
        success(res) {
            if (!res.success) return;
            document.getElementById('note-' + id)?.remove();
            if (!document.querySelector('#notes-list .note-card')) {
                document.getElementById('notes-list').innerHTML = '<div id="notes-empty" style="text-align:center;padding:40px;color:var(--sc-muted)"><i class="fa-solid fa-note-sticky" style="font-size:2rem;margin-bottom:12px;opacity:.3"></i><div style="font-size:14px">No notes yet.</div></div>';
            }
            showToast('Note deleted.', 'success');
        },
        error() { alert('Failed to delete note.'); }
    });
}
</script>

{{-- Edit Prescription Item Modal --}}
<div class="modal-overlay" id="editItemModal">
    <div class="modal-box" style="max-width:480px">
        <div class="modal-header">
            <h3><i class="fa-solid fa-pen"></i> Edit Prescription Item</h3>
            <button class="modal-close" onclick="closeEditItemModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div style="font-size:13px;font-weight:600;color:var(--sc-navy);margin-bottom:14px" id="editItemMedName"></div>
            <div class="form-row cols-2">
                <div class="form-group">
                    <label class="form-label">Dose</label>
                    <input type="text" id="editItemDose" class="form-control" placeholder="e.g. 500mg">
                </div>
                <div class="form-group">
                    <label class="form-label">Frequency</label>
                    <select id="editItemFreq" class="form-control">
                        <option value="OD">OD — Once Daily</option>
                        <option value="BD">BD — Twice Daily</option>
                        <option value="TDS">TDS — Thrice Daily</option>
                        <option value="QID">QID — Four times/day</option>
                        <option value="HS">HS — At Bedtime</option>
                        <option value="SOS">SOS — As Needed</option>
                        <option value="Stat">STAT — Immediately</option>
                    </select>
                </div>
            </div>
            <div class="form-row cols-2">
                <div class="form-group">
                    <label class="form-label">Route</label>
                    <select id="editItemRoute" class="form-control">
                        <option>Oral</option><option>IV</option><option>IM</option><option>SC</option>
                        <option>Topical</option><option>Inhaled</option><option>Sublingual</option><option>Rectal</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Duration (Days)</label>
                    <input type="number" id="editItemDur" class="form-control" min="1">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Instructions</label>
                <input type="text" id="editItemInstr" class="form-control" placeholder="e.g. After food">
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeEditItemModal()">Cancel</button>
            <button class="btn btn-primary" id="edit-item-save-btn" onclick="saveEditItem()"><i class="fa-solid fa-floppy-disk"></i> Save Changes</button>
        </div>
    </div>
</div>

<div class="modal-overlay" id="editRxModal">
    <div class="modal-box" style="max-width:480px">
        <div class="modal-header">
            <h3><i class="fa-solid fa-pen"></i> Edit Prescription</h3>
            <button class="modal-close" onclick="closeEditRxModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label">Notes</label>
                <textarea id="editRxNotes" class="form-control" rows="3" placeholder="Additional notes..."></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Advice to Patient</label>
                <textarea id="editRxAdvice" class="form-control" rows="3" placeholder="Diet, lifestyle, activity..."></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeEditRxModal()">Cancel</button>
            <button class="btn btn-primary" id="edit-rx-save-btn" onclick="saveEditRx()"><i class="fa-solid fa-floppy-disk"></i> Save Changes</button>
        </div>
    </div>
</div>

{{-- Email Prescription Modal --}}
<div id="email-rx-modal" class="modal-overlay">
    <div class="modal-box" style="max-width:480px">
        <div class="modal-header">
            <i class="fa-solid fa-envelope" style="color:var(--sc-teal)"></i>
            <h3>Email Prescription</h3>
            <button class="modal-close" onclick="document.getElementById('email-rx-modal').classList.remove('open')">&times;</button>
        </div>
        <form id="email-rx-form" method="POST" action="">
            @csrf
            <div class="modal-body" style="display:flex;flex-direction:column;gap:12px">
                <div class="form-group" style="margin:0">
                    <label class="form-label">To <span class="req">*</span></label>
                    <input type="email" name="to" class="form-control" value="{{ $encounter->patient->email ?? '' }}" required>
                </div>
                <div class="form-group" style="margin:0">
                    <label class="form-label">Subject</label>
                    <input type="text" name="subject" class="form-control" placeholder="Auto-filled if left blank">
                </div>
                <div class="form-group" style="margin:0">
                    <label class="form-label">Message</label>
                    <textarea name="message" class="form-control" rows="4" placeholder="Auto-filled if left blank"></textarea>
                </div>
                <div class="text-muted" style="font-size:11px"><i class="fa-solid fa-info-circle"></i> Prescription PDF is attached automatically. Configure SMTP in .env (MAIL_*) before first use.</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('email-rx-modal').classList.remove('open')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-paper-plane"></i> Send</button>
            </div>
        </form>
    </div>
</div>
<script>
function openEmailRxModal(rxId) {
    document.getElementById('email-rx-form').action = '{{ url('/prescriptions') }}/' + rxId + '/email';
    document.getElementById('email-rx-modal').classList.add('open');
}
</script>

{{-- WhatsApp Prescription Modal --}}
<div id="whatsapp-rx-modal" class="modal-overlay">
    <div class="modal-box" style="max-width:480px">
        <div class="modal-header">
            <i class="fa-brands fa-whatsapp" style="color:#25D366"></i>
            <h3>Send Prescription via WhatsApp</h3>
            <button class="modal-close" onclick="document.getElementById('whatsapp-rx-modal').classList.remove('open')">&times;</button>
        </div>
        <form id="whatsapp-rx-form" method="POST" action="">
            @csrf
            <div class="modal-body" style="display:flex;flex-direction:column;gap:12px">
                <div class="form-group" style="margin:0">
                    <label class="form-label">Send to <span class="req">*</span></label>
                    <input type="text" name="phone" class="form-control" value="{{ $encounter->patient->phone ?? $encounter->patient->alt_phone ?? '' }}" required>
                    <div class="text-muted" style="font-size:11px;margin-top:4px">Defaults to the patient's number — edit to send to a different one.</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('whatsapp-rx-modal').classList.remove('open')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fa-brands fa-whatsapp"></i> Send</button>
            </div>
        </form>
    </div>
</div>
<script>
function openWhatsAppRxModal(rxId) {
    document.getElementById('whatsapp-rx-form').action = '{{ url('/prescriptions') }}/' + rxId + '/whatsapp';
    document.getElementById('whatsapp-rx-modal').classList.add('open');
}
</script>

{{-- Encounter Template Modal --}}
<div id="enc-template-modal" class="modal-overlay">
    <div class="modal-box" style="max-width:640px">
        <div class="modal-header">
            <i class="fa-solid fa-layer-group" style="color:var(--sc-teal)"></i>
            <h3>Apply Encounter Template</h3>
            <button class="modal-close" onclick="document.getElementById('enc-template-modal').classList.remove('open')">&times;</button>
        </div>
        <div class="modal-body">
            <div style="font-size:12px;color:var(--sc-muted);margin-bottom:10px">
                Pick a template to pre-fill chief complaint, clinical notes, diagnoses and a default prescription. Existing content is preserved &mdash; new content is appended.
            </div>
            <div id="enc-tmpl-loading" style="text-align:center;padding:20px;color:var(--sc-muted)">
                <i class="fa-solid fa-spinner fa-spin"></i> Loading templates&hellip;
            </div>
            <div id="enc-tmpl-list" style="display:none"></div>
            <div id="enc-tmpl-empty" style="display:none;text-align:center;padding:30px;color:var(--sc-muted)">
                No templates yet. <a href="{{ route('encounter-templates.index') }}" style="color:var(--sc-teal)">Create one &rarr;</a>
            </div>
        </div>
    </div>
</div>

{{-- Copy From Last Visit Modal --}}
<div id="last-visit-modal" class="modal-overlay">
    <div class="modal-box" style="max-width:640px">
        <div class="modal-header">
            <i class="fa-solid fa-clone" style="color:var(--sc-teal)"></i>
            <h3>Copy from Last Visit</h3>
            <button class="modal-close" onclick="document.getElementById('last-visit-modal').classList.remove('open')">&times;</button>
        </div>
        <div class="modal-body">
            <div id="lv-loading" style="text-align:center;padding:24px;color:var(--sc-muted)">
                <i class="fa-solid fa-spinner fa-spin"></i> Loading previous visit&hellip;
            </div>
            <div id="lv-empty" style="display:none;text-align:center;padding:30px;color:var(--sc-muted)">
                No previous encounter found for this patient.
            </div>
            <div id="lv-content" style="display:none">
                <div id="lv-summary" style="padding:12px 14px;background:var(--sc-bg);border:1px solid var(--sc-border);border-radius:8px;margin-bottom:14px;font-size:13px;line-height:1.6"></div>
                <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--sc-muted);margin-bottom:8px">What to copy</div>
                <label style="display:flex;gap:10px;align-items:flex-start;padding:10px 12px;border:1px solid var(--sc-border);border-radius:8px;margin-bottom:8px;cursor:pointer">
                    <input type="checkbox" id="lv-opt-clinical" checked style="margin-top:3px">
                    <div>
                        <div style="font-weight:600;font-size:13px">Clinical notes <span id="lv-clin-count" class="badge badge-outline" style="font-size:10px"></span></div>
                        <div style="font-size:12px;color:var(--sc-muted)">Chief complaint, HPI, PMH, family/social, examination, impression, plan</div>
                    </div>
                </label>
                <label style="display:flex;gap:10px;align-items:flex-start;padding:10px 12px;border:1px solid var(--sc-border);border-radius:8px;margin-bottom:8px;cursor:pointer">
                    <input type="checkbox" id="lv-opt-dx" checked style="margin-top:3px">
                    <div>
                        <div style="font-weight:600;font-size:13px">Diagnoses <span id="lv-dx-count" class="badge badge-outline" style="font-size:10px"></span></div>
                        <div id="lv-dx-preview" style="font-size:12px;color:var(--sc-muted)"></div>
                    </div>
                </label>
                <label style="display:flex;gap:10px;align-items:flex-start;padding:10px 12px;border:1px solid var(--sc-border);border-radius:8px;margin-bottom:8px;cursor:pointer">
                    <input type="checkbox" id="lv-opt-rx" checked style="margin-top:3px">
                    <div>
                        <div style="font-weight:600;font-size:13px">Prescription <span id="lv-rx-count" class="badge badge-outline" style="font-size:10px"></span></div>
                        <div id="lv-rx-preview" style="font-size:12px;color:var(--sc-muted)"></div>
                        <div style="font-size:11px;color:var(--sc-muted);margin-top:3px"><i class="fa-solid fa-info-circle"></i> Adds to the Rx tab as new rows; you'll save it as a fresh prescription dated today.</div>
                    </div>
                </label>
                <div style="font-size:11px;color:var(--sc-muted);margin-top:10px">
                    <i class="fa-solid fa-info-circle"></i> Existing content is preserved &mdash; copied content is appended.
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" onclick="document.getElementById('last-visit-modal').classList.remove('open')">Cancel</button>
            <button type="button" id="lv-copy-btn" class="btn btn-primary" disabled onclick="applyLastVisit()"><i class="fa-solid fa-clone"></i> Copy</button>
        </div>
    </div>
</div>
<script>
let LAST_VISIT_DATA = null;

function openLastVisitModal() {
    const modal = document.getElementById('last-visit-modal');
    const loadEl = document.getElementById('lv-loading');
    const emptyEl = document.getElementById('lv-empty');
    const contentEl = document.getElementById('lv-content');
    const copyBtn = document.getElementById('lv-copy-btn');
    modal.classList.add('open');
    loadEl.style.display = '';
    emptyEl.style.display = 'none';
    contentEl.style.display = 'none';
    copyBtn.disabled = true;
    LAST_VISIT_DATA = null;

    fetch('{{ route("encounters.last-visit", $encounter) }}', {
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
    })
    .then(r => r.ok ? r.json() : { found: false })
    .then(data => {
        loadEl.style.display = 'none';
        if (!data.found) { emptyEl.style.display = ''; return; }
        LAST_VISIT_DATA = data;

        const clinicalKeys = ['chief_complaint','history_of_present_illness','past_medical_history','family_history','social_history','examination_findings','impression','plan'];
        const filled = clinicalKeys.filter(k => (data[k] || '').toString().trim() !== '').length;
        const dxN = (data.diagnoses || []).length;
        const rxN = (data.rx_items || []).length;

        const daysTxt = data.days_ago === 0 ? 'today' : (data.days_ago === 1 ? '1 day ago' : `${data.days_ago} days ago`);
        document.getElementById('lv-summary').innerHTML = `
            <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap">
                <div>
                    <strong>${escapeHtml(data.encounter_no || '')}</strong>
                    <span style="color:var(--sc-muted)">&middot; ${escapeHtml(data.encounter_date || '')} (${daysTxt})</span>
                </div>
                ${data.doctor_name ? `<span class="badge badge-outline"><i class="fa-solid fa-user-doctor"></i> ${escapeHtml(data.doctor_name)}</span>` : ''}
            </div>
        `;
        document.getElementById('lv-clin-count').textContent = filled ? `${filled} field${filled>1?'s':''}` : 'empty';
        document.getElementById('lv-dx-count').textContent = dxN;
        document.getElementById('lv-rx-count').textContent = rxN;
        document.getElementById('lv-dx-preview').textContent = dxN
            ? data.diagnoses.map(d => d.name + (d.icd_code ? ` (${d.icd_code})` : '')).join(', ')
            : 'None recorded.';
        document.getElementById('lv-rx-preview').textContent = rxN
            ? data.rx_items.map(i => i.medicine_name).filter(Boolean).join(', ')
            : 'None recorded.';

        document.getElementById('lv-opt-clinical').disabled = filled === 0;
        document.getElementById('lv-opt-clinical').checked = filled > 0;
        document.getElementById('lv-opt-dx').disabled = dxN === 0;
        document.getElementById('lv-opt-dx').checked = dxN > 0;
        document.getElementById('lv-opt-rx').disabled = rxN === 0;
        document.getElementById('lv-opt-rx').checked = rxN > 0;

        contentEl.style.display = '';
        copyBtn.disabled = (filled + dxN + rxN) === 0;
    })
    .catch(() => {
        loadEl.style.display = 'none';
        emptyEl.style.display = '';
    });
}

function applyLastVisit() {
    const data = LAST_VISIT_DATA;
    if (!data) return;
    const wantClinical = document.getElementById('lv-opt-clinical').checked;
    const wantDx       = document.getElementById('lv-opt-dx').checked;
    const wantRx       = document.getElementById('lv-opt-rx').checked;

    let copied = { clinical: 0, dx: 0, rx: 0 };

    if (wantClinical) {
        const map = {
            chief_complaint: 'chief_complaint',
            history_of_present_illness: 'history_of_present_illness',
            past_medical_history: 'past_medical_history',
            family_history: 'family_history',
            social_history: 'social_history',
            examination_findings: 'examination_findings',
            impression: 'impression',
            plan: 'plan',
        };
        Object.entries(map).forEach(([key, qKey]) => {
            const val = (data[key] || '').toString().trim();
            if (!val) return;
            const q = clinicalQuills[qKey];
            if (!q) return;
            const existingHtml = q.root.innerHTML.trim();
            const existingText = q.getText().trim();
            const incoming = val.replace(/\n/g, '<br>');
            q.root.innerHTML = existingText
                ? existingHtml + '<br>' + incoming
                : incoming;
            copied.clinical++;
        });
    }

    if (wantDx && (data.diagnoses || []).length) {
        const container = document.getElementById('diagnoses-container');
        if (container && container.style.display === 'none' && typeof enterDiagEditMode === 'function') {
            enterDiagEditMode();
        }
        data.diagnoses.forEach((dx, i) => {
            addDiagnosisRow();
            const rows = document.querySelectorAll('#diagnoses-container .diagnosis-row');
            const last = rows[rows.length - 1];
            if (!last) return;
            const nameIn = last.querySelector('[name="diagnoses[name][]"]');
            const codeIn = last.querySelector('[name="diagnoses[icd_code][]"]');
            const typeIn = last.querySelector('[name="diagnoses[type][]"]');
            const stIn   = last.querySelector('[name="diagnoses[status][]"]');
            if (nameIn) nameIn.value = dx.name || '';
            if (codeIn) codeIn.value = dx.icd_code || '';
            if (typeIn) typeIn.value = dx.type || (i === 0 ? 'Primary' : 'Secondary');
            if (stIn)   stIn.value   = dx.status === 'Resolved' ? 'Active' : (dx.status || 'Active');
            copied.dx++;
        });
    }

    if (wantRx && (data.rx_items || []).length) {
        data.rx_items.forEach(item => {
            addRxItem();
            const idx = rxCount;
            const sel = document.getElementById('med-sel-' + idx);
            if (sel && item.medicine_id) {
                const opt = sel.querySelector(`option[value="${item.medicine_id}"]`);
                if (opt) {
                    if (typeof $ !== 'undefined') $(`#med-sel-${idx}`).val(opt.value).trigger('change');
                    else { sel.value = opt.value; sel.dispatchEvent(new Event('change')); }
                }
            }
            if (item.dose)          rxSetVal('dose-'  + idx, item.dose);
            if (item.frequency)     rxSetVal('freq-'  + idx, item.frequency);
            if (item.route)         rxSetVal('route-' + idx, item.route);
            if (item.duration_days) rxSetVal('dur-'   + idx, item.duration_days);
            if (item.instructions)  rxSetVal('instr-' + idx, item.instructions);
            copied.rx++;
        });
    }

    document.getElementById('last-visit-modal').classList.remove('open');

    const parts = [];
    if (copied.clinical) parts.push(`${copied.clinical} clinical field${copied.clinical>1?'s':''}`);
    if (copied.dx)       parts.push(`${copied.dx} diagnos${copied.dx>1?'es':'is'}`);
    if (copied.rx)       parts.push(`${copied.rx} medicine${copied.rx>1?'s':''}`);
    const msg = parts.length ? `Copied ${parts.join(', ')} from last visit. Review and save each section.` : 'Nothing copied.';
    if (typeof showToast === 'function') showToast(msg, 'success');
    else alert(msg);

    if (copied.rx) {
        const rxTab = document.querySelector('.tab-btn[data-tab="rx"]');
        if (rxTab) rxTab.click();
    } else if (copied.dx) {
        const clinTab = document.querySelector('.tab-btn[data-tab="clinical"]');
        if (clinTab) clinTab.click();
    }
}
</script>

{{-- Prescription Template Modal --}}
<div id="rx-template-modal" class="modal-overlay">
    <div class="modal-box" style="max-width:540px">
        <div class="modal-header">
            <i class="fa-solid fa-layer-group" style="color:var(--sc-teal)"></i>
            <h3>Load Prescription Template</h3>
            <button class="modal-close" onclick="document.getElementById('rx-template-modal').classList.remove('open')">&times;</button>
        </div>
        <div class="modal-body">
            <div id="template-loading" style="text-align:center;padding:20px;color:var(--sc-muted)">
                <i class="fa-solid fa-spinner fa-spin"></i> Loading templates...
            </div>
            <div id="template-list" style="display:none"></div>
            <div id="template-empty" style="display:none;text-align:center;padding:30px;color:var(--sc-muted)">
                No templates found. <a href="{{ route('prescription-templates.index') }}" style="color:var(--sc-teal)">Create a template</a>.
            </div>
        </div>
    </div>
</div>

<script>
// ──────────────── ENCOUNTER TEMPLATE (full visit) ────────────────
function openEncTemplateModal() {
    const modal   = document.getElementById('enc-template-modal');
    const listEl  = document.getElementById('enc-tmpl-list');
    const loadEl  = document.getElementById('enc-tmpl-loading');
    const emptyEl = document.getElementById('enc-tmpl-empty');
    modal.classList.add('open');
    listEl.style.display = 'none';
    emptyEl.style.display = 'none';
    loadEl.style.display = '';

    fetch('{{ route("encounter-templates.list") }}', {
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
    })
    .then(r => r.ok ? r.json() : [])
    .then(data => {
        loadEl.style.display = 'none';
        if (!Array.isArray(data) || !data.length) {
            emptyEl.style.display = '';
            return;
        }
        listEl.innerHTML = '';
        data.forEach(tmpl => {
            const card = document.createElement('div');
            card.style.cssText = 'border:1px solid var(--sc-border);border-radius:8px;padding:14px;margin-bottom:10px;cursor:pointer;transition:all .15s';
            card.onmouseenter = () => { card.style.borderColor = 'var(--sc-teal)'; card.style.background = 'rgba(10,180,180,.04)'; };
            card.onmouseleave = () => { card.style.borderColor = 'var(--sc-border)'; card.style.background = ''; };

            const dxCount = (tmpl.diagnoses || []).length;
            const rxCount = (tmpl.prescription_template || []).length;
            const spec    = tmpl.specialization ? `<span class="badge badge-outline" style="margin-left:8px">${escapeHtml(tmpl.specialization)}</span>` : '';

            card.innerHTML = `
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px">
                    <div style="font-weight:700;font-size:14px">${escapeHtml(tmpl.name)}${spec}</div>
                    <div style="font-size:11px;color:var(--sc-muted)">
                        ${dxCount} dx &middot; ${rxCount} rx
                    </div>
                </div>
                ${tmpl.chief_complaint ? `<div style="font-size:12px;color:#475569;margin-top:4px"><strong>CC:</strong> ${escapeHtml(tmpl.chief_complaint).slice(0,160)}${tmpl.chief_complaint.length>160?'…':''}</div>` : ''}
            `;
            card.addEventListener('click', () => {
                applyEncounterTemplate(tmpl);
                modal.classList.remove('open');
            });
            listEl.appendChild(card);
        });
        listEl.style.display = '';
    })
    .catch(() => {
        loadEl.style.display = 'none';
        emptyEl.style.display = '';
    });
}

// Render the "Suggested Lab Order" card (Labs tab) from a set of panel/test
// names — shared by applyEncounterTemplate() (template-driven) and
// fetchInvestigationSuggestions() (diagnosis-driven). Each pill has its own
// × to drop just that one item before placing the order; the whole card can
// still be dismissed via the existing header button.
function renderSuggestedLabOrder(panelNames, testNames, clinicalInfo, titleText, procedures) {
    const wrap   = document.getElementById('suggested-lab-order');
    const list   = document.getElementById('suggested-lab-order-list');
    const fields = document.getElementById('suggested-lab-order-fields');
    const clin   = document.getElementById('suggested-lab-clinical');
    const title  = document.getElementById('suggested-lab-order-title');
    const procWrap = document.getElementById('suggested-lab-order-procedures');
    if (!wrap || !list || !fields) return;

    panelNames = (panelNames || []).filter(Boolean);
    testNames  = (testNames  || []).filter(Boolean);
    procedures = (procedures || []).filter(p => p && p.name && p.url);
    if (!panelNames.length && !testNames.length && !procedures.length) { wrap.style.display = 'none'; return; }

    list.innerHTML   = '';
    fields.innerHTML = '';

    const addPill = (name, cls, icon) => {
        const pill = document.createElement('span');
        pill.className = cls;
        pill.style.cssText = 'font-size:12px;padding:5px 6px 5px 10px;display:inline-flex;align-items:center;gap:6px';
        pill.innerHTML = `${icon ? `<i class="fa-solid fa-layer-group"></i> ` : ''}${escapeHtml(name)}`;
        const x = document.createElement('button');
        x.type = 'button';
        x.setAttribute('aria-label', 'Remove');
        x.style.cssText = 'background:none;border:none;cursor:pointer;padding:0 2px;line-height:1;color:inherit;opacity:.6;font-size:13px';
        x.innerHTML = '&times;';
        pill.appendChild(x);
        list.appendChild(pill);
        const inp = document.createElement('input');
        inp.type = 'hidden'; inp.name = (icon ? 'panel_names[]' : 'test_names[]'); inp.value = name;
        fields.appendChild(inp);
        x.addEventListener('click', () => { pill.remove(); inp.remove(); if (!list.children.length) wrap.style.display = 'none'; });
    };

    panelNames.forEach(name => addPill(name, 'badge badge-teal', true));
    testNames.forEach(name => addPill(name, 'badge badge-outline', false));

    // Procedures (ECG/Echo/Chest X-ray) are separate records, not lab order
    // items — render as direct links to that module's create form instead.
    if (procWrap) {
        procWrap.innerHTML = '';
        procedures.forEach(p => {
            const link = document.createElement('a');
            link.href = p.url;
            link.className = 'badge badge-teal';
            link.style.cssText = 'font-size:12px;padding:5px 10px;text-decoration:none;display:inline-flex;align-items:center;gap:6px';
            link.innerHTML = `<i class="fa-solid fa-heart-pulse"></i> ${escapeHtml(p.name)}`;
            procWrap.appendChild(link);
        });
        procWrap.style.display = procedures.length ? 'flex' : 'none';
    }

    if (clin) clin.value = clinicalInfo || '';
    if (title) title.textContent = titleText || '';
    wrap.style.display = '';
}

// After a diagnosis is saved, fetch rule+AI based investigation suggestions
// and populate the same Suggested Lab Order card. Silent no-op on failure —
// this is a convenience, not a required step in saving the diagnosis.
function fetchInvestigationSuggestions() {
    fetch(`{{ url("/encounters") }}/${encounterId}/suggest-investigations`, { headers: { 'Accept': 'application/json' } })
        .then(r => r.ok ? r.json() : Promise.reject())
        .then(res => {
            const panels = res.panels || [];
            const tests  = res.tests  || [];
            const procedures = res.procedures || [];
            if (!panels.length && !tests.length && !procedures.length) return;
            const dx = Array.from(document.querySelectorAll('#diag-read-view .diag-read-name'))
                .map(el => el.textContent.trim()).filter(Boolean).join('; ');
            const clinicalInfo = 'Dx: ' + dx + (res.ai_used ? ' (rule + AI suggested)' : ' (rule suggested)');
            renderSuggestedLabOrder(panels, tests, clinicalInfo, 'based on diagnosis', procedures);
        })
        .catch(() => {});
}

function applyEncounterTemplate(tmpl) {
    let added = { cc:false, notes:false, dx:0, rx:0 };

    // 1) Chief complaint → cq-chief-complaint Quill (append if existing)
    if (tmpl.chief_complaint && clinicalQuills.chief_complaint) {
        const q = clinicalQuills.chief_complaint;
        const existing = q.getText().trim();
        const incoming = tmpl.chief_complaint.replace(/\n/g,'<br>');
        q.root.innerHTML = existing
            ? q.root.innerHTML + '<br>' + incoming
            : incoming;
        added.cc = true;
    }

    // 2) Clinical notes (SOAP) → split S/O/A/P into matching Quills if recognisable
    if (tmpl.clinical_notes) {
        const sections = parseSoap(tmpl.clinical_notes);
        const map = [
            ['S', 'history_of_present_illness'],
            ['O', 'examination_findings'],
            ['A', 'impression'],
            ['P', 'plan'],
        ];
        let placedAny = false;
        map.forEach(([k, key]) => {
            if (sections[k] && clinicalQuills[key]) {
                const q = clinicalQuills[key];
                const existing = q.getText().trim();
                const incoming = sections[k].replace(/\n/g,'<br>');
                q.root.innerHTML = existing
                    ? q.root.innerHTML + '<br>' + incoming
                    : incoming;
                placedAny = true;
            }
        });
        // Fallback — if SOAP markers not found, dump the whole block into Examination Findings
        if (!placedAny && clinicalQuills.examination_findings) {
            const q = clinicalQuills.examination_findings;
            const existing = q.getText().trim();
            const incoming = tmpl.clinical_notes.replace(/\n/g,'<br>');
            q.root.innerHTML = existing
                ? q.root.innerHTML + '<br>' + incoming
                : incoming;
        }
        added.notes = true;
    }

    // 3) Diagnoses → add rows in edit mode
    (tmpl.diagnoses || []).forEach(dx => {
        if (typeof enterDiagEditMode === 'function') {
            const container = document.getElementById('diagnoses-container');
            if (container && container.style.display === 'none') enterDiagEditMode();
        }
        addDiagnosisRow();
        const rows = document.querySelectorAll('#diagnoses-container .diagnosis-row');
        const last = rows[rows.length - 1];
        if (!last) return;
        // Parse "Name (CODE)" → split out code if present
        const m = String(dx).match(/^(.*?)\s*\(([A-Z][0-9A-Z.]+)\)\s*$/);
        const name = m ? m[1].trim() : String(dx).trim();
        const code = m ? m[2].trim() : '';
        const nameIn = last.querySelector('[name="diagnoses[name][]"]');
        const codeIn = last.querySelector('[name="diagnoses[icd_code][]"]');
        const typeIn = last.querySelector('[name="diagnoses[type][]"]');
        if (nameIn) nameIn.value = name;
        if (codeIn) codeIn.value = code;
        if (typeIn && added.dx === 0) typeIn.value = 'Primary';
        added.dx++;
    });

    // 4) Prescription items → add Rx rows (mirrors loadTemplateItems matching logic)
    (tmpl.prescription_template || []).forEach(item => {
        addRxItem();
        const idx = rxCount;
        const sel = document.getElementById('med-sel-' + idx);
        if (sel && item.drug_name) {
            const search = String(item.drug_name).toLowerCase().trim();
            const opts   = Array.from(sel.options);
            let match = opts.find(o => o.text.toLowerCase().replace(/\s*\[.*?\]/g,'').trim() === search);
            if (!match) match = opts.find(o => o.text.toLowerCase().includes(search));
            if (!match) {
                const bare = search.replace(/^(tab|cap|inj|mdi|syp|inhaler|sachet)\s+/i,'');
                match = opts.find(o => {
                    const ot = o.text.toLowerCase().replace(/\s*\[.*?\]/g,'').trim()
                                     .replace(/^(tab|cap|inj|mdi|syp|inhaler|sachet)\s+/i,'');
                    return bare.includes(ot) || ot.includes(bare);
                });
            }
            if (match) {
                if (typeof $ !== 'undefined') {
                    $(`#med-sel-${idx}`).val(match.value).trigger('change');
                } else {
                    sel.value = match.value;
                    sel.dispatchEvent(new Event('change'));
                }
            }
        }
        if (item.dose)         rxSetVal('dose-'  + idx, item.dose);
        if (item.frequency)    rxSetVal('freq-'  + idx, item.frequency);
        if (item.route)        rxSetVal('route-' + idx, item.route);
        if (item.instructions) rxSetVal('instr-' + idx, item.instructions);
        added.rx++;
    });

    // 5) Lab orders (tests + panels) → render Suggested Lab Order card on Lab Orders tab
    const labs = tmpl.lab_orders || {};
    const labTests  = (labs.tests  || []).filter(Boolean);
    const labPanels = (labs.panels || []).filter(Boolean);
    if (labTests.length || labPanels.length) {
        const cc = (tmpl.chief_complaint || '').slice(0, 200);
        const dx = (tmpl.diagnoses || []).join('; ');
        const clinicalInfo = (cc ? 'CC: ' + cc : '') + (dx ? ' | Dx: ' + dx : '');
        renderSuggestedLabOrder(labPanels, labTests, clinicalInfo, 'from template');
        added.labs = labTests.length + labPanels.length;
    }

    const bits = [];
    if (added.cc || added.notes) bits.push('clinical notes');
    if (added.dx) bits.push(`${added.dx} diagnosis${added.dx>1?'es':''}`);
    if (added.rx) bits.push(`${added.rx} drug${added.rx>1?'s':''}`);
    if (added.labs) bits.push(`${added.labs} lab item${added.labs>1?'s':''}`);
    showToast(`Template applied — ${bits.join(', ')}. Review each tab and Save.`, 'success');
}

// Split a SOAP block like "S: ...\nO: ...\nA: ...\nP: ..." into a {S,O,A,P} object.
function parseSoap(text) {
    const out = {};
    if (!text) return out;
    const re = /(^|\n)\s*([SOAP])\s*:\s*([\s\S]*?)(?=(?:\n\s*[SOAP]\s*:)|$)/g;
    let m;
    while ((m = re.exec(text)) !== null) {
        out[m[2]] = (m[3] || '').trim();
    }
    return out;
}

function openTemplateModal() {
    const modal = document.getElementById('rx-template-modal');
    modal.classList.add('open');
    const listEl = document.getElementById('template-list');
    const loadEl = document.getElementById('template-loading');
    const emptyEl = document.getElementById('template-empty');
    listEl.style.display = 'none';
    emptyEl.style.display = 'none';
    loadEl.style.display = '';

    $.get('{{ route("prescription-templates.index") }}', function() {}, 'html')
        .fail(function() {})
        .always(function() {});

    // Actually fetch via JSON
    $.get('{{ url("/prescription-templates") }}', {}, function(html) {
        // We use the items endpoint instead
    });

    // Fetch template list via a simple JSON approach
    fetch('{{ url("/prescription-templates") }}', {
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
    }).then(r => {
        if (!r.ok) return [];
        return r.json().catch(() => null);
    }).then(data => {
        loadEl.style.display = 'none';
        if (!data || !Array.isArray(data) || !data.length) {
            emptyEl.style.display = '';
            return;
        }
        listEl.innerHTML = '';
        data.forEach(tmpl => {
            const div = document.createElement('div');
            div.style.cssText = 'border:1px solid var(--sc-border);border-radius:8px;padding:14px;margin-bottom:10px;cursor:pointer;transition:all .15s';
            div.onmouseenter = () => { div.style.borderColor = 'var(--sc-teal)'; div.style.background = 'rgba(10,180,180,.04)'; };
            div.onmouseleave = () => { div.style.borderColor = 'var(--sc-border)'; div.style.background = ''; };
            div.innerHTML = `<div style="font-weight:700;font-size:13px;margin-bottom:6px">${tmpl.name}</div>
                <div style="display:flex;flex-wrap:wrap;gap:5px">
                    ${(tmpl.items || []).map(it => `<span class="badge badge-teal">${it.medicine_name}</span>`).join('')}
                </div>`;
            div.addEventListener('click', () => {
                loadTemplateItems(tmpl.id, tmpl.items || []);
                document.getElementById('rx-template-modal').classList.remove('open');
            });
            listEl.appendChild(div);
        });
        listEl.style.display = '';
    }).catch(() => {
        loadEl.style.display = 'none';
        emptyEl.style.display = '';
    });
}

function loadTemplateItems(templateId, items) {
    items.forEach(item => {
        addRxItem();
        const idx = rxCount;
        const sel = document.getElementById('med-sel-' + idx);
        if (sel) {
            const search = item.medicine_name.toLowerCase().trim();
            const opts   = Array.from(sel.options);
            // 1. Exact name match (strip [generic] and strength suffix from option text)
            let match = opts.find(o => o.text.toLowerCase().replace(/\s*\[.*?\]/g,'').trim() === search);
            // 2. Option text contains full template name
            if (!match) match = opts.find(o => o.text.toLowerCase().includes(search));
            // 3. Template name contains the bare option text (generic in brackets)
            if (!match) {
                const bare = search.replace(/^(tab|cap|inj|mdi|syp|inhaler)\s+/i,'');
                match = opts.find(o => {
                    const ot = o.text.toLowerCase().replace(/\s*\[.*?\]/g,'').trim()
                                     .replace(/^(tab|cap|inj|mdi|syp|inhaler)\s+/i,'');
                    return bare.includes(ot) || ot.includes(bare);
                });
            }
            if (match) {
                if (typeof $ !== 'undefined') {
                    $(`#med-sel-${idx}`).val(match.value).trigger('change');
                } else {
                    sel.value = match.value;
                    sel.dispatchEvent(new Event('change'));
                }
            }
        }
        if (item.dosage)       rxSetVal('dose-'  + idx, item.dosage);
        if (item.frequency)    rxSetVal('freq-'  + idx, item.frequency);
        if (item.duration)     rxSetVal('dur-'   + idx, item.duration);
        if (item.instructions) rxSetVal('instr-' + idx, item.instructions);
    });
}

// ---- ALLERGY TAG-INPUT ----
const PATIENT_ID_FOR_ALLERGIES = {{ $encounter->patient_id }};
const ALLERGY_STORE_URL = '{{ route("patients.allergies.store", $encounter->patient) }}';
const CSRF_TOKEN = '{{ csrf_token() }}';

function addAllergy() {
    const input = document.getElementById('new-allergen-input');
    const sev   = document.getElementById('new-allergen-severity');
    const name  = input.value.trim();
    if (!name) { input.focus(); return; }
    fetch(ALLERGY_STORE_URL, {
        method: 'POST',
        headers: {'Content-Type':'application/json','X-CSRF-TOKEN': CSRF_TOKEN},
        body: JSON.stringify({allergen: name, severity: sev.value})
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) return;
        const pa = data.allergy;
        const colors = {
            severe:   {bg:'#fef2f2',color:'#991b1b',border:'#fca5a5'},
            moderate: {bg:'#fffbeb',color:'#92400e',border:'#fcd34d'},
            mild:     {bg:'#f0fdf4',color:'#166534',border:'#86efac'},
            unknown:  {bg:'#f1f5f9',color:'#334155',border:'#cbd5e1'},
        };
        const c = colors[pa.severity] || colors.unknown;
        const tag = document.createElement('span');
        tag.className = 'allergy-tag';
        tag.dataset.id = pa.id;
        tag.style.cssText = `display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:600;background:${c.bg};color:${c.color};border:1px solid ${c.border}`;
        tag.innerHTML = `${escapeHtml(pa.allergen)} <span style="font-size:10px;opacity:.7">(${pa.severity})</span><button type="button" onclick="removeAllergy(${pa.id},this)" style="background:none;border:none;cursor:pointer;padding:0 0 0 3px;color:inherit;opacity:.7;font-size:14px;line-height:1">&times;</button>`;
        document.getElementById('allergy-tags').appendChild(tag);
        // also push to PATIENT_ALLERGIES_ENC for live drug checking
        PATIENT_ALLERGIES_ENC.push(pa.allergen.toLowerCase());
        input.value = '';
        sev.value = 'unknown';
        showToast('Allergy added', 'success');
    });
}

// Ctrl/Cmd+S — save the form for the currently active tab on the encounter page.
// Hooked here (not in the layout) since these handlers only exist on this view.
document.addEventListener('keydown', function(e) {
    if (!((e.metaKey || e.ctrlKey) && (e.key === 's' || e.key === 'S'))) return;
    const activePane = document.querySelector('#enc-tabs .tab-pane.active');
    if (!activePane) return;
    e.preventDefault();
    const tab = activePane.id;
    if (tab === 'tab-clinical') {
        const form = activePane.querySelector('form');
        if (form) form.requestSubmit ? form.requestSubmit() : form.dispatchEvent(new Event('submit', { cancelable: true }));
    } else if (tab === 'tab-diagnosis') {
        if (typeof saveDiagnoses === 'function') saveDiagnoses();
    } else if (tab === 'tab-rx') {
        if (typeof savePrescription === 'function') savePrescription();
    } else if (tab === 'tab-notes') {
        if (typeof saveNewNote === 'function') saveNewNote();
    } else if (tab.startsWith('tab-form-')) {
        const form = activePane.querySelector('form.spec-form');
        if (form) form.requestSubmit();
    }
});

function removeAllergy(id, btn) {
    if (!confirm('Remove this allergy from patient record?')) return;
    fetch(ALLERGY_STORE_URL.replace('/allergies', '/allergies/' + id), {
        method: 'DELETE',
        headers: {'X-CSRF-TOKEN': CSRF_TOKEN}
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) return;
        const tag = btn.closest('.allergy-tag');
        const name = tag.childNodes[0].textContent.trim().toLowerCase();
        tag.remove();
        const idx = PATIENT_ALLERGIES_ENC.indexOf(name);
        if (idx > -1) PATIENT_ALLERGIES_ENC.splice(idx, 1);
        showToast('Allergy removed', 'success');
    });
}
</script>

@if(!$encounter->bill)
<script>
// ── QUICK BILL ──
const QB_PRESET = @json($quickBillItems);
let qbRows = [];

function openQuickBill() {
    qbRows = QB_PRESET.map((r, i) => ({ ...r, id: i }));
    qbRender();
    qbRecalc();
    document.getElementById('quick-bill-modal').classList.add('open');
}

function qbRender() {
    const tbody = document.getElementById('qb-items-body');
    if (!qbRows.length) {
        tbody.innerHTML = '<tr><td colspan="6" style="padding:16px;text-align:center;color:var(--sc-muted);font-size:13px">No items yet — add from the catalogue below.</td></tr>';
        return;
    }
    tbody.innerHTML = qbRows.map((r, i) => `
        <tr style="border-top:1px solid var(--sc-border)">
            <td style="padding:8px 12px">
                <input type="hidden" name="items[${i}][name]" value="${esc(r.name)}">
                <input type="hidden" name="items[${i}][type]" value="${esc(r.type)}">
                <span style="font-size:13px;font-weight:500">${esc(r.name)}</span>
            </td>
            <td style="padding:8px 12px">
                <span class="badge badge-teal" style="font-size:10px">${esc(r.type)}</span>
            </td>
            <td style="padding:8px 12px;text-align:center">
                <input type="number" name="items[${i}][qty]" value="${r.qty}" min="1" style="width:54px;padding:4px 6px;border:1.5px solid var(--sc-border);border-radius:6px;text-align:center;font-size:13px;font-family:inherit"
                    oninput="qbRows[${i}].qty=Math.max(1,+this.value||1);qbRecalc()">
            </td>
            <td style="padding:8px 12px;text-align:right">
                <input type="number" name="items[${i}][price]" value="${r.price}" min="0" step="0.01" style="width:88px;padding:4px 8px;border:1.5px solid var(--sc-border);border-radius:6px;text-align:right;font-size:13px;font-family:inherit"
                    oninput="qbRows[${i}].price=+this.value||0;qbRecalc()">
            </td>
            <td style="padding:8px 12px;text-align:right;font-weight:600;font-size:13px" id="qb-row-total-${i}">
                ₹${fmtN(r.qty * r.price)}
            </td>
            <td style="padding:8px 4px;text-align:center">
                <input type="hidden" name="items[${i}][discount]" value="0">
                <button type="button" style="background:none;border:none;color:var(--sc-muted);cursor:pointer;font-size:14px;padding:2px 6px" onclick="qbRemove(${i})" title="Remove">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </td>
        </tr>
    `).join('');
    qbRecalc();
}

function qbRemove(i) {
    qbRows.splice(i, 1);
    qbRender();
}

function qbAddRow() {
    qbRows.push({ id: Date.now(), name: 'Service', type: 'Other', price: 0, qty: 1 });
    qbRender();
    // Focus the last name field
    const tbody = document.getElementById('qb-items-body');
    const inputs = tbody.querySelectorAll('input[type="number"]');
    if (inputs.length) inputs[inputs.length - 2].focus();
}

function qbAddFromCatalogue() {
    const sel = document.getElementById('qb-service-picker');
    const opt = sel.options[sel.selectedIndex];
    if (!opt || !opt.value) return;
    qbRows.push({
        id: Date.now(),
        name:  opt.dataset.name,
        type:  opt.dataset.type,
        price: parseFloat(opt.dataset.price) || 0,
        qty:   1,
    });
    sel.selectedIndex = 0;
    qbRender();
}

function qbRecalc() {
    let sub = 0;
    qbRows.forEach((r, i) => {
        const rowTotal = (r.qty || 1) * (r.price || 0);
        sub += rowTotal;
        const el = document.getElementById('qb-row-total-' + i);
        if (el) el.textContent = '₹' + fmtN(rowTotal);
    });
    const discPct  = parseFloat(document.getElementById('qb-discount')?.value || 0) || 0;
    const discAmt  = sub * discPct / 100;
    const total    = sub - discAmt;
    const paid     = parseFloat(document.getElementById('qb-paid')?.value || 0) || 0;
    const due      = Math.max(0, total - paid);

    const set = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
    set('qb-subtotal',     '₹' + fmtN(sub));
    set('qb-discount-amt', '−₹' + fmtN(discAmt));
    set('qb-total',        '₹' + fmtN(total));
    set('qb-paid-disp',    '₹' + fmtN(paid));
    set('qb-due',          '₹' + fmtN(due));
}

function qbPayFull() {
    const total = parseFloat(document.getElementById('qb-total')?.textContent.replace(/[₹,]/g,'')) || 0;
    const inp   = document.getElementById('qb-paid');
    if (inp) { inp.value = total.toFixed(2); qbRecalc(); }
}

function fmtN(n) { return Number(n || 0).toLocaleString('en-IN', { maximumFractionDigits: 2 }); }
function esc(s)  { return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }

document.getElementById('quick-bill-form')?.addEventListener('submit', function() {
    const btn = document.getElementById('qb-submit-btn');
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Creating…'; }
});
</script>
@endif

{{-- ── AI Clinical Assistant Panel ───────────────────────────────────────── --}}
@if(app(\App\Services\LicenseService::class)->isEnabled('ai_assistant'))
<div id="ai-panel" style="
    position:fixed;right:-420px;bottom:0;width:420px;height:100vh;
    background:var(--sc-card-bg,#fff);border-left:2px solid var(--sc-border,#e2e8f0);
    display:flex;flex-direction:column;z-index:9999;
    box-shadow:-4px 0 24px rgba(0,0,0,.12);transition:right .3s ease;
">
    {{-- Header --}}
    <div style="padding:16px 18px;border-bottom:1px solid var(--sc-border);background:linear-gradient(135deg,#0f172a,#1e3a5f);color:#fff;display:flex;align-items:center;gap:10px;flex-shrink:0">
        <div style="width:36px;height:36px;background:rgba(255,255,255,.15);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <i class="fa-solid fa-robot" style="font-size:1.1rem"></i>
        </div>
        <div style="flex:1;min-width:0">
            <div style="font-weight:700;font-size:14px">AI Clinical Assistant</div>
            <div style="font-size:11px;opacity:.7">{{ $encounter->patient->full_name }} · {{ $encounter->patient->age }}</div>
        </div>
        <button onclick="toggleAiPanel()" style="background:rgba(255,255,255,.15);border:none;color:#fff;border-radius:6px;padding:6px 10px;cursor:pointer">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    {{-- Allergy warning if applicable --}}
    @if($patientAllergyRecords->isNotEmpty())
    <div style="background:#fef2f2;border-bottom:1px solid #fca5a5;padding:8px 16px;font-size:12px;color:#991b1b;display:flex;align-items:center;gap:8px;flex-shrink:0">
        <i class="fa-solid fa-triangle-exclamation"></i>
        <span><strong>Allergies:</strong>
        @foreach($patientAllergyRecords as $pa)
            <span style="background:#fca5a5;padding:1px 6px;border-radius:10px;margin-left:4px">{{ $pa->allergen }}</span>
        @endforeach
        </span>
    </div>
    @endif

    {{-- Suggested prompts --}}
    <div id="ai-suggestions" style="padding:12px 16px;border-bottom:1px solid var(--sc-border);flex-shrink:0">
        <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:var(--sc-muted);margin-bottom:8px">Quick Ask</div>
        <div style="display:flex;flex-wrap:wrap;gap:6px">
            @php $suggestions = [
                'Differential diagnoses for this presentation',
                'Check drug interactions in current Rx',
                'Suggested investigations',
                'Treatment guidelines',
                'Red flag symptoms to watch for',
            ]; @endphp
            @foreach($suggestions as $s)
            <button onclick="aiSuggest(this)" data-text="{{ $s }}" class="ai-chip">{{ $s }}</button>
            @endforeach
        </div>
    </div>

    {{-- Messages --}}
    <div id="ai-messages" style="flex:1;overflow-y:auto;padding:16px;display:flex;flex-direction:column;gap:12px"></div>

    {{-- Input --}}
    <div style="padding:12px 16px;border-top:1px solid var(--sc-border);flex-shrink:0;background:var(--sc-card-bg,#fff)">
        <div style="display:flex;gap:8px;align-items:flex-end">
            <textarea id="ai-input" placeholder="Ask a clinical question…" rows="2" style="flex:1;resize:none;border:1px solid var(--sc-border);border-radius:8px;padding:8px 12px;font-size:13px;font-family:inherit;outline:none;background:#fff" onkeydown="aiKeydown(event)"></textarea>
            <button id="ai-send-btn" onclick="aiSend()" style="background:#1e3a5f;color:#fff;border:none;border-radius:8px;padding:10px 14px;cursor:pointer;flex-shrink:0;transition:background .2s">
                <i class="fa-solid fa-paper-plane"></i>
            </button>
        </div>
        <div style="font-size:10px;color:var(--sc-muted);margin-top:6px">AI-generated content. Always apply clinical judgement.</div>
    </div>
</div>

{{-- Floating trigger button --}}
<button id="ai-fab" onclick="toggleAiPanel()" title="AI Clinical Assistant" style="
    position:fixed;bottom:24px;right:24px;width:54px;height:54px;
    background:linear-gradient(135deg,#0f172a,#1e3a5f);color:#fff;border:none;border-radius:50%;
    font-size:1.3rem;cursor:pointer;z-index:9998;
    box-shadow:0 4px 16px rgba(0,0,0,.3);transition:transform .2s;
    display:flex;align-items:center;justify-content:center;
" onmouseenter="this.style.transform='scale(1.1)'" onmouseleave="this.style.transform='scale(1)'">
    <i class="fa-solid fa-robot"></i>
</button>
@endif

<style>
.ai-chip {
    background:var(--sc-bg,#f8fafc);
    border:1px solid var(--sc-border,#e2e8f0);
    border-radius:20px;
    padding:4px 12px;
    font-size:12px;
    cursor:pointer;
    color:var(--sc-text,#1e293b);
    transition:background .15s,border-color .15s;
}
.ai-chip:hover { background:#e0f2fe;border-color:#7dd3fc; }
.ai-msg-user {
    align-self:flex-end;
    background:#1e3a5f;
    color:#fff;
    border-radius:12px 12px 2px 12px;
    padding:10px 14px;
    font-size:13px;
    max-width:85%;
    word-break:break-word;
}
.ai-msg-ai {
    align-self:flex-start;
    background:var(--sc-bg,#f8fafc);
    border:1px solid var(--sc-border,#e2e8f0);
    border-radius:2px 12px 12px 12px;
    padding:10px 14px;
    font-size:13px;
    max-width:100%;
    line-height:1.6;
    white-space:pre-wrap;
    word-break:break-word;
}
.ai-msg-ai strong { color:var(--sc-navy,#1e3a5f); }
.ai-thinking {
    display:flex;gap:4px;align-items:center;padding:10px 14px;
    background:var(--sc-bg,#f8fafc);border:1px solid var(--sc-border);
    border-radius:2px 12px 12px 12px;align-self:flex-start;
}
.ai-thinking span {
    width:6px;height:6px;border-radius:50%;background:#94a3b8;
    animation:ai-bounce .8s infinite ease-in-out both;
}
.ai-thinking span:nth-child(2){animation-delay:.16s}
.ai-thinking span:nth-child(3){animation-delay:.32s}
@keyframes ai-bounce{0%,80%,100%{transform:scale(0)}40%{transform:scale(1)}}
#ai-panel[data-open="true"] { right:0 !important; }
</style>

<script>
const aiChatUrl = '{{ route("encounters.ai-chat", $encounter) }}';
const aiCsrf    = '{{ csrf_token() }}';
let aiHistory   = [];
let aiOpen      = false;

function toggleAiPanel() {
    aiOpen = !aiOpen;
    const panel = document.getElementById('ai-panel');
    const fab   = document.getElementById('ai-fab');
    panel.dataset.open = aiOpen;
    fab.style.display  = aiOpen ? 'none' : 'flex';
    if (aiOpen && aiHistory.length === 0) {
        appendAiMsg('ai', '👋 Hello, Doctor. I have loaded the patient context including vitals, diagnoses, allergies and current medications. Ask me anything about this case.');
    }
    if (aiOpen) setTimeout(() => document.getElementById('ai-input')?.focus(), 300);
}

function aiSuggest(btn) {
    document.getElementById('ai-input').value = btn.dataset.text;
    aiSend();
}

function aiKeydown(e) {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); aiSend(); }
}

function appendAiMsg(role, text) {
    const msgs = document.getElementById('ai-messages');
    const div  = document.createElement('div');
    div.className = role === 'user' ? 'ai-msg-user' : 'ai-msg-ai';
    div.textContent = text;
    msgs.appendChild(div);
    msgs.scrollTop = msgs.scrollHeight;
    return div;
}

function appendThinking() {
    const msgs = document.getElementById('ai-messages');
    const div  = document.createElement('div');
    div.className = 'ai-thinking';
    div.innerHTML = '<span></span><span></span><span></span>';
    msgs.appendChild(div);
    msgs.scrollTop = msgs.scrollHeight;
    return div;
}

async function aiSend() {
    const input = document.getElementById('ai-input');
    const btn   = document.getElementById('ai-send-btn');
    const msg   = input.value.trim();
    if (!msg || btn.disabled) return;

    appendAiMsg('user', msg);
    aiHistory.push({ role: 'user', content: msg });
    input.value = '';
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';

    const thinking = appendThinking();

    try {
        const res = await fetch(aiChatUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': aiCsrf,
                'Accept': 'text/event-stream',
            },
            body: JSON.stringify({ message: msg, history: aiHistory.slice(-10) }),
        });

        if (!res.ok) {
            const err = await res.json();
            thinking.remove();
            appendAiMsg('ai', '⚠️ ' + (err.error || 'Error contacting AI.'));
            return;
        }

        thinking.remove();
        const aiDiv = appendAiMsg('ai', '');
        const msgs  = document.getElementById('ai-messages');
        let fullText = '';

        const reader = res.body.getReader();
        const decoder = new TextDecoder();
        let buffer = '';

        while (true) {
            const { done, value } = await reader.read();
            if (done) break;
            buffer += decoder.decode(value, { stream: true });
            const lines = buffer.split('\n');
            buffer = lines.pop();
            for (const line of lines) {
                if (!line.startsWith('data: ')) continue;
                const payload = line.slice(6).trim();
                if (payload === '[DONE]') break;
                try {
                    const d = JSON.parse(payload);
                    if (d.text) {
                        fullText += d.text;
                        aiDiv.textContent = fullText;
                        msgs.scrollTop = msgs.scrollHeight;
                    }
                } catch {}
            }
        }

        if (fullText) aiHistory.push({ role: 'assistant', content: fullText });

    } catch (e) {
        thinking.remove();
        appendAiMsg('ai', '⚠️ Network error. Please try again.');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-paper-plane"></i>';
    }
}
scTrackPatient({{ $encounter->patient->id }}, @json($encounter->patient->full_name), @json($encounter->patient->jhc_id), @json(route('patients.show', $encounter->patient)));
window.scPatientVisitUrl = @json(route('appointments.create', ['patient_id' => $encounter->patient_id]));

/* ===================== CLINICAL REMINDERS (CDS) ===================== */
const CDS_URL      = @json(route('encounters.reminders', $encounter));
const CDS_PATIENT  = { age: {{ (int) $encounter->patient->age_years }}, gender: @json($encounter->patient->gender) };
const CDS_VITALS   = ['bp_systolic','bp_diastolic','pulse','temperature','spo2','respiratory_rate','weight','height','blood_glucose'];
const CDS_TEXT     = ['chief_complaint','history_of_present_illness','past_medical_history','family_history','social_history','examination_findings','impression','plan'];
const CDS_SEV      = { urgent: '#ef4444', warning: '#f59e0b', info: '#3b82f6' };
let   cdsPopupShown = false;
let   cdsTimer      = null;
let   cdsLastSig    = '';

function cdsCollect() {
    const data = { age: CDS_PATIENT.age, gender: CDS_PATIENT.gender };
    CDS_VITALS.forEach(function (n) {
        const el = document.querySelector('#vitals-card [name="' + n + '"]');
        if (el && el.value !== '') data[n] = el.value;
    });
    if (typeof clinicalQuills !== 'undefined' && clinicalQuills) {
        CDS_TEXT.forEach(function (k) {
            if (clinicalQuills[k]) data[k] = clinicalQuills[k].getText();
        });
    }
    return data;
}

function cdsRow(s) {
    const dot = CDS_SEV[s.severity] || '#3b82f6';
    return '<div style="display:flex;gap:9px;align-items:flex-start;padding:8px 2px;border-bottom:1px solid var(--sc-border)">'
        + '<span title="' + s.severity + '" style="margin-top:4px;width:8px;height:8px;border-radius:50%;flex:none;background:' + dot + '"></span>'
        + '<i class="fa-solid ' + s.icon + '" style="color:' + s.color + ';margin-top:2px;flex:none;width:16px;text-align:center"></i>'
        + '<div style="min-width:0"><div style="font-size:12.5px;font-weight:600;color:var(--sc-navy)">' + cdsEsc(s.name)
        + ' <span style="font-weight:400;color:' + s.color + ';font-size:10.5px">· ' + cdsEsc(s.category_label) + '</span></div>'
        + '<div style="font-size:12px;color:var(--sc-navy-soft,#475569);line-height:1.45">' + cdsEsc(s.suggestion) + '</div></div></div>';
}

function cdsEsc(t) { const d = document.createElement('div'); d.textContent = t == null ? '' : t; return d.innerHTML; }

function cdsRender(list) {
    const panel = document.getElementById('cds-panel');
    const n = list.length;
    document.getElementById('cds-panel-count').textContent = n;
    document.getElementById('cds-popup-count').textContent = n;
    if (!n) { panel.style.display = 'none'; return; }
    const html = list.map(cdsRow).join('');
    document.getElementById('cds-panel-list').innerHTML = html;
    document.getElementById('cds-popup-list').innerHTML = html;
    panel.style.display = '';
}

function cdsOpenPopup() { document.getElementById('cds-popup').classList.add('open'); }
function cdsClosePopup() { document.getElementById('cds-popup').classList.remove('open'); }

function cdsEvaluate(showPopup) {
    const data = cdsCollect();
    const sig = JSON.stringify(data);
    if (sig === cdsLastSig && !showPopup) return;   // nothing changed
    cdsLastSig = sig;
    $.post(CDS_URL, data, function (res) {
        if (!res || !res.success) return;
        cdsRender(res.suggestions || []);
        if (res.count > 0 && showPopup && !cdsPopupShown) { cdsOpenPopup(); cdsPopupShown = true; }
    });
}

function cdsEvaluateSoon() { clearTimeout(cdsTimer); cdsTimer = setTimeout(function () { cdsEvaluate(false); }, 800); }

document.addEventListener('DOMContentLoaded', function () {
    // Re-evaluate as the doctor edits vitals…
    document.querySelectorAll('#vitals-card [name]').forEach(function (el) {
        el.addEventListener('change', cdsEvaluateSoon);
        el.addEventListener('input',  cdsEvaluateSoon);
    });
    // …and as they type clinical notes (Quill editors are ready by now).
    if (typeof clinicalQuills !== 'undefined' && clinicalQuills) {
        Object.values(clinicalQuills).forEach(function (q) { q.on('text-change', cdsEvaluateSoon); });
    }
    // Initial pass from the saved encounter → auto-popup if anything is due.
    setTimeout(function () { cdsEvaluate(true); }, 400);
});
</script>
@endpush
