{{-- FILE: resources/views/lab/index.blade.php --}}
@extends('layouts.app')
@section('title','Laboratory')
@section('page-title','Laboratory')
@section('content')
<div class="page-header">
    <div class="page-header-left"><h1>Laboratory</h1><div class="breadcrumb">Lab orders & results</div></div>
    @include('diagnostics._toolbar', ['inline'=>true, 'extra' => [
        ['url'=>route('lab.create-order'), 'icon'=>'fa-plus', 'label'=>'New Lab Order', 'bg'=>'#047857'],
        ['url'=>route('lab.report.upload'), 'icon'=>'fa-file-arrow-up', 'label'=>'Upload Lab Report', 'bg'=>'#7c3aed'],
        ['url'=>route('lab.pending'), 'icon'=>'fa-inbox', 'label'=>'Pending Reports'.(($pendingCount ?? 0) ? ' ('.$pendingCount.')' : ''), 'bg'=>(($pendingCount ?? 0) ? '#b45309' : '#64748b')],
        ['url'=>route('settings.index', ['tab' => 'lab']), 'icon'=>'fa-flask-vial', 'label'=>'Tests & Pricing', 'bg'=>'#4b5563'],
        ['url'=>route('settings.index', ['tab' => 'panels']), 'icon'=>'fa-layer-group', 'label'=>'Panels', 'bg'=>'#64748b'],
    ]])
</div>

<div class="grid grid-4 mb-4">
    @php
        $statusCounts = $orders->groupBy('status');
    @endphp
    @foreach(['Ordered'=>['amber','fa-clock'],'Collected'=>['teal','fa-vials'],'Processing'=>['purple','fa-cog'],'Completed'=>['green','fa-check-circle']] as $st=>[$color,$icon])
    <div class="stat-card">
        <div class="stat-icon {{ $color }}"><i class="fa-solid {{ $icon }}"></i></div>
        <div><div class="stat-value">{{ $orders->where('status',$st)->count() }}</div><div class="stat-label">{{ $st }}</div></div>
    </div>
    @endforeach
</div>

<div class="card">
    <div class="card-header">
        <i class="fa-solid fa-flask-vial"></i> Lab Orders
        <div class="card-actions">
            <form method="GET" style="display:flex;gap:8px">
                <select name="status" class="form-control" style="width:130px">
                    <option value="">All Status</option>
                    @foreach(['Ordered','Collected','Processing','Completed','Cancelled'] as $s)
                        <option value="{{ $s }}" {{ request('status')==$s ? 'selected':'' }}>{{ $s }}</option>
                    @endforeach
                </select>
                <input type="date" name="date" class="form-control" style="width:150px" value="{{ request('date') }}">
                <button type="submit" class="btn btn-primary btn-sm">Filter</button>
            </form>
        </div>
    </div>
    <div class="table-wrap">
        <table class="sc-table">
            <thead>
                <tr><th>Order No.</th><th>Patient</th><th>Date</th><th>Tests</th><th>Amount</th><th>Status</th><th>Actions</th></tr>
            </thead>
            <tbody>
            @forelse($orders as $o)
            <tr>
                <td class="mono fw-600">{{ $o->order_no }}</td>
                <td>
                    <a href="{{ route('patients.show', $o->patient) }}" style="font-weight:600;color:var(--sc-navy);text-decoration:none">{{ $o->patient->full_name }}</a>
                    <div class="text-small text-muted mono">{{ $o->patient->jhc_id }}</div>
                </td>
                <td>{{ $o->ordered_date->format('d M Y') }}</td>
                <td>
                    <div style="display:flex;flex-wrap:wrap;gap:4px">
                        @foreach($o->items->take(3) as $item)
                            <span class="badge badge-navy" style="font-size:10px">{{ $item->test?->name ?? $item->panel?->name ?? '—' }}</span>
                        @endforeach
                        @if($o->items->count() > 3)<span class="text-muted text-small">+{{ $o->items->count()-3 }} more</span>@endif
                    </div>
                </td>
                <td class="fw-600">₹{{ number_format($o->net_amount, 2) }}</td>
                <td>
                    @php $bc=['Ordered'=>'badge-amber','Collected'=>'badge-teal','Processing'=>'badge-purple','Completed'=>'badge-green','Cancelled'=>'badge-red']; @endphp
                    <span class="badge {{ $bc[$o->status] ?? 'badge-gray' }}">{{ $o->status }}</span>
                </td>
                <td>
                    <div style="display:flex;gap:5px">
                        <a href="{{ route('lab.order', $o) }}" class="btn btn-sm btn-outline">View</a>
                        @if($o->status === 'Completed')
                            <a href="{{ route('lab.print-report', $o) }}" class="btn btn-sm btn-outline" target="_blank"><i class="fa-solid fa-print"></i></a>
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="7" class="text-center text-muted" style="padding:40px">No lab orders found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $orders->links() }}</div>
</div>
@endsection
