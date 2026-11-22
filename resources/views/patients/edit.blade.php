{{-- FILE: resources/views/patients/edit.blade.php --}}
@extends('layouts.app')
@section('title','Edit Patient')
@section('page-title','Edit Patient')
@section('content')
<div class="page-header">
    <div class="page-header-left">
        <h1>Edit Patient</h1>
        <div class="breadcrumb"><a href="{{ route('patients.index') }}">Patients</a> › <a href="{{ route('patients.show', $patient) }}">{{ $patient->jhc_id }}</a> › Edit</div>
    </div>
</div>

<form method="POST" action="{{ route('patients.update', $patient) }}" enctype="multipart/form-data">
@csrf @method('PUT')
<div class="grid" style="grid-template-columns:1fr 300px;gap:20px;align-items:start">
<div style="display:flex;flex-direction:column;gap:20px">
    <div class="card">
        <div class="card-header"><i class="fa-solid fa-user"></i> Personal Information</div>
        <div class="card-body">
            <div class="form-row cols-3">
                <div class="form-group"><label class="form-label">First Name <span class="req">*</span></label><input type="text" name="first_name" class="form-control" value="{{ old('first_name', $patient->first_name) }}" required></div>
                <div class="form-group"><label class="form-label">Middle Name</label><input type="text" name="middle_name" class="form-control" value="{{ old('middle_name', $patient->middle_name) }}"></div>
                <div class="form-group"><label class="form-label">Last Name <span class="req">*</span></label><input type="text" name="last_name" class="form-control" value="{{ old('last_name', $patient->last_name) }}" required></div>
                <div class="form-group"><label class="form-label">Gender <span class="req">*</span></label>
                    <select name="gender" class="form-control" required>
                        @foreach(['Male','Female','Other','Unknown'] as $g)
                            <option value="{{ $g }}" {{ old('gender',$patient->gender)==$g ? 'selected':'' }}>{{ $g }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group"><label class="form-label">Date of Birth <span class="req">*</span></label><input type="date" name="date_of_birth" class="form-control" value="{{ old('date_of_birth', $patient->date_of_birth?->toDateString()) }}" required></div>
                <div class="form-group"><label class="form-label">Blood Group</label>
                    <select name="blood_group" class="form-control">
                        <option value="">Unknown</option>
                        @foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bg)
                            <option value="{{ $bg }}" {{ old('blood_group',$patient->blood_group)==$bg ? 'selected':'' }}>{{ $bg }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group"><label class="form-label">Phone <span class="req">*</span></label><input type="tel" name="phone" class="form-control" value="{{ old('phone', $patient->phone) }}" required></div>
                <div class="form-group"><label class="form-label">Alt. Phone</label><input type="tel" name="alt_phone" class="form-control" value="{{ old('alt_phone', $patient->alt_phone) }}"></div>
                <div class="form-group"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="{{ old('email', $patient->email) }}"></div>
                <div class="form-group"><label class="form-label">Marital Status</label>
                    <select name="marital_status" class="form-control">
                        <option value="">Select</option>
                        @foreach(['Single','Married','Divorced','Widowed'] as $ms)
                            <option value="{{ $ms }}" {{ old('marital_status',$patient->marital_status)==$ms ? 'selected':'' }}>{{ $ms }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group"><label class="form-label">Occupation</label><input type="text" name="occupation" class="form-control" value="{{ old('occupation', $patient->occupation) }}"></div>
            </div>
            <div class="form-group"><label class="form-label">Address <span class="req">*</span></label><textarea name="address" class="form-control" rows="2" required>{{ old('address', $patient->address) }}</textarea></div>
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
                <div class="form-group"><label class="form-label">Pincode</label><input type="text" name="pincode" class="form-control" value="{{ old('pincode', $patient->pincode) }}"></div>
            </div>
            <div class="form-row cols-3">
                <div class="form-group"><label class="form-label">Emergency Contact</label><input type="text" name="emergency_contact_name" class="form-control" value="{{ old('emergency_contact_name', $patient->emergency_contact_name) }}"></div>
                <div class="form-group"><label class="form-label">Emergency Phone</label><input type="tel" name="emergency_contact_phone" class="form-control" value="{{ old('emergency_contact_phone', $patient->emergency_contact_phone) }}"></div>
                <div class="form-group"><label class="form-label">Relation</label>
                    <select name="emergency_contact_relation" class="form-control">
                        <option value="">Select</option>
                        @foreach(['Spouse','Parent','Child','Sibling','Friend','Other'] as $r)
                            <option value="{{ $r }}" {{ old('emergency_contact_relation',$patient->emergency_contact_relation)==$r ? 'selected':'' }}>{{ $r }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Allergies -->
    <div class="card">
        <div class="card-header"><i class="fa-solid fa-triangle-exclamation" style="color:#dc2626"></i> Allergies &amp; Alerts</div>
        <div class="card-body">
            <div class="form-group" style="margin:0">
                <label class="form-label">Known Allergies / Drug Alerts</label>
                <textarea name="allergies" class="form-control" rows="2"
                    placeholder="e.g. Penicillin, Sulpha drugs, Aspirin, Shellfish…">{{ old('allergies', $patient->allergies) }}</textarea>
                <div class="form-text">This will display as a red alert banner on every clinical screen for this patient.</div>
            </div>
            <div class="form-group" style="margin-top:12px">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-weight:500">
                    {{-- Hidden 0 so the checkbox always submits a value (Laravel boolean handling). --}}
                    <input type="hidden" name="is_chronic" value="0">
                    <input type="checkbox" name="is_chronic" value="1" {{ old('is_chronic', $patient->is_chronic) ? 'checked' : '' }}>
                    Chronic patient (HTN / T2DM / post-MI etc.)
                </label>
                <div class="form-text">When on, the patient gets a daily morning WhatsApp medication reminder.</div>
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
                    <input type="text" name="insurance_company" class="form-control" value="{{ old('insurance_company', $patient->insurance_company) }}" placeholder="e.g. Star Health, ICICI Lombard">
                </div>
                <div class="form-group">
                    <label class="form-label">Policy Number</label>
                    <input type="text" name="policy_no" class="form-control" value="{{ old('policy_no', $patient->policy_no) }}" placeholder="Policy / ID No.">
                </div>
                <div class="form-group">
                    <label class="form-label">TPA Name</label>
                    <input type="text" name="tpa_name" class="form-control" value="{{ old('tpa_name', $patient->tpa_name) }}" placeholder="Third Party Administrator name">
                </div>
                <div class="form-group">
                    <label class="form-label">Insurance Valid Until</label>
                    <input type="date" name="insurance_valid_until" class="form-control" value="{{ old('insurance_valid_until', $patient->insurance_valid_until?->toDateString()) }}">
                </div>
            </div>
            <div class="form-group" style="margin-bottom:0">
                <label class="form-label">Referred By</label>
                <select name="referral_source_id" class="form-control">
                    <option value="">— None / Walk-in —</option>
                    @foreach($referralSources ?? [] as $rs)
                        <option value="{{ $rs->id }}" {{ old('referral_source_id', $patient->referral_source_id) == $rs->id ? 'selected' : '' }}>
                            {{ $rs->name }}
                            @if($rs->rate_type === 'fixed') — ₹{{ number_format($rs->rate_value,0) }}/pt
                            @else — {{ $rs->rate_value }}% of bill
                            @endif
                        </option>
                    @endforeach
                </select>
                <input type="hidden" name="referred_by" value="{{ old('referred_by', $patient->referred_by) }}">
                @if(($referralSources ?? collect())->isEmpty())
                <div style="font-size:11px;color:#888;margin-top:3px">
                    No referral sources yet. <a href="{{ route('referrals.create') }}" target="_blank">Add one →</a>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
<div>
    <div class="card mb-3">
        <div class="card-header"><i class="fa-solid fa-camera"></i> Photo</div>
        <div class="card-body text-center">
            <div style="width:100px;height:100px;border-radius:50%;margin:0 auto 10px;overflow:hidden;border:2px solid var(--sc-border)">
                @if($patient->photo)
                    <img src="{{ asset('storage/'.$patient->photo) }}" style="width:100%;height:100%;object-fit:cover">
                @else
                    <div style="width:100%;height:100%;background:var(--sc-teal-glow);display:flex;align-items:center;justify-content:center;font-size:28px;font-weight:700;color:var(--sc-teal)">{{ strtoupper(substr($patient->first_name,0,1)) }}</div>
                @endif
            </div>
            <input type="file" name="photo" class="form-control" accept="image/*">
            <div class="form-text">Upload new photo (leave empty to keep current)</div>
        </div>
    </div>
    <div class="card mb-3">
        <div class="card-body">
            <div style="font-size:11px;color:var(--sc-muted);margin-bottom:4px">JHC ID (cannot be changed)</div>
            <div style="font-size:18px;font-weight:700;color:var(--sc-teal);font-family:'DM Mono',monospace">{{ $patient->jhc_id }}</div>
        </div>
    </div>
    <div class="card">
        <div class="card-body" style="display:flex;flex-direction:column;gap:10px">
            <button type="submit" class="btn btn-primary btn-lg" style="width:100%;justify-content:center"><i class="fa-solid fa-save"></i> Save Changes</button>
            <a href="{{ route('patients.show', $patient) }}" class="btn btn-outline" style="width:100%;justify-content:center">Cancel</a>
            <form method="POST" action="{{ route('patients.toggle-active', $patient) }}"
                  onsubmit="return confirm('{{ $patient->is_active ? 'Mark this patient as INACTIVE? Their records are kept; they are just hidden from active lists.' : 'Reactivate this patient?' }}')">
                @csrf
                @if($patient->is_active)
                    <button type="submit" class="btn btn-lg" style="width:100%;justify-content:center;background:#f59e0b;color:#fff;border:none;font-weight:600">
                        <i class="fa-solid fa-user-slash"></i> Mark Inactive
                    </button>
                @else
                    <button type="submit" class="btn btn-lg" style="width:100%;justify-content:center;background:#15803d;color:#fff;border:none;font-weight:600">
                        <i class="fa-solid fa-user-check"></i> Reactivate Patient
                    </button>
                @endif
            </form>
            @if(auth()->user()?->isOwner())
            {{-- Owner-only: Delete button submits the hidden form below via form="..." --}}
            <button type="submit" form="patient-delete-form" class="btn btn-lg"
                    style="width:100%;justify-content:center;background:#b91c1c;color:#fff;border:none;font-weight:600;margin-top:4px">
                <i class="fa-solid fa-trash"></i> Delete Patient
            </button>
            <div class="text-small text-muted" style="text-align:center;margin-top:-2px">
                Owner-only • Only deletes if no clinical / billing records exist.
            </div>
            @endif
        </div>
    </div>
</div>
</div>
</form>

@if(auth()->user()?->isOwner())
{{-- Hidden delete form (no UI of its own — the button above targets it via form="...") --}}
<form id="patient-delete-form" method="POST" action="{{ route('patients.destroy', $patient) }}"
      onsubmit="return confirm('Delete {{ $patient->first_name }} {{ $patient->last_name }} ({{ $patient->jhc_id }}) permanently?\n\nWorks only if the patient has no bills/encounters/prescriptions/etc. If they do, you\'ll get a refusal — toggle Active off on the edit form instead.')">
    @csrf @method('DELETE')
</form>
@endif

@endsection
@push('scripts')
@include('patients._state_city_js', ['selectedState' => old('state', $patient->state ?: 'Andaman and Nicobar Islands'), 'selectedCity' => old('city', $patient->city ?: 'Port Blair')])
@endpush
