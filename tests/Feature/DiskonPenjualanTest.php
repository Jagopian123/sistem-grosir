<?php

declare(strict_types=1);

use App\Actions\Sales\CreateSaleAction;
use App\Enums\DiscountType;
use App\Enums\PaymentMethod;
use App\Models\MutasiStok;
use App\Models\Pelanggan;
use App\Models\Produk;
use App\Models\SatuanProduk;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * @param  array<int, array{satuan_id: int, qty: int}>  $items
 */
function buatPenjualanDiskon(
    ?DiscountType $tipe,
    float $nilai,
    array $items,
    SatuanProduk $satuan,
) {
    return app(CreateSaleAction::class)->execute(
        pelanggan: Pelanggan::factory()->create(),
        items: $items,
        metode: PaymentMethod::Tunai,
        diskonTipe: $tipe,
        diskonNilai: $nilai,
    );
}

function satuanSederhana(int $harga = 10_000, int $konversi = 1, int $stok = 1000): SatuanProduk
{
    $produk = Produk::factory()->create(['stok' => $stok, 'harga_beli' => 5_000]);

    return SatuanProduk::factory()->create([
        'produk_id' => $produk->id,
        'nama_satuan' => 'pcs',
        'konversi' => $konversi,
        'harga_jual' => $harga,
    ]);
}

test('penjualan tanpa diskon menyimpan subtotal sama dengan total', function () {
    $satuan = satuanSederhana(10_000);

    $penjualan = buatPenjualanDiskon(null, 0, [['satuan_id' => $satuan->id, 'qty' => 5]], $satuan);

    expect($penjualan->subtotal)->toBe('50000.00')
        ->and($penjualan->diskon_tipe)->toBeNull()
        ->and($penjualan->diskon_nominal)->toBe('0.00')
        ->and($penjualan->total)->toBe('50000.00')
        ->and($penjualan->adaDiskon())->toBeFalse();
});

test('diskon nominal mengurangi total dan di-snapshot', function () {
    $satuan = satuanSederhana(10_000);

    $penjualan = buatPenjualanDiskon(DiscountType::Nominal, 15_000, [['satuan_id' => $satuan->id, 'qty' => 5]], $satuan);

    expect($penjualan->subtotal)->toBe('50000.00')
        ->and($penjualan->diskon_tipe)->toBe(DiscountType::Nominal)
        ->and($penjualan->diskon_nilai)->toBe('15000.00')
        ->and($penjualan->diskon_nominal)->toBe('15000.00')
        ->and($penjualan->total)->toBe('35000.00')
        ->and($penjualan->adaDiskon())->toBeTrue();
});

test('diskon persentase dihitung dari subtotal', function () {
    $satuan = satuanSederhana(10_000);

    // 10% dari 50.000 = 5.000
    $penjualan = buatPenjualanDiskon(DiscountType::Persen, 10, [['satuan_id' => $satuan->id, 'qty' => 5]], $satuan);

    expect($penjualan->subtotal)->toBe('50000.00')
        ->and($penjualan->diskon_nominal)->toBe('5000.00')
        ->and($penjualan->total)->toBe('45000.00');
});

test('diskon nominal melebihi subtotal di-clamp sehingga total tidak negatif', function () {
    $satuan = satuanSederhana(10_000);

    $penjualan = buatPenjualanDiskon(DiscountType::Nominal, 999_999, [['satuan_id' => $satuan->id, 'qty' => 5]], $satuan);

    expect($penjualan->diskon_nominal)->toBe('50000.00')
        ->and($penjualan->total)->toBe('0.00');
});

test('diskon persen melebihi 100 dibatasi 100 persen', function () {
    $satuan = satuanSederhana(10_000);

    $penjualan = buatPenjualanDiskon(DiscountType::Persen, 150, [['satuan_id' => $satuan->id, 'qty' => 5]], $satuan);

    expect($penjualan->diskon_nominal)->toBe('50000.00')
        ->and($penjualan->total)->toBe('0.00');
});

test('diskon dengan nilai nol dianggap tanpa diskon', function () {
    $satuan = satuanSederhana(10_000);

    $penjualan = buatPenjualanDiskon(DiscountType::Persen, 0, [['satuan_id' => $satuan->id, 'qty' => 5]], $satuan);

    expect($penjualan->diskon_tipe)->toBeNull()
        ->and($penjualan->diskon_nominal)->toBe('0.00')
        ->and($penjualan->total)->toBe('50000.00');
});

test('diskon nota tidak mengubah stok maupun ledger mutasi', function () {
    $satuan = satuanSederhana(10_000, konversi: 1, stok: 100);

    $penjualan = buatPenjualanDiskon(DiscountType::Persen, 20, [['satuan_id' => $satuan->id, 'qty' => 5]], $satuan);

    // Stok berkurang sesuai qty, bukan dipengaruhi diskon
    expect($satuan->produk->fresh()->stok)->toBe(95);

    // Tepat 1 baris mutasi senilai qty keluar (diskon murni soal uang)
    $mutasi = MutasiStok::where('produk_id', $satuan->produk_id)->get();
    expect($mutasi)->toHaveCount(1)
        ->and($mutasi->first()->qty)->toBe(5);

    // Subtotal item tetap penuh; hanya total nota yang dipotong
    expect($penjualan->details->first()->subtotal)->toBe('50000.00')
        ->and($penjualan->total)->toBe('40000.00');
});
