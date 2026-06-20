<?php

declare(strict_types=1);

use App\Actions\Purchasing\ReceiveStockAction;
use App\Enums\StockMovementType;
use App\Models\MutasiStok;
use App\Models\Pembelian;
use App\Models\Produk;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('stok masuk menambah stok produk dan menulis mutasi', function () {
    $produk = Produk::factory()->create(['stok' => 50, 'harga_beli' => 2_500]);
    $supplier = Supplier::factory()->create();

    $pembelian = app(ReceiveStockAction::class)->execute(
        supplier: $supplier,
        items: [
            ['produk_id' => $produk->id, 'qty' => 100, 'harga_beli' => 2_800],
        ],
        tanggal: Carbon::now(),
    );

    // Stok bertambah
    expect($produk->fresh()->stok)->toBe(150);

    // HPP diupdate ke harga beli terbaru
    expect($produk->fresh()->harga_beli)->toBe('2800.00');

    // Tepat 1 mutasi ditulis
    $mutasi = MutasiStok::where('produk_id', $produk->id)->first();
    expect($mutasi)->not->toBeNull();
    expect($mutasi->tipe)->toBe(StockMovementType::Masuk);
    expect($mutasi->qty)->toBe(100);
    expect($mutasi->stok_sebelum)->toBe(50);
    expect($mutasi->stok_sesudah)->toBe(150);

    // Referensi menunjuk ke pembelian
    expect($mutasi->referensi)->toBe("pembelian:{$pembelian->id}");

    // Total pembelian benar
    expect($pembelian->total)->toBe('280000.00');
});

test('transaksi pembelian rollback saat produk tidak ditemukan', function () {
    $supplier = Supplier::factory()->create();
    $produk = Produk::factory()->create(['stok' => 50]);

    expect(fn () => app(ReceiveStockAction::class)->execute(
        supplier: $supplier,
        items: [
            ['produk_id' => 99999, 'qty' => 10, 'harga_beli' => 1_000],
        ],
        tanggal: Carbon::now(),
    ))->toThrow(ModelNotFoundException::class);

    // Stok tidak berubah
    expect($produk->fresh()->stok)->toBe(50);

    // Tidak ada pembelian dan mutasi yang tersimpan
    expect(Pembelian::count())->toBe(0);
    expect(MutasiStok::count())->toBe(0);
});

test('pembelian multi-produk menulis mutasi untuk setiap produk', function () {
    $produkA = Produk::factory()->create(['stok' => 10]);
    $produkB = Produk::factory()->create(['stok' => 20]);
    $supplier = Supplier::factory()->create();

    app(ReceiveStockAction::class)->execute(
        supplier: $supplier,
        items: [
            ['produk_id' => $produkA->id, 'qty' => 50, 'harga_beli' => 1_000],
            ['produk_id' => $produkB->id, 'qty' => 30, 'harga_beli' => 2_000],
        ],
        tanggal: Carbon::now(),
    );

    expect($produkA->fresh()->stok)->toBe(60);
    expect($produkB->fresh()->stok)->toBe(50);
    expect(MutasiStok::count())->toBe(2);
});
