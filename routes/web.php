<?php

use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\SuratJalanController;
use App\Http\Controllers\UnduhanController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/unduhan/surat-jalan/{file}', [UnduhanController::class, 'suratJalan'])
    ->middleware(['auth', 'signed'])
    ->name('unduhan.surat-jalan');

Route::get('/unduhan/laporan/{file}', [UnduhanController::class, 'laporan'])
    ->middleware(['auth', 'signed'])
    ->name('unduhan.laporan');

Route::get('/surat-jalan/{penjualan}', SuratJalanController::class)
    ->middleware(['auth'])
    ->name('surat-jalan');

Route::get('/invoice/{penjualan}/{format?}', InvoiceController::class)
    ->whereIn('format', ['a4', 'thermal58', 'thermal80'])
    ->middleware(['auth'])
    ->name('invoice');
