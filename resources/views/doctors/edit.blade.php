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
            <div class="form-group"><label class="form-label">Department</label><select name="department_id" class="form-control"><option value="">None</option>@foreach($departments as $d)<option value="{{ $d->id }}" {{ $doctor->department_id==$d->id?'selected':'' }}>{{ $d->name }}</option>@endforeach</select></div>
            <div class="form-group"><label class="form-label">Start Time</label><input type="time" name="start_time" class="form-control" value="{{ $doctor->start_time }}"></div>
            <div class="form-group"><label class="form-label">End Time</label><input type="time" name="end_time" class="form-control" value="{{ $doctor->end_time }}"></div>
        </div>
        <div class="form-group"><label class="form-label">Available Days</label>
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
@endsection
