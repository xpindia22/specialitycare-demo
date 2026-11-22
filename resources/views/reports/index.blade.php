{{-- FILE: resources/views/reports/index.blade.php --}}
@extends('layouts.app')
@section('title','Reports')
@section('page-title','Reports')
@section('content')

<div class="page-header">
    <div class="page-header-left"><h1>Reports</h1><div class="breadcrumb">Hospital Management &mdash; Reports &amp; Analytics</div></div>
</div>

{{-- Filter bar --}}
<div class="card mb-4">
    <div class="card-body" style="padding:14px 20px">
        <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <div>
                <label class="form-label" style="margin-bottom:4px">From</label>
                <input type="date" name="from" class="form-control" value="{{ $fromDate }}" style="width:150px">
            </div>
            <div>
                <label class="form-label" style="margin-bottom:4px">To</label>
                <input type="date" name="to" class="form-control" value="{{ $toDate }}" style="width:150px">
            </div>
            <div>
                <label class="form-label" style="margin-bottom:4px">Ward</label>
                <select name="ward_id" class="form-control" style="width:160px">
                    <option value="">All Wards</option>
                    @foreach($wards as $w)
                    <option value="{{ $w->id }}" {{ $wardId == $w->id ? 'selected' : '' }}>{{ $w->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label" style="margin-bottom:4px">Doctor</label>
                <select name="doctor_id" class="form-control" style="width:180px">
                    <option value="">All Doctors</option>
                    @foreach($doctors as $d)
                    <option value="{{ $d->id }}" {{ $doctorId == $d->id ? 'selected' : '' }}>Dr. {{ $d->first_name }} {{ $d->last_name }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-filter"></i> Apply</button>
            <a href="{{ route('reports.index') }}" class="btn btn-outline">Reset</a>
        </form>
    </div>
</div>

{{-- Tab nav --}}
<div class="tab-nav" style="margin-bottom:20px">
    <a href="?tab=admissions&from={{ $fromDate }}&to={{ $toDate }}&ward_id={{ $wardId }}&doctor_id={{ $doctorId }}"
       class="tab-btn {{ $tab==='admissions'?'active':'' }}"><i class="fa-solid fa-bed-pulse"></i> Admissions</a>
    <a href="?tab=revenue&from={{ $fromDate }}&to={{ $toDate }}&ward_id={{ $wardId }}&doctor_id={{ $doctorId }}"
       class="tab-btn {{ $tab==='revenue'?'active':'' }}"><i class="fa-solid fa-file-invoice-dollar"></i> IPD Revenue</a>
    <a href="?tab=discharge&from={{ $fromDate }}&to={{ $toDate }}&ward_id={{ $wardId }}&doctor_id={{ $doctorId }}"
       class="tab-btn {{ $tab==='discharge'?'active':'' }}"><i class="fa-solid fa-person-walking-arrow-right"></i> Discharges</a>
    <a href="?tab=opd&from={{ $fromDate }}&to={{ $toDate }}&doctor_id={{ $doctorId }}"
       class="tab-btn {{ $tab==='opd'?'active':'' }}"><i class="fa-solid fa-stethoscope"></i> OPD</a>
    <a href="?tab=opd-billing&from={{ $fromDate }}&to={{ $toDate }}&doctor_id={{ $doctorId }}"
       class="tab-btn {{ $tab==='opd-billing'?'active':'' }}"><i class="fa-solid fa-file-invoice-dollar"></i> OPD Billing</a>
    <a href="?tab=demographics"
       class="tab-btn {{ $tab==='demographics'?'active':'' }}"><i class="fa-solid fa-chart-pie"></i> Demographics</a>
    <a href="?tab=doctor-revenue&from={{ $fromDate }}&to={{ $toDate }}"
       class="tab-btn {{ $tab==='doctor-revenue'?'active':'' }}"><i class="fa-solid fa-user-doctor"></i> Doctor Revenue</a>
    <a href="?tab=daily-collection&from={{ today()->toDateString() }}&to={{ today()->toDateString() }}"
       class="tab-btn {{ $tab==='daily-collection'?'active':'' }}"><i class="fa-solid fa-cash-register"></i> Daily Collection</a>
    <a href="?tab=lab-revenue&from={{ $fromDate }}&to={{ $toDate }}&doctor_id={{ $doctorId }}"
       class="tab-btn {{ $tab==='lab-revenue'?'active':'' }}"><i class="fa-solid fa-flask"></i> Lab Revenue</a>
    <a href="?tab=pharmacy-revenue&from={{ $fromDate }}&to={{ $toDate }}&doctor_id={{ $doctorId }}"
       class="tab-btn {{ $tab==='pharmacy-revenue'?'active':'' }}"><i class="fa-solid fa-pills"></i> Pharmacy Revenue</a>
    <a href="?tab=consultation-revenue&from={{ $fromDate }}&to={{ $toDate }}&doctor_id={{ $doctorId }}"
       class="tab-btn {{ $tab==='consultation-revenue'?'active':'' }}"><i class="fa-solid fa-user-doctor"></i> Consultation Revenue</a>
    <a href="?tab=services-revenue&from={{ $fromDate }}&to={{ $toDate }}&doctor_id={{ $doctorId }}"
       class="tab-btn {{ $tab==='services-revenue'?'active':'' }}"><i class="fa-solid fa-briefcase-medical"></i> Services Revenue</a>
</div>

@if($tab === 'admissions')
{{-- Admissions Report --}}
<div class="card">
    <div class="card-header">
        <i class="fa-solid fa-bed-pulse"></i> Admissions — {{ \Carbon\Carbon::parse($fromDate)->format('d M Y') }} to {{ \Carbon\Carbon::parse($toDate)->format('d M Y') }}
        <div class="card-actions">
            <span class="badge badge-navy">{{ $admissionsReport->count() }} records</span>
            <button onclick="window.print()" class="btn btn-sm btn-outline no-print"><i class="fa-solid fa-print"></i> Print</button>
        </div>
    </div>
    @php
        $byStatus = $admissionsReport->groupBy('status');
        $byType   = $admissionsReport->groupBy('admission_type');
    @endphp
    @if($admissionsReport->isNotEmpty())
    <div class="card-body" style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;border-bottom:1px solid var(--sc-border)">
        <div><div class="form-label">Total</div><div class="fw-700 mono" style="font-size:22px">{{ $admissionsReport->count() }}</div></div>
        <div><div class="form-label">Currently Admitted</div><div class="fw-700 mono" style="font-size:22px;color:var(--sc-teal)">{{ $byStatus->get('admitted',collect())->count() }}</div></div>
        <div><div class="form-label">Discharged</div><div class="fw-700 mono" style="font-size:22px;color:var(--sc-green)">{{ $byStatus->get('discharged',collect())->count() }}</div></div>
        <div><div class="form-label">Avg Stay</div>
            @php $avgDays = $admissionsReport->where('status','discharged')->avg('days_admitted'); @endphp
            <div class="fw-700 mono" style="font-size:22px">{{ $avgDays ? round($avgDays,1).'d' : '—' }}</div>
        </div>
    </div>
    @endif
    <div class="table-wrap">
        <table class="sc-table">
            <thead>
                <tr><th>IPD No.</th><th>Patient</th><th>Ward</th><th>Doctor</th><th>Type</th><th>Admitted</th><th>Days</th><th>Status</th><th></th></tr>
            </thead>
            <tbody>
                @forelse($admissionsReport as $adm)
                <tr>
                    <td class="mono fw-600">{{ $adm->ipd_no }}</td>
                    <td>
                        <div class="fw-600">{{ $adm->patient->full_name }}</div>
                        <div class="text-small text-muted mono">{{ $adm->patient->jhc_id }}</div>
                    </td>
                    <td>{{ $adm->ward->name }}</td>
                    <td>{{ $adm->admittingDoctor->full_name }}</td>
                    <td><span class="badge badge-navy">{{ \App\Models\Admission::ADMISSION_TYPES[$adm->admission_type] ?? $adm->admission_type }}</span></td>
                    <td>{{ $adm->admission_date->format('d M Y') }}</td>
                    <td class="text-center"><span class="badge badge-gray">{{ $adm->days_admitted }}d</span></td>
                    <td><span class="badge badge-{{ $adm->status_color }}">{{ $adm->status_label }}</span></td>
                    <td><a href="{{ route('admissions.show', $adm) }}" class="btn btn-sm btn-outline no-print">View</a></td>
                </tr>
                @empty
                <tr><td colspan="9" class="text-center text-muted" style="padding:40px">No admissions in this period.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@elseif($tab === 'revenue')
{{-- Revenue Report --}}
<div class="grid" style="grid-template-columns:300px 1fr;gap:20px;align-items:start">
    <div class="card">
        <div class="card-header"><i class="fa-solid fa-chart-pie"></i> By Category</div>
        <div class="card-body" style="display:flex;flex-direction:column;gap:12px">
            @php $grandTotal = $revenueSummary->sum('total'); @endphp
            @forelse($revenueSummary as $row)
            @php $catMeta = \App\Models\IpdCharge::CATEGORIES[$row->category] ?? ['label'=>$row->category,'color'=>'gray','icon'=>'fa-circle']; @endphp
            <div>
                <div style="display:flex;justify-content:space-between;margin-bottom:3px">
                    <span style="font-size:12.5px"><i class="fa-solid {{ $catMeta['icon'] }}"></i> {{ $catMeta['label'] }}</span>
                    <span class="fw-600 mono">₹{{ number_format($row->total,2) }}</span>
                </div>
                <div style="height:6px;background:var(--sc-border);border-radius:3px;overflow:hidden">
                    <div style="height:100%;width:{{ $grandTotal > 0 ? round(($row->total/$grandTotal)*100) : 0 }}%;background:var(--sc-teal);border-radius:3px"></div>
                </div>
                <div class="text-small text-muted" style="margin-top:1px">{{ $row->count }} charges</div>
            </div>
            @empty
            <div class="text-center text-muted" style="padding:20px">No revenue in this period.</div>
            @endforelse
            @if($revenueSummary->isNotEmpty())
            <div style="border-top:2px solid var(--sc-navy);padding-top:10px;display:flex;justify-content:space-between;font-weight:700;font-size:14px">
                <span>Grand Total</span>
                <span class="mono">₹{{ number_format($grandTotal,2) }}</span>
            </div>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <i class="fa-solid fa-list"></i> All Charges — {{ \Carbon\Carbon::parse($fromDate)->format('d M') }} to {{ \Carbon\Carbon::parse($toDate)->format('d M Y') }}
            <div class="card-actions">
                <button onclick="window.print()" class="btn btn-sm btn-outline no-print"><i class="fa-solid fa-print"></i> Print</button>
            </div>
        </div>
        <div class="table-wrap">
            <table class="sc-table">
                <thead>
                    <tr><th>Date</th><th>IPD No.</th><th>Patient</th><th>Ward</th><th>Category</th><th>Description</th><th class="text-right">Amount</th></tr>
                </thead>
                <tbody>
                    @forelse($revenueReport as $c)
                    <tr>
                        <td>{{ $c->charge_date->format('d M Y') }}</td>
                        <td class="mono">{{ $c->admission->ipd_no }}</td>
                        <td>{{ $c->admission->patient->full_name }}</td>
                        <td>{{ $c->admission->ward->name }}</td>
                        <td><span class="badge badge-{{ $c->category_color }}"><i class="fa-solid {{ $c->category_icon }}"></i> {{ $c->category_label }}</span></td>
                        <td>{{ $c->description }}</td>
                        <td class="text-right mono fw-600">₹{{ number_format($c->amount,2) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center text-muted" style="padding:40px">No charges in this period.</td></tr>
                    @endforelse
                </tbody>
                @if($revenueReport->isNotEmpty())
                <tfoot>
                    <tr>
                        <td colspan="6" class="text-right fw-700" style="padding:10px 14px;background:var(--sc-bg)">Total</td>
                        <td class="text-right fw-700 mono" style="padding:10px 14px;background:var(--sc-bg);font-size:14px">₹{{ number_format($revenueReport->sum('amount'),2) }}</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>

@elseif($tab === 'discharge')
{{-- Discharge Report --}}
<div class="card">
    <div class="card-header">
        <i class="fa-solid fa-person-walking-arrow-right"></i> Discharges — {{ \Carbon\Carbon::parse($fromDate)->format('d M Y') }} to {{ \Carbon\Carbon::parse($toDate)->format('d M Y') }}
        <div class="card-actions">
            <span class="badge badge-navy">{{ $dischargeReport->count() }} records</span>
            <button onclick="window.print()" class="btn btn-sm btn-outline no-print"><i class="fa-solid fa-print"></i> Print</button>
        </div>
    </div>
    @if($dischargeReport->isNotEmpty())
    <div class="card-body" style="display:grid;grid-template-columns:repeat(5,1fr);gap:16px;border-bottom:1px solid var(--sc-border)">
        @php $byCondition = $dischargeReport->groupBy('discharge_condition'); @endphp
        @foreach(\App\Models\Admission::DISCHARGE_CONDITIONS as $key => $label)
        <div><div class="form-label">{{ $label }}</div><div class="fw-700 mono" style="font-size:20px">{{ $byCondition->get($key,collect())->count() }}</div></div>
        @endforeach
    </div>
    @endif
    <div class="table-wrap">
        <table class="sc-table">
            <thead>
                <tr><th>IPD No.</th><th>Patient</th><th>Ward</th><th>Doctor</th><th>Admitted</th><th>Discharged</th><th>Days</th><th>Condition</th><th></th></tr>
            </thead>
            <tbody>
                @forelse($dischargeReport as $adm)
                <tr>
                    <td class="mono fw-600">{{ $adm->ipd_no }}</td>
                    <td>
                        <div class="fw-600">{{ $adm->patient->full_name }}</div>
                        <div class="text-small text-muted mono">{{ $adm->patient->jhc_id }}</div>
                    </td>
                    <td>{{ $adm->ward->name }}</td>
                    <td>{{ $adm->admittingDoctor->full_name }}</td>
                    <td>{{ $adm->admission_date->format('d M Y') }}</td>
                    <td>{{ $adm->discharge_date->format('d M Y') }}</td>
                    <td class="text-center"><span class="badge badge-gray">{{ $adm->days_admitted }}d</span></td>
                    <td><span class="badge badge-{{ $adm->status_color }}">{{ \App\Models\Admission::DISCHARGE_CONDITIONS[$adm->discharge_condition] ?? '—' }}</span></td>
                    <td><a href="{{ route('admissions.show', $adm) }}" class="btn btn-sm btn-outline no-print">View</a></td>
                </tr>
                @empty
                <tr><td colspan="9" class="text-center text-muted" style="padding:40px">No discharges in this period.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@elseif($tab === 'opd')
{{-- OPD Report --}}
@php
    $opdTotal   = $opdReport->count();
    $opdDone    = $opdReport->where('status','done')->count();
    $opdSkipped = $opdReport->where('status','skipped')->count();
    $opdWaiting = $opdReport->whereIn('status',['waiting','called','in_consultation'])->count();
    $compRate   = $opdTotal > 0 ? round(($opdDone / $opdTotal) * 100) : 0;
@endphp

<div class="grid" style="grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:20px">
    @foreach([
        ['label'=>'Total Tokens','val'=>$opdTotal,'color'=>'navy'],
        ['label'=>'Completed','val'=>$opdDone,'color'=>'green'],
        ['label'=>'Skipped','val'=>$opdSkipped,'color'=>'red'],
        ['label'=>'Completion Rate','val'=>$compRate.'%','color'=>'teal'],
    ] as $stat)
    <div class="card">
        <div class="card-body" style="padding:16px 20px">
            <div class="form-label">{{ $stat['label'] }}</div>
            <div class="fw-700 mono" style="font-size:28px;color:var(--sc-{{ $stat['color'] }})">{{ $stat['val'] }}</div>
        </div>
    </div>
    @endforeach
</div>

<div class="grid" style="grid-template-columns:1fr 320px;gap:20px;align-items:start;margin-bottom:20px">
    {{-- Daily breakdown --}}
    <div class="card">
        <div class="card-header"><i class="fa-solid fa-calendar-days"></i> Daily Breakdown</div>
        <div class="table-wrap">
            <table class="sc-table">
                <thead><tr><th>Date</th><th class="text-center">Total</th><th class="text-center">Done</th><th class="text-center">Skipped</th><th>Rate</th></tr></thead>
                <tbody>
                @forelse($opdByDay as $day)
                @php $rate = $day['total'] > 0 ? round(($day['done']/$day['total'])*100) : 0; @endphp
                <tr>
                    <td>{{ \Carbon\Carbon::parse($day['date'])->format('d M Y, D') }}</td>
                    <td class="text-center fw-600 mono">{{ $day['total'] }}</td>
                    <td class="text-center" style="color:var(--sc-green)">{{ $day['done'] }}</td>
                    <td class="text-center" style="color:var(--sc-red)">{{ $day['skipped'] }}</td>
                    <td>
                        <div style="display:flex;align-items:center;gap:8px">
                            <div style="flex:1;height:6px;background:var(--sc-border);border-radius:3px;overflow:hidden">
                                <div style="height:100%;width:{{ $rate }}%;background:var(--sc-teal);border-radius:3px"></div>
                            </div>
                            <span class="mono" style="font-size:11.5px;min-width:32px">{{ $rate }}%</span>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center text-muted" style="padding:30px">No OPD tokens in this period.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- By doctor --}}
    <div class="card">
        <div class="card-header"><i class="fa-solid fa-user-doctor"></i> By Doctor</div>
        <div class="card-body" style="display:flex;flex-direction:column;gap:12px">
            @forelse($opdByDoctor as $row)
            @php $dRate = $row['total'] > 0 ? round(($row['done']/$row['total'])*100) : 0; @endphp
            <div>
                <div style="display:flex;justify-content:space-between;margin-bottom:4px">
                    <span class="fw-600" style="font-size:13px">Dr. {{ $row['doctor']->first_name }} {{ $row['doctor']->last_name }}</span>
                    <span class="mono" style="font-size:12px">{{ $row['total'] }} tokens</span>
                </div>
                <div style="height:7px;background:var(--sc-border);border-radius:4px;overflow:hidden;margin-bottom:3px">
                    <div style="height:100%;width:{{ $dRate }}%;background:var(--sc-teal);border-radius:4px"></div>
                </div>
                <div style="display:flex;gap:10px;font-size:11px;color:var(--sc-muted)">
                    <span style="color:var(--sc-green)">{{ $row['done'] }} done</span>
                    <span style="color:var(--sc-amber)">{{ $row['waiting'] }} pending</span>
                    <span style="color:var(--sc-red)">{{ $row['skipped'] }} skipped</span>
                    <span style="margin-left:auto">{{ $dRate }}%</span>
                </div>
            </div>
            @empty
            <div class="text-center text-muted" style="padding:20px">No data.</div>
            @endforelse
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <i class="fa-solid fa-list-ol"></i> Token-wise Detail — {{ \Carbon\Carbon::parse($fromDate)->format('d M Y') }} to {{ \Carbon\Carbon::parse($toDate)->format('d M Y') }}
        <div class="card-actions">
            <span class="badge badge-navy">{{ $opdTotal }} tokens</span>
            <button onclick="window.print()" class="btn btn-sm btn-outline no-print"><i class="fa-solid fa-print"></i> Print</button>
        </div>
    </div>
    <div class="table-wrap">
        <table class="sc-table">
            <thead>
                <tr><th>Date</th><th>Token</th><th>Patient</th><th>Doctor</th><th>Purpose</th><th>Priority</th><th>Status</th><th>Done At</th></tr>
            </thead>
            <tbody>
            @forelse($opdReport as $t)
            <tr>
                <td>{{ $t->token_date->format('d M Y') }}</td>
                <td class="mono fw-600">{{ $t->display }}</td>
                <td>
                    <div class="fw-600">{{ $t->patient->full_name }}</div>
                    <div class="text-small text-muted mono">{{ $t->patient->jhc_id }}</div>
                </td>
                <td>Dr. {{ $t->doctor->first_name }} {{ $t->doctor->last_name }}</td>
                <td>{{ $t->purpose ?: '—' }}</td>
                <td>
                    @if($t->priority === 'urgent')
                    <span class="badge badge-red">Urgent</span>
                    @elseif($t->priority === 'high')
                    <span class="badge badge-amber">High</span>
                    @else
                    <span class="badge badge-gray">Normal</span>
                    @endif
                </td>
                <td>
                    @php $sc=['waiting'=>'navy','called'=>'amber','in_consultation'=>'teal','done'=>'green','skipped'=>'red']; @endphp
                    <span class="badge badge-{{ $sc[$t->status] ?? 'gray' }}">{{ ucfirst(str_replace('_',' ',$t->status)) }}</span>
                </td>
                <td class="text-small text-muted">{{ $t->completed_at ? $t->completed_at->format('h:i A') : '—' }}</td>
            </tr>
            @empty
            <tr><td colspan="8" class="text-center text-muted" style="padding:40px">No OPD tokens in this period.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

@elseif($tab === 'opd-billing')
{{-- OPD Billing Report --}}
<div class="grid" style="grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:20px">
    @foreach([
        ['label'=>'Bills Raised','val'=>$opdBillingSummary['count'],'color'=>'navy','mono'=>false],
        ['label'=>'Total Billed','val'=>'₹'.number_format($opdBillingSummary['billed'],2),'color'=>'navy','mono'=>true],
        ['label'=>'Collected','val'=>'₹'.number_format($opdBillingSummary['paid'],2),'color'=>'green','mono'=>true],
        ['label'=>'Outstanding','val'=>'₹'.number_format($opdBillingSummary['due'],2),'color'=>'red','mono'=>true],
    ] as $stat)
    <div class="card">
        <div class="card-body" style="padding:16px 20px">
            <div class="form-label">{{ $stat['label'] }}</div>
            <div class="fw-700 {{ $stat['mono']?'mono':'' }}" style="font-size:22px;color:var(--sc-{{ $stat['color'] }})">{{ $stat['val'] }}</div>
        </div>
    </div>
    @endforeach
</div>

<div class="grid" style="grid-template-columns:220px 1fr;gap:16px;margin-bottom:20px;align-items:start">
    <div class="card">
        <div class="card-header"><i class="fa-solid fa-chart-pie"></i> By Status</div>
        <div class="card-body" style="display:flex;flex-direction:column;gap:10px">
            @foreach(['paid_ct'=>['Paid','green'],'partial'=>['Partial','amber'],'unpaid'=>['Unpaid','red']] as $key=>[$label,$color])
            <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid var(--sc-border)">
                <span class="badge badge-{{ $color }}">{{ $label }}</span>
                <span class="fw-700 mono">{{ $opdBillingSummary[$key] }}</span>
            </div>
            @endforeach
            @if($opdBillingSummary['discount'] > 0)
            <div style="display:flex;justify-content:space-between;font-size:12.5px;color:var(--sc-muted)">
                <span>Discounts</span><span>₹{{ number_format($opdBillingSummary['discount'],2) }}</span>
            </div>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <i class="fa-solid fa-list"></i> OPD Bills — {{ \Carbon\Carbon::parse($fromDate)->format('d M Y') }} to {{ \Carbon\Carbon::parse($toDate)->format('d M Y') }}
            <div class="card-actions">
                <button onclick="window.print()" class="btn btn-sm btn-outline no-print"><i class="fa-solid fa-print"></i> Print</button>
            </div>
        </div>
        <div class="table-wrap">
            <table class="sc-table">
                <thead>
                    <tr><th>Bill No.</th><th>Date</th><th>Patient</th><th>Doctor</th><th class="text-right">Billed</th><th class="text-right">Paid</th><th class="text-right">Due</th><th>Status</th><th></th></tr>
                </thead>
                <tbody>
                @forelse($opdBillingReport as $bill)
                @php $sc=['Paid'=>'green','Partial'=>'amber','Unpaid'=>'red']; @endphp
                <tr>
                    <td class="mono fw-600">{{ $bill->bill_no }}</td>
                    <td>{{ $bill->bill_date->format('d M Y') }}</td>
                    <td>
                        <div class="fw-600">{{ $bill->patient->full_name }}</div>
                        <div class="text-small text-muted mono">{{ $bill->patient->jhc_id }}</div>
                    </td>
                    <td>
                        @if($bill->encounter?->doctor)
                        Dr. {{ $bill->encounter->doctor->first_name }} {{ $bill->encounter->doctor->last_name }}
                        @else —
                        @endif
                    </td>
                    <td class="text-right mono">₹{{ number_format($bill->total_amount,2) }}</td>
                    <td class="text-right mono" style="color:var(--sc-green)">₹{{ number_format($bill->paid_amount,2) }}</td>
                    <td class="text-right mono" style="color:var(--sc-red)">₹{{ number_format($bill->due_amount,2) }}</td>
                    <td><span class="badge badge-{{ $sc[$bill->status] ?? 'gray' }}">{{ $bill->status }}</span></td>
                    <td><a href="{{ route('billing.show', $bill) }}" class="btn btn-sm btn-outline no-print">View</a></td>
                </tr>
                @empty
                <tr><td colspan="9" class="text-center text-muted" style="padding:40px">No OPD bills in this period.</td></tr>
                @endforelse
                </tbody>
                @if($opdBillingReport->count())
                <tfoot>
                    <tr>
                        <td colspan="4" class="text-right fw-700" style="padding:10px 14px;background:var(--sc-bg)">Total</td>
                        <td class="text-right fw-700 mono" style="padding:10px 14px;background:var(--sc-bg)">₹{{ number_format($opdBillingReport->sum('total_amount'),2) }}</td>
                        <td class="text-right fw-700 mono" style="padding:10px 14px;background:var(--sc-bg);color:var(--sc-green)">₹{{ number_format($opdBillingReport->sum('paid_amount'),2) }}</td>
                        <td class="text-right fw-700 mono" style="padding:10px 14px;background:var(--sc-bg);color:var(--sc-red)">₹{{ number_format($opdBillingReport->sum('due_amount'),2) }}</td>
                        <td colspan="2" style="background:var(--sc-bg)"></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>

@elseif($tab === 'demographics')
{{-- Demographics Report --}}
@php
    $totalPatients = array_sum(array_column($demoGender->toArray(), 'count'));
    $months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    $maxMonth = collect($demoMonthly)->max('count') ?: 1;
    $ageOrder = ['Infant (<1)','Child (1–5)','Child (6–12)','Teen (13–17)','Young Adult (18–29)','Adult (30–44)','Middle Age (45–59)','Senior (60–74)','Elderly (75+)'];
    $ageData = $demoAgeGroups->keyBy('age_group');
    $maxAge = $demoAgeGroups->max('count') ?: 1;
@endphp

<div class="grid" style="grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:20px">
    <div class="card">
        <div class="card-body" style="padding:16px 20px">
            <div class="form-label">Total Registered Patients</div>
            <div class="fw-700 mono" style="font-size:32px;color:var(--sc-navy)">{{ number_format($totalPatients) }}</div>
        </div>
    </div>
    @foreach($demoGender as $g => $row)
    <div class="card">
        <div class="card-body" style="padding:16px 20px">
            <div class="form-label">{{ ucfirst($g) }}</div>
            <div style="display:flex;align-items:baseline;gap:10px">
                <div class="fw-700 mono" style="font-size:32px;color:var(--sc-teal)">{{ number_format($row->count) }}</div>
                <div class="text-muted" style="font-size:13px">{{ $totalPatients > 0 ? round(($row->count/$totalPatients)*100) : 0 }}%</div>
            </div>
        </div>
    </div>
    @endforeach
</div>

<div class="grid" style="grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px">
    {{-- Monthly registrations bar chart --}}
    <div class="card">
        <div class="card-header"><i class="fa-solid fa-calendar-plus"></i> New Registrations — {{ now()->year }}</div>
        <div class="card-body">
            <div style="display:flex;align-items:flex-end;gap:6px;height:140px">
                @foreach(range(1,12) as $m)
                @php $cnt = $demoMonthly->get($m)?->count ?? 0; $pct = $maxMonth > 0 ? round(($cnt/$maxMonth)*100) : 0; @endphp
                <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:3px">
                    <div style="font-size:10px;color:var(--sc-muted);font-weight:600">{{ $cnt > 0 ? $cnt : '' }}</div>
                    <div style="width:100%;background:var(--sc-teal);border-radius:3px 3px 0 0;height:{{ max(4,$pct*1.2) }}px;opacity:{{ $m <= now()->month ? 1 : 0.3 }}"></div>
                    <div style="font-size:9.5px;color:var(--sc-muted)">{{ $months[$m-1] }}</div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Age group bars --}}
    <div class="card">
        <div class="card-header"><i class="fa-solid fa-users"></i> Age Distribution</div>
        <div class="card-body" style="display:flex;flex-direction:column;gap:8px">
            @foreach($ageOrder as $grp)
            @php $row = $ageData->get($grp); $cnt = $row?->count ?? 0; $pct = $maxAge > 0 ? round(($cnt/$maxAge)*100) : 0; @endphp
            <div style="display:flex;align-items:center;gap:10px">
                <div style="width:140px;font-size:11.5px;color:var(--sc-muted);white-space:nowrap">{{ $grp }}</div>
                <div style="flex:1;height:8px;background:var(--sc-border);border-radius:4px;overflow:hidden">
                    <div style="height:100%;width:{{ $pct }}%;background:var(--sc-navy);border-radius:4px"></div>
                </div>
                <div class="mono" style="width:32px;text-align:right;font-size:12px;font-weight:600">{{ $cnt }}</div>
            </div>
            @endforeach
        </div>
    </div>
</div>

@if($demoTopDiagnoses->count())
<div class="card">
    <div class="card-header">
        <i class="fa-solid fa-diagnoses"></i> Top Diagnoses (IPD Provisional)
        <div class="card-actions">
            <span class="badge badge-navy">Top {{ $demoTopDiagnoses->count() }}</span>
        </div>
    </div>
    @php $maxDiag = $demoTopDiagnoses->max('count'); @endphp
    <div class="card-body" style="display:flex;flex-direction:column;gap:10px">
        @foreach($demoTopDiagnoses as $d)
        @php $dpct = $maxDiag > 0 ? round(($d->count/$maxDiag)*100) : 0; @endphp
        <div style="display:flex;align-items:center;gap:10px">
            <div style="flex:1;font-size:12.5px">{{ Str::limit($d->provisional_diagnosis, 60) }}</div>
            <div style="width:120px;height:7px;background:var(--sc-border);border-radius:4px;overflow:hidden">
                <div style="height:100%;width:{{ $dpct }}%;background:var(--sc-teal);border-radius:4px"></div>
            </div>
            <div class="mono fw-600" style="width:28px;text-align:right;font-size:12px">{{ $d->count }}</div>
        </div>
        @endforeach
    </div>
</div>
@endif

@elseif($tab === 'doctor-revenue')
{{-- ── DOCTOR REVENUE ── --}}
<div class="grid grid-2 mb-4">
    <div class="stat-card">
        <div class="stat-icon navy"><i class="fa-solid fa-user-doctor"></i></div>
        <div>
            <div class="stat-value">{{ $doctorRevenue->count() }}</div>
            <div class="stat-label">Doctors with Activity</div>
            <div class="stat-sub text-muted">{{ \Carbon\Carbon::parse($fromDate)->format('d M Y') }} – {{ \Carbon\Carbon::parse($toDate)->format('d M Y') }}</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fa-solid fa-indian-rupee-sign"></i></div>
        <div>
            <div class="stat-value">₹{{ number_format($doctorRevenue->sum('opd_revenue') + $doctorRevenue->sum('ipd_revenue'), 0) }}</div>
            <div class="stat-label">Combined Revenue</div>
            <div class="stat-sub text-muted">OPD + IPD charges in period</div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><i class="fa-solid fa-user-doctor"></i> Doctor-wise Revenue — {{ \Carbon\Carbon::parse($fromDate)->format('d M Y') }} to {{ \Carbon\Carbon::parse($toDate)->format('d M Y') }}</div>
    <div class="table-wrap">
        <table class="sc-table">
            <thead>
                <tr>
                    <th>Doctor</th>
                    <th>Specialization</th>
                    <th class="text-right">OPD Consultations</th>
                    <th class="text-right">OPD Billing</th>
                    <th class="text-right">IPD Admissions</th>
                    <th class="text-right">IPD Charges</th>
                    <th class="text-right">Total Revenue</th>
                </tr>
            </thead>
            <tbody>
            @forelse($doctorRevenue as $dr)
            <tr>
                <td class="fw-600">{{ $dr->doctor_name }}</td>
                <td class="text-muted">{{ $dr->specialization ?: '—' }}</td>
                <td class="text-right">{{ $dr->opd_count }}</td>
                <td class="text-right">₹{{ number_format($dr->opd_revenue, 0) }}</td>
                <td class="text-right">{{ $dr->ipd_count }}</td>
                <td class="text-right">₹{{ number_format($dr->ipd_revenue, 0) }}</td>
                <td class="text-right fw-700">₹{{ number_format($dr->opd_revenue + $dr->ipd_revenue, 0) }}</td>
            </tr>
            @empty
            <tr><td colspan="7" class="text-center text-muted" style="padding:30px">No data for this period.</td></tr>
            @endforelse
            </tbody>
            @if($doctorRevenue->count())
            <tfoot>
                <tr style="background:var(--sc-bg)">
                    <td colspan="3" class="fw-700" style="padding:10px 14px">Total</td>
                    <td class="text-right fw-700" style="padding:10px 14px">₹{{ number_format($doctorRevenue->sum('opd_revenue'), 0) }}</td>
                    <td class="text-right fw-700" style="padding:10px 14px">{{ $doctorRevenue->sum('ipd_count') }}</td>
                    <td class="text-right fw-700" style="padding:10px 14px">₹{{ number_format($doctorRevenue->sum('ipd_revenue'), 0) }}</td>
                    <td class="text-right fw-700" style="padding:10px 14px">₹{{ number_format($doctorRevenue->sum('opd_revenue') + $doctorRevenue->sum('ipd_revenue'), 0) }}</td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>
@endif

{{-- ── DAILY COLLECTION TAB ── --}}
@if($tab === 'daily-collection')
@php $dc = $dailyCollectionData; @endphp
<div class="card mb-4">
    <div class="card-body" style="padding:12px 20px">
        <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end">
            <input type="hidden" name="tab" value="daily-collection">
            <div>
                <label class="form-label" style="margin-bottom:4px">From</label>
                <input type="date" name="from" class="form-control" value="{{ $dc['from'] ?? today()->toDateString() }}" style="width:150px">
            </div>
            <div>
                <label class="form-label" style="margin-bottom:4px">To</label>
                <input type="date" name="to" class="form-control" value="{{ $dc['to'] ?? today()->toDateString() }}" style="width:150px">
            </div>
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-filter"></i> Apply</button>
        </form>
    </div>
</div>

<div class="grid grid-3 mb-4">
    <div class="stat-card">
        <div class="stat-icon teal"><i class="fa-solid fa-file-invoice-dollar"></i></div>
        <div><div class="stat-value">₹{{ number_format($dc['opd_total'] ?? 0, 0) }}</div><div class="stat-label">OPD Collections</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fa-solid fa-money-bill-transfer"></i></div>
        <div><div class="stat-value">₹{{ number_format($dc['deposits_total'] ?? 0, 0) }}</div><div class="stat-label">IPD Deposits</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon amber"><i class="fa-solid fa-cash-register"></i></div>
        <div><div class="stat-value">₹{{ number_format($dc['grand_total'] ?? 0, 0) }}</div><div class="stat-label">Grand Total</div></div>
    </div>
</div>

<div class="grid" style="grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px">
    <div class="card">
        <div class="card-header"><i class="fa-solid fa-file-invoice-dollar"></i> OPD by Payment Method</div>
        <div class="table-wrap">
            <table class="sc-table">
                <thead><tr><th>Method</th><th class="text-right">Count</th><th class="text-right">Total</th></tr></thead>
                <tbody>
                @forelse($dc['opd_bills'] ?? [] as $row)
                <tr>
                    <td><span class="badge badge-teal">{{ strtoupper($row->payment_method ?: 'N/A') }}</span></td>
                    <td class="text-right">{{ $row->count }}</td>
                    <td class="text-right mono fw-600">₹{{ number_format($row->total, 2) }}</td>
                </tr>
                @empty
                <tr><td colspan="3" class="text-center text-muted" style="padding:20px">No OPD collections.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><i class="fa-solid fa-money-bill-transfer"></i> IPD Deposits by Method</div>
        <div class="table-wrap">
            <table class="sc-table">
                <thead><tr><th>Method</th><th class="text-right">Count</th><th class="text-right">Total</th></tr></thead>
                <tbody>
                @forelse($dc['deposits'] ?? [] as $row)
                <tr>
                    <td><span class="badge badge-green">{{ strtoupper($row->payment_method) }}</span></td>
                    <td class="text-right">{{ $row->count }}</td>
                    <td class="text-right mono fw-600">₹{{ number_format($row->total, 2) }}</td>
                </tr>
                @empty
                <tr><td colspan="3" class="text-center text-muted" style="padding:20px">No IPD deposits.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header"><i class="fa-solid fa-receipt"></i> OPD Bill Payments</div>
    <div class="table-wrap">
        <table class="sc-table">
            <thead><tr><th>Bill No.</th><th>Patient</th><th>Doctor</th><th>Amount</th><th>Method</th><th>Status</th><th>Time</th></tr></thead>
            <tbody>
            @forelse($dc['daily_bills'] ?? [] as $bill)
            <tr>
                <td class="mono fw-600">{{ $bill->bill_no }}</td>
                <td>{{ $bill->patient->full_name }}<div class="text-small text-muted">{{ $bill->patient->jhc_id }}</div></td>
                <td class="text-small">{{ $bill->encounter?->doctor ? 'Dr. '.$bill->encounter->doctor->first_name.' '.$bill->encounter->doctor->last_name : '—' }}</td>
                <td class="mono fw-600">₹{{ number_format($bill->paid_amount, 2) }}</td>
                <td><span class="badge badge-teal">{{ strtoupper($bill->payment_method ?: 'N/A') }}</span></td>
                <td>
                    @php $bs=['Paid'=>'green','Partial'=>'amber','Unpaid'=>'red','Refunded'=>'navy']; @endphp
                    <span class="badge badge-{{ $bs[$bill->status] ?? 'gray' }}">{{ $bill->status }}</span>
                </td>
                <td class="text-small">{{ $bill->created_at->format('h:i A') }}</td>
            </tr>
            @empty
            <tr><td colspan="7" class="text-center text-muted" style="padding:30px">No OPD bills for this period.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header"><i class="fa-solid fa-hospital"></i> IPD Deposits</div>
    <div class="table-wrap">
        <table class="sc-table">
            <thead><tr><th>Patient</th><th>IPD No.</th><th>Amount</th><th>Method</th><th>Ref</th><th>Time</th></tr></thead>
            <tbody>
            @forelse($dc['daily_deposits'] ?? [] as $dep)
            <tr>
                <td class="fw-600">{{ $dep->admission->patient->full_name ?? '—' }}</td>
                <td class="mono">{{ $dep->admission->ipd_no ?? '—' }}</td>
                <td class="mono fw-600" style="color:var(--sc-green)">₹{{ number_format($dep->amount, 2) }}</td>
                <td><span class="badge badge-green">{{ strtoupper($dep->payment_method) }}</span></td>
                <td class="text-small text-muted">{{ $dep->transaction_ref ?: '—' }}</td>
                <td class="text-small">{{ $dep->created_at->format('h:i A') }}</td>
            </tr>
            @empty
            <tr><td colspan="6" class="text-center text-muted" style="padding:30px">No deposits for this period.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endif


@php $rangeLabel = \Carbon\Carbon::parse($fromDate)->format('d M Y') . ' to ' . \Carbon\Carbon::parse($toDate)->format('d M Y'); @endphp

@if($tab === 'lab-revenue')
{{-- Lab Revenue: orders in range (panels included, cancelled excluded) --}}
@php $b = $breakdown; $m = $b['revenue'] > 0 ? round($b['profit'] / $b['revenue'] * 100) : 0; @endphp
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin-bottom:20px">
    <div class="stat-card"><div class="stat-icon navy"><i class="fa-solid fa-vials"></i></div><div><div class="stat-value">{{ $b['rows']->count() }}</div><div class="stat-label">Lab Orders</div></div></div>
    <div class="stat-card"><div class="stat-icon amber"><i class="fa-solid fa-flask"></i></div><div><div class="stat-value">₹{{ number_format($b['revenue'],0) }}</div><div class="stat-label">Lab Revenue</div></div></div>
    <div class="stat-card"><div class="stat-icon green"><i class="fa-solid fa-sack-dollar"></i></div><div><div class="stat-value" style="color:var(--sc-green)">₹{{ number_format($b['profit'],0) }}</div><div class="stat-label">Lab Profit</div><div class="stat-sub text-muted">{{ $m }}% margin</div></div></div>
</div>
<div class="card">
    <div class="card-header">
        <i class="fa-solid fa-flask"></i> Lab Revenue — {{ $rangeLabel }}
        <div class="card-actions"><button onclick="window.print()" class="btn btn-sm btn-outline no-print"><i class="fa-solid fa-print"></i> Print</button></div>
    </div>
    <div class="table-wrap">
        <table class="sc-table">
            <thead><tr><th>Date</th><th>Order No.</th><th>Patient</th><th>Panels / Tests</th><th>Status</th><th class="text-right">Amount</th><th class="text-right">Profit</th></tr></thead>
            <tbody>
            @forelse($b['rows'] as $o)
            <tr style="cursor:pointer" onclick="location='{{ route('lab.order', $o->id) }}'">
                <td style="white-space:nowrap">{{ \Carbon\Carbon::parse($o->ordered_date)->format('d M Y') }}</td>
                <td class="mono fw-600">{{ $o->order_no }}</td>
                <td><div class="fw-600">{{ $o->patient }}</div><div class="text-small text-muted mono">{{ $o->jhc_id }}</div></td>
                <td class="text-small" style="max-width:320px">
                    @if($o->panels)<div><span class="badge badge-navy">Panel</span> {{ $o->panels }}</div>@endif
                    @if($o->tests)<div class="text-muted">{{ Str::limit($o->tests, 120) }}</div>@endif
                </td>
                <td><span class="badge badge-{{ $o->status === 'Completed' ? 'green' : 'amber' }}">{{ $o->status }}</span></td>
                <td class="text-right fw-600">₹{{ number_format($o->total_amount,0) }}</td>
                <td class="text-right fw-600" style="color:var(--sc-green)">₹{{ number_format($o->profit,0) }}</td>
            </tr>
            @empty
            <tr><td colspan="7" class="text-center text-muted" style="padding:30px">No lab orders for this period.</td></tr>
            @endforelse
            </tbody>
            @if($b['rows']->count())
            <tfoot><tr style="font-weight:700;border-top:2px solid var(--sc-border)"><td colspan="5">Total</td><td class="text-right">₹{{ number_format($b['revenue'],0) }}</td><td class="text-right" style="color:var(--sc-green)">₹{{ number_format($b['profit'],0) }}</td></tr></tfoot>
            @endif
        </table>
    </div>
</div>
@endif

@if($tab === 'pharmacy-revenue')
{{-- Pharmacy Revenue: dispensed medicines in range --}}
@php $b = $breakdown; $m = $b['sale'] > 0 ? round($b['profit'] / $b['sale'] * 100) : 0; @endphp
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin-bottom:20px">
    <div class="stat-card"><div class="stat-icon navy"><i class="fa-solid fa-prescription-bottle"></i></div><div><div class="stat-value">{{ $b['rows']->count() }}</div><div class="stat-label">Dispenses</div></div></div>
    <div class="stat-card"><div class="stat-icon teal"><i class="fa-solid fa-pills"></i></div><div><div class="stat-value">₹{{ number_format($b['sale'],0) }}</div><div class="stat-label">Pharmacy Sales</div></div></div>
    <div class="stat-card"><div class="stat-icon amber"><i class="fa-solid fa-boxes-stacked"></i></div><div><div class="stat-value">₹{{ number_format($b['cost'],0) }}</div><div class="stat-label">Purchase Cost</div><div class="stat-sub text-muted">of priced dispenses</div></div></div>
    <div class="stat-card"><div class="stat-icon green"><i class="fa-solid fa-sack-dollar"></i></div><div><div class="stat-value" style="color:var(--sc-green)">₹{{ number_format($b['profit'],0) }}</div><div class="stat-label">Medicine Profit</div><div class="stat-sub text-muted">{{ $m }}% of sales</div></div></div>
</div>
@if($b['unpriced'] > 0)
<div class="alert alert-warning mb-4" style="font-size:13px">
    <i class="fa-solid fa-circle-info"></i>
    {{ $b['unpriced'] }} of {{ $b['rows']->count() }} dispenses have no selling price or no purchase cost, so their profit can't be worked out (shown as —).
</div>
@endif
<div class="card">
    <div class="card-header">
        <i class="fa-solid fa-pills"></i> Pharmacy Revenue — {{ $rangeLabel }}
        <div class="card-actions"><button onclick="window.print()" class="btn btn-sm btn-outline no-print"><i class="fa-solid fa-print"></i> Print</button></div>
    </div>
    <div class="table-wrap">
        <table class="sc-table">
            <thead><tr><th>Date</th><th>Medicine</th><th>Batch</th><th>Patient</th><th class="text-right">Qty</th><th class="text-right">Sale Value</th><th class="text-right">Cost</th><th class="text-right">Profit</th></tr></thead>
            <tbody>
            @forelse($b['rows'] as $l)
            <tr>
                <td style="white-space:nowrap">{{ \Carbon\Carbon::parse($l->created_at)->format('d M Y H:i') }}</td>
                <td class="fw-600">{{ $l->medicine ?: '—' }}</td>
                <td class="mono text-small text-muted">{{ $l->batch_number ?: '—' }}</td>
                <td>@if($l->patient)<div>{{ $l->patient }}</div><div class="text-small text-muted mono">{{ $l->jhc_id }}</div>@else<span class="text-muted">{{ $l->reference ?: '—' }}</span>@endif</td>
                <td class="text-right">{{ rtrim(rtrim(number_format($l->quantity,2),'0'),'.') }}</td>
                <td class="text-right">{{ $l->sale > 0 ? '₹'.number_format($l->sale,2) : '—' }}</td>
                <td class="text-right text-muted">{{ $l->cost !== null ? '₹'.number_format($l->cost,2) : '—' }}</td>
                <td class="text-right fw-600" style="color:var(--sc-green)">{{ $l->profit !== null ? '₹'.number_format($l->profit,2) : '—' }}</td>
            </tr>
            @empty
            <tr><td colspan="8" class="text-center text-muted" style="padding:30px">No medicines dispensed in this period.</td></tr>
            @endforelse
            </tbody>
            @if($b['rows']->count())
            <tfoot><tr style="font-weight:700;border-top:2px solid var(--sc-border)"><td colspan="5">Total</td><td class="text-right">₹{{ number_format($b['sale'],2) }}</td><td class="text-right">₹{{ number_format($b['cost'],2) }}</td><td class="text-right" style="color:var(--sc-green)">₹{{ number_format($b['profit'],2) }}</td></tr></tfoot>
            @endif
        </table>
    </div>
</div>
@endif

@if(in_array($tab, ['consultation-revenue', 'services-revenue']))
{{-- Consultation / Services revenue: OPD bill line items (paid bills) in range --}}
@php $b = $breakdown; $isCons = $tab === 'consultation-revenue'; @endphp
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin-bottom:20px">
    <div class="stat-card"><div class="stat-icon navy"><i class="fa-solid fa-receipt"></i></div><div><div class="stat-value">{{ $b['rows']->count() }}</div><div class="stat-label">{{ $isCons ? 'Consultations' : 'Billed Items' }}</div></div></div>
    <div class="stat-card"><div class="stat-icon green"><i class="fa-solid fa-indian-rupee-sign"></i></div><div><div class="stat-value" style="color:var(--sc-green)">₹{{ number_format($b['total'],0) }}</div><div class="stat-label">{{ $isCons ? 'Consultation Revenue' : 'Services Revenue' }}</div></div></div>
</div>
@if(!$isCons)
<div class="card mb-4">
    <div class="card-body" style="display:flex;gap:8px;flex-wrap:wrap;padding:12px 20px;align-items:center">
        <span class="text-small text-muted" style="margin-right:4px">Category:</span>
        <a href="?tab=services-revenue&from={{ $fromDate }}&to={{ $toDate }}&doctor_id={{ $doctorId }}" class="btn btn-sm {{ !$b['category'] ? 'btn-primary' : 'btn-outline' }}">All</a>
        @foreach($b['types'] as $type)
        <a href="?tab=services-revenue&from={{ $fromDate }}&to={{ $toDate }}&doctor_id={{ $doctorId }}&category={{ urlencode($type) }}" class="btn btn-sm {{ $b['category'] === $type ? 'btn-primary' : 'btn-outline' }}">{{ $type }}</a>
        @endforeach
    </div>
</div>
@endif
<div style="display:grid;grid-template-columns:1fr 2fr;gap:20px;align-items:start">
    <div class="card">
        <div class="card-header"><i class="fa-solid fa-chart-simple"></i> {{ $isCons ? 'By Doctor' : 'By Category' }}</div>
        <div class="table-wrap">
            <table class="sc-table">
                <thead><tr><th>{{ $isCons ? 'Doctor' : 'Category' }}</th><th class="text-right">Count</th><th class="text-right">Amount</th></tr></thead>
                <tbody>
                @forelse($isCons ? $b['byDoctor'] : $b['byType'] as $name => $g)
                <tr><td class="fw-600">{{ $isCons && $name !== '—' ? 'Dr. '.$name : $name }}</td><td class="text-right text-muted">{{ $g['count'] }}</td><td class="text-right fw-600">₹{{ number_format($g['total'],0) }}</td></tr>
                @empty
                <tr><td colspan="3" class="text-center text-muted" style="padding:20px">—</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card">
        <div class="card-header">
            <i class="fa-solid {{ $isCons ? 'fa-user-doctor' : 'fa-briefcase-medical' }}"></i> {{ $isCons ? 'Consultation' : 'Services' }} Revenue — {{ $rangeLabel }}
            <div class="card-actions"><button onclick="window.print()" class="btn btn-sm btn-outline no-print"><i class="fa-solid fa-print"></i> Print</button></div>
        </div>
        <div class="table-wrap">
            <table class="sc-table">
                <thead><tr><th>Date</th><th>Bill No.</th><th>Patient</th>@if(!$isCons)<th>Category</th>@endif<th>Item</th><th>Doctor</th><th class="text-right">Amount</th></tr></thead>
                <tbody>
                @forelse($b['rows'] as $r)
                <tr style="cursor:pointer" onclick="location='{{ route('billing.show', $r->bill_id) }}'">
                    <td style="white-space:nowrap">{{ \Carbon\Carbon::parse($r->bill_date)->format('d M Y') }}</td>
                    <td class="mono fw-600">{{ $r->bill_no }}</td>
                    <td><div class="fw-600">{{ $r->patient }}</div><div class="text-small text-muted mono">{{ $r->jhc_id }}</div></td>
                    @if(!$isCons)<td><span class="badge badge-navy">{{ $r->item_type }}</span></td>@endif
                    <td class="text-small">{{ $r->item_name }}{{ $r->quantity > 1 ? ' ×'.rtrim(rtrim(number_format($r->quantity,2),'0'),'.') : '' }}</td>
                    <td class="text-small">{{ $r->doctor ? 'Dr. '.$r->doctor : '—' }}</td>
                    <td class="text-right fw-600">₹{{ number_format($r->total,0) }}</td>
                </tr>
                @empty
                <tr><td colspan="{{ $isCons ? 6 : 7 }}" class="text-center text-muted" style="padding:30px">Nothing billed in this period.</td></tr>
                @endforelse
                </tbody>
                @if($b['rows']->count())
                <tfoot><tr style="font-weight:700;border-top:2px solid var(--sc-border)"><td colspan="{{ $isCons ? 5 : 6 }}">Total</td><td class="text-right">₹{{ number_format($b['total'],0) }}</td></tr></tfoot>
                @endif
            </table>
        </div>
    </div>
</div>
@endif

@endsection
