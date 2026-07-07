<?php

namespace App\Http\Controllers;

use App\Services\RegionResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class TrackingController extends Controller
{
    // Format resi: RESI + kode region (BRT/TGH/TMR) + 3 digit angka. Contoh: RESIBRT001
    private const POLA_RESI = '/^RESI(BRT|TGH|TMR)[0-9]{3,}$/';

    // FITUR 1: Distributed Tracking Query (dengan Redis cache)
    public function index()
    {
        return view('tracking');
    }

    public function search(Request $request)
    {
        $request->validate([
            'nomor_resi' => ['required', 'string', 'max:20'],
        ], [
            'nomor_resi.required' => 'Nomor resi wajib diisi.',
        ]);

        $resi = strtoupper(trim($request->input('nomor_resi')));

        if (!preg_match(self::POLA_RESI, $resi)) {
            return back()->with('error', "Format nomor resi tidak valid. Gunakan format seperti RESIBRT001.");
        }

        $cacheKey = "resi:$resi";

        // Global Query Optimization: cek Redis dulu 
        $cached = Redis::get($cacheKey);

        if ($cached) {
            $data = json_decode($cached, true);
            return view('tracking', [
                'kargo' => (object) $data,
                'region' => $data['_region'],
                'dari_cache' => true,
            ]);
        }

        // Cache miss: lanjut ke PostgreSQL 
        $koneksi = RegionResolver::dariResi($resi);

        if (!$koneksi) {
            return back()->with('error', 'Format nomor resi tidak dikenali.');
        }

        $kargo = DB::connection($koneksi['nama_koneksi'])
            ->table('kargo')
            ->where('nomor_resi', $resi)
            ->first();

        if (!$kargo) {
            return back()->with('error', "Nomor resi $resi tidak ditemukan di {$koneksi['label']}.");
        }

        // Simpan ke Redis untuk request berikutnya, kadaluarsa 5 menit
        $dataArray = (array) $kargo;
        $dataArray['_region'] = $koneksi['label'];
        Redis::set($cacheKey, json_encode($dataArray));
        Redis::expire($cacheKey, 300);

        return view('tracking', [
            'kargo' => $kargo,
            'region' => $koneksi['label'],
            'dari_cache' => false,
        ]);
    }

   
    // FITUR 2: Cross-Regional Transaction (Two-Phase Commit)
    public function pindahForm()
    {
        return view('pindah_kargo');
    }

    public function pindahProses(Request $request)
    {
        $request->validate([
            'nomor_resi' => ['required', 'string', 'max:20'],
            'tujuan_region' => ['required', 'in:barat,tengah,timur'],
        ], [
            'nomor_resi.required' => 'Nomor resi wajib diisi.',
            'tujuan_region.required' => 'Region tujuan wajib dipilih.',
            'tujuan_region.in' => 'Region tujuan tidak valid.',
        ]);

        $resi = strtoupper(trim($request->input('nomor_resi')));

        if (!preg_match(self::POLA_RESI, $resi)) {
            return back()->with('error', "Format nomor resi tidak valid. Gunakan format seperti RESIBRT001.");
        }

        $tujuanRegion = $request->input('tujuan_region');

        $asal = RegionResolver::dariResi($resi);
        if (!$asal) {
            return back()->with('error', 'Nomor resi tidak dikenali.');
        }

        $tujuan = RegionResolver::dariKey($tujuanRegion);
        if (!$tujuan) {
            return back()->with('error', 'Region tujuan tidak valid.');
        }
        if ($tujuan['nama_koneksi'] === $asal['nama_koneksi']) {
            return back()->with('error', 'Region tujuan tidak boleh sama dengan region asal.');
        }

        $kargo = DB::connection($asal['nama_koneksi'])->table('kargo')->where('nomor_resi', $resi)->first();
        if (!$kargo) {
            return back()->with('error', "Resi $resi tidak ditemukan di {$asal['label']}.");
        }
        if ($kargo->status === 'Terkirim') {
            return back()->with('error', "Kargo $resi sudah berstatus Terkirim, tidak bisa dipindah lagi.");
        }

        $gid = 'web_' . $resi . '_' . time();
        $pdoAsal = DB::connection($asal['nama_koneksi'])->getPdo();
        $pdoTujuan = DB::connection($tujuan['nama_koneksi'])->getPdo();

        try {
            // FASE 1: PREPARE di kedua sisi 
            $pdoAsal->exec("BEGIN");
            $pdoAsal->exec("UPDATE kargo SET status = 'Terkirim' WHERE nomor_resi = " . $pdoAsal->quote($resi));
            $pdoAsal->exec("PREPARE TRANSACTION " . $pdoAsal->quote($gid));

            $pdoTujuan->exec("BEGIN");
            $sql = sprintf(
                "INSERT INTO kargo (nomor_resi, asal_pengiriman, tujuan_pengiriman, berat, status, region_fragment) VALUES (%s, %s, %s, %s, %s, %s)",
                $pdoTujuan->quote($kargo->nomor_resi),
                $pdoTujuan->quote($kargo->asal_pengiriman),
                $pdoTujuan->quote($kargo->tujuan_pengiriman),
                $kargo->berat,
                $pdoTujuan->quote('Diterima'),
                $pdoTujuan->quote($tujuan['kode'])
            );
            $pdoTujuan->exec($sql);
            $pdoTujuan->exec("PREPARE TRANSACTION " . $pdoTujuan->quote($gid));

            // ===== FASE 2: COMMIT di kedua sisi =====
            $pdoAsal->exec("COMMIT PREPARED " . $pdoAsal->quote($gid));
            $pdoTujuan->exec("COMMIT PREPARED " . $pdoTujuan->quote($gid));

            return back()->with('success', "Kargo $resi berhasil dipindahkan dari {$asal['label']} ke {$tujuan['label']} (via 2PC).");

        } catch (\Throwable $e) {
            // ===== ABORT: rollback prepared di kedua sisi =====
            try { $pdoAsal->exec("ROLLBACK PREPARED " . $pdoAsal->quote($gid)); } catch (\Throwable $e2) {}
            try { $pdoTujuan->exec("ROLLBACK PREPARED " . $pdoTujuan->quote($gid)); } catch (\Throwable $e2) {}
            try { $pdoAsal->exec("ROLLBACK"); } catch (\Throwable $e2) {}
            try { $pdoTujuan->exec("ROLLBACK"); } catch (\Throwable $e2) {}

            return back()->with('error', "Transaksi dibatalkan (2PC rollback). Sebab: " . $e->getMessage());
        }
    }
}