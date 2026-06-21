<?php

declare(strict_types=1);

use App\Actions\Sales\CreateSaleAction;
use App\Enums\PaymentMethod;
use App\Models\Pelanggan;
use App\Models\Penjualan;
use App\Models\Produk;
use App\Models\SatuanProduk;
use App\Support\RingkasanPenjualanCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

function buatPenjualan(int $qty = 2, float $harga = 10_000): Penjualan
{
    $produk = Produk::factory()->create(['stok' => 100]);
    $satuan = SatuanProduk::factory()->create([
        'produk_id' => $produk->id,
        'konversi' => 1,
        'harga_jual' => $harga,
    ]);

    return app(CreateSaleAction::class)->execute(
        pelanggan: Pelanggan::factory()->create(),
        items: [['satuan_id' => $satuan->id, 'qty' => $qty]],
        metode: PaymentMethod::Tunai,
    );
}

test('ringkasan penjualan disimpan ke cache saat diambil', function () {
    $cacheKey = 'ringkasan_penjualan:'.now()->toDateString();

    expect(Cache::tags(RingkasanPenjualanCache::TAG)->has($cacheKey))->toBeFalse();

    $ringkasan = app(RingkasanPenjualanCache::class)->ambil();

    expect($ringkasan['transaksi_hari'])->toBe(0)
        ->and($ringkasan['omzet_hari'])->toBe(0.0)
        ->and(Cache::tags(RingkasanPenjualanCache::TAG)->has($cacheKey))->toBeTrue();
});

test('cache laporan otomatis di-invalidasi saat ada penjualan baru', function () {
    $cache = app(RingkasanPenjualanCache::class);

    // Prime cache saat masih kosong.
    expect($cache->ambil()['transaksi_hari'])->toBe(0);

    // Transaksi baru → PenjualanObserver harus mem-flush cache laporan.
    buatPenjualan(qty: 2, harga: 10_000);

    $ringkasan = $cache->ambil();

    expect($ringkasan['transaksi_hari'])->toBe(1)
        ->and($ringkasan['omzet_hari'])->toBe(20_000.0);
});

test('lupakan() membuang cache laporan sehingga dihitung ulang', function () {
    $cache = app(RingkasanPenjualanCache::class);
    $cacheKey = 'ringkasan_penjualan:'.now()->toDateString();

    $cache->ambil();
    expect(Cache::tags(RingkasanPenjualanCache::TAG)->has($cacheKey))->toBeTrue();

    RingkasanPenjualanCache::lupakan();

    expect(Cache::tags(RingkasanPenjualanCache::TAG)->has($cacheKey))->toBeFalse();
});

test('omzet bulan ini terakumulasi dari beberapa transaksi', function () {
    buatPenjualan(qty: 1, harga: 15_000);
    buatPenjualan(qty: 1, harga: 25_000);

    $ringkasan = app(RingkasanPenjualanCache::class)->ambil();

    expect($ringkasan['transaksi_bulan'])->toBe(2)
        ->and($ringkasan['omzet_bulan'])->toBe(40_000.0);
});
