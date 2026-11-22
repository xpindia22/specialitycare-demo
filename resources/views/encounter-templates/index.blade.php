@extends('layouts.app')
@section('title','Encounter Templates')
@section('page-title','Encounter Templates')
@section('content')

<div class="page-header">
    <div class="page-header-left"><h1>Encounter Templates</h1></div>
    @include('templates._toolbar', ['inline'=>true, 'extra' => [
        ['url'=>route('encounter-templates.create'), 'icon'=>'fa-plus', 'label'=>'New Template', 'bg'=>'#047857'],
    ]])
</div>

@if(session('success'))
<div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> {{ session('success') }}</div>
@endif

@if($templates->isNotEmpty())
<div style="margin-bottom:12px;display:flex;align-items:center;gap:10px">
    <div style="position:relative;flex:1;max-width:420px">
        <i class="fa-solid fa-magnifying-glass" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--sc-muted);font-size:13px"></i>
        <input type="text" id="tpl-search" placeholder="Search by name, specialization, diagnosis…"
               class="form-control" style="padding-left:34px" oninput="filterTemplateRows(this.value)" autofocus autocomplete="off">
    </div>
    <span id="tpl-search-count" style="font-size:12px;color:var(--sc-muted)"></span>
</div>
@endif

@if($templates->isEmpty())
<div class="card"><div class="card-body" style="padding:0">
    <x-empty-state
        icon="fa-layer-group"
        title="No encounter templates yet"
        message="Templates pre-fill chief complaint, clinical notes, diagnoses, prescriptions and lab orders so you spend less time typing per visit."
        action="Create your first template"
        :actionUrl="route('encounter-templates.create')" />
</div></div>
@else
<div class="card">
    <div class="table-wrap">
        <table class="sc-table">
            <thead>
                <tr><th>Name</th><th>Specialization</th><th>Diagnoses</th><th>Rx Items</th><th>Labs</th><th>Status</th><th></th></tr>
            </thead>
            <tbody>
            @foreach($templates as $t)
            <tr>
                <td class="fw-600">{{ $t->name }}</td>
                <td>{{ $t->specialization ?: '—' }}</td>
                <td>{{ count($t->diagnoses ?? []) }} diagnosis{{ count($t->diagnoses ?? []) != 1 ? 'es' : '' }}</td>
                <td>{{ count($t->prescription_template ?? []) }} drug{{ count($t->prescription_template ?? []) != 1 ? 's' : '' }}</td>
                @php
                    $labs = $t->lab_orders ?? ['tests' => [], 'panels' => []];
                    $nT = count($labs['tests'] ?? []);
                    $nP = count($labs['panels'] ?? []);
                @endphp
                <td>{{ $nT + $nP === 0 ? '—' : ($nP ? $nP.' panel'.($nP>1?'s':'') : '') . ($nP && $nT ? ' + ' : '') . ($nT ? $nT.' test'.($nT>1?'s':'') : '') }}</td>
                <td><span class="badge {{ $t->is_active ? 'badge-green' : 'badge-gray' }}">{{ $t->is_active ? 'Active' : 'Inactive' }}</span></td>
                <td>
                    <div style="display:flex;gap:4px">
                        <a href="{{ route('encounter-templates.edit', $t) }}" class="btn btn-sm btn-outline"><i class="fa-solid fa-pen"></i></a>
                        <form method="POST" action="{{ route('encounter-templates.destroy', $t) }}" onsubmit="return confirm('Delete?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline" style="color:var(--sc-red);border-color:var(--sc-red)"><i class="fa-solid fa-trash"></i></button>
                        </form>
                    </div>
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<script>
function filterTemplateRows(q) {
    const s = (q || '').trim().toLowerCase();
    const rows = document.querySelectorAll('.sc-table tbody tr');
    let shown = 0, total = rows.length;
    rows.forEach(tr => {
        const match = !s || tr.textContent.toLowerCase().includes(s);
        tr.style.display = match ? '' : 'none';
        if (match) shown++;
    });
    const cnt = document.getElementById('tpl-search-count');
    if (cnt) cnt.textContent = s ? `${shown} of ${total}` : '';
}
</script>
@endsection
