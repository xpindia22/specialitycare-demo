@extends('layouts.app')
@section('title', 'Consent Templates')
@section('page-title', 'Consent Templates')
@section('content')

<div class="page-header">
    <div class="page-header-left"><h1>Consent Templates</h1></div>
    @include('templates._toolbar', ['inline'=>true, 'extra' => [
        ['url'=>route('consent-templates.create'), 'icon'=>'fa-plus', 'label'=>'New Template', 'bg'=>'#047857'],
    ]])
</div>

@if(session('success'))<div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> {{ session('success') }}</div>@endif

@if($templates->isEmpty())
<div class="card"><div class="card-body" style="padding:0">
    <x-empty-state
        icon="fa-file-signature"
        title="No consent templates yet"
        message="Create reusable consent text once (with placeholders like {patient_name}, {procedure}) so you can generate signed consents for any patient in one click."
        action="Create your first template"
        :actionUrl="route('consent-templates.create')" />
</div></div>
@else
<div class="card">
    <div class="table-wrap">
        <table class="sc-table">
            <thead>
                <tr><th>Name</th><th>Category</th><th>Status</th><th>Created By</th><th></th></tr>
            </thead>
            <tbody>
            @foreach($templates as $t)
            <tr>
                <td class="fw-600">{{ $t->name }}</td>
                <td>{{ \App\Models\ConsentTemplate::CATEGORIES[$t->category] ?? '—' }}</td>
                <td><span class="badge {{ $t->is_active ? 'badge-green' : 'badge-gray' }}">{{ $t->is_active ? 'Active' : 'Archived' }}</span></td>
                <td class="text-small text-muted">{{ optional($t->createdBy)->name ?: '—' }}</td>
                <td>
                    <div style="display:flex;gap:4px">
                        <a href="{{ route('consent-templates.edit', $t) }}" class="btn btn-sm btn-outline"><i class="fa-solid fa-pen"></i></a>
                        <form method="POST" action="{{ route('consent-templates.destroy', $t) }}" onsubmit="return confirm('Archive this template?')">
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
<div class="alert" style="background:#f0f4f8;color:var(--sc-muted);font-size:12px;border-radius:8px;padding:10px 14px;margin-top:14px">
    <i class="fa-solid fa-circle-info"></i> Placeholders inside template body: <code>{patient_name}</code>, <code>{jhc_id}</code>, <code>{age}</code>, <code>{gender}</code>, <code>{procedure}</code>, <code>{date}</code> — these are substituted when generating a consent for a patient.
</div>
@endif
@endsection
