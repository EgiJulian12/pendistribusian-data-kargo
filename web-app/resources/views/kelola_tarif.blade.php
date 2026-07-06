@extends('layouts.app')

@section('title', 'Kelola Tarif Pengiriman')

@section('content')
    <div class="eyebrow">Sentralisasi Pembaruan Data Master</div>
    <h1 style="font-size:32px;margin-bottom:8px;">Kelola Tarif Pengiriman</h1>
    <p style="color:var(--text-soft);margin-bottom:12px;font-size:15px;">Perubahan disimpan di Peladen Pusat dan otomatis direplikasi ke seluruh region.</p>

    <div class="card" style="background:#eaf0ff;border:none;margin-bottom:24px;">
        <div style="font-size:13px;color:#2952c8;">💡 Setelah menyimpan, cek Dashboard Administrator untuk memverifikasi replikasi sudah tersinkronisasi ke semua region.</div>
    </div>

    <table class="manifest-table">
        <thead>
            <tr>
                <th>Asal</th>
                <th>Tujuan</th>
                <th>Berat Min</th>
                <th>Berat Max</th>
                <th>Tarif Saat Ini</th>
                <th>Ubah Tarif</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($dataTarif as $t)
                <tr>
                    <td style="font-weight:600;">{{ $t->wilayah_asal }}</td>
                    <td style="font-weight:600;">{{ $t->wilayah_tujuan }}</td>
                    <td>{{ $t->berat_min }} kg</td>
                    <td>{{ $t->berat_max }} kg</td>
                    <td>Rp {{ number_format($t->tarif, 0, ',', '.') }}</td>
                    <td>
                        <form action="{{ route('master.tarif.update', $t->id_tarif) }}" method="POST" style="display:flex;gap:8px;">
                            @csrf
                            <input type="text" name="tarif" value="{{ $t->tarif }}" style="width:120px;padding:8px 10px;">
                            <button type="submit" class="btn-primary" style="padding:8px 16px;font-size:13px;">Simpan</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p style="margin-top:24px;"><a href="{{ route('dashboard.admin') }}" class="link">&larr; Kembali ke Dashboard Admin</a></p>
@endsection