@extends('owner._layout')
@section('title','Heartbeats')
@section('content')

@php
    $totalLicenses = $rows->count();
    $suspect = $rows->where('distinct_installs', '>', 1)->count();
@endphp

<div class="stat-row">
    <div class="stat">
        <div class="lbl">Window</div>
        <div class="val" style="font-size:15px">{{ $since->format('d M') }} → today</div>
        <div class="sub">Last 14 days</div>
    </div>
    <div class="stat green">
        <div class="lbl">Active Licenses</div>
        <div class="val">{{ $totalLicenses }}</div>
        <div class="sub">Sending heartbeats</div>
    </div>
    <div class="stat {{ $suspect ? 'red' : 'green' }}">
        <div class="lbl">Suspected Clones</div>
        <div class="val">{{ $suspect }}</div>
        <div class="sub">License with &gt;1 install_id</div>
    </div>
    <div class="stat">
        <div class="lbl">Total Heartbeats</div>
        <div class="val">{{ $rows->sum('total') }}</div>
        <div class="sub">In window</div>
    </div>
</div>

@if($suspect > 0)
<div class="alert alert-danger">
    <i class="fa-solid fa-shield-halved"></i>
    <div>
        <strong>{{ $suspect }} {{ \Illuminate\Support\Str::plural('license', $suspect) }} flagged.</strong>
        Multiple distinct install_ids OR hosts have heartbeated for the same license — strong evidence the license file has been shared or the install was cloned. Review below and consider revoking.
    </div>
</div>
@endif

<div class="card">
    <div class="card-h"><i class="fa-solid fa-heart-pulse"></i> Per-License Heartbeats</div>
    @if($rows->isEmpty())
        <div class="empty"><i class="fa-solid fa-satellite-dish"></i>No heartbeats yet. Either no buyer install is online, or none have MODULES_HEARTBEAT_URL configured.</div>
    @else
    <table>
        <thead><tr>
            <th>License</th><th>Buyer</th>
            <th class="text-right">Total</th>
            <th class="text-right">Distinct installs</th>
            <th class="text-right">Distinct hosts</th>
            <th>Last seen</th>
            <th></th>
        </tr></thead>
        <tbody>
            @foreach($rows as $r)
                @php $lic = $licenses[$r->issued_license_id] ?? null; @endphp
                @if(!$lic) @continue @endif
                <tr style="{{ $r->distinct_installs > 1 ? 'background:rgba(232,76,76,.05)' : '' }}">
                    <td>
                        <div class="fw-600"><a href="{{ route('owner.licenses.show', $lic) }}">#{{ $lic->id }}</a></div>
                        <div class="text-small text-muted">fp: <span class="mono">{{ $lic->public_key_fingerprint }}</span></div>
                    </td>
                    <td>
                        <a href="{{ route('owner.buyers.show', $lic->buyer) }}">{{ $lic->buyer->name }}</a>
                        @if($lic->revoked_at)<div><span class="badge red">Revoked</span></div>@endif
                    </td>
                    <td class="text-right">{{ $r->total }}</td>
                    <td class="text-right">
                        @if($r->distinct_installs > 1)
                            <span class="badge red">{{ $r->distinct_installs }}</span>
                        @else
                            <span class="badge green">1</span>
                        @endif
                    </td>
                    <td class="text-right">{{ $r->distinct_hosts }}</td>
                    <td><div>{{ \Carbon\Carbon::parse($r->last_seen)->diffForHumans() }}</div><div class="text-small text-muted">{{ \Carbon\Carbon::parse($r->last_seen)->format('d M H:i') }}</div></td>
                    <td class="text-right">
                        @if(!$lic->revoked_at)
                        <form method="POST" action="{{ route('owner.licenses.revoke', $lic) }}" style="margin:0" onsubmit="return confirm('Revoke this license?')">
                            @csrf
                            <input type="hidden" name="revoke_reason" value="Cloning detected via heartbeats">
                            <button class="btn btn-sm btn-danger"><i class="fa-solid fa-ban"></i> Revoke</button>
                        </form>
                        @endif
                    </td>
                </tr>
                @if($r->distinct_installs > 1 && isset($perInstall[$r->issued_license_id]))
                <tr style="background:#fff8f8"><td colspan="7" style="padding-left:30px">
                    <div class="text-small fw-600" style="color:var(--red);margin-bottom:6px"><i class="fa-solid fa-arrow-turn-up" style="transform:rotate(90deg)"></i> Distinct installs reporting under this license:</div>
                    <table style="background:#fff;font-size:12px">
                        <thead><tr><th>install_id</th><th>host</th><th>ip</th><th>hits</th><th>last seen</th></tr></thead>
                        <tbody>
                            @foreach($perInstall[$r->issued_license_id] as $h)
                            <tr>
                                <td><span class="mono" style="font-size:11px">{{ $h->install_id }}</span></td>
                                <td>{{ $h->host ?: '—' }}</td>
                                <td><span class="mono">{{ $h->ip ?: '—' }}</span></td>
                                <td>{{ $h->hits }}</td>
                                <td>{{ \Carbon\Carbon::parse($h->last_seen)->format('d M H:i') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </td></tr>
                @endif
            @endforeach
        </tbody>
    </table>
    @endif
</div>

@endsection
