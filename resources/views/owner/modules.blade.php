@extends('owner._layout')
@section('title','Modules')
@section('content')

{{-- License status row --}}
<div class="stat-row">
    <div class="stat {{ $status === 'active' ? 'green' : ($status === 'invalid' ? 'red' : 'amber') }}">
        <div class="lbl">License Status</div>
        <div class="val">
            @if($status === 'active')<i class="fa-solid fa-shield-check"></i> Active
            @elseif($status === 'invalid')<i class="fa-solid fa-shield-xmark"></i> Invalid
            @else<i class="fa-solid fa-shield"></i> No license
            @endif
        </div>
    </div>
    <div class="stat">
        <div class="lbl">Licensee</div>
        <div class="val" style="font-size:15px">{{ $payload['licensee'] ?? '—' }}</div>
    </div>
    <div class="stat">
        <div class="lbl">Expires</div>
        <div class="val" style="font-size:15px">
            {{ !empty($payload['expires_at']) ? \Carbon\Carbon::parse($payload['expires_at'])->format('d M Y') : (is_array($payload) ? 'Never' : '—') }}
        </div>
    </div>
    <div class="stat green">
        <div class="lbl">Modules Enabled</div>
        <div class="val">{{ count($enabled) }}<span style="font-size:14px;color:var(--muted);font-weight:400"> / {{ count($catalog) }}</span></div>
    </div>
</div>

{{-- Module catalog --}}
<div class="card">
    <div class="card-h"><i class="fa-solid fa-cubes"></i> Module Catalog
        <div class="right"><a href="{{ route('owner.licenses.create') }}" class="btn btn-sm"><i class="fa-solid fa-plus"></i> Issue License</a></div>
    </div>
    <table>
        <thead><tr><th style="width:40px"></th><th>Module</th><th>Key</th><th style="width:140px">Status</th></tr></thead>
        <tbody>
            @foreach($catalog as $key => $meta)
            <tr>
                <td style="text-align:center;color:var(--teal)"><i class="fa-solid {{ $meta['icon'] ?? 'fa-cube' }}"></i></td>
                <td>
                    <div style="font-weight:600">{{ $meta['label'] }}</div>
                    <div class="desc">{{ $meta['description'] }}</div>
                </td>
                <td><span class="mono text-muted">{{ $key }}</span></td>
                <td>
                    @if(in_array($key, $enabled))
                        <span class="badge green"><i class="fa-solid fa-check"></i> Enabled</span>
                    @else
                        <span class="badge gray"><i class="fa-solid fa-minus"></i> Disabled</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

@if($hasPriv)
<div class="card">
    <div class="card-h"><i class="fa-solid fa-bolt"></i> Quick Sign — Activate on This Server</div>
    <div class="card-b">
        <div class="alert alert-warning"><i class="fa-solid fa-triangle-exclamation"></i>
            Private key is on this server. Use this form to flip modules on/off for THIS install only —
            for buyers, use the <a href="{{ route('owner.licenses.create') }}">Issue License</a> flow which logs the buyer + payment.
        </div>
        <form method="POST" action="{{ route('owner.modules.sign') }}">
            @csrf
            <div class="grid-2">
                <div class="form-row">
                    <label>Licensee (this install's name)</label>
                    <input type="text" name="licensee" required value="{{ old('licensee', $payload['licensee'] ?? 'Local Dev') }}">
                </div>
                <div class="form-row">
                    <label>Expiry (optional)</label>
                    <input type="date" name="expires_at" min="{{ now()->addDay()->toDateString() }}" value="{{ old('expires_at', !empty($payload['expires_at']) ? \Carbon\Carbon::parse($payload['expires_at'])->toDateString() : '') }}">
                </div>
            </div>
            <div class="form-row">
                <label>Modules</label>
                <div class="grid-2">
                    @foreach($catalog as $key => $meta)
                    <label class="checkbox {{ in_array($key, old('modules', $enabled)) ? 'checked' : '' }}">
                        <input type="checkbox" name="modules[]" value="{{ $key }}" {{ in_array($key, old('modules', $enabled)) ? 'checked' : '' }} onchange="this.parentNode.classList.toggle('checked', this.checked)">
                        <i class="fa-solid {{ $meta['icon'] ?? 'fa-cube' }}" style="color:var(--teal)"></i>
                        <span style="flex:1">{{ $meta['label'] }}</span>
                    </label>
                    @endforeach
                </div>
            </div>
            <button type="submit" class="btn btn-navy"><i class="fa-solid fa-pen-nib"></i> Sign &amp; Install on This Server</button>
        </form>
    </div>
</div>
@else
<div class="card">
    <div class="card-h"><i class="fa-solid fa-upload"></i> Upload Signed License</div>
    <div class="card-b">
        <div class="alert alert-info"><i class="fa-solid fa-info-circle"></i>
            No private key on this server. Sign on your machine and upload <span class="mono">license.json</span> + <span class="mono">license.sig</span> here.
        </div>
        <form method="POST" action="{{ route('owner.modules.upload') }}" enctype="multipart/form-data">
            @csrf
            <div class="grid-2">
                <div class="form-row"><label>license.json</label><input type="file" name="license_json" accept=".json,application/json" required></div>
                <div class="form-row"><label>license.sig</label><input type="file" name="license_sig" required></div>
            </div>
            <button type="submit" class="btn"><i class="fa-solid fa-cloud-arrow-up"></i> Upload &amp; Verify</button>
        </form>
    </div>
</div>
@endif

@endsection
