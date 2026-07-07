<?php

namespace App\Http\Controllers;

use App\Services\RegionResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CargoController extends Controller
{
    private const POLA_RESI = '/^RESI(BRT|TGH|TMR)[0-9]{3,}$/';


    // FITUR: Tambah Kargo Baru
    public function tambahForm()
    {
        return view('tambah_kargo', ['regions' => RegionResolver::semua()]);
    }

    public function tambahProses(Request $request)
    {
        $request->validate([
            'region_asal' => 'required|in:barat,tengah,timur',
            'asal_pengiriman' => 'required|string|min:2|max:100|regex:/^[a-zA-Z\s]+$/',
            'tujuan_pengiriman' => 'required|string|min:2|max:100|regex:/^[a-zA-Z\s]+$/',
            'berat' => 'required|numeric|min:0.1|max:5000',
        ], [
            'asal_pengiriman.regex' => 'Nama kota asal hanya boleh berisi huruf.',
            'tujuan_pengiriman.regex' => 'Nama kota tujuan hanya boleh berisi huruf.',
            'berat.max' => 'Berat maksimum per kargo adalah 5000 kg.',
        ]);

        $region = RegionResolver::dariKey($request->region_asal);
        $urutan = DB::connection($region['nama_koneksi'])->table('kargo')->count() + 1;
        $nomorResi = 'RESI' . $region['kode'] . str_pad($urutan, 3, '0', STR_PAD_LEFT);

        // Pastikan nomor resi belum dipakai (jaga-jaga jika ada penghapusan data sebelumnya)
        while (DB::connection($region['nama_koneksi'])->table('kargo')->where('nomor_resi', $nomorResi)->exists()) {
            $urutan++;
            $nomorResi = 'RESI' . $region['kode'] . str_pad($urutan, 3, '0', STR_PAD_LEFT);
        }

        $idKargo = DB::connection($region['nama_koneksi'])->table('kargo')->insertGetId([
            'nomor_resi' => $nomorResi,
            'asal_pengiriman' => $request->asal_pengiriman,
            'tujuan_pengiriman' => $request->tujuan_pengiriman,
            'tanggal_kirim' => now()->toDateString(),
            'berat' => $request->berat,
            'status' => 'Diproses',
            'region_fragment' => ucfirst($request->region_asal),
        ], 'id_kargo');

        // Ambil satu gudang di region ini untuk dicatat sebagai riwayat pertama
        $gudang = DB::connection($region['nama_koneksi'])->table('gudang')
            ->where('wilayah', $region['label'])
            ->first();

        if ($gudang) {
            DB::connection($region['nama_koneksi'])->table('riwayat_pengiriman')->insert([
                'id_kargo' => $idKargo,
                'id_gudang' => $gudang->id_gudang,
                'waktu_update' => now(),
                'status' => 'Diproses',
                'keterangan' => 'Kargo pertama kali didaftarkan ke sistem.',
            ]);
        }

        return back()->with('success', "Kargo berhasil didaftarkan dengan nomor resi: $nomorResi ({$region['label']})");
    }


    // FITUR: Riwayat Pengiriman
    public function riwayatForm()
    {
        return view('riwayat_kargo');
    }

    public function riwayatProses(Request $request)
    {
        $request->validate([
            'nomor_resi' => ['required', 'string', 'max:20'],
        ]);

        $resi = strtoupper(trim($request->input('nomor_resi')));

        if (!preg_match(self::POLA_RESI, $resi)) {
            return back()->with('error', "Format nomor resi tidak valid. Gunakan format seperti RESIBRT001.");
        }

        $koneksi = RegionResolver::dariResi($resi);

        if (!$koneksi) {
            return back()->with('error', 'Format nomor resi tidak dikenali.');
        }

        $kargo = DB::connection($koneksi['nama_koneksi'])->table('kargo')->where('nomor_resi', $resi)->first();

        if (!$kargo) {
            return back()->with('error', "Nomor resi $resi tidak ditemukan di {$koneksi['label']}.");
        }

        $riwayat = DB::connection($koneksi['nama_koneksi'])
            ->table('riwayat_pengiriman')
            ->join('gudang', 'riwayat_pengiriman.id_gudang', '=', 'gudang.id_gudang')
            ->where('riwayat_pengiriman.id_kargo', $kargo->id_kargo)
            ->orderBy('riwayat_pengiriman.waktu_update', 'asc')
            ->select('riwayat_pengiriman.*', 'gudang.nama_gudang', 'gudang.wilayah')
            ->get();

        return view('riwayat_kargo', [
            'kargo' => $kargo,
            'region' => $koneksi['label'],
            'riwayat' => $riwayat,
        ]);
    }
}