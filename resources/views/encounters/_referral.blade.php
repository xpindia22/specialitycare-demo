{{-- OPD referral on the visit page: who referred this visit, referrals made from it, and the Refer modal.
     Needs $encounter, $referralFrom, $referralsMade, $referDepartments, $referDoctors --}}
@php
    $fromDept = \App\Services\OpdFee::departmentOf($encounter);
    $referStatusBadge = ['pending' => 'badge-amber', 'queued' => 'badge-navy', 'booked' => 'badge-purple', 'seen' => 'badge-green', 'cancelled' => 'badge-gray'];
@endphp

@if($referralFrom)
<div class="alert alert-keep ref-banner">
    <div><i class="fa-solid fa-share-from-square" style="font-size:1.4rem;color:#7c3aed"></i></div>
    <div style="flex:1;min-width:220px">
        <div class="fw-700" style="font-size:14px">Referred by {{ $referralFrom->from_label }}
            @if($referralFrom->priority === 'urgent')<span class="badge badge-red" style="margin-left:6px">Urgent</span>@endif
            <span class="text-muted" style="font-weight:400;font-size:12px">· {{ $referralFrom->created_at->format('d M, h:i A') }}</span></div>
        <div style="font-size:13px;margin-top:2px"><b>Reason:</b> {{ $referralFrom->reason }}</div>
        @if($referralFrom->notes)<div style="font-size:12.5px;margin-top:4px;white-space:pre-line;color:#334155">{{ $referralFrom->notes }}</div>@endif
    </div>
    <div style="display:flex;gap:6px;flex-wrap:wrap">
        @if($referralFrom->fromEncounter && !$referralFrom->fromEncounter->trashed())
        <a href="{{ route('encounters.show', $referralFrom->fromEncounter) }}" class="btn btn-sm btn-outline"><i class="fa-solid fa-arrow-left"></i> Referring visit</a>
        @endif
        <a href="{{ route('opd.referrals.print', $referralFrom) }}" target="_blank" class="btn btn-sm btn-outline"><i class="fa-solid fa-print"></i> Letter</a>
    </div>
</div>
@endif

@if($referralsMade->isNotEmpty())
<div class="alert alert-keep ref-made">
    <i class="fa-solid fa-share" style="color:#7c3aed;margin-top:2px"></i>
    <div style="flex:1">
        <div class="fw-700" style="font-size:13px;margin-bottom:4px">Referred from this visit</div>
        @foreach($referralsMade as $r)
        <div class="ref-made-row">
            <span style="color:{{ $r->toDepartment?->color ?: '#334155' }};font-weight:600"><i class="fa-solid {{ $r->toDepartment?->icon ?: 'fa-hospital' }}"></i> {{ $r->toDepartment?->name }}</span>
            @if($r->toDoctor)<span>· {{ $r->toDoctor->full_name }}</span>@endif
            <span class="text-muted">· {{ $r->reason }}</span>
            @if($r->token)<span class="mono fw-600">· {{ $r->token->display }}</span>@endif
            @if($r->when === 'later' && $r->preferred_date)<span class="text-muted">· for {{ $r->preferred_date->format('d M') }}</span>@endif
            <span class="badge {{ $referStatusBadge[$r->status] ?? 'badge-gray' }}">{{ $r->status_label }}</span>
            <a href="{{ route('opd.referrals.print', $r) }}" target="_blank" class="btn btn-sm btn-outline" style="padding:1px 8px;font-size:11px"><i class="fa-solid fa-print"></i> Letter</a>
            @if($r->token)<a href="{{ route('opd.slip', $r->token) }}" target="_blank" class="btn btn-sm btn-outline" style="padding:1px 8px;font-size:11px"><i class="fa-solid fa-ticket"></i> Slip</a>@endif
        </div>
        @endforeach
    </div>
</div>
@endif

@unless($encounter->trashed())
<div id="refer-modal" class="modal-overlay">
    <div class="modal-box" style="max-width:600px">
        <div class="modal-header">
            <i class="fa-solid fa-share-from-square" style="color:#7c3aed"></i>
            <h3>Refer {{ $encounter->patient->full_name }} to another department</h3>
            <button type="button" class="modal-close" onclick="document.getElementById('refer-modal').classList.remove('open')">&times;</button>
        </div>
        <form method="POST" action="{{ route('opd.referrals.store', $encounter) }}" id="refer-form">
            @csrf
            <div class="modal-body" style="display:flex;flex-direction:column;gap:12px;font-size:13px">
                @if($errors->hasAny(['to_department_id', 'to_doctor_id', 'preferred_date', 'reason']))
                <div class="alert alert-danger" style="margin:0">{{ $errors->first('to_department_id') ?: $errors->first('to_doctor_id') ?: $errors->first('preferred_date') ?: $errors->first('reason') }}</div>
                @endif
                <div class="ref-grid">
                    <div class="form-group" style="margin:0">
                        <label class="form-label">Department <span class="req">*</span></label>
                        <select name="to_department_id" id="ref-dept" class="form-control" required onchange="refFilterDoctors()">
                            <option value="">— choose —</option>
                            @foreach($referDepartments as $d)
                            <option value="{{ $d->id }}" @selected(old('to_department_id') == $d->id)>{{ $d->name }}{{ $d->id === $fromDept ? ' (this department)' : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group" style="margin:0">
                        <label class="form-label">Doctor</label>
                        <select name="to_doctor_id" id="ref-doctor" class="form-control">
                            <option value="">Any doctor in the department</option>
                            @foreach($referDoctors as $doc)
                            @continue($doc->id === $encounter->doctor_id)
                            <option value="{{ $doc->id }}" data-depts="{{ implode(',', $doc->departmentIds()) }}" @selected(old('to_doctor_id') == $doc->id)>{{ $doc->full_name }}{{ $doc->specialization ? ' — '.$doc->specialization : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="form-group" style="margin:0">
                    <label class="form-label">Reason for referral <span class="req">*</span></label>
                    <input type="text" name="reason" class="form-control" required maxlength="255" value="{{ old('reason') }}" placeholder="e.g. Diabetic retinopathy screening, Knee pain for opinion">
                </div>
                <div class="form-group" style="margin:0">
                    <label class="form-label">Note for the other doctor</label>
                    <textarea name="notes" class="form-control" rows="3" maxlength="3000" placeholder="Findings, what you want them to look at…">{{ old('notes', $encounter->impression) }}</textarea>
                    <div class="text-small text-muted" style="font-size:11px">The letter also lists this visit's diagnoses and medicines.</div>
                </div>
                <div class="ref-grid">
                    <div class="form-group" style="margin:0">
                        <label class="form-label">When</label>
                        <label class="ref-radio"><input type="radio" name="when" value="today" @checked(old('when', 'today') === 'today') onchange="refWhen()"> Today: give a token in that department's queue now</label>
                        <label class="ref-radio"><input type="radio" name="when" value="later" @checked(old('when') === 'later') onchange="refWhen()"> Later: reception books an appointment</label>
                        <input type="date" name="preferred_date" id="ref-date" class="form-control" style="margin-top:6px;{{ old('when') === 'later' ? '' : 'display:none' }}"
                               min="{{ today()->toDateString() }}" value="{{ old('preferred_date', today()->addDay()->toDateString()) }}">
                    </div>
                    <div class="form-group" style="margin:0">
                        <label class="form-label">Priority</label>
                        <label class="ref-radio"><input type="radio" name="priority" value="normal" @checked(old('priority', 'normal') === 'normal')> Normal</label>
                        <label class="ref-radio" style="color:var(--sc-red)"><input type="radio" name="priority" value="urgent" @checked(old('priority') === 'urgent')> Urgent: top of their queue</label>
                    </div>
                </div>
                @if($encounter->opdToken && in_array($encounter->opdToken->status, ['waiting', 'called', 'in_consultation']))
                <label style="display:flex;gap:8px;align-items:center;cursor:pointer">
                    <input type="checkbox" name="finish_mine" value="1" @checked(old('finish_mine', '1'))>
                    Mark my token {{ $encounter->opdToken->display }} done (patient leaves my queue)
                </label>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('refer-modal').classList.remove('open')">Cancel</button>
                <button type="submit" class="btn" style="background:#7c3aed;border-color:#7c3aed;color:#fff"><i class="fa-solid fa-share-from-square"></i> Refer</button>
            </div>
        </form>
    </div>
</div>
@if($errors->hasAny(['to_department_id', 'to_doctor_id', 'preferred_date', 'reason']))
<script>document.addEventListener('DOMContentLoaded', () => { document.getElementById('refer-modal').classList.add('open'); refFilterDoctors(true); });</script>
@endif
<script>
function refFilterDoctors(keep) {
    const dept = document.getElementById('ref-dept').value, sel = document.getElementById('ref-doctor');
    [...sel.options].forEach(o => {
        if (!o.value) return;
        const ok = !dept || (o.dataset.depts || '').split(',').includes(dept);
        o.hidden = !ok; o.disabled = !ok;
    });
    if (!keep && sel.selectedOptions[0]?.disabled) sel.value = '';
}
function refWhen() {
    const later = document.querySelector('#refer-form input[name=when]:checked').value === 'later';
    const d = document.getElementById('ref-date');
    d.style.display = later ? '' : 'none'; d.required = later;
}
</script>
@endunless

@push('styles')
<style>
.ref-banner { background:#f5f3ff; border:1px solid #c4b5fd; color:#3b0764; border-radius:8px; padding:12px 18px; margin-bottom:14px; display:flex; gap:14px; align-items:center; flex-wrap:wrap; }
.ref-made { background:#faf5ff; border:1px solid #e9d5ff; color:#334155; border-radius:8px; padding:10px 16px; margin-bottom:14px; display:flex; gap:12px; align-items:flex-start; }
.ref-made-row { display:flex; gap:6px; align-items:center; flex-wrap:wrap; font-size:12.5px; padding:3px 0; }
.ref-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
.ref-radio { display:flex; gap:6px; align-items:flex-start; font-size:12.5px; margin:3px 0; cursor:pointer; }
.ref-radio input { margin-top:3px; }
@media (max-width:600px) { .ref-grid { grid-template-columns:1fr; } }
</style>
@endpush
