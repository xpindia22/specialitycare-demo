@extends('layouts.app')
@section('title', 'Follow-Up Tracker')
@section('content')

<div class="page-header">
    <div class="page-header-left">
        <h1>Follow-Up Tracker</h1>
        <div class="breadcrumb">Reports &rsaquo; Follow-Up Tracker</div>
    </div>
    <a href="{{ route('reports.follow-up.export', request()->query()) }}" class="btn btn-outline">
        <i class="fa-solid fa-file-csv"></i> Export CSV
    </a>
</div>

{{-- Filter tabs --}}
<div style="display:flex;gap:0;margin-bottom:20px;background:var(--sc-surface);border:1px solid var(--sc-border);border-radius:var(--radius);overflow:hidden;width:fit-content">
    @foreach(['overdue' => ['label'=>'Overdue','color'=>'var(--sc-red)'], 'today' => ['label'=>'Due Today','color'=>'var(--sc-amber)'], 'upcoming' => ['label'=>'Next 7 Days','color'=>'var(--sc-teal)']] as $key => $cfg)
    <a href="{{ route('reports.follow-up', array_merge(request()->query(), ['filter'=>$key])) }}"
       style="padding:10px 20px;font-size:13px;font-weight:600;text-decoration:none;border-right:1px solid var(--sc-border);
              {{ $filter === $key ? 'background:'.$cfg['color'].';color:#fff;' : 'color:var(--sc-muted);' }}">
        {{ $cfg['label'] }}
        <span style="margin-left:6px;background:{{ $filter === $key ? 'rgba(255,255,255,.25)' : 'var(--sc-border)' }};color:{{ $filter === $key ? '#fff' : 'var(--sc-navy)' }};padding:1px 7px;border-radius:10px;font-size:11px">
            {{ $counts[$key] }}
        </span>
    </a>
    @endforeach
</div>

{{-- Search / doctor filter --}}
<form method="GET" class="card mb-4">
    <div class="card-body" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;padding:14px 20px">
        <input type="hidden" name="filter" value="{{ $filter }}">
        <div class="form-group" style="margin:0;flex:1;min-width:200px">
            <label class="form-label">Search Patient</label>
            <input type="text" name="q" value="{{ $q }}" class="form-control" placeholder="Name or JHC ID">
        </div>
        <div class="form-group" style="margin:0;min-width:180px">
            <label class="form-label">Doctor</label>
            <select name="doctor_id" class="form-control">
                <option value="">All Doctors</option>
                @foreach($doctors as $doc)
                <option value="{{ $doc->id }}" {{ $doctorId == $doc->id ? 'selected' : '' }}>{{ $doc->full_name }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-filter"></i> Filter</button>
        <a href="{{ route('reports.follow-up', ['filter' => $filter]) }}" class="btn btn-outline">Reset</a>
    </div>
</form>

{{-- Table --}}
<div class="card">
    <div class="card-header">
        <i class="fa-solid fa-calendar-check"></i>
        {{ ['overdue'=>'Overdue Follow-Ups','today'=>'Due Today','upcoming'=>'Upcoming (Next 7 Days)'][$filter] }}
        <div class="card-actions"><span class="badge badge-navy">{{ $encounters->count() }}</span></div>
    </div>
    @php $canRemind = in_array($filter, ['overdue', 'today']) && $encounters->isNotEmpty(); @endphp
    @if($canRemind)
    {{-- Bulk reminders. "Select all" skips patients who already returned/booked
         and anyone reminded on that channel in the last 7 days. --}}
    <div id="fu-bulk" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;padding:10px 20px;border-bottom:1px solid var(--sc-border);background:var(--sc-surface)">
        <label style="display:flex;gap:6px;align-items:center;font-size:13px;margin:0;cursor:pointer">
            <input type="checkbox" id="fu-all" onchange="fuSelectAll(this.checked)"> Select all needing a reminder
        </label>
        <span class="text-small text-muted" id="fu-count">0 selected</span>
        <button type="button" class="btn btn-sm btn-outline" style="color:#25D366;border-color:#25D366" onclick="fuBulk('whatsapp')"><i class="fa-brands fa-whatsapp"></i> Send WhatsApp</button>
        <button type="button" class="btn btn-sm btn-outline" onclick="fuBulk('email')"><i class="fa-solid fa-envelope"></i> Send Email</button>
        <span class="text-small" id="fu-progress"></span>
    </div>
    @endif
    <div class="table-wrap">
        <table class="sc-table">
            <thead>
                <tr>
                    @if($canRemind)<th style="width:28px"></th>@endif
                    <th>Patient</th>
                    <th>JHC ID</th>
                    <th>Doctor</th>
                    <th>Encounter Date</th>
                    <th>Follow-Up Date</th>
                    <th>Days {{ $filter === 'overdue' ? 'Overdue' : 'Remaining' }}</th>
                    <th>Diagnosis</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($encounters as $enc)
                @php
                    $days = (int) now()->diffInDays($enc->follow_up_date, false);
                    $isOverdue = $days < 0;
                    $dayColor = $isOverdue ? 'var(--sc-red)' : ($days === 0 ? 'var(--sc-amber)' : 'var(--sc-teal)');
                    $diagnosis = $enc->diagnoses->first()?->icd_description ?? $enc->impression ?? '—';
                    $back = $returned[$enc->id] ?? null;
                    $waAt = $enc->followup_whatsapp_sent_at;
                    $emAt = $enc->followup_email_sent_at;
                    $hasPhone = $enc->patient->phone || $enc->patient->alt_phone;
                    $hasEmail = (bool) $enc->patient->email;
                @endphp
                <tr>
                    @if($canRemind)
                    <td><input type="checkbox" class="fu-pick" value="{{ $enc->id }}" onchange="fuCount()"
                               data-returned="{{ $back ? 1 : 0 }}"
                               data-phone="{{ $hasPhone ? 1 : 0 }}" data-email="{{ $hasEmail ? 1 : 0 }}"
                               data-wa-recent="{{ $waAt && $waAt->gt(now()->subDays(7)) ? 1 : 0 }}"
                               data-em-recent="{{ $emAt && $emAt->gt(now()->subDays(7)) ? 1 : 0 }}"></td>
                    @endif
                    <td class="fw-600">{{ $enc->patient->full_name }}
                        @if($back)<span class="badge badge-green" title="{{ $back === 'Returned' ? 'Visited again after this encounter' : 'Has an appointment on/after the follow-up date' }}">{{ $back }}</span>@endif
                        @if($waAt || $emAt)
                        <div class="text-small text-muted" style="font-weight:400">
                            Reminded: @if($waAt)WhatsApp {{ $waAt->format('d M') }}@endif @if($waAt && $emAt)·@endif @if($emAt)Email {{ $emAt->format('d M') }}@endif
                        </div>
                        @endif
                    </td>
                    <td class="mono text-small text-muted">{{ $enc->patient->jhc_id }}</td>
                    <td class="text-small">{{ $enc->doctor->full_name }}</td>
                    <td class="text-small text-muted">{{ $enc->encounter_date->format('d M Y') }}</td>
                    <td class="fw-600" style="color:{{ $dayColor }}">{{ $enc->follow_up_date->format('d M Y') }}</td>
                    <td class="fw-700 text-center" style="color:{{ $dayColor }}">
                        {{ $isOverdue ? abs($days).' days ago' : ($days === 0 ? 'Today' : $days.' days') }}
                    </td>
                    <td class="text-small text-muted">{{ Str::limit($diagnosis, 40) }}</td>
                    <td style="white-space:nowrap">
                        @if($canRemind)
                        <button type="button" class="btn btn-sm btn-outline" style="color:#25D366;border-color:#25D366" id="fu-wa-{{ $enc->id }}"
                                title="{{ $hasPhone ? 'WhatsApp follow-up reminder' : 'No phone on file' }}" {{ $hasPhone ? '' : 'disabled' }}
                                onclick="fuSend({{ $enc->id }}, 'whatsapp', this)"><i class="fa-brands fa-whatsapp"></i></button>
                        <button type="button" class="btn btn-sm btn-outline" id="fu-em-{{ $enc->id }}"
                                title="{{ $hasEmail ? 'Email follow-up reminder (CC clinic)' : 'No email on file' }}" {{ $hasEmail ? '' : 'disabled' }}
                                onclick="fuSend({{ $enc->id }}, 'email', this)"><i class="fa-solid fa-envelope"></i></button>
                        @endif
                        <button type="button" class="btn btn-sm btn-outline" title="Book follow-up appointment" onclick="scBookFollowUp({{ $enc->id }}, this)"><i class="fa-solid fa-calendar-plus"></i> Book</button>
                        <a href="{{ route('encounters.show', $enc) }}" class="btn btn-sm btn-outline">View</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="{{ $canRemind ? 9 : 8 }}" style="text-align:center;padding:48px;color:var(--sc-muted)">
                        <i class="fa-solid fa-calendar-check" style="font-size:2rem;margin-bottom:8px;display:block;opacity:.3"></i>
                        No follow-ups found for this filter.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@push('scripts')
<script>
function fuUrl(id, channel) {
    return '{{ url("/reports/follow-up") }}/' + id + (channel === 'email' ? '/remind-email' : '/remind-whatsapp');
}

// Send one reminder; resolves true/false so bulk sends can count results.
function fuSend(id, channel, btn, quiet) {
    btn = btn || document.getElementById((channel === 'email' ? 'fu-em-' : 'fu-wa-') + id);
    var orig = btn.innerHTML;
    btn.disabled = true; btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
    return $.post(fuUrl(id, channel), {}).then(function (res) {
        btn.innerHTML = '<i class="fa-solid fa-check"></i>';
        btn.style.color = '#15803d'; btn.style.borderColor = '#86efac';
        btn.title = res.message || 'Reminder sent';
        return true;
    }, function (xhr) {
        btn.disabled = false; btn.innerHTML = orig;
        var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'Could not send the reminder.';
        btn.title = msg;
        if (!quiet) alert(msg);
        return $.Deferred().resolve(false);
    });
}

function fuPicked() { return Array.prototype.slice.call(document.querySelectorAll('.fu-pick:checked')); }
function fuCount() { document.getElementById('fu-count').textContent = fuPicked().length + ' selected'; }

function fuSelectAll(on) {
    document.querySelectorAll('.fu-pick').forEach(function (cb) {
        cb.checked = on && cb.dataset.returned !== '1'
                   && !(cb.dataset.waRecent === '1' && cb.dataset.emRecent === '1');
    });
    fuCount();
}

function fuBulk(channel) {
    var picked = fuPicked();
    if (!picked.length) { alert('Tick the patients to remind first (or "Select all").'); return; }
    var contactKey = channel === 'email' ? 'email' : 'phone';
    var recentKey  = channel === 'email' ? 'emRecent' : 'waRecent';
    var todo = picked.filter(function (cb) { return cb.dataset[contactKey] === '1' && cb.dataset[recentKey] !== '1'; });
    var noContact = picked.filter(function (cb) { return cb.dataset[contactKey] !== '1'; }).length;
    var recent = picked.length - todo.length - noContact;
    var label = channel === 'email' ? 'email' : 'WhatsApp';
    if (!todo.length) { alert('None of the selected patients can get a ' + label + ' reminder (no ' + (channel === 'email' ? 'email' : 'phone') + ' on file, or reminded in the last 7 days).'); return; }
    var note = (noContact ? '\n' + noContact + ' skipped: no ' + (channel === 'email' ? 'email' : 'phone') + ' on file.' : '')
             + (recent ? '\n' + recent + ' skipped: already sent a ' + label + ' reminder in the last 7 days.' : '');
    if (!confirm('Send a ' + label + ' follow-up reminder to ' + todo.length + ' patient(s)?' + note)) return;

    var prog = document.getElementById('fu-progress'), ok = 0, fail = 0, i = 0;
    (function next() {
        if (i >= todo.length) {
            prog.innerHTML = '<span style="color:#15803d">' + label + ': ' + ok + ' sent</span>'
                           + (fail ? ', <span style="color:var(--sc-red)">' + fail + ' failed (hover the button for the reason)</span>' : '');
            return;
        }
        var cb = todo[i++];
        prog.textContent = 'Sending ' + label + ' ' + i + ' of ' + todo.length + '…';
        fuSend(cb.value, channel, null, true).then(function (sent) {
            if (sent) { ok++; cb.dataset[recentKey] = '1'; cb.checked = false; } else { fail++; }
            fuCount();
            setTimeout(next, 400);
        });
    })();
}
</script>
@endpush
@endsection
