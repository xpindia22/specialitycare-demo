{{-- Shared section quick-action toolbar (mirrors the accounts toolbar look).
     Include a per-section wrapper (e.g. opd._toolbar) which passes:
       $links = [ ['url'=>..., 'icon'=>'fa-…', 'label'=>'…', 'bg'=>'#hex', 'active'=>bool], … ]
     Self-contained: pill buttons, white text, hover-darken, current page ringed.
     Hidden when printing so it never appears on register / census / bill sheets. --}}
@once
<style>
    .mod-toolbar { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:14px; }
    .mod-toolbar .mod-btn { font-weight:600; color:#fff !important; border:1px solid transparent; border-radius:8px; transition:transform .12s, box-shadow .12s, filter .15s; }
    .mod-toolbar .mod-btn:hover { transform:translateY(-1px); box-shadow:0 4px 12px rgba(0,0,0,.12); filter:brightness(0.9); color:#fff !important; }
    .mod-toolbar .mod-btn i { color:#fff !important; }
    .mod-toolbar .mod-btn.is-active { box-shadow:0 0 0 2px rgba(0,0,0,.28); filter:brightness(1.05); }
    /* inline = sit in the page-header row, right of the title (saves a row) */
    .mod-toolbar.mod-toolbar-inline { margin-bottom:0; justify-content:flex-end; }
    @media print { .mod-toolbar { display:none !important; } }
</style>
@endonce
<div class="mod-toolbar{{ ($inline ?? false) ? ' mod-toolbar-inline' : '' }}">
    @foreach($links as $l)
        <a href="{{ $l['url'] ?? '#' }}"@if(!empty($l['onclick'])) onclick="{{ $l['onclick'] }}"@endif class="btn mod-btn{{ ($l['active'] ?? false) ? ' is-active' : '' }}" style="background:{{ $l['bg'] }}"><i class="fa-solid {{ $l['icon'] }}"></i> {{ $l['label'] }}</a>
    @endforeach
</div>
