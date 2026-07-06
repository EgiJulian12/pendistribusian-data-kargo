<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MasterDataController extends Controller
{
    public function index()
    {
        $dataTarif = DB::connection('pgsql_pusat')->table('tarif_pengiriman')->orderBy('wilayah_asal')->get();

        return view('kelola_tarif', ['dataTarif' => $dataTarif]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'tarif' => 'required|numeric|min:0',
        ]);

        DB::connection('pgsql_pusat')->table('tarif_pengiriman')
            ->where('id_tarif', $id)
            ->update(['tarif' => $request->tarif]);

        return back()->with('success', 'Tarif berhasil diperbarui di Peladen Pusat. Perubahan akan otomatis tersinkronisasi ke seluruh region dalam beberapa detik.');
    }
}