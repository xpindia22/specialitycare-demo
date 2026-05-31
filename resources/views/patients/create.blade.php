{{-- FILE: resources/views/patients/create.blade.php --}}
@extends('layouts.app')
@section('title','Register Patient')
@section('page-title','Register New Patient')
@section('content')
<div class="page-header">
    <div class="page-header-left">
        <h1>Register New Patient</h1>
        <div class="breadcrumb"><a href="{{ route('patients.index') }}">Patients</a> › Register</div>
    </div>
</div>

<form method="POST" action="{{ route('patients.store') }}" enctype="multipart/form-data">
@csrf
<div class="grid" style="grid-template-columns:1fr 320px;gap:20px;align-items:start">

    <div style="display:flex;flex-direction:column;gap:20px">

        <!-- Personal Info -->
        <div class="card">
            <div class="card-header"><i class="fa-solid fa-user"></i> Personal Information</div>
            <div class="card-body">
                <div class="form-row cols-3">
                    <div class="form-group">
                        <label class="form-label">First Name <span class="req">*</span></label>
                        <input type="text" name="first_name" class="form-control {{ $errors->has('first_name') ? 'is-invalid':'' }}" value="{{ old('first_name') }}" required>
                        @error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Middle Name</label>
                        <input type="text" name="middle_name" class="form-control" value="{{ old('middle_name') }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Last Name <span class="req">*</span></label>
                        <input type="text" name="last_name" class="form-control {{ $errors->has('last_name') ? 'is-invalid':'' }}" value="{{ old('last_name') }}" required>
                        @error('last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="form-row cols-3">
                    <div class="form-group">
                        <label class="form-label">Gender <span class="req">*</span></label>
                        <select name="gender" class="form-control" required>
                            <option value="">Select Gender</option>
                            @foreach(['Male','Female','Other','Unknown'] as $g)
                                <option value="{{ $g }}" {{ old('gender')==$g ? 'selected':'' }}>{{ $g }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Date of Birth <span class="req">*</span></label>
                        <input type="date" name="date_of_birth" class="form-control {{ $errors->has('date_of_birth') ? 'is-invalid':'' }}" value="{{ old('date_of_birth') }}" max="{{ now()->toDateString() }}" required>
                        @error('date_of_birth')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Blood Group</label>
                        <select name="blood_group" class="form-control">
                            <option value="">Unknown</option>
                            @foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bg)
                                <option value="{{ $bg }}" {{ old('blood_group')==$bg ? 'selected':'' }}>{{ $bg }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="form-row cols-3">
                    <div class="form-group">
                        <label class="form-label">Marital Status</label>
                        <select name="marital_status" class="form-control">
                            <option value="">Select</option>
                            @foreach(['Single','Married','Divorced','Widowed'] as $ms)
                                <option value="{{ $ms }}" {{ old('marital_status')==$ms ? 'selected':'' }}>{{ $ms }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Occupation</label>
                        <input type="text" name="occupation" class="form-control" value="{{ old('occupation') }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Referred By</label>
                        <select name="referral_source_id" id="ptRefSelect" class="form-control" onchange="onRefSelectChange(this)">
                            <option value="">— None / Walk-in —</option>
                            @foreach($referralSources ?? [] as $rs)
                            <option value="{{ $rs->id }}" data-name="{{ $rs->name }}"
                                {{ old('referral_source_id') == $rs->id ? 'selected' : '' }}>{{ $rs->name }}</option>
                            @endforeach
                            <option value="__add__" style="font-style:italic;color:var(--sc-teal,#0ab4b4)">＋ Add new referred by…</option>
                        </select>
                        <input type="hidden" name="referred_by" id="ptRefText" value="{{ old('referred_by') }}">
                    </div>
                    <script>
                    function onRefSelectChange(sel) {
                        if (sel.value === '__add__') {
                            const name = (window.prompt('New "Referred By" name (e.g. Dr. R. Kumar, Apollo Hospital):') || '').trim();
                            // restore previous selection so we don't leave "__add__" stuck if cancelled
                            if (!name) { sel.value = ''; document.getElementById('ptRefText').value = ''; return; }
                            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                            fetch(@json(route('referrals.quick')), {
                                method: 'POST',
                                headers: {'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN': token},
                                body: JSON.stringify({name})
                            }).then(r => r.ok ? r.json() : Promise.reject(r))
                              .then(rs => {
                                  const opt = new Option(rs.name, rs.id, true, true);
                                  opt.dataset.name = rs.name;
                                  sel.insertBefore(opt, sel.querySelector('option[value="__add__"]'));
                                  document.getElementById('ptRefText').value = rs.name;
                              })
                              .catch(() => { alert('Could not save the new referral source. Please try again or use the Referrals page.'); sel.value=''; document.getElementById('ptRefText').value=''; });
                            return;
                        }
                        const opt = sel.options[sel.selectedIndex];
                        document.getElementById('ptRefText').value = opt?.dataset?.name || '';
                    }
                    </script>
                </div>
                <div class="form-group">
                    <label class="form-label">Aadhar Number</label>
                    <input type="text" name="aadhar_no" class="form-control" value="{{ old('aadhar_no') }}" maxlength="20" placeholder="XXXX XXXX XXXX">
                </div>
            </div>
        </div>

        <!-- Contact Info -->
        <div class="card">
            <div class="card-header"><i class="fa-solid fa-address-card"></i> Contact Information</div>
            <div class="card-body">
                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-label">Primary Phone <span class="req">*</span></label>
                        <input type="tel" name="phone" class="form-control {{ $errors->has('phone') ? 'is-invalid':'' }}" value="{{ old('phone') }}" required>
                        @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Alternate Phone</label>
                        <input type="tel" name="alt_phone" class="form-control" value="{{ old('alt_phone') }}">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="{{ old('email') }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Address <span class="req">*</span></label>
                    <textarea name="address" class="form-control" rows="2" required>{{ old('address') }}</textarea>
                </div>
                <div class="form-row cols-3">
                    <div class="form-group">
                        <label class="form-label">State</label>
                        <select name="state" id="state-select" class="form-control select2">
                            <option value="">— Select State / UT —</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">City</label>
                        <select name="city" id="city-select" class="form-control select2" disabled>
                            <option value="">— Select State first —</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Pincode</label>
                        <input type="text" name="pincode" class="form-control" value="{{ old('pincode') }}" maxlength="10">
                    </div>
                </div>
            </div>
        </div>

        <!-- Emergency Contact -->
        <div class="card">
            <div class="card-header"><i class="fa-solid fa-phone-volume"></i> Emergency Contact</div>
            <div class="card-body">
                <div class="form-row cols-3">
                    <div class="form-group">
                        <label class="form-label">Contact Name</label>
                        <input type="text" name="emergency_contact_name" class="form-control" value="{{ old('emergency_contact_name') }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone</label>
                        <input type="tel" name="emergency_contact_phone" class="form-control" value="{{ old('emergency_contact_phone') }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Relation</label>
                        <select name="emergency_contact_relation" class="form-control">
                            <option value="">Select</option>
                            @foreach(['Spouse','Parent','Child','Sibling','Friend','Other'] as $r)
                                <option value="{{ $r }}" {{ old('emergency_contact_relation')==$r ? 'selected':'' }}>{{ $r }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Insurance / TPA -->
        <div class="card">
            <div class="card-header"><i class="fa-solid fa-shield"></i> Insurance / TPA</div>
            <div class="card-body">
                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-label">Insurance Company</label>
                        <input type="text" name="insurance_company" class="form-control" value="{{ old('insurance_company') }}" placeholder="e.g. Star Health, ICICI Lombard">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Policy Number</label>
                        <input type="text" name="policy_no" class="form-control" value="{{ old('policy_no') }}" placeholder="Policy / ID No.">
                    </div>
                    <div class="form-group">
                        <label class="form-label">TPA Name</label>
                        <input type="text" name="tpa_name" class="form-control" value="{{ old('tpa_name') }}" placeholder="Third Party Administrator name">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Insurance Valid Until</label>
                        <input type="date" name="insurance_valid_until" class="form-control" value="{{ old('insurance_valid_until') }}">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- RIGHT SIDEBAR -->
    <div style="display:flex;flex-direction:column;gap:20px">
        <div class="card">
            <div class="card-header"><i class="fa-solid fa-camera"></i> Patient Photo</div>
            <div class="card-body text-center">
                <div id="photo-preview" style="width:120px;height:120px;border-radius:50%;background:var(--sc-bg);border:2px dashed var(--sc-border);display:flex;align-items:center;justify-content:center;margin:0 auto 12px;overflow:hidden;cursor:pointer" onclick="document.getElementById('photo-input').click()">
                    <i class="fa-solid fa-camera" style="font-size:28px;color:var(--sc-muted)"></i>
                </div>
                <input type="file" name="photo" id="photo-input" accept="image/*" style="display:none" onchange="previewPhoto(this)">
                <div class="text-small text-muted">Click to upload photo<br>JPG, PNG (max 2MB)</div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><i class="fa-solid fa-id-card"></i> JHC ID</div>
            <div class="card-body text-center">
                <div style="font-size:11px;color:var(--sc-muted);margin-bottom:4px">Will be auto-assigned</div>
                <div style="font-size:22px;font-weight:700;color:var(--sc-teal);font-family:'DM Mono',monospace">AUTO</div>
                <div class="text-small text-muted mt-1">Format: JHC00001</div>
            </div>
        </div>

        @if(session('duplicate_warning'))
        @php $dup = session('duplicate_warning'); @endphp
        <div class="alert" style="background:#fff8e1;border:1px solid #f59e0b;color:#78350f;border-radius:8px;padding:14px 16px;margin-bottom:0">
            <div class="fw-700"><i class="fa-solid fa-triangle-exclamation"></i> Possible Duplicate Patient</div>
            <div style="margin-top:6px">
                <a href="{{ route('patients.show', $dup['id']) }}" target="_blank" style="color:#92400e;font-weight:600">{{ $dup['full_name'] }}</a>
                ({{ $dup['jhc_id'] }}) — {{ $dup['phone'] }} — {{ $dup['age'] }}
            </div>
            <div style="margin-top:10px">
                <label style="display:flex;gap:8px;align-items:center;cursor:pointer">
                    <input type="checkbox" name="confirm_duplicate" value="1">
                    <span>I confirm this is a different patient — register anyway</span>
                </label>
            </div>
        </div>
        @endif

        <div class="card">
            <div class="card-body" style="display:flex;flex-direction:column;gap:10px">
                <button type="submit" class="btn btn-primary btn-lg" style="width:100%;justify-content:center">
                    <i class="fa-solid fa-user-plus"></i> Register Patient
                </button>
                <a href="{{ route('patients.index') }}" class="btn btn-outline" style="width:100%;justify-content:center">Cancel</a>
            </div>
        </div>
    </div>

</div>
</form>
@endsection
@push('scripts')
<script>
function previewPhoto(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            const p = document.getElementById('photo-preview');
            p.innerHTML = `<img src="${e.target.result}" style="width:100%;height:100%;object-fit:cover">`;
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
@include('patients._state_city_js', ['selectedState' => old('state', 'Andaman and Nicobar Islands'), 'selectedCity' => old('city', 'Port Blair')])
@endpush
