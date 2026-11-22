@extends('public.layout')
@section('title', 'Book an Appointment')
@section('meta_description', 'Book your appointment online with ' . ($clinic['name'] ?? config('app.name')) . ' in just 30 seconds. Choose a doctor, pick a date, get a confirmation.')

@push('head')
<style>
.hero { text-align:center; padding:24px 0 32px; }
.hero h1 { font-size:32px; font-weight:700; line-height:1.2; margin-bottom:10px; }
.hero p { color:var(--c-muted); font-size:16px; max-width:580px; margin:0 auto; }

.booking-grid { display:grid; grid-template-columns:1fr 1fr; gap:22px; }
@media (max-width:880px) { .booking-grid { grid-template-columns:1fr; } }

.section-title { font-size:14px; font-weight:700; text-transform:uppercase; letter-spacing:0.5px; color:var(--c-muted); margin-bottom:14px; display:flex; align-items:center; gap:8px; }
.section-title i { color:var(--c-teal); }

.doctor-list { display:flex; flex-direction:column; gap:10px; }
.doctor-card { display:flex; gap:12px; padding:14px; border:2px solid var(--c-border); border-radius:var(--radius); background:#fff; cursor:pointer; transition:all .15s; align-items:center; }
.doctor-card:hover { border-color:var(--c-teal); transform:translateY(-1px); box-shadow:var(--shadow); }
.doctor-card.selected { border-color:var(--c-teal); background:rgba(10,180,180,0.05); }
.doctor-card input[type=radio] { display:none; }
.doctor-avatar { width:54px; height:54px; border-radius:50%; background:var(--c-bg); display:flex; align-items:center; justify-content:center; flex-shrink:0; overflow:hidden; }
.doctor-avatar img { width:100%; height:100%; object-fit:cover; }
.doctor-avatar i { font-size:22px; color:var(--c-muted); }
.doctor-info { flex:1; min-width:0; }
.doctor-name { font-weight:600; font-size:15px; }
.doctor-spec { font-size:12px; color:var(--c-muted); margin-top:2px; }
.doctor-fee { font-size:12px; color:var(--c-teal); font-weight:600; margin-top:4px; }

.row-2 { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
.row-3 { display:grid; grid-template-columns:1fr 1fr 1fr; gap:12px; }
@media (max-width:480px) { .row-2, .row-3 { grid-template-columns:1fr; } }

.flash-error { background:#fef2f2; border:1px solid #fecaca; color:#991b1b; border-radius:8px; padding:10px 14px; margin-bottom:14px; font-size:13px; }
.helper { font-size:11px; color:var(--c-muted); margin-top:4px; }
.step-no { width:22px; height:22px; border-radius:50%; background:var(--c-teal); color:#fff; display:inline-flex; align-items:center; justify-content:center; font-size:12px; }
.spec-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(120px, 1fr)); gap:8px; }
.spec-chip { display:flex; flex-direction:column; align-items:center; gap:4px; padding:12px 6px; border:2px solid var(--c-border); border-radius:var(--radius); background:#fff; cursor:pointer; font:inherit; text-align:center; transition:all .15s; }
.spec-chip i { font-size:20px; color:var(--dc); }
.spec-chip span { font-size:13px; font-weight:600; line-height:1.2; }
.spec-chip small { font-size:11px; color:var(--c-muted); }
.spec-chip:hover { border-color:var(--dc); transform:translateY(-1px); }
.spec-chip.selected { border-color:var(--dc); background:color-mix(in srgb, var(--dc) 8%, #fff); }
.slot-grid { display:flex; flex-wrap:wrap; gap:6px; margin-top:6px; }
.slot-btn { padding:7px 10px; border:1.5px solid var(--c-border); border-radius:8px; background:#fff; cursor:pointer; font:inherit; font-size:13px; min-width:64px; }
.slot-btn:hover:not(:disabled) { border-color:var(--c-teal); }
.slot-btn.selected { background:var(--c-teal); border-color:var(--c-teal); color:#fff; }
.slot-btn:disabled { opacity:.4; cursor:not-allowed; text-decoration:line-through; }
</style>
@endpush

@section('content')
<div class="hero">
    <h1>Book your appointment online</h1>
    <p>Choose a speciality and doctor, pick a free time, and we'll confirm by phone shortly. Takes about 30 seconds.</p>
</div>

@if($errors->any())
<div class="flash-error">
    <i class="fa-solid fa-circle-exclamation"></i>
    <strong>Please fix the highlighted fields.</strong>
    <ul style="margin:6px 0 0 18px; padding:0; font-size:12px">
        @foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach
    </ul>
</div>
@endif

<form method="POST" action="{{ route('public.book.store') }}">
    @csrf
    {{-- Honeypot for bots --}}
    <div class="honeypot" aria-hidden="true">
        <label>Leave this empty <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
    </div>

    <div class="booking-grid">
        {{-- LEFT: Speciality → Doctor → Date & slot --}}
        <div class="card">
            <div class="card-body">
                @if($departments->count() > 1)
                <div class="section-title"><span class="step-no">1</span> Choose a Speciality</div>
                <input type="hidden" name="department_id" id="department_id" value="{{ old('department_id') }}">
                <div class="spec-grid">
                    @foreach($departments as $dep)
                    <button type="button" class="spec-chip" data-dept="{{ $dep->id }}" style="--dc:{{ $dep->color ?: '#0ab4b4' }}" onclick="pickDept('{{ $dep->id }}')">
                        <i class="fa-solid {{ $dep->icon ?: 'fa-hospital' }}"></i>
                        <span>{{ $dep->name }}</span>
                        <small>{{ $dep->bookable }} {{ $dep->bookable > 1 ? 'doctors' : 'doctor' }}</small>
                    </button>
                    @endforeach
                </div>
                <div style="margin-top:20px"></div>
                @else
                <input type="hidden" name="department_id" id="department_id" value="{{ old('department_id', $departments->first()?->id) }}">
                @endif

                <div class="section-title"><span class="step-no">{{ $departments->count() > 1 ? 2 : 1 }}</span> Choose a Doctor</div>
                <div id="doctor-hint" class="helper" style="margin:-6px 0 10px; {{ $departments->count() > 1 ? '' : 'display:none' }}">Pick a speciality above to see its doctors.</div>
                <div class="doctor-list">
                    <label class="doctor-card" data-doctor-card data-any style="display:none">
                        <input type="radio" name="doctor_id" value="any" required {{ old('doctor_id') === 'any' ? 'checked' : '' }}>
                        <div class="doctor-avatar"><i class="fa-solid fa-user-group"></i></div>
                        <div class="doctor-info">
                            <div class="doctor-name">Any available doctor</div>
                            <div class="doctor-spec">Earliest free time in this speciality</div>
                        </div>
                    </label>
                    @forelse($doctors as $doctor)
                    <label class="doctor-card" data-doctor-card data-depts="{{ implode(',', $doctor->departmentIds()) }}" {!! $departments->count() > 1 ? 'style="display:none"' : '' !!}>
                        <input type="radio" name="doctor_id" value="{{ $doctor->id }}" required {{ old('doctor_id') == $doctor->id ? 'checked' : '' }}>
                        <div class="doctor-avatar">
                            @if($doctor->photo)
                                <img src="{{ asset('storage/'.$doctor->photo) }}" alt="">
                            @else
                                <i class="fa-solid fa-user-doctor"></i>
                            @endif
                        </div>
                        <div class="doctor-info">
                            <div class="doctor-name">Dr. {{ $doctor->first_name }} {{ $doctor->last_name }}</div>
                            <div class="doctor-spec">
                                {{ $doctor->specialization ?: ($doctor->department->name ?? 'General Practitioner') }}
                                @if($doctor->qualification) · {{ $doctor->qualification }} @endif
                                @if($doctor->experience_years) · {{ $doctor->experience_years }} yrs experience @endif
                            </div>
                            @if($timetables[$doctor->id])<div class="doctor-spec" data-timetable><i class="fa-regular fa-clock"></i> <span>{{ $timetables[$doctor->id] }}</span></div>@endif
                            @if($doctor->languages)<div class="doctor-spec"><i class="fa-solid fa-language"></i> {{ $doctor->languages }}</div>@endif
                            @if($doctor->consultation_fee > 0)
                            <div class="doctor-fee">Consultation: ₹{{ number_format($doctor->consultation_fee, 0) }}</div>
                            @endif
                        </div>
                    </label>
                    @empty
                    <div style="color:var(--c-muted); padding:20px; text-align:center">No doctors available right now. Please call us.</div>
                    @endforelse
                </div>

                <div style="margin-top:20px">
                    <div class="section-title"><span class="step-no">{{ $departments->count() > 1 ? 3 : 2 }}</span> Pick a Date &amp; Time</div>
                    <div class="form-group" style="margin-bottom:10px">
                        <label class="form-label" for="appointment_date">Date *</label>
                        <input type="date" id="appointment_date" name="appointment_date" class="form-control" required
                               min="{{ now()->toDateString() }}"
                               max="{{ now()->addDays(59)->toDateString() }}"
                               value="{{ old('appointment_date', now()->toDateString()) }}" onchange="loadSlots()">
                    </div>
                    <input type="hidden" id="appointment_time" name="appointment_time" value="{{ old('appointment_time') }}">
                    <div id="slot-box" class="helper" style="font-size:12px">Choose a doctor to see free times.</div>
                </div>
            </div>
        </div>

        {{-- RIGHT: Patient details --}}
        <div class="card">
            <div class="card-body">
                <div class="section-title"><i class="fa-solid fa-id-card"></i> Your Details</div>

                <div class="form-group">
                    <label class="form-label" for="name">Full Name *</label>
                    <input type="text" id="name" name="name" class="form-control" required maxlength="120"
                           placeholder="As on ID" value="{{ old('name') }}">
                </div>

                <div class="form-group">
                    <label class="form-label" for="phone">Mobile Number *</label>
                    <input type="tel" id="phone" name="phone" class="form-control" required maxlength="20"
                           placeholder="10-digit mobile" inputmode="numeric"
                           pattern="[0-9 +\-]{10,20}" value="{{ old('phone') }}">
                    <div class="helper">We'll call you on this number to confirm.</div>
                </div>

                <div class="row-3">
                    <div class="form-group">
                        <label class="form-label" for="age">Age *</label>
                        <input type="number" id="age" name="age" class="form-control" required min="0" max="120"
                               value="{{ old('age') }}">
                    </div>
                    <div class="form-group" style="grid-column:span 2">
                        <label class="form-label" for="gender">Gender *</label>
                        <select name="gender" id="gender" class="form-control" required>
                            <option value="">Select…</option>
                            <option value="Male"   {{ old('gender') === 'Male'   ? 'selected' : '' }}>Male</option>
                            <option value="Female" {{ old('gender') === 'Female' ? 'selected' : '' }}>Female</option>
                            <option value="Other"  {{ old('gender') === 'Other'  ? 'selected' : '' }}>Other</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="reason">Reason for Visit (optional)</label>
                    <textarea name="reason" id="reason" class="form-control" rows="3" maxlength="300"
                              placeholder="Briefly tell us your symptoms or what brings you in…">{{ old('reason') }}</textarea>
                </div>

                <button type="submit" class="btn btn-primary btn-lg" style="width:100%; justify-content:center">
                    <i class="fa-solid fa-calendar-check"></i> Request Appointment
                </button>

                <div class="helper" style="margin-top:10px; text-align:center">
                    <i class="fa-solid fa-shield-halved" style="color:var(--c-teal)"></i>
                    Your information is private and used only to schedule your visit.
                </div>
            </div>
        </div>
    </div>
</form>

@push('scripts')
<script>
const SLOT_URL = @json(route('public.book.slots'));
let OLD_TIME = @json(old('appointment_time'));
const DEPT_TT = {!! json_encode($deptTimetables) !!};
const cards = () => document.querySelectorAll('[data-doctor-card]');
function syncCards() { cards().forEach(c => c.classList.toggle('selected', c.querySelector('input[type=radio]').checked)); }

// Step 1: speciality → show its doctors (+ "any available" when it has several)
function pickDept(id) {
    document.getElementById('department_id').value = id;
    document.querySelectorAll('.spec-chip').forEach(c => c.classList.toggle('selected', c.dataset.dept === id));
    let n = 0;
    cards().forEach(c => {
        if (c.hasAttribute('data-any')) return;
        const ok = (c.dataset.depts || '').split(',').includes(id);
        c.style.display = ok ? '' : 'none';
        if (ok) n++;
        else c.querySelector('input').checked = false;
    });
    // show each doctor's hours for this speciality
    cards().forEach(c => {
        const span = c.querySelector('[data-timetable] span'), docId = c.querySelector('input').value;
        if (span && DEPT_TT[id]?.[docId]) span.textContent = DEPT_TT[id][docId];
    });
    const any = document.querySelector('[data-any]');
    any.style.display = n > 1 ? '' : 'none';
    if (n <= 1) any.querySelector('input').checked = false;
    document.getElementById('doctor-hint').style.display = 'none';
    // a single doctor: select straight away
    const vis = Array.from(cards()).filter(c => c.style.display !== 'none' && !c.hasAttribute('data-any'));
    if (vis.length === 1) vis[0].querySelector('input').checked = true;
    syncCards(); loadSlots();
}

// Step 2: doctor
cards().forEach(card => card.addEventListener('click', () => {
    card.querySelector('input[type=radio]').checked = true; syncCards(); loadSlots();
}));

// Step 3: free times for that doctor (or any doctor of the speciality) on the date
function loadSlots() {
    const box = document.getElementById('slot-box');
    const doc = document.querySelector('input[name=doctor_id]:checked')?.value;
    const date = document.getElementById('appointment_date').value;
    const keep = document.getElementById('appointment_time').value || OLD_TIME;
    document.getElementById('appointment_time').value = '';
    if (!doc || !date) { box.textContent = 'Choose a doctor to see free times.'; return; }
    box.textContent = 'Loading free times…';
    const qs = new URLSearchParams({doctor_id: doc, date, department_id: document.getElementById('department_id').value || ''});
    fetch(SLOT_URL + '?' + qs).then(r => r.json()).then(d => {
        if (!d.slots || !d.slots.length || !d.slots.some(s => s.available)) {
            box.innerHTML = `<span style="color:#b45309"><i class="fa-solid fa-circle-info"></i> ${d.note || 'No free times this day.'}</span>`;
            return;
        }
        box.innerHTML = '<div>Tap a time (optional, we will confirm by phone):</div><div class="slot-grid">' +
            d.slots.map(s => `<button type="button" class="slot-btn" ${s.available ? '' : 'disabled'} data-t="${s.time}">${s.time}</button>`).join('') + '</div>';
        box.querySelectorAll('.slot-btn:not(:disabled)').forEach(b => b.addEventListener('click', () => {
            box.querySelectorAll('.slot-btn').forEach(x => x.classList.remove('selected'));
            b.classList.add('selected');
            document.getElementById('appointment_time').value = b.dataset.t;
        }));
        if (keep) { OLD_TIME = null; }
        if (keep) box.querySelector(`.slot-btn[data-t="${keep}"]:not(:disabled)`)?.click();
    }).catch(() => { box.textContent = 'Could not load times. You can still send the request; we will call you.'; });
}

// Restore after a validation error
document.addEventListener('DOMContentLoaded', () => {
    const dep = document.getElementById('department_id').value;
    const chosen = document.querySelector('input[name=doctor_id]:checked');
    if (dep && document.querySelector('.spec-chip')) { pickDept(dep); if (chosen) { chosen.checked = true; syncCards(); loadSlots(); } }
    else if (chosen) { syncCards(); loadSlots(); }
});
</script>
@endpush
@endsection
