@extends('owner._layout')
@section('title','Buyers')
@section('content')

<div class="card">
    <div class="card-h"><i class="fa-solid fa-users-line"></i> Buyers ({{ $buyers->count() }})
        <div class="right"><a href="{{ route('owner.buyers.create') }}" class="btn btn-sm"><i class="fa-solid fa-plus"></i> Add Buyer</a></div>
    </div>
    @if($buyers->isEmpty())
        <div class="empty"><i class="fa-solid fa-users-slash"></i>No buyers yet. Add your first one to start issuing licenses.</div>
    @else
    <table>
        <thead><tr>
            <th>Buyer</th><th>Contact</th><th>Location</th><th class="text-right">Licenses</th><th>Last License</th><th></th>
        </tr></thead>
        <tbody>
            @foreach($buyers as $b)
            @php $last = $b->licenses->first(); @endphp
            <tr>
                <td>
                    <div style="font-weight:600"><a href="{{ route('owner.buyers.show', $b) }}">{{ $b->name }}</a></div>
                    @if($b->notes)<div class="desc">{{ \Illuminate\Support\Str::limit($b->notes, 80) }}</div>@endif
                </td>
                <td>
                    @if($b->contact_name)<div>{{ $b->contact_name }}</div>@endif
                    @if($b->contact_email)<div class="text-small text-muted">{{ $b->contact_email }}</div>@endif
                    @if($b->contact_phone)<div class="text-small text-muted mono">{{ $b->contact_phone }}</div>@endif
                </td>
                <td class="text-small text-muted">{{ trim($b->city.($b->city && $b->state ? ', ' : '').$b->state) ?: '—' }}</td>
                <td class="text-right"><strong>{{ $b->licenses_count }}</strong></td>
                <td>
                    @if($last)
                        <div class="text-small">{{ $last->issued_at->format('d M Y') }}</div>
                        <div>
                            @if($last->status === 'active')<span class="badge green">Active</span>
                            @elseif($last->status === 'revoked')<span class="badge red">Revoked</span>
                            @else<span class="badge amber">Expired</span>
                            @endif
                        </div>
                    @else
                        <span class="text-muted text-small">No license yet</span>
                    @endif
                </td>
                <td class="text-right">
                    <a href="{{ route('owner.licenses.create', $b) }}" class="btn btn-sm btn-outline"><i class="fa-solid fa-file-signature"></i> Issue</a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
</div>

@endsection
