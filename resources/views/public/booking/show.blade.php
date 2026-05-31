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
</style>
@endpush

@section('content')
<div class="hero">
    <h1>Book your appointment online</h1>
    <p>Choose your doctor, pick a date, and we'll confirm by phone shortly. Takes about 30 seconds.</p>
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
        {{-- LEFT: Doctor + Date --}}
        <div class="card">
            <div class="card-body">
                <div class="section-title"><i class="fa-solid fa-user-doctor"></i> Choose a Doctor</div>
                <div class="doctor-list">
                    @forelse($doctors as $doctor)
                    <label class="doctor-card" data-doctor-card>
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
                            </div>
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
                    <div class="section-title"><i class="fa-solid fa-calendar-day"></i> Pick a Date &amp; Time</div>
                    <div class="row-2">
                        <div class="form-group" style="margin-bottom:0">
                            <label class="form-label" for="appointment_date">Preferred Date *</label>
                            <input type="date" id="appointment_date" name="appointment_date" class="form-control" required
                                   min="{{ now()->toDateString() }}"
                                   max="{{ now()->addDays(60)->toDateString() }}"
                                   value="{{ old('appointment_date', now()->toDateString()) }}">
                        </div>
                        <div class="form-group" style="margin-bottom:0">
                            <label class="form-label" for="appointment_time">Preferred Time</label>
                            <input type="time" id="appointment_time" name="appointment_time" class="form-control"
                                   value="{{ old('appointment_time') }}">
                            <div class="helper">Optional — we'll confirm a slot.</div>
                        </div>
                    </div>
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
// Visual feedback for selected doctor card
document.querySelectorAll('[data-doctor-card]').forEach(card => {
    const radio = card.querySelector('input[type=radio]');
    const sync = () => {
        document.querySelectorAll('[data-doctor-card]').forEach(c => c.classList.toggle('selected', c.querySelector('input[type=radio]').checked));
    };
    card.addEventListener('click', () => { radio.checked = true; sync(); });
    if (radio.checked) sync();
});
</script>
@endpush
@endsection
