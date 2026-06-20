<?php

declare(strict_types=1);

use App\Actions\Stock\StockOpnameAction;
use App\Enums\StockMovementType;
use App\Models\MutasiStok;
use App\Models\Produk;
use App\Models\StockOpname;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('opname dengan stok fisik lebih besar menambah stok dan menulis penyesuaian', function () {
    $produk = Produk::factory()->create(['stok' => 100]);

    $opname = app(StockOpnameAction::class)->execute(
        items: [['produk_id' => $produk->id, 'stok_fisik' => 110]],
        tanggal: Carbon::now(),
    );

    expect($produk->fresh()->stok)->toBe(110);

    $mutasi = MutasiStok::where('referensi', "stock_opname:{$opname->id}")->first();
    expect($mutasi)->not->toBeNull()
        ->and($mutasi->tipe)->toBe(StockMovementType::Penyesuaian)
        ->and($mutasi->qty)->toBe(10)
        ->and($mutasi->stok_sebelum)->toBe(100)
        ->and($mutasi->stok_sesudah)->toBe(110);

    $detail = $opname->details->first();
    expect($detail->stok_sistem)->toBe(100)
        ->and($detail->stok_fisik)->toBe(110)
        ->and($detail->selisih)->toBe(10);
    expect($opname->total_selisih)->toBe(10);
    expect($opname->no_opname)->toStartWith('OPN-'.now()->format('Ymd'));
});

test('opname dengan stok fisik lebih kecil mengurangi stok (selisih negatif)', function () {
    $produk = Produk::factory()->create(['stok' => 100]);

    $opname = app(StockOpnameAction::class)->execute(
        items: [['produk_id' => $produk->id, 'stok_fisik' => 85]],
        tanggal: Carbon::now(),
    );

    expect($produk->fresh()->stok)->toBe(85);

    $mutasi = MutasiStok::where('referensi', "stock_opname:{$opname->id}")->first();
    expect($mutasi->tipe)->toBe(StockMovementType::Penyesuaian)
        ->and($mutasi->qty)->toBe(15)
        ->and($mutasi->stok_sebelum)->toBe(100)
        ->and($mutasi->stok_sesudah)->toBe(85);

    expect($opname->details->first()->selisih)->toBe(-15);
    expect($opname->total_selisih)->toBe(-15);
});

test('opname tanpa selisih tidak menulis mutasi tapi tetap mencatat detail', function () {
    $produk = Produk::factory()->create(['stok' => 100]);

    $opname = app(StockOpnameAction::class)->execute(
        items: [['produk_id' => $produk->id, 'stok_fisik' => 100]],
        tanggal: Carbon::now(),
    );

    expect($produk->fresh()->stok)->toBe(100);
    expect(MutasiStok::where('referensi', "stock_opname:{$opname->id}")->count())->toBe(0);
    expect($opname->details)->toHaveCount(1);
    expect($opname->details->first()->selisih)->toBe(0);
    expect($opname->total_selisih)->toBe(0);
});

test('opname multi-produk menghitung total selisih bersih', function () {
    $a = Produk::factory()->create(['stok' => 100]);
    $b = Produk::factory()->create(['stok' => 50]);

    $opname = app(StockOpnameAction::class)->execute(
        items: [
            ['produk_id' => $a->id, 'stok_fisik' => 108], // +8
            ['produk_id' => $b->id, 'stok_fisik' => 45],  // -5
        ],
        tanggal: Carbon::now(),
    );

    expect($a->fresh()->stok)->toBe(108);
    expect($b->fresh()->stok)->toBe(45);
    expect($opname->total_selisih)->toBe(3);
    expect(MutasiStok::where('referensi', "stock_opname:{$opname->id}")->count())->toBe(2);
});

test('opname rollback saat produk tidak ditemukan', function () {
    $produk = Produk::factory()->create(['stok' => 100]);

    expect(fn () => app(StockOpnameAction::class)->execute(
        items: [
            ['produk_id' => $produk->id, 'stok_fisik' => 120],
            ['produk_id' => 99999, 'stok_fisik' => 10],
        ],
        tanggal: Carbon::now(),
    ))->toThrow(ModelNotFoundException::class);

    expect($produk->fresh()->stok)->toBe(100);
    expect(StockOpname::count())->toBe(0);
    expect(MutasiStok::count())->toBe(0);
});

test('opname menolak stok fisik negatif dan rollback', function () {
    $produk = Produk::factory()->create(['stok' => 100]);

    expect(fn () => app(StockOpnameAction::class)->execute(
        items: [['produk_id' => $produk->id, 'stok_fisik' => -5]],
        tanggal: Carbon::now(),
    ))->toThrow(RuntimeException::class);

    expect($produk->fresh()->stok)->toBe(100);
    expect(StockOpname::count())->toBe(0);
});

test('opname menolak produk duplikat dalam satu sesi', function () {
    $produk = Produk::factory()->create(['stok' => 100]);

    expect(fn () => app(StockOpnameAction::class)->execute(
        items: [
            ['produk_id' => $produk->id, 'stok_fisik' => 110],
            ['produk_id' => $produk->id, 'stok_fisik' => 120],
        ],
        tanggal: Carbon::now(),
    ))->toThrow(RuntimeException::class);

    expect($produk->fresh()->stok)->toBe(100);
    expect(StockOpname::count())->toBe(0);
});
