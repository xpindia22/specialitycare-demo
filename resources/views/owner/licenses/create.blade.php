@extends('owner._layout')
@section('title','Issue License')
@section('content')

<div class="card">
    <div class="card-h"><i class="fa-solid fa-file-signature"></i> Issue New License
        <div class="right"><a href="{{ route('owner.licenses.index') }}" class="btn btn-sm btn-outline"><i class="fa-solid fa-arrow-left"></i> Back</a></div>
    </div>
    <div class="card-b">
        @if($buyers->isEmpty())
            <div class="alert alert-warning"><i class="fa-solid fa-triangle-exclamation"></i>
                No buyers in the ledger. <a href="{{ route('owner.buyers.create') }}">Add a buyer first →</a>
            </div>
        @else
        <form method="POST" action="{{ route('owner.licenses.store') }}">
            @csrf

            <div class="form-row">
                <label>Buyer <span class="req">*</span></label>
                <select name="buyer_id" required>
                    <option value="">Select buyer…</option>
                    @foreach($buyers as $b)
                    <option value="{{ $b->id }}" {{ old('buyer_id', $buyer?->id) == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                    @endforeach
                </select>
                <div class="text-small text-muted" style="margin-top:4px">Don't see them? <a href="{{ route('owner.buyers.create') }}">Add a new buyer →</a></div>
            </div>

            <div class="form-row">
                <label>Modules to enable <span class="req">*</span></label>
                <div class="grid-2">
                    @foreach($catalog as $key => $meta)
                    <label class="checkbox {{ in_array($key, old('modules', [])) ? 'checked' : '' }}">
                        <input type="checkbox" name="modules[]" value="{{ $key }}" {{ in_array($key, old('modules', [])) ? 'checked' : '' }} onchange="this.parentNode.classList.toggle('checked', this.checked)">
                        <i class="fa-solid {{ $meta['icon'] ?? 'fa-cube' }}" style="color:var(--teal)"></i>
                        <span style="flex:1">
                            <div style="font-weight:600">{{ $meta['label'] }}</div>
                            <div class="text-small text-muted">{{ $meta['description'] }}</div>
                        </span>
                    </label>
                    @endforeach
                </div>
            </div>

            <div class="form-row">
                <label>Expiry (leave blank for perpetual)</label>
                <input type="date" name="expires_at" min="{{ now()->addDay()->toDateString() }}" value="{{ old('expires_at') }}">
            </div>

            <div class="form-row">
                <label>Dead-man grace (seconds)</label>
                <input type="number" name="heartbeat_grace_seconds" id="heartbeat_grace_seconds"
                       min="0" max="31536000" step="1"
                       value="{{ old('heartbeat_grace_seconds', $buyer?->default_grace_seconds) }}"
                       placeholder="blank = buyer default ({{ $buyer?->default_grace_seconds ?? 'none' }}) or system fallback ({{ config('modules.heartbeat_grace_seconds', 3600) }})">
                <div class="text-small text-muted" style="margin-top:6px">
                    Per-license override for how long the install may run without a successful heartbeat before modules auto-disable. Blank = inherit the buyer's default. 0 = dead-man off (revocation still applies). Min 60 if positive.
                </div>
                <div style="margin-top:8px;display:flex;gap:6px;flex-wrap:wrap">
                    <button type="button" class="btn btn-sm btn-outline" onclick="document.getElementById('heartbeat_grace_seconds').value=0">Disabled</button>
                    <button type="button" class="btn btn-sm btn-outline" onclick="document.getElementById('heartbeat_grace_seconds').value=60">1 min</button>
                    <button type="button" class="btn btn-sm btn-outline" onclick="document.getElementById('heartbeat_grace_seconds').value=3600">1 hour</button>
                    <button type="button" class="btn btn-sm btn-outline" onclick="document.getElementById('heartbeat_grace_seconds').value=86400">1 day</button>
                    <button type="button" class="btn btn-sm btn-outline" onclick="document.getElementById('heartbeat_grace_seconds').value=604800">7 days</button>
                    <button type="button" class="btn btn-sm btn-outline" onclick="document.getElementById('heartbeat_grace_seconds').value=2592000">30 days</button>
                </div>
            </div>

            <hr style="margin:18px 0;border:none;border-top:1px solid var(--border)">
            <div class="text-small text-muted" style="margin-bottom:10px;text-transform:uppercase;letter-spacing:.5px;font-weight:600">Payment (optional — for your records)</div>

            <div class="grid-3">
                <div class="form-row">
                    <label>Amount</label>
                    <input type="number" name="payment_amount" min="0" step="0.01" value="{{ old('payment_amount') }}" placeholder="e.g. 50000">
                </div>
                <div class="form-row">
                    <label>Currency</label>
                    <select name="payment_currency">
                        <option value="INR" {{ old('payment_currency','INR')==='INR'?'selected':'' }}>INR ₹</option>
                        <option value="USD" {{ old('payment_currency')==='USD'?'selected':'' }}>USD $</option>
                        <option value="EUR" {{ old('payment_currency')==='EUR'?'selected':'' }}>EUR €</option>
                    </select>
                </div>
                <div class="form-row">
                    <label>Received on</label>
                    <input type="date" name="payment_received_at" max="{{ today()->toDateString() }}" value="{{ old('payment_received_at') }}">
                </div>
            </div>
            <div class="form-row">
                <label>Payment reference (UTR / cheque no / Stripe id)</label>
                <input type="text" name="payment_reference" maxlength="120" value="{{ old('payment_reference') }}">
            </div>
            <div class="form-row">
                <label>Payment notes</label>
                <textarea name="payment_notes" rows="2">{{ old('payment_notes') }}</textarea>
            </div>

            <hr style="margin:18px 0;border:none;border-top:1px solid var(--border)">

            <label class="checkbox">
                <input type="checkbox" name="install_locally" value="1" onchange="this.parentNode.classList.toggle('checked', this.checked)">
                <span><strong>Also install this license on THIS server.</strong> Use only when you're issuing for your own install — not when generating for a buyer.</span>
            </label>

            <div style="margin-top:18px">
                <button type="submit" class="btn btn-navy"><i class="fa-solid fa-pen-nib"></i> Sign &amp; Save License</button>
            </div>
        </form>
        @endif
    </div>
</div>

@endsection
