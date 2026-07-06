@extends('layouts.app')

@section('title', 'Kapasitas Gudang')

@section('content')
    <div class="eyebrow">Monitoring Operasional Gudang</div>
    <h1 style="font-size:32px;margin-bottom:8px;">Kapasitas Gudang per Region</h1>
    <p style="color:var(--text-soft);margin-bottom:28px;font-size:15px;">Perbandingan beban kargo aktif (status "Diproses") terhadap kapasitas maksimum tiap gudang.</p>

    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;">
        @foreach ($dataKapasitas as $g)
            @php
                $regionClass = str_contains($g['region'], 'Barat') ? 'barat' : (str_contains($g['region'], 'Tengah') ? 'tengah' : 'timur');
                $barColor = $g['status'] === 'kritis' ? '#ef4444' : ($g['status'] === 'waspada' ? '#f5a623' : '#16b897');
            @endphp
            <div class="card">
                <span class="tag-region {{ $regionClass }}">{{ $g['region'] }}</span>
                <h3 style="margin:14px 0 4px;font-size:17px;">{{ $g['nama_gudang'] }}</h3>
                <p style="color:var(--text-soft);font-size:13px;margin-bottom:18px;">{{ $g['alamat'] }}</p>

                <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:6px;">
                    <span style="color:var(--text-soft);">Beban Aktif</span>
                    <span style="font-weight:700;">{{ $g['beban_aktif'] }} / {{ $g['kapasitas'] }}</span>
                </div>
                <div style="background:#eef1f8;border-radius:999px;height:10px;overflow:hidden;margin-bottom:10px;">
                    <div style="width:{{ $g['persentase'] }}%;background:{{ $barColor }};height:100%;border-radius:999px;"></div>
                </div>

                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <span style="font-size:20px;font-weight:800;color:var(--ink);font-family:'Plus Jakarta Sans',sans-serif;">{{ $g['persentase'] }}%</span>
                    @if ($g['status'] === 'kritis')
                        <span class="pill" style="background:#fde8e4;color:#a4331d;">⚠ Kritis</span>
                    @elseif ($g['status'] === 'waspada')
                        <span class="pill diproses">⚠ Waspada</span>
                    @else
                        <span class="pill diterima">✓ Aman</span>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <p style="margin-top:28px;"><a href="{{ route('tracking.index') }}" class="link">&larr; Kembali ke Pelacakan Kargo</a></p>
@endsection