<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Message the clinic — {{ $clinic['name'] ?? config('app.name') }}</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
:root{--navy:#0f1f3d;--teal:#0ab4b4;--bg:#f5f8fc;--surface:#fff;--border:#e2e8f0;--text:#1a2942;--muted:#64748b}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--text);min-height:100vh}
.bar{background:var(--navy);color:#fff;padding:14px 20px;display:flex;align-items:center;justify-content:space-between}
.bar .brand{display:flex;align-items:center;gap:10px;font-weight:700}
.bar .brand i{color:var(--teal)}
.bar a{color:#fff;text-decoration:none;border:1px solid rgba(255,255,255,.3);border-radius:7px;padding:7px 12px;font-size:13px}
.wrap{max-width:640px;margin:24px auto;padding:0 18px}
.card{background:var(--surface);border:1px solid var(--border);border-radius:14px;box-shadow:0 4px 18px rgba(15,31,61,.07);overflow:hidden}
.head{padding:16px 20px;border-bottom:1px solid var(--border)}
.head h1{font-size:18px;color:var(--navy)}
.head p{color:var(--muted);font-size:12.5px;margin-top:3px}
.list{height:52vh;min-height:280px;overflow-y:auto;padding:16px;background:#efeae2}
.row{display:flex;margin-bottom:8px}
.row.patient{justify-content:flex-end}
.bubble{max-width:78%;padding:8px 12px;border-radius:10px;font-size:14px;line-height:1.45;box-shadow:0 1px 1px rgba(0,0,0,.08)}
.row.clinic .bubble{background:#fff;border-top-left-radius:0}
.row.patient .bubble{background:#d2f8c6;border-top-right-radius:0}
.who{font-size:11px;font-weight:700;color:var(--teal)}
.meta{display:block;font-size:10.5px;color:rgba(0,0,0,.45);margin-top:3px;text-align:right}
.compose{display:flex;gap:8px;padding:12px;background:#f0f2f5;border-top:1px solid var(--border)}
.compose textarea{flex:1;border:1px solid var(--border);border-radius:9px;padding:10px 12px;font-size:14px;resize:none;font-family:inherit}
.compose button{background:var(--teal);color:#fff;border:0;border-radius:9px;padding:0 18px;font-size:16px;cursor:pointer}
.empty{text-align:center;color:var(--muted);margin-top:50px;font-size:13px}
</style>
</head>
<body>
<div class="bar">
    <div class="brand"><i class="fa-solid fa-heart-pulse"></i> {{ $clinic['name'] ?? config('app.name') }}</div>
    <a href="{{ route('portal.home') }}"><i class="fa-solid fa-arrow-left"></i> Back</a>
</div>
<div class="wrap">
    <div class="card">
        <div class="head">
            <h1><i class="fa-solid fa-comment-medical" style="color:var(--teal)"></i> Message the clinic</h1>
            <p>A private, direct conversation with {{ $clinic['name'] ?? 'the clinic' }}. We'll reply here and email you.</p>
        </div>
        <div class="list" id="pm-list">
            @forelse($messages as $m)
                <div class="row {{ $m->sender === 'patient' ? 'patient' : 'clinic' }}">
                    <div class="bubble">
                        @if($m->sender === 'staff')<div class="who">{{ $m->user?->name ?? 'Clinic' }}</div>@endif
                        {!! nl2br(e($m->body)) !!}
                        <span class="meta">{{ $m->created_at->format('d M, H:i') }}</span>
                    </div>
                </div>
            @empty
                <p class="empty">No messages yet. Send your first message to the clinic below.</p>
            @endforelse
            <span id="pm-bottom"></span>
        </div>
        <form class="compose" method="POST" action="{{ route('portal.messages.send') }}">
            @csrf
            <textarea name="body" rows="2" maxlength="10000" required placeholder="Type your message…"></textarea>
            <button type="submit" title="Send"><i class="fa-solid fa-paper-plane"></i></button>
        </form>
    </div>
    <p style="text-align:center;color:var(--muted);font-size:12px;margin-top:14px">For emergencies, please call the clinic directly{{ !empty($clinic['phone']) ? ' on '.$clinic['phone'] : '' }}.</p>
</div>
<script>(function(){var el=document.getElementById('pm-list');if(el)el.scrollTop=el.scrollHeight;})();</script>
</body>
</html>
