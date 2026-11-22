{{-- One input of a speciality form: $k (stored key), $f (field def), $v (value), $bare --}}
@php
    $ro = $encounter->trashed();
    $warn = (isset($f['warn_above']) ? ' data-warn-above="'.$f['warn_above'].'"' : '') . (isset($f['warn_below']) ? ' data-warn-below="'.$f['warn_below'].'"' : '');
@endphp
@switch($f['type'])
    @case('select')
        <select name="f[{{ $k }}]" class="form-control spec-in" @disabled($ro)>
            <option value=""></option>
            @foreach($f['options'] as $o)<option @selected((string) $v === (string) $o)>{{ $o }}</option>@endforeach
            @if($v !== '' && !in_array($v, $f['options']))<option selected>{{ $v }}</option>@endif
        </select>
        @break
    @case('checks')
        <div class="spec-checks">
            @foreach($f['options'] as $o)
            <label class="spec-check"><input type="checkbox" name="f[{{ $k }}][]" value="{{ $o }}" @checked(in_array($o, (array) $v)) @disabled($ro)> {{ $o }}</label>
            @endforeach
        </div>
        @break
    @case('textarea')
        <textarea name="f[{{ $k }}]" class="form-control spec-in" rows="2" placeholder="{{ $f['placeholder'] ?? '' }}" @disabled($ro)>{{ $v }}</textarea>
        @break
    @case('number')
        <input type="number" name="f[{{ $k }}]" value="{{ $v }}" class="form-control spec-in" step="{{ $f['step'] ?? 'any' }}"
               @isset($f['min']) min="{{ $f['min'] }}" @endisset @isset($f['max']) max="{{ $f['max'] }}" @endisset
               placeholder="{{ $f['placeholder'] ?? '' }}" {!! $warn !!} @disabled($ro)>
        @break
    @case('date')
        <input type="date" name="f[{{ $k }}]" value="{{ $v }}" class="form-control spec-in" @disabled($ro)>
        @break
    @default
        <input type="text" name="f[{{ $k }}]" value="{{ $v }}" class="form-control spec-in" maxlength="120" placeholder="{{ $f['placeholder'] ?? '' }}" @disabled($ro)>
@endswitch
