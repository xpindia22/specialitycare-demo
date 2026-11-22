{{-- FILE: resources/views/patients/show.blade.php --}}
@extends('layouts.app')
@section('title', $patient->full_name)
@section('page-title', 'Patient Profile')
@section('patient-context')
<div class="sc-patient-bar" style="gap:10px">
    <span class="spb-name">
        <i class="fa-solid fa-circle-user"></i>
        <a href="{{ route('patients.show', $patient) }}">{{ $patient->full_name }}</a>
        <span class="spb-jhc">{{ $patient->jhc_id }}</span>
    </span>
    <div class="spb-actions">
        @if($currentAdmission)
            <a href="{{ route('admissions.show', $currentAdmission) }}" class="btn btn-navy"><i class="fa-solid fa-bed-pulse"></i> IPD: {{ $currentAdmission->ipd_no }}</a>
        @else
            <a href="{{ route('admissions.create', ['patient_id' => $patient->id]) }}" class="btn btn-navy"><i class="fa-solid fa-bed-pulse"></i> Admit to IPD</a>
        @endif
        <a href="{{ route('billing.create', ['patient_id' => $patient->id]) }}" class="btn btn-amber"><i class="fa-solid fa-file-invoice"></i> Create Bill</a>
        <a href="{{ route('vaccinations.index', $patient) }}" class="btn btn-outline"><i class="fa-solid fa-syringe"></i> Vaccinations</a>
        <a href="{{ route('glucose.index', $patient) }}" class="btn btn-outline"><i class="fa-solid fa-droplet"></i> Diabetes Diary</a>
        <a href="{{ route('bp.index', $patient) }}" class="btn btn-outline"><i class="fa-solid fa-heart-pulse"></i> BP Diary</a>
        <a href="{{ route('health.index', $patient) }}" class="btn btn-outline"><i class="fa-solid fa-heart"></i> Health Tracker</a>
        @if($patient->date_of_birth)
            <a href="{{ route('patients.growth', $patient) }}" class="btn btn-outline"><i class="fa-solid fa-chart-line"></i> Growth</a>
        @endif
        <a href="{{ route('patients.edit', $patient) }}" class="btn btn-outline"><i class="fa-solid fa-pen"></i> Edit</a>
        @if(!empty($patient->phone) && app(\App\Services\LicenseService::class)->isEnabled('notifications'))
            <button type="button" class="btn btn-outline" style="color:#25D366;border-color:#25D366" onclick="document.getElementById('wa-modal').style.display='flex'">
                <i class="fa-brands fa-whatsapp"></i> WhatsApp
            </button>
        @endif
    </div>
</div>
<style>
.sc-patient-bar .spb-actions{display:flex;gap:6px;align-items:center;margin-left:auto;flex-wrap:nowrap}
.sc-patient-bar .spb-actions .btn{padding:4px 9px;font-size:12px;white-space:nowrap;line-height:1.4}
</style>
@endsection
@section('content')

{{-- Patient Profile heading + breadcrumb removed; action buttons moved into the
     sticky patient-context bar above (see @section('patient-context')). --}}

@if(!empty($patient->phone) && app(\App\Services\LicenseService::class)->isEnabled('notifications'))
<div id="wa-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:9999;align-items:center;justify-content:center">
    <div style="background:#fff;border-radius:10px;width:520px;max-width:92vw;padding:20px;box-shadow:0 8px 32px rgba(0,0,0,.25)">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
            <h3 style="margin:0;color:#25D366"><i class="fa-brands fa-whatsapp"></i> Send WhatsApp to {{ $patient->full_name }}</h3>
            <button type="button" class="btn btn-sm btn-outline" onclick="document.getElementById('wa-modal').style.display='none'"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST" action="{{ route('patients.whatsapp.send', $patient) }}">
            @csrf
            <div class="text-small text-muted" style="margin-bottom:8px">Sending to <code>{{ $patient->phone }}</code>. Recipient must have joined the Twilio sandbox if you're still on it.</div>
            <textarea name="message" class="form-control" rows="5" maxlength="1500" required placeholder="Type your message…">Hi {{ explode(' ', trim($patient->full_name))[0] ?? 'there' }},

</textarea>
            <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:12px">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('wa-modal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary" style="background:#25D366;border-color:#25D366"><i class="fa-brands fa-whatsapp"></i> Send</button>
            </div>
        </form>
    </div>
</div>
@endif

@if(isset($refillAlerts) && $refillAlerts->isNotEmpty())
<div id="refill-alert-box" style="background:#fffbeb;border:1px solid #fcd34d;border-radius:8px;padding:10px 18px;margin-bottom:16px">
    <div style="display:flex;align-items:center;justify-content:space-between;cursor:pointer" onclick="var b=document.getElementById('refill-alert-body'),c=document.getElementById('refill-chevron'),open=b.style.display==='block';b.style.display=open?'none':'block';c.style.transform=open?'rotate(-90deg)':'rotate(0deg)';">
        <div style="font-weight:700;color:#92400e;font-size:13px">
            <i id="refill-chevron" class="fa-solid fa-chevron-down" style="margin-right:4px;font-size:11px;transition:transform .15s;transform:rotate(-90deg);display:inline-block"></i>
            <i class="fa-solid fa-triangle-exclamation"></i> Prescription Refill Due ({{ $refillAlerts->count() }})
        </div>
        <button type="button" title="Dismiss" onclick="event.stopPropagation();document.getElementById('refill-alert-box').remove()" style="background:none;border:none;color:#92400e;cursor:pointer;font-size:14px;padding:0 4px;line-height:1">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>
    <div id="refill-alert-body" style="display:none;margin-top:8px">
        @foreach($refillAlerts as $alert)
        <div style="font-size:13px;color:#78350f;margin-bottom:2px">
            <i class="fa-solid fa-pills" style="margin-right:6px;color:#d97706"></i>
            <strong>{{ $alert['name'] }}</strong> —
            @if($alert['diff'] < 0)
                <span style="color:#dc2626">Overdue by {{ abs($alert['diff']) }} day(s)</span>
            @elseif($alert['diff'] === 0)
                <span style="color:#dc2626">Due today</span>
            @else
                Due in {{ $alert['diff'] }} day(s) ({{ $alert['due_at']->format('d M Y') }})
            @endif
            @if(!empty($alert['encounter_id']))
                <a href="{{ route('encounters.show', $alert['encounter_id']) }}" style="margin-left:6px;font-size:12px" title="Go to the visit this was prescribed in">View visit <i class="fa-solid fa-arrow-up-right-from-square"></i></a>
            @endif
        </div>
        @endforeach
    </div>
</div>
@endif

<!-- Patient Strip -->
<div class="patient-strip">
    <div class="ps-avatar">
        @if($patient->photo)
            <img src="{{ asset('storage/'.$patient->photo) }}" style="width:100%;height:100%;object-fit:cover;border-radius:50%">
        @else
            {{ strtoupper(substr($patient->first_name, 0, 1)) }}
        @endif
    </div>
    <div style="flex:1">
        <div class="ps-name">{{ $patient->full_name }}</div>
        <div class="ps-jhc_id">{{ $patient->jhc_id }} @if($patient->openemr_pid)&nbsp;·&nbsp; OpenEMR: {{ $patient->openemr_pid }}@endif</div>
        <div class="ps-meta">
            <span><i class="fa-solid fa-calendar"></i> {{ $patient->age }}</span>
            <span><i class="fa-solid fa-venus-mars"></i> {{ $patient->gender }}</span>
            @if($patient->blood_group)<span><i class="fa-solid fa-droplet"></i> {{ $patient->blood_group }}</span>@endif
            <span><i class="fa-solid fa-phone"></i> {{ $patient->phone }}</span>
            @if($patient->city)<span><i class="fa-solid fa-location-dot"></i> {{ $patient->city }}</span>@endif
        </div>
    </div>
    <div class="ps-actions">
        @if($billSummary['due'] > 0)
        <div style="text-align:center;background:rgba(220,38,38,.15);border:1px solid rgba(220,38,38,.3);border-radius:8px;padding:6px 14px">
            <div style="font-size:10px;color:#fca5a5;text-transform:uppercase;letter-spacing:.5px">Outstanding</div>
            <div style="font-size:18px;font-weight:800;color:#fca5a5">₹{{ number_format($billSummary['due'], 0) }}</div>
        </div>
        @endif
        <a href="{{ route('patients.timeline', $patient) }}" class="btn btn-outline" style="color:#fff;border-color:rgba(255,255,255,.3)"><i class="fa-solid fa-timeline"></i> Timeline</a>
        <a href="{{ route('lab.create-order', ['patient_id' => $patient->id]) }}" class="btn btn-outline" style="color:#fff;border-color:rgba(255,255,255,.3)"><i class="fa-solid fa-flask"></i> Lab Order</a>
        <a href="{{ route('patients.certificate', $patient) }}" class="btn btn-outline" style="color:#fff;border-color:rgba(255,255,255,.3)" target="_blank"><i class="fa-solid fa-file-medical"></i> Certificate</a>
        <a href="{{ route('diet-prescriptions.create', ['patient_id' => $patient->id]) }}" class="btn btn-outline" style="color:#fff;border-color:rgba(255,255,255,.3)"><i class="fa-solid fa-utensils"></i> Diet Rx</a>
    </div>
</div>

<!-- Tabs -->
<div class="tab-container tab-nav" id="patient-tabs">
    <button class="tab-btn active" data-tab="overview"><i class="fa-solid fa-circle-info"></i> Overview</button>
    <button class="tab-btn" data-tab="encounters"><i class="fa-solid fa-stethoscope"></i> Visits ({{ $encounters->total() }})</button>
    <button class="tab-btn" data-tab="prescriptions"><i class="fa-solid fa-prescription"></i> Prescriptions</button>
    <button class="tab-btn" data-tab="labs"><i class="fa-solid fa-flask-vial"></i> Lab Orders</button>
    <button class="tab-btn" data-tab="ecg-echo"><i class="fa-solid fa-heart-pulse"></i> ECG / Echo</button>
    <a class="tab-btn" href="{{ route('glucose.index', $patient) }}" style="text-decoration:none"><i class="fa-solid fa-droplet"></i> Diabetes Diary</a>
    <a class="tab-btn" href="{{ route('bp.index', $patient) }}" style="text-decoration:none"><i class="fa-solid fa-heart-pulse"></i> BP Diary</a>
    <a class="tab-btn" href="{{ route('health.index', $patient) }}" style="text-decoration:none"><i class="fa-solid fa-heart"></i> Health Tracker</a>
    <button class="tab-btn" data-tab="billing"><i class="fa-solid fa-file-invoice"></i> Billing</button>
    <button class="tab-btn" data-tab="ipd"><i class="fa-solid fa-bed-pulse"></i> IPD ({{ $admissions->count() }})</button>
    <button class="tab-btn" data-tab="timeline"><i class="fa-solid fa-timeline"></i> Timeline</button>
</div>

<!-- OVERVIEW TAB -->
<div id="tab-overview" class="tab-pane active">
    @if($nextAppointment)
    <div style="background:#f0fdf4;border:1px solid #86efac;border-radius:10px;padding:12px 18px;margin-bottom:16px;display:flex;align-items:center;gap:14px;flex-wrap:wrap">
        <div style="width:38px;height:38px;border-radius:10px;background:#dcfce7;display:flex;align-items:center;justify-content:center;color:#16a34a;font-size:16px;flex-shrink:0">
            <i class="fa-solid fa-calendar-check"></i>
        </div>
        <div style="flex:1;min-width:0">
            <div style="font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:#16a34a;font-weight:700">Next Appointment</div>
            <div style="font-weight:700;color:#14532d;font-size:14px">
                {{ $nextAppointment->appointment_date->format('D, d M Y') }}
                &nbsp;·&nbsp; {{ \Carbon\Carbon::parse($nextAppointment->appointment_time)->format('h:i A') }}
                @if($nextAppointment->doctor)&nbsp;·&nbsp; Dr. {{ $nextAppointment->doctor->full_name }}@endif
            </div>
            @if($nextAppointment->chief_complaint)
            <div style="font-size:12px;color:#166534;margin-top:2px">{{ $nextAppointment->chief_complaint }}</div>
            @endif
        </div>
        <a href="{{ route('appointments.show', $nextAppointment) }}" class="btn btn-sm" style="background:#16a34a;color:#fff;border-color:#16a34a;white-space:nowrap">
            <i class="fa-solid fa-eye"></i> View
        </a>
    </div>
    @endif
    <div class="grid grid-2">
        <div class="card">
            <div class="card-header"><i class="fa-solid fa-user"></i> Personal Details</div>
            <div class="card-body">
                @php $rows = [
                    ['Date of Birth', $patient->date_of_birth?->format('d M Y') . ' (Age: '.$patient->age.')'],
                    ['Gender', $patient->gender],
                    ['Blood Group', $patient->blood_group ?: 'Not recorded'],
                    ['Marital Status', $patient->marital_status ?: '—'],
                    ['Occupation', $patient->occupation ?: '—'],
                    ['Nationality', $patient->nationality],
                    ['Aadhar No.', $patient->aadhar_no ?: '—'],
                    ['Referred By', $patient->referred_by ?: '—'],
                ]; @endphp
                <table style="width:100%;border-collapse:collapse">
                @foreach($rows as [$k,$v])
                <tr style="border-bottom:1px solid var(--sc-border)">
                    <td style="padding:8px 0;font-weight:600;font-size:12px;color:var(--sc-muted);width:40%">{{ $k }}</td>
                    <td style="padding:8px 0;font-size:13px">{{ $v }}</td>
                </tr>
                @endforeach
                </table>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><i class="fa-solid fa-address-book"></i> Contact & Emergency</div>
            <div class="card-body">
                @php $rows2 = [
                    ['Phone', $patient->phone],
                    ['Alt. Phone', $patient->alt_phone ?: '—'],
                    ['Email', $patient->email ?: '—'],
                    ['Address', $patient->address . ($patient->city ? ', '.$patient->city : '') . ($patient->state ? ', '.$patient->state : '') . ($patient->pincode ? ' - '.$patient->pincode : '')],
                    ['Emergency Contact', $patient->emergency_contact_name ?: '—'],
                    ['Emergency Phone', $patient->emergency_contact_phone ?: '—'],
                    ['Emergency Relation', $patient->emergency_contact_relation ?: '—'],
                ]; @endphp
                <table style="width:100%;border-collapse:collapse">
                @foreach($rows2 as [$k,$v])
                <tr style="border-bottom:1px solid var(--sc-border)">
                    <td style="padding:8px 0;font-weight:600;font-size:12px;color:var(--sc-muted);width:40%">{{ $k }}</td>
                    <td style="padding:8px 0;font-size:13px">{{ $v }}</td>
                </tr>
                @endforeach
                </table>
            </div>
        </div>
    </div>

    <!-- Communication log for this patient -->
    @if(app(\App\Services\LicenseService::class)->isEnabled('forum') && auth()->user()->can('messages.send'))
    <div class="card mt-3" id="comm-log">
        <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px">
            <span><i class="fa-solid fa-comments"></i> Communication Log</span>
            <span style="display:flex;gap:6px">
                <button type="button" class="btn btn-outline btn-sm" onclick="(function(f){f.style.display = (f.style.display==='grid'?'none':'grid');})(document.getElementById('comm-form'))"><i class="fa-solid fa-plus"></i> Log a communication</button>
                <a href="{{ route('messages.compose', ['patient' => $patient->id]) }}" class="btn btn-outline btn-sm" title="Start an in-app conversation about this patient"><i class="fa-solid fa-pen-to-square"></i> New DM</a>
            </span>
        </div>

        {{-- Manual log form (WhatsApp / Email / Call / In-person) --}}
        <form id="comm-form" method="POST" action="{{ route('patients.communications.store', $patient) }}"
              style="padding:14px;border-bottom:1px solid var(--sc-border);background:var(--sc-bg);display:none;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:10px;align-items:end">
            @csrf
            <div class="form-group" style="margin:0">
                <label class="form-label">Mode</label>
                <select name="mode" class="form-control" required>
                    @foreach(\App\Models\PatientCommunication::MANUAL_MODES as $val => $label)
                        <option value="{{ $val }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group" style="margin:0">
                <label class="form-label">With (doctor / staff)</label>
                <select name="with_user_id" class="form-control">
                    <option value="">— Not specified —</option>
                    @foreach($commStaff as $s)
                        <option value="{{ $s->id }}">{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group" style="margin:0">
                <label class="form-label">Date &amp; time</label>
                <input type="datetime-local" name="occurred_at" class="form-control" value="{{ now()->format('Y-m-d\TH:i') }}">
            </div>
            <div class="form-group" style="margin:0;grid-column:1/-1">
                <label class="form-label">What was discussed</label>
                <textarea name="note" class="form-control" rows="2" required maxlength="5000" placeholder="Brief note of the discussion…"></textarea>
            </div>
            <div style="grid-column:1/-1;text-align:right">
                <button class="btn btn-primary btn-sm"><i class="fa-solid fa-save"></i> Save entry</button>
            </div>
        </form>

        <div class="card-body" style="padding:0">
            @if($patientComms->total() > 0)
            <div style="overflow-x:auto">
                <table class="table" style="width:100%;font-size:13px;margin:0">
                    <thead>
                        <tr style="text-align:left;color:var(--sc-muted);font-size:11.5px;text-transform:uppercase">
                            <th style="padding:8px 14px">Date &amp; Time</th>
                            <th style="padding:8px 10px">With</th>
                            <th style="padding:8px 10px">Mode</th>
                            <th style="padding:8px 14px">Topic / Note</th>
                            <th style="padding:8px 10px"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($patientComms as $c)
                            @php
                                $isDm = $c->mode === 'dm';
                                $isDmThread = $isDm && $c->thread;   // auto-linked to a conversation
                                $with = $c->withUser?->name ?: ($isDmThread && $c->thread?->is_group ? 'Group' : '—');
                                if ($isDmThread) {
                                    $note = $c->thread?->subject
                                        ?: ($c->thread?->lastMessage ? \Illuminate\Support\Str::limit(strip_tags($c->thread->lastMessage->body ?: '📎 attachment'), 70) : 'Conversation');
                                } else {
                                    $note = \Illuminate\Support\Str::limit($c->note, 90);
                                }
                                // Auto-linked DMs are managed from the chat; everything else (incl. a
                                // manually-logged DM note) is deletable by its author or a senior.
                                $canDelete = !$isDmThread && ($c->logged_by === auth()->id() || auth()->user()->can('forum.moderate'));
                            @endphp
                            <tr style="border-top:1px solid var(--sc-border);{{ $isDmThread ? 'cursor:pointer' : '' }}"
                                @if($isDmThread) onclick="window.location='{{ route('messages.thread', $c->thread) }}'" @endif>
                                <td style="padding:9px 14px;white-space:nowrap">{{ optional($c->occurred_at)->format('d M Y, H:i') ?? $c->created_at->format('d M Y, H:i') }}</td>
                                <td style="padding:9px 10px;white-space:nowrap">{{ $with }}</td>
                                <td style="padding:9px 10px;white-space:nowrap"><i class="{{ $c->modeIcon() }}" style="color:var(--sc-teal)"></i> {{ $c->modeLabel() }}</td>
                                <td style="padding:9px 14px;color:var(--sc-muted);max-width:300px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                                    {{ $note }}@if($isDm) <i class="fa-solid fa-arrow-up-right-from-square" style="font-size:10px;opacity:.6"></i>@endif
                                </td>
                                <td style="padding:9px 10px;text-align:right">
                                    @if($canDelete)
                                        <form method="POST" action="{{ route('patients.communications.destroy', [$patient, $c]) }}" onsubmit="return confirm('Remove this log entry?')" style="display:inline">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-outline btn-sm" style="color:#b91c1c;padding:2px 8px" title="Remove"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($patientComms->hasPages())
                <div style="padding:10px 14px;border-top:1px solid var(--sc-border)">{{ $patientComms->links() }}</div>
            @endif
            @else
                <p style="padding:14px;color:var(--sc-muted);font-size:13px;margin:0">
                    No communications logged yet. Click <strong>Log a communication</strong> for a WhatsApp/Email/Call/in-person chat,
                    or <strong>New DM</strong> to start an in-app conversation linked to this patient.
                </p>
            @endif
        </div>
    </div>
    @endif

    <!-- Patient Forum Access (phone + desk-issued PIN) -->
    @if(app(\App\Services\LicenseService::class)->isEnabled('forum'))
    <div class="card mt-3">
        <div class="card-header"><i class="fa-solid fa-comments"></i> Patient Forum Access</div>
        <div class="card-body">
            @if(session('portal_pin') && session('portal_pin_patient') == $patient->id)
                <div class="alert alert-success alert-keep" style="font-size:14px;position:relative">
                    <i class="fa-solid fa-key"></i>
                    <div style="flex:1">
                        <strong>PIN for {{ $patient->full_name }}:</strong>
                        <span style="font-size:22px;font-weight:800;letter-spacing:3px;font-family:monospace">{{ session('portal_pin') }}</span><br>
                        Give this to the patient now — it is shown only once. They log in at
                        <strong>{{ url('/portal') }}</strong> with their phone (<strong>{{ $patient->phone }}</strong>) and this PIN.
                    </div>
                    <button type="button" onclick="this.closest('.alert').remove()" title="Dismiss"
                            style="background:none;border:0;color:inherit;cursor:pointer;font-size:16px;padding:0 4px">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            @endif

            @if($patient->portal_enabled)
                <p style="font-size:13px;margin-bottom:10px">
                    <span class="badge badge-green">Enabled</span>
                    Login: phone <strong>{{ $patient->phone }}</strong> + PIN at <strong>{{ url('/portal') }}</strong>.
                    @if($patient->portal_pin_set_at)<span class="text-muted">PIN set {{ $patient->portal_pin_set_at->diffForHumans() }}.</span>@endif
                </p>
                <div style="display:flex;gap:8px">
                    <form method="POST" action="{{ route('patients.portal.reset', $patient) }}" onsubmit="return confirm('Generate a new PIN? The old one stops working.')">
                        @csrf
                        <button class="btn btn-outline btn-sm"><i class="fa-solid fa-rotate"></i> Reset PIN</button>
                    </form>
                    <form method="POST" action="{{ route('patients.portal.disable', $patient) }}" onsubmit="return confirm('Disable forum access for this patient?')">
                        @csrf
                        <button class="btn btn-outline btn-sm" style="color:#b91c1c"><i class="fa-solid fa-ban"></i> Disable</button>
                    </form>
                </div>
            @else
                <p style="font-size:13px;color:var(--sc-muted);margin-bottom:10px">Forum access is not enabled. Enabling generates a PIN the patient uses with their phone number to log in at the patient portal.</p>
                <form method="POST" action="{{ route('patients.portal.enable', $patient) }}">
                    @csrf
                    <button class="btn btn-primary btn-sm"><i class="fa-solid fa-key"></i> Enable forum access &amp; generate PIN</button>
                </form>
            @endif
        </div>
    </div>
    @endif

    <!-- Recent Appointments -->
    @if($appointments->count())
    <div class="card mt-3">
        <div class="card-header"><i class="fa-solid fa-calendar"></i> Recent Appointments</div>
        <div class="table-wrap">
            <table class="sc-table">
                <thead><tr><th>No.</th><th>Date</th><th>Time</th><th>Doctor</th><th>Type</th><th>Status</th><th></th></tr></thead>
                <tbody>
                @foreach($appointments as $a)
                <tr>
                    <td class="mono">{{ $a->appointment_no }}</td>
                    <td>{{ $a->appointment_date->format('d M Y') }}</td>
                    <td class="mono">{{ \Carbon\Carbon::parse($a->appointment_time)->format('h:i A') }}</td>
                    <td>{{ $a->doctor->full_name }}</td>
                    <td><span class="badge badge-navy">{{ $a->type }}</span></td>
                    <td><span class="badge {{ $a->status === 'Completed' ? 'badge-green' : ($a->status === 'Cancelled' ? 'badge-red' : 'badge-teal') }}">{{ $a->status }}</span></td>
                    <td><a href="{{ route('appointments.show', $a) }}" class="btn btn-sm btn-outline">View</a></td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>

<!-- ENCOUNTERS TAB -->
<div id="tab-encounters" class="tab-pane">
    <div style="display:flex;justify-content:flex-end;margin-bottom:12px">
        <a href="{{ route('appointments.create', ['patient_id' => $patient->id]) }}" class="btn btn-primary btn-sm">
            <i class="fa-solid fa-plus"></i> New Visit
        </a>
    </div>
    @forelse($encounters as $enc)
    <div class="card mb-3">
        <div class="card-header">
            <i class="fa-solid fa-stethoscope"></i>
            <span class="mono">{{ $enc->encounter_no }}</span>
            <span class="badge {{ $enc->visit_type === 'New Visit' ? 'badge-teal' : 'badge-navy' }}" style="margin-left:8px">{{ $enc->visit_type }}</span>
            <span class="text-muted" style="margin-left:8px;font-size:12px">{{ $enc->encounter_date->format('d M Y') }} &nbsp;·&nbsp; {{ $enc->doctor->full_name }}</span>
            <div class="card-actions">
                @if($enc->follow_up_date)
                    <button type="button" class="btn btn-sm btn-outline" title="Book follow-up on {{ $enc->follow_up_date->format('d M Y') }}" onclick="scBookFollowUp({{ $enc->id }}, this)"><i class="fa-solid fa-calendar-plus"></i> Book follow-up</button>
                @endif
                <span class="badge {{ $enc->status === 'Completed' ? 'badge-green' : 'badge-amber' }}">{{ $enc->status }}</span>
                <a href="{{ route('encounters.show', $enc) }}" class="btn btn-sm btn-outline">Open</a>
            </div>
        </div>
        <div class="card-body" style="padding:0">
            {{-- Chief Complaint + Diagnoses row --}}
            @if($enc->chief_complaint || $enc->diagnoses->count())
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;padding:12px 16px;border-bottom:1px solid var(--sc-border)">
                @if($enc->chief_complaint)
                <div>
                    <div style="font-size:11px;font-weight:600;color:var(--sc-muted);text-transform:uppercase;margin-bottom:4px">Chief Complaint</div>
                    {{-- Quill stores rich-text HTML; strip tags + nl2br so the
                         summary card reads as plain text with line breaks intact
                         instead of showing <p> tags literally. --}}
                    <div style="font-size:13px">{!! nl2br(e(strip_tags($enc->chief_complaint))) !!}</div>
                </div>
                @endif
                @if($enc->diagnoses->count())
                <div>
                    <div style="font-size:11px;font-weight:600;color:var(--sc-muted);text-transform:uppercase;margin-bottom:4px">Diagnoses</div>
                    <div style="display:flex;flex-wrap:wrap;gap:6px">
                        @foreach($enc->diagnoses as $d)
                            <span class="badge {{ $d->type === 'Primary' ? 'badge-teal' : 'badge-gray' }}">{{ $d->diagnosis_name }}</span>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
            @endif

            {{-- Prescribed Medicines --}}
            @php $rxItems = $enc->prescriptions->flatMap(fn($rx) => $rx->items)->filter(fn($i) => $i->medicine); @endphp
            @if($rxItems->count())
            <div style="padding:10px 16px">
                <div style="font-size:11px;font-weight:600;color:var(--sc-muted);text-transform:uppercase;margin-bottom:8px">
                    <i class="fa-solid fa-pills" style="color:var(--sc-teal);margin-right:4px"></i>Prescribed Medicines
                </div>
                <div style="display:flex;flex-wrap:wrap;gap:6px">
                    @foreach($rxItems as $item)
                    <div style="display:inline-flex;align-items:center;gap:6px;background:var(--sc-bg);border:1px solid var(--sc-border);border-radius:6px;padding:4px 10px;font-size:12px">
                        <span style="font-weight:600;color:var(--sc-navy)">{{ $item->medicine->name }}</span>
                        @if($item->medicine->strength)
                            <span style="color:var(--sc-muted)">{{ $item->medicine->strength }}</span>
                        @endif
                        <span style="color:var(--sc-teal);font-size:11px">{{ $item->dose }} &middot; {{ $item->frequency }} &middot; {{ $item->duration_days }}d</span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Vitals summary if present --}}
            @if($enc->bp_systolic || $enc->pulse || $enc->spo2 || $enc->weight)
            <div style="padding:8px 16px;background:var(--sc-bg);border-top:1px solid var(--sc-border);display:flex;flex-wrap:wrap;gap:16px">
                @if($enc->bp_systolic && $enc->bp_diastolic)
                <span style="font-size:12px;color:var(--sc-muted)"><i class="fa-solid fa-heart-pulse" style="color:var(--sc-red);margin-right:3px"></i>BP: <strong>{{ $enc->bp_systolic }}/{{ $enc->bp_diastolic }}</strong> mmHg</span>
                @endif
                @if($enc->pulse)
                <span style="font-size:12px;color:var(--sc-muted)"><i class="fa-solid fa-wave-square" style="color:var(--sc-teal);margin-right:3px"></i>Pulse: <strong>{{ $enc->pulse }}</strong>/min</span>
                @endif
                @if($enc->spo2)
                <span style="font-size:12px;color:var(--sc-muted)"><i class="fa-solid fa-lungs" style="color:#3b82f6;margin-right:3px"></i>SpO2: <strong>{{ $enc->spo2 }}%</strong></span>
                @endif
                @if($enc->weight)
                <span style="font-size:12px;color:var(--sc-muted)"><i class="fa-solid fa-weight-scale" style="margin-right:3px"></i>Wt: <strong>{{ $enc->weight }} kg</strong></span>
                @endif
                @if($enc->bmi)
                <span style="font-size:12px;color:var(--sc-muted)">BMI: <strong>{{ $enc->bmi }}</strong></span>
                @endif
            </div>
            @endif
        </div>
    </div>
    @empty
    <div class="card"><div class="card-body text-center text-muted" style="padding:40px">No visits recorded yet.</div></div>
    @endforelse
    <div>{{ $encounters->links() }}</div>
</div>

<!-- PRESCRIPTIONS TAB -->
<div id="tab-prescriptions" class="tab-pane">
    @forelse($prescriptions as $rx)
    <div class="card mb-3">
        <div class="card-header">
            <i class="fa-solid fa-prescription"></i>
            <span class="mono">{{ $rx->prescription_no }}</span>
            <span class="text-muted" style="font-size:12px;margin-left:8px">{{ $rx->prescribed_date->format('d M Y') }} &nbsp;·&nbsp; {{ $rx->doctor->full_name }}</span>
            <div class="card-actions">
                <span class="badge {{ $rx->status === 'Dispensed' ? 'badge-green' : 'badge-amber' }}">{{ $rx->status }}</span>
                <a href="{{ route('prescriptions.print', $rx) }}" class="btn btn-sm btn-outline" target="_blank"><i class="fa-solid fa-print"></i></a>
            </div>
        </div>
        <div class="card-body" style="padding:12px 16px">
            @foreach($rx->items as $item)
            <div class="rx-item">
                <div class="rx-name">{{ $item->medicine->name }}
                    @if(!$item->is_in_stock)<span class="badge badge-amber" style="margin-left:8px;font-size:10px"><i class="fa-solid fa-warning"></i> Not in Stock</span>@endif
                    @if($item->is_dispensed)<span class="badge badge-green" style="margin-left:8px;font-size:10px"><i class="fa-solid fa-check"></i> Dispensed</span>@endif
                </div>
                <div class="rx-details">
                    {{ $item->dose }} &nbsp;·&nbsp; {{ $item->frequency }} &nbsp;·&nbsp; {{ $item->route }} &nbsp;·&nbsp; {{ $item->duration_days }} days &nbsp;·&nbsp; Qty: {{ $item->quantity }}
                    @if($item->instructions)<br>📝 {{ $item->instructions }}@endif
                </div>
                @if($item->medicine->side_effects)
                <div class="rx-side-effects"><i class="fa-solid fa-triangle-exclamation"></i> Side Effects: {{ Str::limit($item->medicine->side_effects, 150) }}</div>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @empty
    <div class="card"><div class="card-body text-center text-muted" style="padding:40px">No prescriptions recorded.</div></div>
    @endforelse
</div>

<!-- LAB ORDERS TAB -->
<div id="tab-labs" class="tab-pane">
    <div style="display:flex;justify-content:flex-end;margin-bottom:12px">
        <a href="{{ route('lab.create-order', ['patient_id' => $patient->id]) }}" class="btn btn-primary btn-sm">
            <i class="fa-solid fa-plus"></i> New Lab Order
        </a>
    </div>
    @forelse($labOrders as $lo)
    <div class="card mb-3">
        <div class="card-header">
            <i class="fa-solid fa-flask"></i> <span class="mono">{{ $lo->order_no }}</span>
            <span class="text-muted" style="font-size:12px;margin-left:8px">{{ $lo->ordered_date->format('d M Y') }}</span>
            <div class="card-actions">
                <span class="badge {{ $lo->status === 'Completed' ? 'badge-green' : ($lo->status === 'Ordered' ? 'badge-amber' : 'badge-teal') }}">{{ $lo->status }}</span>
                <a href="{{ route('lab.order', $lo) }}" class="btn btn-sm btn-outline"><i class="fa-solid fa-pen-to-square"></i> Enter Results</a>
            </div>
        </div>
        <div class="card-body" style="padding:0">
            <table style="width:100%;border-collapse:collapse;font-size:13px">
                <thead>
                    <tr style="background:var(--sc-bg);border-bottom:1px solid var(--sc-border)">
                        <th style="padding:8px 14px;text-align:left;font-weight:600">Test</th>
                        <th style="padding:8px 14px;text-align:left;font-weight:600">Result</th>
                        <th style="padding:8px 14px;text-align:left;font-weight:600">Normal Range</th>
                        <th style="padding:8px 14px;text-align:left;font-weight:600">Flag</th>
                        <th style="padding:8px 14px;text-align:right;font-weight:600">Price</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($lo->items as $item)
                    @php $result = $item->results->first(); @endphp
                    <tr style="border-bottom:1px solid var(--sc-border)">
                        <td style="padding:10px 14px">
                            <div style="font-weight:600">{{ $item->test?->name ?? $item->panel?->name ?? '—' }}</div>
                            @if($item->test?->sample_type)
                                <small style="color:var(--sc-muted)">{{ $item->test->sample_type }}</small>
                            @endif
                        </td>
                        <td style="padding:10px 14px">
                            @if($result)
                                <span style="font-weight:700;font-size:15px;color:{{ in_array($result->flag, ['Normal','Negative'], true) ? 'var(--sc-green)' : 'var(--sc-red)' }}">
                                    {{ $result->result_value }}
                                </span>
                                @if($result->unit) <span style="color:var(--sc-muted);font-size:12px"> {{ $result->unit }}</span> @endif
                            @else
                                <span style="color:var(--sc-muted)">Pending</span>
                            @endif
                        </td>
                        <td style="padding:10px 14px;color:var(--sc-muted);font-size:12px">
                            {{ $item->test?->normal_range ?? '—' }}
                        </td>
                        <td style="padding:10px 14px">
                            @if($result)
                                @php $flagColor = match($result->flag) {
                                    'Normal','Negative' => '#22c55e',
                                    'High','Low','Abnormal','Review' => '#f59e0b',
                                    'Critical High','Critical Low','Positive' => '#ef4444',
                                    default => '#6b7280'
                                }; @endphp
                                <span style="background:{{ $flagColor }}20;color:{{ $flagColor }};padding:2px 8px;border-radius:4px;font-size:11px;font-weight:600">
                                    {{ $result->flag }}
                                </span>
                            @else
                                <span style="color:var(--sc-muted)">—</span>
                            @endif
                        </td>
                        <td style="padding:10px 14px;text-align:right;color:var(--sc-muted)">₹{{ number_format($item->price, 2) }}</td>
                    </tr>
                @endforeach
                </tbody>
                <tfoot>
                    <tr style="background:var(--sc-bg)">
                        <td colspan="4" style="padding:8px 14px;text-align:right;font-weight:600">Total</td>
                        <td style="padding:8px 14px;text-align:right;font-weight:700;color:var(--sc-navy)">₹{{ number_format($lo->net_amount, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    @empty
    <div class="card"><div class="card-body text-center text-muted" style="padding:40px">No lab orders found.</div></div>
    @endforelse

    {{-- Vitals Trends --}}
    @if(isset($vitalsTrends) && $vitalsTrends->count())
    <div class="card mt-4">
        <div class="card-header"><i class="fa-solid fa-heart-pulse"></i> Vital Sign Trends
            <span class="text-muted" style="font-weight:400;font-size:12px;margin-left:6px">across {{ $vitalsTrends->first()['points']->count() }}+ visits</span>
        </div>
        <div class="card-body">
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:20px">
                @foreach($vitalsTrends as $trend)
                <div>
                    <div style="font-size:12.5px;font-weight:700;color:var(--sc-navy);margin-bottom:6px">
                        {{ $trend['label'] }}
                        @if($trend['unit'])<span class="text-muted" style="font-weight:400">({{ $trend['unit'] }})</span>@endif
                        <span class="text-muted" style="font-weight:400;font-size:11px;float:right">{{ count($trend['points']) }} readings</span>
                    </div>
                    <canvas id="vital_{{ $trend['key'] }}" height="80"></canvas>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    {{-- Lab Trends --}}
    @if($labTrends->count())
    <div class="card mt-4">
        <div class="card-header"><i class="fa-solid fa-chart-line"></i> Lab Result Trends</div>
        <div class="card-body">
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:20px">
                @foreach($labTrends as $trend)
                <div>
                    <div style="font-size:12.5px;font-weight:700;color:var(--sc-navy);margin-bottom:6px">
                        {{ $trend['name'] }}
                        @if($trend['unit'])<span class="text-muted" style="font-weight:400">({{ $trend['unit'] }})</span>@endif
                    </div>
                    <canvas id="trend_{{ $loop->index }}" height="80"></canvas>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif
</div>

<!-- ECG/ECHO TAB -->
<div id="tab-ecg-echo" class="tab-pane">
    <div class="grid grid-2">
        <div>
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
                <strong>ECG Records</strong>
                <a href="{{ route('ecg.create', ['patient_id' => $patient->id]) }}" class="btn btn-sm btn-outline"><i class="fa-solid fa-plus"></i> New ECG</a>
            </div>
            @forelse($ecgRecords as $ecg)
            <div class="card mb-2">
                <div class="card-body" style="padding:12px 16px">
                    <div style="display:flex;justify-content:space-between">
                        <div>
                            <div class="fw-600 mono">{{ $ecg->record_no }}</div>
                            <div class="text-small text-muted">{{ $ecg->record_date->format('d M Y') }}</div>
                        </div>
                        <div class="text-right">
                            @if($ecg->rhythm)<span class="badge badge-teal">{{ $ecg->rhythm }}</span>@endif
                            <div class="text-small text-muted mt-1">HR: {{ $ecg->heart_rate ?? '—' }}</div>
                        </div>
                    </div>
                    @if($ecg->interpretation)<div class="text-small mt-1">{{ Str::limit($ecg->interpretation, 100) }}</div>@endif
                    <div style="margin-top:8px"><a href="{{ route('ecg.show', $ecg) }}" class="btn btn-sm btn-outline">View Full Report</a></div>
                </div>
            </div>
            @empty
            <div class="card"><div class="card-body text-center text-muted">No ECG records.</div></div>
            @endforelse
        </div>
        <div>
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
                <strong>Echo Records</strong>
                <a href="{{ route('echo.create', ['patient_id' => $patient->id]) }}" class="btn btn-sm btn-outline"><i class="fa-solid fa-plus"></i> New Echo</a>
            </div>
            @forelse($echoRecords as $echo)
            <div class="card mb-2">
                <div class="card-body" style="padding:12px 16px">
                    <div style="display:flex;justify-content:space-between">
                        <div>
                            <div class="fw-600 mono">{{ $echo->record_no }}</div>
                            <div class="text-small text-muted">{{ $echo->record_date->format('d M Y') }}</div>
                        </div>
                        <div class="text-right">
                            @if($echo->ef_percent)<span class="badge badge-teal">EF: {{ $echo->ef_percent }}%</span>@endif
                        </div>
                    </div>
                    @if($echo->impression)<div class="text-small mt-1">{{ Str::limit(strip_tags($echo->impression), 100) }}</div>@endif
                    <div style="margin-top:8px"><a href="{{ route('echo.show', $echo) }}" class="btn btn-sm btn-outline">View Full Report</a></div>
                </div>
            </div>
            @empty
            <div class="card"><div class="card-body text-center text-muted">No echo records.</div></div>
            @endforelse
        </div>
    </div>
</div>

<!-- BILLING TAB -->
<div id="tab-billing" class="tab-pane">
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:16px">
        <div class="stat-card" style="padding:12px 16px">
            <div class="stat-icon navy" style="width:34px;height:34px;font-size:13px"><i class="fa-solid fa-file-invoice"></i></div>
            <div><div class="stat-value" style="font-size:18px">₹{{ number_format($billSummary['total'],0) }}</div><div class="stat-label">Total Billed</div></div>
        </div>
        <div class="stat-card" style="padding:12px 16px">
            <div class="stat-icon green" style="width:34px;height:34px;font-size:13px"><i class="fa-solid fa-circle-check"></i></div>
            <div><div class="stat-value" style="font-size:18px">₹{{ number_format($billSummary['paid'],0) }}</div><div class="stat-label">Total Paid</div></div>
        </div>
        <div class="stat-card" style="padding:12px 16px">
            <div class="stat-icon red" style="width:34px;height:34px;font-size:13px"><i class="fa-solid fa-clock"></i></div>
            <div><div class="stat-value" style="font-size:18px;{{ $billSummary['due']>0?'color:var(--sc-red)':'' }}">₹{{ number_format($billSummary['due'],0) }}</div><div class="stat-label">Outstanding</div></div>
        </div>
        <div class="stat-card" style="padding:12px 16px;justify-content:space-between;align-items:center">
            <div class="stat-value" style="font-size:15px;margin-bottom:4px">{{ $billSummary['unpaid'] }} unpaid bill{{ $billSummary['unpaid']!=1?'s':'' }}</div>
            <a href="{{ route('billing.create', ['patient_id' => $patient->id]) }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus"></i> New Bill</a>
        </div>
    </div>
    <div class="table-wrap">
        <table class="sc-table">
            <thead><tr><th>Bill No.</th><th>Date</th><th>Total</th><th>Paid</th><th>Due</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse($bills as $b)
            <tr>
                <td class="mono">{{ $b->bill_no }}</td>
                <td>{{ $b->bill_date->format('d M Y') }}</td>
                <td class="fw-600">₹{{ number_format($b->total_amount, 2) }}</td>
                <td class="text-success" style="color:var(--sc-green)">₹{{ number_format($b->paid_amount, 2) }}</td>
                <td class="{{ $b->due_amount > 0 ? 'stock-critical' : 'stock-ok' }}">₹{{ number_format($b->due_amount, 2) }}</td>
                <td><span class="badge {{ $b->payment_status === 'Paid' ? 'badge-green' : ($b->payment_status === 'Partial' ? 'badge-amber' : 'badge-red') }}">{{ $b->payment_status }}</span></td>
                <td><a href="{{ route('billing.show', $b) }}" class="btn btn-sm btn-outline">View</a></td>
            </tr>
            @empty
            <tr><td colspan="7" class="text-center text-muted" style="padding:30px">No bills found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<!-- IPD TAB -->
<div id="tab-ipd" class="tab-pane">
    {{-- Current admission banner --}}
    @if($currentAdmission)
    <div class="card mb-3" style="border-left:4px solid var(--sc-teal)">
        <div class="card-body" style="display:flex;align-items:center;gap:20px;flex-wrap:wrap">
            <div style="flex:0 0 auto">
                <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.8px;color:var(--sc-teal);margin-bottom:3px">Currently Admitted</div>
                <div class="fw-700 mono" style="font-size:20px">{{ $currentAdmission->ipd_no }}</div>
            </div>
            <div style="flex:1;display:grid;grid-template-columns:repeat(4,1fr);gap:12px">
                <div>
                    <div class="form-label">Ward / Bed</div>
                    <div class="fw-600">{{ $currentAdmission->ward->name }}</div>
                    <div class="text-small text-muted">Bed {{ $currentAdmission->bed->bed_number }}</div>
                </div>
                <div>
                    <div class="form-label">Doctor</div>
                    <div class="fw-600">{{ $currentAdmission->admittingDoctor->full_name }}</div>
                </div>
                <div>
                    <div class="form-label">Admitted</div>
                    <div class="fw-600">{{ $currentAdmission->admission_date->format('d M Y') }}</div>
                    <div class="text-small text-muted">{{ $currentAdmission->days_admitted }} day(s)</div>
                </div>
                <div>
                    <div class="form-label">Diagnosis</div>
                    <div class="fw-600">{{ $currentAdmission->provisional_diagnosis ?: '—' }}</div>
                </div>
            </div>
            <div>
                <a href="{{ route('admissions.show', $currentAdmission) }}" class="btn btn-primary"><i class="fa-solid fa-arrow-right"></i> Open IPD Record</a>
            </div>
        </div>
    </div>
    @endif

    {{-- All admissions table --}}
    <div class="card">
        <div class="card-header">
            <i class="fa-solid fa-bed-pulse"></i> Admission History
            <div class="card-actions">
                @if(!$currentAdmission)
                <a href="{{ route('admissions.create', ['patient_id' => $patient->id]) }}" class="btn btn-sm btn-primary"><i class="fa-solid fa-plus"></i> New Admission</a>
                @endif
            </div>
        </div>
        <div class="table-wrap">
            <table class="sc-table">
                <thead>
                    <tr><th>IPD No.</th><th>Ward</th><th>Bed</th><th>Doctor</th><th>Type</th><th>Admitted</th><th>Discharged</th><th>Days</th><th>Status</th><th></th></tr>
                </thead>
                <tbody>
                @forelse($admissions as $adm)
                <tr>
                    <td class="mono fw-600">{{ $adm->ipd_no }}</td>
                    <td>{{ $adm->ward->name }}</td>
                    <td class="text-center"><span class="badge badge-gray">{{ $adm->bed->bed_number }}</span></td>
                    <td>{{ $adm->admittingDoctor->full_name }}</td>
                    <td><span class="badge badge-navy">{{ \App\Models\Admission::ADMISSION_TYPES[$adm->admission_type] ?? $adm->admission_type }}</span></td>
                    <td>{{ $adm->admission_date->format('d M Y') }}</td>
                    <td>{{ $adm->discharge_date ? $adm->discharge_date->format('d M Y') : '—' }}</td>
                    <td class="text-center"><span class="badge badge-gray">{{ $adm->days_admitted }}d</span></td>
                    <td><span class="badge badge-{{ $adm->status_color }}">{{ $adm->status_label }}</span></td>
                    <td><a href="{{ route('admissions.show', $adm) }}" class="btn btn-sm btn-outline">View</a></td>
                </tr>
                @empty
                <tr><td colspan="10" class="text-center text-muted" style="padding:40px">No IPD admissions recorded.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- TIMELINE TAB -->
<div id="tab-timeline" class="tab-pane">
    @if(isset($patientTimeline) && $patientTimeline->count())
    <div class="timeline">
        @foreach($patientTimeline as $item)
        @php
            $dotColor = match($item['type']) {
                'encounter'  => 'background:var(--sc-teal);box-shadow:0 0 0 2px var(--sc-teal)',
                'lab'        => 'background:var(--sc-amber);box-shadow:0 0 0 2px var(--sc-amber)',
                'bill'       => 'background:var(--sc-navy);box-shadow:0 0 0 2px var(--sc-navy)',
                'admission'  => 'background:var(--sc-green);box-shadow:0 0 0 2px var(--sc-green)',
                'discharge'  => 'background:var(--sc-muted);box-shadow:0 0 0 2px var(--sc-muted)',
                'prescription'=> 'background:#8b5cf6;box-shadow:0 0 0 2px #8b5cf6',
                default      => 'background:var(--sc-teal);box-shadow:0 0 0 2px var(--sc-teal)',
            };
            $icon = match($item['type']) {
                'encounter'   => 'fa-stethoscope',
                'lab'         => 'fa-flask',
                'bill'        => 'fa-file-invoice-dollar',
                'admission'   => 'fa-bed-pulse',
                'discharge'   => 'fa-person-walking-arrow-right',
                'prescription'=> 'fa-prescription',
                default       => 'fa-circle',
            };
            $typeLabel = match($item['type']) {
                'encounter'   => 'Visit',
                'lab'         => 'Lab Order',
                'bill'        => 'Bill',
                'admission'   => 'IPD Admission',
                'discharge'   => 'Discharged',
                'prescription'=> 'Prescription',
                default       => ucfirst($item['type']),
            };
        @endphp
        <div class="timeline-item">
            <div class="timeline-dot" style="{{ $dotColor }}"></div>
            <div class="card" style="margin-bottom:0">
                <div class="card-body" style="padding:12px 16px">
                    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
                        <span style="font-size:11px;color:var(--sc-muted);font-family:'DM Mono',monospace;white-space:nowrap">
                            {{ \Carbon\Carbon::parse($item['date'])->format('d M Y') }}
                        </span>
                        <span class="badge" style="font-size:11px;
                            @if($item['type']==='encounter') background:rgba(10,180,180,.12);color:var(--sc-teal)
                            @elseif($item['type']==='lab') background:rgba(245,166,35,.15);color:#c47e0a
                            @elseif($item['type']==='bill') background:rgba(15,31,61,.08);color:var(--sc-navy)
                            @elseif($item['type']==='admission') background:rgba(39,196,122,.12);color:#1b9e5c
                            @elseif($item['type']==='discharge') background:#f1f5f9;color:var(--sc-muted)
                            @elseif($item['type']==='prescription') background:rgba(139,92,246,.12);color:#7c3aed
                            @endif">
                            <i class="fa-solid {{ $icon }}"></i> {{ $typeLabel }}
                        </span>
                        <div style="flex:1;font-weight:600;font-size:13px">
                            @if($item['type'] === 'encounter')
                                {{ $item['data']->encounter_no }} — {{ $item['data']->doctor->full_name ?? '—' }}
                                @if($item['data']->chief_complaint)<span class="text-muted" style="font-weight:400"> · {{ Str::limit(strip_tags($item['data']->chief_complaint), 60) }}</span>@endif
                            @elseif($item['type'] === 'lab')
                                {{ $item['data']->order_no }}
                                <span class="text-muted" style="font-weight:400"> · {{ $item['data']->items->count() }} test(s)</span>
                            @elseif($item['type'] === 'bill')
                                {{ $item['data']->bill_no }} — ₹{{ number_format($item['data']->total_amount, 0) }}
                                <span class="badge badge-{{ $item['data']->status === 'Paid' ? 'green' : ($item['data']->status === 'Partial' ? 'amber' : 'red') }}" style="margin-left:6px">{{ $item['data']->status }}</span>
                            @elseif($item['type'] === 'admission')
                                IPD: {{ $item['data']->ipd_no }} — {{ $item['data']->ward->name ?? '—' }}
                                @if($item['data']->provisional_diagnosis)<span class="text-muted" style="font-weight:400"> · {{ Str::limit($item['data']->provisional_diagnosis, 60) }}</span>@endif
                            @elseif($item['type'] === 'discharge')
                                Discharged from {{ $item['data']->ipd_no }}
                                @if($item['data']->final_diagnosis)<span class="text-muted" style="font-weight:400"> · {{ Str::limit($item['data']->final_diagnosis, 60) }}</span>@endif
                            @elseif($item['type'] === 'prescription')
                                {{ $item['data']->prescription_no }} — {{ $item['data']->doctor->full_name ?? '—' }}
                                <span class="text-muted" style="font-weight:400"> · {{ $item['data']->items->count() }} medicine(s)</span>
                            @endif
                        </div>
                        <div>
                            @if($item['type'] === 'encounter')
                                <a href="{{ route('encounters.show', $item['data']) }}" class="btn btn-sm btn-outline">View</a>
                            @elseif($item['type'] === 'lab')
                                <a href="{{ route('lab.order', $item['data']) }}" class="btn btn-sm btn-outline">View</a>
                            @elseif($item['type'] === 'bill')
                                <a href="{{ route('billing.show', $item['data']) }}" class="btn btn-sm btn-outline">View</a>
                            @elseif(in_array($item['type'], ['admission','discharge']))
                                <a href="{{ route('admissions.show', $item['data']) }}" class="btn btn-sm btn-outline">View</a>
                            @elseif($item['type'] === 'prescription')
                                <a href="{{ route('prescriptions.print', $item['data']) }}" class="btn btn-sm btn-outline" target="_blank">Print</a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @else
    <div class="card"><div class="card-body text-center text-muted" style="padding:40px">No activity recorded for this patient yet.</div></div>
    @endif
</div>

@endsection

@push('scripts')
<script>
window.scPatientVisitUrl = @json(route('appointments.create', ['patient_id' => $patient->id]));
document.addEventListener('DOMContentLoaded', function() {
    scTrackPatient({{ $patient->id }}, @json($patient->full_name), @json($patient->jhc_id), @json(route('patients.show', $patient)));
    // Restore tab from URL hash
    const hash = location.hash.replace('#','');
    if (hash) {
        const btn = document.querySelector(`#patient-tabs [data-tab="${hash.replace('tab-','')}"]`);
        if (btn) btn.click();
    }
});
</script>
@if((isset($vitalsTrends) && $vitalsTrends->count()) || (isset($labTrends) && $labTrends->count()))
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endif

@if(isset($vitalsTrends) && $vitalsTrends->count())
<script>
(function() {
    const trends = @json($vitalsTrends);
    const palette = ['#0ab4b4', '#e84c4c', '#27c47a', '#f5a623', '#1e3360'];
    trends.forEach(t => {
        const ctx = document.getElementById('vital_' + t.key);
        if (!ctx) return;
        const datasets = (t.fields || []).map((f, i) => ({
            label: f.replace('_', ' ').replace(/\b\w/g, c => c.toUpperCase()),
            data: t.points.map(p => p.values[i]),
            borderColor: palette[i % palette.length],
            backgroundColor: palette[i % palette.length] + '22',
            tension: 0.3,
            pointRadius: 4,
            fill: t.fields.length === 1,
        }));
        new Chart(ctx, {
            type: 'line',
            data: { labels: t.points.map(p => p.date), datasets },
            options: {
                responsive: true,
                plugins: { legend: { display: t.fields.length > 1, labels: { font: { size: 10 } } } },
                scales: {
                    y: { ticks: { font: { size: 10 } } },
                    x: { ticks: { font: { size: 10 } } }
                }
            }
        });
    });
})();
</script>
@endif

@if(isset($labTrends) && $labTrends->count())
<script>
const labTrends = @json($labTrends);
labTrends.forEach((trend, i) => {
    const ctx = document.getElementById('trend_' + i);
    if (!ctx) return;
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: trend.points.map(p => p.date),
            datasets: [{
                label: trend.name,
                data: trend.points.map(p => p.value),
                borderColor: '#0ab4b4', backgroundColor: 'rgba(10,180,180,.08)',
                tension: 0.3, pointRadius: 4, fill: true,
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { ticks: { font: { size: 10 } } },
                x: { ticks: { font: { size: 10 } } }
            }
        }
    });
});
</script>
@endif
@endpush
