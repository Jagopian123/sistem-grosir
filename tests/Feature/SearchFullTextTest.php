<?php

declare(strict_types=1);

use App\Models\Kategori;
use App\Models\Pelanggan;
use App\Models\Produk;
use App\Models\Supplier;
use App\Support\FullTextSearch;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('booleanTerm membungkus tiap kata jadi prefix wajib', function () {
    expect(FullTextSearch::booleanTerm('beras'))->toBe('+beras*')
        ->and(FullTextSearch::booleanTerm('beras putih'))->toBe('+beras* +putih*')
        // karakter operator boolean dibuang supaya tidak jadi sintaks/injeksi
        ->and(FullTextSearch::booleanTerm('coca-cola'))->toBe('+coca* +cola*')
        ->and(FullTextSearch::booleanTerm('  +(jagung)*  '))->toBe('+jagung*');
});

test('qualifies hanya untuk term yang semua katanya cukup panjang', function () {
    expect(FullTextSearch::qualifies('beras'))->toBeTrue()
        ->and(FullTextSearch::qualifies('ber'))->toBeTrue()
        ->and(FullTextSearch::qualifies('be'))->toBeFalse()   // < MIN_TOKEN_SIZE → fallback LIKE
        ->and(FullTextSearch::qualifies('beras ab'))->toBeFalse()
        ->and(FullTextSearch::qualifies('   '))->toBeFalse();
});

test('scope whereFullTextSearch menemukan produk lewat kata sebagian', function () {
    $kategori = Kategori::factory()->create();
    $beras = Produk::factory()->for($kategori)->create(['nama' => 'Beras Pandan Wangi']);
    Produk::factory()->for($kategori)->create(['nama' => 'Gula Pasir Premium']);

    $hasil = Produk::query()->whereFullTextSearch('beras')->get();

    expect($hasil)->toHaveCount(1)
        ->and($hasil->first()->is($beras))->toBeTrue();
});

test('scope whereFullTextSearch tanpa term tidak memfilter apa pun', function () {
    Produk::factory()->count(3)->create();

    expect(Produk::query()->whereFullTextSearch('')->count())->toBe(3)
        ->and(Produk::query()->whereFullTextSearch(null)->count())->toBe(3);
});

test('scope whereFullTextSearch bekerja untuk pelanggan dan supplier', function () {
    Pelanggan::factory()->create(['nama_toko' => 'Toko Makmur Jaya']);
    Pelanggan::factory()->create(['nama_toko' => 'Warung Sederhana']);
    Supplier::factory()->create(['nama' => 'CV Sumber Rejeki']);
    Supplier::factory()->create(['nama' => 'PT Aneka Pangan']);

    expect(Pelanggan::query()->whereFullTextSearch('makmur')->count())->toBe(1)
        ->and(Supplier::query()->whereFullTextSearch('rejeki')->count())->toBe(1);
});
