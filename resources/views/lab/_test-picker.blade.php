{{-- FILE: resources/views/lab/_test-picker.blade.php
     Reusable test/panel selection picker. Expects: $labCategories, $panels,
     $pinnedTests, $pinnedPanels, $frequentTests. Renders hidden checkboxes
     name="tests[]" / "panels[]" — wrap this in a <form> on the host page. --}}
@php
    $pinnedTestIds = $pinnedTests->pluck('id')->all();
    $pinnedPanelIds = $pinnedPanels->pluck('id')->all();
    $frequentPanels = $frequentPanels ?? collect();
@endphp

<div class="form-group">
    <label class="form-label">Search Tests / Panels</label>
    <input type="text" id="lab-item-search" class="form-control" placeholder="Type test name, panel name, code..." oninput="filterLabOrderItems()">
</div>

{{-- Pinned + Frequently-used quick rows (clicking toggles the real checkbox below) --}}
@if($pinnedTests->count() || $pinnedPanels->count() || $frequentTests->count())
<div id="lab-quick-rows" style="margin-bottom:16px">
    @if($pinnedTests->count() || $pinnedPanels->count())
    <div style="margin-bottom:10px">
        <div style="font-size:11px;font-weight:700;color:#f5a623;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px"><i class="fa-solid fa-star"></i> Pinned</div>
        <div style="display:flex;flex-wrap:wrap;gap:6px">
            @foreach($pinnedTests as $t)
            <button type="button" class="lab-quick-chip" data-type="test" data-id="{{ $t->id }}" onclick="quickToggle('test',{{ $t->id }})">{{ $t->name }} <span class="text-muted">₹{{ number_format($t->price) }}</span></button>
            @endforeach
            @foreach($pinnedPanels as $p)
            <button type="button" class="lab-quick-chip" data-type="panel" data-id="{{ $p->id }}" onclick="quickToggle('panel',{{ $p->id }})"><i class="fa-solid fa-layer-group" style="font-size:10px"></i> {{ $p->name }} <span class="text-muted">₹{{ number_format($p->price) }}</span></button>
            @endforeach
        </div>
    </div>
    @endif
    @if($frequentTests->count() || $frequentPanels->count())
    <div>
        <div style="font-size:11px;font-weight:700;color:var(--sc-teal);text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px"><i class="fa-solid fa-fire"></i> Frequently used</div>
        <div style="display:flex;flex-wrap:wrap;gap:6px">
            @foreach($frequentPanels as $p)
                @continue(in_array($p->id, $pinnedPanelIds))
                <button type="button" class="lab-quick-chip" data-type="panel" data-id="{{ $p->id }}" onclick="quickToggle('panel',{{ $p->id }})"><i class="fa-solid fa-layer-group" style="font-size:10px"></i> {{ $p->name }} <span class="text-muted">₹{{ number_format($p->price) }}</span></button>
            @endforeach
            @foreach($frequentTests as $t)
                @continue(in_array($t->id, $pinnedTestIds))
                <button type="button" class="lab-quick-chip" data-type="test" data-id="{{ $t->id }}" onclick="quickToggle('test',{{ $t->id }})">{{ $t->name }} <span class="text-muted">₹{{ number_format($t->price) }}</span></button>
            @endforeach
        </div>
    </div>
    @endif
</div>
@endif

<div style="display:flex;gap:8px;margin-bottom:12px">
    <button type="button" class="btn btn-sm btn-outline active" id="lab-tabbtn-tests" onclick="showTab('tests')">Individual Tests</button>
    <button type="button" class="btn btn-sm btn-outline" id="lab-tabbtn-panels" onclick="showTab('panels')">Panels</button>
</div>

{{-- Tests Tab --}}
<div id="tab-tests" class="lab-tab">
    @foreach($labCategories as $cat)
    <div style="margin-bottom:16px" class="lab-category-group">
        <div style="font-size:12px;font-weight:700;color:var(--sc-muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;padding:6px 0;border-bottom:1px solid var(--sc-border)">{{ $cat->name }}</div>
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px">
            @foreach($cat->tests as $test)
            <label style="display:flex;align-items:center;gap:8px;padding:8px 10px;background:var(--sc-bg);border-radius:7px;border:1.5px solid var(--sc-border);cursor:pointer;transition:all .15s" class="test-label" data-id="{{ $test->id }}" data-name="{{ $test->name }}" data-price="{{ $test->price }}" data-type="test" data-search="{{ strtolower($test->name.' '.$test->short_code.' '.$cat->name) }}">
                <input type="checkbox" name="tests[]" value="{{ $test->id }}" style="display:none" onchange="updateSelected(this.closest('.test-label'))">
                <div style="flex:1">
                    <div style="font-size:12.5px;font-weight:600">{{ $test->name }}</div>
                    @if($test->short_code)<div style="font-size:10px;color:var(--sc-muted)">{{ $test->short_code }}</div>@endif
                </div>
                <div style="font-size:12px;font-weight:700;color:var(--sc-teal);white-space:nowrap">₹{{ number_format($test->price) }}</div>
                <button type="button" class="lab-pin-btn" title="Pin to top" onclick="togglePinUI(event,'test',{{ $test->id }},this)">
                    <i class="fa-solid fa-star" style="color:{{ $test->is_pinned ? '#f5a623' : '#cbd5e1' }}"></i>
                </button>
            </label>
            @endforeach
        </div>
    </div>
    @endforeach
</div>

{{-- Panels Tab --}}
<div id="tab-panels" class="lab-tab" style="display:none">
    <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:12px">
        @foreach($panels as $panel)
        <label style="display:flex;gap:12px;padding:14px;background:var(--sc-bg);border-radius:8px;border:1.5px solid var(--sc-border);cursor:pointer;transition:all .15s" class="test-label panel-label" data-id="{{ $panel->id }}" data-name="{{ $panel->name }}" data-price="{{ $panel->price }}" data-type="panel" data-search="{{ strtolower($panel->name.' '.$panel->short_code.' '.$panel->description.' '.$panel->tests->pluck('name')->join(' ')) }}">
            <input type="checkbox" name="panels[]" value="{{ $panel->id }}" style="display:none" onchange="updateSelected(this.closest('.test-label'))">
            <div style="flex:1">
                <div style="font-size:14px;font-weight:700">{{ $panel->name }}</div>
                <div style="font-size:11.5px;color:var(--sc-muted);margin-top:3px">{{ $panel->description }}</div>
                <div style="font-size:11px;color:var(--sc-muted);margin-top:6px">{{ $panel->tests->pluck('name')->join(' · ') }}</div>
            </div>
            <div style="text-align:right">
                <div style="font-size:16px;font-weight:700;color:var(--sc-teal)">₹{{ number_format($panel->price) }}</div>
                <div style="font-size:10px;color:var(--sc-muted)">{{ $panel->tests->count() }} tests</div>
                <button type="button" class="lab-pin-btn" title="Pin to top" onclick="togglePinUI(event,'panel',{{ $panel->id }},this)">
                    <i class="fa-solid fa-star" style="color:{{ $panel->is_pinned ? '#f5a623' : '#cbd5e1' }}"></i>
                </button>
            </div>
        </label>
        @endforeach
    </div>
</div>

@once
@push('styles')
<style>
.lab-quick-chip { background:#fff;border:1.5px solid var(--sc-border);border-radius:999px;padding:5px 12px;font-size:12px;cursor:pointer;transition:all .15s;white-space:nowrap }
.lab-quick-chip:hover { border-color:var(--sc-teal) }
.lab-quick-chip.active { border-color:var(--sc-teal);background:rgba(10,180,180,.08);font-weight:600 }
.lab-pin-btn { background:none;border:none;cursor:pointer;padding:2px 4px;font-size:13px;line-height:1 }
.lab-pin-btn:hover i { color:#f5a623 !important }
</style>
@endpush
@push('scripts')
<script>
window.selectedItems = window.selectedItems || {};

function showTab(tab) {
    document.querySelectorAll('.lab-tab').forEach(t => t.style.display = 'none');
    const el = document.getElementById('tab-' + tab);
    if (el) el.style.display = 'block';
    document.getElementById('lab-tabbtn-tests')?.classList.toggle('active', tab === 'tests');
    document.getElementById('lab-tabbtn-panels')?.classList.toggle('active', tab === 'panels');
}

function updateSelected(label) {
    if (!label) return;
    const { id, name, type } = label.dataset;
    const price = parseFloat(label.dataset.price) || 0;
    const cb = label.querySelector('input[type=checkbox]');
    const key = type + '-' + id;
    if (cb.checked) {
        window.selectedItems[key] = { id, name, price, type };
        label.style.borderColor = 'var(--sc-teal)';
        label.style.background = 'rgba(10,180,180,0.06)';
    } else {
        delete window.selectedItems[key];
        label.style.borderColor = 'var(--sc-border)';
        label.style.background = 'var(--sc-bg)';
    }
    // sync any matching quick chip
    document.querySelector(`.lab-quick-chip[data-type="${type}"][data-id="${id}"]`)?.classList.toggle('active', cb.checked);
    renderSelected();
    updateTotal();
    const counter = document.getElementById('lab-selected-count');
    if (counter) counter.textContent = Object.keys(window.selectedItems).length;
}

// Quick chip click -> toggle the real checkbox (single source of truth)
function quickToggle(type, id) {
    const label = document.querySelector(`.test-label[data-type="${type}"][data-id="${id}"]`);
    if (!label) return;
    const cb = label.querySelector('input[type=checkbox]');
    cb.checked = !cb.checked;
    updateSelected(label);
}

function renderSelected() {
    const c = document.getElementById('selected-tests');
    if (!c) return; // host page may not have a live cart
    const items = Object.values(window.selectedItems);
    if (!items.length) {
        c.innerHTML = '<div id="no-selection" class="text-center text-muted text-small" style="padding:16px">No tests selected</div>';
        return;
    }
    c.innerHTML = items.map(item => `
        <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid var(--sc-border);font-size:12.5px">
            <span>${item.name}</span>
            <span style="font-weight:600;color:var(--sc-navy)">₹${item.price.toFixed(2)}</span>
        </div>`).join('');
}

function updateTotal() {
    const subEl = document.getElementById('lab-subtotal');
    if (!subEl) return; // no cart on this page
    const sub = Object.values(window.selectedItems).reduce((s, i) => s + i.price, 0);
    const disc = parseFloat(document.getElementById('lab-disc')?.value || 0);
    subEl.textContent = '₹' + sub.toFixed(2);
    const totalEl = document.getElementById('lab-total');
    if (totalEl) totalEl.textContent = '₹' + Math.max(0, sub - disc).toFixed(2);
}

function filterLabOrderItems() {
    const q = (document.getElementById('lab-item-search').value || '').toLowerCase().trim();
    document.querySelectorAll('.test-label').forEach(label => {
        const checked = label.querySelector('input[type=checkbox]')?.checked;
        label.style.display = !q || checked || (label.dataset.search || '').includes(q) ? '' : 'none';
    });
    document.querySelectorAll('.lab-category-group').forEach(group => {
        const visible = Array.from(group.querySelectorAll('.test-label')).some(l => l.style.display !== 'none');
        group.style.display = visible ? '' : 'none';
    });
}

function togglePinUI(e, type, id, btn) {
    e.preventDefault(); e.stopPropagation();
    const icon = btn.querySelector('i');
    const token = document.querySelector('meta[name=csrf-token]')?.content
        || document.querySelector('input[name=_token]')?.value;
    fetch(`{{ url('/lab/pin') }}/${type}/${id}`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
    }).then(r => r.json()).then(res => {
        if (res.success) icon.style.color = res.pinned ? '#f5a623' : '#cbd5e1';
    }).catch(() => {});
}
</script>
@endpush
@endonce
