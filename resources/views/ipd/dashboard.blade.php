{{-- FILE: resources/views/ipd/dashboard.blade.php --}}
@extends('layouts.app')
@section('title','IPD Dashboard')
@section('page-title','IPD Dashboard')
@section('content')

<div class="page-header">
    <div class="page-header-left">
        <h1>IPD Dashboard</h1>
        <div class="breadcrumb">Inpatient Department — Live Overview</div>
    </div>
    @include('ipd._toolbar', ['inline'=>true])
</div>

{{-- KPI Row --}}
<div class="grid grid-4 mb-4">
    <a href="{{ route('admissions.index') }}" class="stat-card" style="text-decoration:none;color:inherit;cursor:pointer">
        <div class="stat-icon teal"><i class="fa-solid fa-bed-pulse"></i></div>
        <div>
            <div class="stat-value">{{ $occupiedBeds }}</div>
            <div class="stat-label">Currently Admitted</div>
            <div class="stat-sub text-muted">{{ $todayAdmissions }} admitted today</div>
        </div>
    </a>
    <a href="{{ route('wards.bed-board') }}" class="stat-card" style="text-decoration:none;color:inherit;cursor:pointer">
        <div class="stat-icon" style="background:rgba(39,196,122,.12);color:var(--sc-green)">
            <i class="fa-solid fa-bed"></i>
        </div>
        <div>
            <div class="stat-value">{{ $availableBeds }}</div>
            <div class="stat-label">Beds Available</div>
            <div class="stat-sub text-muted">of {{ $totalBeds }} total</div>
        </div>
    </a>
    <a href="{{ route('wards.bed-board') }}" class="stat-card" style="text-decoration:none;color:inherit;cursor:pointer">
        <div class="stat-icon amber"><i class="fa-solid fa-gauge-high"></i></div>
        <div>
            <div class="stat-value">{{ $occupancyPct }}%</div>
            <div class="stat-label">Bed Occupancy</div>
            <div class="stat-sub" style="margin-top:6px">
                <div style="height:6px;background:var(--sc-border);border-radius:3px">
                    <div style="height:100%;width:{{ $occupancyPct }}%;background:var(--sc-amber);border-radius:3px"></div>
                </div>
            </div>
        </div>
    </a>
    <a href="{{ route('admissions.index') }}" class="stat-card" style="text-decoration:none;color:inherit;cursor:pointer">
        <div class="stat-icon green"><i class="fa-solid fa-person-walking-arrow-right"></i></div>
        <div>
            <div class="stat-value">{{ $todayDischarges }}</div>
            <div class="stat-label">Discharged Today</div>
            <div class="stat-sub text-muted">Avg stay {{ $avgStay ? round($avgStay,1).'d' : '—' }} this month</div>
        </div>
    </a>
</div>

<div class="grid" style="grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px">
    <a href="{{ route('billing.index') }}" class="stat-card" style="text-decoration:none;color:inherit;cursor:pointer">
        <div class="stat-icon navy"><i class="fa-solid fa-file-invoice-dollar"></i></div>
        <div>
            <div class="stat-value">₹{{ number_format($revenueMonth,0) }}</div>
            <div class="stat-label">Revenue This Month</div>
            <div class="stat-sub text-muted">₹{{ number_format($revenueToday,0) }} today</div>
        </div>
    </a>
    <a href="{{ route('admissions.index') }}" class="stat-card" style="text-decoration:none;color:inherit;cursor:pointer">
        <div class="stat-icon red"><i class="fa-solid fa-triangle-exclamation"></i></div>
        <div>
            <div class="stat-value">{{ $longStay->count() }}</div>
            <div class="stat-label">Long Stay (&gt; 7 days)</div>
            <div class="stat-sub text-muted">{{ $longStay->isNotEmpty() ? $longStay->pluck('patient.first_name')->join(', ') : 'None' }}</div>
        </div>
    </a>
</div>

<div class="grid" style="grid-template-columns:2fr 1fr;gap:20px;align-items:start">

    {{-- Current inpatients --}}
    <div>
        <div class="card mb-4">
            <div class="card-header">
                <i class="fa-solid fa-bed-pulse"></i> Current Inpatients
                <div class="card-actions">
                    <a href="{{ route('admissions.index') }}" class="btn btn-sm btn-outline">All Admissions</a>
                </div>
            </div>
            <div class="table-wrap">
                <table class="sc-table">
                    <thead>
                        <tr>
                            <th>IPD No.</th>
                            <th>Patient</th>
                            <th>Ward / Bed</th>
                            <th>Doctor</th>
                            <th>Days</th>
                            <th>Diagnosis</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($currentAdmissions as $adm)
                        <tr {{ $adm->days_admitted > 7 ? 'style=background:rgba(245,166,35,.05)' : '' }}>
                            <td class="mono fw-600">{{ $adm->ipd_no }}</td>
                            <td>
                                <div class="fw-600">{{ $adm->patient->full_name }}</div>
                                <div class="text-small text-muted">{{ $adm->patient->age }} / {{ ucfirst($adm->patient->gender) }}</div>
                            </td>
                            <td>
                                <div>{{ $adm->ward->name }}</div>
                                <div class="text-small text-muted">Bed {{ $adm->bed->bed_number }}</div>
                            </td>
                            <td class="text-small">{{ $adm->admittingDoctor->full_name }}</td>
                            <td>
                                <span class="badge {{ $adm->days_admitted > 7 ? 'badge-amber' : 'badge-navy' }}">
                                    {{ $adm->days_admitted }}d
                                </span>
                            </td>
                            <td class="text-small" style="max-width:160px;white-space:normal">
                                {{ Str::limit($adm->provisional_diagnosis, 50) ?: '—' }}
                            </td>
                            <td>
                                <a href="{{ route('admissions.show', $adm) }}" class="btn btn-sm btn-outline">View</a>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="text-center text-muted" style="padding:30px">No current inpatients.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Recent discharges --}}
        <div class="card">
            <div class="card-header"><i class="fa-solid fa-person-walking-arrow-right"></i> Recent Discharges</div>
            <div class="table-wrap">
                <table class="sc-table">
                    <thead>
                        <tr><th>IPD No.</th><th>Patient</th><th>Discharged</th><th>Condition</th><th>Doctor</th><th></th></tr>
                    </thead>
                    <tbody>
                        @forelse($recentDischarges as $adm)
                        <tr>
                            <td class="mono">{{ $adm->ipd_no }}</td>
                            <td class="fw-600">{{ $adm->patient->full_name }}</td>
                            <td class="text-small">{{ $adm->discharge_date->format('d M Y') }}</td>
                            <td><span class="badge badge-{{ $adm->status_color }}">{{ $adm->status_label }}</span></td>
                            <td class="text-small">{{ $adm->admittingDoctor->full_name }}</td>
                            <td><a href="{{ route('admissions.show', $adm) }}" class="btn btn-sm btn-outline">View</a></td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center text-muted" style="padding:20px">No recent discharges.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Right sidebar --}}
    <div>
        {{-- Ward occupancy --}}
        <div class="card mb-4">
            <div class="card-header"><i class="fa-solid fa-building"></i> Ward Occupancy</div>
            <div class="card-body" style="display:flex;flex-direction:column;gap:14px">
                @foreach($wards as $ward)
                @php
                    $total    = $ward->beds->where('is_active',true)->count();
                    $occupied = $ward->beds->where('status','occupied')->count();
                    $pct      = $total > 0 ? round(($occupied/$total)*100) : 0;
                @endphp
                <div>
                    <div style="display:flex;justify-content:space-between;margin-bottom:4px">
                        <span class="fw-600" style="font-size:12.5px">{{ $ward->name }}</span>
                        <span style="font-size:12px;color:var(--sc-muted)">{{ $occupied }}/{{ $total }}</span>
                    </div>
                    <div style="height:8px;background:var(--sc-border);border-radius:4px;overflow:hidden">
                        <div style="height:100%;width:{{ $pct }}%;background:{{ $pct >= 90 ? 'var(--sc-red)' : ($pct >= 70 ? 'var(--sc-amber)' : 'var(--sc-teal)') }};border-radius:4px;transition:.3s"></div>
                    </div>
                    <div style="font-size:10.5px;color:var(--sc-muted);margin-top:2px">{{ $ward->type_label }} &mdash; {{ $pct }}% full</div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Revenue by category --}}
        @if($chargeByCategory->isNotEmpty())
        <div class="card">
            <div class="card-header"><i class="fa-solid fa-chart-pie"></i> Revenue by Category</div>
            <div class="card-body" style="display:flex;flex-direction:column;gap:10px">
                @php $maxAmt = $chargeByCategory->first()->total; @endphp
                @foreach($chargeByCategory as $cat)
                @php $catMeta = \App\Models\IpdCharge::CATEGORIES[$cat->category] ?? ['label'=>$cat->category,'color'=>'gray']; @endphp
                <div>
                    <div style="display:flex;justify-content:space-between;margin-bottom:3px">
                        <span style="font-size:12px"><i class="fa-solid {{ $catMeta['icon'] ?? 'fa-circle' }}"></i> {{ $catMeta['label'] }}</span>
                        <span style="font-size:12px;font-weight:600">₹{{ number_format($cat->total,0) }}</span>
                    </div>
                    <div style="height:6px;background:var(--sc-border);border-radius:3px;overflow:hidden">
                        <div style="height:100%;width:{{ $maxAmt > 0 ? round(($cat->total/$maxAmt)*100) : 0 }}%;background:var(--sc-teal);border-radius:3px"></div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
