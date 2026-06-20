<?php

declare(strict_types=1);

use App\Actions\Sales\CreateSaleAction;
use App\Enums\PaymentMethod;
use App\Models\HargaTingkat;
use App\Models\MutasiStok;
use App\Models\Pelanggan;
use App\Models\Produk;
use App\Models\SatuanProduk;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * @param  array<int, int>  $tiers  min_qty => harga
 */
function buatSatuanBertingkat(int $hargaDasar, array $tiers, int $konversi = 1, int $stok = 1000): SatuanProduk
{
    $produk = Produk::factory()->create(['stok' => $stok, 'harga_beli' => 1_000]);

    $satuan = SatuanProduk::factory()->create([
        'produk_id' => $produk->id,
        'nama_satuan' => 'dus',
        'konversi' => $konversi,
        'harga_jual' => $hargaDasar,
    ]);

    foreach ($tiers as $minQty => $harga) {
        HargaTingkat::create([
            'satuan_id' => $satuan->id,
            'min_qty' => $minQty,
            'harga' => $harga,
        ]);
    }

    return $satuan->load('hargaTingkat');
}

test('qty di bawah tingkat manapun memakai harga dasar', function () {
    $satuan = buatSatuanBertingkat(130_000, [5 => 128_000, 10 => 125_000]);

    expect($satuan->hargaUntukQty(1))->toBe(130_000.0)
        ->and($satuan->hargaUntukQty(4))->toBe(130_000.0);
});

test('qty memilih tingkat tertinggi yang terpenuhi', function () {
    $satuan = buatSatuanBertingkat(130_000, [5 => 128_000, 10 => 125_000]);

    expect($satuan->hargaUntukQty(5))->toBe(128_000.0)
        ->and($satuan->hargaUntukQty(9))->toBe(128_000.0)
        ->and($satuan->hargaUntukQty(10))->toBe(125_000.0)
        ->and($satuan->hargaUntukQty(50))->toBe(125_000.0);
});

test('satuan tanpa tingkat selalu memakai harga dasar', function () {
    $satuan = buatSatuanBertingkat(70_000, []);

    expect($satuan->hargaUntukQty(1))->toBe(70_000.0)
        ->and($satuan->hargaUntukQty(100))->toBe(70_000.0);
});

test('penjualan memakai harga bertingkat dan men-snapshot ke detail', function () {
    $satuan = buatSatuanBertingkat(130_000, [5 => 128_000, 10 => 125_000], konversi: 40, stok: 1000);
    $pelanggan = Pelanggan::factory()->create();

    // Beli 10 dus → harga tingkat 125.000/dus
    $penjualan = app(CreateSaleAction::class)->execute(
        pelanggan: $pelanggan,
        items: [['satuan_id' => $satuan->id, 'qty' => 10]],
        metode: PaymentMethod::Tunai,
    );

    $detail = $penjualan->details->first();
    expect($detail->harga_satuan)->toBe('125000.00')
        ->and($detail->subtotal)->toBe('1250000.00')
        ->and($penjualan->total)->toBe('1250000.00');

    // Stok tetap konsisten: 10 dus × 40 = 400 keluar, 1 baris mutasi
    expect($satuan->produk->fresh()->stok)->toBe(600)
        ->and(MutasiStok::where('produk_id', $satuan->produk_id)->count())->toBe(1)
        ->and(MutasiStok::where('produk_id', $satuan->produk_id)->first()->qty)->toBe(400);
});

test('harga lama tidak berubah meski tingkat harga diubah setelah transaksi', function () {
    $satuan = buatSatuanBertingkat(130_000, [10 => 125_000], konversi: 1, stok: 100);
    $pelanggan = Pelanggan::factory()->create();

    $penjualan = app(CreateSaleAction::class)->execute(
        pelanggan: $pelanggan,
        items: [['satuan_id' => $satuan->id, 'qty' => 10]],
        metode: PaymentMethod::Tunai,
    );

    // Ubah tingkat harga setelah transaksi
    $satuan->hargaTingkat->first()->update(['harga' => 100_000]);

    expect($penjualan->details->first()->fresh()->harga_satuan)->toBe('125000.00');
});
