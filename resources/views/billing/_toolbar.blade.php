@php
    $billLinks = [
        ['url'=>route('billing.index'),  'icon'=>'fa-file-invoice-dollar', 'label'=>'Billing',      'bg'=>'#0369a1', 'active'=>request()->routeIs('billing.index')],
        ['url'=>route('billing.create'), 'icon'=>'fa-file-invoice',        'label'=>'Create Bill',  'bg'=>'#047857', 'active'=>request()->routeIs('billing.create')],
        ['url'=>route('billing.daily'),  'icon'=>'fa-calendar-day',        'label'=>'Daily Report', 'bg'=>'#6d28d9', 'active'=>request()->routeIs('billing.daily')],
        ['url'=>route('packages.index'), 'icon'=>'fa-box-open',            'label'=>'Packages',     'bg'=>'#334155', 'active'=>request()->routeIs('packages.index')],
        ['url'=>route('packages.create'),'icon'=>'fa-plus',                'label'=>'New Package',  'bg'=>'#b45309', 'active'=>request()->routeIs('packages.create','packages.edit')],
    ];
    // Per-page actions appended into the same row.
    $billLinks = array_merge($billLinks, $extra ?? []);
@endphp
@include('partials.section-toolbar', ['links' => $billLinks])
