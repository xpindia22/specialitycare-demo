{{-- One speciality form tab pane: $encounter, $code, $def, $saved (?EncounterForm), $prev (?EncounterForm) --}}
@php
    use App\Services\OpdForms;
    $d = $saved?->data ?? [];
    $ro = $encounter->trashed();
    $val = fn ($k) => $d[$k] ?? '';
    $snellen = config('opd_forms.OPTH.sections.va.fields.va_unaided.options', []);
@endphp
<div id="tab-form-{{ $code }}" class="tab-pane">
<form class="spec-form" data-form="{{ $code }}" onsubmit="saveSpecForm(event)" autocomplete="off">
    <div class="card mb-3">
        <div class="card-header" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
            <span><i class="fa-solid {{ $def['icon'] }}"></i> {{ $def['title'] }}</span>
            @if($saved)<span class="text-small text-muted">· last saved {{ $saved->updated_at->format('d M H:i') }}{{ $saved->updatedBy ? ' by '.$saved->updatedBy->name : '' }}</span>@endif
            <div class="card-actions" style="margin-left:auto;display:flex;gap:6px;flex-wrap:wrap">
                @if($prev && !$ro)
                <button type="button" class="btn btn-sm btn-outline" onclick="copySpecForm(this, {{ json_encode($prev->data) }})" title="Fill empty boxes from the visit of {{ $prev->encounter?->encounter_date?->format('d M Y') }}">
                    <i class="fa-solid fa-clone"></i> Copy last visit ({{ $prev->encounter?->encounter_date?->format('d M Y') }})
                </button>
                @endif
                @if($saved)
                    @if($code === 'OPTH')
                    <a class="btn btn-sm btn-outline" target="_blank" href="{{ route('encounters.forms.print', [$encounter, $code]) }}?glasses=1"><i class="fa-solid fa-glasses"></i> {{ $def['print_label'] }}</a>
                    @endif
                    <a class="btn btn-sm btn-outline" target="_blank" href="{{ route('encounters.forms.print', [$encounter, $code]) }}"><i class="fa-solid fa-print"></i> Print {{ $def['short'] }} findings</a>
                @endif
                @unless($ro)
                <button type="submit" class="btn btn-sm btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save {{ $def['short'] }}</button>
                @endunless
            </div>
        </div>
    </div>

    <div class="spec-grid">
    @foreach($def['sections'] as $sKey => $sec)
        @php
            $eyeFields = array_filter($sec['fields'], fn ($f) => !empty($f['eyes']));
            $plainFields = array_filter($sec['fields'], fn ($f) => empty($f['eyes']) && $f['type'] !== 'refraction');
            $refFields = array_filter($sec['fields'], fn ($f) => $f['type'] === 'refraction');
            $wide = $refFields || count($eyeFields) > 3;
        @endphp
        <div class="card spec-card {{ $wide ? 'spec-wide' : '' }}">
            <div class="card-header"><i class="fa-solid {{ $sec['icon'] }}"></i> {{ $sec['title'] }}</div>
            <div class="card-body" style="padding:12px 14px">

            @foreach($refFields as $key => $f)
                @php $parts = OpdForms::REFRACTION_PARTS; if (empty($f['add'])) unset($parts['add']); @endphp
                <div style="display:flex;align-items:center;gap:8px;margin:2px 0 4px">
                    <b style="font-size:12.5px">{{ $f['label'] }}</b>
                    @if(!empty($f['copy_from']) && !$ro)
                    <button type="button" class="btn btn-sm btn-outline" style="padding:2px 8px;font-size:11px" onclick="copyRefraction(this.form, '{{ $f['copy_from'] }}', '{{ $key }}')"><i class="fa-solid fa-arrow-down"></i> from subjective</button>
                    @endif
                </div>
                <table class="spec-table">
                    <tr><th></th>@foreach($parts as $p => $pl)<th>{{ $pl }}</th>@endforeach</tr>
                    @foreach(OpdForms::EYES as $eye => $eyeLabel)
                    <tr>
                        <td class="spec-eye">{{ $eye === 'r' ? 'R' : 'L' }}</td>
                        @foreach($parts as $p => $pl)
                            @php $k = "{$key}_{$eye}_{$p}"; @endphp
                            <td>
                            @if($p === 'va')
                                <select name="f[{{ $k }}]" class="form-control spec-in" @disabled($ro)><option value=""></option>@foreach($snellen as $o)<option @selected($val($k) === $o)>{{ $o }}</option>@endforeach</select>
                            @elseif($p === 'axis')
                                <input type="number" name="f[{{ $k }}]" value="{{ $val($k) }}" min="0" max="180" step="1" class="form-control spec-in" placeholder="°" @disabled($ro)>
                            @else
                                <input type="text" inputmode="decimal" name="f[{{ $k }}]" value="{{ $val($k) }}" class="form-control spec-in spec-dioptre" placeholder="{{ $p === 'add' ? '+0.00' : '±0.00' }}" data-part="{{ $p }}" @disabled($ro)>
                            @endif
                            </td>
                        @endforeach
                    </tr>
                    @endforeach
                </table>
            @endforeach

            @if($eyeFields)
                <table class="spec-table">
                    <tr><th></th><th>Right (OD)</th><th>Left (OS)</th></tr>
                    @foreach($eyeFields as $key => $f)
                    <tr>
                        <td class="spec-label">{{ $f['label'] }}@if(!empty($f['unit']))<span class="text-muted"> ({{ $f['unit'] }})</span>@endif</td>
                        @foreach(['r', 'l'] as $eye)
                        <td>@include('encounters._speciality-field', ['k' => "{$key}_{$eye}", 'f' => $f, 'v' => $val("{$key}_{$eye}"), 'bare' => true])</td>
                        @endforeach
                    </tr>
                    @endforeach
                </table>
            @endif

            @if($plainFields)
                <div class="spec-fields">
                @foreach($plainFields as $key => $f)
                    <div class="form-group {{ in_array($f['type'], ['textarea', 'checks']) ? 'spec-full' : '' }}" style="margin-bottom:8px">
                        <label class="form-label" style="font-size:11.5px">{{ $f['label'] }}@if(!empty($f['unit']))<span class="text-muted"> ({{ $f['unit'] }})</span>@endif</label>
                        @include('encounters._speciality-field', ['k' => $key, 'f' => $f, 'v' => $d[$key] ?? ($f['type'] === 'checks' ? [] : ''), 'bare' => false])
                        @if(!empty($f['hint']))<div class="text-small text-muted" style="font-size:10.5px">{{ $f['hint'] }}</div>@endif
                    </div>
                @endforeach
                </div>
            @endif
            </div>
        </div>
    @endforeach
    </div>

    @unless($ro)
    <div style="display:flex;justify-content:flex-end;margin-top:4px">
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save {{ $def['short'] }}</button>
    </div>
    @endunless
</form>
</div>
