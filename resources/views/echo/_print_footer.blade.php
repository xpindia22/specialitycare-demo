{{-- Echo report footer — repeated via <tfoot> on every printed page.
     Honors the global toggle + editable services/timings text. --}}
@if(setting('print_footer_enabled','1') !== '0')
<div class="rpt-page-footer">
    <hr class="rpt-footer-hr">
    @php
        $svcLine     = (string) setting('print_footer_services', 'Medical Consultations · Lab · Pharmacy · ECG · ECHO · Day Care');
        $timingsLine = (string) setting('print_footer_timings',  'Mon – Sat: 8:00 AM – 1:00 PM & 4:00 PM – 6:30 PM   ·   Sundays Closed');
    @endphp
    @if($svcLine !== '')
        <div class="rpt-footer-services"><strong>Services:</strong>&nbsp;{{ $svcLine }}</div>
    @endif
    @if($timingsLine !== '')
        <div class="rpt-footer-timings"><strong>Timings:</strong>&nbsp;{{ $timingsLine }}</div>
    @endif
</div>
@endif
