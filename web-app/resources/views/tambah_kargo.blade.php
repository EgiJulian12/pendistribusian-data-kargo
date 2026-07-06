@extends('layouts.app')

@section('title', 'Tambah Kargo Baru')

@section('content')
    <div class="eyebrow">Pendaftaran Kargo Baru</div>
    <h1 style="font-size:32px;margin-bottom:8px;">Tambah Kargo</h1>
    <p style="color:var(--text-soft);margin-bottom:28px;font-size:15px;">Nomor resi akan dibuat otomatis sesuai region asal yang dipilih.</p>

    <div class="card">
        <form action="{{ route('kargo.tambah.proses') }}" method="POST">
            @csrf
            <label style="font-size:12px;font-weight:600;color:var(--text-soft);display:block;margin-bottom:6px;">Region Asal Pengiriman</label>
            <select name="region_asal" required style="margin-bottom:18px;">
                <option value="">— Pilih region —</option>
                <option value="barat">Region Barat</option>
                <option value="tengah">Region Tengah</option>
                <option value="timur">Region Timur</option>
            </select>

            <label style="font-size:12px;font-weight:600;color:var(--text-soft);display:block;margin-bottom:6px;">Kota Asal Pengiriman</label>
            <input type="text" name="asal_pengiriman" placeholder="Contoh: Bandung" required style="margin-bottom:18px;">

            <label style="font-size:12px;font-weight:600;color:var(--text-soft);display:block;margin-bottom:6px;">Kota Tujuan Pengiriman</label>
            <input type="text" name="tujuan_pengiriman" placeholder="Contoh: Semarang" required style="margin-bottom:18px;">

            <label style="font-size:12px;font-weight:600;color:var(--text-soft);display:block;margin-bottom:6px;">Berat (kg)</label>
            <input type="text" name="berat" placeholder="Contoh: 2.5" required style="margin-bottom:22px;">

            <button type="submit" class="btn-primary" style="width:100%;">Daftarkan Kargo</button>
        </form>
    </div>

    <p style="margin-top:24px;"><a href="{{ route('tracking.index') }}" class="link">&larr; Kembali ke Pelacakan Kargo</a></p>
@endsection