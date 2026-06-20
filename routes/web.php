<?php

use App\Http\Controllers\SuratJalanController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/surat-jalan/{penjualan}', SuratJalanController::class)
    ->middleware(['auth'])
    ->name('surat-jalan');
