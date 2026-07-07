<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Sistem Logistik Nasional')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&family=Inter:wght@400;500;600&display=swap"
        rel="stylesheet">
    <style>
        :root {
            --bg: #f4f7fc;
            --ink: #1e2a4a;
            --text: #384868;
            --text-soft: #7c88a6;
            --blue: #4f7cff;
            --teal: #16b897;
            --amber: #f5a623;
            --card: #ffffff;
            --line: #e6ebf5;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            margin: 0;
            background: var(--bg);
            color: var(--text);
        }

        h1,
        h2,
        h3 {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 700;
            color: var(--ink);
            margin: 0;
        }

        nav.topbar {
            background: var(--card);
            padding: 0 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 72px;
            border-bottom: 1px solid var(--line);
            position: sticky;
            top: 0;
            z-index: 10;
        }

        nav.topbar .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 800;
            font-size: 18px;
            color: var(--ink);
        }

        nav.topbar .brand .dot-flow {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--blue), var(--teal));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }

        nav.topbar .menu {
            display: flex;
            gap: 6px;
        }

        nav.topbar .menu a {
            color: var(--text-soft);
            text-decoration: none;
            padding: 9px 18px;
            font-size: 14px;
            font-weight: 600;
            border-radius: 999px;
        }

        nav.topbar .menu a:hover {
            background: #eef2fb;
            color: var(--ink);
        }

        nav.topbar .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .role-pill {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: var(--blue);
            background: #eaf0ff;
            padding: 5px 12px;
            border-radius: 999px;
        }

        .logout-btn {
            background: var(--bg);
            color: var(--text-soft);
            border: none;
            padding: 8px 16px;
            border-radius: 999px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            font-family: 'Inter', sans-serif;
        }

        .logout-btn:hover {
            background: #fde8e4;
            color: #c0432c;
        }

        main {
            max-width: 1020px;
            margin: 0 auto;
            padding: 44px 24px 70px;
        }

        .eyebrow {
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: var(--teal);
            margin-bottom: 8px;
        }

        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 20px;
            padding: 26px;
            box-shadow: 0 4px 20px rgba(30, 42, 74, 0.05);
        }

        .pill {
            display: inline-flex;
            align-items: center;
            font-size: 12px;
            font-weight: 700;
            padding: 5px 14px;
            border-radius: 999px;
        }

        .pill.diproses {
            background: #fef1dc;
            color: #a9700b;
        }

        .pill.terkirim {
            background: #e8f0ff;
            color: #2952c8;
        }

        .pill.diterima {
            background: #e2f7f0;
            color: #0f8a6b;
        }

        .tag-region {
            font-size: 12px;
            font-weight: 700;
            padding: 5px 14px;
            border-radius: 999px;
            color: white;
        }

        .tag-region.barat {
            background: var(--blue);
        }

        .tag-region.tengah {
            background: var(--teal);
        }

        .tag-region.timur {
            background: var(--amber);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--blue), var(--teal));
            color: white;
            border: none;
            padding: 13px 26px;
            border-radius: 999px;
            font-weight: 700;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            font-size: 14px;
            box-shadow: 0 6px 16px rgba(79, 124, 255, 0.28);
        }

        .btn-primary:hover {
            filter: brightness(1.05);
            transform: translateY(-1px);
        }

        input[type=text],
        input[type=email],
        input[type=password],
        select {
            font-family: 'Inter', sans-serif;
            padding: 13px 16px;
            border: 1.5px solid var(--line);
            border-radius: 14px;
            background: #fbfcfe;
            font-size: 14px;
            width: 100%;
        }

        input:focus,
        select:focus {
            outline: none;
            border-color: var(--blue);
            box-shadow: 0 0 0 4px rgba(79, 124, 255, 0.12);
        }

        .alert {
            padding: 14px 20px;
            border-radius: 16px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert.success {
            background: #e2f7f0;
            color: #0f6e54;
        }

        .alert.error {
            background: #fde8e4;
            color: #a4331d;
        }

        a.link {
            color: var(--blue);
            font-weight: 600;
            text-decoration: none;
        }

        a.link:hover {
            text-decoration: underline;
        }

        table.manifest-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 18px;
            overflow: hidden;
        }

        table.manifest-table th {
            background: #f7f9fd;
            color: var(--text-soft);
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.03em;
            padding: 12px 18px;
            text-align: left;
        }

        table.manifest-table td {
            padding: 13px 18px;
            border-top: 1px solid var(--line);
            font-size: 14px;
        }
    </style>
</head>

<body>
    <nav class="topbar">
        <div class="brand"><span class="dot-flow">📦</span> Kargo Nasional</div>
        <div class="menu">
            @auth
                @php $role = auth()->user()->role; @endphp
                @if ($role === 'pelanggan')
                    <a href="{{ route('tracking.index') }}">Lacak Kargo</a>
                @endif
                @if ($role === 'petugas')
                    <a href="{{ route('tracking.index') }}">Lacak Kargo</a>
                    <a href="{{ route('pindah.form') }}">Pindah Kargo</a>
                @endif
                @if ($role === 'admin')
                    <a href="{{ route('dashboard.admin') }}">Dashboard Admin</a>
                    <a href="{{ route('master.tarif.index') }}">Kelola Tarif</a>
                    <a href="{{ route('log.transaksi') }}">Log Transaksi</a>
                @endif
                @if ($role === 'eksekutif')
                    <a href="{{ route('dashboard.eksekutif') }}">Dashboard Eksekutif</a>
                @endif
            @endauth
        </div>
        <div class="user-info">
            @auth
                @if (in_array(auth()->user()->role, ['petugas', 'admin']))
                    <div style="position:relative;">
                        <button id="bell-btn"
                            style="background:none;border:none;cursor:pointer;font-size:18px;position:relative;padding:6px;">
                            🔔
                            <span id="bell-badge"
                                style="display:none;position:absolute;top:0;right:0;background:#ef4444;color:white;font-size:10px;font-weight:700;border-radius:999px;padding:1px 5px;">0</span>
                        </button>
                        <div id="bell-dropdown"
                            style="display:none;position:absolute;right:0;top:38px;width:300px;background:white;border:1px solid var(--line);border-radius:14px;box-shadow:0 10px 30px rgba(30,42,74,0.15);padding:10px;z-index:50;max-height:320px;overflow-y:auto;">
                        </div>
                    </div>
                @endif
                <span style="font-size:14px;font-weight:600;color:var(--ink);">{{ auth()->user()->name }}</span>
                <span class="role-pill">{{ auth()->user()->role }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="logout-btn" type="submit">Logout</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="link">Login</a>
            @endauth
        </div>
    </nav>

    @auth
        @if (in_array(auth()->user()->role, ['petugas', 'admin']))
            <script>
                let notifTerakhirDilihat = 0;

                async function ambilNotifikasi() {
                    try {
                        const res = await fetch('{{ route('notifikasi.terbaru') }}');
                        const data = await res.json();

                        const dropdown = document.getElementById('bell-dropdown');
                        const badge = document.getElementById('bell-badge');

                        if (data.length === 0) {
                            dropdown.innerHTML =
                                '<div style="padding:14px;font-size:13px;color:var(--text-soft);">Belum ada notifikasi.</div>';
                            badge.style.display = 'none';
                            return;
                        }

                        dropdown.innerHTML = data.map(n => `
                        <div style="padding:10px 12px;border-bottom:1px solid var(--line);font-size:13px;">
                            <div>${n.pesan}</div>
                            <div style="font-size:11px;color:var(--text-soft);margin-top:2px;">${n.waktu}</div>
                        </div>
                    `).join('');

                        if (data.length > notifTerakhirDilihat) {
                            badge.textContent = data.length;
                            badge.style.display = 'inline-block';
                        }
                    } catch (e) {
                        // gagal ambil notifikasi, diam saja agar tidak mengganggu UX
                    }
                }

                document.getElementById('bell-btn').addEventListener('click', () => {
                    const dropdown = document.getElementById('bell-dropdown');
                    const badge = document.getElementById('bell-badge');
                    dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
                    badge.style.display = 'none';
                    notifTerakhirDilihat = 999;
                });

                ambilNotifikasi();
                setInterval(ambilNotifikasi, 5000); // polling tiap 5 detik
            </script>
        @endif
    @endauth

    <main>
        @if (session('success'))
            <div class="alert success">✅ {{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert error">⚠ {{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert error">
                ⚠
                @foreach ($errors->all() as $pesan)
                    {{ $pesan }}@if (!$loop->last)
                        <br>
                    @endif
                @endforeach
            </div>
        @endif

        @yield('content')
    </main>
</body>

</html>
