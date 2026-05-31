@extends('layouts.app')
@section('title','Modules')
@section('page-title','Modules')
@section('content')

<div class="page-header">
    <div class="page-header-left">
        <h1>Active Modules</h1>
        <div class="breadcrumb">{{ count($enabled) }} of {{ count($catalog) }} modules active on this install</div>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success">
    <i class="fa-solid fa-circle-check"></i>
    <div>{{ session('success') }}</div>
</div>
@endif
@if(session('error'))
<div class="alert alert-danger">
    <i class="fa-solid fa-circle-xmark"></i>
    <div>{{ session('error') }}</div>
</div>
@endif

@if($status !== 'active' && $status !== 'dev_override')
<div class="alert alert-warning">
    <i class="fa-solid fa-triangle-exclamation"></i>
    <div>
        @switch($status)
            @case('invalid')
                <strong>License invalid.</strong> The license file failed signature verification — either the file was edited, or the public key on this install does not match the one used to sign the license. Use the form below to install the correct files.
                @break
            @case('revoked')
                <strong>License revoked.</strong> Your software vendor has revoked this license. Modules are disabled. Contact your vendor to resolve.
                @break
            @case('stale')
                <strong>License heartbeat overdue.</strong> This install has not been able to contact the licensor for more than 7 days. Modules are disabled until a successful heartbeat is recorded. Check your server's outbound internet access, then use the "Run heartbeat now" button below.
                @break
            @default
                <strong>No license installed.</strong> No paid modules are active. Use the form below to install your license file, or contact your software vendor to obtain one.
        @endswitch
    </div>
</div>
@endif

<div class="grid grid-4 mb-4">
    <div class="stat-card">
        <div class="stat-icon {{ $status === 'active' ? 'green' : ($status === 'invalid' ? 'red' : 'amber') }}">
            <i class="fa-solid fa-shield-{{ $status === 'active' ? 'check' : ($status === 'invalid' ? 'xmark' : 'halved') }}"></i>
        </div>
        <div>
            <div class="stat-value" style="font-size:18px">
                {{ $status === 'active' ? 'Licensed' : ($status === 'invalid' ? 'Invalid' : 'No license') }}
            </div>
            <div class="stat-label">License Status</div>
            <div class="stat-sub text-muted">{{ $payload['licensee'] ?? '—' }}</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon teal"><i class="fa-solid fa-calendar-day"></i></div>
        <div>
            <div class="stat-value" style="font-size:18px">
                {{ !empty($payload['expires_at']) ? \Carbon\Carbon::parse($payload['expires_at'])->format('d M Y') : (is_array($payload) ? 'Never' : '—') }}
            </div>
            <div class="stat-label">Expires</div>
            <div class="stat-sub text-muted">
                @if(!empty($payload['expires_at']))
                    {{ \Carbon\Carbon::parse($payload['expires_at'])->diffForHumans() }}
                @endif
            </div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fa-solid fa-toggle-on"></i></div>
        <div>
            <div class="stat-value">{{ count($enabled) }}</div>
            <div class="stat-label">Active Modules</div>
            <div class="stat-sub text-muted">Available to use</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon navy"><i class="fa-solid fa-cubes"></i></div>
        <div>
            <div class="stat-value">{{ count($catalog) - count($enabled) }}</div>
            <div class="stat-label">Available to Add</div>
            <div class="stat-sub text-muted">Contact vendor to enable</div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <i class="fa-solid fa-cubes"></i> Module Catalog
    </div>
    <div class="table-responsive">
        <table class="sc-table table" style="margin-bottom:0">
            <thead>
                <tr>
                    <th style="width:40px"></th>
                    <th>Module</th>
                    <th style="width:140px">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($catalog as $key => $meta)
                <tr>
                    <td style="text-align:center;color:var(--sc-teal)"><i class="fa-solid {{ $meta['icon'] ?? 'fa-cube' }}" style="font-size:18px"></i></td>
                    <td>
                        <div class="fw-600">{{ $meta['label'] }}</div>
                        <div class="text-small text-muted">{{ $meta['description'] }}</div>
                    </td>
                    <td>
                        @if(in_array($key, $enabled))
                            <span class="badge badge-green"><i class="fa-solid fa-check"></i> Active</span>
                        @else
                            <span class="badge badge-gray"><i class="fa-solid fa-lock"></i> Not licensed</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @if(count($catalog) - count($enabled) > 0)
    <div style="padding:14px 18px;background:#f7f9fc;border-top:1px solid var(--sc-border);font-size:12.5px;color:var(--sc-muted)">
        <i class="fa-solid fa-info-circle"></i>
        Want to add more modules? Contact your software vendor for a new license file, then install it using the form below.
    </div>
    @endif
</div>

<div class="card mt-4">
    <div class="card-header">
        <i class="fa-solid fa-key"></i> Install or Update License
    </div>
    <div style="padding:18px">
        <p class="text-small text-muted" style="margin-top:0">
            Your software vendor will send you two things: <strong>a public key</strong> (a short text string shown on your buyer detail page) and <strong>a license zip</strong> containing <code>license.json</code> and <code>license.sig</code>. Paste the key and upload both files below. The signature is verified before anything is saved, so a wrong key or tampered file will be rejected with a clear error.
        </p>

        @if($publicKeySource === 'env')
        <div class="alert alert-info" style="margin-bottom:14px">
            <i class="fa-solid fa-circle-info"></i>
            <div>
                Note: <code>MODULES_PUBLIC_KEY</code> is currently set in this server's <code>.env</code> file. That value will take precedence over anything you submit here. Ask your server administrator to clear it from <code>.env</code> if you want to manage the key from this page.
            </div>
        </div>
        @endif

        @if($errors->any())
        <div class="alert alert-danger" style="margin-bottom:14px">
            <i class="fa-solid fa-circle-xmark"></i>
            <div>
                @foreach($errors->all() as $err)
                    <div>{{ $err }}</div>
                @endforeach
            </div>
        </div>
        @endif

        <form action="{{ route('admin.modules.install-license') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="form-group">
                <label for="public_key" class="form-label">Public Key</label>
                <input type="text" name="public_key" id="public_key"
                       class="form-control" autocomplete="off" spellcheck="false"
                       placeholder="e.g. /LkV/gUZ2XHX6RYQwjv1MsyNBA3xFVbqZQZULXOg1dE="
                       value="{{ old('public_key', $publicKeySource === 'buyer_config' ? $currentPubKey : '') }}">
                <div class="form-help text-small text-muted">
                    44-character base64 string. Copy it exactly from the "Buyers" page on your vendor's licensing panel.
                </div>
            </div>

            <div class="grid grid-2" style="gap:14px">
                <div class="form-group">
                    <label for="license_json" class="form-label">license.json</label>
                    <input type="file" name="license_json" id="license_json" class="form-control" accept=".json,application/json,text/plain">
                </div>
                <div class="form-group">
                    <label for="license_sig" class="form-label">license.sig</label>
                    <input type="file" name="license_sig" id="license_sig" class="form-control" accept=".sig,text/plain,application/octet-stream">
                </div>
            </div>

            <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:6px">
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-upload"></i> Install License
                </button>
            </div>
        </form>

        @if(!empty($heartbeatUrl))
        <div class="text-small text-muted" style="margin-top:14px">
            <i class="fa-solid fa-satellite-dish"></i>
            License status refreshed automatically from <code>{{ $heartbeatUrl }}</code> every time this page is opened.
        </div>
        @endif
    </div>
</div>

@endsection
