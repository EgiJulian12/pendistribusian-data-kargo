<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function eksekutif()
    {
        $regions = [
            'barat'  => ['koneksi' => 'pgsql_barat', 'label' => 'Region Barat'],
            'tengah' => ['koneksi' => 'pgsql_tengah', 'label' => 'Region Tengah'],
            'timur'  => ['koneksi' => 'pgsql_timur', 'label' => 'Region Timur'],
        ];

        $volumePerRegion = [];
        $statusGlobal = [
            'Diproses' => 0,
            'Terkirim' => 0,
            'Diterima' => 0,
        ];
        $totalBeratPerRegion = [];
        $totalKargoNasional = 0;

        foreach ($regions as $key => $region) {
            $rows = DB::connection($region['koneksi'])->table('kargo')->get();

            $volumePerRegion[$region['label']] = $rows->count();
            $totalBeratPerRegion[$region['label']] = round($rows->sum('berat'), 2);
            $totalKargoNasional += $rows->count();

            foreach ($rows as $row) {
                if (isset($statusGlobal[$row->status])) {
                    $statusGlobal[$row->status]++;
                } else {
                    $statusGlobal[$row->status] = 1;
                }
            }
        }

        return view('dashboard_eksekutif', [
            'volumePerRegion' => $volumePerRegion,
            'statusGlobal' => $statusGlobal,
            'totalBeratPerRegion' => $totalBeratPerRegion,
            'totalKargoNasional' => $totalKargoNasional,
        ]);
    }

    public function adminPusat()
    {
        $tabelMaster = ['gudang', 'tarif_pengiriman', 'kode_pos'];

        $regions = [
            'barat'  => ['koneksi' => 'pgsql_barat', 'label' => 'Region Barat'],
            'tengah' => ['koneksi' => 'pgsql_tengah', 'label' => 'Region Tengah'],
            'timur'  => ['koneksi' => 'pgsql_timur', 'label' => 'Region Timur'],
        ];

        // Ambil jumlah baris data master dari Peladen Pusat (sumber kebenaran)
        $jumlahPusat = [];
        foreach ($tabelMaster as $tabel) {
            $jumlahPusat[$tabel] = DB::connection('pgsql_pusat')->table($tabel)->count();
        }

        // Bandingkan jumlah baris tiap region terhadap Pusat
        $statusSinkronisasi = [];
        foreach ($regions as $key => $region) {
            foreach ($tabelMaster as $tabel) {
                $jumlahRegion = DB::connection($region['koneksi'])->table($tabel)->count();
                $statusSinkronisasi[$region['label']][$tabel] = [
                    'jumlah' => $jumlahRegion,
                    'sinkron' => $jumlahRegion === $jumlahPusat[$tabel],
                ];
            }
        }

        // Ambil isi tabel gudang & tarif dari Pusat untuk ditampilkan
        $dataGudang = DB::connection('pgsql_pusat')->table('gudang')->get();
        $dataTarif = DB::connection('pgsql_pusat')->table('tarif_pengiriman')->get();

        return view('dashboard_admin', [
            'jumlahPusat' => $jumlahPusat,
            'statusSinkronisasi' => $statusSinkronisasi,
            'dataGudang' => $dataGudang,
            'dataTarif' => $dataTarif,
        ]);
    }

    public function kapasitasGudang()
    {
        $regions = [
            'barat'  => ['koneksi' => 'pgsql_barat', 'label' => 'Region Barat'],
            'tengah' => ['koneksi' => 'pgsql_tengah', 'label' => 'Region Tengah'],
            'timur'  => ['koneksi' => 'pgsql_timur', 'label' => 'Region Timur'],
        ];

        $dataKapasitas = [];

        foreach ($regions as $key => $region) {
            // Ambil gudang yang berlokasi di region ini
            $gudangList = DB::connection($region['koneksi'])
                ->table('gudang')
                ->where('wilayah', $region['label'])
                ->get();

            // Hitung beban aktif: kargo yang belum selesai (status Diproses) di region ini
            $bebanAktif = DB::connection($region['koneksi'])
                ->table('kargo')
                ->where('status', 'Diproses')
                ->count();

            foreach ($gudangList as $gudang) {
                $persentase = $gudang->kapasitas > 0
                    ? round(($bebanAktif / $gudang->kapasitas) * 100, 1)
                    : 0;

                $dataKapasitas[] = [
                    'region' => $region['label'],
                    'nama_gudang' => $gudang->nama_gudang,
                    'alamat' => $gudang->alamat,
                    'kapasitas' => $gudang->kapasitas,
                    'beban_aktif' => $bebanAktif,
                    'persentase' => min($persentase, 100),
                    'status' => $persentase >= 90 ? 'kritis' : ($persentase >= 60 ? 'waspada' : 'aman'),
                ];
            }
        }

        return view('kapasitas_gudang', ['dataKapasitas' => $dataKapasitas]);
    }
}