{{-- FILE: resources/views/triage/show.blade.php --}}
@extends('layouts.app')
@section('title','Triage Record')
@section('page-title','Triage Record')
@section('content')

<div class="page-header">
    <div class="page-header-left"><h1>Triage — {{ $token->display }}</h1></div>
    <div class="d-flex gap-2">
        <a href="{{ route('triage.create', $token) }}" class="btn btn-outline"><i class="fa-solid fa-rotate me-1"></i>Re-Triage</a>
        <a href="{{ route('triage.index') }}" class="btn btn-outline">Back</a>
    </div>
</div>

@php $tr = $token->triage; @endphp

<div style="max-width:760px">

    {{-- Patient + Level --}}
    <div class="card mb-3">
        <div class="card-body" style="padding:20px">
            <div class="d-flex align-items-center gap-16" style="gap:20px">
                <div style="text-align:center;min-width:80px">
                    <div style="font-size:36px;font-weight:900;color:#1d4ed8">{{ $token->display }}</div>
                    <span class="triage-pill triage-{{ $tr->triage_level }}" style="font-size:13px;padding:4px 14px">
                        {{ strtoupper($tr->triage_level) }}
                    </span>
                </div>
                <div>
                    <div style="font-size:18px;font-weight:700">{{ $token->patient->first_name }} {{ $token->patient->last_name }}</div>
                    <div style="font-size:12px;color:#666;margin-top:3px">
                        {{ $token->patient->jhc_id }} &nbsp;·&nbsp; {{ $token->patient->gender }}, {{ $token->patient->age }} yrs
                        @if($token->doctor) &nbsp;·&nbsp; Dr. {{ $token->doctor->first_name }} {{ $token->doctor->last_name }} @endif
                    </div>
                    @if($tr->chief_complaint)
                    <div style="font-size:13px;margin-top:8px;color:#444;font-style:italic">
                        <i class="fa-solid fa-comment-medical me-1" style="color:#888"></i>{{ $tr->chief_complaint }}
                    </div>
                    @endif
                </div>
                <div style="margin-left:auto;font-size:11px;color:#888;text-align:right">
                    Assessed by: <strong>{{ $tr->assessedBy?->name ?? '—' }}</strong><br>
                    {{ $tr->created_at->format('d M Y, h:i A') }}
                </div>
            </div>
        </div>
    </div>

    {{-- Vitals --}}
    <div class="card mb-3">
        <div class="card-header"><strong><i class="fa-solid fa-heart-pulse me-1"></i>Vitals</strong></div>
        <div class="card-body">
            <div class="vitals-display-grid">
                <div class="vd-item"><div class="vd-val">{{ $tr->bp }}</div><div class="vd-lbl">Blood Pressure</div></div>
                <div class="vd-item"><div class="vd-val">{{ $tr->pulse ? $tr->pulse.' bpm' : '—' }}</div><div class="vd-lbl">Pulse</div></div>
                <div class="vd-item"><div class="vd-val">{{ $tr->temperature ? $tr->temperature.'°F' : '—' }}</div><div class="vd-lbl">Temperature</div></div>
                <div class="vd-item"><div class="vd-val">{{ $tr->spo2 ? $tr->spo2.'%' : '—' }}</div><div class="vd-lbl">SpO2</div></div>
                <div class="vd-item"><div class="vd-val">{{ $tr->weight ? $tr->weight.' kg' : '—' }}</div><div class="vd-lbl">Weight</div></div>
                <div class="vd-item"><div class="vd-val">{{ $tr->height ? $tr->height.' cm' : '—' }}</div><div class="vd-lbl">Height</div></div>
                <div class="vd-item"><div class="vd-val">{{ $tr->bmi ?? '—' }}</div><div class="vd-lbl">BMI</div></div>
                <div class="vd-item"><div class="vd-val">{{ $tr->blood_glucose ?? '—' }}</div><div class="vd-lbl">Blood Glucose</div></div>
            </div>
        </div>
    </div>

    @if($tr->nurse_notes)
    <div class="card">
        <div class="card-header"><strong>Nurse Notes</strong></div>
        <div class="card-body" style="white-space:pre-wrap;font-size:13px">{{ $tr->nurse_notes }}</div>
    </div>
    @endif

</div>

@push('styles')
<style>
.card-header { background:#f8f9fa; padding:10px 16px; border-bottom:1px solid #e0e0e0; }
.card-body   { padding:16px; }
.triage-pill { display:inline-block; padding:2px 10px; border-radius:20px; font-size:11px; font-weight:700; letter-spacing:.5px; }
.triage-green  { background:#dcfce7; color:#14532d; }
.triage-yellow { background:#fef9c3; color:#713f12; }
.triage-red    { background:#fee2e2; color:#7f1d1d; }
.vitals-display-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; }
@media(max-width:600px){ .vitals-display-grid { grid-template-columns:repeat(2,1fr); } }
.vd-item { text-align:center; background:#f8f9fa; border-radius:6px; padding:12px 8px; }
.vd-val  { font-size:17px; font-weight:700; color:#1d4ed8; }
.vd-lbl  { font-size:10.5px; color:#666; margin-top:3px; }
</style>
@endpush
@endsection
