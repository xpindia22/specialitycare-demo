@extends('layouts.app')
@section('title','Notification Preferences')
@section('page-title','Notification Preferences')
@section('content')

@push('head')
<style>
.np-help{background:var(--sc-teal-glow);border:1px solid rgba(10,180,180,.25);border-radius:var(--radius);padding:12px 16px;margin-bottom:20px;font-size:13px;color:#065e5e;line-height:1.55}
.np-help strong{color:var(--sc-navy)}
.np-group{margin-bottom:20px}
.np-group .card-header .badge{margin-left:6px}
/* Lock all groups to identical column widths so checkboxes line up
   vertically across Patient / OPD / Clinical / Lab / Reports / Billing / IPD. */
.np-table{table-layout:fixed;width:100%}
/* Lock columns by percentage so the Event text never pushes the checkbox
   columns around — overflow wraps inside the first cell instead. */
.np-table col.c-event{width:42%}
.np-table col.c-enabled{width:10%}
.np-table col.c-check{width:12%}
.np-table th, .np-table td{padding-left:8px;padding-right:8px}
.np-table th.col-check, .np-table td.col-check{text-align:center;white-space:nowrap;padding-left:4px;padding-right:4px}
.np-table th.col-enabled, .np-table td.col-enabled{text-align:center;border-right:1px solid var(--sc-border);padding-left:4px;padding-right:4px}
.np-table th.col-group{text-align:center;font-size:10px;letter-spacing:.6px;padding-bottom:4px;border-bottom:1px solid var(--sc-border)}
.np-table th.col-group.grp-patient{border-right:1px dashed var(--sc-border)}
.np-table td.col-check.boundary-right{border-right:1px dashed var(--sc-border)}
.np-table th.col-check.boundary-right{border-right:1px dashed var(--sc-border)}
.np-table tr.disabled td{opacity:.45}
.np-table td:first-child, .np-table th:first-child{padding-left:14px;overflow-wrap:anywhere;word-break:break-word}
.np-event{font-weight:600;color:var(--sc-navy);font-size:13px;line-height:1.35}
.np-key{font-size:11px;color:var(--sc-muted);font-family:'DM Mono',ui-monospace,monospace;margin-top:1px;word-break:break-all}
.np-note{font-size:11.5px;color:var(--sc-muted);margin-top:4px;line-height:1.4}
.np-table input[type=checkbox]{cursor:pointer;width:16px;height:16px;accent-color:var(--sc-teal);vertical-align:middle;margin:0}
.np-table input[type=checkbox]:disabled{cursor:not-allowed;opacity:.4}
.np-table .badge{padding:2px 7px;font-size:10px}
.np-actions{position:sticky;bottom:0;background:linear-gradient(180deg,transparent,var(--sc-bg) 40%);padding:16px 0 4px;text-align:right;margin-top:8px;z-index:5}
.np-legend{display:inline-flex;gap:10px;align-items:center;margin-top:6px}
</style>
@endpush

<div class="page-header">
    <div class="page-header-left">
        <h1>Notification Preferences</h1>
        <div class="breadcrumb">Who gets notified, on which channel, for each domain event</div>
    </div>
    <div class="page-header-right">
        <a href="{{ route('admin.notifications.whatsapp') }}" class="btn btn-outline">
            <i class="fa-brands fa-whatsapp"></i> WhatsApp Log
        </a>
    </div>
</div>

@if(session('status'))
<div class="alert alert-success"><i class="fa-solid fa-check"></i><div>{{ session('status') }}</div></div>
@endif
@if($errors->any())
<div class="alert alert-danger"><i class="fa-solid fa-circle-xmark"></i><div>{{ $errors->first() }}</div></div>
@endif

<div class="np-help">
    <strong><i class="fa-solid fa-circle-info"></i> How this works.</strong>
    Each event has a master <em>Enabled</em> toggle plus four channel checkboxes — patient email / WhatsApp, admin email / WhatsApp.
    Twilio WhatsApp messages cost money per send; email is free.
    Changes apply within ~60 seconds (the lookup is cached).
    <div class="np-legend">
        <span class="badge badge-amber"><i class="fa-solid fa-envelope"></i> Email</span>
        <span class="badge badge-green"><i class="fa-brands fa-whatsapp"></i> WhatsApp (₹)</span>
    </div>
</div>

<form method="POST" action="{{ route('admin.notifications.preferences.update') }}">
    @csrf

    @foreach($events as $group => $rows)
    <div class="np-group card">
        <div class="card-header">
            <i class="fa-solid fa-layer-group"></i>
            {{ $group ?: 'Other' }}
            <span class="badge badge-gray">{{ $rows->count() }} events</span>
        </div>
        <div class="table-wrap">
            <table class="sc-table np-table">
                <colgroup>
                    <col class="c-event">
                    <col class="c-enabled">
                    <col class="c-check">
                    <col class="c-check">
                    <col class="c-check">
                    <col class="c-check">
                </colgroup>
                <thead>
                    <tr>
                        <th rowspan="2">Event</th>
                        <th class="col-enabled" rowspan="2">Enabled</th>
                        <th class="col-group grp-patient" colspan="2"><i class="fa-solid fa-user"></i> Patient</th>
                        <th class="col-group" colspan="2"><i class="fa-solid fa-user-shield"></i> Admin</th>
                    </tr>
                    <tr>
                        <th class="col-check"><span class="badge badge-amber"><i class="fa-solid fa-envelope"></i> Email</span></th>
                        <th class="col-check boundary-right"><span class="badge badge-green"><i class="fa-brands fa-whatsapp"></i> WA</span></th>
                        <th class="col-check"><span class="badge badge-amber"><i class="fa-solid fa-envelope"></i> Email</span></th>
                        <th class="col-check"><span class="badge badge-green"><i class="fa-brands fa-whatsapp"></i> WA</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $r)
                    <tr class="{{ $r->is_enabled ? '' : 'disabled' }}">
                        <td>
                            <div class="np-event">{{ $r->event_label }}</div>
                            <div class="np-key">{{ $r->event_key }}</div>
                            @if($r->note)
                            <div class="np-note">{{ $r->note }}</div>
                            @endif
                        </td>
                        <td class="col-enabled">
                            <input type="checkbox" name="prefs[{{ $r->id }}][is_enabled]" value="1" data-toggle-row {{ $r->is_enabled ? 'checked' : '' }}>
                        </td>
                        <td class="col-check">
                            <input type="checkbox" name="prefs[{{ $r->id }}][patient_email]" value="1" data-channel {{ $r->patient_email ? 'checked' : '' }} {{ $r->is_enabled ? '' : 'disabled' }}>
                        </td>
                        <td class="col-check boundary-right">
                            <input type="checkbox" name="prefs[{{ $r->id }}][patient_whatsapp]" value="1" data-channel {{ $r->patient_whatsapp ? 'checked' : '' }} {{ $r->is_enabled ? '' : 'disabled' }}>
                        </td>
                        <td class="col-check">
                            <input type="checkbox" name="prefs[{{ $r->id }}][admin_email]" value="1" data-channel {{ $r->admin_email ? 'checked' : '' }} {{ $r->is_enabled ? '' : 'disabled' }}>
                        </td>
                        <td class="col-check">
                            <input type="checkbox" name="prefs[{{ $r->id }}][admin_whatsapp]" value="1" data-channel {{ $r->admin_whatsapp ? 'checked' : '' }} {{ $r->is_enabled ? '' : 'disabled' }}>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endforeach

    <div class="np-actions">
        <button type="submit" class="btn btn-primary btn-lg">
            <i class="fa-solid fa-floppy-disk"></i> Save Preferences
        </button>
    </div>
</form>

<script>
// Toggling Enabled disables/enables the four channel checkboxes in that row.
document.querySelectorAll('[data-toggle-row]').forEach(toggle => {
    toggle.addEventListener('change', () => {
        const tr = toggle.closest('tr');
        tr.classList.toggle('disabled', !toggle.checked);
        tr.querySelectorAll('[data-channel]').forEach(cb => cb.disabled = !toggle.checked);
    });
});
</script>

@endsection
