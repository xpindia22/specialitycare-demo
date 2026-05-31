<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Owner Panel — SpecialityCare</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { margin:0;padding:0;box-sizing:border-box; }
        body { font-family:'DM Sans',sans-serif;background:#0f1f3d;min-height:100vh;display:flex;align-items:center;justify-content:center; }
        .box { background:#fff;border-radius:16px;padding:40px;width:100%;max-width:420px;box-shadow:0 20px 60px rgba(0,0,0,.5); }
        .logo { text-align:center;margin-bottom:24px; }
        .logo-icon { width:60px;height:60px;background:#0f1f3d;border-radius:14px;display:inline-flex;align-items:center;justify-content:center;font-size:24px;color:#f5a623;margin-bottom:10px; }
        .logo h1 { font-size:18px;font-weight:700;color:#0f1f3d; }
        .logo p { font-size:12px;color:#64748b;margin-top:4px;letter-spacing:.5px;text-transform:uppercase; }
        label { display:block;font-size:12.5px;font-weight:600;color:#1a2942;margin-bottom:6px; }
        input { width:100%;padding:10px 14px;border:1.5px solid #e2e8f0;border-radius:8px;font-family:inherit;font-size:13.5px;outline:none; }
        input:focus { border-color:#0ab4b4;box-shadow:0 0 0 3px rgba(10,180,180,.15); }
        button { width:100%;padding:12px;background:#0f1f3d;color:#fff;border:none;border-radius:8px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit;margin-top:8px; }
        button:hover { background:#162545; }
        .err { background:#fee2e2;color:#991b1b;padding:10px 14px;border-radius:8px;font-size:12.5px;margin-bottom:14px;border:1px solid #fecaca; }
        .footer { text-align:center;margin-top:18px;font-size:11px;color:#94a3b8; }
    </style>
</head>
<body>
<div class="box">
    <div class="logo">
        <div class="logo-icon"><i class="fa-solid fa-key"></i></div>
        <h1>Owner Panel</h1>
        <p>Module licensing &amp; control</p>
    </div>
    @if($errors->any())
    <div class="err"><i class="fa-solid fa-circle-exclamation"></i> {{ $errors->first() }}</div>
    @endif
    <form method="POST" action="{{ route('owner.login.post') }}">
        @csrf
        <label for="owner_key">Owner key</label>
        <input type="password" id="owner_key" name="owner_key" autofocus autocomplete="off" required>
        <button type="submit"><i class="fa-solid fa-arrow-right-to-bracket"></i> Enter</button>
    </form>
    <div class="footer">Restricted access. All actions are logged.</div>
</div>
</body>
</html>
