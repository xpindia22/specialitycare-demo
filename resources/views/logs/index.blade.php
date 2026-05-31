{{-- FILE: resources/views/logs/index.blade.php --}}
@extends('layouts.app')
@section('title','Activity Logs')
@section('page-title','Activity Logs')
@section('content')

<div class="page-header">
    <div class="page-header-left">
        <h1>Activity Logs</h1>
        <div class="breadcrumb">Administration &rsaquo; Activity Logs</div>
    </div>
</div>

{{-- Filters --}}
<div class="card mb-3">
    <div class="card-header"><i class="fa-solid fa-filter"></i> Filter Logs</div>
    <div class="card-body" style="padding:14px 18px">
        <form method="GET" action="{{ route('logs.index') }}">
            <div style="display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end">
                <div class="form-group" style="margin-bottom:0;min-width:150px">
                    <label class="form-label">User</label>
                    <select name="user_id" class="form-control">
                        <option value="">All Users</option>
                        @foreach($users as $u)
                            <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group" style="margin-bottom:0;min-width:140px">
                    <label class="form-label">Action</label>
                    <select name="action" class="form-control">
                        <option value="">All Actions</option>
                        @foreach($actions as $a)
                            <option value="{{ $a }}" {{ request('action') === $a ? 'selected' : '' }}>{{ ucfirst($a) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group" style="margin-bottom:0">
                    <label class="form-label">From Date</label>
                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                </div>
                <div class="form-group" style="margin-bottom:0">
                    <label class="form-label">To Date</label>
                    <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                </div>
                <div class="form-group" style="margin-bottom:0;min-width:180px;flex:1">
                    <label class="form-label">Search Description</label>
                    <input type="text" name="search" class="form-control" placeholder="keyword…" value="{{ request('search') }}">
                </div>
                <div style="display:flex;gap:6px;padding-bottom:1px">
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-magnifying-glass"></i> Filter</button>
                    <a href="{{ route('logs.index') }}" class="btn btn-outline">Reset</a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <i class="fa-solid fa-clock-rotate-left"></i> Log Entries
        <span class="text-muted text-small" style="margin-left:8px;font-weight:400">{{ $logs->total() }} total</span>
    </div>
    <div class="table-wrap">
        <table class="sc-table">
            <thead>
                <tr>
                    <th style="width:13%">Date &amp; Time</th>
                    <th style="width:14%">User</th>
                    <th style="width:13%">Action</th>
                    <th>Description</th>
                    <th style="width:11%">IP Address</th>
                </tr>
            </thead>
            <tbody>
            @forelse($logs as $log)
            <tr>
                <td style="white-space:nowrap">
                    <div style="font-size:12px">{{ $log->created_at->format('d M Y') }}</div>
                    <div style="font-size:11px;color:var(--sc-muted)">{{ $log->created_at->format('h:i A') }}</div>
                </td>
                <td>
                    <div style="font-size:12.5px;font-weight:600">{{ $log->user_name }}</div>
                </td>
                <td>
                    @php
                        $actionLower = strtolower($log->action);
                        $badge = match(true) {
                            str_contains($actionLower, 'creat') || str_contains($actionLower, 'admit') || str_contains($actionLower, 'issue') || str_contains($actionLower, 'added') => 'badge-green',
                            str_contains($actionLower, 'updat') || str_contains($actionLower, 'transfer') || str_contains($actionLower, 'start') || str_contains($actionLower, 'mark') => 'badge-blue',
                            str_contains($actionLower, 'delet') || str_contains($actionLower, 'discharg') || str_contains($actionLower, 'lock') || str_contains($actionLower, 'stop') => 'badge-red',
                            str_contains($actionLower, 'complet') || str_contains($actionLower, 'done') || str_contains($actionLower, 'unlock') => 'badge-teal',
                            default => 'badge-grey',
                        };
                    @endphp
                    <span class="badge {{ $badge }}" style="font-size:11px">{{ $log->action }}</span>
                </td>
                <td style="font-size:12.5px">{{ $log->description }}</td>
                <td style="font-size:11px;color:var(--sc-muted);font-family:'DM Mono',monospace">{{ $log->ip_address ?? '—' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="text-center text-muted" style="padding:48px">
                    <i class="fa-solid fa-inbox" style="font-size:2rem;margin-bottom:10px;display:block;opacity:.3"></i>
                    No activity logs found matching your filters.
                </td>
            </tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px">
        <div style="font-size:12px;color:var(--sc-muted)">
            Showing {{ $logs->firstItem() ?? 0 }}–{{ $logs->lastItem() ?? 0 }} of {{ $logs->total() }} entries
        </div>
        {{ $logs->withQueryString()->links() }}
    </div>
</div>

@push('styles')
<style>
.badge-blue { background: #dbeafe; color: #1d4ed8; }
.badge-grey { background: #f3f4f6; color: #555; }
</style>
@endpush
@endsection
