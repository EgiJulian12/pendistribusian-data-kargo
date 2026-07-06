@extends('layouts.app')

@section('title', 'Riwayat Pengiriman')

@section('content')
    <div class="eyebrow">Riwayat Perjalanan Kargo</div>
    <h1 style="font-size:32px;margin-bottom:8px;">Riwayat Pengiriman</h1>
    <p style="color:var(--text-soft);margin-bottom:28px;font-size:15px;">Lihat jejak perjalanan status kargo dari waktu ke waktu.</p>

    <div class="card" style="margin-bottom:24px;">
        <form action="{{ route('kargo.riwayat.proses') }}" method="POST" style="display:flex;gap:12px;">
            @csrf
            <input type="text" name="nomor_resi" placeholder="Contoh: RESIBRT001" required style="flex:1;">
            <button type="submit" class="btn-primary" style="white-space:nowrap;">Lihat Riwayat</button>
        </form>
    </div>

    @if (isset($kargo))
        <div class="card" style="margin-bottom:20px;">
            @php
                $regionClass = str_contains($region, 'Barat') ? 'barat' : (str_contains($region, 'Tengah') ? 'tengah' : 'timur');
            @endphp
            <span class="tag-region {{ $regionClass }}">📍 {{ $region }}</span>
            <h2 style="font-size:22px;margin-top:14px;">{{ $kargo->nomor_resi }}</h2>
            <p style="color:var(--text-soft);font-size:14px;">{{ $kargo->asal_pengiriman }} &rarr; {{ $kargo->tujuan_pengiriman }} · {{ $kargo->berat }} kg</p>
        </div>

        <div class="card">
            <h3 style="font-size:15px;margin-bottom:20px;">Jejak Perjalanan ({{ $riwayat->count() }} catatan)</h3>

            @forelse ($riwayat as $i => $r)
                <div style="display:flex;gap:16px;padding-bottom:{{ $loop->last ? '0' : '20px' }};">
                    <div style="display:flex;flex-direction:column;align-items:center;">
                        <div style="width:14px;height:14px;border-radius:50%;background:linear-gradient(135deg,var(--blue),var(--teal));flex-shrink:0;"></div>
                        @unless ($loop->last)
                            <div style="width:2px;flex:1;background:var(--line);margin-top:4px;"></div>
                        @endunless
                    </div>
                    <div style="padding-bottom:4px;">
                        <div style="font-weight:700;font-size:14px;color:var(--ink);">{{ $r->status }}</div>
                        <div style="font-size:13px;color:var(--text-soft);margin-top:2px;">{{ $r->nama_gudang }} · {{ $r->wilayah }}</div>
                        <div style="font-size:12px;color:var(--text-soft);margin-top:2px;">{{ \Carbon\Carbon::parse($r->waktu_update)->format('d M Y, H:i') }}</div>
                        @if ($r->keterangan)
                            <div style="font-size:13px;color:var(--text);margin-top:6px;background:#f7f9fd;padding:8px 12px;border-radius:10px;">{{ $r->keterangan }}</div>
                        @endif
                    </div>
                </div>
            @empty
                <p style="color:var(--text-soft);">Belum ada riwayat perjalanan untuk kargo ini.</p>
            @endforelse
        </div>
    @endif
@endsection