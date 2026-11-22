{{-- Echo report header — repeated via <thead> on every printed page.
     Honors the global print_header_enabled toggle. --}}
@php
    preg_match_all('/\d{6,}/', (string) setting('clinic_phone'), $m);
    $phLine = implode(', ', $m[0] ?? []);
@endphp
@if(setting('print_header_enabled','1') === '0')
    {{-- header disabled in settings — render nothing --}}
@else
<div class="rpt-page-header">
    <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td width="33%" valign="top" style="padding-left:14px">
                <div class="rpt-doc-info">
                    <strong>{{ $echo->doctor->full_name }}</strong><br>
                    @if($echo->doctor->qualification)<span>{!! nl2br(e($echo->doctor->qualification)) !!}</span><br>@endif
                    @if($echo->doctor->specialization)<span>{{ $echo->doctor->specialization }}</span>@endif
                </div>
            </td>
            <td width="34%" valign="middle" align="center">
                @if(setting('clinic_logo'))
                    <img src="{{ Storage::url(setting('clinic_logo')) }}" style="height:56px;margin-bottom:3px"><br>
                @else
                    <div class="rpt-heart-icon">&#9829;</div>
                @endif
                <div class="rpt-clinic-name">{{ setting('clinic_name','James Heart Care Pvt. Ltd.') }}</div>
                @if(setting('clinic_tagline'))<div class="rpt-tagline">{{ setting('clinic_tagline') }}</div>@endif
            </td>
            <td width="33%" valign="top" align="right" style="padding-right:14px">
                <div class="rpt-doc-info" style="text-align:right">
                    <strong>{{ setting('clinic_name') }}</strong><br>
                    @if(setting('clinic_address'))<span>{!! nl2br(e(setting('clinic_address'))) !!}</span><br>@endif
                    @if($phLine)<span>Ph: {{ $phLine }}</span>@endif
                </div>
            </td>
        </tr>
    </table>
    <hr class="rpt-hr">
</div>
@endif
