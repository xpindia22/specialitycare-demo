{{-- FILE: resources/views/doctors/show.blade.php --}}
@extends('layouts.app')
@section('title', $doctor->full_name)
@section('page-title', 'Doctor Profile')
@section('content')

<div class="page-header">
    <div class="page-header-left">
        <h1>Dr. {{ $doctor->full_name }}</h1>
        <div class="breadcrumb"><a href="{{ route('doctors.index') }}">Doctors</a> › {{ $doctor->doctor_id }}</div>
    </div>
    <div style="display:flex;gap:8px">
        <a href="{{ route('opd.doctor', ['doctor_id' => $doctor->id]) }}" class="btn btn-outline"><i class="fa-solid fa-ticket"></i> OPD Queue</a>
        <a href="{{ route('appointments.create', ['doctor_id' => $doctor->id]) }}" class="btn btn-outline"><i class="fa-solid fa-calendar-plus"></i> Book</a>
        <a href="{{ route('doctors.edit', $doctor) }}" class="btn btn-primary"><i class="fa-solid fa-pen"></i> Edit</a>
    </div>
</div>

{{-- Stats row --}}
<div class="grid" style="grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:20px">
    <div class="stat-card">
        <div class="stat-icon teal"><i class="fa-solid fa-ticket"></i></div>
        <div>
            <div class="stat-value">{{ $todayTokens }}</div>
            <div class="stat-label">Today's Queue</div>
            <div class="stat-sub text-muted">{{ $todayDone }} completed</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:rgba(39,196,122,.12);color:var(--sc-green)">
            <i class="fa-solid fa-stethoscope"></i>
        </div>
        <div>
            <div class="stat-value">{{ $monthEncounters }}</div>
            <div class="stat-label">Encounters This Month</div>
            <div class="stat-sub text-muted">{{ now()->format('F Y') }}</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon amber"><i class="fa-solid fa-calendar-check"></i></div>
        <div>
            <div class="stat-value">{{ $todayAppointments->count() }}</div>
            <div class="stat-label">Today's Appointments</div>
            <div class="stat-sub text-muted">{{ $todayAppointments->whereIn('status',['Scheduled','Confirmed'])->count() }} pending</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon navy"><i class="fa-solid fa-indian-rupee-sign"></i></div>
        <div>
            <div class="stat-value">₹{{ number_format($doctor->consultation_fee) }}</div>
            <div class="stat-label">Consultation Fee</div>
            <div class="stat-sub text-muted">{{ $doctor->specialization }}</div>
        </div>
    </div>
</div>

<div class="grid" style="grid-template-columns:340px 1fr;gap:20px;align-items:start;margin-bottom:20px">

    {{-- Doctor Info --}}
    <div style="display:flex;flex-direction:column;gap:16px">
        <div class="card">
            <div class="card-header"><i class="fa-solid fa-user-doctor"></i> Doctor Info</div>
            <div class="card-body">
                @php $rows = [
                    ['Doctor ID',       $doctor->doctor_id],
                    ['Specialization',  $doctor->specialization],
                    ['Qualification',   $doctor->qualification],
                    ['Registration No.', $doctor->registration_no ?: '—'],
                    ['Department',      $doctor->department?->name ?? '—'],
                    ['Experience',      $doctor->experience_years ? $doctor->experience_years.' years' : '—'],
                    ['Phone',           $doctor->phone ?: '—'],
                    ['Email',           $doctor->email ?: '—'],
                ]; @endphp
                @foreach($rows as [$k,$v])
                <div style="display:flex;padding:7px 0;border-bottom:1px solid var(--sc-border);font-size:13px">
                    <div style="width:45%;color:var(--sc-muted);font-weight:600">{{ $k }}</div>
                    <div>{{ $v }}</div>
                </div>
                @endforeach
            </div>
        </div>

        <div class="card">
            <div class="card-header"><i class="fa-solid fa-clock"></i> Schedule</div>
            <div class="card-body">
                @php $days = is_array($doctor->available_days) ? $doctor->available_days : (is_string($doctor->available_days) ? (json_decode($doctor->available_days, true) ?: []) : []); @endphp
                <div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:12px">
                    @foreach(['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'] as $day)
                    <span class="badge {{ in_array($day,$days) ? 'badge-teal' : 'badge-gray' }}">{{ substr($day,0,3) }}</span>
                    @endforeach
                </div>
                <div style="display:flex;gap:20px">
                    <div><div class="text-muted text-small">Start</div><div class="fw-600">{{ $doctor->start_time ? \Carbon\Carbon::parse($doctor->start_time)->format('h:i A') : '—' }}</div></div>
                    <div><div class="text-muted text-small">End</div><div class="fw-600">{{ $doctor->end_time ? \Carbon\Carbon::parse($doctor->end_time)->format('h:i A') : '—' }}</div></div>
                    <div><div class="text-muted text-small">Slot</div><div class="fw-600">{{ $doctor->slot_duration ?? 15 }} min</div></div>
                </div>
                @if($doctor->user)
                <div style="margin-top:14px;padding-top:12px;border-top:1px solid var(--sc-border)">
                    <div class="text-muted text-small" style="margin-bottom:4px">System Login</div>
                    <div class="mono" style="font-size:12.5px">{{ $doctor->user->email }}</div>
                    <span class="badge {{ $doctor->user->is_active ? 'badge-green' : 'badge-red' }}" style="margin-top:4px">{{ $doctor->user->is_active ? 'Active' : 'Suspended' }}</span>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Today's appointments --}}
    <div style="display:flex;flex-direction:column;gap:16px">
        <div class="card">
            <div class="card-header">
                <i class="fa-solid fa-calendar-day"></i> Today's Appointments — {{ today()->format('d M Y') }}
                <div class="card-actions"><span class="badge badge-navy">{{ $todayAppointments->count() }}</span></div>
            </div>
            @if($todayAppointments->count())
            <div class="table-wrap">
                <table class="sc-table">
                    <thead><tr><th>Time</th><th>Patient</th><th>Type</th><th>Chief Complaint</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    @foreach($todayAppointments as $a)
                    @php $bc=['Scheduled'=>'badge-gray','Confirmed'=>'badge-teal','Checked-In'=>'badge-amber','In-Progress'=>'badge-purple','Completed'=>'badge-green','Cancelled'=>'badge-red','Rescheduled'=>'badge-navy']; @endphp
                    <tr>
                        <td class="mono fw-600">{{ \Carbon\Carbon::parse($a->appointment_time)->format('h:i A') }}</td>
                        <td>
                            <a href="{{ route('patients.show', $a->patient) }}" style="color:var(--sc-navy);font-weight:600;text-decoration:none">{{ $a->patient->full_name }}</a>
                            <div class="text-small text-muted mono">{{ $a->patient->jhc_id }}</div>
                        </td>
                        <td><span class="badge badge-navy">{{ $a->type }}</span></td>
                        <td style="font-size:12.5px;color:var(--sc-muted)">{{ Str::limit($a->chief_complaint ?? '—', 40) }}</td>
                        <td><span class="badge {{ $bc[$a->status] ?? 'badge-gray' }}">{{ $a->status }}</span></td>
                        <td><a href="{{ route('appointments.show', $a) }}" class="btn btn-sm btn-outline">View</a></td>
                    </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="card-body text-center text-muted" style="padding:30px">No appointments today.</div>
            @endif
        </div>

        <div class="card">
            <div class="card-header">
                <i class="fa-solid fa-stethoscope"></i> Recent Encounters
                <div class="card-actions">
                    <a href="{{ route('encounters.index', ['doctor_id' => $doctor->id]) }}" class="btn btn-sm btn-outline no-print">View All</a>
                </div>
            </div>
            @if($recentEncounters->count())
            <div class="table-wrap">
                <table class="sc-table">
                    <thead><tr><th>Date</th><th>Encounter No.</th><th>Patient</th><th>Type</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    @foreach($recentEncounters as $enc)
                    @php $sc=['In Progress'=>'amber','Completed'=>'green','Cancelled'=>'red']; @endphp
                    <tr>
                        <td>{{ $enc->encounter_date->format('d M Y') }}</td>
                        <td class="mono fw-600">{{ $enc->encounter_no }}</td>
                        <td>
                            <a href="{{ route('patients.show', $enc->patient) }}" style="color:var(--sc-navy);font-weight:600;text-decoration:none">{{ $enc->patient->full_name }}</a>
                        </td>
                        <td><span class="badge badge-navy">{{ $enc->visit_type }}</span></td>
                        <td><span class="badge badge-{{ $sc[$enc->status] ?? 'gray' }}">{{ $enc->status }}</span></td>
                        <td><a href="{{ route('encounters.show', $enc) }}" class="btn btn-sm btn-outline">View</a></td>
                    </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="card-body text-center text-muted" style="padding:30px">No encounters recorded yet.</div>
            @endif
        </div>
    </div>

</div>

@endsection
