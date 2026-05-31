{{-- FILE: resources/views/staff/duty-roster.blade.php --}}
@extends('layouts.app')
@section('title','Duty Roster')
@section('page-title','Staff Duty Roster')
@section('content')

<div class="page-header">
    <div class="page-header-left">
        <h1>Duty Roster</h1>
        <div class="breadcrumb"><a href="{{ route('staff.index') }}">Staff</a> › Duty Roster</div>
    </div>
    <div style="display:flex;gap:8px;align-items:center">
        <a href="?week={{ $weekStart->copy()->subWeek()->toDateString() }}" class="btn btn-outline btn-sm"><i class="fa-solid fa-chevron-left"></i> Prev</a>
        <span style="font-size:13px;font-weight:600">{{ $weekStart->format('d M') }} – {{ $weekStart->copy()->endOfWeek()->format('d M Y') }}</span>
        <a href="?week={{ $weekStart->copy()->addWeek()->toDateString() }}" class="btn btn-outline btn-sm">Next <i class="fa-solid fa-chevron-right"></i></a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> {{ session('success') }}</div>
@endif

<div class="grid" style="grid-template-columns:1fr 3fr;gap:20px;align-items:start">
    {{-- Add assignment form --}}
    <div class="card">
        <div class="card-header"><i class="fa-solid fa-plus"></i> Add Assignment</div>
        <div class="card-body">
            <form method="POST" action="{{ route('staff.duty-roster.store') }}" id="roster-form">
                @csrf
                <input type="hidden" name="week_start" value="{{ $weekStart->toDateString() }}">
                <div class="form-group">
                    <label class="form-label">Staff Member</label>
                    <select name="staff_id" class="form-control" required>
                        <option value="">Select staff...</option>
                        @foreach($staff as $s)
                        <option value="{{ $s->id }}">{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" style="display:flex;align-items:center;justify-content:space-between">
                        <span>Days</span>
                        <span style="display:flex;gap:4px">
                            <button type="button" class="btn btn-sm btn-outline" style="font-size:10px;padding:2px 6px" onclick="setDays('weekdays')">Mon–Fri</button>
                            <button type="button" class="btn btn-sm btn-outline" style="font-size:10px;padding:2px 6px" onclick="setDays('weekends')">Sat–Sun</button>
                            <button type="button" class="btn btn-sm btn-outline" style="font-size:10px;padding:2px 6px" onclick="setDays('all')">All</button>
                            <button type="button" class="btn btn-sm btn-outline" style="font-size:10px;padding:2px 6px" onclick="setDays('none')">Clear</button>
                        </span>
                    </label>
                    <div id="day-chips" style="display:flex;flex-wrap:wrap;gap:5px">
                        @foreach(['monday'=>'Mon','tuesday'=>'Tue','wednesday'=>'Wed','thursday'=>'Thu','friday'=>'Fri','saturday'=>'Sat','sunday'=>'Sun'] as $d => $label)
                        <label class="day-chip" style="cursor:pointer;border:1.5px solid var(--sc-border);border-radius:18px;padding:5px 11px;font-size:12px;font-weight:600;background:#fff;transition:all .12s">
                            <input type="checkbox" name="days_of_week[]" value="{{ $d }}" style="display:none">
                            {{ $label }}
                        </label>
                        @endforeach
                    </div>
                    <div class="text-small text-muted" style="margin-top:4px">Tick one or more days. Quick-pick buttons fill in common patterns.</div>
                </div>

                <div class="form-group">
                    <label class="form-label">Shift</label>
                    @php
                        $sh = fn($k, $d) => setting($k, $d);
                        $shiftHours = [
                            'morning' => $sh('shift_morning_start','08:00').'–'.$sh('shift_morning_end','13:00'),
                            'evening' => $sh('shift_evening_start','16:00').'–'.$sh('shift_evening_end','18:30'),
                            'night'   => $sh('shift_night_start','22:00').'–'.$sh('shift_night_end','06:00'),
                        ];
                    @endphp
                    <select name="shift" id="shift-select" class="form-control" required onchange="toggleCustomTimes()">
                        <option value="morning">Morning ({{ $shiftHours['morning'] }})</option>
                        <option value="evening">Evening ({{ $shiftHours['evening'] }})</option>
                        <option value="night">Night ({{ $shiftHours['night'] }})</option>
                        <option value="custom">Custom hours…</option>
                    </select>
                    <div class="text-small text-muted" style="margin-top:4px">Edit defaults in <a href="{{ route('settings.index') }}" style="color:var(--sc-teal)">Settings → Hospital Info → Shift Hours</a>.</div>
                </div>

                <div class="form-group" id="custom-times" style="display:none">
                    <label class="form-label">Custom Hours</label>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
                        <div>
                            <input type="time" name="start_time" id="start_time" class="form-control" placeholder="Start">
                            <div class="text-small text-muted" style="margin-top:3px">Start</div>
                        </div>
                        <div>
                            <input type="time" name="end_time" id="end_time" class="form-control" placeholder="End">
                            <div class="text-small text-muted" style="margin-top:3px">End (next day if before start)</div>
                        </div>
                    </div>
                </div>
                <script>
                    function toggleCustomTimes() {
                        const sel = document.getElementById('shift-select');
                        const box = document.getElementById('custom-times');
                        const isCustom = sel.value === 'custom';
                        box.style.display = isCustom ? '' : 'none';
                        document.getElementById('start_time').required = isCustom;
                        document.getElementById('end_time').required   = isCustom;
                    }
                </script>

                <div class="form-group">
                    <label class="form-label">Apply To</label>
                    <div style="display:flex;gap:6px;background:var(--sc-bg);border-radius:8px;padding:4px">
                        <label style="flex:1;cursor:pointer;text-align:center;padding:8px 6px;border-radius:6px;font-size:12px;font-weight:600" class="scope-pill">
                            <input type="radio" name="apply_scope" value="week" checked style="display:none">
                            Just this week
                            <div class="text-small text-muted" style="font-weight:400;font-size:10px;margin-top:2px">{{ $weekStart->format('d M') }} – {{ $weekStart->copy()->endOfWeek()->format('d M') }}</div>
                        </label>
                        <label style="flex:1;cursor:pointer;text-align:center;padding:8px 6px;border-radius:6px;font-size:12px;font-weight:600" class="scope-pill">
                            <input type="radio" name="apply_scope" value="month" style="display:none">
                            All weeks in {{ $weekStart->format('M Y') }}
                            <div class="text-small text-muted" style="font-weight:400;font-size:10px;margin-top:2px">Repeats across the month</div>
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Ward (optional)</label>
                    <select name="ward_id" class="form-control">
                        <option value="">Any</option>
                        @foreach($wards as $w)
                        <option value="{{ $w->id }}">{{ $w->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Notes</label>
                    <input type="text" name="notes" class="form-control" placeholder="Optional note">
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%"><i class="fa-solid fa-floppy-disk"></i> Save Assignment(s)</button>
            </form>

            <style>
                .day-chip.is-on { background:var(--sc-teal); color:#fff; border-color:var(--sc-teal); }
                .day-chip:hover { border-color:var(--sc-teal); }
                .scope-pill input:checked + * , .scope-pill.is-on { /* fallback */ }
                .scope-pill.is-on { background:#fff; box-shadow:var(--shadow); color:var(--sc-teal); }
            </style>
            <script>
                (function(){
                    const chips = document.querySelectorAll('#day-chips .day-chip');
                    const sync = () => chips.forEach(c => c.classList.toggle('is-on', c.querySelector('input').checked));
                    chips.forEach(c => {
                        c.addEventListener('click', e => {
                            if (e.target.tagName === 'INPUT') return;
                            const cb = c.querySelector('input');
                            cb.checked = !cb.checked;
                            sync();
                        });
                    });
                    window.setDays = (mode) => {
                        chips.forEach(c => {
                            const v = c.querySelector('input').value;
                            const inWeekday = ['monday','tuesday','wednesday','thursday','friday'].includes(v);
                            const inWeekend = ['saturday','sunday'].includes(v);
                            c.querySelector('input').checked = (
                                mode === 'all' ||
                                (mode === 'weekdays' && inWeekday) ||
                                (mode === 'weekends' && inWeekend)
                            );
                        });
                        sync();
                    };

                    const scopes = document.querySelectorAll('.scope-pill');
                    const syncScope = () => scopes.forEach(p => p.classList.toggle('is-on', p.querySelector('input').checked));
                    scopes.forEach(p => p.addEventListener('click', () => { p.querySelector('input').checked = true; syncScope(); }));
                    syncScope();
                })();
            </script>
        </div>
    </div>

    {{-- Roster Grid --}}
    <div class="card">
        <div class="card-header"><i class="fa-solid fa-table"></i> Weekly Schedule</div>
        <div style="overflow-x:auto">
            <table class="sc-table" style="min-width:700px">
                <thead>
                    <tr>
                        <th style="width:90px">Shift</th>
                        @foreach(['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $d)
                        <th>{{ $d }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                @php
                    $shiftDots = ['morning'=>'#f59e0b','evening'=>'#6366f1','night'=>'#334155','custom'=>'#0ab4b4'];
                    $shiftLabels = [
                        'morning' => 'Morning · '.setting('shift_morning_start','08:00').'–'.setting('shift_morning_end','13:00'),
                        'evening' => 'Evening · '.setting('shift_evening_start','16:00').'–'.setting('shift_evening_end','18:30'),
                        'night'   => 'Night · '.setting('shift_night_start','22:00').'–'.setting('shift_night_end','06:00'),
                        'custom'  => 'Custom',
                    ];
                @endphp
                @foreach($shiftLabels as $shift => $label)
                <tr>
                    <td style="font-weight:700;color:var(--sc-muted);font-size:10px;text-transform:uppercase;letter-spacing:.4px;background:var(--sc-surface);white-space:nowrap;width:130px">
                        <i class="fa-solid fa-circle" style="font-size:8px;margin-right:4px;color:{{ $shiftDots[$shift] }}"></i>{{ $label }}
                    </td>
                    @foreach(['monday','tuesday','wednesday','thursday','friday','saturday','sunday'] as $day)
                    <td style="vertical-align:top;padding:6px;min-width:90px">
                        @foreach($grid[$shift][$day] as $entry)
                        <div style="display:flex;justify-content:space-between;align-items:center;background:#f8fafc;border:1px solid var(--sc-border);border-radius:5px;padding:3px 6px;margin-bottom:3px;font-size:11px">
                            <div>
                                <div style="font-weight:600">{{ $entry->staff->name }}</div>
                                @if($entry->shift === 'custom' && $entry->time_range)
                                <div style="color:var(--sc-teal);font-weight:600;font-family:'DM Mono',monospace;font-size:10px">{{ $entry->time_range }}</div>
                                @endif
                                @if($entry->ward)<div style="color:var(--sc-muted)">{{ $entry->ward->name }}</div>@endif
                            </div>
                            <form method="POST" action="{{ route('staff.duty-roster.destroy', $entry) }}" style="margin:0">
                                @csrf @method('DELETE')
                                <button type="submit" style="background:none;border:none;color:var(--sc-red);cursor:pointer;padding:2px" title="Remove"><i class="fa-solid fa-xmark"></i></button>
                            </form>
                        </div>
                        @endforeach
                    </td>
                    @endforeach
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection
