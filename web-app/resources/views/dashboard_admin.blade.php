@extends('layouts.app')

@section('title', 'Dashboard Administrator Pusat')

@section('content')
    <div class="eyebrow">Monitoring Replikasi Data Master</div>
    <h1 style="font-size:32px;margin-bottom:8px;">Dashboard Administrator Pusat</h1>
    <p style="color:var(--text-soft);margin-bottom:28px;font-size:15px;">Status kesehatan sinkronisasi dari Peladen Pusat ke tiga Region.</p>

    <h3 style="font-size:16px;margin-bottom:14px;">Status Sinkronisasi Replikasi</h3>
    <table class="manifest-table" style="margin-bottom:32px;">
        <thead>
            <tr>
                <th>Region</th>
                <th>Gudang</th>
                <th>Tarif Pengiriman</th>
                <th>Kode Pos</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($statusSinkronisasi as $regionLabel => $tabelStatus)
                <tr>
                    <td style="font-weight:600;">{{ $regionLabel }}</td>
                    @foreach (['gudang', 'tarif_pengiriman', 'kode_pos'] as $tabel)
                        <td>
                            {{ $tabelStatus[$tabel]['jumlah'] }} baris
                            @if ($tabelStatus[$tabel]['sinkron'])
                                <span class="pill diterima">✓ Sinkron</span>
                            @else
                                <span class="pill" style="background:#fde8e4;color:#a4331d;">✗ Tidak Sinkron</span>
                            @endif
                        </td>
                    @endforeach
                </tr>
            @endforeach
            <tr style="background:#f7f9fd;">
                <td style="font-weight:700;">Peladen Pusat (sumber)</td>
                <td>{{ $jumlahPusat['gudang'] }} baris</td>
                <td>{{ $jumlahPusat['tarif_pengiriman'] }} baris</td>
                <td>{{ $jumlahPusat['kode_pos'] }} baris</td>
            </tr>
        </tbody>
    </table>

    <h3 style="font-size:16px;margin-bottom:14px;">Data Master: Gudang</h3>
    <table class="manifest-table" style="margin-bottom:32px;">
        <thead>
            <tr><th>Nama Gudang</th><th>Wilayah</th><th>Alamat</th><th>Kapasitas</th></tr>
        </thead>
        <tbody>
            @foreach ($dataGudang as $g)
                <tr>
                    <td style="font-weight:600;">{{ $g->nama_gudang }}</td>
                    <td><span class="tag-region {{ strtolower($g->wilayah) }}">{{ $g->wilayah }}</span></td>
                    <td>{{ $g->alamat }}</td>
                    <td>{{ $g->kapasitas }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h3 style="font-size:16px;margin-bottom:14px;">Data Master: Tarif Pengiriman</h3>
    <table class="manifest-table">
        <thead>
            <tr><th>Asal</th><th>Tujuan</th><th>Berat Min</th><th>Berat Max</th><th>Tarif</th></tr>
        </thead>
        <tbody>
            @foreach ($dataTarif as $t)
                <tr>
                    <td>{{ $t->wilayah_asal }}</td>
                    <td>{{ $t->wilayah_tujuan }}</td>
                    <td>{{ $t->berat_min }} kg</td>
                    <td>{{ $t->berat_max }} kg</td>
                    <td style="font-weight:600;">Rp {{ number_format($t->tarif, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection