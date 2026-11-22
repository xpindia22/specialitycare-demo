{{-- FILE: resources/views/pharmacy/interactions.blade.php --}}
@extends('layouts.app')
@section('title','Drug Interaction Database')
@section('page-title','Drug Interactions')
@section('content')

@include('pharmacy._toolbar')

<div class="page-header">
    <div class="page-header-left">
        <h1><i class="fa-solid fa-triangle-exclamation" style="color:var(--sc-amber)"></i> Drug Interaction Database</h1>
        <div class="breadcrumb"><a href="{{ route('pharmacy.dashboard') }}">Pharmacy</a> › Drug Interactions</div>
    </div>
    <button type="button" class="btn btn-primary" onclick="document.getElementById('addModal').classList.add('open')">
        <i class="fa-solid fa-plus"></i> Add Interaction
    </button>
</div>

@if(session('success'))
<div class="alert alert-success" style="background:#f0fdf4;border:1px solid #86efac;color:#14532d;padding:12px 18px;border-radius:8px;margin-bottom:16px">
    <i class="fa-solid fa-circle-check"></i> {{ session('success') }}
</div>
@endif

<!-- Stats -->
<div class="grid grid-4 mb-4">
    @php $sev = \App\Models\DrugInteraction::SEVERITIES; @endphp
    @foreach(['contraindicated','major','moderate','minor'] as $s)
    <div class="stat-card">
        <div class="stat-icon" style="background:{{ $sev[$s]['bg'] }};color:{{ $sev[$s]['color'] }}">
            <i class="fa-solid fa-{{ $s === 'contraindicated' ? 'ban' : ($s === 'major' ? 'circle-exclamation' : ($s === 'moderate' ? 'triangle-exclamation' : 'info-circle')) }}"></i>
        </div>
        <div>
            <div class="stat-value" style="color:{{ $sev[$s]['color'] }}">{{ $stats[$s] }}</div>
            <div class="stat-label">{{ $sev[$s]['label'] }}</div>
        </div>
    </div>
    @endforeach
</div>

<!-- Drug Interaction Checker Tool -->
<div class="card mb-4">
    <div class="card-header"><i class="fa-solid fa-flask"></i> Quick Interaction Checker</div>
    <div class="card-body">
        <div style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap">
            <div class="form-group" style="flex:1;min-width:200px;margin:0">
                <label class="form-label">Drug Names (one per line or comma-separated)</label>
                <textarea id="checkDrugsInput" class="form-control" rows="2" placeholder="e.g. warfarin, aspirin, metformin"></textarea>
            </div>
            <button type="button" class="btn btn-primary" onclick="runInteractionCheck()">
                <i class="fa-solid fa-search"></i> Check Interactions
            </button>
        </div>
        <div id="checkResults" style="margin-top:12px"></div>
    </div>
</div>

<!-- Interactions Table -->
<div class="card">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
        <span><i class="fa-solid fa-list"></i> All Interactions ({{ $interactions->total() }})</span>
        <div style="display:flex;gap:8px">
            @foreach(['','contraindicated','major','moderate','minor'] as $f)
            <a href="{{ route('pharmacy.interactions.index', $f ? ['severity'=>$f] : []) }}"
               class="btn btn-sm {{ request('severity')===$f ? 'btn-primary' : 'btn-outline' }}">
                {{ $f ? ucfirst($f) : 'All' }}
            </a>
            @endforeach
        </div>
    </div>
    <div style="overflow-x:auto">
        <table class="table">
            <thead>
                <tr>
                    <th style="width:15%">Drug 1</th>
                    <th style="width:15%">Drug 2</th>
                    <th style="width:10%">Severity</th>
                    <th>Description</th>
                    <th>Management</th>
                    <th style="width:60px"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($interactions as $ix)
                @php $s = \App\Models\DrugInteraction::SEVERITIES[$ix->severity] ?? ['label'=>$ix->severity,'color'=>'#555','bg'=>'#f9f9f9','border'=>'#ccc']; @endphp
                <tr>
                    <td><span class="badge" style="background:#e8f4fd;color:#1e40af;font-weight:600">{{ ucwords($ix->drug1_name) }}</span></td>
                    <td><span class="badge" style="background:#e8f4fd;color:#1e40af;font-weight:600">{{ ucwords($ix->drug2_name) }}</span></td>
                    <td>
                        <span class="badge" style="background:{{ $s['bg'] }};color:{{ $s['color'] }};border:1px solid {{ $s['border'] }};font-weight:700">
                            {{ $s['label'] }}
                        </span>
                    </td>
                    <td style="font-size:13px">{{ $ix->description }}</td>
                    <td style="font-size:12px;color:var(--sc-muted)">{{ $ix->management ?? '—' }}</td>
                    <td>
                        <form method="POST" action="{{ route('pharmacy.interactions.destroy', $ix) }}" onsubmit="return confirm('Remove this interaction?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-danger btn-icon btn-sm" title="Delete"><i class="fa-solid fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-muted" style="padding:32px">No interactions found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($interactions->hasPages())
    <div style="padding:16px">{{ $interactions->links() }}</div>
    @endif
</div>

<!-- Add Interaction Modal -->
<div class="modal-overlay" id="addModal">
    <div class="modal-box" style="max-width:580px">
        <div class="modal-header">
            <h3><i class="fa-solid fa-plus"></i> Add Drug Interaction</h3>
            <button class="modal-close" onclick="document.getElementById('addModal').classList.remove('open')">&times;</button>
        </div>
        <form method="POST" action="{{ route('pharmacy.interactions.store') }}">
            @csrf
            <div class="modal-body">
                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-label">Drug 1 Generic Name <span class="req">*</span></label>
                        <input type="text" name="drug1_name" class="form-control" required placeholder="e.g. warfarin" style="text-transform:lowercase">
                        <div class="form-text">Use lowercase generic name (e.g. "warfarin" not "Warfarin")</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Drug 2 Generic Name <span class="req">*</span></label>
                        <input type="text" name="drug2_name" class="form-control" required placeholder="e.g. aspirin" style="text-transform:lowercase">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Severity <span class="req">*</span></label>
                    <select name="severity" class="form-control" required>
                        @foreach(\App\Models\DrugInteraction::SEVERITIES as $key => $meta)
                        <option value="{{ $key }}">{{ $meta['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Description <span class="req">*</span></label>
                    <textarea name="description" class="form-control" rows="3" required
                              placeholder="What happens when these drugs are combined?"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Management</label>
                    <textarea name="management" class="form-control" rows="2"
                              placeholder="What should be done if combination is necessary?"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('addModal').classList.remove('open')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save Interaction</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function runInteractionCheck() {
    const raw = document.getElementById('checkDrugsInput').value;
    const names = raw.split(/[\n,;]+/).map(s => s.trim().toLowerCase()).filter(Boolean);
    if (names.length < 2) { alert('Enter at least 2 drug names to check.'); return; }

    const res = document.getElementById('checkResults');
    res.innerHTML = '<div class="text-muted" style="font-size:13px">Checking...</div>';

    $.post('{{ route("pharmacy.interactions.check") }}', { names, _token: '{{ csrf_token() }}' }, function(data) {
        if (!data.interactions.length) {
            res.innerHTML = '<div style="color:#065f46;background:#f0fdf4;border:1px solid #86efac;padding:10px 14px;border-radius:6px;font-size:13px"><i class="fa-solid fa-circle-check"></i> No known interactions found between these drugs.</div>';
            return;
        }
        let html = '';
        data.interactions.forEach(i => {
            html += `<div style="background:${i.bg};border:1px solid ${i.border};border-radius:8px;padding:12px 16px;margin-bottom:8px">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px">
                    <span style="background:${i.bg};border:1.5px solid ${i.border};color:${i.color};font-weight:700;font-size:11px;padding:2px 8px;border-radius:4px;text-transform:uppercase">${i.label}</span>
                    <span style="font-weight:700;color:${i.color};font-size:14px">${escHtml(i.matched1)} + ${escHtml(i.matched2)}</span>
                </div>
                <div style="font-size:13px;margin-bottom:4px">${escHtml(i.description)}</div>
                ${i.management ? `<div style="font-size:12px;color:#555"><strong>Management:</strong> ${escHtml(i.management)}</div>` : ''}
            </div>`;
        });
        res.innerHTML = html;
    }).fail(() => { res.innerHTML = '<div class="text-muted">Check failed. Please try again.</div>'; });
}
function escHtml(s) { return String(s||'').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c])); }
</script>
@endpush
@endsection
