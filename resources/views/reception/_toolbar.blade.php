@php
    $recLinks = [
        ['url'=>route('patients.index'),       'icon'=>'fa-hospital-user',  'label'=>'Patients',        'bg'=>'#0369a1', 'active'=>request()->routeIs('patients.index')],
        ['url'=>route('opd.index'),            'icon'=>'fa-list-ol',        'label'=>'Reception Queue', 'bg'=>'#15803d', 'active'=>request()->routeIs('opd.index')],
        ['url'=>route('appointments.index'),   'icon'=>'fa-calendar-check', 'label'=>'Appointments',    'bg'=>'#6d28d9', 'active'=>request()->routeIs('appointments.index')],
        ['url'=>route('appointments.calendar'),'icon'=>'fa-calendar-days',  'label'=>'Calendar',        'bg'=>'#334155', 'active'=>request()->routeIs('appointments.calendar')],
        ['url'=>route('patients.create'),      'icon'=>'fa-user-plus',      'label'=>'New Patient',     'bg'=>'#047857', 'active'=>request()->routeIs('patients.create')],
        ['url'=>route('appointments.create'),  'icon'=>'fa-calendar-plus',  'label'=>'New Appointment', 'bg'=>'#b45309', 'active'=>request()->routeIs('appointments.create')],
    ];
    // Per-page actions appended into the same row.
    $recLinks = array_merge($recLinks, $extra ?? []);
@endphp
@include('partials.section-toolbar', ['links' => $recLinks])
