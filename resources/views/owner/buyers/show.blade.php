@extends('owner._layout')
@section('title', $buyer->name)
@section('content')

@php
    $totalPaid = $buyer->licenses->sum(fn ($l) => (float) $l->payment_amount);
    $active = $buyer->licenses->firstWhere('status', 'active');
@endphp

<div class="card">
    <div class="card-h">
        <i class="fa-solid fa-building"></i> {{ $buyer->name }}
        <div class="right">
            <a href="{{ route('owner.buyers.edit', $buyer) }}" class="btn btn-sm btn-outline"><i class="fa-solid fa-pen-to-square"></i> Edit</a>
            <a href="{{ route('owner.licenses.create', $buyer) }}" class="btn btn-sm"><i class="fa-solid fa-file-signature"></i> Issue License</a>
        </div>
    </div>
    <div class="card-b">
        <div class="grid-3">
            <div>
                <div class="text-small text-muted">Contact</div>
                <div style="font-weight:600">{{ $buyer->contact_name ?: '—' }}</div>
                @if($buyer->contact_email)<div class="text-small">{{ $buyer->contact_email }}</div>@endif
                @if($buyer->contact_phone)<div class="text-small mono">{{ $buyer->contact_phone }}</div>@endif
            </div>
            <div>
                <div class="text-small text-muted">Location</div>
                <div>{{ trim($buyer->city.($buyer->city && $buyer->state ? ', ' : '').$buyer->state) ?: '—' }}</div>
                @if($buyer->address)<div class="text-small text-muted">{{ $buyer->address }}</div>@endif
            </div>
            <div>
                <div class="text-small text-muted">Total Paid</div>
                <div style="font-size:18px;font-weight:700;color:var(--green)">₹{{ number_format($totalPaid, 0) }}</div>
                <div class="text-small text-muted">{{ $buyer->licenses->count() }} {{ \Illuminate\Support\Str::plural('license', $buyer->licenses->count()) }} issued</div>
            </div>
        </div>
        @if($buyer->notes)
        <div style="margin-top:14px;padding-top:14px;border-top:1px solid var(--border)">
            <div class="text-small text-muted">Notes</div>
            <div style="white-space:pre-wrap">{{ $buyer->notes }}</div>
        </div>
        @endif
    </div>
</div>

{{-- Per-buyer keypair: critical anti-piracy element --}}
<div class="card">
    <div class="card-h">
        <i class="fa-solid fa-key"></i> Buyer's Cryptographic Keypair
        <div class="right">
            <form method="POST" action="{{ route('owner.buyers.regenerate-keypair', $buyer) }}" style="margin:0"
                  onsubmit="return confirm('{{ $buyer->public_key ? 'Regenerate keypair? ALL existing licenses for this buyer become invalid — they will need a new license + new public key in their .env.' : 'Generate keypair for this buyer?' }}')">
                @csrf
                <button type="submit" class="btn btn-sm {{ $buyer->public_key ? 'btn-amber' : 'btn-navy' }}">
                    <i class="fa-solid fa-rotate"></i> {{ $buyer->public_key ? 'Regenerate' : 'Generate' }} Keypair
                </button>
            </form>
        </div>
    </div>
    <div class="card-b">
        @if($buyer->public_key)
            <div class="alert alert-info">
                <i class="fa-solid fa-info-circle"></i>
                <div>
                    Each buyer has their <strong>own unique keypair</strong>. A license signed for {{ $buyer->name }} will only validate on installs whose <span class="mono">.env</span> has the public key below. This is what stops a buyer from sharing licenses with friends.
                </div>
            </div>

            <div class="grid-2">
                <div>
                    <label>Public key fingerprint</label>
                    <div class="mono" style="font-size:13px;color:var(--navy);font-weight:600">{{ $buyer->publicKeyFingerprint() }}</div>
                </div>
                <div>
                    <label>Generated</label>
                    <div>{{ $buyer->keypair_generated_at?->format('d M Y H:i') ?? '—' }}</div>
                </div>
            </div>

            <div style="margin-top:14px">
                <label>Public key (give this to the buyer)</label>
                <div style="display:flex;gap:6px;align-items:flex-start">
                    <textarea readonly style="font-family:'DM Mono',monospace;font-size:11.5px;height:60px;background:#f7f9fc">{{ $buyer->public_key }}</textarea>
                    <button type="button" class="btn btn-sm btn-outline" onclick="navigator.clipboard.writeText('{{ $buyer->public_key }}'); this.innerHTML='<i class=\'fa-solid fa-check\'></i>'"><i class="fa-solid fa-copy"></i></button>
                </div>
            </div>

            <div style="margin-top:14px">
                <label>Buyer install setup snippet</label>
                <pre class="mono" style="background:var(--navy);color:#cbd5e1;padding:14px;border-radius:6px;font-size:12px;overflow-x:auto;line-height:1.6">{{-- raw to preserve newlines --}}
# Add this line to .env on the buyer's server, then:
#   php artisan config:clear
MODULES_PUBLIC_KEY={{ $buyer->public_key }}</pre>
            </div>

            <div class="alert alert-danger" style="margin-top:14px">
                <i class="fa-solid fa-shield-halved"></i>
                <div>
                    The matching <strong>private key never leaves your owner panel</strong> (encrypted in the database).
                    If you regenerate, ALL existing licenses for this buyer become invalid.
                </div>
            </div>
        @else
            <div class="alert alert-warning">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <div>
                    No keypair yet — click <strong>Generate Keypair</strong> above to create one. Until you do, you can't issue licenses for this buyer.
                </div>
            </div>
        @endif
    </div>
</div>

<div class="card">
    <div class="card-h"><i class="fa-solid fa-file-signature"></i> Licenses for {{ $buyer->name }}</div>
    @if($buyer->licenses->isEmpty())
        <div class="empty"><i class="fa-solid fa-file-circle-xmark"></i>No licenses issued yet. <a href="{{ route('owner.licenses.create', $buyer) }}">Issue the first one →</a></div>
    @else
    <table>
        <thead><tr>
            <th style="width:70px">#</th><th>Issued</th><th>Modules</th><th>Expires</th><th>Payment</th><th>Status</th><th></th>
        </tr></thead>
        <tbody>
            @foreach($buyer->licenses as $l)
            <tr>
                <td><a href="{{ route('owner.licenses.show', $l) }}" class="mono" style="font-weight:600">#{{ $l->id }}</a></td>
                <td><div>{{ $l->issued_at->format('d M Y') }}</div><div class="text-small text-muted">by {{ $l->createdBy?->name ?? '—' }}</div></td>
                <td>@foreach($l->modules as $m)<span class="badge teal" style="margin-right:3px">{{ $m }}</span>@endforeach</td>
                <td>{{ $l->expires_at ? $l->expires_at->format('d M Y') : 'Never' }}</td>
                <td>
                    @if($l->payment_amount)
                        <div class="mono">₹{{ number_format($l->payment_amount, 0) }}</div>
                        @if($l->payment_received_at)<div class="text-small text-muted">{{ $l->payment_received_at->format('d M Y') }}</div>@endif
                    @else <span class="text-muted text-small">—</span>@endif
                </td>
                <td>
                    @if($l->status === 'active')<span class="badge green">Active</span>
                    @elseif($l->status === 'revoked')<span class="badge red">Revoked</span>
                    @else<span class="badge amber">Expired</span>
                    @endif
                </td>
                <td class="text-right"><a href="{{ route('owner.licenses.show', $l) }}" class="btn btn-sm btn-outline"><i class="fa-solid fa-eye"></i></a></td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
</div>

@endsection
