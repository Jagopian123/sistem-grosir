<?php

use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\SuratJalanController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/surat-jalan/{penjualan}', SuratJalanController::class)
    ->middleware(['auth'])
    ->name('surat-jalan');

Route::get('/invoice/{penjualan}/{format?}', InvoiceController::class)
    ->whereIn('format', ['a4', 'thermal58', 'thermal80'])
    ->middleware(['auth'])
    ->name('invoice');
