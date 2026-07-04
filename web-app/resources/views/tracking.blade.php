<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Pelacakan Kargo - Sistem Logistik Nasional</title>
    <style>
        body {
            font-family: sans-serif;
            max-width: 600px;
            margin: 60px auto;
            padding: 0 20px;
        }

        h1 {
            color: #2d3748;
        }

        form {
            margin-bottom: 24px;
        }

        input[type=text] {
            padding: 10px;
            width: 70%;
            border: 1px solid #ccc;
            border-radius: 6px;
        }

        button {
            padding: 10px 20px;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }

        .hasil {
            background: #f0fdf4;
            border: 1px solid #86efac;
            padding: 16px;
            border-radius: 8px;
            margin-top: 16px;
        }

        .error {
            background: #fef2f2;
            border: 1px solid #fca5a5;
            padding: 16px;
            border-radius: 8px;
            margin-top: 16px;
            color: #991b1b;
        }

        .label-region {
            display: inline-block;
            background: #dbeafe;
            color: #1e40af;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 13px;
        }
    </style>
</head>

<body>
    <h1>📦 Pelacakan Kargo Nasional</h1>
    <p>Masukkan nomor resi untuk melihat status pengiriman.</p>

    <form action="{{ route('tracking.search') }}" method="POST">
        @csrf
        <input type="text" name="nomor_resi" placeholder="Contoh: RESIBRT001" required>
        <button type="submit">Lacak</button>
    </form>

    @if (session('error'))
        <div class="error">{{ session('error') }}</div>
    @endif

    @if (isset($kargo))
        <div class="hasil">
            <span class="label-region">📍 Diambil dari: {{ $region }}</span>
            @if ($dari_cache)
                <span class="label-region" style="background:#fef9c3;color:#854d0e;">⚡ Dari Cache (Redis)</span>
            @else
                <span class="label-region" style="background:#e0e7ff;color:#3730a3;">🗄️ Dari Database (PostgreSQL)</span>
            @endif
            <h3>Nomor Resi: {{ $kargo->nomor_resi }}</h3>
            <p><b>Status:</b> {{ $kargo->status }}</p>
            <p><b>Asal:</b> {{ $kargo->asal_pengiriman }}</p>
            <p><b>Tujuan:</b> {{ $kargo->tujuan_pengiriman }}</p>
            <p><b>Berat:</b> {{ $kargo->berat }} kg</p>
            <p><b>Tanggal Kirim:</b> {{ $kargo->tanggal_kirim }}</p>
        </div>
    @endif
</body>

</html>
