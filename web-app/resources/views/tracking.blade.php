@extends('layouts.app')

@section('title', 'Lacak Kargo')

@section('content')
    <div class="eyebrow">Distributed Tracking Query</div>
    <h1 style="font-size:32px;margin-bottom:8px;">Lacak Kargo</h1>
    <p style="color:var(--text-soft);margin-bottom:28px;font-size:15px;">Masukkan nomor resi — sistem otomatis mendeteksi region penyimpanan data dari kodenya.</p>

    <div class="card" style="margin-bottom:24px;">
        <form action="{{ route('tracking.search') }}" method="POST" style="display:flex;gap:12px;">
            @csrf
            <input type="text" name="nomor_resi" placeholder="Contoh: RESIBRT001" required style="flex:1;">
            <button type="submit" class="btn-primary" style="white-space:nowrap;">Lacak Kargo</button>
        </form>
    </div>

    @if (isset($kargo))
        <div class="card">
            <div style="display:flex;gap:8px;margin-bottom:18px;">
                @php
                    $regionClass = str_contains($region, 'Barat') ? 'barat' : (str_contains($region, 'Tengah') ? 'tengah' : 'timur');
                @endphp
                <span class="tag-region {{ $regionClass }}">📍 {{ $region }}</span>
                @if ($dari_cache)
                    <span class="pill terkirim">⚡ Dari Cache Redis</span>
                @else
                    <span class="pill diproses">🗄️ Dari Database PostgreSQL</span>
                @endif
            </div>

            <h2 style="font-size:24px;font-family:'Inter',sans-serif;letter-spacing:0.02em;">{{ $kargo->nomor_resi }}</h2>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:20px;">
                <div>
                    <div style="font-size:12px;color:var(--text-soft);font-weight:600;margin-bottom:4px;">STATUS</div>
                    @php
                        $statusClass = $kargo->status === 'Terkirim' ? 'terkirim' : ($kargo->status === 'Diterima' ? 'diterima' : 'diproses');
                    @endphp
                    <span class="pill {{ $statusClass }}">{{ $kargo->status }}</span>
                </div>
                <div>
                    <div style="font-size:12px;color:var(--text-soft);font-weight:600;margin-bottom:4px;">BERAT</div>
                    <div style="font-weight:600;">{{ $kargo->berat }} kg</div>
                </div>
                <div>
                    <div style="font-size:12px;color:var(--text-soft);font-weight:600;margin-bottom:4px;">ASAL</div>
                    <div style="font-weight:600;">{{ $kargo->asal_pengiriman }}</div>
                </div>
                <div>
                    <div style="font-size:12px;color:var(--text-soft);font-weight:600;margin-bottom:4px;">TUJUAN</div>
                    <div style="font-weight:600;">{{ $kargo->tujuan_pengiriman }}</div>
                </div>
            </div>
        </div>
    @endif
@endsection