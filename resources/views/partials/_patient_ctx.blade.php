{{-- Sticky patient context bar. Params: $patient, $active (profile|glucose|bp|health|encounter), $compact (true = name+JHC only, no nav) --}}
<div class="sc-patient-bar">
    <span class="spb-name">
        <i class="fa-solid fa-circle-user"></i>
        <a href="{{ route('patients.show', $patient) }}">{{ $patient->full_name }}</a>
        <span class="spb-jhc">{{ $patient->jhc_id }}</span>
    </span>
    @if(empty($compact))
    <nav class="spb-links">
        <a href="{{ route('patients.show', $patient) }}"   class="{{ ($active??'') === 'profile'  ? 'spb-active' : '' }}"><i class="fa-solid fa-id-card"></i> Profile</a>
        <a href="{{ route('glucose.index', $patient) }}"   class="{{ ($active??'') === 'glucose'  ? 'spb-active' : '' }}"><i class="fa-solid fa-droplet"></i> Glucose</a>
        <a href="{{ route('bp.index', $patient) }}"        class="{{ ($active??'') === 'bp'       ? 'spb-active' : '' }}"><i class="fa-solid fa-heart-pulse"></i> BP</a>
        <a href="{{ route('health.index', $patient) }}"    class="{{ ($active??'') === 'health'   ? 'spb-active' : '' }}"><i class="fa-solid fa-heart"></i> Tracker</a>
    </nav>
    @endif
</div>
