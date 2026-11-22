@php use App\Models\IcuInfusion; use App\Models\IpdVitals; @endphp
@if($rows->isEmpty())
    <div class="card"><div class="card-body text-center text-muted" style="padding:40px">
        No patients in ICU. Patients appear here when admitted to a ward of type <b>ICU</b> (Wards &amp; Beds) or when an infusion is started from their ICU chart.
    </div></div>
@else
<div class="icu-grid">
@foreach($rows as $r)
    @php $a = $r['a']; $v = $r['v']; $p = $a->patient; @endphp
    <a href="{{ route('icu.chart', $a) }}" class="icu-tile lv-{{ $r['level'] }}">
        <div class="top">
            <div style="min-width:0">
                <div class="name">{{ $p->first_name }} {{ $p->last_name }} <span style="font-weight:400;font-size:12px;opacity:.7">{{ $p->date_of_birth ? $p->date_of_birth->age.'y' : '' }} {{ substr($p->gender,0,1) }}</span></div>
                <div class="dx">{{ \Illuminate\Support\Str::limit($a->provisional_diagnosis, 60) ?: $a->ipd_no }} · Day {{ $a->days_admitted }}</div>
            </div>
            <span class="bed">{{ $a->ward->name ?? '' }} {{ $a->bed ? '· '.$a->bed->bed_number : '' }}</span>
        </div>
        @if(count($r['alerts']))
        <div class="als">@foreach($r['alerts'] as [$sev, $msg])<span class="{{ $sev }}">{{ $msg }}</span>@endforeach</div>
        @endif
        <div class="vit">
            <div class="{{ $v && $v->pulse !== null && ($v->pulse > 130 || $v->pulse < 40) ? 'crit' : '' }}"><div class="l">HR</div><div class="v hr">{{ $v->pulse ?? '—' }}</div></div>
            <div class="{{ $v && $v->mean_ap !== null && $v->mean_ap < 65 ? 'crit' : '' }}"><div class="l">MAP</div><div class="v bp">{{ $v->mean_ap ?? '—' }}</div></div>
            <div class="{{ $v && $v->spo2 !== null && $v->spo2 < 90 ? 'crit' : '' }}"><div class="l">SpO₂</div><div class="v sp">{{ $v->spo2 ?? '—' }}</div></div>
            <div class="{{ $v && $v->respiration_rate !== null && ($v->respiration_rate > 30 || $v->respiration_rate < 8) ? 'crit' : '' }}"><div class="l">RR</div><div class="v rr">{{ $v->respiration_rate ?? '—' }}</div></div>
            <div><div class="l">BP</div><div class="v bp" style="font-size:15px">{{ $v && $v->bp_systolic ? $v->bp_systolic.'/'.$v->bp_diastolic : '—' }}</div></div>
            <div><div class="l">Temp °F</div><div class="v tp" style="font-size:16px">{{ $v && $v->temperature ? (float) $v->temperature : '—' }}</div></div>
            <div><div class="l">GCS</div><div class="v gc" style="font-size:16px">{{ $v && $v->gcs !== null ? $v->gcs.($v->intubated ? 'T' : '') : ($v->avpu ?? '—') }}</div></div>
            <div class="{{ $r['level'] === 'high' ? 'crit' : '' }}"><div class="l">NEWS2</div><div class="v nw" style="font-size:16px">{{ $v->news2 ?? '—' }}</div></div>
        </div>
        <div class="inf" style="opacity:.9">
            <span style="background:rgba(255,255,255,.08);color:#e2e8f0">{{ $v && $v->o2_device ? IpdVitals::O2_DEVICES[$v->o2_device] : 'O₂ —' }}{{ $v && $v->fio2 ? ' · FiO₂ '.$v->fio2 : '' }}{{ $v && $v->peep !== null ? ' · PEEP '.(float) $v->peep : '' }}</span>
            <span style="background:rgba(255,255,255,.08);color:#e2e8f0">24 h I/O {{ number_format($r['in']) }} / {{ number_format($r['out']) }} ml</span>
        </div>
        @if($r['running']->count())
        <div class="inf">
            @foreach($r['running'] as $inf)
                <span class="{{ $inf->drug && $inf->dose > $inf->drug['max'] ? 'over' : '' }}">{{ $inf->drug_name }} {{ IcuInfusion::fmt($inf->dose) }} {{ $inf->dose_unit }} · {{ IcuInfusion::fmt($inf->rate_ml_h) }} ml/h</span>
            @endforeach
        </div>
        @endif
        <div class="foot">
            <span>{{ $a->admittingDoctor ? 'Dr. '.$a->admittingDoctor->first_name.' '.$a->admittingDoctor->last_name : '' }}</span>
            <span>{{ $v ? 'obs '.$v->recorded_at->diffForHumans() : 'no obs' }}{{ $r['weight'] ? ' · '.$r['weight'].' kg' : '' }}</span>
        </div>
    </a>
@endforeach
</div>
@endif
