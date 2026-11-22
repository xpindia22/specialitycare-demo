@php
    $ipdLinks = [
        ['url'=>route('ipd.dashboard'),    'icon'=>'fa-bed-pulse',      'label'=>'IPD Dashboard',   'bg'=>'#0369a1', 'active'=>request()->routeIs('ipd.dashboard')],
        ['url'=>route('icu.board'),        'icon'=>'fa-display',        'label'=>'ICU Board',       'bg'=>'#b91c1c', 'active'=>request()->routeIs('icu.board','icu.chart'), 'icu'=>true],
        ['url'=>route('icu.calculator'),   'icon'=>'fa-calculator',     'label'=>'Drug Calculator', 'bg'=>'#be185d', 'active'=>request()->routeIs('icu.calculator'), 'icu'=>true],
        ['url'=>route('ipd.census'),       'icon'=>'fa-clipboard-list', 'label'=>'Census / Rounds', 'bg'=>'#6d28d9', 'active'=>request()->routeIs('ipd.census')],
        ['url'=>route('wards.bed-board'),  'icon'=>'fa-grip',           'label'=>'Bed Board',       'bg'=>'#15803d', 'active'=>request()->routeIs('wards.bed-board')],
        ['url'=>route('wards.index'),      'icon'=>'fa-hospital',       'label'=>'Wards & Beds',    'bg'=>'#334155', 'active'=>request()->routeIs('wards.index')],
        ['url'=>route('admissions.index'), 'icon'=>'fa-list',           'label'=>'Admissions',      'bg'=>'#4b5563', 'active'=>request()->routeIs('admissions.index')],
        ['url'=>route('admissions.create'),'icon'=>'fa-plus',           'label'=>'New Admission',   'bg'=>'#b45309', 'active'=>request()->routeIs('admissions.create')],
    ];
    // ICU pages are for clinical staff only
    if (!auth()->user()?->hasRole(['admin','doctor','nurse'])) $ipdLinks = array_values(array_filter($ipdLinks, fn ($l) => empty($l['icu'])));
    // Per-page actions (e.g. census "Print") appended into the same row.
    $ipdLinks = array_merge($ipdLinks, $extra ?? []);
@endphp
@include('partials.section-toolbar', ['links' => $ipdLinks])
