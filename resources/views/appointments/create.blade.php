{{-- FILE: resources/views/appointments/create.blade.php --}}
@extends('layouts.app')
@section('title','Book Appointment')
@section('page-title','Book Appointment')
@section('content')
<div class="page-header">
    <div class="page-header-left"><h1>Book Appointment</h1></div>
</div>

<form method="POST" action="{{ route('appointments.store') }}" id="appt-form">
@csrf
<div class="grid" style="grid-template-columns:1fr 320px;gap:20px;align-items:start">
<div>

<!-- Patient -->
<div class="card mb-3 appt-patient-card">
    <div class="card-header"><i class="fa-solid fa-user"></i> Select Patient</div>
    <div class="card-body">
        @if(isset($patient) && $patient)
            <div class="patient-strip" style="padding:12px">
                <div class="ps-avatar">{{ strtoupper(substr($patient->first_name,0,1)) }}</div>
                <div><div class="ps-name">{{ $patient->full_name }}</div><div class="ps-jhc_id">{{ $patient->jhc_id }}</div>
                <div class="ps-meta"><span>{{ $patient->age }}</span><span>{{ $patient->gender }}</span><span>{{ $patient->phone }}</span></div></div>
            </div>
            <input type="hidden" name="patient_id" value="{{ $patient->id }}">
        @else
            <select name="patient_id" id="appt-patient-select" class="form-control select2" required style="width:100%">
                <option value="">Search patient by name, JHC ID, or phone...</option>
                @foreach($patients as $p)
                    <option value="{{ $p->id }}" {{ old('patient_id') == $p->id ? 'selected' : '' }}>
                        {{ $p->first_name }} {{ $p->last_name }}{{ $p->jhc_id ? ' - '.$p->jhc_id : '' }}{{ $p->phone ? ' - '.$p->phone : '' }}
                    </option>
                @endforeach
            </select>
        @endif
    </div>
</div>

<!-- Doctor & Time -->
<div class="card mb-3">
    <div class="card-header"><i class="fa-solid fa-user-doctor"></i> Doctor & Schedule</div>
    <div class="card-body">
        <div class="form-row cols-2">
            <div class="form-group">
                <label class="form-label">Doctor <span class="req">*</span></label>
                <select name="doctor_id" id="doctor-select" class="form-control select2" required style="width:100%" onchange="loadSlots()">
                    <option value="">Select Doctor</option>
                    @foreach($doctors as $d)
                        <option value="{{ $d->id }}" {{ old('doctor_id', request('doctor_id'))==$d->id ? 'selected':'' }}>{{ $d->full_name }} — {{ $d->specialization }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Appointment Date <span class="req">*</span></label>
                <input type="date" name="appointment_date" id="appt-date" class="form-control" value="{{ old('appointment_date', request('date', today()->toDateString())) }}" min="{{ today()->toDateString() }}" required onchange="loadSlots()">
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Time Slot <span class="req">*</span></label>
            <div id="slots-container">
                <div class="text-muted text-small" style="padding:12px;background:var(--sc-bg);border-radius:8px">Select a doctor and date to see available slots.</div>
            </div>
            <input type="hidden" name="appointment_time" id="slot-hidden" value="17:00:00">
        </div>

        <div class="form-row cols-2">
            <div class="form-group">
                <label class="form-label">Visit Type <span class="req">*</span></label>
                @php $selType = old('type', request('type', 'New Consultation')); @endphp
                <select name="type" class="form-control" id="appt-type" required onchange="document.getElementById('meetingUrlRow').style.display = this.value === 'Teleconsult' ? '' : 'none'">
                    <option value="New Consultation" {{ $selType=='New Consultation' ? 'selected':'' }}>New Consultation</option>
                    <option value="Follow-up" {{ $selType=='Follow-up' ? 'selected':'' }}>Follow-up</option>
                    <option value="Review" {{ $selType=='Review' ? 'selected':'' }}>Review</option>
                    <option value="Emergency" {{ $selType=='Emergency' ? 'selected':'' }}>Emergency</option>
                    <option value="Teleconsult" {{ $selType=='Teleconsult' ? 'selected':'' }}>Teleconsult (video)</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Status</label>
                <select name="status" class="form-control">
                    <option value="Scheduled">Scheduled</option>
                    <option value="Confirmed">Confirmed</option>
                </select>
            </div>
        </div>

        <div class="form-group" id="meetingUrlRow" style="display:{{ old('type')=='Teleconsult' ? '' : 'none' }}">
            <label class="form-label">Meeting URL <span style="color:#94a3b8;font-weight:400">(Google Meet / Zoom / WhatsApp video link)</span></label>
            <input type="url" name="meeting_url" class="form-control" placeholder="https://meet.google.com/abc-xyz-pqr" value="{{ old('meeting_url') }}">
        </div>
        <div class="form-group">
            <label class="form-label">Reason / Chief Complaint</label>
            <textarea name="reason" class="form-control" rows="2" placeholder="Brief reason for visit...">{{ old('reason') }}</textarea>
        </div>
        <div class="form-group">
            <label class="form-label">Notes</label>
            <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
        </div>
    </div>
</div>
</div>

<!-- Sidebar -->
<div>
    <div class="card">
        <div class="card-header"><i class="fa-solid fa-circle-info"></i> Quick Info</div>
        <div class="card-body">
            <div id="doctor-info" style="display:none;background:var(--sc-bg);border-radius:8px;padding:12px;margin-bottom:14px">
                <div class="text-muted text-small" style="margin-bottom:4px">Selected Doctor</div>
                <div id="doc-name" class="fw-600"></div>
                <div id="doc-spec" class="text-small text-muted"></div>
                <div id="doc-fee" class="text-small" style="color:var(--sc-teal);margin-top:4px"></div>
                <div id="doc-slots" class="text-small text-muted"></div>
            </div>
        </div>
    </div>
    <div class="card mt-3">
        <div class="card-body" style="display:flex;flex-direction:column;gap:10px">
            <button type="submit" class="btn btn-primary btn-lg" style="width:100%;justify-content:center"><i class="fa-solid fa-calendar-check"></i> Book Appointment</button>
            <a href="{{ route('appointments.index') }}" class="btn btn-outline" style="width:100%;justify-content:center">Cancel</a>
        </div>
    </div>
</div>
</div>
</form>
@endsection

@push('styles')
<style>
.appt-patient-card { overflow: visible; position: relative; z-index: 30; }
.appt-patient-card .card-body { overflow: visible; }
</style>
@endpush

@push('scripts')
<script>
$(function() {
    var $ps = $('#appt-patient-select');
    if ($ps.length) {
        if ($ps.data('select2')) $ps.select2('destroy');
        $ps.select2({
            theme: 'classic',
            width: '100%',
            placeholder: 'Search patient by name, JHC ID, or phone...',
            allowClear: true,
            dropdownParent: $ps.closest('.card')
        });
    }
});

const doctors = {!! json_encode($doctors->map(function($d) {
    return [
        'id'            => $d->id,
        'name'          => $d->first_name . ' ' . $d->last_name,
        'spec'          => $d->specialization,
        'fee'           => $d->consultation_fee,
        'start'         => $d->start_time,
        'end'           => $d->end_time,
        'slot_duration' => $d->slot_duration ?? 15,
    ];
})->values()) !!};

function loadSlots() {
    const doctorId = document.getElementById('doctor-select').value;
    const date     = document.getElementById('appt-date').value;
    const container = document.getElementById('slots-container');
    if (!doctorId || !date) return;

    container.innerHTML = '<div class="text-muted small">Loading slots...</div>';

    fetch(`{{ url('/appointments/slots') }}?doctor_id=${doctorId}&date=${date}`)
        .then(r => r.json())
        .then(data => { const slots = data.slots || data;
            if (!slots || !slots.length) {
                container.innerHTML = '<div class="text-muted small">No slots available for this date.</div>';
                return;
            }
            container.innerHTML = slots.map(slot =>
                `<button type="button"
                    class="btn btn-sm me-1 mb-1 ${slot.available ? 'btn-outline-primary slot-btn' : 'btn-warning slot-btn'}"
                    onclick="selectSlot('${slot.time}')"
                    data-time="${slot.time}"
                    style="min-width:70px;font-size:0.8rem">
                    ${slot.time}
                </button>`
            ).join('');
            setTimeout(autoSelectDefaultSlot, 50);
        })
        .catch(() => { container.innerHTML = '<div class="text-danger small">Error loading slots.</div>'; });
}


function autoSelectDefaultSlot() {
    var preferred = ['17:00', '16:45', '16:30'];
    for (var i = 0; i < preferred.length; i++) {
        var btn = document.querySelector('.slot-btn[data-time="' + preferred[i] + '"]');
        if (btn && !btn.classList.contains('disabled')) {
            selectSlot(preferred[i]);
            btn.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }
    }
}
function selectSlot(time) {
    document.getElementById('slot-hidden').value = time;
    document.querySelectorAll('.slot-btn').forEach(b => b.classList.remove('active', 'btn-primary'));
    document.querySelectorAll(`.slot-btn[data-time="${time}"]`).forEach(b => {
        b.classList.add('active', 'btn-primary');
        b.classList.remove('btn-outline-primary');
    });
}

document.getElementById('doctor-select')?.addEventListener('change', function() {
    const doc = doctors.find(d => d.id == this.value);
    if (doc) {
        document.getElementById('fee-display').textContent = '₹' + doc.fee;
    }
    loadSlots();
});

document.getElementById('appt-date')?.addEventListener('change', loadSlots);
document.addEventListener('DOMContentLoaded', function() {
    const select = document.getElementById('doctor-select');
    if (!select.value) {
        for (let opt of select.options) {
            if (opt.text.toLowerCase().includes('robert')) {
                opt.selected = true;
                select.dispatchEvent(new Event('change'));
                break;
            }
        }
    } else {
        loadSlots();
    }
});
document.addEventListener('DOMContentLoaded', function() {
    const doctorId = document.getElementById('doctor-select').value;
    const date = document.getElementById('appt-date').value;
    if (doctorId && date) loadSlots();
});
</script>
@endpush
<script>
document.querySelector('form').addEventListener('submit', function(e) {
    var time = document.getElementById('slot-hidden').value;
    if (!time) {
        var btn = document.querySelector('.slot-btn');
        if (btn) {
            selectSlot(btn.getAttribute('data-time'));
        } else {
            document.getElementById('slot-hidden').value = '17:00:00';
        }
    }
});
</script>
