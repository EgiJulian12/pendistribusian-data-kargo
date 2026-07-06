<?php

namespace App\Services;

class RegionResolver
{
    /**
     * Peta konfigurasi region: kode prefiks, nama koneksi database, dan label tampilan.
     * Satu-satunya sumber kebenaran (single source of truth) untuk aturan routing region.
     */
    private static array $peta = [
        'BRT' => ['nama_koneksi' => 'pgsql_barat', 'label' => 'Region Barat'],
        'TGH' => ['nama_koneksi' => 'pgsql_tengah', 'label' => 'Region Tengah'],
        'TMR' => ['nama_koneksi' => 'pgsql_timur', 'label' => 'Region Timur'],
    ];

    /**
     * Menentukan region berdasarkan prefiks nomor resi.
     * Mengembalikan null apabila format resi tidak dikenali.
     */
    public static function dariResi(string $resi): ?array
    {
        foreach (self::$peta as $kode => $info) {
            if (str_contains($resi, $kode)) {
                return $info;
            }
        }
        return null;
    }

    /**
     * Menentukan region berdasarkan key sederhana (barat/tengah/timur),
     * dipakai pada form yang menyediakan pilihan region secara eksplisit.
     */
    public static function dariKey(string $key): ?array
    {
        $mapping = [
            'barat'  => ['nama_koneksi' => 'pgsql_barat', 'label' => 'Region Barat', 'kode' => 'BRT'],
            'tengah' => ['nama_koneksi' => 'pgsql_tengah', 'label' => 'Region Tengah', 'kode' => 'TGH'],
            'timur'  => ['nama_koneksi' => 'pgsql_timur', 'label' => 'Region Timur', 'kode' => 'TMR'],
        ];

        return $mapping[$key] ?? null;
    }

    /**
     * Mengembalikan seluruh daftar region, dipakai untuk keperluan looping
     * (misalnya agregasi data di Dashboard Eksekutif/Admin).
     */
    public static function semua(): array
    {
        return [
            'barat'  => ['koneksi' => 'pgsql_barat', 'label' => 'Region Barat'],
            'tengah' => ['koneksi' => 'pgsql_tengah', 'label' => 'Region Tengah'],
            'timur'  => ['koneksi' => 'pgsql_timur', 'label' => 'Region Timur'],
        ];
    }
}