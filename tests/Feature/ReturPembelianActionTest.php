<?php

declare(strict_types=1);

use App\Actions\Purchasing\CreatePurchaseReturnAction;
use App\Actions\Purchasing\ReceiveStockAction;
use App\Enums\StockMovementType;
use App\Models\MutasiStok;
use App\Models\Pembelian;
use App\Models\Produk;
use App\Models\ReturPembelian;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Helper: buat produk + terima stok dari supplier, kembalikan [produk, pembelian].
 *
 * @return array{0: Produk, 1: Pembelian}
 */
function setupPembelian(int $stokAwal = 50, int $beli = 100, float $harga = 2_800): array
{
    $produk = Produk::factory()->create(['stok' => $stokAwal, 'harga_beli' => 2_500]);
    $supplier = Supplier::factory()->create();

    $pembelian = app(ReceiveStockAction::class)->execute(
        supplier: $supplier,
        items: [['produk_id' => $produk->id, 'qty' => $beli, 'harga_beli' => $harga]],
        tanggal: Carbon::now(),
    );

    return [$produk, $pembelian];
}

test('retur pembelian mengurangi stok dan menulis mutasi retur_keluar', function () {
    [$produk, $pembelian] = setupPembelian(stokAwal: 50, beli: 100, harga: 2_800);

    // Setelah beli 100: stok 150
    expect($produk->fresh()->stok)->toBe(150);

    $retur = app(CreatePurchaseReturnAction::class)->execute(
        pembelian: $pembelian,
        items: [['produk_id' => $produk->id, 'qty' => 30]],
        tanggal: Carbon::now(),
    );

    // Stok berkurang 30 → 120
    expect($produk->fresh()->stok)->toBe(120);

    $mutasi = MutasiStok::where('referensi', "retur_pembelian:{$retur->id}")->first();
    expect($mutasi)->not->toBeNull()
        ->and($mutasi->tipe)->toBe(StockMovementType::ReturKeluar)
        ->and($mutasi->qty)->toBe(30)
        ->and($mutasi->stok_sebelum)->toBe(150)
        ->and($mutasi->stok_sesudah)->toBe(120);

    // Detail & total (harga di-snapshot dari pembelian asli)
    expect($retur->details)->toHaveCount(1);
    $detail = $retur->details->first();
    expect($detail->qty)->toBe(30)
        ->and($detail->harga_beli)->toBe('2800.00')
        ->and($detail->subtotal)->toBe('84000.00');
    expect($retur->total)->toBe('84000.00');
    expect($retur->no_retur)->toStartWith('RTB-'.now()->format('Ymd'));
});

test('retur pembelian melebihi qty dibeli ditolak', function () {
    [$produk, $pembelian] = setupPembelian(stokAwal: 50, beli: 100, harga: 2_800);

    expect(fn () => app(CreatePurchaseReturnAction::class)->execute(
        pembelian: $pembelian,
        items: [['produk_id' => $produk->id, 'qty' => 101]], // dibeli hanya 100
        tanggal: Carbon::now(),
    ))->toThrow(RuntimeException::class);

    expect($produk->fresh()->stok)->toBe(150);
    expect(ReturPembelian::count())->toBe(0);
});

test('retur pembelian ditolak bila melebihi stok fisik saat ini', function () {
    // Beli 100 (stok jadi 110), lalu kurangi stok manual jadi 20 (seolah sudah terjual).
    [$produk, $pembelian] = setupPembelian(stokAwal: 10, beli: 100, harga: 2_800);
    $produk->update(['stok' => 20]);

    // Sisa yang bisa diretur menurut pembelian = 100, tapi stok fisik hanya 20.
    expect(fn () => app(CreatePurchaseReturnAction::class)->execute(
        pembelian: $pembelian,
        items: [['produk_id' => $produk->id, 'qty' => 50]],
        tanggal: Carbon::now(),
    ))->toThrow(RuntimeException::class);

    expect($produk->fresh()->stok)->toBe(20);
    expect(ReturPembelian::count())->toBe(0);
});

test('retur pembelian rollback saat produk tidak ditemukan', function () {
    [$produk, $pembelian] = setupPembelian(stokAwal: 50, beli: 100, harga: 2_800);

    expect(fn () => app(CreatePurchaseReturnAction::class)->execute(
        pembelian: $pembelian,
        items: [['produk_id' => 99999, 'qty' => 5]],
        tanggal: Carbon::now(),
    ))->toThrow(ModelNotFoundException::class);

    expect($produk->fresh()->stok)->toBe(150);
    expect(ReturPembelian::count())->toBe(0);
});
