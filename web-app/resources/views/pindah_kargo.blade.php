@extends('layouts.app')

@section('title', 'Pindah Kargo Lintas Region')

@section('content')
    <div class="eyebrow">Cross-Regional Transaction · Two-Phase Commit</div>
    <h1 style="font-size:32px;margin-bottom:8px;">Pindah Kargo Lintas Region</h1>
    <p style="color:var(--text-soft);margin-bottom:12px;font-size:15px;">Menjalankan transaksi 2PC beneran ke dua database sekaligus — kalau salah satu gagal, keduanya otomatis dibatalkan.</p>

    <div class="card" style="background:#eaf0ff;border:none;margin-bottom:24px;">
        <div style="font-size:13px;color:#2952c8;">💡 Coba resi yang belum berstatus "Terkirim", misalnya: <b>RESITGH002</b> atau <b>RESITMR002</b></div>
    </div>

    <div class="card">
        <form action="{{ route('pindah.proses') }}" method="POST">
            @csrf
            <label style="font-size:12px;font-weight:600;color:var(--text-soft);display:block;margin-bottom:6px;">Nomor Resi</label>
            <input type="text" name="nomor_resi" placeholder="Contoh: RESITGH002" required style="margin-bottom:18px;">

            <label style="font-size:12px;font-weight:600;color:var(--text-soft);display:block;margin-bottom:6px;">Pindahkan ke Region</label>
            <select name="tujuan_region" required style="margin-bottom:22px;">
                <option value="">— Pilih region tujuan —</option>
                <option value="barat">Region Barat</option>
                <option value="tengah">Region Tengah</option>
                <option value="timur">Region Timur</option>
            </select>

            <button type="submit" class="btn-primary" style="width:100%;">Proses Transaksi 2PC</button>
        </form>
    </div>

    <p style="margin-top:24px;"><a href="{{ route('tracking.index') }}" class="link">&larr; Kembali ke Pelacakan Kargo</a></p>
@endsection