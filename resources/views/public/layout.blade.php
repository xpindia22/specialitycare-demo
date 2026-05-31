<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="description" content="@yield('meta_description', 'Book an appointment online with ' . ($clinic['name'] ?? config('app.name')) . '.')">
<meta name="robots" content="index,follow">
<title>@yield('title', 'Book an Appointment') — {{ $clinic['name'] ?? config('app.name') }}</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
:root {
    --c-navy:#0f1f3d; --c-teal:#0ab4b4; --c-teal-dark:#089898;
    --c-gold:#f5a623; --c-red:#e84c4c; --c-green:#27c47a;
    --c-bg:#f5f8fc; --c-surface:#fff; --c-border:#e2e8f0;
    --c-text:#1a2942; --c-muted:#64748b; --radius:12px;
    --shadow:0 2px 12px rgba(15,31,61,0.06); --shadow-md:0 8px 32px rgba(15,31,61,0.1);
}
*,*::before,*::after { box-sizing:border-box; margin:0; padding:0; }
html { font-size:15px; }
body { font-family:'DM Sans',sans-serif; background:var(--c-bg); color:var(--c-text); line-height:1.55; }

.pub-header { background:var(--c-navy); color:#fff; padding:14px 0; box-shadow:var(--shadow); }
.pub-header .pub-container { display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; }
.pub-header .brand { display:flex; align-items:center; gap:10px; font-weight:700; font-size:18px; }
.pub-header .brand i { color:var(--c-teal); font-size:22px; }
.pub-header .pub-contact { font-size:13px; color:rgba(255,255,255,0.85); display:flex; gap:14px; flex-wrap:wrap; }
.pub-header .pub-contact a { color:#fff; text-decoration:none; }
.pub-header .pub-contact i { color:var(--c-teal); margin-right:5px; }

.pub-container { max-width:1100px; margin:0 auto; padding:0 18px; }
.pub-main { padding:28px 0 60px; }

.pub-footer { background:#0a172c; color:rgba(255,255,255,0.7); padding:24px 0; font-size:12px; text-align:center; margin-top:40px; }
.pub-footer a { color:var(--c-teal); text-decoration:none; }

.btn { display:inline-flex; align-items:center; gap:6px; border:1px solid transparent; border-radius:8px; padding:10px 18px; font-weight:600; font-size:14px; cursor:pointer; transition:all .15s; font-family:inherit; text-decoration:none; }
.btn-primary { background:var(--c-teal); color:#fff; }
.btn-primary:hover { background:var(--c-teal-dark); }
.btn-lg { padding:14px 26px; font-size:15px; }

.form-control { display:block; width:100%; padding:10px 12px; border:1px solid var(--c-border); border-radius:8px; font-size:14px; font-family:inherit; background:#fff; transition:border-color .15s, box-shadow .15s; }
.form-control:focus { outline:none; border-color:var(--c-teal); box-shadow:0 0 0 3px rgba(10,180,180,0.15); }
.form-label { display:block; font-size:12px; font-weight:600; color:var(--c-text); margin-bottom:5px; text-transform:uppercase; letter-spacing:0.3px; }
.form-group { margin-bottom:14px; }
.form-error { color:var(--c-red); font-size:12px; margin-top:4px; }

.card { background:var(--c-surface); border:1px solid var(--c-border); border-radius:var(--radius); box-shadow:var(--shadow); }
.card-body { padding:22px; }

.honeypot { position:absolute; left:-9999px; width:1px; height:1px; overflow:hidden; }

@media (max-width: 640px) {
    .pub-header .pub-contact { display:none; }
}
</style>
@stack('head')

{{-- Google Ads / gtag.js (AW-18185291868) --}}
<script async src="https://www.googletagmanager.com/gtag/js?id=AW-18185291868"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', 'AW-18185291868');
</script>
</head>
<body>

<header class="pub-header">
    <div class="pub-container">
        <div class="brand">
            <i class="fa-solid fa-heart-pulse"></i>
            <span>{{ $clinic['name'] ?? config('app.name') }}</span>
        </div>
        <div class="pub-contact">
            @if(!empty($clinic['phone']))
            <span><i class="fa-solid fa-phone"></i><a href="tel:{{ $clinic['phone'] }}">{{ $clinic['phone'] }}</a></span>
            @endif
            @if(!empty($clinic['address']))
            <span><i class="fa-solid fa-location-dot"></i>{{ $clinic['address'] }}</span>
            @endif
        </div>
    </div>
</header>

<main class="pub-main">
    <div class="pub-container">
        @yield('content')
    </div>
</main>

<footer class="pub-footer">
    <div class="pub-container">
        © {{ date('Y') }} {{ $clinic['name'] ?? config('app.name') }}.
        @if(!empty($clinic['phone']))
        Call us: <a href="tel:{{ $clinic['phone'] }}">{{ $clinic['phone'] }}</a>
        @endif
    </div>
</footer>

@stack('scripts')
</body>
</html>
