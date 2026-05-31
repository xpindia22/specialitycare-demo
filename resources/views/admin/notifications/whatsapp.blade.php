@extends('layouts.app')
@section('title','WhatsApp Notifications')
@section('page-title','WhatsApp Notifications')
@section('content')

<div class="page-header">
    <div class="page-header-left">
        <h1>WhatsApp Notifications</h1>
        <div class="breadcrumb">Send test messages, view recent activity, monitor delivery status</div>
    </div>
    <div class="page-header-right">
        <a href="{{ route('admin.notifications.preferences') }}" class="btn btn-secondary">
            <i class="fa-solid fa-sliders"></i> Notification Preferences
        </a>
    </div>
</div>

<div class="alert alert-info">
    <i class="fa-solid fa-circle-info"></i>
    <div>
        Active provider:
        <strong>{{ ['meta' => 'Meta WhatsApp Cloud API', 'gupshup' => 'Gupshup'][$provider] ?? 'Twilio' }}</strong>
        (<code>WHATSAPP_PROVIDER={{ $provider }}</code>).
    </div>
</div>

{{-- Master kill switch for all in-app WhatsApp sends.
     Bash cron jobs (backup notifications, etc.) bypass this since they call
     Gupshup directly — they have their own controls under Settings. --}}
<div class="card" style="margin-bottom:16px;{{ $masterOff ? 'border:2px solid #dc2626;background:#fef2f2' : '' }}">
    <div class="card-header">
        <i class="fa-solid fa-power-off"></i> Master Switch
        @if($masterOff)<span class="badge badge-red" style="margin-left:8px">ALL WHATSAPP DISABLED</span>@endif
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.notifications.whatsapp.master-toggle') }}">
            @csrf
            <label style="display:flex;align-items:center;gap:10px;cursor:pointer;font-size:14px">
                <input type="checkbox" name="master_off" value="1" {{ $masterOff ? 'checked' : '' }}
                       style="width:18px;height:18px;cursor:pointer">
                <span><strong>Stop all WhatsApp messages</strong> — overrides every per-event preference. When checked, no patient/owner/CC WhatsApp goes out from the app. Per-event prefs below stay configured but inactive. <em>Backup script sends from the server are unaffected (separate control under Settings).</em></span>
            </label>
            <button type="submit" class="btn btn-primary btn-sm" style="margin-top:12px">Save</button>
        </form>
    </div>
</div>

@if(!$configured)
<div class="alert alert-warning">
    <i class="fa-solid fa-triangle-exclamation"></i>
    <div>
        @if($provider === 'meta')
            <strong>Meta Cloud API is not fully configured.</strong>
            Set <code>META_WA_TOKEN</code> and <code>META_WA_PHONE_NUMBER_ID</code> in <code>.env</code> on this server.
        @elseif($provider === 'gupshup')
            <strong>Gupshup is not fully configured.</strong>
            Set <code>GUPSHUP_API_KEY</code> and <code>GUPSHUP_SOURCE</code> in <code>.env</code> on this server.
        @else
            <strong>Twilio is not fully configured.</strong>
            Set <code>TWILIO_SID</code>, <code>TWILIO_TOKEN</code>, and <code>TWILIO_WHATSAPP_FROM</code> in <code>.env</code> on this server.
        @endif
    </div>
</div>
@endif

@if($provider === 'meta' && $configured)
<div class="alert alert-info">
    <i class="fa-solid fa-circle-info"></i>
    <div>
        <strong>Live Meta Cloud API.</strong>
        Reminders &amp; other clinic-initiated messages are sent as <strong>approved templates</strong>.
        Free-form text (the box below) only reaches a patient within 24h of their last message to you.
    </div>
</div>
@endif

@if($sandbox)
<div class="alert alert-info">
    <i class="fa-solid fa-circle-info"></i>
    <div>
        <strong>Twilio sandbox mode.</strong>
        Sending from <code>{{ $fromNumber }}</code>. Each recipient must first send the sandbox join code (e.g. <code>join &lt;two-words&gt;</code>) to that number on WhatsApp before they can receive any messages from us. Find your join code in the Twilio console under Messaging → Try it out → Send a WhatsApp message.
    </div>
</div>
@endif

@if(session('status'))
<div class="alert alert-success"><i class="fa-solid fa-check"></i><div>{{ session('status') }}</div></div>
@endif
@if($errors->any())
<div class="alert alert-danger"><i class="fa-solid fa-circle-xmark"></i><div>{{ $errors->first() }}</div></div>
@endif

<div class="grid grid-3 mb-4">
    <div class="stat-card">
        <div class="stat-icon green"><i class="fa-brands fa-whatsapp"></i></div>
        <div>
            <div class="stat-value">{{ $sentToday }}</div>
            <div class="stat-label">Sent today</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red"><i class="fa-solid fa-circle-xmark"></i></div>
        <div>
            <div class="stat-value">{{ $failedToday }}</div>
            <div class="stat-label">Failed today</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon teal"><i class="fa-solid fa-paper-plane"></i></div>
        <div>
            <div class="stat-value" style="font-size:14px">{{ $fromNumber ?: '—' }}</div>
            <div class="stat-label">From number</div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <i class="fa-solid fa-paper-plane"></i> Send a test WhatsApp
    </div>
    <form method="POST" action="{{ route('admin.notifications.whatsapp.test') }}" style="padding:16px">
        @csrf
        <div class="grid grid-2" style="gap:16px">
            <div>
                <label class="form-label">Recipient phone</label>
                <input type="text" name="phone" class="form-control" placeholder="+91 9876543210 or 9876543210" value="{{ old('phone', $testRecipient) }}" required>
                <div class="text-small text-muted" style="margin-top:4px">10-digit numbers are auto-prefixed with +{{ config('services.whatsapp.default_country', '91') }}.</div>
            </div>
            <div>
                <label class="form-label">Message</label>
                <textarea name="message" class="form-control" rows="3" maxlength="1500" required>{{ old('message', 'Hi, this is a test message from ' . config('app.name', 'Speciality Care') . '. Please ignore.') }}</textarea>
            </div>
        </div>
        <div style="margin-top:12px">
            <button type="submit" class="btn btn-primary"><i class="fa-brands fa-whatsapp"></i> Send</button>
        </div>
    </form>
</div>

<div class="card">
    <div class="card-header">
        <i class="fa-solid fa-clock-rotate-left"></i> Recent messages (last 50)
    </div>
    <div class="table-responsive">
        <table class="sc-table table" style="margin-bottom:0">
            <thead>
                <tr>
                    <th style="width:140px">When</th>
                    <th style="width:90px">Status</th>
                    <th style="width:140px">Kind</th>
                    <th style="width:160px">To</th>
                    <th>Message</th>
                    <th style="width:160px">Patient</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $msg)
                <tr>
                    <td class="text-small text-muted">{{ $msg->created_at?->format('d M H:i') }}</td>
                    <td>
                        @if($msg->status === 'sent')
                            <span class="badge badge-green"><i class="fa-solid fa-check"></i> Sent</span>
                        @elseif($msg->status === 'failed')
                            <span class="badge badge-red" title="{{ $msg->error }}"><i class="fa-solid fa-xmark"></i> Failed</span>
                        @else
                            <span class="badge badge-gray">{{ $msg->status }}</span>
                        @endif
                    </td>
                    <td><span class="text-small">{{ $msg->kind }}</span></td>
                    <td><code>{{ $msg->to_phone }}</code></td>
                    <td class="text-small" style="max-width:400px;overflow:hidden;text-overflow:ellipsis">{{ \Illuminate\Support\Str::limit($msg->body, 120) }}</td>
                    <td class="text-small">{{ $msg->patient?->full_name ?? '—' }}</td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-muted" style="padding:24px">No messages yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
