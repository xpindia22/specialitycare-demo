{{-- Consultation fee worked out by the OPD fee rules: $q (App\Services\OpdFee::quote) --}}
@if($q)
@php $c = ['free' => ['var(--sc-green)', 'fa-gift'], 'followup' => ['var(--sc-teal)', 'fa-rotate'], 'new' => ['var(--sc-navy)', 'fa-user-doctor']][$q['kind']]; @endphp
<div style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-start;padding:9px 12px;margin-bottom:12px;border-radius:8px;border:1px solid var(--sc-border);border-left:4px solid {{ $c[0] }};font-size:12.5px">
    <i class="fa-solid {{ $c[1] }}" style="color:{{ $c[0] }};margin-top:2px"></i>
    <div style="flex:1">
        <b>Consultation: {{ $q['kind'] === 'free' ? 'FREE' : '₹' . number_format($q['net']) }}</b> — {{ $q['label'] }}
        @if($q['discount'])<span> · {{ $q['discount']['name'] }} {{ rtrim(rtrim(number_format($q['discount']['percent'], 2), '0'), '.') }}% off ₹{{ number_format($q['base']) }}</span>@endif
        <div class="text-muted" style="font-size:11px">
            Fees: new ₹{{ number_format($q['new_fee']) }}, follow-up ₹{{ number_format($q['followup_fee']) }}{{ $q['rule'] ? ' · rule: '.$q['rule'] : '' }}{{ $q['last_paid'] ? ' · last paid consultation '.\Carbon\Carbon::parse($q['last_paid'])->format('d M Y') : ' · no earlier paid consultation' }}.
            You can still change the price below.
        </div>
    </div>
    @if($q['base'] > 0 && count($q['discounts']))
    <select class="form-control" style="width:auto;height:30px;padding:2px 8px;font-size:12px" title="Discount on the consultation"
            onchange="feeApplyDiscount(this, {{ $q['base'] }})">
        <option value="" data-pct="0" @selected(!$q['discount'])>No discount</option>
        @foreach($q['discounts'] as $d)
        <option value="{{ $d['name'] }}" data-pct="{{ $d['percent'] }}" @selected($q['discount'] && $q['discount']['id'] === $d['id'])>{{ $d['name'] }} {{ rtrim(rtrim(number_format($d['percent'], 2), '0'), '.') }}%{{ $d['auto'] ? '' : ' (pick)' }}</option>
        @endforeach
    </select>
    @endif
</div>
<script>
// Re-price the consultation line on this page (quick bill or new bill) for the chosen discount
function feeApplyDiscount(sel, base) {
    const opt = sel.options[sel.selectedIndex], pct = parseFloat(opt.dataset.pct) || 0;
    const type = document.querySelector('input[name^="items["][name$="[type]"][value="Consultation"]');
    if (!type) return;
    const pfx = type.name.replace('[type]', '');
    const nameEl = document.querySelector(`[name="${pfx}[name]"]`), priceEl = document.querySelector(`[name="${pfx}[price]"]`);
    const price = Math.round(base * (100 - pct)) / 100;
    const name = nameEl.value.replace(/ — [^—]+ off ₹[\d,]+$/, '') + (pct ? ` — ${opt.value} ${pct}% off ₹${Math.round(base).toLocaleString('en-IN')}` : '');
    nameEl.value = name;
    const shown = nameEl.parentElement.querySelector('span');
    if (shown && nameEl.type === 'hidden') shown.textContent = name;
    const i = +(pfx.match(/\[(\d+)\]/) || [])[1];
    if (typeof qbRows !== 'undefined' && qbRows[i]) qbRows[i].name = name;
    priceEl.value = price;
    priceEl.dispatchEvent(new Event('input', { bubbles: true }));
}
</script>
@endif
