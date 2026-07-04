<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

use App\Http\Controllers\TrackingController;

Route::get('/tracking', [TrackingController::class, 'index'])->name('tracking.index');
Route::post('/tracking', [TrackingController::class, 'search'])->name('tracking.search');
Route::get('/pindah-kargo', [TrackingController::class, 'pindahForm'])->name('pindah.form');
Route::post('/pindah-kargo', [TrackingController::class, 'pindahProses'])->name('pindah.proses');