<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Pindah Kargo Lintas Region - 2PC</title>
    <style>
        body { font-family: sans-serif; max-width: 600px; margin: 60px auto; padding: 0 20px; }
        h1 { color: #2d3748; }
        p.desc { color: #6b7280; margin-bottom: 24px; }
        form { display: flex; flex-direction: column; gap: 12px; margin-bottom: 24px; }
        input[type=text], select { padding: 10px; border: 1px solid #ccc; border-radius: 6px; font-size: 14px; }
        button { padding: 12px; background: #dc2626; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; }
        button:hover { background: #b91c1c; }
        .success { background: #f0fdf4; border: 1px solid #86efac; padding: 16px; border-radius: 8px; color: #166534; }
        .error { background: #fef2f2; border: 1px solid #fca5a5; padding: 16px; border-radius: 8px; color: #991b1b; }
        .info-box { background: #eff6ff; border: 1px solid #93c5fd; padding: 12px 16px; border-radius: 8px; font-size: 13px; color: #1e40af; margin-bottom: 20px; }
        a { color: #2563eb; text-decoration: none; }
    </style>
</head>
<body>
    <h1>🔄 Pindah Kargo Lintas Region</h1>
    <p class="desc">Fitur ini menjalankan <b>Two-Phase Commit (2PC)</b> beneran ke dua database sekaligus. Kalau salah satu gagal, dua-duanya otomatis dibatalkan (rollback).</p>

    <div class="info-box">
        💡 Coba resi yang belum berstatus "Terkirim", misalnya: <b>RESITGH002</b>, <b>RESITMR002</b>
    </div>

    @if (session('success'))
        <div class="success">✅ {{ session('success') }}</div>
        <br>
    @endif

    @if (session('error'))
        <div class="error">❌ {{ session('error') }}</div>
        <br>
    @endif

    <form action="{{ route('pindah.proses') }}" method="POST">
        @csrf
        <label>Nomor Resi (asal region otomatis terdeteksi dari kode)</label>
        <input type="text" name="nomor_resi" placeholder="Contoh: RESITGH002" required>

        <label>Pindahkan ke Region</label>
        <select name="tujuan_region" required>
            <option value="">-- Pilih region tujuan --</option>
            <option value="barat">Region Barat</option>
            <option value="tengah">Region Tengah</option>
            <option value="timur">Region Timur</option>
        </select>

        <button type="submit">Proses Transaksi 2PC</button>
    </form>

    <p><a href="{{ route('tracking.index') }}">&larr; Kembali ke Pelacakan Kargo</a></p>
</body>
</html>