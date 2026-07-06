<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - Kargo Nasional</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #eef3ff 0%, #f4f7fc 40%, #e9fbf6 100%);
            position: relative;
            overflow: hidden;
        }
        .blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(60px);
            opacity: 0.35;
        }
        .blob1 { width: 420px; height: 420px; background: #4f7cff; top: -120px; left: -100px; }
        .blob2 { width: 380px; height: 380px; background: #16b897; bottom: -140px; right: -80px; }

        .route-svg { position: absolute; top: 0; left: 0; width: 100%; height: 100%; opacity: 0.5; }

        .panel {
            background: white;
            width: 400px;
            border-radius: 28px;
            box-shadow: 0 30px 70px rgba(30,42,74,0.18);
            padding: 40px 36px;
            position: relative;
            z-index: 2;
        }
        .logo-row { display: flex; align-items: center; gap: 12px; margin-bottom: 6px; }
        .logo-dot {
            width: 42px; height: 42px; border-radius: 14px;
            background: linear-gradient(135deg, #4f7cff, #16b897);
            display: flex; align-items: center; justify-content: center;
            font-size: 20px;
        }
        .brand-title { font-family: 'Plus Jakarta Sans', sans-serif; font-weight: 800; font-size: 20px; color: #1e2a4a; }
        .brand-sub { font-size: 13px; color: #7c88a6; margin-bottom: 28px; }

        label { font-size: 12px; font-weight: 600; color: #7c88a6; display: block; margin-bottom: 6px; margin-top: 16px; }
        input {
            width: 100%; padding: 13px 16px; border: 1.5px solid #e6ebf5; border-radius: 14px;
            font-family: 'Inter', sans-serif; font-size: 14px; background: #fbfcfe; box-sizing: border-box;
        }
        input:focus { outline: none; border-color: #4f7cff; box-shadow: 0 0 0 4px rgba(79,124,255,0.12); }

        button {
            width: 100%; margin-top: 24px; padding: 14px;
            background: linear-gradient(135deg, #4f7cff, #16b897);
            color: white; border: none; border-radius: 999px;
            font-weight: 700; cursor: pointer; font-size: 15px;
            box-shadow: 0 10px 24px rgba(79,124,255,0.3);
        }
        button:hover { filter: brightness(1.05); }

        .error { background: #fde8e4; color: #a4331d; padding: 12px 16px; border-radius: 14px; font-size: 13px; margin-top: 16px; }
        .demo {
            margin-top: 26px; padding-top: 20px; border-top: 1px dashed #e6ebf5;
            font-size: 12px; color: #a3adc4; line-height: 1.9;
        }
        .demo b { color: #7c88a6; }
    </style>
</head>
<body>
    <div class="blob blob1"></div>
    <div class="blob blob2"></div>
    <svg class="route-svg" viewBox="0 0 800 600" preserveAspectRatio="xMidYMid slice">
        <path d="M60,480 Q250,380 400,420 T740,180" fill="none" stroke="#4f7cff" stroke-width="2" stroke-dasharray="6 10" opacity="0.4"/>
        <circle cx="60" cy="480" r="6" fill="#4f7cff"/>
        <circle cx="400" cy="420" r="6" fill="#16b897"/>
        <circle cx="740" cy="180" r="6" fill="#f5a623"/>
    </svg>

    <div class="panel">
        <div class="logo-row">
            <div class="logo-dot">📦</div>
            <div class="brand-title">Kargo Nasional</div>
        </div>
        <div class="brand-sub">Sistem Logistik & Distribusi Terdistribusi</div>

        @if (session('error'))
            <div class="error">⚠ {{ session('error') }}</div>
        @endif

        <form method="POST" action="{{ route('login.proses') }}">
            @csrf
            <label>Email</label>
            <input type="email" name="email" required>
            <label>Password</label>
            <input type="password" name="password" required>
            <button type="submit">Masuk ke Sistem</button>
        </form>

        <div class="demo">
            <b>AKUN DEMO</b> · password: password123<br>
            pelanggan@demo.com &nbsp;·&nbsp; petugas@demo.com<br>
            admin@demo.com &nbsp;·&nbsp; eksekutif@demo.com
        </div>
    </div>
</body>
</html>