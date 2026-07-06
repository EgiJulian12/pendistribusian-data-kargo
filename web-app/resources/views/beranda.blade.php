@extends('layouts.app')

@section('title', 'Beranda')

@section('content')
    @php $role = auth()->user()->role; @endphp

    <div class="eyebrow">Beranda</div>
    <h1 style="font-size:34px;margin-bottom:8px;">Halo, {{ auth()->user()->name }} 👋</h1>
    <p style="color:var(--text-soft);margin-bottom:32px;font-size:15px;">Berikut akses yang tersedia sesuai peran Anda dalam sistem.</p>

    @if ($role === 'pelanggan')
        <div class="card">
            <span class="tag-region barat">Pelanggan</span>
            <h3 style="margin:16px 0 8px;font-size:20px;">Lacak Status Pengiriman</h3>
            <p style="color:var(--text-soft);font-size:14px;margin-bottom:18px;">Masukkan nomor resi untuk melihat status dan lokasi kargo Anda secara real-time.</p>
            <a href="{{ route('tracking.index') }}" class="btn-primary" style="display:inline-block;text-decoration:none;">Mulai Lacak Kargo →</a>
        </div>
    @endif

    @if ($role === 'petugas')
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
            <div class="card">
                <span class="tag-region barat">Petugas Gudang</span>
                <h3 style="margin:16px 0 8px;font-size:18px;">Lacak Kargo</h3>
                <p style="color:var(--text-soft);font-size:14px;margin-bottom:16px;">Cari status pengiriman kargo di seluruh region.</p>
                <a href="{{ route('tracking.index') }}" class="link">Buka halaman →</a>
            </div>
            <div class="card">
                <span class="tag-region timur">Petugas Gudang</span>
                <h3 style="margin:16px 0 8px;font-size:18px;">Pindah Kargo Lintas Region</h3>
                <p style="color:var(--text-soft);font-size:14px;margin-bottom:16px;">Jalankan transaksi Two-Phase Commit antar wilayah.</p>
                <a href="{{ route('pindah.form') }}" class="link">Buka halaman →</a>
            </div>
        </div>
    @endif

    @if ($role === 'admin')
        <div class="card">
            <span class="tag-region tengah">Administrator Pusat</span>
            <h3 style="margin:16px 0 8px;font-size:20px;">Monitoring Replikasi Data Master</h3>
            <p style="color:var(--text-soft);font-size:14px;margin-bottom:18px;">Pantau kesehatan sinkronisasi data referensial dari Peladen Pusat ke seluruh region.</p>
            <a href="{{ route('dashboard.admin') }}" class="btn-primary" style="display:inline-block;text-decoration:none;">Buka Dashboard Admin →</a>
        </div>
    @endif

    @if ($role === 'eksekutif')
        <div class="card">
            <span class="tag-region timur">Eksekutif</span>
            <h3 style="margin:16px 0 8px;font-size:20px;">Laporan Kinerja Nasional</h3>
            <p style="color:var(--text-soft);font-size:14px;margin-bottom:18px;">Lihat agregasi volume kargo, status pengiriman, dan performa tiap region secara nasional.</p>
            <a href="{{ route('dashboard.eksekutif') }}" class="btn-primary" style="display:inline-block;text-decoration:none;">Buka Dashboard Eksekutif →</a>
        </div>
    @endif
@endsection