@php
    $tplLinks = [
        ['url'=>route('charge-templates.index'),      'icon'=>'fa-layer-group',   'label'=>'Charge Templates',       'bg'=>'#0369a1', 'active'=>request()->routeIs('charge-templates.*')],
        ['url'=>route('encounter-templates.index'),   'icon'=>'fa-file-medical',  'label'=>'Encounter Templates',    'bg'=>'#15803d', 'active'=>request()->routeIs('encounter-templates.*')],
        ['url'=>route('prescription-templates.index'),'icon'=>'fa-prescription',  'label'=>'Prescription Templates', 'bg'=>'#6d28d9', 'active'=>request()->routeIs('prescription-templates.*')],
        ['url'=>route('consent-templates.index'),     'icon'=>'fa-file-signature','label'=>'Consent Templates',      'bg'=>'#334155', 'active'=>request()->routeIs('consent-templates.*')],
    ];
    $tplLinks = array_merge($tplLinks, $extra ?? []);
@endphp
@include('partials.section-toolbar', ['links' => $tplLinks])
