<?php

declare(strict_types=1);

use App\Actions\Purchasing\CreatePurchaseReturnAction;
use App\Actions\Purchasing\ReceiveStockAction;
use App\Actions\Sales\CreateSaleAction;
use App\Enums\ExpiryStatus;
use App\Enums\PaymentMethod;
use App\Models\BatchStok;
use App\Models\Pelanggan;
use App\Models\Pembelian;
use App\Models\Produk;
use App\Models\SatuanProduk;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/** Stok masuk untuk satu produk berbatch, mengembalikan produk-nya. */
function terimaStokBerbatch(Produk $produk, int $qty, string $ed, float $harga = 3_000): Pembelian
{
    return app(ReceiveStockAction::class)->execute(
        supplier: Supplier::factory()->create(),
        items: [[
            'produk_id' => $produk->id,
            'qty' => $qty,
            'harga_beli' => $harga,
            'tanggal_kadaluarsa' => $ed,
            'kode_batch' => 'B-'.$ed,
        ]],
        tanggal: Carbon::now(),
    );
}

test('stok masuk produk berbatch membuat batch dengan ED dan qty_sisa penuh', function () {
    $produk = Produk::factory()->lacakKadaluarsa()->create(['stok' => 0]);

    terimaStokBerbatch($produk, 100, '2026-08-01');

    expect($produk->fresh()->stok)->toBe(100);

    $batch = BatchStok::where('produk_id', $produk->id)->sole();
    expect($batch->qty_masuk)->toBe(100);
    expect($batch->qty_sisa)->toBe(100);
    expect($batch->kode_batch)->toBe('B-2026-08-01');
    expect($batch->tanggal_kadaluarsa->toDateString())->toBe('2026-08-01');
    expect($batch->sumber)->toStartWith('pembelian:');
});

test('stok masuk produk non-batch tidak membuat batch', function () {
    $produk = Produk::factory()->create(['stok' => 0, 'lacak_kadaluarsa' => false]);

    app(ReceiveStockAction::class)->execute(
        supplier: Supplier::factory()->create(),
        items: [['produk_id' => $produk->id, 'qty' => 50, 'harga_beli' => 1_000]],
        tanggal: Carbon::now(),
    );

    expect($produk->fresh()->stok)->toBe(50);
    expect(BatchStok::count())->toBe(0);
});

test('produk berbatch tanpa tanggal kadaluarsa ditolak dan transaksi rollback', function () {
    $produk = Produk::factory()->lacakKadaluarsa()->create(['stok' => 10]);

    expect(fn () => app(ReceiveStockAction::class)->execute(
        supplier: Supplier::factory()->create(),
        items: [['produk_id' => $produk->id, 'qty' => 20, 'harga_beli' => 1_000]],
        tanggal: Carbon::now(),
    ))->toThrow(RuntimeException::class);

    expect($produk->fresh()->stok)->toBe(10);
    expect(Pembelian::count())->toBe(0);
    expect(BatchStok::count())->toBe(0);
});

test('penjualan mengkonsumsi batch FEFO: ED terdekat habis lebih dulu', function () {
    $produk = Produk::factory()->lacakKadaluarsa()->create(['stok' => 0]);

    // Batch ED jauh masuk lebih dulu, batch ED dekat masuk belakangan.
    terimaStokBerbatch($produk, 100, '2026-12-01');
    terimaStokBerbatch($produk, 50, '2026-08-01');

    $satuan = SatuanProduk::factory()->create([
        'produk_id' => $produk->id,
        'nama_satuan' => 'pcs',
        'konversi' => 1,
        'harga_jual' => 5_000,
    ]);

    // Jual 60 → harus menghabiskan batch ED 2026-08 (50) lalu ambil 10 dari ED 2026-12.
    app(CreateSaleAction::class)->execute(
        pelanggan: Pelanggan::factory()->create(),
        items: [['satuan_id' => $satuan->id, 'qty' => 60]],
        metode: PaymentMethod::Tunai,
    );

    $edDekat = BatchStok::where('kode_batch', 'B-2026-08-01')->sole();
    $edJauh = BatchStok::where('kode_batch', 'B-2026-12-01')->sole();

    expect($edDekat->qty_sisa)->toBe(0);
    expect($edJauh->qty_sisa)->toBe(90);

    // Sisa batch selalu konsisten dengan stok produk.
    expect((int) BatchStok::where('produk_id', $produk->id)->sum('qty_sisa'))
        ->toBe($produk->fresh()->stok);
});

test('retur pembelian mengkonsumsi batch FEFO', function () {
    $produk = Produk::factory()->lacakKadaluarsa()->create(['stok' => 0]);

    terimaStokBerbatch($produk, 30, '2026-09-01');
    $pembelian = terimaStokBerbatch($produk, 40, '2026-07-01');

    // Retur 25 dari pembelian batch ED 2026-07 → konsumsi FEFO (ED 2026-07 dulu).
    app(CreatePurchaseReturnAction::class)->execute(
        pembelian: $pembelian,
        items: [['produk_id' => $produk->id, 'qty' => 25]],
        tanggal: Carbon::now(),
    );

    $edDekat = BatchStok::where('kode_batch', 'B-2026-07-01')->sole();
    $edJauh = BatchStok::where('kode_batch', 'B-2026-09-01')->sole();

    expect($edDekat->qty_sisa)->toBe(15);
    expect($edJauh->qty_sisa)->toBe(30);
    expect((int) BatchStok::where('produk_id', $produk->id)->sum('qty_sisa'))
        ->toBe($produk->fresh()->stok);
});

test('status batch: aman, mendekati, dan kadaluarsa', function () {
    $produk = Produk::factory()->lacakKadaluarsa()->create(['stok' => 0]);

    $aman = BatchStok::factory()->for($produk)->create([
        'tanggal_kadaluarsa' => now()->addDays(120),
    ]);
    $mendekati = BatchStok::factory()->for($produk)->create([
        'tanggal_kadaluarsa' => now()->addDays(10),
    ]);
    $lewat = BatchStok::factory()->for($produk)->create([
        'tanggal_kadaluarsa' => now()->subDay(),
    ]);

    expect($aman->status())->toBe(ExpiryStatus::Aman);
    expect($mendekati->status())->toBe(ExpiryStatus::Mendekati);
    expect($lewat->status())->toBe(ExpiryStatus::Kadaluarsa);
});

test('scope perluPerhatian hanya batch bersisa yang mendekati atau lewat ED', function () {
    $produk = Produk::factory()->lacakKadaluarsa()->create(['stok' => 0]);

    BatchStok::factory()->for($produk)->create(['tanggal_kadaluarsa' => now()->addDays(120), 'qty_sisa' => 10]); // aman
    BatchStok::factory()->for($produk)->create(['tanggal_kadaluarsa' => now()->addDays(5), 'qty_sisa' => 10]);   // mendekati
    BatchStok::factory()->for($produk)->create(['tanggal_kadaluarsa' => now()->subDay(), 'qty_sisa' => 10]);     // kadaluarsa
    BatchStok::factory()->for($produk)->create(['tanggal_kadaluarsa' => now()->addDay(), 'qty_sisa' => 0]);      // mendekati tapi habis
    BatchStok::factory()->for($produk)->create(['tanggal_kadaluarsa' => null, 'qty_sisa' => 10]);                // tanpa ED

    expect(BatchStok::query()->perluPerhatian()->count())->toBe(2);
});
