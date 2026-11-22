@include('partials.section-toolbar', ['links' => [
    ['url'=>route('pharmacy.dashboard'),        'icon'=>'fa-gauge',                'label'=>'Dashboard',        'bg'=>'#0369a1', 'active'=>request()->routeIs('pharmacy.dashboard')],
    ['url'=>route('pharmacy.medicines'),        'icon'=>'fa-pills',                'label'=>'Medicines',        'bg'=>'#15803d', 'active'=>request()->routeIs('pharmacy.medicines')],
    ['url'=>route('pharmacy.stock'),            'icon'=>'fa-boxes-stacked',        'label'=>'Stock',            'bg'=>'#0d9488', 'active'=>request()->routeIs('pharmacy.stock')],
    ['url'=>route('pharmacy.stock-register'),   'icon'=>'fa-book',                 'label'=>'Stock Register',   'bg'=>'#334155', 'active'=>request()->routeIs('pharmacy.stock-register')],
    ['url'=>route('pharmacy.stock-management'), 'icon'=>'fa-warehouse',            'label'=>'Stock Mgmt',       'bg'=>'#b91c1c', 'active'=>request()->routeIs('pharmacy.stock-management')],
    ['url'=>route('pharmacy.purchases.index'),  'icon'=>'fa-receipt',              'label'=>'Purchases',        'bg'=>'#6d28d9', 'active'=>request()->routeIs('pharmacy.purchases.*')],
    ['url'=>route('pharmacy.interactions.index'),'icon'=>'fa-triangle-exclamation','label'=>'Interactions',    'bg'=>'#b45309', 'active'=>request()->routeIs('pharmacy.interactions.*')],
    ['url'=>route('pharmacy.expired'),          'icon'=>'fa-hourglass-end',        'label'=>'Expired',          'bg'=>'#4b5563', 'active'=>request()->routeIs('pharmacy.expired')],
    ['url'=>route('pharmacy.purchases.create'), 'icon'=>'fa-truck-ramp-box',       'label'=>'New Stock Receipt','bg'=>'#047857', 'active'=>request()->routeIs('pharmacy.purchases.create')],
]])
