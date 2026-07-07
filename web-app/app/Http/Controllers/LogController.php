<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class LogController extends Controller
{
    public function transaksi()
    {
        $logs = DB::connection('pgsql_pusat')
            ->table('transaksi_log')
            ->orderBy('waktu', 'desc')
            ->limit(50)
            ->get();

        $totalSukses = DB::connection('pgsql_pusat')->table('transaksi_log')->where('status', 'SUKSES')->count();
        $totalGagal = DB::connection('pgsql_pusat')->table('transaksi_log')->where('status', 'GAGAL')->count();

        return view('log_transaksi', [
            'logs' => $logs,
            'totalSukses' => $totalSukses,
            'totalGagal' => $totalGagal,
        ]);
    }

    public function notifikasiTerbaru()
    {
        $items = Redis::lrange('notifikasi_gudang', 0, 9);
        $data = array_map(fn ($item) => json_decode($item, true), $items);

        return response()->json($data);
    }
}