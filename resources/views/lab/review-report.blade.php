{{-- FILE: resources/views/lab/review-report.blade.php --}}
@extends('layouts.app')
@section('title','Review Lab Report')
@section('page-title','Review Lab Report')
@section('content')
<div class="page-header">
    <div class="page-header-left">
        <h1>Review Lab Report</h1>
        <div class="breadcrumb"><a href="{{ route('lab.index') }}">Laboratory</a> &rsaquo; Review &amp; Confirm</div>
    </div>
    <a href="{{ route('lab.report.upload') }}" class="btn btn-outline"><i class="fa-solid fa-arrow-left"></i> Cancel</a>
</div>

@if($errors->any())
<div class="alert" style="background:#fef2f2;border:1px solid #fecaca;color:#991b1b;border-radius:8px;padding:10px 16px;margin-bottom:16px">
    {{ $errors->first() }}
</div>
@endif

@if(($parsed['source'] ?? 'text') === 'ocr')
<div class="alert" style="background:#fffbeb;border:1px solid #fde68a;color:#92400e;border-radius:8px;padding:10px 16px;margin-bottom:16px">
    <i class="fa-solid fa-triangle-exclamation"></i>
    This was a scanned report read by OCR &mdash; please check every value and range carefully before confirming.
</div>
@endif

<form method="POST" action="{{ route('lab.report.store') }}">
@csrf
<input type="hidden" name="report_path" value="{{ $path }}">
@isset($pending)<input type="hidden" name="pending_id" value="{{ $pending->id }}">@endisset

<div class="card mb-3">
    <div class="card-header"><i class="fa-solid fa-user"></i> Patient &amp; Encounter</div>
    <div class="card-body">
        <div class="grid" style="grid-template-columns:1fr 220px 1fr;gap:16px;align-items:center">
            <div class="form-group" style="margin:0">
                <label class="form-label">Patient</label>
                @if($patient)
                    <div class="patient-strip" style="padding:12px">
                        <div class="ps-avatar">{{ strtoupper(substr($patient->first_name,0,1)) }}</div>
                        <div><div class="ps-name">{{ $patient->full_name }}</div><div class="ps-jhc_id">{{ $patient->jhc_id }}</div></div>
                    </div>
                    <input type="hidden" name="patient_id" value="{{ $patient->id }}">
                @else
                    <select name="patient_id" id="patient-select" class="form-control select2" required style="width:100%">
                        <option value="">Search patient by name, JHC ID, or phone...</option>
                        @foreach(($patients ?? []) as $p)
                            <option value="{{ $p->id }}">{{ $p->full_name }}{{ $p->jhc_id ? ' - '.$p->jhc_id : '' }}{{ $p->phone ? ' - '.$p->phone : '' }}</option>
                        @endforeach
                    </select>
                @endif
            </div>
            <div class="form-group" style="margin:0">
                <label class="form-label">Sample date</label>
                <input type="date" name="sample_date" value="{{ $sampleDate }}" class="form-control" required>
            </div>
            <div class="form-group" style="margin:0">
                <label class="form-label">Attach to visit</label>
                @if($patient && $encounterId)
                    <input type="hidden" name="encounter_id" value="{{ $encounterId }}">
                    <div class="text-small" style="color:#047857"><i class="fa-solid fa-link"></i> This visit (#{{ $encounterId }})</div>
                @else
                    <select name="encounter_id" id="encounter-select" class="form-control" data-preset-patient="{{ $patient->id ?? '' }}">
                        <option value="new">➕ Create a new visit (on the sample date)</option>
                    </select>
                    <div class="text-small text-muted" id="encounter-hint" style="margin-top:4px">
                        @if($patient)Loading this patient's visits…@else Select the patient first to list their visits.@endif
                    </div>
                @endif
                @if(($parsed['header']['reg_id'] ?? null))<div class="mono text-small" style="margin-top:4px">Lab Reg. ID: {{ $parsed['header']['reg_id'] }}</div>@endif
                @if(($parsed['header']['name'] ?? null))<div class="mono text-small">On report: {{ $parsed['header']['name'] }}</div>@endif
            </div>
        </div>
    </div>
</div>

@if(isset($panels) && count($panels))
<div class="card mb-3">
    <div class="card-header"><i class="fa-solid fa-layer-group"></i> Panels</div>
    <div class="card-body text-small text-muted" style="padding:10px 16px">
        <i class="fa-solid fa-circle-info"></i>
        Tests that belong to a panel in the report (LFT, RFT, CBC&hellip;) are grouped
        automatically &mdash; see the <b>Panel</b> column below. Change any row's panel,
        or set it to <b>Individual</b>, before saving. Results are always saved per test.
    </div>
</div>
@endif

<div class="card">
    <div class="card-header"><i class="fa-solid fa-flask-vial"></i> Results read from the report ({{ count($parsed['analytes']) }})</div>
    <div class="table-wrap">
        <table class="sc-table">
            <thead>
                <tr>
                    <th style="width:40px">Import</th>
                    <th>Parameter</th>
                    <th style="width:110px">Value</th>
                    <th style="width:110px">Unit</th>
                    <th style="width:140px">Normal range</th>
                    <th style="width:240px">Map to test</th>
                    <th style="width:200px">Panel</th>
                </tr>
            </thead>
            <tbody>
            @forelse($parsed['analytes'] as $i => $a)
                <tr>
                    <td style="text-align:center">
                        <input type="hidden" name="rows[{{ $i }}][include]" value="0">
                        <input type="checkbox" name="rows[{{ $i }}][include]" value="1" checked>
                    </td>
                    <td><input type="text" name="rows[{{ $i }}][parameter]" value="{{ $a['parameter'] }}" class="form-control" style="min-width:220px"></td>
                    <td><input type="text" name="rows[{{ $i }}][value]" value="{{ $a['value'] }}" class="form-control"></td>
                    <td><input type="text" name="rows[{{ $i }}][unit]" value="{{ $a['unit'] }}" class="form-control"></td>
                    <td><input type="text" name="rows[{{ $i }}][normal_range]" value="{{ $a['normal_range'] }}" class="form-control"></td>
                    <td>
                        <select name="rows[{{ $i }}][test_id]" class="form-control">
                            <option value="new" {{ ($a['test_id'] ?? 'new')==='new' ? 'selected':'' }}>➕ Create new test "{{ \Illuminate\Support\Str::limit($a['parameter'],24) }}"</option>
                            <option value="">— Skip mapping (keep as text) —</option>
                            @foreach($tests as $t)
                                <option value="{{ $t->id }}" {{ (string)($a['test_id'] ?? '')===(string)$t->id ? 'selected':'' }}>
                                    {{ $t->name }}{{ $t->short_code ? ' ('.$t->short_code.')' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </td>
                    <td>
                        @php $pv = (string)($a['panel_id'] ?? ''); $isNew = \Illuminate\Support\Str::startsWith($pv,'new:'); @endphp
                        <select name="rows[{{ $i }}][panel_id]" class="form-control">
                            <option value="">— Individual —</option>
                            @if($isNew)
                                <option value="{{ $pv }}" selected>➕ Create panel &ldquo;{{ \Illuminate\Support\Str::after($pv,'new:') }}&rdquo;</option>
                            @endif
                            @foreach(($panels ?? []) as $pl)
                                <option value="{{ $pl->id }}" {{ $pv===(string)$pl->id ? 'selected':'' }}>
                                    {{ $pl->name }}{{ $pl->short_code ? ' ('.$pl->short_code.')' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted" style="padding:24px">
                    No results could be read from this PDF. You can still go back and enter the order manually.
                </td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-body" style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap">
        <span class="text-small text-muted">
            @if(count($parsed['analytes']))
                {{ count($parsed['analytes']) }} result(s) read. Untick any you don't want, then save.
            @else
                <i class="fa-solid fa-triangle-exclamation" style="color:#b45309"></i>
                No results could be read from this PDF automatically. You can still pick a patient + panel and save, or go back and add results manually on the order.
            @endif
        </span>
        <span style="display:flex;gap:10px">
            <a href="{{ route('lab.report.upload') }}" class="btn btn-outline">Cancel</a>
            <button type="submit" id="confirm-btn" class="btn btn-primary"><i class="fa-solid fa-check"></i> Confirm &amp; Save to Encounter</button>
        </span>
    </div>
</div>
</form>
@endsection

@push('scripts')
<script>
// When a patient is chosen, list their visits so staff pick which one to attach to
// (and if they have none, "Create a new visit" is always offered + auto-created on save).
(function(){
  var es = document.getElementById('encounter-select');
  if (!es || !window.jQuery) return;
  var ps = document.getElementById('patient-select');
  var sampleDate = document.querySelector('[name="sample_date"]');
  var hint = document.getElementById('encounter-hint');

  function load(pid){
    if (!pid) {
      es.innerHTML = '<option value="new">➕ Create a new visit (on the sample date)</option>';
      if (hint) hint.textContent = 'Select the patient first to list their visits.';
      return;
    }
    if (hint) hint.textContent = 'Loading visits…';
    jQuery.getJSON('{{ url("/lab/patient") }}/' + pid + '/encounters')
      .done(function(res){
        var encs = (res && res.encounters) ? res.encounters : [];
        var html = '';
        encs.forEach(function(e){ html += '<option value="'+e.id+'">'+e.label+'</option>'; });
        html += '<option value="new">➕ Create a new visit (on the sample date)</option>';
        es.innerHTML = html;
        // Prefer a visit on the sample date, else the latest, else "new".
        var sd = sampleDate ? sampleDate.value : null;
        var match = encs.find(function(e){ return e.date === sd; });
        es.value = match ? String(match.id) : (encs.length ? String(encs[0].id) : 'new');
        if (hint) hint.textContent = encs.length
          ? 'Pick the visit to attach results to, or create a new one.'
          : 'No visits yet — a new visit will be created automatically on save.';
      })
      .fail(function(){ if (hint) hint.textContent = 'Could not load visits — a new visit will be created on save.'; });
  }

  if (ps) jQuery(ps).on('change', function(){ load(ps.value); });
  // Preset patient (came in already chosen) — load immediately.
  var preset = es.getAttribute('data-preset-patient');
  if (preset) load(preset);
})();
</script>
<style>
  @keyframes ctaPulse { 0%,100%{ box-shadow:0 0 0 0 rgba(124,58,237,.5);} 50%{ box-shadow:0 0 0 6px rgba(124,58,237,0);} }
  .cta-pulse { animation: ctaPulse 1.4s ease-in-out 3; }
</style>
@endpush
