<?php

declare(strict_types=1);

use App\Actions\Sales\CreateSaleAction;
use App\Actions\Sales\CreateSalesReturnAction;
use App\Enums\PaymentMethod;
use App\Enums\StockMovementType;
use App\Models\MutasiStok;
use App\Models\Pelanggan;
use App\Models\Penjualan;
use App\Models\Produk;
use App\Models\ReturPenjualan;
use App\Models\SatuanProduk;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Helper: buat produk + satuan + jual sejumlah dus, kembalikan [produk, satuan, penjualan].
 *
 * @return array{0: Produk, 1: SatuanProduk, 2: Penjualan}
 */
function setupPenjualan(int $stokAwal = 100, int $jualDus = 2): array
{
    $produk = Produk::factory()->create(['stok' => $stokAwal, 'harga_beli' => 2_800]);
    $satuan = SatuanProduk::factory()->create([
        'produk_id' => $produk->id,
        'nama_satuan' => 'dus',
        'konversi' => 24,
        'harga_jual' => 70_000,
    ]);
    $pelanggan = Pelanggan::factory()->create();

    $penjualan = app(CreateSaleAction::class)->execute(
        pelanggan: $pelanggan,
        items: [['satuan_id' => $satuan->id, 'qty' => $jualDus]],
        metode: PaymentMethod::Tunai,
    );

    return [$produk, $satuan, $penjualan];
}

test('retur penjualan 1 dus menambah 24 pcs kembali dan menulis mutasi retur_masuk', function () {
    [$produk, $satuan, $penjualan] = setupPenjualan(stokAwal: 100, jualDus: 2);

    // Setelah jual 2 dus: stok 100 - 48 = 52
    expect($produk->fresh()->stok)->toBe(52);

    $retur = app(CreateSalesReturnAction::class)->execute(
        penjualan: $penjualan,
        items: [['satuan_id' => $satuan->id, 'qty' => 1]],
        tanggal: Carbon::now(),
    );

    // Stok bertambah 24 (1 dus) → 76
    expect($produk->fresh()->stok)->toBe(76);

    // Mutasi retur_masuk tertulis dengan benar
    $mutasi = MutasiStok::where('referensi', "retur_penjualan:{$retur->id}")->first();
    expect($mutasi)->not->toBeNull()
        ->and($mutasi->tipe)->toBe(StockMovementType::ReturMasuk)
        ->and($mutasi->qty)->toBe(24)
        ->and($mutasi->stok_sebelum)->toBe(52)
        ->and($mutasi->stok_sesudah)->toBe(76);

    // Detail & total retur (harga di-snapshot dari penjualan asli)
    expect($retur->details)->toHaveCount(1);
    $detail = $retur->details->first();
    expect($detail->qty)->toBe(1)
        ->and($detail->harga_satuan)->toBe('70000.00')
        ->and($detail->subtotal)->toBe('70000.00');
    expect($retur->total)->toBe('70000.00');
    expect($retur->no_retur)->toStartWith('RTJ-'.now()->format('Ymd'));
});

test('retur penjualan melebihi qty terjual ditolak dan tidak mengubah stok', function () {
    [$produk, $satuan, $penjualan] = setupPenjualan(stokAwal: 100, jualDus: 2);

    expect(fn () => app(CreateSalesReturnAction::class)->execute(
        penjualan: $penjualan,
        items: [['satuan_id' => $satuan->id, 'qty' => 3]], // terjual hanya 2
        tanggal: Carbon::now(),
    ))->toThrow(RuntimeException::class);

    // Rollback: stok tetap 52, tidak ada retur & mutasi retur tersimpan
    expect($produk->fresh()->stok)->toBe(52);
    expect(ReturPenjualan::count())->toBe(0);
    expect(MutasiStok::where('tipe', StockMovementType::ReturMasuk->value)->count())->toBe(0);
});

test('akumulasi retur tidak boleh melebihi qty terjual', function () {
    [, $satuan, $penjualan] = setupPenjualan(stokAwal: 100, jualDus: 2);

    // Retur pertama 1 dus → sisa 1
    app(CreateSalesReturnAction::class)->execute(
        penjualan: $penjualan,
        items: [['satuan_id' => $satuan->id, 'qty' => 1]],
        tanggal: Carbon::now(),
    );

    // Retur kedua 2 dus → melebihi sisa (1) → ditolak
    expect(fn () => app(CreateSalesReturnAction::class)->execute(
        penjualan: $penjualan,
        items: [['satuan_id' => $satuan->id, 'qty' => 2]],
        tanggal: Carbon::now(),
    ))->toThrow(RuntimeException::class);

    expect(ReturPenjualan::count())->toBe(1);
});

test('retur penjualan rollback saat satuan tidak ditemukan', function () {
    [$produk, , $penjualan] = setupPenjualan(stokAwal: 100, jualDus: 2);

    expect(fn () => app(CreateSalesReturnAction::class)->execute(
        penjualan: $penjualan,
        items: [['satuan_id' => 99999, 'qty' => 1]],
        tanggal: Carbon::now(),
    ))->toThrow(ModelNotFoundException::class);

    expect($produk->fresh()->stok)->toBe(52);
    expect(ReturPenjualan::count())->toBe(0);
});
