<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CargoController;
use App\Http\Controllers\MasterDataController;


Route::get('/', function () {
    return view('welcome');
});

use App\Http\Controllers\TrackingController;

Route::get('/tracking', [TrackingController::class, 'index'])->middleware('auth')->name('tracking.index');
Route::post('/tracking', [TrackingController::class, 'search'])->middleware('auth')->name('tracking.search');

Route::get('/pindah-kargo', [TrackingController::class, 'pindahForm'])->middleware('role:petugas')->name('pindah.form');
Route::post('/pindah-kargo', [TrackingController::class, 'pindahProses'])->middleware('role:petugas')->name('pindah.proses');

Route::get('/dashboard-eksekutif', [DashboardController::class, 'eksekutif'])->middleware('role:eksekutif')->name('dashboard.eksekutif');
Route::get('/dashboard-admin', [DashboardController::class, 'adminPusat'])->middleware('role:admin')->name('dashboard.admin');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.proses');

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/beranda', function () {return view('beranda');})->middleware('auth')->name('beranda');

Route::get('/tambah-kargo', [CargoController::class, 'tambahForm'])->middleware('role:petugas')->name('kargo.tambah.form');
Route::post('/tambah-kargo', [CargoController::class, 'tambahProses'])->middleware('role:petugas')->name('kargo.tambah.proses');

Route::get('/riwayat-kargo', [CargoController::class, 'riwayatForm'])->middleware('auth')->name('kargo.riwayat.form');
Route::post('/riwayat-kargo', [CargoController::class, 'riwayatProses'])->middleware('auth')->name('kargo.riwayat.proses');

Route::get('/kelola-tarif', [MasterDataController::class, 'index'])->middleware('role:admin')->name('master.tarif.index');
Route::post('/kelola-tarif/{id}/update', [MasterDataController::class, 'update'])->middleware('role:admin')->name('master.tarif.update');

Route::get('/kapasitas-gudang', [DashboardController::class, 'kapasitasGudang'])->middleware('role:petugas')->name('kapasitas.gudang');