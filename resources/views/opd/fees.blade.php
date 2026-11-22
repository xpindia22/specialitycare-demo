{{-- Administration → OPD Fee Rules --}}
@extends('layouts.app')
@section('title','OPD Fee Rules')
@section('page-title','OPD Fee Rules')
@section('content')

<div class="page-header">
    <div class="page-header-left" style="min-width:320px">
        <h1>OPD Fee Rules</h1>
        <div class="breadcrumb">Consultation fee by department / doctor, follow-up and free follow-up, discounts. Used when billing a visit.</div>
    </div>
    @if($canEdit)
    <div style="display:flex;gap:8px;flex-wrap:wrap">
        <button type="button" class="btn btn-primary" onclick="openRule(null)"><i class="fa-solid fa-plus"></i> Add Fee Rule</button>
        <button type="button" class="btn btn-outline" onclick="openDisc(null)"><i class="fa-solid fa-percent"></i> Add Discount</button>
    </div>
    @endif
</div>

<div class="alert alert-info alert-keep" style="font-size:12.5px">
    <i class="fa-solid fa-circle-info"></i>
    <div>
        <b>How the fee is worked out:</b> the most specific rule is used: <i>department + doctor</i>, then <i>doctor</i>, then <i>department</i>, then <i>Default</i>.
        If the patient paid a consultation with the same doctor within the <b>free days</b>, the visit is <b>free</b> (up to the free visits allowed);
        within the <b>follow-up days</b> it is charged the <b>follow-up fee</b>; otherwise the <b>new-visit fee</b>. A blank fee means the doctor's
        consultation fee (new) or the "Follow-up Consultation" service price. The largest automatic discount for the patient's age is then taken off.
        Staff can still change the price on the bill.
    </div>
</div>

<div class="card mb-3">
    <div class="card-header"><i class="fa-solid fa-indian-rupee-sign"></i> Fee Rules <div class="card-actions"><span class="badge badge-navy">{{ $rules->where('is_active', true)->count() }} active</span></div></div>
    <div class="table-wrap">
        <table class="sc-table">
            <thead><tr><th>Applies to</th><th>New visit</th><th>Follow-up</th><th>Follow-up within</th><th>Free within</th><th>Notes</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse($rules as $r)
            <tr style="{{ $r->is_active ? '' : 'opacity:.55' }}">
                <td class="fw-600">{{ $r->scope_label }}</td>
                <td>{{ $r->new_fee !== null ? '₹'.number_format($r->new_fee) : 'Doctor\'s fee' }}</td>
                <td>{{ $r->followup_fee !== null ? '₹'.number_format($r->followup_fee) : 'Service price' }}</td>
                <td>{{ $r->followup_days !== null ? $r->followup_days.' days' : '—' }}</td>
                <td>{{ $r->free_days !== null ? $r->free_days.' days' . ($r->free_visits > 1 ? ', '.$r->free_visits.' visits' : ', 1 visit') : '—' }}</td>
                <td class="text-small">{{ $r->notes }}</td>
                <td>@if($r->is_active)<span class="badge badge-green">Active</span>@else<span class="badge badge-gray">Off</span>@endif</td>
                <td style="white-space:nowrap">
                    @if($canEdit)
                    <button type="button" class="btn btn-sm btn-outline" onclick="openRule({{ json_encode($r->only(['id','department_id','doctor_id','new_fee','followup_fee','followup_days','free_days','free_visits','notes','is_active'])) }})"><i class="fa-solid fa-pen"></i></button>
                    <form method="POST" action="{{ route('opd.fees.destroy', $r) }}" style="display:inline" onsubmit="return confirm('Remove this fee rule?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline" style="color:var(--sc-red)"><i class="fa-solid fa-trash"></i></button></form>
                    @endif
                </td>
            </tr>
            @empty
            <tr><td colspan="8" class="text-muted" style="text-align:center;padding:18px">No rules yet: every visit is charged the doctor's fee, or the follow-up price when the visit type is Follow-up. Add a <b>Default</b> rule to set follow-up and free follow-up days for everyone.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(340px,1fr));gap:16px;align-items:start">
<div class="card">
    <div class="card-header"><i class="fa-solid fa-percent"></i> Discounts</div>
    <div class="table-wrap">
        <table class="sc-table">
            <thead><tr><th>Name</th><th>%</th><th>Applied</th><th></th></tr></thead>
            <tbody>
            @forelse($discounts as $d)
            <tr style="{{ $d->is_active ? '' : 'opacity:.55' }}">
                <td class="fw-600">{{ $d->name }}</td>
                <td>{{ rtrim(rtrim(number_format($d->percent, 2), '0'), '.') }}%</td>
                <td class="text-small">
                    @if($d->is_auto)
                        Automatically, age {{ $d->age_min !== null && $d->age_max !== null ? $d->age_min.'–'.$d->age_max : ($d->age_min !== null ? $d->age_min.'+' : 'up to '.$d->age_max) }}
                    @else
                        Picked by staff on the bill
                    @endif
                    @unless($d->is_active) · off @endunless
                </td>
                <td style="white-space:nowrap">
                    @if($canEdit)
                    <button type="button" class="btn btn-sm btn-outline" onclick="openDisc({{ json_encode($d->only(['id','name','percent','age_min','age_max','is_active'])) }})"><i class="fa-solid fa-pen"></i></button>
                    <form method="POST" action="{{ route('opd.discounts.destroy', $d) }}" style="display:inline" onsubmit="return confirm('Remove this discount?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline" style="color:var(--sc-red)"><i class="fa-solid fa-trash"></i></button></form>
                    @endif
                </td>
            </tr>
            @empty
            <tr><td colspan="4" class="text-muted" style="text-align:center;padding:14px">No discounts. Example: "Senior citizen", 20%, age 60+.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header"><i class="fa-solid fa-calculator"></i> Check a fee</div>
    <div class="card-body">
        <div class="form-group" style="position:relative">
            <label class="form-label">Patient</label>
            <input type="text" id="chk-patient-q" class="form-control" placeholder="Name, JHC ID or phone" autocomplete="off" oninput="chkSearch()">
            <input type="hidden" id="chk-patient">
            <div id="chk-results" style="position:absolute;z-index:20;left:0;right:0;background:var(--sc-card,#fff);border:1px solid var(--sc-border);border-radius:8px;display:none;max-height:220px;overflow:auto"></div>
        </div>
        <div class="form-row cols-2">
            <div class="form-group"><label class="form-label">Doctor</label>
                <select id="chk-doctor" class="form-control" onchange="chkQuote()">@foreach($doctors as $doc)<option value="{{ $doc->id }}">{{ $doc->full_name }}</option>@endforeach</select></div>
            <div class="form-group"><label class="form-label">Department</label>
                <select id="chk-dept" class="form-control" onchange="chkQuote()"><option value="">Doctor's main</option>@foreach($departments as $dep)<option value="{{ $dep->id }}">{{ $dep->name }}</option>@endforeach</select></div>
        </div>
        <div id="chk-out" class="text-muted" style="font-size:13px">Pick a patient to see today's fee.</div>
    </div>
</div>
</div>

@if($canEdit)
<div id="rule-modal" class="modal-overlay">
    <div class="modal-box" style="max-width:560px">
        <div class="modal-header"><i class="fa-solid fa-indian-rupee-sign" style="color:var(--sc-teal)"></i><h3 id="rule-title">Add Fee Rule</h3><button class="modal-close" type="button" onclick="closeModal('rule-modal')">&times;</button></div>
        <form id="rule-form" method="POST" action="{{ route('opd.fees.store') }}">
            @csrf <input type="hidden" name="_method" id="rule-method" value="POST">
            <div class="modal-body">
                @if($errors->any())<div class="alert alert-danger alert-keep"><i class="fa-solid fa-triangle-exclamation"></i> <div>Not saved: {{ implode(' ', $errors->all()) }}</div></div>@endif
                <div class="form-row cols-2">
                    <div class="form-group"><label class="form-label">Department</label>
                        <select name="department_id" id="r-dept" class="form-control"><option value="">Any department</option>@foreach($departments as $dep)<option value="{{ $dep->id }}">{{ $dep->name }}</option>@endforeach</select></div>
                    <div class="form-group"><label class="form-label">Doctor</label>
                        <select name="doctor_id" id="r-doc" class="form-control"><option value="">Any doctor</option>@foreach($doctors as $doc)<option value="{{ $doc->id }}">{{ $doc->full_name }}</option>@endforeach</select></div>
                    <div class="form-group"><label class="form-label">New visit fee (₹)</label><input type="number" name="new_fee" id="r-new" class="form-control" min="0" step="1" placeholder="blank = doctor's fee"></div>
                    <div class="form-group"><label class="form-label">Follow-up fee (₹)</label><input type="number" name="followup_fee" id="r-fu" class="form-control" min="0" step="1" placeholder="blank = service price"></div>
                    <div class="form-group"><label class="form-label">Follow-up fee within (days)</label><input type="number" name="followup_days" id="r-fud" class="form-control" min="0" placeholder="e.g. 30"></div>
                    <div class="form-group"><label class="form-label">Free follow-up within (days)</label><input type="number" name="free_days" id="r-free" class="form-control" min="0" placeholder="e.g. 7"></div>
                    <div class="form-group"><label class="form-label">Free visits allowed</label><input type="number" name="free_visits" id="r-fv" class="form-control" min="1" max="20" value="1"></div>
                    <div class="form-group" style="display:flex;align-items:flex-end"><label style="display:flex;gap:8px;align-items:center;font-weight:normal;margin-bottom:8px"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" id="r-active" value="1" checked> Active</label></div>
                </div>
                <div class="form-group"><label class="form-label">Notes</label><input type="text" name="notes" id="r-notes" class="form-control" maxlength="255"></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-outline" onclick="closeModal('rule-modal')">Cancel</button><button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Save</button></div>
        </form>
    </div>
</div>

<div id="disc-modal" class="modal-overlay">
    <div class="modal-box" style="max-width:460px">
        <div class="modal-header"><i class="fa-solid fa-percent" style="color:var(--sc-teal)"></i><h3 id="disc-title">Add Discount</h3><button class="modal-close" type="button" onclick="closeModal('disc-modal')">&times;</button></div>
        <form id="disc-form" method="POST" action="{{ route('opd.discounts.store') }}">
            @csrf <input type="hidden" name="_method" id="disc-method" value="POST">
            <div class="modal-body">
                <div class="form-row cols-2">
                    <div class="form-group"><label class="form-label">Name <span class="req">*</span></label><input type="text" name="name" id="d-name" class="form-control" required maxlength="80" placeholder="Senior citizen"></div>
                    <div class="form-group"><label class="form-label">Percent <span class="req">*</span></label><input type="number" name="percent" id="d-pct" class="form-control" required min="0" max="100" step="0.5"></div>
                    <div class="form-group"><label class="form-label">Auto from age</label><input type="number" name="age_min" id="d-min" class="form-control" min="0" max="130" placeholder="e.g. 60"></div>
                    <div class="form-group"><label class="form-label">Auto up to age</label><input type="number" name="age_max" id="d-max" class="form-control" min="0" max="130" placeholder="e.g. 12"></div>
                </div>
                <div class="text-small text-muted" style="margin-bottom:8px">Leave both ages blank for a discount staff pick on the bill (e.g. staff family, BPL).</div>
                <label style="display:flex;gap:8px;align-items:center;font-weight:normal"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" id="d-active" value="1" checked> Active</label>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-outline" onclick="closeModal('disc-modal')">Cancel</button><button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Save</button></div>
        </form>
    </div>
</div>
@endif

<script>
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
@if($canEdit)
function openRule(r) {
    const f = document.getElementById('rule-form');
    document.getElementById('rule-title').textContent = r ? 'Edit Fee Rule' : 'Add Fee Rule';
    f.action = r ? '{{ url('/opd/fee-rules') }}/' + r.id : '{{ route('opd.fees.store') }}';
    document.getElementById('rule-method').value = r ? 'PUT' : 'POST';
    const set = (id, v) => document.getElementById(id).value = (v === null || v === undefined) ? '' : v;
    set('r-dept', r?.department_id); set('r-doc', r?.doctor_id);
    set('r-new', r?.new_fee !== null && r?.new_fee !== undefined ? parseFloat(r.new_fee) : '');
    set('r-fu', r?.followup_fee !== null && r?.followup_fee !== undefined ? parseFloat(r.followup_fee) : '');
    set('r-fud', r?.followup_days); set('r-free', r?.free_days); set('r-fv', r?.free_visits ?? 1); set('r-notes', r?.notes);
    document.getElementById('r-active').checked = r ? !!r.is_active : true;
    document.getElementById('rule-modal').classList.add('open');
}
function openDisc(d) {
    const f = document.getElementById('disc-form');
    document.getElementById('disc-title').textContent = d ? 'Edit Discount' : 'Add Discount';
    f.action = d ? '{{ url('/opd/discounts') }}/' + d.id : '{{ route('opd.discounts.store') }}';
    document.getElementById('disc-method').value = d ? 'PUT' : 'POST';
    document.getElementById('d-name').value = d?.name ?? '';
    document.getElementById('d-pct').value = d ? parseFloat(d.percent) : '';
    document.getElementById('d-min').value = d?.age_min ?? '';
    document.getElementById('d-max').value = d?.age_max ?? '';
    document.getElementById('d-active').checked = d ? !!d.is_active : true;
    document.getElementById('disc-modal').classList.add('open');
}
@if($errors->any())
document.addEventListener('DOMContentLoaded', () => openRule({ id: null, department_id: @json(old('department_id')), doctor_id: @json(old('doctor_id')), new_fee: @json(old('new_fee')), followup_fee: @json(old('followup_fee')), followup_days: @json(old('followup_days')), free_days: @json(old('free_days')), free_visits: @json(old('free_visits')), notes: @json(old('notes')), is_active: true }));
@endif
@endif

let chkTimer;
function chkSearch() {
    clearTimeout(chkTimer);
    const q = document.getElementById('chk-patient-q').value.trim(), box = document.getElementById('chk-results');
    if (q.length < 2) { box.style.display = 'none'; return; }
    chkTimer = setTimeout(() => fetch('{{ route('patients.search') }}?q=' + encodeURIComponent(q), { headers: { Accept: 'application/json' } })
        .then(r => r.json()).then(list => {
            box.innerHTML = list.map(p => `<div style="padding:7px 10px;cursor:pointer;border-bottom:1px solid var(--sc-border);font-size:13px" data-id="${p.id}" data-name="${escapeHtml(p.full_name)}">${escapeHtml(p.full_name)} <span class="text-muted mono">${escapeHtml(p.jhc_id || '')}</span></div>`).join('') || '<div style="padding:8px 10px" class="text-muted">No match</div>';
            box.style.display = 'block';
            box.querySelectorAll('[data-id]').forEach(el => el.onclick = () => {
                document.getElementById('chk-patient').value = el.dataset.id;
                document.getElementById('chk-patient-q').value = el.dataset.name;
                box.style.display = 'none';
                chkQuote();
            });
        }), 250);
}
function escapeHtml(s) { return String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c])); }
function chkQuote() {
    const pid = document.getElementById('chk-patient').value, out = document.getElementById('chk-out');
    if (!pid) return;
    const qs = new URLSearchParams({ patient_id: pid, doctor_id: document.getElementById('chk-doctor').value, department_id: document.getElementById('chk-dept').value });
    fetch('{{ route('opd.fee-quote') }}?' + qs, { headers: { Accept: 'application/json' } }).then(r => r.json()).then(q => {
        const money = n => '₹' + Math.round(n).toLocaleString('en-IN');
        out.classList.remove('text-muted');
        out.innerHTML = `<div style="font-size:20px;font-weight:700;color:${q.kind === 'free' ? 'var(--sc-green)' : 'var(--sc-navy)'}">${q.kind === 'free' ? 'FREE' : money(q.net)}</div>
            <div>${escapeHtml(q.label)}${q.discount ? ` · ${escapeHtml(q.discount.name)} ${q.discount.percent}% off ${money(q.base)}` : ''}</div>
            <div class="text-small text-muted">Rule: ${escapeHtml(q.rule || 'none (doctor\'s fee)')} · new ${money(q.new_fee)}, follow-up ${money(q.followup_fee)} · ${q.last_paid ? 'last paid ' + q.last_paid + ' (' + q.days + ' days ago)' : 'no earlier paid consultation'}${q.age !== null ? ' · age ' + q.age : ''}</div>`;
    });
}
</script>
@endsection
