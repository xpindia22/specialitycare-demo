@php
    $opdLinks = [
        ['url'=>route('opd.index'),    'icon'=>'fa-users-line',  'label'=>'Reception Queue', 'bg'=>'#0369a1', 'active'=>request()->routeIs('opd.index')],
        ['url'=>route('opd.doctor'),   'icon'=>'fa-stethoscope', 'label'=>'Doctor View',     'bg'=>'#15803d', 'active'=>request()->routeIs('opd.doctor')],
        ['url'=>route('opd.board'),    'icon'=>'fa-display',     'label'=>'Queue Board',     'bg'=>'#6d28d9', 'active'=>request()->routeIs('opd.board')],
        ['url'=>route('opd.register'), 'icon'=>'fa-book-open',   'label'=>'Daily Register',  'bg'=>'#334155', 'active'=>request()->routeIs('opd.register')],
        ['url'=>route('opd.referrals.index'), 'icon'=>'fa-share-from-square', 'label'=>'Referrals', 'bg'=>'#7c3aed', 'active'=>request()->routeIs('opd.referrals.*')],
    ];
    // Per-page actions (e.g. register "Print") appended into the same row.
    $opdLinks = array_merge($opdLinks, $extra ?? []);
@endphp
@include('partials.section-toolbar', ['links' => $opdLinks])
