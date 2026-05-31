@extends('owner._layout')
@section('title','Issued Licenses')
@section('content')

<div class="card">
    <div class="card-h"><i class="fa-solid fa-file-signature"></i> Issued Licenses ({{ $licenses->total() }})
        <div class="right"><a href="{{ route('owner.licenses.create') }}" class="btn btn-sm"><i class="fa-solid fa-plus"></i> Issue New</a></div>
    </div>
    @if($licenses->isEmpty())
        <div class="empty"><i class="fa-solid fa-file-circle-xmark"></i>No licenses issued yet.</div>
    @else
    <table>
        <thead><tr>
            <th style="width:70px">#</th><th>Issued</th><th>Buyer</th><th>Modules</th><th>Expires</th><th>Payment</th><th>Status</th><th></th>
        </tr></thead>
        <tbody>
            @foreach($licenses as $l)
            <tr>
                <td><a href="{{ route('owner.licenses.show', $l) }}" class="mono" style="font-weight:600">#{{ $l->id }}</a></td>
                <td><div>{{ $l->issued_at->format('d M Y') }}</div><div class="text-small text-muted mono">{{ $l->public_key_fingerprint }}</div></td>
                <td><a href="{{ route('owner.buyers.show', $l->buyer) }}" style="font-weight:600">{{ $l->buyer->name }}</a></td>
                <td>@foreach($l->modules as $m)<span class="badge teal" style="margin-right:3px">{{ $m }}</span>@endforeach</td>
                <td>{{ $l->expires_at ? $l->expires_at->format('d M Y') : 'Never' }}</td>
                <td>
                    @if($l->payment_amount)
                        <span class="mono">₹{{ number_format($l->payment_amount, 0) }}</span>
                    @else <span class="text-muted text-small">—</span>@endif
                </td>
                <td>
                    @if($l->status === 'active')<span class="badge green">Active</span>
                    @elseif($l->status === 'revoked')<span class="badge red">Revoked</span>
                    @else<span class="badge amber">Expired</span>
                    @endif
                </td>
                <td class="text-right"><a href="{{ route('owner.licenses.show', $l) }}" class="btn btn-sm btn-outline"><i class="fa-solid fa-eye"></i></a></td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div style="padding:12px">{{ $licenses->links() }}</div>
    @endif
</div>

@endsection
