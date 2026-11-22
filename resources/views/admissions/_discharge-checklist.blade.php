{{-- Discharge checklist: $admission; optional $compact --}}
@php
    $checklist = \App\Services\DischargeChecklist::for($admission);
    $done = collect($checklist)->where('ok', true)->count();
    $total = count($checklist);
@endphp
<div class="card mb-3" id="discharge-checklist">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
        <span><i class="fa-solid fa-list-check"></i> Discharge Checklist</span>
        <span class="badge {{ $done === $total ? 'badge-green' : 'badge-amber' }}">{{ $done }}/{{ $total }} cleared</span>
    </div>
    <div class="card-body" style="padding:8px 14px">
        @foreach($checklist as $key => $it)
        <div style="display:flex;gap:10px;align-items:flex-start;padding:8px 0;{{ !$loop->last ? 'border-bottom:1px solid var(--sc-border);' : '' }}">
            <i class="fa-solid {{ $it['ok'] ? 'fa-circle-check' : 'fa-circle' }}" style="margin-top:3px;color:{{ $it['ok'] ? 'var(--sc-green)' : '#cbd5e1' }}"></i>
            <div style="flex:1;min-width:0">
                <div style="font-size:13px;font-weight:600;{{ $it['ok'] ? '' : 'color:var(--sc-navy)' }}"><i class="fa-solid {{ $it['icon'] }}" style="color:var(--sc-muted);width:16px"></i> {{ $it['label'] }}</div>
                @if($it['detail'])<div class="text-small" style="color:{{ $it['auto'] || $it['ok'] ? 'var(--sc-muted)' : '#b45309' }}">{{ $it['detail'] }}</div>@endif
                @if($it['clearance'] && $key !== 'infusions')
                    <div class="text-small text-muted">
                        Signed off by {{ $it['clearance']->user->name ?? '—' }}, {{ $it['clearance']->cleared_at->format('d M H:i') }}{{ $it['clearance']->note ? ' — '.$it['clearance']->note : '' }}
                        @if($admission->status === 'admitted' && ($it['clearance']->cleared_by === auth()->id() || auth()->user()->isAdmin()))
                        <form method="POST" action="{{ route('admissions.checklist.unclear', [$admission, $key]) }}" style="display:inline">
                            @csrf @method('DELETE')
                            <button class="btn-link" style="background:none;border:none;color:var(--sc-red);cursor:pointer;font-size:11px;padding:0">undo</button>
                        </form>
                        @endif
                    </div>
                @endif
            </div>
            @if(!$it['ok'] && $it['can'] && $admission->status === 'admitted')
            <form method="POST" action="{{ route('admissions.checklist.clear', [$admission, $key]) }}" style="display:flex;gap:4px;flex-shrink:0">
                @csrf
                <input type="text" name="note" class="form-control" placeholder="note" maxlength="300" style="width:110px;padding:4px 6px;font-size:12px;height:auto">
                <button class="btn btn-sm btn-primary" title="Sign off"><i class="fa-solid fa-check"></i></button>
            </form>
            @endif
        </div>
        @endforeach
    </div>
</div>
