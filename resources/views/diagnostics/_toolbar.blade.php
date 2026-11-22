@php
    $dxLinks = [
        ['url'=>route('lab.index'),       'icon'=>'fa-flask-vial',        'label'=>'Laboratory',  'bg'=>'#0369a1', 'active'=>request()->routeIs('lab.*')],
        ['url'=>route('radiology.index'), 'icon'=>'fa-x-ray',             'label'=>'Radiology',   'bg'=>'#6d28d9', 'active'=>request()->routeIs('radiology.*')],
        ['url'=>route('ecg.index'),       'icon'=>'fa-heart-circle-bolt', 'label'=>'ECG',         'bg'=>'#b91c1c', 'active'=>request()->routeIs('ecg.*')],
        ['url'=>route('echo.index'),      'icon'=>'fa-wave-square',       'label'=>'Echo',        'bg'=>'#0d9488', 'active'=>request()->routeIs('echo.*')],
        ['url'=>route('ot.index'),        'icon'=>'fa-syringe',           'label'=>'OT Register', 'bg'=>'#334155', 'active'=>request()->routeIs('ot.*')],
    ];
    // Per-page actions appended into the same row.
    $dxLinks = array_merge($dxLinks, $extra ?? []);
@endphp
@include('partials.section-toolbar', ['links' => $dxLinks])
