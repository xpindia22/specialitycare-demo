{{-- FILE: resources/views/lab/pending.blade.php --}}
@extends('layouts.app')
@section('title','Pending Lab Reports')
@section('page-title','Pending Lab Reports')
@section('content')
<div class="page-header">
    <div class="page-header-left">
        <h1>Pending Lab Reports</h1>
        <div class="breadcrumb"><a href="{{ route('lab.index') }}">Laboratory</a> &rsaquo; Pending (WhatsApp / uploads)</div>
    </div>
    <a href="{{ route('lab.index') }}" class="btn btn-outline"><i class="fa-solid fa-arrow-left"></i> Back to Lab</a>
</div>

@if(session('success'))
<div class="alert" style="background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46;border-radius:8px;padding:10px 16px;margin-bottom:16px">{{ session('success') }}</div>
@endif

<div class="card">
    <div class="card-header"><i class="fa-solid fa-inbox"></i> Awaiting review</div>
    <div class="table-wrap">
        <table class="sc-table">
            <thead>
                <tr><th>Received</th><th>From</th><th>Name on report</th><th>Reg. ID</th><th>Sample date</th><th>Results</th><th>Read</th><th>Actions</th></tr>
            </thead>
            <tbody>
            @forelse($pending as $r)
                <tr>
                    <td>{{ $r->created_at->format('d M Y, h:i A') }}</td>
                    <td>
                        <span class="badge badge-navy" style="font-size:10px">{{ ucfirst($r->channel) }}</span>
                        <div class="mono text-small">{{ $r->sender ?? '—' }}</div>
                    </td>
                    <td>{{ $r->report_name ?? '—' }}</td>
                    <td class="mono">{{ $r->reg_id ?? '—' }}</td>
                    <td>{{ optional($r->sample_date)->format('d M Y') ?? '—' }}</td>
                    <td class="fw-600">{{ $r->analyte_count }}</td>
                    <td>
                        @if($r->source === 'ocr')
                            <span class="badge badge-amber" title="Read by OCR — check carefully">OCR</span>
                        @else
                            <span class="badge badge-green">Text</span>
                        @endif
                    </td>
                    <td style="display:flex;gap:6px">
                        <a href="{{ route('lab.pending.review', $r) }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-magnifying-glass"></i> Review</a>
                        <form method="POST" action="{{ route('lab.pending.reject', $r) }}" onsubmit="return confirm('Dismiss this report?')">
                            @csrf
                            <button class="btn btn-outline btn-sm" style="color:#b91c1c"><i class="fa-solid fa-xmark"></i></button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center text-muted" style="padding:28px">
                    <i class="fa-solid fa-check-circle" style="color:#10b981"></i> No reports waiting. Forwarded WhatsApp reports will appear here.
                </td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($pending->hasPages())<div class="card-body">{{ $pending->links() }}</div>@endif
</div>
@endsection
