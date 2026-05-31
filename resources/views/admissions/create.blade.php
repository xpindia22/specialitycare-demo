{{-- FILE: resources/views/admissions/create.blade.php --}}
@extends('layouts.app')
@section('title','New Admission')
@section('page-title','New IPD Admission')
@section('content')

<div class="page-header">
    <div class="page-header-left"><h1>New Admission</h1></div>
    <a href="{{ route('admissions.index') }}" class="btn btn-outline">Back</a>
</div>

@if($errors->any())
<div class="alert alert-danger">
    <i class="fa-solid fa-circle-exclamation"></i>
    <ul style="margin:0;padding-left:16px">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif

<form method="POST" action="{{ route('admissions.store') }}">
@csrf
<div class="grid" style="grid-template-columns:1fr 340px;gap:20px;align-items:start">

    {{-- Left column --}}
    <div>
        <div class="card mb-3">
            <div class="card-header"><i class="fa-solid fa-user"></i> Patient</div>
            <div class="card-body">
                @if($patient)
                    <input type="hidden" name="patient_id" value="{{ $patient->id }}">
                    <div class="patient-strip" style="margin-bottom:0">
                        <div class="ps-avatar">{{ strtoupper(substr($patient->first_name,0,1)) }}</div>
                        <div>
                            <div class="ps-name">{{ $patient->full_name }}</div>
                            <div class="ps-jhc_id">{{ $patient->jhc_id }}</div>
                            <div class="ps-meta">
                                <span><i class="fa-solid fa-venus-mars"></i> {{ ucfirst($patient->gender) }}</span>
                                <span><i class="fa-solid fa-cake-candles"></i> {{ $patient->age }}</span>
                                @if($patient->blood_group)<span><i class="fa-solid fa-droplet"></i> {{ $patient->blood_group }}</span>@endif
                            </div>
                        </div>
                        <a href="{{ route('admissions.create') }}" class="btn btn-sm btn-outline ps-actions" style="color:#fff;border-color:rgba(255,255,255,.3)">Change</a>
                    </div>
                @else
                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label">Search Patient <span class="req">*</span></label>
                        <select name="patient_id" class="form-control @error('patient_id') is-invalid @enderror" required>
                            <option value="">— Select patient —</option>
                            @foreach($patients as $p)
                            <option value="{{ $p->id }}" {{ old('patient_id') == $p->id ? 'selected' : '' }}>
                                {{ $p->full_name }} ({{ $p->jhc_id }})
                            </option>
                            @endforeach
                        </select>
                        @error('patient_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                @endif
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><i class="fa-solid fa-stethoscope"></i> Admission Details</div>
            <div class="card-body">
                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-label">Admission Type <span class="req">*</span></label>
                        <select name="admission_type" class="form-control" required>
                            @foreach(\App\Models\Admission::ADMISSION_TYPES as $val => $label)
                            <option value="{{ $val }}" {{ old('admission_type','elective') === $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Admission Date &amp; Time <span class="req">*</span></label>
                        <input type="datetime-local" name="admission_date" class="form-control"
                               value="{{ old('admission_date', now()->format('Y-m-d\TH:i')) }}" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Admitting Doctor <span class="req">*</span></label>
                    <select name="admitting_doctor_id" class="form-control @error('admitting_doctor_id') is-invalid @enderror" required>
                        <option value="">— Select doctor —</option>
                        @foreach($doctors as $doc)
                        <option value="{{ $doc->id }}" {{ (old('admitting_doctor_id', $defaultDoctorId ?? null)) == $doc->id ? 'selected' : '' }}>
                            {{ $doc->full_name }}{{ $doc->specialization ? ' — ' . $doc->specialization : '' }}
                        </option>
                        @endforeach
                    </select>
                    @error('admitting_doctor_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Provisional Diagnosis</label>
                    <input type="text" name="provisional_diagnosis" class="form-control"
                           value="{{ old('provisional_diagnosis') }}" placeholder="e.g. Acute MI, Pneumonia, COPD exacerbation…">
                </div>
                <div class="form-group">
                    <label class="form-label">Referral Source <span style="font-size:11px;color:#888">(clinic / doctor who referred)</span></label>
                    <select name="referral_source_id" class="form-control" id="referralSourceSelect" onchange="fillReferredBy(this)">
                        <option value="">— None / Walk-in —</option>
                        @foreach($referralSources as $src)
                        <option value="{{ $src->id }}"
                            data-name="{{ $src->name }}"
                            {{ old('referral_source_id') == $src->id ? 'selected' : '' }}>
                            {{ $src->name }}
                            @if($src->rate_type === 'fixed') — ₹{{ number_format($src->rate_value,0) }}/pt
                            @else — {{ $src->rate_value }}% of bill
                            @endif
                        </option>
                        @endforeach
                    </select>
                    @if($referralSources->isEmpty())
                    <div style="font-size:11px;color:#888;margin-top:4px">
                        No referral sources configured. <a href="{{ route('referrals.create') }}" target="_blank">Add one →</a>
                    </div>
                    @endif
                </div>
                <div class="form-group">
                    <label class="form-label">Referred By <span style="font-size:11px;color:#888">(free text, auto-filled)</span></label>
                    <input type="text" name="referred_by" id="referredByText" class="form-control"
                           value="{{ old('referred_by') }}" placeholder="Referring doctor / hospital name">
                </div>
                <div class="form-group">
                    <label class="form-label">Admission Notes</label>
                    <textarea name="notes" class="form-control" rows="3" placeholder="Initial clinical notes, history summary…">{{ old('notes') }}</textarea>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><i class="fa-solid fa-shield-halved"></i> MLC (Medico-Legal Case)</div>
            <div class="card-body">
                <div class="form-group">
                    <label style="display:flex;align-items:center;gap:10px;cursor:pointer;font-weight:600;font-size:13px">
                        <input type="checkbox" name="is_mlc" value="1" id="mlcCheck" {{ old('is_mlc') ? 'checked' : '' }}
                               style="width:16px;height:16px;accent-color:var(--sc-red)">
                        <span>Tag as Medico-Legal Case (MLC)</span>
                    </label>
                </div>
                <div id="mlcFields" style="{{ old('is_mlc') ? '' : 'display:none' }}">
                    <div class="form-row cols-2">
                        <div class="form-group">
                            <label class="form-label">MLC Number</label>
                            <input type="text" name="mlc_no" class="form-control" value="{{ old('mlc_no') }}" placeholder="e.g. MLC/2024/001">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Police Station</label>
                            <input type="text" name="mlc_police_station" class="form-control" value="{{ old('mlc_police_station') }}" placeholder="Reporting police station">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">MLC Notes</label>
                        <textarea name="mlc_notes" class="form-control" rows="2" placeholder="Circumstances, injury description, reporting officer…">{{ old('mlc_notes') }}</textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Right column: Ward / Bed --}}
    <div>
        <div class="card">
            <div class="card-header"><i class="fa-solid fa-bed"></i> Ward &amp; Bed</div>
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label">Ward <span class="req">*</span></label>
                    <select name="ward_id" id="wardSelect" class="form-control @error('ward_id') is-invalid @enderror" required>
                        <option value="">— Select ward —</option>
                        @foreach($wards as $ward)
                        <option value="{{ $ward->id }}" {{ old('ward_id') == $ward->id ? 'selected' : '' }}>
                            {{ $ward->name }} ({{ $ward->available_beds_count }} available)
                        </option>
                        @endforeach
                    </select>
                    @error('ward_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Bed <span class="req">*</span></label>
                    <select name="bed_id" id="bedSelect" class="form-control @error('bed_id') is-invalid @enderror" required>
                        <option value="">— Select ward first —</option>
                    </select>
                    @error('bed_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div id="bedInfo" style="display:none" class="alert alert-info" style="margin-top:8px">
                    <i class="fa-solid fa-circle-info"></i> <span id="bedInfoText"></span>
                </div>

                {{-- Bed map preview --}}
                <div id="wardBedMap" style="display:none;margin-top:12px">
                    <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.8px;color:var(--sc-muted);margin-bottom:8px">Bed Map</div>
                    <div id="bedMapGrid" style="display:grid;grid-template-columns:repeat(4,1fr);gap:6px"></div>
                    <div style="display:flex;gap:12px;margin-top:10px;font-size:11px;color:var(--sc-muted)">
                        <span><span style="display:inline-block;width:10px;height:10px;border-radius:3px;background:#dcfce7;border:1px solid #86efac"></span> Available</span>
                        <span><span style="display:inline-block;width:10px;height:10px;border-radius:3px;background:#fee2e2;border:1px solid #fca5a5"></span> Occupied</span>
                        <span><span style="display:inline-block;width:10px;height:10px;border-radius:3px;background:#fef3c7;border:1px solid #fcd34d"></span> Reserved</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-3 d-flex gap-2" style="display:flex;gap:8px;margin-top:12px">
            <button type="submit" class="btn btn-primary" style="flex:1"><i class="fa-solid fa-bed-pulse"></i> Admit Patient</button>
        </div>
    </div>
</div>
</form>

<script>
function fillReferredBy(select) {
    const opt = select.options[select.selectedIndex];
    const name = opt.dataset.name || '';
    document.getElementById('referredByText').value = name;
}

const bedsEndpoint = '{{ route('admissions.beds') }}';
const wardSelect   = document.getElementById('wardSelect');
const bedSelect    = document.getElementById('bedSelect');
const bedMapGrid   = document.getElementById('bedMapGrid');
const wardBedMap   = document.getElementById('wardBedMap');
const bedInfo      = document.getElementById('bedInfo');
const bedInfoText  = document.getElementById('bedInfoText');

function loadBeds(wardId) {
    if (!wardId) {
        bedSelect.innerHTML = '<option value="">— Select ward first —</option>';
        wardBedMap.style.display = 'none';
        bedInfo.style.display = 'none';
        return;
    }
    bedSelect.innerHTML = '<option value="">Loading…</option>';
    bedSelect.disabled  = true;
    bedMapGrid.innerHTML = '';
    wardBedMap.style.display = 'none';

    fetch(`${bedsEndpoint}?ward_id=${wardId}`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        credentials: 'same-origin'
    })
    .then(r => {
        if (!r.ok) throw new Error(`Server returned ${r.status}`);
        return r.json();
    })
    .then(beds => {
        bedSelect.disabled = false;
        bedSelect.innerHTML = beds.length
            ? '<option value="">— Select bed —</option>'
            : '<option value="">No available beds in this ward</option>';

        beds.forEach(b => {
            const opt = document.createElement('option');
            opt.value    = b.id;
            opt.text     = `Bed ${b.bed_number} (${b.type}) — ${b.status}`;
            opt.disabled = b.status !== 'available';
            bedSelect.appendChild(opt);

            const colors  = {available:'#dcfce7', occupied:'#fee2e2', reserved:'#fef3c7', maintenance:'#f1f5f9'};
            const borders = {available:'#86efac', occupied:'#fca5a5', reserved:'#fcd34d', maintenance:'#cbd5e1'};
            const tile = document.createElement('div');
            tile.style.cssText = `background:${colors[b.status]||'#f1f5f9'};border:1px solid ${borders[b.status]||'#cbd5e1'};border-radius:6px;padding:6px;text-align:center;font-size:11px;cursor:${b.status==='available'?'pointer':'default'}`;
            tile.innerHTML = `<div style="font-weight:700;font-size:12px">${b.bed_number}</div><div style="opacity:.7;font-size:10px">${b.type}</div>`;
            if (b.status === 'available') {
                tile.addEventListener('click', () => {
                    bedSelect.value = b.id;
                    bedSelect.dispatchEvent(new Event('change'));
                    document.querySelectorAll('#bedMapGrid > div').forEach(t => t.style.outline = 'none');
                    tile.style.outline = '2px solid #0ab4b4';
                });
            }
            bedMapGrid.appendChild(tile);
        });

        wardBedMap.style.display = beds.length ? 'block' : 'none';
    })
    .catch(err => {
        bedSelect.disabled = false;
        bedSelect.innerHTML = '<option value="">Error loading beds — try again</option>';
        console.error('Beds load failed:', err);
    });
}

wardSelect.addEventListener('change', function () { loadBeds(this.value); });

bedSelect.addEventListener('change', function () {
    const opt = this.options[this.selectedIndex];
    if (this.value && opt) {
        bedInfo.style.display = 'block';
        bedInfoText.textContent = opt.text;
    } else {
        bedInfo.style.display = 'none';
    }
});

// Re-populate on page load if ward already selected (old() on validation error)
if (wardSelect.value) loadBeds(wardSelect.value);

// MLC toggle
document.getElementById('mlcCheck').addEventListener('change', function() {
    document.getElementById('mlcFields').style.display = this.checked ? '' : 'none';
});
</script>
@endsection
