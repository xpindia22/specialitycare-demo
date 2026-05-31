<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Patient Forum — {{ $clinic['name'] ?? config('app.name') }}</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
:root{--navy:#0f1f3d;--teal:#0ab4b4;--bg:#f5f8fc;--surface:#fff;--border:#e2e8f0;--text:#1a2942;--muted:#64748b}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--text);min-height:100vh}
.bar{background:var(--navy);color:#fff;padding:14px 20px;display:flex;align-items:center;justify-content:space-between}
.bar .brand{display:flex;align-items:center;gap:10px;font-weight:700}
.bar .brand i{color:var(--teal)}
.bar form{margin:0}
.bar button{background:transparent;border:1px solid rgba(255,255,255,.3);color:#fff;border-radius:7px;padding:7px 12px;font-size:13px;cursor:pointer;font-family:inherit}
.wrap{max-width:640px;margin:30px auto;padding:0 18px}
.card{background:var(--surface);border:1px solid var(--border);border-radius:14px;box-shadow:0 4px 18px rgba(15,31,61,.07);padding:26px;text-align:center}
.btn{display:inline-flex;align-items:center;gap:8px;background:var(--teal);color:#fff;border:0;border-radius:9px;padding:14px 24px;font-weight:700;font-size:15px;text-decoration:none;margin-top:10px}
.btn:hover{background:#089898}
.muted{color:var(--muted);font-size:13px;margin-top:12px;line-height:1.5}
</style>
</head>
<body>
<div class="bar">
    <div class="brand"><i class="fa-solid fa-heart-pulse"></i> {{ $clinic['name'] ?? config('app.name') }}</div>
    <form method="POST" action="{{ route('portal.logout') }}">@csrf<button type="submit"><i class="fa-solid fa-arrow-right-from-bracket"></i> Log out</button></form>
</div>
<div class="wrap">
    <div class="card">
        <h1 style="font-size:22px;color:var(--navy)">Welcome, {{ $patient->first_name }}</h1>
        <p class="muted">You're signed in to the patient forum. Read clinic announcements and ask questions on the board.</p>
        <a class="btn" href="{{ $forumUrl }}"><i class="fa-solid fa-comments"></i> Open the Forum</a>
        <a class="btn" href="{{ route('portal.messages') }}" style="background:var(--navy)"><i class="fa-solid fa-comment-medical"></i> Message the clinic privately</a>
        <p class="muted">Use <strong>Message the clinic</strong> for anything private (it's a direct, confidential chat with the clinic). The public <strong>Forum</strong> is for announcements and general questions — please don't share personal medical details there.</p>
    </div>
</div>
</body>
</html>
