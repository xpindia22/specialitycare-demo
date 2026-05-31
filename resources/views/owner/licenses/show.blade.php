@extends('owner._layout')
@section('title','License — '.$license->buyer->name)
@section('content')

<div class="card">
    <div class="card-h">
        <i class="fa-solid fa-file-signature"></i> License #{{ $license->id }} — {{ $license->buyer->name }}
        <div class="right">
            @if($license->status === 'active')<span class="badge green">Active</span>
            @elseif($license->status === 'revoked')<span class="badge red">Revoked</span>
            @else<span class="badge amber">Expired</span>
            @endif
        </div>
    </div>
    <div class="card-b">
        <div class="grid-3">
            <div>
                <div class="text-small text-muted">Buyer</div>
                <div style="font-weight:600"><a href="{{ route('owner.buyers.show', $license->buyer) }}">{{ $license->buyer->name }}</a></div>
                @if($license->buyer->contact_email)<div class="text-small">{{ $license->buyer->contact_email }}</div>@endif
            </div>
            <div>
                <div class="text-small text-muted">Issued</div>
                <div>{{ $license->issued_at->format('d M Y') }}</div>
                <div class="text-small text-muted">by {{ $license->createdBy?->name ?? '—' }}</div>
            </div>
            <div>
                <div class="text-small text-muted">Expires</div>
                <div>{{ $license->expires_at ? $license->expires_at->format('d M Y') : 'Never' }}</div>
                <div class="text-small text-muted mono">fp: {{ $license->public_key_fingerprint }}</div>
            </div>
        </div>
        <div style="margin-top:14px;padding-top:14px;border-top:1px solid var(--border)">
            <div class="text-small text-muted">Modules</div>
            <div style="margin-top:4px">
                @foreach($license->modules as $m)<span class="badge teal" style="margin-right:4px">{{ $m }}</span>@endforeach
            </div>
        </div>
        @if($license->payment_amount)
        <div style="margin-top:14px;padding-top:14px;border-top:1px solid var(--border)" class="grid-3">
            <div>
                <div class="text-small text-muted">Amount</div>
                <div style="font-weight:600;color:var(--green)" class="mono">{{ $license->payment_currency }} {{ number_format($license->payment_amount, 2) }}</div>
            </div>
            <div>
                <div class="text-small text-muted">Received on</div>
                <div>{{ $license->payment_received_at?->format('d M Y') ?? '—' }}</div>
            </div>
            <div>
                <div class="text-small text-muted">Reference</div>
                <div class="mono text-small">{{ $license->payment_reference ?: '—' }}</div>
            </div>
        </div>
        @if($license->payment_notes)<div class="text-small text-muted" style="margin-top:8px">{{ $license->payment_notes }}</div>@endif
        @endif
        @if($license->revoked_at)
        <div class="alert alert-danger" style="margin-top:14px"><i class="fa-solid fa-ban"></i>
            <div>Revoked on {{ $license->revoked_at->format('d M Y') }} — {{ $license->revoke_reason ?: 'no reason given' }}</div>
        </div>
        @endif
    </div>
</div>

<div class="card">
    <div class="card-h"><i class="fa-solid fa-cogs"></i> Actions</div>
    <div class="card-b" style="display:flex;gap:10px;flex-wrap:wrap">
        <a href="{{ route('owner.licenses.download', $license) }}" class="btn btn-navy">
            <i class="fa-solid fa-cloud-arrow-down"></i> Download .zip (license + README)
        </a>

        <form method="POST" action="{{ route('owner.licenses.install', $license) }}" onsubmit="return confirm('Install this license to THIS server? It will replace the current license file.')" style="margin:0">
            @csrf
            <button type="submit" class="btn btn-amber"><i class="fa-solid fa-download"></i> Install on This Server</button>
        </form>

        @if($license->revoked_at)
            <form method="POST" action="{{ route('owner.licenses.unrevoke', $license) }}" style="margin:0" onsubmit="return confirm('Clear the revocation flag?')">
                @csrf
                <button type="submit" class="btn btn-outline"><i class="fa-solid fa-rotate-left"></i> Clear Revocation</button>
            </form>
        @else
            <form method="POST" action="{{ route('owner.licenses.revoke', $license) }}" style="margin:0;display:flex;gap:6px;align-items:center" onsubmit="return confirm('Mark this license as revoked in your ledger? (Buyer\'s installed copy continues to work until you re-issue.)')">
                @csrf
                <input type="text" name="revoke_reason" placeholder="Reason (optional)" maxlength="255" style="width:240px">
                <button type="submit" class="btn btn-danger"><i class="fa-solid fa-ban"></i> Revoke</button>
            </form>
        @endif
    </div>
</div>

<div class="card">
    <div class="card-h"><i class="fa-solid fa-code"></i> Signed Payload</div>
    <div class="card-b">
        <details>
            <summary style="cursor:pointer;font-weight:600">View license.json</summary>
            <pre class="mono" style="background:#f7f9fc;padding:12px;border-radius:6px;margin-top:10px;font-size:12px;overflow-x:auto">{{ $license->license_json }}</pre>
        </details>
        <details style="margin-top:10px">
            <summary style="cursor:pointer;font-weight:600">View signature</summary>
            <pre class="mono" style="background:#f7f9fc;padding:12px;border-radius:6px;margin-top:10px;font-size:11px;overflow-wrap:break-word;word-break:break-all;white-space:pre-wrap">{{ $license->license_sig }}</pre>
        </details>
    </div>
</div>

@endsection
