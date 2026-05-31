@extends('owner._layout')
@section('title', $buyer->exists ? 'Edit Buyer' : 'Add Buyer')
@section('content')

<div class="card">
    <div class="card-h">
        <i class="fa-solid {{ $buyer->exists ? 'fa-pen-to-square' : 'fa-user-plus' }}"></i>
        {{ $buyer->exists ? 'Edit '.$buyer->name : 'Add Buyer' }}
        <div class="right"><a href="{{ route('owner.buyers.index') }}" class="btn btn-sm btn-outline"><i class="fa-solid fa-arrow-left"></i> Back</a></div>
    </div>
    <div class="card-b">
        <form method="POST" action="{{ $buyer->exists ? route('owner.buyers.update', $buyer) : route('owner.buyers.store') }}">
            @csrf
            @if($buyer->exists)@method('PUT')@endif

            <div class="grid-2">
                <div class="form-row">
                    <label>Institution name <span class="req">*</span></label>
                    <input type="text" name="name" required value="{{ old('name', $buyer->name) }}" placeholder="e.g. ABC Hospital, Hyderabad">
                </div>
                <div class="form-row">
                    <label>Contact person</label>
                    <input type="text" name="contact_name" value="{{ old('contact_name', $buyer->contact_name) }}">
                </div>
                <div class="form-row">
                    <label>Contact email</label>
                    <input type="email" name="contact_email" value="{{ old('contact_email', $buyer->contact_email) }}">
                </div>
                <div class="form-row">
                    <label>Contact phone</label>
                    <input type="tel" name="contact_phone" value="{{ old('contact_phone', $buyer->contact_phone) }}">
                </div>
                <div class="form-row">
                    <label>City</label>
                    <input type="text" name="city" value="{{ old('city', $buyer->city) }}">
                </div>
                <div class="form-row">
                    <label>State</label>
                    <input type="text" name="state" value="{{ old('state', $buyer->state) }}">
                </div>
            </div>
            <div class="form-row">
                <label>Address</label>
                <textarea name="address" rows="2">{{ old('address', $buyer->address) }}</textarea>
            </div>
            <div class="form-row">
                <label>Notes (private — not shown to the buyer)</label>
                <textarea name="notes" rows="3" placeholder="Sales lead source, contract terms, anything to remember">{{ old('notes', $buyer->notes) }}</textarea>
            </div>

            <hr style="margin:18px 0;border:none;border-top:1px solid var(--border)">
            <div class="text-small text-muted" style="margin-bottom:10px;text-transform:uppercase;letter-spacing:.5px;font-weight:600">Enforcement</div>

            <div class="form-row">
                <label>Default dead-man grace (seconds)</label>
                <input type="number" name="default_grace_seconds" id="default_grace_seconds"
                       min="0" max="31536000" step="1"
                       value="{{ old('default_grace_seconds', $buyer->default_grace_seconds) }}"
                       placeholder="leave blank to use system default ({{ config('modules.heartbeat_grace_seconds', 3600) }})">
                <div class="text-small text-muted" style="margin-top:6px">
                    How long this buyer's install may go without a successful heartbeat before modules auto-disable. Used as the default when issuing licenses for this buyer; can be overridden per license. Blank = system default ({{ config('modules.heartbeat_grace_seconds', 3600) }}s). 0 = dead-man off (revocation still works).
                </div>
                <div style="margin-top:8px;display:flex;gap:6px;flex-wrap:wrap">
                    <button type="button" class="btn btn-sm btn-outline" onclick="document.getElementById('default_grace_seconds').value=0">Disabled</button>
                    <button type="button" class="btn btn-sm btn-outline" onclick="document.getElementById('default_grace_seconds').value=60">1 min</button>
                    <button type="button" class="btn btn-sm btn-outline" onclick="document.getElementById('default_grace_seconds').value=3600">1 hour</button>
                    <button type="button" class="btn btn-sm btn-outline" onclick="document.getElementById('default_grace_seconds').value=86400">1 day</button>
                    <button type="button" class="btn btn-sm btn-outline" onclick="document.getElementById('default_grace_seconds').value=604800">7 days</button>
                    <button type="button" class="btn btn-sm btn-outline" onclick="document.getElementById('default_grace_seconds').value=2592000">30 days</button>
                </div>
            </div>

            <button type="submit" class="btn btn-navy"><i class="fa-solid fa-floppy-disk"></i> {{ $buyer->exists ? 'Save Changes' : 'Add Buyer' }}</button>
        </form>
    </div>
</div>

@endsection
