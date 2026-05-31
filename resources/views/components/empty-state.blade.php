@props([
    'icon' => 'fa-folder-open',
    'title' => 'Nothing here yet',
    'message' => '',
    'action' => null,
    'actionUrl' => null,
    'actionIcon' => 'fa-plus',
    'compact' => false,
])

<div style="text-align:center;padding:{{ $compact ? '24px 18px' : '48px 24px' }};color:var(--sc-muted)">
    <div style="display:inline-flex;align-items:center;justify-content:center;width:{{ $compact ? '52px' : '72px' }};height:{{ $compact ? '52px' : '72px' }};border-radius:50%;background:var(--sc-bg);border:1px dashed var(--sc-border);margin-bottom:{{ $compact ? '12px' : '18px' }}">
        <i class="fa-solid {{ $icon }}" style="font-size:{{ $compact ? '20px' : '26px' }};color:var(--sc-teal);opacity:.7"></i>
    </div>
    <div style="font-size:{{ $compact ? '13.5px' : '15px' }};font-weight:600;color:var(--sc-text);margin-bottom:6px">{{ $title }}</div>
    @if($message)
        <div style="font-size:12.5px;line-height:1.5;max-width:340px;margin:0 auto 14px">{{ $message }}</div>
    @endif
    @if($action && $actionUrl)
        <a href="{{ $actionUrl }}" class="btn btn-primary btn-sm" style="margin-top:6px">
            <i class="fa-solid {{ $actionIcon }}"></i> {{ $action }}
        </a>
    @endif
    {{ $slot }}
</div>
