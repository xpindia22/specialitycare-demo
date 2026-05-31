@extends('layouts.app')

@section('title','Login Activity')

@section('content')
<div class="container-fluid">
    <h1 class="h3 mb-3">Login Activity <small class="text-muted" style="font-size:13px;">— IP &amp; country log (owner only)</small></h1>

    <form method="GET" class="card mb-3">
        <div class="card-body d-flex" style="gap:10px;flex-wrap:wrap;align-items:end;">
            <div>
                <label class="form-label" style="font-size:12px;">Search (email / IP / country)</label>
                <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="e.g. demo@… or 1.2.3.4">
            </div>
            <div>
                <label class="form-label" style="font-size:12px;">Event</label>
                <select name="event" class="form-control">
                    <option value="">All</option>
                    @foreach(['login'=>'Login','failed'=>'Failed','logout'=>'Logout'] as $k=>$v)
                        <option value="{{ $k }}" @selected(request('event')===$k)>{{ $v }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <button class="btn btn-primary">Filter</button>
                <a href="{{ route('admin.login-activity') }}" class="btn btn-light">Reset</a>
            </div>
        </div>
    </form>

    <div class="card">
        <div class="card-body">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>When</th>
                        <th>Event</th>
                        <th>Email</th>
                        <th>IP Address</th>
                        <th>Country</th>
                        <th>City</th>
                        <th>Device</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($activities as $a)
                    <tr>
                        <td style="white-space:nowrap;">{{ optional($a->created_at)->format('d M Y, H:i') }}</td>
                        <td>
                            @php $badge = ['login'=>'success','failed'=>'danger','logout'=>'secondary'][$a->event] ?? 'secondary'; @endphp
                            <span class="badge bg-{{ $badge }}">{{ ucfirst($a->event) }}</span>
                        </td>
                        <td>{{ $a->email ?: '—' }}</td>
                        <td><code>{{ $a->ip_address ?: '—' }}</code></td>
                        <td>{{ $a->country ?: '—' }}</td>
                        <td>{{ $a->city ?: '—' }}</td>
                        <td style="max-width:280px;font-size:11px;color:#64748b;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="{{ $a->user_agent }}">{{ $a->user_agent ?: '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted">No login activity recorded yet.</td></tr>
                @endforelse
                </tbody>
            </table>
            {{ $activities->links() }}
        </div>
    </div>
</div>
@endsection
