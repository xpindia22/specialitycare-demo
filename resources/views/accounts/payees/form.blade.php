@extends('layouts.app')
@section('title', $payee->exists ? 'Edit Payee' : 'New Payee')
@section('page-title', $payee->exists ? 'Edit Payee' : 'New Payee')
@section('content')

<div class="page-header">
    <div class="page-header-left">
        <a href="{{ route('accounts.payees.index') }}" class="text-small"><i class="fa-solid fa-arrow-left"></i> Payees</a>
        <h1>{{ $payee->exists ? "Edit: {$payee->name}" : 'Add Payee' }}</h1>
    </div>
</div>

@if($errors->any())<div class="alert alert-error"><ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

<form method="POST" action="{{ $payee->exists ? route('accounts.payees.update', $payee) : route('accounts.payees.store') }}" class="card" style="padding:16px;max-width:760px">
    @csrf
    @if($payee->exists) @method('PUT') @endif

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
        <div>
            <label>Name *</label>
            <input name="name" value="{{ old('name', $payee->name) }}" class="form-control" required placeholder="e.g., Sample Physician">
        </div>
        <div>
            <label>Role</label>
            <select name="role" class="form-control">
                @php $currentRole = old('role', $payee->role); @endphp
                <option value="">— select —</option>
                @foreach(['Doctor','Nurse','Receptionist','Maid','Cleaner','Driver','Cook','Security','Lab Technician','Pharmacist','Other'] as $r)
                    <option value="{{ $r }}" {{ $currentRole === $r ? 'selected' : '' }}>{{ $r }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label>Monthly amount (₹) *</label>
            <input type="number" step="0.01" min="0" name="monthly_amount" value="{{ old('monthly_amount', (float)$payee->monthly_amount) }}" class="form-control mono" required>
        </div>
        <div>
            <label>Paid from *</label>
            <select name="pay_from_ledger_id" class="form-control" required>
                @foreach($payFrom as $l)
                    <option value="{{ $l->id }}" {{ (int)old('pay_from_ledger_id', $payee->pay_from_ledger_id)===$l->id ? 'selected' : '' }}>{{ $l->name }} ({{ $l->party_type }})</option>
                @endforeach
            </select>
        </div>

        <div style="grid-column:span 2">
            <label>Expense ledger
                <span class="text-muted text-small">(leave blank to auto-create 'Salary - {name}' under Salaries &amp; Wages; pick one to reuse e.g. EXP-MAID for all maids)</span>
            </label>
            <select name="ledger_id" class="form-control">
                <option value="">— auto-create per-payee ledger —</option>
                @foreach($ledgers as $l)
                    <option value="{{ $l->id }}" {{ (int)old('ledger_id', $payee->ledger_id)===$l->id ? 'selected' : '' }}>
                        {{ $l->name }} ({{ $l->group?->name }})
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label>Link to HR staff record <span class="text-muted text-small">(optional — pre-fills name &amp; salary)</span></label>
            <select name="hr_staff_id" class="form-control" id="hrStaffPicker" onchange="prefillFromStaff(this)">
                <option value="">— not linked —</option>
                @foreach($hrStaff as $s)
                    @php $fullName = trim(($s->first_name ?? '') . ' ' . ($s->last_name ?? '')); @endphp
                    <option value="{{ $s->id }}" data-name="{{ $fullName }}" data-salary="{{ (float)($s->salary ?: 0) }}" {{ (int)old('hr_staff_id', $payee->hr_staff_id)===$s->id ? 'selected' : '' }}>
                        {{ $fullName }} — {{ $s->designation }} @if($s->salary)(₹{{ number_format((float)$s->salary,2) }})@endif
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label>… or link to HR doctor <span class="text-muted text-small">(optional)</span></label>
            <select name="hr_doctor_id" class="form-control" id="hrDoctorPicker" onchange="prefillFromStaff(this)">
                <option value="">— not linked —</option>
                @foreach($hrDoctors ?? [] as $d)
                    @php $fullName = 'Dr. ' . trim(($d->first_name ?? '') . ' ' . ($d->last_name ?? '')); @endphp
                    <option value="{{ $d->id }}" data-name="{{ $fullName }}" data-salary="{{ (float)($d->salary ?: 0) }}" {{ (int)old('hr_doctor_id', $payee->hr_doctor_id)===$d->id ? 'selected' : '' }}>
                        {{ $fullName }} — {{ $d->specialization }} @if($d->salary)(₹{{ number_format((float)$d->salary,2) }})@endif
                    </option>
                @endforeach
            </select>
            <div class="text-small text-muted" style="margin-top:3px">Link a Payee to either a staff member OR a doctor (not both). Used by HR Payroll to auto-post salary vouchers.</div>
        </div>

        <div>
            <label>Phone</label>
            <input name="contact_phone" value="{{ old('contact_phone', $payee->contact_phone) }}" class="form-control" placeholder="+91…">
        </div>
        <div>
            <label style="display:flex;gap:6px;align-items:center;margin-top:22px">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $payee->exists ? $payee->is_active : 1) ? 'checked' : '' }}> Active
            </label>
        </div>
        <div style="grid-column:span 2">
            <label>Notes</label>
            <textarea name="notes" rows="2" class="form-control">{{ old('notes', $payee->notes) }}</textarea>
        </div>
    </div>

    <div style="margin-top:16px;display:flex;gap:8px">
        <button class="btn btn-primary"><i class="fa-solid fa-save"></i> Save</button>
        <a href="{{ route('accounts.payees.index') }}" class="btn btn-outline">Cancel</a>
    </div>
</form>

<script>
function prefillFromStaff(sel) {
    const opt = sel.options[sel.selectedIndex];
    if (!opt || !opt.dataset.name) return;
    const nameInput = document.querySelector('input[name="name"]');
    const amtInput  = document.querySelector('input[name="monthly_amount"]');
    // Only prefill if fields are empty / zero, to avoid clobbering user input
    if (nameInput && !nameInput.value.trim()) nameInput.value = opt.dataset.name;
    if (amtInput && (!amtInput.value || parseFloat(amtInput.value) === 0)) {
        const sal = parseFloat(opt.dataset.salary || 0);
        if (sal > 0) amtInput.value = sal;
    }
}
</script>

@endsection
