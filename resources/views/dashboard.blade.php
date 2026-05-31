@extends('layouts.app')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
@php $u = auth()->user(); @endphp

<div class="page-header">
    <div class="page-header-left">
        <h1>Good {{ now()->hour < 12 ? 'Morning' : (now()->hour < 17 ? 'Afternoon' : 'Evening') }}, {{ $u->name }}</h1>
        <div class="breadcrumb">{{ now()->format('l, d F Y') }} &nbsp;·&nbsp; Hospital Overview</div>
    </div>
    <div style="display:flex;gap:8px;align-items:center">
        <span id="kpi-last-updated" style="font-size:11px;color:var(--sc-muted)"><i class="fa-solid fa-circle" style="font-size:7px;color:var(--sc-green);margin-right:4px"></i>Live</span>
        @if($u->hasRole(['admin','doctor','nurse','receptionist']))
        <a href="{{ route('admissions.create') }}" class="btn btn-outline"><i class="fa-solid fa-bed-pulse"></i> Admit Patient</a>
        @endif
        <a href="{{ route('appointments.create') }}" class="btn btn-primary"><i class="fa-solid fa-calendar-plus"></i> Book Appointment</a>
    </div>
</div>

{{-- ── KPI Row 1: OPD --}}
@if($u->hasRole(['admin','doctor','nurse','receptionist']))
<div style="font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--sc-muted);margin-bottom:8px">OPD</div>
<div class="grid grid-4 mb-4">
    <a href="{{ route('opd.index') }}" class="stat-card" style="text-decoration:none;color:inherit;cursor:pointer">
        <div class="stat-icon teal"><i class="fa-solid fa-ticket"></i></div>
        <div>
            <div class="stat-value" id="kpi-opd-tokens">{{ $stats['today_opd_tokens'] }}</div>
            <div class="stat-label">OPD Tokens Today</div>
            <div class="stat-sub"><span style="color:var(--sc-amber)" id="kpi-opd-waiting">{{ $stats['opd_waiting'] }} waiting</span></div>
        </div>
    </a>
    <a href="{{ route('appointments.index') }}" class="stat-card" style="text-decoration:none;color:inherit;cursor:pointer">
        <div class="stat-icon teal"><i class="fa-solid fa-calendar-check"></i></div>
        <div>
            <div class="stat-value" id="kpi-appts">{{ $stats['today_appointments'] }}</div>
            <div class="stat-label">Appointments Today</div>
            <div class="stat-sub text-muted"><span id="kpi-appts-pending">{{ $stats['pending_appointments'] }}</span> pending · <span id="kpi-today-encounters">{{ $stats['today_encounters'] ?? 0 }}</span> visits done</div>
        </div>
    </a>
    <a href="{{ route('triage.index') }}" class="stat-card" style="text-decoration:none;color:inherit;cursor:pointer">
        <div class="stat-icon amber"><i class="fa-solid fa-notes-medical"></i></div>
        <div>
            <div class="stat-value">{{ $pendingTriage }}</div>
            <div class="stat-label">Pending Triage</div>
            <div class="stat-sub">
                <span style="color:var(--sc-teal);font-size:11px">Nurse Station →</span>
                @if($followUpsToday->count()) &nbsp;·&nbsp; <span style="color:var(--sc-amber);font-size:11px">{{ $followUpsToday->count() }} follow-up{{ $followUpsToday->count()>1?'s':'' }} due</span>@endif
            </div>
        </div>
    </a>
    <a href="{{ route('patients.index') }}" class="stat-card" style="text-decoration:none;color:inherit;cursor:pointer">
        <div class="stat-icon green"><i class="fa-solid fa-users"></i></div>
        <div>
            <div class="stat-value">{{ $stats['today_patients'] }}</div>
            <div class="stat-label">New Patients Today</div>
            <div class="stat-sub text-muted">{{ number_format($stats['total_patients']) }} total registered</div>
        </div>
    </a>
</div>
@endif

{{-- ── KPI Row 2: IPD --}}
@if($u->hasRole(['admin','doctor','nurse','receptionist']))
<div style="font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--sc-muted);margin-bottom:8px">IPD</div>
<div class="grid grid-4 mb-4">
    <a href="{{ route('ipd.dashboard') }}" class="stat-card" style="text-decoration:none;color:inherit;cursor:pointer">
        <div class="stat-icon teal"><i class="fa-solid fa-bed-pulse"></i></div>
        <div>
            <div class="stat-value" id="kpi-ipd-current">{{ $stats['ipd_current'] }}</div>
            <div class="stat-label">Currently Admitted</div>
            <div class="stat-sub text-muted"><span id="kpi-ipd-admitted">{{ $stats['ipd_today_admitted'] }}</span> admitted today</div>
        </div>
    </a>
    <a href="{{ route('ipd.dashboard') }}" class="stat-card" style="text-decoration:none;color:inherit;cursor:pointer">
        <div class="stat-icon green"><i class="fa-solid fa-bed"></i></div>
        <div>
            <div class="stat-value" id="kpi-beds">{{ $stats['beds_available'] }}</div>
            <div class="stat-label">Beds Available</div>
            <div class="stat-sub">
                @php $occ = $stats['beds_total'] > 0 ? round((($stats['beds_total']-$stats['beds_available'])/$stats['beds_total'])*100) : 0; @endphp
                <div style="height:5px;background:var(--sc-border);border-radius:3px;margin-top:4px" id="kpi-occ-bar-wrap">
                    <div id="kpi-occ-bar" style="height:100%;width:{{ $occ }}%;background:{{ $occ>=90?'var(--sc-red)':($occ>=70?'var(--sc-amber)':'var(--sc-teal)') }};border-radius:3px"></div>
                </div>
                <span style="font-size:10px;color:var(--sc-muted)" id="kpi-occ-pct">{{ $occ }}% occupied</span>
            </div>
        </div>
    </a>
    <a href="{{ route('admissions.index') }}" class="stat-card" style="text-decoration:none;color:inherit;cursor:pointer">
        <div class="stat-icon amber"><i class="fa-solid fa-person-walking-arrow-right"></i></div>
        <div>
            <div class="stat-value" id="kpi-discharged">{{ $stats['ipd_today_discharged'] }}</div>
            <div class="stat-label">Discharged Today</div>
            <div class="stat-sub"><span style="color:var(--sc-teal);font-size:11px">View All →</span></div>
        </div>
    </a>
    <a href="{{ route('billing.daily') }}" class="stat-card" style="text-decoration:none;color:inherit;cursor:pointer">
        <div class="stat-icon navy"><i class="fa-solid fa-file-invoice-dollar"></i></div>
        <div>
            <div class="stat-value" id="kpi-revenue">₹{{ number_format($stats['total_today_revenue'],0) }}</div>
            <div class="stat-label">Revenue Today</div>
            <div class="stat-sub text-muted" id="kpi-revenue-sub">OPD ₹{{ number_format($stats['opd_collection_today'],0) }} · IPD ₹{{ number_format($stats['ipd_charges_today'],0) }}</div>
        </div>
    </a>
</div>
@endif

<div class="grid" style="grid-template-columns:2fr 1fr;gap:20px">
    <div>
        {{-- Today's Appointments --}}
        <div class="card mb-4">
            <div class="card-header">
                <i class="fa-solid fa-list-check"></i> Today's Appointments
                <span class="badge badge-teal" style="margin-left:8px">{{ $todayAppointments->count() }}</span>
                <div class="card-actions">
                    <a href="{{ route('appointments.index') }}" class="btn btn-outline btn-sm">View All</a>
                </div>
            </div>
            @if($todayAppointments->isEmpty())
            <div class="card-body text-center text-muted" style="padding:40px">
                <i class="fa-solid fa-calendar-xmark" style="font-size:32px;opacity:.3;display:block;margin-bottom:10px"></i>
                No appointments scheduled for today.
            </div>
            @else
            <div class="table-wrap">
                <table class="sc-table">
                    <thead>
                        <tr><th>#</th><th>Patient</th><th>Doctor</th><th>Time</th><th>Status</th><th>Action</th></tr>
                    </thead>
                    <tbody>
                    @foreach($todayAppointments as $i => $appt)
                    <tr>
                        <td class="text-muted mono">{{ $i+1 }}</td>
                        <td>
                            <a href="{{ route('patients.show', $appt->patient) }}" style="font-weight:600;color:var(--sc-navy);text-decoration:none">{{ $appt->patient->full_name }}</a>
                            <div class="text-small text-muted mono">{{ $appt->patient->jhc_id }}</div>
                        </td>
                        <td>{{ $appt->doctor->full_name }}<div class="text-small text-muted">{{ $appt->doctor->specialization }}</div></td>
                        <td class="mono fw-600">{{ \Carbon\Carbon::parse($appt->appointment_time)->format('h:i A') }}</td>
                        <td>
                            @php $badgeMap=['Scheduled'=>'badge-gray','Confirmed'=>'badge-teal','Checked-In'=>'badge-amber','In-Progress'=>'badge-purple','Completed'=>'badge-green','Cancelled'=>'badge-red']; @endphp
                            <span class="badge {{ $badgeMap[$appt->status] ?? 'badge-gray' }}">{{ $appt->status }}</span>
                        </td>
                        <td>
                            @if(in_array($appt->status,['Scheduled','Confirmed']))
                            <form method="POST" action="{{ route('appointments.checkin', $appt) }}" style="display:inline">@csrf
                                <button class="btn btn-sm btn-outline" style="min-width:100px"><i class="fa-solid fa-door-open"></i> Check In</button>
                            </form>
                            @elseif($appt->status==='Checked-In')
                            <a href="{{ route('appointments.start-encounter', $appt) }}" class="btn btn-sm btn-outline" style="min-width:100px"><i class="fa-solid fa-play"></i> Start Visit</a>
                            @elseif($appt->status==='In-Progress')
                            <a href="{{ route('encounters.show', $appt->encounter) }}" class="btn btn-sm btn-outline" style="min-width:100px"><i class="fa-solid fa-stethoscope"></i> Continue</a>
                            @else
                            <a href="{{ route('appointments.show', $appt) }}" class="btn btn-sm btn-outline" style="min-width:100px"><i class="fa-solid fa-eye"></i> View</a>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>

        {{-- Combined Revenue Chart --}}
        <div class="card">
            <div class="card-header">
                <i class="fa-solid fa-chart-bar"></i> Monthly Revenue {{ date('Y') }}
                <div class="card-actions" style="font-size:12px;color:var(--sc-muted)">
                    <span style="display:inline-flex;align-items:center;gap:4px"><span style="width:10px;height:10px;background:#0ab4b4;border-radius:2px;display:inline-block"></span> OPD</span>
                    &nbsp;
                    <span style="display:inline-flex;align-items:center;gap:4px"><span style="width:10px;height:10px;background:#0f1f3d;border-radius:2px;display:inline-block"></span> IPD</span>
                </div>
            </div>
            <div class="card-body"><canvas id="revenueChart" height="120"></canvas></div>
        </div>
    </div>

    {{-- Right sidebar --}}
    <div style="display:flex;flex-direction:column;gap:20px">

        {{-- Live Attendance — HR module + hr.attendance permission --}}
        @if($u->can('hr.attendance') && app(\App\Services\LicenseService::class)->isEnabled('hr'))
        @php
            $todayAttendance = \App\Models\Attendance::whereDate('date', today())->get()->keyBy('staff_id');
            $activeStaff     = \App\Models\Staff::where('is_active', true)->get();
            $statBuckets     = ['present'=>0,'absent'=>0,'leave'=>0,'off'=>0,'unmarked'=>0];
            foreach ($activeStaff as $s) {
                $r = $todayAttendance->get($s->id);
                if (!$r) { $statBuckets['unmarked']++; continue; }
                if (in_array($r->status, ['present','half_day']))                $statBuckets['present']++;
                elseif ($r->status === 'absent')                                  $statBuckets['absent']++;
                elseif (in_array($r->status, ['paid_leave','unpaid_leave']))      $statBuckets['leave']++;
                elseif (in_array($r->status, ['week_off','holiday']))             $statBuckets['off']++;
            }
        @endphp
        @if($activeStaff->isNotEmpty())
        <div class="card">
            <div class="card-header">
                <i class="fa-solid fa-users-rectangle"></i> Today's Attendance
                <div class="card-actions"><a href="{{ route('hr.attendance.index') }}" class="btn btn-sm btn-outline">Mark</a></div>
            </div>
            <div class="card-body" style="padding:14px 16px">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:10px">
                    <div style="text-align:center;background:#dcfce7;border-radius:6px;padding:8px"><div style="font-size:11px;color:#14532d;text-transform:uppercase">Present</div><div style="font-size:20px;font-weight:700;color:#16a34a">{{ $statBuckets['present'] }}</div></div>
                    <div style="text-align:center;background:#fee2e2;border-radius:6px;padding:8px"><div style="font-size:11px;color:#7f1d1d;text-transform:uppercase">Absent</div><div style="font-size:20px;font-weight:700;color:#dc2626">{{ $statBuckets['absent'] }}</div></div>
                    <div style="text-align:center;background:#ede9fe;border-radius:6px;padding:8px"><div style="font-size:11px;color:#5b21b6;text-transform:uppercase">Leave</div><div style="font-size:20px;font-weight:700;color:#7c3aed">{{ $statBuckets['leave'] }}</div></div>
                    <div style="text-align:center;background:#e2e8f0;border-radius:6px;padding:8px"><div style="font-size:11px;color:#1e293b;text-transform:uppercase">Off</div><div style="font-size:20px;font-weight:700;color:#475569">{{ $statBuckets['off'] }}</div></div>
                </div>
                @if($statBuckets['unmarked'] > 0)
                <a href="{{ route('hr.attendance.index') }}" style="display:block;text-align:center;background:#fef3c7;border:1px solid #fde68a;color:#78350f;padding:8px;border-radius:6px;font-size:12px;font-weight:600;text-decoration:none">
                    <i class="fa-solid fa-circle-exclamation"></i> {{ $statBuckets['unmarked'] }} staff unmarked — click to mark
                </a>
                @else
                <div style="text-align:center;color:var(--sc-muted);font-size:12px">All {{ $activeStaff->count() }} staff marked for today.</div>
                @endif
            </div>
        </div>
        @endif
        @endif

        {{-- IPD Census --}}
        @if($u->hasRole(['admin','doctor','nurse','receptionist']) && $ipdCensus->isNotEmpty())
        <div class="card">
            <div class="card-header">
                <i class="fa-solid fa-building"></i> IPD Census
                <div class="card-actions"><a href="{{ route('ipd.dashboard') }}" class="btn btn-sm btn-outline">Full View</a></div>
            </div>
            <div class="card-body" style="display:flex;flex-direction:column;gap:10px">
                @foreach($ipdCensus as $ward)
                <div style="display:flex;justify-content:space-between;align-items:center">
                    <span style="font-size:13px">{{ $ward->ward_name }}</span>
                    <span class="badge badge-teal">{{ $ward->count }} pts</span>
                </div>
                @endforeach
                <div style="border-top:1px solid var(--sc-border);padding-top:8px;display:flex;justify-content:space-between;font-weight:600;font-size:13px">
                    <span>Total Inpatients</span>
                    <span>{{ $ipdCensus->sum('count') }}</span>
                </div>
            </div>
        </div>
        @endif

        {{-- Alerts / Quick Links --}}
        <div class="card">
            <div class="card-header"><i class="fa-solid fa-triangle-exclamation"></i> Alerts</div>
            <div class="card-body" style="display:flex;flex-direction:column;gap:10px">
                @if($stats['pending_labs'] > 0)
                <a href="{{ route('lab.index') }}" style="text-decoration:none;display:flex;align-items:center;gap:10px;padding:10px;background:rgba(245,166,35,.08);border-radius:8px;border:1px solid rgba(245,166,35,.2)">
                    <i class="fa-solid fa-flask-vial" style="color:var(--sc-amber);font-size:16px;width:20px"></i>
                    <div><div style="font-weight:600;font-size:12.5px">{{ $stats['pending_labs'] }} Pending Lab Orders</div></div>
                </a>
                @endif
                @if($stats['low_stock'] > 0)
                <a href="{{ route('pharmacy.dashboard') }}" style="text-decoration:none;display:flex;align-items:center;gap:10px;padding:10px;background:rgba(232,76,76,.08);border-radius:8px;border:1px solid rgba(232,76,76,.2)">
                    <i class="fa-solid fa-pills" style="color:var(--sc-red);font-size:16px;width:20px"></i>
                    <div><div style="font-weight:600;font-size:12.5px">{{ $stats['low_stock'] }} Low Stock Items</div></div>
                </a>
                @endif
                @if($stats['pending_bills'] > 0)
                <a href="{{ route('billing.index') }}" style="text-decoration:none;display:flex;align-items:center;gap:10px;padding:10px;background:rgba(232,76,76,.08);border-radius:8px;border:1px solid rgba(232,76,76,.2)">
                    <i class="fa-solid fa-file-invoice-dollar" style="color:var(--sc-red);font-size:16px;width:20px"></i>
                    <div>
                        <div style="font-weight:600;font-size:12.5px">{{ $stats['pending_bills'] }} Unpaid Bills</div>
                        @if(($stats['pending_dues_amount'] ?? 0) > 0)
                            <div style="font-size:11px;color:#666">₹{{ number_format($stats['pending_dues_amount'], 0) }} outstanding</div>
                        @endif
                    </div>
                </a>
                @endif
                @if(($stats['expiring_stock_count'] ?? 0) > 0)
                <a href="{{ route('pharmacy.stock-register') }}" style="text-decoration:none;display:flex;align-items:center;gap:10px;padding:10px;background:rgba(245,166,35,.08);border-radius:8px;border:1px solid rgba(245,166,35,.2)">
                    <i class="fa-solid fa-clock" style="color:var(--sc-amber);font-size:16px;width:20px"></i>
                    <div><div style="font-weight:600;font-size:12.5px">{{ $stats['expiring_stock_count'] }} Stock Batch{{ $stats['expiring_stock_count']>1?'es':'' }} Expiring (90d)</div></div>
                </a>
                @endif
                @if(($stats['labs_awaiting_review'] ?? 0) > 0)
                <a href="{{ route('lab.index') }}" style="text-decoration:none;display:flex;align-items:center;gap:10px;padding:10px;background:rgba(10,180,180,.08);border-radius:8px;border:1px solid rgba(10,180,180,.2)">
                    <i class="fa-solid fa-flask" style="color:var(--sc-teal);font-size:16px;width:20px"></i>
                    <div><div style="font-weight:600;font-size:12.5px">{{ $stats['labs_awaiting_review'] }} Lab Result{{ $stats['labs_awaiting_review']>1?'s':'' }} Ready Today</div></div>
                </a>
                @endif
                @if(($stats['expiring_rx_count'] ?? 0) > 0)
                <a href="{{ route('pharmacy.dashboard') }}" style="text-decoration:none;display:flex;align-items:center;gap:10px;padding:10px;background:rgba(245,166,35,.08);border-radius:8px;border:1px solid rgba(245,166,35,.2)">
                    <i class="fa-solid fa-prescription-bottle-medical" style="color:var(--sc-amber);font-size:16px;width:20px"></i>
                    <div>
                        <div style="font-weight:600;font-size:12.5px">{{ $stats['expiring_rx_count'] }} Rx Expiring This Week</div>
                        <div style="font-size:11px;color:#666">Not yet dispensed</div>
                    </div>
                </a>
                @endif
                @if($stats['pending_labs'] == 0 && $stats['low_stock'] == 0 && $stats['pending_bills'] == 0 && ($stats['expiring_stock_count']??0) == 0 && ($stats['labs_awaiting_review']??0) == 0 && ($stats['expiring_rx_count']??0) == 0)
                <div class="text-center text-muted" style="padding:20px"><i class="fa-solid fa-circle-check" style="color:var(--sc-green);font-size:20px;display:block;margin-bottom:6px"></i> All clear!</div>
                @endif
            </div>
        </div>

        {{-- Expiring Stock detail (top 8 batches) --}}
        @if($expiringStock->count())
        <div class="card">
            <div class="card-header">
                <i class="fa-solid fa-hourglass-end" style="color:var(--sc-amber)"></i> Expiring Soon
                <div class="card-actions"><a href="{{ route('pharmacy.stock-register') }}" class="btn btn-sm btn-outline">Stock Register</a></div>
            </div>
            <div class="card-body" style="padding:0">
                @foreach($expiringStock as $row)
                @php
                    $daysLeft = (int) \Carbon\Carbon::parse($row->expiry_date)->diffInDays(today(), false);
                    $daysLeft = abs($daysLeft);
                @endphp
                <div style="display:flex;align-items:center;gap:10px;padding:10px 16px;border-bottom:1px solid var(--sc-border)">
                    <div style="flex:1;min-width:0">
                        <div style="font-weight:600;font-size:12.5px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $row->medicine_name }}</div>
                        <div style="font-size:11px;color:var(--sc-muted)">Batch {{ $row->batch_number ?: '—' }} &middot; Qty {{ $row->quantity }}</div>
                    </div>
                    <div style="text-align:right">
                        <div style="font-size:11.5px;font-weight:600;color:{{ $daysLeft <= 30 ? 'var(--sc-red)' : 'var(--sc-amber)' }}">{{ \Carbon\Carbon::parse($row->expiry_date)->format('d M Y') }}</div>
                        <div style="font-size:10.5px;color:var(--sc-muted)">{{ $daysLeft }} d left</div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Lab orders ready today --}}
        @if($labsAwaitingReview->count())
        <div class="card">
            <div class="card-header">
                <i class="fa-solid fa-clipboard-check" style="color:var(--sc-teal)"></i> Lab Results Ready Today
                <div class="card-actions"><a href="{{ route('lab.index') }}" class="btn btn-sm btn-outline">All Orders</a></div>
            </div>
            <div class="card-body" style="padding:0">
                @foreach($labsAwaitingReview as $lo)
                <a href="{{ route('lab.order', $lo->id) }}" style="display:flex;align-items:center;gap:10px;padding:10px 16px;border-bottom:1px solid var(--sc-border);text-decoration:none;color:var(--sc-text)">
                    <i class="fa-solid fa-flask" style="color:var(--sc-teal);font-size:13px;width:18px"></i>
                    <div style="flex:1;min-width:0">
                        <div style="font-weight:600;font-size:12.5px">{{ $lo->order_no }}</div>
                        <div style="font-size:11px;color:var(--sc-muted)">{{ $lo->first_name }} {{ $lo->last_name }} &middot; {{ $lo->jhc_id }}</div>
                    </div>
                    <i class="fa-solid fa-chevron-right" style="font-size:11px;color:var(--sc-muted)"></i>
                </a>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Expiring Rx detail --}}
        @if(isset($expiringRx) && $expiringRx->count())
        <div class="card">
            <div class="card-header">
                <i class="fa-solid fa-prescription-bottle-medical" style="color:var(--sc-amber)"></i> Rx Expiring This Week
                <div class="card-actions"><a href="{{ route('pharmacy.dashboard') }}" class="btn btn-sm btn-outline">Dispense</a></div>
            </div>
            <div class="card-body" style="padding:0">
                @foreach($expiringRx as $rx)
                @php $daysLeft = (int)\Carbon\Carbon::parse($rx->end_date)->diffInDays(today(), false); $daysLeft = abs($daysLeft); @endphp
                <a href="{{ route('patients.show', $rx->patient_id) }}" style="display:flex;align-items:center;gap:10px;padding:10px 16px;border-bottom:1px solid var(--sc-border);text-decoration:none;color:var(--sc-text)">
                    <div style="flex:1;min-width:0">
                        <div style="font-weight:600;font-size:12.5px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $rx->first_name }} {{ $rx->last_name }}</div>
                        <div style="font-size:11px;color:var(--sc-muted)">{{ $rx->jhc_id }} &middot; {{ $rx->medicine_name }}</div>
                    </div>
                    <div style="text-align:right;flex-shrink:0">
                        <div style="font-size:11.5px;font-weight:600;color:{{ $daysLeft <= 2 ? 'var(--sc-red)' : 'var(--sc-amber)' }}">{{ $daysLeft }}d left</div>
                        <div style="font-size:10.5px;color:var(--sc-muted)">ends {{ \Carbon\Carbon::parse($rx->end_date)->format('d M') }}</div>
                    </div>
                </a>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Critical low stock --}}
        @if($lowStockList->count())
        <div class="card">
            <div class="card-header">
                <i class="fa-solid fa-battery-low" style="color:var(--sc-red)"></i> Stock Below Reorder
                <div class="card-actions"><a href="{{ route('pharmacy.stock') }}" class="btn btn-sm btn-outline">Pharmacy</a></div>
            </div>
            <div class="card-body" style="padding:0">
                @foreach($lowStockList as $row)
                <div style="display:flex;align-items:center;gap:10px;padding:10px 16px;border-bottom:1px solid var(--sc-border)">
                    <div style="flex:1;min-width:0">
                        <div style="font-weight:600;font-size:12.5px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $row->medicine_name }}</div>
                        <div style="font-size:11px;color:var(--sc-muted)">Reorder at {{ $row->reorder_level }}</div>
                    </div>
                    <span class="badge {{ $row->total_qty == 0 ? 'badge-red' : 'badge-amber' }}">{{ $row->total_qty }} left</span>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Follow-ups Due Today --}}
        <div class="card">
            <div class="card-header">
                <i class="fa-solid fa-calendar-check" style="color:var(--sc-amber)"></i> Follow-ups Due Today
                @if($followUpsToday->count())
                <span class="badge badge-amber" style="margin-left:6px">{{ $followUpsToday->count() }}</span>
                @endif
            </div>
            @if($followUpsToday->isEmpty())
            <div class="card-body text-center text-muted" style="padding:20px;font-size:12px">
                <i class="fa-solid fa-circle-check" style="color:var(--sc-green);display:block;font-size:20px;margin-bottom:6px"></i>
                No follow-ups scheduled for today.
            </div>
            @else
            @foreach($followUpsToday as $enc)
            <div style="display:flex;align-items:center;gap:10px;padding:10px 16px;border-bottom:1px solid var(--sc-border)">
                <a href="{{ route('encounters.show', $enc) }}" style="display:flex;align-items:center;gap:10px;text-decoration:none;color:var(--sc-text);flex:1;min-width:0">
                    <div style="width:32px;height:32px;border-radius:50%;background:rgba(245,166,35,.12);color:var(--sc-amber);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:12px;flex-shrink:0">{{ strtoupper(substr($enc->patient->first_name,0,1)) }}</div>
                    <div style="flex:1;min-width:0">
                        <div style="font-weight:600;font-size:12.5px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $enc->patient->full_name }}</div>
                        <div style="font-size:11px;color:var(--sc-muted)">{{ $enc->patient->jhc_id }} &middot; {{ $enc->encounter_no }}</div>
                    </div>
                </a>
                <button type="button" class="btn btn-icon btn-sm btn-outline" title="Book follow-up appointment" onclick="scBookFollowUp({{ $enc->id }}, this)"><i class="fa-solid fa-calendar-plus"></i></button>
            </div>
            @endforeach
            @endif
        </div>

        {{-- Recent Patients --}}
        <div class="card">
            <div class="card-header"><i class="fa-solid fa-clock-rotate-left"></i> Recent Patients</div>
            @foreach($recentPatients as $p)
            <a href="{{ route('patients.show', $p) }}" style="display:flex;align-items:center;gap:12px;padding:12px 16px;text-decoration:none;color:var(--sc-text);border-bottom:1px solid var(--sc-border)">
                <div style="width:34px;height:34px;border-radius:50%;background:var(--sc-teal-glow);color:var(--sc-teal);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;flex-shrink:0">{{ strtoupper(substr($p->first_name,0,1)) }}</div>
                <div style="flex:1">
                    <div style="font-weight:600;font-size:13px">{{ $p->full_name }}</div>
                    <div style="font-size:11px;color:var(--sc-muted)">{{ $p->jhc_id }} &middot; {{ $p->age }}</div>
                </div>
                <i class="fa-solid fa-chevron-right" style="font-size:11px;color:var(--sc-muted)"></i>
            </a>
            @endforeach
        </div>

        {{-- Admin quick actions --}}
        @if($u->isAdmin())
        <div class="card">
            <div class="card-header"><i class="fa-solid fa-bolt"></i> Quick Actions</div>
            <div class="card-body" style="display:flex;flex-direction:column;gap:8px">
                <form method="POST" action="{{ route('ipd.room-charges') }}">
                    @csrf
                    <button type="submit" class="btn btn-outline" style="width:100%;justify-content:flex-start">
                        <i class="fa-solid fa-bed" style="color:var(--sc-teal)"></i> Generate Today's Room Charges
                    </button>
                </form>
                <a href="{{ route('reports.index') }}" class="btn btn-outline" style="justify-content:flex-start">
                    <i class="fa-solid fa-chart-line" style="color:var(--sc-teal)"></i> Reports
                </a>
                <a href="{{ route('logs.index') }}" class="btn btn-outline" style="justify-content:flex-start">
                    <i class="fa-solid fa-clock-rotate-left" style="color:var(--sc-teal)"></i> Activity Logs
                </a>
            </div>
        </div>
        @endif
    </div>
</div>
{{-- Revenue Drill-Down Modal --}}
<div id="rev-drill-modal" style="display:none;position:fixed;inset:0;z-index:1050;background:rgba(0,0,0,.5);align-items:center;justify-content:center">
    <div style="background:#fff;border-radius:10px;width:760px;max-width:95vw;box-shadow:0 8px 40px rgba(0,0,0,.22)">
        <div style="padding:14px 20px;border-bottom:1px solid var(--sc-border);display:flex;align-items:center;justify-content:space-between">
            <div>
                <div style="font-weight:700;font-size:14px"><i class="fa-solid fa-chart-bar" style="color:var(--sc-teal);margin-right:6px"></i><span id="drill-title">Daily Revenue</span></div>
                <div style="font-size:11px;color:var(--sc-muted);margin-top:2px">Click a bar on the main chart to drill into any month</div>
            </div>
            <button onclick="closeDrilldown()" style="background:none;border:none;font-size:20px;cursor:pointer;color:#888">&times;</button>
        </div>
        <div style="padding:16px 20px">
            <div style="display:flex;gap:24px;margin-bottom:14px">
                <div style="text-align:center">
                    <div style="font-size:11px;color:var(--sc-muted);margin-bottom:2px"><span style="display:inline-block;width:10px;height:10px;background:#0ab4b4;border-radius:2px;margin-right:4px"></span>OPD Total</div>
                    <div id="drill-opd-total" style="font-size:18px;font-weight:700;color:var(--sc-teal)">—</div>
                </div>
                <div style="text-align:center">
                    <div style="font-size:11px;color:var(--sc-muted);margin-bottom:2px"><span style="display:inline-block;width:10px;height:10px;background:#0f1f3d;border-radius:2px;margin-right:4px"></span>IPD Total</div>
                    <div id="drill-ipd-total" style="font-size:18px;font-weight:700;color:var(--sc-navy)">—</div>
                </div>
                <div style="text-align:center">
                    <div style="font-size:11px;color:var(--sc-muted);margin-bottom:2px">Combined</div>
                    <div id="drill-combined-total" style="font-size:18px;font-weight:700">—</div>
                </div>
            </div>
            <canvas id="drillCanvas" height="130"></canvas>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
<script>
// ── DASHBOARD AUTO-REFRESH (every 60s) ──
(function() {
    const fmt = n => '₹' + Number(n).toLocaleString('en-IN', {maximumFractionDigits:0});
    const set = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };

    function refreshStats() {
        fetch('{{ route("dashboard.stats") }}', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.ok ? r.json() : null)
            .then(d => {
                if (!d) return;
                set('kpi-opd-tokens',  d.today_opd_tokens);
                set('kpi-opd-waiting', d.opd_waiting + ' waiting');
                set('kpi-appts',       d.today_appointments);
                set('kpi-appts-pending', d.pending_appointments);
                set('kpi-today-encounters', d.today_encounters);
                set('kpi-ipd-current', d.ipd_current);
                set('kpi-ipd-admitted', d.ipd_today_admitted);
                set('kpi-beds',        d.beds_available);
                set('kpi-discharged',  d.ipd_today_discharged);
                set('kpi-revenue',     fmt(d.total_today_revenue));
                set('kpi-revenue-sub', 'OPD ' + fmt(d.opd_collection_today) + ' · IPD ' + fmt(d.ipd_charges_today));
                set('kpi-occ-pct',     d.beds_occ_pct + '% occupied');
                const bar = document.getElementById('kpi-occ-bar');
                if (bar) {
                    bar.style.width = d.beds_occ_pct + '%';
                    bar.style.background = d.beds_occ_pct >= 90 ? 'var(--sc-red)' : (d.beds_occ_pct >= 70 ? 'var(--sc-amber)' : 'var(--sc-teal)');
                }
                const ts = document.getElementById('kpi-last-updated');
                if (ts) ts.textContent = 'Updated ' + new Date().toLocaleTimeString('en-IN', {hour:'2-digit', minute:'2-digit'});
            }).catch(() => {});
    }

    setInterval(refreshStats, 60000);
})();
</script>
<script>
const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
const opd = @json($opdRevenue);
const ipd = @json($ipdRevenue);
const opdData = months.map((_,i) => opd[i+1]?.total ?? 0);
const ipdData = months.map((_,i) => ipd[i+1]?.total ?? 0);

const revenueChart = new Chart(document.getElementById('revenueChart').getContext('2d'), {
    type: 'bar',
    data: {
        labels: months,
        datasets: [
            { label:'OPD', data: opdData, backgroundColor:'rgba(10,180,180,0.75)', borderRadius:3 },
            { label:'IPD', data: ipdData, backgroundColor:'rgba(15,31,61,0.75)',   borderRadius:3 },
        ]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => '₹' + Number(ctx.raw).toLocaleString('en-IN',{maximumFractionDigits:0}) } } },
        scales: {
            x: { stacked: false, ticks:{font:{size:10}}, grid:{display:false} },
            y: { beginAtZero:true, ticks:{callback:v=>'₹'+(v>=1000?(v/1000)+'k':v),font:{size:10}}, grid:{color:'rgba(0,0,0,0.05)'} }
        },
        onClick(evt, activeEls) {
            if (!activeEls.length) return;
            openDrilldown(activeEls[0].index + 1, {{ date('Y') }});
        },
        onHover(evt, activeEls) {
            evt.native.target.style.cursor = activeEls.length ? 'pointer' : 'default';
        }
    }
});

let drillChart = null;
function openDrilldown(month, year) {
    document.getElementById('rev-drill-modal').style.display = 'flex';
    document.getElementById('drill-title').textContent = 'Loading…';
    ['drill-opd-total','drill-ipd-total','drill-combined-total'].forEach(id => document.getElementById(id).textContent = '—');
    fetch(`{{ url('/dashboard/revenue-drilldown') }}?month=${month}&year=${year}`, { headers:{'X-Requested-With':'XMLHttpRequest'} })
        .then(r => r.json())
        .then(d => {
            document.getElementById('drill-title').textContent = d.label + ' — Daily Revenue';
            const fmt = n => '₹' + Number(n).toLocaleString('en-IN',{maximumFractionDigits:0});
            document.getElementById('drill-opd-total').textContent = fmt(d.opd_total);
            document.getElementById('drill-ipd-total').textContent = fmt(d.ipd_total);
            document.getElementById('drill-combined-total').textContent = fmt(d.opd_total + d.ipd_total);
            if (drillChart) drillChart.destroy();
            drillChart = new Chart(document.getElementById('drillCanvas').getContext('2d'), {
                type: 'bar',
                data: {
                    labels: d.days,
                    datasets: [
                        { label:'OPD', data:d.opd, backgroundColor:'rgba(10,180,180,0.75)', borderRadius:2 },
                        { label:'IPD', data:d.ipd, backgroundColor:'rgba(15,31,61,0.75)',   borderRadius:2 },
                    ]
                },
                options: {
                    responsive: true,
                    plugins: { legend:{display:true,position:'top',labels:{font:{size:11}}}, tooltip:{callbacks:{label:ctx=>'₹'+Number(ctx.raw).toLocaleString('en-IN',{maximumFractionDigits:0})}} },
                    scales: {
                        x: { ticks:{font:{size:10}}, grid:{display:false} },
                        y: { beginAtZero:true, ticks:{callback:v=>'₹'+(v>=1000?(v/1000)+'k':v),font:{size:10}}, grid:{color:'rgba(0,0,0,0.05)'} }
                    }
                }
            });
        });
}
function closeDrilldown() {
    document.getElementById('rev-drill-modal').style.display = 'none';
}
document.getElementById('rev-drill-modal').addEventListener('click', function(e) {
    if (e.target === this) closeDrilldown();
});
</script>
@endpush
