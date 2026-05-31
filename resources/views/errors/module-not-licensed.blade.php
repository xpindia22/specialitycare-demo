@extends('layouts.app')
@section('title', $module['label'].' — Not Licensed')
@section('page-title', 'Module Not Licensed')
@section('content')

<div style="max-width:640px;margin:40px auto">
    <div class="card" style="text-align:center;padding:48px 32px">
        <div style="display:inline-flex;align-items:center;justify-content:center;width:88px;height:88px;background:rgba(245,166,35,.12);color:var(--sc-amber);border-radius:50%;margin-bottom:20px">
            <i class="fa-solid {{ $module['icon'] ?? 'fa-cube' }}" style="font-size:38px"></i>
        </div>
        <h1 style="font-size:22px;font-weight:700;color:var(--sc-navy);margin-bottom:8px">
            {{ $module['label'] }} is not included in your license
        </h1>
        <p class="text-muted" style="font-size:14px;margin-bottom:18px">
            @if(!empty($module['description'])){{ $module['description'] }}@endif
        </p>

        <div class="alert alert-info" style="text-align:left">
            <i class="fa-solid fa-circle-info"></i>
            <div>
                Your current license covers
                @php $enabled = app(\App\Services\LicenseService::class)->enabledModules(); @endphp
                @if(empty($enabled))
                    <strong>no modules</strong>.
                @else
                    @foreach($enabled as $i => $k)
                        <strong>{{ config('modules.catalog.'.$k.'.label', $k) }}</strong>{{ $i < count($enabled) - 1 ? ',' : '.' }}
                    @endforeach
                @endif
                Contact your software vendor to add the <strong>{{ $module['label'] }}</strong> module to your license.
            </div>
        </div>

        <div style="margin-top:24px;display:flex;gap:10px;justify-content:center;flex-wrap:wrap">
            <a href="{{ url('/') }}" class="btn btn-primary"><i class="fa-solid fa-house"></i> Back to Dashboard</a>
            <a href="{{ route('admin.modules.index') }}" class="btn btn-outline"><i class="fa-solid fa-cubes"></i> View My Modules</a>
        </div>

        <div class="text-small text-muted" style="margin-top:24px;padding-top:16px;border-top:1px solid var(--sc-border)">
            Module key: <span class="mono">{{ $moduleKey }}</span>
        </div>
    </div>
</div>

@endsection
