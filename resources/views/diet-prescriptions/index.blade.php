{{-- FILE: resources/views/diet-prescriptions/index.blade.php --}}
@extends('layouts.app')
@section('title','Diet Prescriptions')
@section('page-title','Diet Prescriptions')
@section('content')
<div class="page-header">
    <div class="page-header-left"><h1>Diet Prescriptions</h1></div>
    <a href="{{ route('diet-prescriptions.create') }}" class="btn btn-primary"><i class="fa-solid fa-plus"></i> New Prescription</a>
</div>

<form method="GET" style="margin-bottom:12px;display:flex;align-items:center;gap:8px">
    <div style="position:relative;flex:1;max-width:420px">
        <i class="fa-solid fa-magnifying-glass" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--sc-muted);font-size:13px"></i>
        <input type="text" name="q" value="{{ request('q') }}"
               placeholder="Search by patient name, JHC ID, phone…"
               class="form-control" style="padding-left:34px" autocomplete="off">
    </div>
    <button type="submit" class="btn btn-outline btn-sm">Search</button>
    @if(request('q'))<a href="{{ route('diet-prescriptions.index') }}" class="btn btn-outline btn-sm">Clear</a>@endif
</form>

<div class="card">
    <div class="table-wrap">
        <table class="sc-table">
            <thead>
                <tr><th>Date</th><th>Patient</th><th>BMI</th><th>Calories</th><th>Conditions</th><th>By</th><th></th></tr>
            </thead>
            <tbody>
            @forelse($prescriptions as $rx)
            <tr>
                <td class="text-small">{{ $rx->prescribed_at->format('d M Y') }}</td>
                <td>
                    <div class="fw-600">{{ $rx->patient->full_name }}</div>
                    <div class="text-small text-muted mono">{{ $rx->patient->jhc_id }}</div>
                </td>
                <td>
                    <span style="font-weight:700;color:{{ \App\Models\DietPrescription::bmiColor($rx->bmi) }}">{{ $rx->bmi }}</span>
                    <div class="text-small text-muted">{{ \App\Models\DietPrescription::bmiCategory($rx->bmi) }}</div>
                </td>
                <td class="fw-600" style="color:var(--sc-teal)">{{ number_format($rx->calorie_target) }} kcal</td>
                <td>
                    @foreach($rx->conditions as $ck)
                    @php $cm = \App\Models\DietPrescription::CONDITIONS[$ck] ?? null; @endphp
                    @if($cm)<span class="badge badge-navy" style="margin:1px;font-size:10px">{{ $cm['label'] }}</span>@endif
                    @endforeach
                </td>
                <td class="text-small">{{ $rx->prescribedBy->name }}</td>
                <td><a href="{{ route('diet-prescriptions.show', $rx) }}" class="btn btn-sm btn-outline">View / Print</a></td>
            </tr>
            @empty
            <tr><td colspan="7" style="padding:0">
                @if(request('q'))
                    <x-empty-state icon="fa-magnifying-glass" title="No matches for &quot;{{ request('q') }}&quot;" message="Try a different patient name, JHC ID or phone number." />
                @else
                    <x-empty-state icon="fa-utensils" title="No diet prescriptions yet" message="Diet prescriptions are written from the encounter or patient profile."
                        action="New diet prescription" :actionUrl="route('diet-prescriptions.create')" />
                @endif
            </td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($prescriptions->hasPages())
    <div style="padding:16px">{{ $prescriptions->links() }}</div>
    @endif
</div>
@endsection
