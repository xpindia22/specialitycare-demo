@extends('layouts.app')
@section('title','Charge Templates')
@section('page-title','Charge Templates')
@section('content')

<div class="page-header">
    <div class="page-header-left"><h1>Charge Templates</h1></div>
    @include('templates._toolbar', ['inline'=>true, 'extra' => [
        ['url'=>route('charge-templates.create'), 'icon'=>'fa-plus', 'label'=>'New Template', 'bg'=>'#047857'],
    ]])
</div>

@if(session('success'))
<div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> {{ session('success') }}</div>
@endif

@if($templates->isEmpty())
<div class="card"><div class="card-body" style="padding:0">
    <x-empty-state
        icon="fa-tags"
        title="No charge templates yet"
        message="Save common charge bundles as templates so you can apply them to bills and admissions in one click."
        action="Create your first template"
        :actionUrl="route('charge-templates.create')" />
</div></div>
@else
<div style="margin-bottom:12px;display:flex;align-items:center;gap:10px">
    <div style="position:relative;flex:1;max-width:420px">
        <i class="fa-solid fa-magnifying-glass" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--sc-muted);font-size:13px"></i>
        <input type="text" id="ctpl-search" placeholder="Search by name, description, item…"
               class="form-control" style="padding-left:34px" oninput="filterChargeTemplateRows(this.value)" autofocus autocomplete="off">
    </div>
    <span id="ctpl-search-count" style="font-size:12px;color:var(--sc-muted)"></span>
</div>
<div class="card">
    <div class="table-wrap">
        <table class="sc-table">
            <thead>
                <tr><th>Name</th><th>Description</th><th>Items</th><th>Total</th><th>Status</th><th>Created By</th><th></th></tr>
            </thead>
            <tbody>
            @foreach($templates as $t)
            <tr>
                <td class="fw-600">{{ $t->name }}</td>
                <td class="text-small text-muted">{{ $t->description ?: '—' }}</td>
                <td>
                    @foreach($t->items as $item)
                    <span class="badge badge-navy" style="margin-right:3px;margin-bottom:2px">{{ $item['description'] }}</span>
                    @endforeach
                </td>
                <td class="mono fw-600">₹{{ number_format(collect($t->items)->sum(fn($i) => $i['unit_price'] * $i['quantity']), 2) }}</td>
                <td><span class="badge {{ $t->is_active ? 'badge-green' : 'badge-gray' }}">{{ $t->is_active ? 'Active' : 'Inactive' }}</span></td>
                <td class="text-small">{{ $t->createdBy?->name ?? '—' }}</td>
                <td>
                    <div style="display:flex;gap:4px">
                        <a href="{{ route('charge-templates.edit', $t) }}" class="btn btn-sm btn-outline"><i class="fa-solid fa-pen"></i></a>
                        <form method="POST" action="{{ route('charge-templates.destroy', $t) }}" onsubmit="return confirm('Delete this template?')">
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
function filterChargeTemplateRows(q) {
    const s = (q || '').trim().toLowerCase();
    const rows = document.querySelectorAll('.sc-table tbody tr');
    let shown = 0, total = rows.length;
    rows.forEach(tr => {
        const match = !s || tr.textContent.toLowerCase().includes(s);
        tr.style.display = match ? '' : 'none';
        if (match) shown++;
    });
    const cnt = document.getElementById('ctpl-search-count');
    if (cnt) cnt.textContent = s ? `${shown} of ${total}` : '';
}
</script>
@endsection
