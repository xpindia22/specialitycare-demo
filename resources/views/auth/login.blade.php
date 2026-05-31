{{-- FILE: resources/views/auth/login.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Demo Clinic Pvt. Ltd.</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin:0;padding:0;box-sizing:border-box; }
        body { font-family:'DM Sans',sans-serif;background:linear-gradient(135deg,#0f1f3d 0%,#162545 50%,#0ab4b430 100%);min-height:100vh;display:flex;align-items:center;justify-content:center; }
        .login-box { background:#fff;border-radius:16px;padding:40px;width:100%;max-width:420px;box-shadow:0 20px 60px rgba(15,31,61,.4); }
        .login-logo { text-align:center;margin-bottom:32px; }
        .logo-icon { width:64px;height:64px;background:linear-gradient(135deg,#0ab4b4,#0891b2);border-radius:16px;display:inline-flex;align-items:center;justify-content:center;font-size:28px;margin-bottom:12px; }
        .login-logo h1 { font-size:22px;font-weight:700;color:#0f1f3d;margin-bottom:4px; }
        .login-logo p { font-size:13px;color:#64748b; }
        .form-group { margin-bottom:16px; }
        .form-label { display:block;font-size:12.5px;font-weight:600;color:#1a2942;margin-bottom:6px; }
        .form-control { width:100%;padding:10px 14px;border:1.5px solid #e2e8f0;border-radius:8px;font-family:inherit;font-size:13.5px;outline:none;transition:border .15s; }
        .form-control:focus { border-color:#0ab4b4;box-shadow:0 0 0 3px rgba(10,180,180,.15); }
        .form-control.is-invalid { border-color:#e84c4c; }
        .invalid-feedback { font-size:11.5px;color:#e84c4c;margin-top:4px; }
        .btn-login { width:100%;padding:12px;background:linear-gradient(135deg,#0ab4b4,#089898);color:#fff;border:none;border-radius:8px;font-size:15px;font-weight:700;cursor:pointer;font-family:inherit;transition:all .15s; }
        .btn-login:hover { background:linear-gradient(135deg,#089898,#076e6e); }
        .remember-row { display:flex;align-items:center;gap:8px;margin-bottom:16px; }
        .remember-row label { font-size:13px;color:#64748b;cursor:pointer; }
        .alert-danger { background:#fee2e2;color:#991b1b;padding:10px 14px;border-radius:8px;font-size:12.5px;margin-bottom:16px;border:1px solid #fecaca; }
        .patient-cta { margin-top:18px;padding:14px 16px;border:1.5px dashed #0ab4b4;border-radius:10px;background:#f0fdfa;text-align:center; }
        .patient-cta-label { font-size:12px;color:#64748b;margin-bottom:6px; }
        .patient-cta a { display:inline-block;color:#0ab4b4;font-weight:700;font-size:14px;text-decoration:none;padding:6px 14px;border-radius:6px;transition:background .15s; }
        .patient-cta a:hover { background:#ccfbf1; }
        .divider { display:flex;align-items:center;gap:10px;margin:18px 0 0;color:#94a3b8;font-size:11px;text-transform:uppercase;letter-spacing:1px; }
        .divider::before, .divider::after { content:'';flex:1;height:1px;background:#e2e8f0; }
        .contact-footer { text-align:center;margin-top:22px;padding-top:14px;border-top:1px solid #e2e8f0;font-size:12px;color:#475569;line-height:1.55; }
        .contact-footer strong { color:#0f1f3d;font-weight:600; }
        .demo-banner { background:#eef6ff;border:1px solid #b6d4fe;color:#084298;border-radius:8px;padding:12px 14px;margin-bottom:16px;font-size:13px;line-height:1.6; }
        .demo-banner code { background:#fff;padding:1px 6px;border-radius:4px;font-size:12.5px;color:#0f1f3d; }
        .demo-banner .demo-cred { margin-top:2px; }
        .demo-banner .demo-note { margin-top:6px;font-size:11px;color:#3b6ea5; }
    </style>
</head>
<body>
<div class="login-box">
    <div class="login-logo">
        <div class="logo-icon">♥</div>
        <h1>Demo Clinic Pvt. Ltd.</h1>
        <p>We Care · God Heals</p>
    </div>

    <div class="demo-banner">
        <strong>🔑 Demo access</strong>
        <div class="demo-cred">Email: <code>demo@specialitycare.co.in</code></div>
        <div class="demo-cred">Password: <code>Demo@1234</code></div>
        <div class="demo-note">Demonstration build — sample data only · license valid until 2026-12-01</div>
    </div>

    @if(session('error'))
    <div class="alert-danger">{{ session('error') }}</div>
    @endif
    @if($errors->any())
    <div class="alert-danger">
        @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
    </div>
    @endif

    <form method="POST" action="{{ route('login') }}">
    @csrf
    <div class="form-group">
        <label class="form-label">Email Address</label>
        <input type="email" name="email" class="form-control {{ $errors->has('email') ? 'is-invalid':'' }}" value="{{ old('email', 'demo@specialitycare.co.in') }}" required autofocus placeholder="Enter your email">
        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="form-group">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control" required placeholder="Enter your password">
    </div>
    <div class="remember-row">
        <input type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked':'' }}>
        <label for="remember">Keep me signed in</label>
    </div>
    <button type="submit" class="btn-login">Sign In →</button>
    </form>

    <div class="divider">Are you a patient?</div>
    <div class="patient-cta">
        <div class="patient-cta-label">No login required — book in 30 seconds</div>
        <a href="{{ route('public.book.show') }}">📅 Book an Appointment →</a>
    </div>

    <div class="contact-footer">
        <div><strong>WhatsApp Only:</strong> <a href="https://wa.me/919000000000" style="color:#0ab4b4;text-decoration:none">9000000000</a></div>
        <div><strong>Call:</strong> <a href="tel:+919242001215" style="color:#475569;text-decoration:none">9242001215</a> &nbsp;·&nbsp; <a href="tel:+918900953527" style="color:#475569;text-decoration:none">8900953527</a></div>
    </div>

    </div>
</body>
</html>
