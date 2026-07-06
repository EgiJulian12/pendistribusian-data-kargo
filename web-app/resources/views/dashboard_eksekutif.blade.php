@extends('layouts.app')

@section('title', 'Dashboard Eksekutif')

@section('content')
    <div class="eyebrow">Laporan Agregat Nasional</div>
    <h1 style="font-size:32px;margin-bottom:8px;">Dashboard Eksekutif</h1>
    <p style="color:var(--text-soft);margin-bottom:28px;font-size:15px;">Rangkuman performa logistik lintas Region Barat, Tengah, dan Timur.</p>

    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:28px;">
        <div class="card" style="text-align:center;">
            <div style="font-size:30px;font-weight:800;color:var(--ink);font-family:'Plus Jakarta Sans',sans-serif;">{{ $totalKargoNasional }}</div>
            <div style="font-size:12px;color:var(--text-soft);font-weight:600;margin-top:4px;">Total Nasional</div>
        </div>
        @foreach ($volumePerRegion as $label => $jumlah)
            <div class="card" style="text-align:center;">
                <div style="font-size:30px;font-weight:800;color:var(--ink);font-family:'Plus Jakarta Sans',sans-serif;">{{ $jumlah }}</div>
                <div style="font-size:12px;color:var(--text-soft);font-weight:600;margin-top:4px;">{{ $label }}</div>
            </div>
        @endforeach
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;">
        <div class="card">
            <h3 style="font-size:15px;margin-bottom:16px;">Volume Kargo per Region</h3>
            <canvas id="chartVolume"></canvas>
        </div>
        <div class="card">
            <h3 style="font-size:15px;margin-bottom:16px;">Distribusi Status (Nasional)</h3>
            <canvas id="chartStatus"></canvas>
        </div>
    </div>

    <div class="card">
        <h3 style="font-size:15px;margin-bottom:16px;">Total Berat Kargo per Region (kg)</h3>
        <canvas id="chartBerat"></canvas>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
    <script>
        const palette = ['#4f7cff', '#16b897', '#f5a623'];

        new Chart(document.getElementById('chartVolume'), {
            type: 'bar',
            data: {
                labels: {!! json_encode(array_keys($volumePerRegion)) !!},
                datasets: [{
                    label: 'Jumlah Kargo',
                    data: {!! json_encode(array_values($volumePerRegion)) !!},
                    backgroundColor: palette,
                    borderRadius: 10,
                }]
            },
            options: { plugins: { legend: { display: false } }, scales: { y: { grid: { color: '#eef1f8' } }, x: { grid: { display: false } } } }
        });

        new Chart(document.getElementById('chartStatus'), {
            type: 'doughnut',
            data: {
                labels: {!! json_encode(array_keys($statusGlobal)) !!},
                datasets: [{
                    data: {!! json_encode(array_values($statusGlobal)) !!},
                    backgroundColor: ['#f5a623', '#4f7cff', '#16b897', '#ef4444'],
                    borderWidth: 0,
                }]
            },
            options: { cutout: '65%' }
        });

        new Chart(document.getElementById('chartBerat'), {
            type: 'bar',
            data: {
                labels: {!! json_encode(array_keys($totalBeratPerRegion)) !!},
                datasets: [{
                    label: 'Total Berat (kg)',
                    data: {!! json_encode(array_values($totalBeratPerRegion)) !!},
                    backgroundColor: '#4f7cff',
                    borderRadius: 10,
                }]
            },
            options: { indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { grid: { color: '#eef1f8' } }, y: { grid: { display: false } } } }
        });
    </script>
@endsection