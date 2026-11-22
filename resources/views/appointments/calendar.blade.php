@extends('layouts.app')
@section('title', 'Appointment Calendar')
@section('content')

@php
    $initialView = ['week' => 'timeGridWeek', 'month' => 'dayGridMonth', 'year' => 'multiMonthYear'][$view] ?? 'timeGridWeek';
@endphp

<div class="page-header">
    <div class="page-header-left">
        <h1>Appointment Calendar</h1>
        <div class="breadcrumb">Drag an appointment to reschedule — drop on a day/time slot.</div>
    </div>
    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
        <select id="cal-doctor" class="form-control" style="width:auto">
            <option value="">All Doctors</option>
            @foreach($doctors as $doc)
            <option value="{{ $doc->id }}" {{ $doctorId == $doc->id ? 'selected' : '' }}>{{ $doc->full_name }}</option>
            @endforeach
        </select>
        @include('reception._toolbar', ['inline'=>true])
    </div>
</div>

<div class="card"><div class="card-body">
    <div id="calendar"></div>
</div></div>

{{-- Status legend --}}
<div style="display:flex;gap:12px;flex-wrap:wrap;margin-top:14px;font-size:11px;color:var(--sc-muted)">
    @foreach(['Scheduled'=>'#2563eb','Confirmed'=>'#16a34a','Checked-In'=>'#ca8a04','In-Progress'=>'#ea580c','Completed'=>'#15803d','Cancelled'=>'#dc2626','No-Show'=>'#6b7280','Rescheduled'=>'#7c3aed'] as $s => $c)
    <span style="display:flex;align-items:center;gap:4px"><span style="width:10px;height:10px;background:{{ $c }};border-radius:2px;display:inline-block"></span>{{ $s }}</span>
    @endforeach
</div>
@endsection

@push('styles')
<style>
#calendar { --fc-border-color:var(--sc-border); --fc-today-bg-color:rgba(10,180,180,.05); font-size:13px; }
#calendar .fc-event { cursor:grab; }
#calendar .fc-event:active { cursor:grabbing; }
#calendar .fc-timegrid-slot { height:1.7em; }
#calendar .fc-event-title, #calendar .fc-event-time { white-space:normal; line-height:1.2; }
#calendar .fc-toolbar-title { font-size:18px; color:var(--sc-navy); }
#calendar .fc-button-primary { background:var(--sc-surface); border-color:var(--sc-border); color:var(--sc-navy); text-transform:capitalize; }
#calendar .fc-button-primary:not(:disabled).fc-button-active,
#calendar .fc-button-primary:hover { background:var(--sc-teal); border-color:var(--sc-teal); color:#fff; }
.sc-cal-tip { position:absolute; z-index:9999; max-width:270px; background:#0f172a; color:#fff;
    font-size:12px; line-height:1.4; padding:8px 10px; border-radius:6px;
    box-shadow:0 6px 18px rgba(0,0,0,.28); pointer-events:none; }
.sc-cal-tip .muted { color:#cbd5e1; }
.sc-cal-tip .j { margin-top:5px; color:#86efac; }
.sc-cal-tip .cl { margin-top:5px; padding-top:5px; border-top:1px solid #334155; color:#e2e8f0; }
.sc-cal-tip .cl b { color:#fff; }
.sc-cal-tip .hint { margin-top:5px; font-style:italic; color:#fbbf24; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const CSRF = '{{ csrf_token() }}';
    const EVENTS_URL = "{{ route('appointments.calendar-events') }}";
    const MOVE_BASE  = "{{ url('/appointments') }}"; // + /{id}/move|/follow-up
    let doctorId = "{{ $doctorId }}";

    const escapeHtml = s => String(s ?? '').replace(/[&<>"']/g, c =>
        ({ '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;' }[c]));

    const pad = n => String(n).padStart(2, '0');
    const ymd = d => d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
    const hms = d => pad(d.getHours()) + ':' + pad(d.getMinutes()) + ':' + pad(d.getSeconds());

    const calendarEl = document.getElementById('calendar');
    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: '{{ $initialView }}',
        initialDate: '{{ $ref->toDateString() }}',
        timeZone: 'local',
        height: 'auto',
        nowIndicator: true,
        allDaySlot: false,
        slotDuration: '00:15:00',
        slotMinTime: '07:00:00',
        slotMaxTime: '21:00:00',
        scrollTime: '16:00:00',
        firstDay: 1,
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'timeGridDay,timeGridWeek,dayGridMonth,multiMonthYear'
        },
        buttonText: { today:'Today', day:'Day', week:'Week', month:'Month', year:'Year' },
        views: { multiMonthYear: { multiMonthMaxColumns: 4 } },
        editable: true,
        eventStartEditable: true,
        eventDurationEditable: true,
        eventMinHeight: 46,          // keep the box tall enough to show patient + doctor
        slotEventOverlap: false,
        events: function (info, success, failure) {
            const url = new URL(EVENTS_URL, window.location.origin);
            url.searchParams.set('start', info.startStr);
            url.searchParams.set('end', info.endStr);
            if (doctorId) url.searchParams.set('doctor_id', doctorId);
            fetch(url, { headers: { 'Accept': 'application/json' } })
                .then(r => r.json()).then(success).catch(failure);
        },
        eventDrop: function (info) { onDrag(info, false); },
        eventResize: function (info) { onDrag(info, true); },
        eventMouseEnter: showTip,
        eventMouseLeave: hideTip,
    });
    calendar.render();

    // ── Hover bubble: patient status + visit journey ──────────────────────
    let tip = null;
    function showTip(info) {
        const e = info.event, p = e.extendedProps;
        const t = e.start ? e.start.toLocaleTimeString(undefined, { hour:'numeric', minute:'2-digit' }) : '';
        let html = `<b>${escapeHtml(p.patient || e.title)}</b>`;
        if (p.doctor) html += ` <span class="muted">· ${escapeHtml(p.doctor)}</span>`;
        html += `<br><span class="muted">${escapeHtml(t)}${p.type ? ' · ' + escapeHtml(p.type) : ''} · ${escapeHtml(p.status || '')}</span>`;
        const j = [];
        if (p.checked_in)   j.push(`✓ Checked in ${escapeHtml(p.checked_in)}`);
        if (p.encounter_id) j.push(`✓ Encounter #${p.encounter_id}`);
        if (p.completed)    j.push(`✓ Completed ${escapeHtml(p.completed)}`);
        if (j.length) html += `<div class="j">${j.join('<br>')}</div>`;
        // Clinical summary from the linked encounter (diagnosis / treatment / labs).
        if (p.clinical) {
            const c = p.clinical, cl = [];
            if (c.diagnosis) cl.push(`<b>Dx:</b> ${escapeHtml(c.diagnosis)}`);
            if (c.treatment) cl.push(`<b>Plan:</b> ${escapeHtml(c.treatment)}`);
            if (c.rx_count)  cl.push(`<b>Rx:</b> ${c.rx_count} medicine(s)`);
            if (c.labs)      cl.push(`<b>Labs:</b> ${escapeHtml(c.labs)}`);
            if (cl.length) html += `<div class="cl">${cl.join('<br>')}</div>`;
        }
        if (p.locked) html += `<div class="hint">Already seen — drag to book a follow-up</div>`;
        tip = document.createElement('div');
        tip.className = 'sc-cal-tip';
        tip.innerHTML = html;
        document.body.appendChild(tip);
        if (info.jsEvent) {
            tip.style.left = (info.jsEvent.pageX + 12) + 'px';
            tip.style.top  = (info.jsEvent.pageY + 12) + 'px';
        }
    }
    function hideTip() { if (tip) { tip.remove(); tip = null; } }

    // Route a drag/resize: a visit that already happened is a historical record,
    // so we never move it — offer a follow-up at the drop target instead.
    function onDrag(info, isResize) {
        hideTip();
        if (info.event.extendedProps.locked) {
            if (isResize) {
                alert('This visit has already happened — its time can’t be changed here.');
                info.revert();
            } else {
                offerFollowUp(info);
            }
            return;
        }
        applyMove(info, isResize);
    }

    function offerFollowUp(info) {
        const e = info.event, p = e.extendedProps;
        const dropStart = new Date(e.start);          // capture BEFORE revert
        info.revert();                                // original visit stays put + visible
        if (ymd(dropStart) < ymd(new Date())) {       // block past DATES only (today is fine)
            alert(`Can’t book a follow-up before today.\nDropped on: ${ymd(dropStart)}\nToday: ${ymd(new Date())}`);
            return;
        }
        // No confirmation prompt — book the follow-up straight away; the new
        // event appearing (and the original staying put) is the feedback.
        fetch(`${MOVE_BASE}/${e.id}/follow-up`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ date: ymd(dropStart), time: hms(dropStart) }),
        }).then(async r => {
            const res = await r.json().catch(() => ({}));
            if (!r.ok || !res.ok) throw new Error(res.message || 'Could not book follow-up');
            calendar.refetchEvents();                 // re-render: original + new follow-up both show
        }).catch(err => alert(err.message || 'Could not book follow-up.'));
    }

    function applyMove(info, isResize) {
        const e = info.event;
        const start = e.start;
        if (ymd(start) < ymd(new Date())) {           // can't reschedule into the past
            alert(`Can’t move to a past date.\nDropped on: ${ymd(start)}\nToday: ${ymd(new Date())}`);
            info.revert();
            return;
        }
        // No confirmation prompt — apply the move/resize immediately (like
        // Google Calendar); only surface a popup if the save fails.
        const end = e.end || new Date(start.getTime() + 15 * 60000);
        const duration = Math.max(5, Math.round((end - start) / 60000));

        fetch(`${MOVE_BASE}/${e.id}/move`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ date: ymd(start), time: hms(start), duration: duration }),
        }).then(async r => {
            const res = await r.json().catch(() => ({}));
            if (!r.ok || !res.ok) throw new Error(res.message || 'save failed');
        }).catch(err => {
            alert(err.message || 'Could not reschedule — reverting.');
            info.revert();
        });
    }

    document.getElementById('cal-doctor').addEventListener('change', function () {
        doctorId = this.value;
        calendar.refetchEvents();
    });
});
</script>
@endpush
