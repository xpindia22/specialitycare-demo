<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Patient Forum Login — {{ $clinic['name'] ?? config('app.name') }}</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
:root{--navy:#0f1f3d;--teal:#0ab4b4;--bg:#f5f8fc;--surface:#fff;--border:#e2e8f0;--text:#1a2942;--muted:#64748b;--red:#e84c4c}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:18px}
.card{background:var(--surface);border:1px solid var(--border);border-radius:14px;box-shadow:0 8px 32px rgba(15,31,61,.1);width:100%;max-width:400px;padding:30px}
.brand{display:flex;align-items:center;gap:10px;font-weight:700;font-size:18px;color:var(--navy);margin-bottom:4px}
.brand i{color:var(--teal);font-size:22px}
.sub{color:var(--muted);font-size:13px;margin-bottom:22px}
label{display:block;font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.3px;margin-bottom:5px}
.fc{width:100%;padding:11px 12px;border:1px solid var(--border);border-radius:8px;font-size:15px;font-family:inherit;margin-bottom:14px}
.fc:focus{outline:none;border-color:var(--teal);box-shadow:0 0 0 3px rgba(10,180,180,.15)}
.btn{width:100%;background:var(--teal);color:#fff;border:0;border-radius:8px;padding:13px;font-weight:700;font-size:15px;cursor:pointer;font-family:inherit}
.btn:hover{background:#089898}
.err{background:rgba(232,76,76,.1);color:#922020;border:1px solid rgba(232,76,76,.25);border-radius:8px;padding:10px 12px;font-size:13px;margin-bottom:16px}
.note{font-size:12px;color:var(--muted);margin-top:16px;text-align:center;line-height:1.5}
</style>
</head>
<body>
<div class="card">
    <div class="brand"><i class="fa-solid fa-heart-pulse"></i> {{ $clinic['name'] ?? config('app.name') }}</div>
    <div class="sub">Patient Forum — sign in</div>

    @if($errors->any())
        <div class="err">{{ $errors->first() }}</div>
    @endif
    @if(session('success'))
        <div class="err" style="background:rgba(39,196,122,.12);color:#15693a;border-color:rgba(39,196,122,.3)">{{ session('success') }}</div>
    @endif

    <form method="POST" action="{{ route('portal.login.post') }}">
        @csrf
        <label>Mobile number</label>
        <input class="fc" type="tel" name="phone" value="{{ old('phone') }}" required autofocus placeholder="Your registered mobile">
        <label>PIN</label>
        <input class="fc" type="password" name="pin" required inputmode="numeric" placeholder="PIN from the clinic">
        <button class="btn" type="submit">Sign in</button>
    </form>

    <div class="note">
        Don't have a PIN? Ask the clinic reception to enable forum access for you.
        @if(!empty($clinic['phone']))<br>Call: {{ $clinic['phone'] }}@endif
    </div>
</div>
</body>
</html>
