@extends('layouts.app')

@section('title', 'Log Audit Transaksi')

@section('content')
    <div class="eyebrow">Audit Trail · Two-Phase Commit</div>
    <h1 style="font-size:32px;margin-bottom:8px;">Log Transaksi Lintas Region</h1>
    <p style="color:var(--text-soft);margin-bottom:28px;font-size:15px;">Riwayat seluruh upaya transaksi 2PC, baik yang berhasil di-commit maupun yang di-rollback.</p>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:28px;">
        <div class="card" style="text-align:center;">
            <div style="font-size:30px;font-weight:800;color:var(--teal);font-family:'Plus Jakarta Sans',sans-serif;">{{ $totalSukses }}</div>
            <div style="font-size:12px;color:var(--text-soft);font-weight:600;margin-top:4px;">Transaksi Sukses</div>
        </div>
        <div class="card" style="text-align:center;">
            <div style="font-size:30px;font-weight:800;color:#ef4444;font-family:'Plus Jakarta Sans',sans-serif;">{{ $totalGagal }}</div>
            <div style="font-size:12px;color:var(--text-soft);font-weight:600;margin-top:4px;">Transaksi Gagal (Rollback)</div>
        </div>
    </div>

    <table class="manifest-table">
        <thead>
            <tr>
                <th>Waktu</th>
                <th>Resi</th>
                <th>Asal &rarr; Tujuan</th>
                <th>Status</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($logs as $log)
                <tr>
                    <td style="white-space:nowrap;">{{ \Carbon\Carbon::parse($log->waktu)->format('d M Y, H:i:s') }}</td>
                    <td style="font-weight:700;">{{ $log->nomor_resi }}</td>
                    <td>{{ $log->region_asal }} &rarr; {{ $log->region_tujuan }}</td>
                    <td>
                        @if ($log->status === 'SUKSES')
                            <span class="pill diterima">✓ Sukses</span>
                        @else
                            <span class="pill" style="background:#fde8e4;color:#a4331d;">✗ Rollback</span>
                        @endif
                    </td>
                    <td style="font-size:13px;color:var(--text-soft);max-width:280px;">{{ Str::limit($log->pesan, 80) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" style="text-align:center;color:var(--text-soft);">Belum ada transaksi yang tercatat.</td></tr>
            @endforelse
        </tbody>
    </table>

    <p style="margin-top:24px;"><a href="{{ route('dashboard.admin') }}" class="link">&larr; Kembali ke Dashboard Admin</a></p>
@endsection