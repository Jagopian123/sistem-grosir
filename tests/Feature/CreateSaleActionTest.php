<?php

declare(strict_types=1);

use App\Actions\Sales\CreateSaleAction;
use App\Enums\DeliveryStatus;
use App\Enums\PaymentMethod;
use App\Models\MutasiStok;
use App\Models\Pelanggan;
use App\Models\Produk;
use App\Models\SatuanProduk;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('jual 2 dus (1 dus = 24 pcs) mengurangi 48 pcs dan menulis 1 baris mutasi', function () {
    $produk = Produk::factory()->create([
        'stok' => 100,
        'stok_min' => 10,
        'harga_beli' => 2_800,
    ]);

    $satuan = SatuanProduk::factory()->create([
        'produk_id' => $produk->id,
        'nama_satuan' => 'dus',
        'konversi' => 24,
        'harga_jual' => 70_000,
    ]);

    $pelanggan = Pelanggan::factory()->create();

    $penjualan = app(CreateSaleAction::class)->execute(
        pelanggan: $pelanggan,
        items: [['satuan_id' => $satuan->id, 'qty' => 2]],
        metode: PaymentMethod::Tunai,
    );

    // Stok harus berkurang 48 pcs (2 dus × 24 konversi)
    expect($produk->fresh()->stok)->toBe(52);

    // Tepat 1 baris mutasi ditulis
    expect(MutasiStok::where('produk_id', $produk->id)->count())->toBe(1);

    // Verifikasi isi mutasi
    $mutasi = MutasiStok::where('produk_id', $produk->id)->first();
    expect($mutasi->qty)->toBe(48);
    expect($mutasi->stok_sebelum)->toBe(100);
    expect($mutasi->stok_sesudah)->toBe(52);

    // Verifikasi detail penjualan
    expect($penjualan->details)->toHaveCount(1);
    $detail = $penjualan->details->first();
    expect($detail->qty)->toBe(2);
    expect($detail->harga_satuan)->toBe('70000.00');
    expect($detail->subtotal)->toBe('140000.00');

    // Verifikasi total penjualan
    expect($penjualan->total)->toBe('140000.00');

    // Verifikasi status awal
    expect($penjualan->status_kirim)->toBe(DeliveryStatus::SiapKirim);
    expect($penjualan->metode_bayar)->toBe(PaymentMethod::Tunai);
});

test('transaksi penjualan rollback saat satuan tidak ditemukan', function () {
    $produk = Produk::factory()->create(['stok' => 100]);
    $pelanggan = Pelanggan::factory()->create();

    expect(fn () => app(CreateSaleAction::class)->execute(
        pelanggan: $pelanggan,
        items: [['satuan_id' => 99999, 'qty' => 2]],
        metode: PaymentMethod::Tunai,
    ))->toThrow(ModelNotFoundException::class);

    // Stok tidak berubah
    expect($produk->fresh()->stok)->toBe(100);

    // Tidak ada mutasi yang tersimpan
    expect(MutasiStok::count())->toBe(0);
});

test('no_invoice dibuat otomatis dengan format INV-YYYYMMDD-XXXX', function () {
    $produk = Produk::factory()->create(['stok' => 50]);
    $satuan = SatuanProduk::factory()->create([
        'produk_id' => $produk->id,
        'konversi' => 1,
        'harga_jual' => 5_000,
    ]);
    $pelanggan = Pelanggan::factory()->create();

    $penjualan = app(CreateSaleAction::class)->execute(
        pelanggan: $pelanggan,
        items: [['satuan_id' => $satuan->id, 'qty' => 1]],
        metode: PaymentMethod::Transfer,
    );

    expect($penjualan->no_invoice)->toStartWith('INV-'.now()->format('Ymd'));
});
