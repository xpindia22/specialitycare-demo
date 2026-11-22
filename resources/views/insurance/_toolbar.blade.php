@php
    $insLinks = [
        ['url'=>route('insurance.claims.index'),  'icon'=>'fa-shield-halved',    'label'=>'Insurance / TPA', 'bg'=>'#0369a1', 'active'=>request()->routeIs('insurance.*')],
        ['url'=>route('insurers.index'),          'icon'=>'fa-building-columns',  'label'=>'Insurers & TPAs', 'bg'=>'#6d28d9', 'active'=>request()->routeIs('insurers.*')],
        ['url'=>route('insurance.claims.create'), 'icon'=>'fa-plus',              'label'=>'New Claim',       'bg'=>'#047857', 'active'=>request()->routeIs('insurance.claims.create')],
        ['url'=>route('insurers.create'),         'icon'=>'fa-plus',              'label'=>'New Insurer',     'bg'=>'#b45309', 'active'=>request()->routeIs('insurers.create')],
    ];
    $insLinks = array_merge($insLinks, $extra ?? []);
@endphp
@include('partials.section-toolbar', ['links' => $insLinks])
