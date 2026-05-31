<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Owner Panel') — SpecialityCare</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=DM+Mono&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root { --navy:#0f1f3d; --navy-mid:#162545; --teal:#0ab4b4; --teal-dark:#089898; --green:#27c47a; --red:#e84c4c; --amber:#f5a623; --purple:#7c3aed; --muted:#64748b; --border:#e2e8f0; --bg:#f7f9fc; }
        * { margin:0;padding:0;box-sizing:border-box; }
        body { font-family:'DM Sans',sans-serif;background:var(--bg);color:#1a2942;min-height:100vh; }
        a { color:var(--teal);text-decoration:none; } a:hover { color:var(--teal-dark); }
        .topbar { background:var(--navy);color:#fff;padding:14px 28px;display:flex;align-items:center;justify-content:space-between;gap:24px; }
        .topbar h1 { font-size:16px;font-weight:700;display:flex;align-items:center;gap:8px; } .topbar h1 i { color:var(--amber); }
        .topbar nav { display:flex;gap:6px;flex:1;margin-left:24px; }
        .topbar nav a { color:#cbd5e1;padding:6px 12px;border-radius:6px;font-size:13px;font-weight:500;display:inline-flex;align-items:center;gap:6px; }
        .topbar nav a:hover { background:rgba(255,255,255,.08);color:#fff; }
        .topbar nav a.active { background:var(--teal);color:#fff; }
        .topbar .right { display:flex;gap:18px;align-items:center;font-size:13px; }
        .topbar .right a, .topbar .right button { color:#cbd5e1;background:none;border:none;cursor:pointer;font:inherit;display:inline-flex;align-items:center;gap:6px; }
        .topbar .right a:hover, .topbar .right button:hover { color:#fff; }
        .wrap { max-width:1200px;margin:24px auto;padding:0 24px;display:flex;flex-direction:column;gap:18px; }
        .card { background:#fff;border:1px solid var(--border);border-radius:10px;overflow:hidden; }
        .card-h { padding:14px 18px;border-bottom:1px solid var(--border);font-weight:600;font-size:14px;color:var(--navy);display:flex;align-items:center;gap:8px; } .card-h i { color:var(--teal); }
        .card-h .right { margin-left:auto;display:flex;gap:8px;align-items:center; }
        .card-b { padding:18px; }
        .stat-row { display:grid;grid-template-columns:repeat(4,1fr);gap:12px; }
        .stat { background:#fff;border:1px solid var(--border);border-radius:10px;padding:14px; }
        .stat .lbl { font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:.5px; }
        .stat .val { font-size:22px;font-weight:700;color:var(--navy);margin-top:4px; }
        .stat .sub { font-size:12px;color:var(--muted);margin-top:2px; }
        .stat.green .val { color:var(--green); } .stat.amber .val { color:var(--amber); } .stat.red .val { color:var(--red); }
        .badge { display:inline-flex;align-items:center;gap:4px;padding:3px 9px;border-radius:999px;font-size:11px;font-weight:600; }
        .badge.green { background:rgba(39,196,122,.15);color:#1b9e5c; }
        .badge.amber { background:rgba(245,166,35,.15);color:#c47e0a; }
        .badge.red   { background:rgba(232,76,76,.15);color:#b52b2b; }
        .badge.gray  { background:#f1f5f9;color:var(--muted); }
        .badge.teal  { background:rgba(10,180,180,.15);color:var(--teal); }
        .badge.navy  { background:rgba(15,31,61,.08);color:var(--navy); }
        .badge.purple{ background:rgba(124,58,237,.12);color:#7c3aed; }
        table { width:100%;border-collapse:collapse;font-size:13px; }
        th { text-align:left;padding:10px 14px;background:#f1f5f9;font-size:11px;font-weight:700;color:var(--navy);text-transform:uppercase;letter-spacing:.5px; }
        td { padding:11px 14px;border-bottom:1px solid var(--border);vertical-align:middle; }
        tr:hover td { background:rgba(10,180,180,.03); }
        .desc { font-size:12px;color:var(--muted);margin-top:2px; }
        input[type=text],input[type=email],input[type=tel],input[type=date],input[type=file],input[type=number],input[type=password],select,textarea { padding:8px 12px;border:1.5px solid var(--border);border-radius:6px;font-family:inherit;font-size:13px;outline:none;width:100%; }
        input:focus,select:focus,textarea:focus { border-color:var(--teal);box-shadow:0 0 0 3px rgba(10,180,180,.15); }
        textarea { min-height:80px;resize:vertical; }
        label { display:block;font-size:12px;font-weight:600;color:var(--navy);margin-bottom:5px; }
        label .req { color:var(--red); }
        .checkbox { display:flex;align-items:center;gap:8px;font-size:13px;padding:8px 12px;border:1px solid var(--border);border-radius:6px;cursor:pointer; }
        .checkbox input { width:auto; }
        .checkbox.checked { background:rgba(10,180,180,.05);border-color:var(--teal); }
        .btn { display:inline-flex;align-items:center;gap:6px;padding:9px 16px;background:var(--teal);color:#fff;border:none;border-radius:6px;font-size:13px;font-weight:600;cursor:pointer;font-family:inherit;text-decoration:none; }
        .btn:hover { background:var(--teal-dark);color:#fff; }
        .btn-navy { background:var(--navy); } .btn-navy:hover { background:var(--navy-mid); }
        .btn-outline { background:transparent;border:1.5px solid var(--border);color:var(--navy); } .btn-outline:hover { border-color:var(--teal);color:var(--teal);background:transparent; }
        .btn-danger { background:var(--red); } .btn-danger:hover { background:#c93b3b; }
        .btn-amber { background:var(--amber);color:var(--navy); } .btn-amber:hover { background:#dd9117;color:var(--navy); }
        .btn-sm { padding:5px 10px;font-size:12px; }
        .alert { padding:12px 16px;border-radius:8px;font-size:13px;margin-bottom:14px;display:flex;gap:10px;align-items:flex-start; }
        .alert-success { background:rgba(39,196,122,.12);color:#15693a;border:1px solid rgba(39,196,122,.3); }
        .alert-warning { background:rgba(245,166,35,.12);color:#7a4f00;border:1px solid rgba(245,166,35,.3); }
        .alert-danger  { background:rgba(232,76,76,.1);color:#922020;border:1px solid rgba(232,76,76,.25); }
        .alert-info    { background:rgba(10,180,180,.1);color:#065e5e;border:1px solid rgba(10,180,180,.25); }
        .grid-2 { display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:12px; }
        .grid-3 { display:grid;grid-template-columns:repeat(3,1fr);gap:12px; }
        .form-row { margin-bottom:12px; }
        .mono { font-family:'DM Mono',monospace;font-size:12px; }
        .text-muted { color:var(--muted); }
        .text-small { font-size:12px; }
        .text-right { text-align:right; }
        .empty { padding:40px;text-align:center;color:var(--muted); } .empty i { font-size:32px;color:#cbd5e1;display:block;margin-bottom:10px; }
        .breadcrumb { font-size:13px;color:var(--muted); } .breadcrumb a { color:var(--muted); } .breadcrumb a:hover { color:var(--teal); }
    </style>
</head>
<body>
<div class="topbar">
    <h1><i class="fa-solid fa-key"></i> Owner Panel</h1>
    <nav>
        <a href="{{ route('owner.modules.index') }}" class="{{ request()->routeIs('owner.modules.*') ? 'active' : '' }}"><i class="fa-solid fa-cubes"></i> Modules</a>
        <a href="{{ route('owner.buyers.index') }}"  class="{{ request()->routeIs('owner.buyers.*')  ? 'active' : '' }}"><i class="fa-solid fa-users-line"></i> Buyers</a>
        <a href="{{ route('owner.licenses.index') }}" class="{{ request()->routeIs('owner.licenses.*') ? 'active' : '' }}"><i class="fa-solid fa-file-signature"></i> Issued Licenses</a>
        <a href="{{ route('owner.heartbeats.index') }}" class="{{ request()->routeIs('owner.heartbeats.*') ? 'active' : '' }}"><i class="fa-solid fa-heart-pulse"></i> Heartbeats</a>
    </nav>
    <div class="right">
        <a href="{{ url('/') }}"><i class="fa-solid fa-arrow-left"></i> Back to app</a>
        <form method="POST" action="{{ route('owner.logout') }}" style="margin:0">@csrf<button type="submit"><i class="fa-solid fa-right-from-bracket"></i> Sign out</button></form>
    </div>
</div>

<div class="wrap">
    @if(session('success'))<div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> {{ session('success') }}</div>@endif
    @if(session('warning'))<div class="alert alert-warning"><i class="fa-solid fa-triangle-exclamation"></i> {{ session('warning') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation"></i><div>@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div></div>@endif

    @yield('content')
</div>
</body>
</html>
