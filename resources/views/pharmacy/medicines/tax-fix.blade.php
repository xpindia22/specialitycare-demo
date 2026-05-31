@extends('layouts.app')
@section('title','Fix Medicine HSN / GST')
@section('page-title','Fix Medicine HSN / GST')
@section('content')

<div class="page-header">
    <div class="page-header-left">
        <a href="{{ route('pharmacy.medicines') }}" class="text-small"><i class="fa-solid fa-arrow-left"></i> Medicines</a>
        <h1><i class="fa-solid fa-wand-magic-sparkles"></i> Fix HSN &amp; GST in bulk</h1>
        <div class="text-small text-muted">Set the correct HSN code + GST rate for each medicine. Use <strong>Auto-classify</strong> for a fast first pass, then review and override anything wrong before saving.</div>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
        <form method="POST" action="{{ route('pharmacy.medicines.tax-fix.auto') }}" onsubmit="return confirm('Auto-classify will set HSN + GST for matching medicines based on their category + name. Continue?')">
            @csrf
            <input type="hidden" name="scope" value="only_default">
            <button class="btn btn-outline"><i class="fa-solid fa-wand-magic-sparkles"></i> Auto-classify defaults only</button>
        </form>
        <form method="POST" action="{{ route('pharmacy.medicines.tax-fix.auto') }}" onsubmit="return confirm('This OVERWRITES every medicine. Continue?')">
            @csrf
            <input type="hidden" name="scope" value="all">
            <button class="btn btn-outline" style="color:#b54344;border-color:#b54344"><i class="fa-solid fa-wand-magic-sparkles"></i> Auto-classify ALL (overwrite)</button>
        </form>
    </div>
</div>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-error">{{ $errors->first() }}</div>@endif

<form method="GET" action="{{ route('pharmacy.medicines.tax-fix') }}" class="card" style="padding:12px;margin-bottom:14px">
    <div style="display:grid;grid-template-columns:2fr 1.5fr 1fr auto;gap:10px;align-items:end">
        <div>
            <label class="text-small">Search by name</label>
            <input name="q" value="{{ $q }}" class="form-control" placeholder="e.g., Augmentin, vitamin">
        </div>
        <div>
            <label class="text-small">Category</label>
            <select name="category" class="form-control">
                <option value="">— all —</option>
                @foreach($categories as $c)<option value="{{ $c->id }}" {{ (int)$catId===$c->id?'selected':'' }}>{{ $c->name }}</option>@endforeach
            </select>
        </div>
        <div>
            <label style="display:flex;gap:6px;align-items:center;margin-top:18px">
                <input type="checkbox" name="only_default" value="1" {{ $onlyDefault ? 'checked' : '' }}> Only show defaulted
            </label>
        </div>
        <div><button class="btn btn-outline">Filter</button></div>
    </div>
</form>

<form method="POST" action="{{ route('pharmacy.medicines.tax-fix.save') }}">
    @csrf
    <div class="card">
        <div class="table-wrap">
            <table class="sc-table" style="margin:0">
                <thead>
                    <tr>
                        <th style="min-width:280px">Medicine</th>
                        <th>Category</th>
                        <th style="width:160px">HSN Code</th>
                        <th style="width:140px">GST Rate</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($medicines as $i => $m)
                    <tr>
                        <td>
                            <input type="hidden" name="rows[{{ $i }}][id]" value="{{ $m->id }}">
                            <div class="fw-600">{{ $m->name }}</div>
                            @if($m->strength || $m->dosage_form)
                                <div class="text-small text-muted">{{ trim(($m->strength ?? '') . ' ' . ($m->dosage_form ?? '')) }}</div>
                            @endif
                        </td>
                        <td class="text-small">{{ $m->category?->name ?: '—' }}</td>
                        <td>
                            <input name="rows[{{ $i }}][hsn_code]" value="{{ $m->hsn_code }}" class="form-control mono" maxlength="16">
                        </td>
                        <td>
                            <select name="rows[{{ $i }}][gst_rate_id]" class="form-control">
                                @foreach($gstRates as $r)
                                    <option value="{{ $r->id }}" {{ (int)$m->gst_rate_id===$r->id?'selected':'' }}>{{ rtrim(rtrim(number_format((float)$r->rate,2),'0'),'.') }}%</option>
                                @endforeach
                            </select>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" style="text-align:center;padding:18px;color:var(--sc-muted)">No medicines match the filter.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div style="margin-top:14px;display:flex;gap:8px;justify-content:space-between;align-items:center">
        <div>{{ $medicines->links() }}</div>
        <button class="btn btn-primary"><i class="fa-solid fa-save"></i> Save changes on this page</button>
    </div>
</form>

<div class="text-small text-muted" style="margin-top:14px;padding:10px;background:#fafaf6;border-radius:6px">
    <i class="fa-solid fa-info-circle"></i> <strong>Common Indian pharma HSN codes:</strong>
    <code>30041000</code> Penicillins · <code>30042000</code> Other antibiotics · <code>30043100</code> Insulin
    · <code>30043200</code> Corticosteroids/hormones · <code>30045000</code> Vitamins
    · <code>30049011</code> Ayurvedic/Homoeopathic/ORS · <code>30049099</code> Other medicaments (catch-all).
    GST is <strong>12%</strong> for most retail medicines; <strong>5%</strong> for insulin, vaccines, ORS, life-saving drugs.
</div>

@endsection
