{{-- FILE: resources/views/doctors/edit.blade.php --}}
@extends('layouts.app')
@section('title','Edit Doctor')
@section('page-title','Edit Doctor')
@section('content')
<div class="page-header"><div class="page-header-left"><h1>Edit — {{ $doctor->full_name }}</h1></div></div>
<form method="POST" action="{{ route('doctors.update', $doctor) }}" enctype="multipart/form-data">
@csrf @method('PUT')
<div class="card">
    <div class="card-header"><i class="fa-solid fa-user-doctor"></i> Professional Details</div>
    <div class="card-body">
        <div class="form-row cols-3">
            <div class="form-group"><label class="form-label">First Name</label><input type="text" name="first_name" class="form-control" value="{{ old('first_name', $doctor->first_name) }}" required></div>
            <div class="form-group"><label class="form-label">Last Name</label><input type="text" name="last_name" class="form-control" value="{{ old('last_name', $doctor->last_name) }}" required></div>
            <div class="form-group"><label class="form-label">Phone</label><input type="tel" name="phone" class="form-control" value="{{ old('phone', $doctor->phone) }}" required></div>
            <div class="form-group"><label class="form-label">Specialization</label><input type="text" name="specialization" class="form-control" value="{{ old('specialization', $doctor->specialization) }}" required></div>
            <div class="form-group"><label class="form-label">Qualification</label><input type="text" name="qualification" class="form-control" value="{{ old('qualification', $doctor->qualification) }}" required></div>
            <div class="form-group"><label class="form-label">Consultation Fee (₹)</label><input type="number" name="consultation_fee" class="form-control" value="{{ old('consultation_fee', $doctor->consultation_fee) }}" required></div>
            <div class="form-group"><label class="form-label">Monthly Salary (₹)</label><input type="number" name="salary" class="form-control" value="{{ old('salary', $doctor->salary) }}" min="0" step="0.01" placeholder="0 if no fixed monthly retainer"></div>
            <div class="form-group"><label class="form-label">Main Department</label><select name="department_id" class="form-control"><option value="">None</option>@foreach($departments as $d)<option value="{{ $d->id }}" {{ $doctor->department_id==$d->id?'selected':'' }}>{{ $d->name }}</option>@endforeach</select></div>
            <div class="form-group"><label class="form-label">Start Time</label><input type="time" name="start_time" class="form-control" value="{{ $doctor->start_time }}"></div>
            <div class="form-group"><label class="form-label">End Time</label><input type="time" name="end_time" class="form-control" value="{{ $doctor->end_time }}"></div>
        </div>
        @php $extraIds = old('extra_departments', $doctor->departments->pluck('id')->all()); @endphp
        <div class="form-group"><label class="form-label">Also works in <span class="text-muted text-small">(optional extra departments, e.g. a Diabetes clinic)</span></label>
            <div style="display:flex;flex-wrap:wrap;gap:8px">
                @foreach($departments as $d)
                <label style="display:flex;align-items:center;gap:6px;padding:6px 12px;background:var(--sc-bg);border-radius:6px;border:1px solid var(--sc-border);cursor:pointer;font-size:12.5px">
                    <input type="checkbox" name="extra_departments[]" value="{{ $d->id }}" {{ in_array($d->id, $extraIds) ? 'checked' : '' }}>
                    <i class="fa-solid {{ $d->icon ?: 'fa-hospital' }}" style="color:{{ $d->color ?: '#64748b' }}"></i> {{ $d->name }}
                </label>
                @endforeach
            </div>
        </div>
        <div class="form-row cols-3">
            <div class="form-group"><label class="form-label">Experience (years)</label><input type="number" name="experience_years" class="form-control" value="{{ old('experience_years', $doctor->experience_years) }}" min="0" max="70"></div>
            <div class="form-group"><label class="form-label">Languages spoken</label><input type="text" name="languages" class="form-control" value="{{ old('languages', $doctor->languages) }}" maxlength="150" placeholder="e.g. English, Hindi, Bengali"></div>
            <div class="form-group"><label class="form-label">Slot Duration (min)</label><input type="number" name="slot_duration" class="form-control" value="{{ old('slot_duration', $doctor->slot_duration ?? 15) }}" min="1" max="240"></div>
        </div>
        <div class="form-group"><label class="form-label">About the doctor <span class="text-muted text-small">(shown to patients when booking)</span></label>
            <textarea name="bio" class="form-control" rows="2" maxlength="2000">{{ old('bio', $doctor->bio) }}</textarea>
        </div>
        <div class="form-group">
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                <input type="checkbox" name="show_online" value="1" {{ old('show_online', $doctor->show_online) ? 'checked' : '' }}>
                <strong>Show this doctor in online booking</strong>
            </label>
        </div>
        <div class="form-group"><label class="form-label">Available Days <span class="text-muted text-small">(used only when no weekly timetable is set below)</span></label>
            <div style="display:flex;flex-wrap:wrap;gap:8px">
                @php $selDays = is_array($doctor->available_days) ? $doctor->available_days : (is_string($doctor->available_days) ? (json_decode($doctor->available_days, true) ?: []) : []); @endphp
                @foreach(['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'] as $day)
                <label style="display:flex;align-items:center;gap:6px;padding:6px 12px;background:var(--sc-bg);border-radius:6px;border:1px solid var(--sc-border);cursor:pointer;font-size:12.5px">
                    <input type="checkbox" name="available_days[]" value="{{ $day }}" {{ in_array($day,$selDays)?'checked':'' }}> {{ substr($day,0,3) }}
                </label>
                @endforeach
            </div>
        </div>
        <div class="form-group"><label class="form-label">Upload New Photo</label><input type="file" name="photo" class="form-control" accept="image/*"></div>
        <div class="form-group">
            <label class="form-label">Auto-Mark Present</label>
            <label style="display:flex;align-items:flex-start;gap:8px;padding:10px 12px;border:1.5px solid var(--sc-border);border-radius:6px;cursor:pointer;background:#f8fafc">
                <input type="checkbox" name="auto_present" value="1" {{ old('auto_present', $doctor->auto_present) ? 'checked' : '' }} style="margin-top:2px">
                <span>
                    <strong>Mark this doctor Present by default on every day</strong>
                    <div class="text-small text-muted" style="margin-top:2px">
                        System auto-creates a Present attendance record every day. Marking them Absent / Leave / Half-Day manually on a given day always takes precedence.
                    </div>
                </span>
            </label>
        </div>
    </div>
    <div class="card-footer">
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Save Changes</button>
        <a href="{{ route('doctors.show', $doctor) }}" class="btn btn-outline" style="margin-left:8px">Cancel</a>
    </div>
</div>
</form>

{{-- Weekly OPD timetable --}}
<div class="card" id="timetable" style="margin-top:16px">
    <div class="card-header"><i class="fa-solid fa-calendar-week"></i> Weekly OPD Timetable
        <div class="card-actions"><span class="text-small text-muted">{{ app(\App\Services\DoctorAvailability::class)->timetableLabel($doctor) ?: 'Not set' }}</span></div>
    </div>
    <form method="POST" action="{{ route('doctors.schedule', $doctor) }}" id="schedule-form">
        @csrf
        <div class="card-body">
            <p class="text-small text-muted" style="margin-top:0">One row per OPD session. A doctor can have several sessions a day (e.g. 10:00–13:00 and 17:00–20:00).
                Leave this empty to keep using the simple Available Days / Start / End above. <b>Max patients</b> closes booking for that session once reached.</p>
            <div class="table-wrap">
            <table class="sc-table" id="schedule-table">
                <thead><tr><th>Day</th><th>From</th><th>To</th><th>Slot (min)</th><th>Max patients</th><th>Clinic / Department</th><th>Room</th><th></th></tr></thead>
                <tbody></tbody>
            </table>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:10px">
                <button type="button" class="btn btn-sm btn-outline" onclick="addSession()"><i class="fa-solid fa-plus"></i> Add session</button>
                <button type="button" class="btn btn-sm btn-outline" onclick="copyMonday()" title="Copy Monday's sessions to Tuesday–Saturday"><i class="fa-solid fa-copy"></i> Copy Monday to Tue–Sat</button>
            </div>
        </div>
        <div class="card-footer"><button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Save Timetable</button></div>
    </form>
</div>

{{-- Leave --}}
<div class="card" style="margin-top:16px">
    <div class="card-header"><i class="fa-solid fa-plane-departure"></i> Leave &amp; Unavailable Days</div>
    <div class="card-body">
        <form method="POST" action="{{ route('doctors.leaves.store', $doctor) }}" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end">
            @csrf
            <div><label class="form-label" style="margin-bottom:4px">From</label><input type="date" name="from_date" class="form-control" required min="{{ today()->toDateString() }}"></div>
            <div><label class="form-label" style="margin-bottom:4px">To</label><input type="date" name="to_date" class="form-control" required min="{{ today()->toDateString() }}"></div>
            <div style="flex:1;min-width:180px"><label class="form-label" style="margin-bottom:4px">Reason</label><input type="text" name="reason" class="form-control" maxlength="150" placeholder="e.g. Conference, personal leave"></div>
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Add Leave</button>
        </form>
        <div class="table-wrap" style="margin-top:12px">
        <table class="sc-table">
            <thead><tr><th>From</th><th>To</th><th>Days</th><th>Reason</th><th></th></tr></thead>
            <tbody>
            @forelse($leaves as $lv)
                <tr style="{{ $lv->to_date->isPast() && !$lv->to_date->isToday() ? 'opacity:.55' : '' }}">
                    <td>{{ $lv->from_date->format('D, d M Y') }}</td>
                    <td>{{ $lv->to_date->format('D, d M Y') }}</td>
                    <td>{{ $lv->from_date->diffInDays($lv->to_date) + 1 }}</td>
                    <td>{{ $lv->reason ?: '—' }}</td>
                    <td>
                        <form method="POST" action="{{ route('doctors.leaves.destroy', [$doctor, $lv]) }}" onsubmit="return confirm('Remove this leave?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline" style="color:var(--sc-red)"><i class="fa-solid fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted" style="padding:20px">No upcoming leave.</td></tr>
            @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>

<script>
const SC_DAYS = {!! json_encode(\App\Models\DoctorSchedule::DAYS) !!};
const SC_DEPTS = {!! json_encode($departments->map(fn ($d) => ['id' => $d->id, 'name' => $d->name, 'room' => $d->room])->values()) !!};
const SC_ROOMS = {!! json_encode($rooms) !!};
const SC_MAIN_ROOM = {!! json_encode(optional($departments->firstWhere('id', $doctor->department_id))->room) !!};
let scRow = 0;
function addSession(s = {}) {
    const i = scRow++;
    const dayOpts = Object.entries(SC_DAYS).sort((a, b) => ((+a[0] || 7) - (+b[0] || 7)))
        .map(([v, n]) => `<option value="${v}" ${String(s.day_of_week ?? 1) === v ? 'selected' : ''}>${n}</option>`).join('');
    const depOpts = '<option value="">Main / any</option>' + SC_DEPTS.map(d => `<option value="${d.id}" ${s.department_id == d.id ? 'selected' : ''}>${d.name}</option>`).join('');
    const t = v => (v || '').substring(0, 5);
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td><select name="rows[${i}][day_of_week]" class="form-control form-control-sm">${dayOpts}</select></td>
        <td><input type="time" name="rows[${i}][start_time]" class="form-control form-control-sm" value="${t(s.start_time) || '10:00'}" required></td>
        <td><input type="time" name="rows[${i}][end_time]" class="form-control form-control-sm" value="${t(s.end_time) || '13:00'}" required></td>
        <td><input type="number" name="rows[${i}][slot_minutes]" class="form-control form-control-sm" value="${s.slot_minutes ?? ''}" min="1" max="240" placeholder="{{ $doctor->slot_duration ?? 15 }}" style="width:80px"></td>
        <td><input type="number" name="rows[${i}][max_patients]" class="form-control form-control-sm" value="${s.max_patients ?? ''}" min="1" max="500" placeholder="no limit" style="width:90px"></td>
        <td><select name="rows[${i}][department_id]" class="form-control form-control-sm" onchange="deptRoom(this)">${depOpts}</select></td>
        <td>${roomSelect(i, s.room ?? '')}</td>
        <td><button type="button" class="btn btn-sm btn-outline" style="color:var(--sc-red)" onclick="this.closest('tr').remove()"><i class="fa-solid fa-xmark"></i></button></td>`;
    document.querySelector('#schedule-table tbody').appendChild(tr);
    if (!s.room) deptRoom(tr.querySelector('[name$="[department_id]"]'));
}
// Room: dropdown of rooms in use; "New room…" lets you type one
const esc = v => String(v).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;');
function roomSelect(i, cur) {
    const rooms = [...new Set([...SC_ROOMS, ...(cur ? [cur] : [])])];
    return `<select name="rows[${i}][room]" class="form-control form-control-sm" style="width:120px" onchange="newRoom(this)">
        <option value="">—</option>
        ${rooms.map(r => `<option value="${esc(r)}" ${r === cur ? 'selected' : ''}>${esc(r)}</option>`).join('')}
        <option value="__new">+ New room…</option></select>`;
}
function newRoom(sel) {
    if (sel.value !== '__new') return;
    const r = (prompt('Room name / number (e.g. Room 4, C2):') || '').trim().substring(0, 60);
    if (!r) { sel.value = ''; return; }
    if (!SC_ROOMS.includes(r)) SC_ROOMS.push(r);
    if (![...sel.options].some(o => o.value === r)) sel.querySelector('option[value="__new"]').insertAdjacentHTML('beforebegin', `<option value="${esc(r)}">${esc(r)}</option>`);
    sel.value = r;
}
// picking a clinic/department fills its room when the room is still empty
function deptRoom(sel) {
    const room = sel.closest('tr').querySelector('[name$="[room]"]');
    const d = SC_DEPTS.find(x => String(x.id) === sel.value);
    const r = d ? d.room : SC_MAIN_ROOM;
    if (r && !room.value) {
        if (![...room.options].some(o => o.value === r)) room.querySelector('option[value="__new"]').insertAdjacentHTML('beforebegin', `<option value="${esc(r)}">${esc(r)}</option>`);
        room.value = r;
    }
}
function readRows() {
    return Array.from(document.querySelectorAll('#schedule-table tbody tr')).map(tr => {
        const g = n => tr.querySelector(`[name$="[${n}]"]`).value;
        return {day_of_week: g('day_of_week'), start_time: g('start_time'), end_time: g('end_time'), slot_minutes: g('slot_minutes'),
                max_patients: g('max_patients'), department_id: g('department_id'), room: g('room')};
    });
}
function copyMonday() {
    const rows = readRows(), mon = rows.filter(r => r.day_of_week === '1');
    if (!mon.length) { alert('Add Monday sessions first.'); return; }
    document.querySelector('#schedule-table tbody').innerHTML = '';
    rows.filter(r => !['2','3','4','5','6'].includes(r.day_of_week)).forEach(r => addSession(r));
    [2, 3, 4, 5, 6].forEach(d => mon.forEach(r => addSession({...r, day_of_week: d})));
}
{!! json_encode($doctor->schedules->map->only(['day_of_week','start_time','end_time','slot_minutes','max_patients','department_id','room'])->values()) !!}.forEach(s => addSession(s));
</script>
@endsection
