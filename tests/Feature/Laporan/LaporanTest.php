<?php

declare(strict_types=1);

use App\Models\DetailPenjualan;
use App\Models\Pelanggan;
use App\Models\Penjualan;
use App\Models\Produk;
use App\Models\SatuanProduk;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ─── Dashboard Stats ──────────────────────────────────────────────────────────

test('omzet hari ini hanya menghitung penjualan hari ini', function () {
    Penjualan::factory()->create(['total' => 100_000, 'tanggal' => now()]);
    Penjualan::factory()->create(['total' => 200_000, 'tanggal' => now()->subDay()]);

    $omzetHariIni = (float) Penjualan::whereDate('tanggal', today())->sum('total');

    expect($omzetHariIni)->toBe(100_000.0);
});

test('omzet bulan ini menjumlahkan semua penjualan dalam bulan yang sama', function () {
    Penjualan::factory()->create(['total' => 300_000, 'tanggal' => now()]);
    Penjualan::factory()->create(['total' => 150_000, 'tanggal' => now()->startOfMonth()]);
    Penjualan::factory()->create(['total' => 999_999, 'tanggal' => now()->subMonth()]);

    $omzetBulanIni = (float) Penjualan::whereMonth('tanggal', now()->month)
        ->whereYear('tanggal', now()->year)
        ->sum('total');

    expect($omzetBulanIni)->toBe(450_000.0);
});

test('jumlah transaksi hari ini terhitung benar', function () {
    Penjualan::factory()->count(3)->create(['tanggal' => now()]);
    Penjualan::factory()->count(2)->create(['tanggal' => now()->subDay()]);

    $count = Penjualan::whereDate('tanggal', today())->count();

    expect($count)->toBe(3);
});

// ─── Laporan Penjualan — filter tanggal ───────────────────────────────────────

test('filter rentang tanggal laporan penjualan hanya mengembalikan data dalam rentang', function () {
    Penjualan::factory()->create(['tanggal' => '2025-01-10 10:00:00']);
    Penjualan::factory()->create(['tanggal' => '2025-01-15 10:00:00']);
    Penjualan::factory()->create(['tanggal' => '2025-01-20 10:00:00']);

    $hasil = Penjualan::query()
        ->whereDate('tanggal', '>=', '2025-01-12')
        ->whereDate('tanggal', '<=', '2025-01-18')
        ->get();

    expect($hasil)->toHaveCount(1)
        ->and($hasil->first()->tanggal->format('Y-m-d'))->toBe('2025-01-15');
});

// ─── Laporan Produk Terlaris ──────────────────────────────────────────────────

test('laporan produk terlaris mengagregasi qty dan omzet per produk', function () {
    $produkA = Produk::factory()->create(['nama' => 'Produk A', 'harga_beli' => 5_000]);
    $produkB = Produk::factory()->create(['nama' => 'Produk B', 'harga_beli' => 8_000]);
    $satuanA = SatuanProduk::factory()->create(['produk_id' => $produkA->id, 'konversi' => 1, 'harga_jual' => 10_000]);
    $satuanB = SatuanProduk::factory()->create(['produk_id' => $produkB->id, 'konversi' => 1, 'harga_jual' => 15_000]);

    $penjualan = Penjualan::factory()->create(['total' => 50_000]);

    DetailPenjualan::create([
        'penjualan_id' => $penjualan->id,
        'produk_id' => $produkA->id,
        'satuan_id' => $satuanA->id,
        'qty' => 3,
        'harga_satuan' => 10_000,
        'subtotal' => 30_000,
    ]);
    DetailPenjualan::create([
        'penjualan_id' => $penjualan->id,
        'produk_id' => $produkB->id,
        'satuan_id' => $satuanB->id,
        'qty' => 1,
        'harga_satuan' => 15_000,
        'subtotal' => 15_000,
    ]);

    $hasil = Produk::query()
        ->join('detail_penjualans', 'detail_penjualans.produk_id', '=', 'produks.id')
        ->join('penjualans', 'penjualans.id', '=', 'detail_penjualans.penjualan_id')
        ->selectRaw('
            produks.id,
            produks.nama as produk_nama,
            SUM(detail_penjualans.qty) as total_qty,
            SUM(detail_penjualans.subtotal) as total_omzet
        ')
        ->groupBy('produks.id', 'produks.nama')
        ->orderByDesc('total_omzet')
        ->toBase()
        ->get();

    $first = $hasil->first();
    expect($hasil)->toHaveCount(2)
        ->and((string) $first->produk_nama)->toBe('Produk A')
        ->and((int) $first->total_qty)->toBe(3)
        ->and((float) $first->total_omzet)->toBe(30_000.0);
});

// ─── Laporan Stok ─────────────────────────────────────────────────────────────

test('laporan stok menandai produk sebagai menipis ketika stok di bawah minimum', function () {
    Produk::factory()->create(['stok' => 50, 'stok_min' => 10, 'aktif' => true]);
    $menipis = Produk::factory()->create(['stok' => 5, 'stok_min' => 20, 'aktif' => true]);

    $hasil = Produk::query()
        ->lowStock()
        ->active()
        ->get();

    expect($hasil)->toHaveCount(1)
        ->and($hasil->first()->id)->toBe($menipis->id);
});

// ─── Laporan Laba Kotor ───────────────────────────────────────────────────────

test('laba kotor dihitung sebagai omzet dikurangi HPP dari qty x konversi x harga_beli', function () {
    $produk = Produk::factory()->create(['harga_beli' => 5_000]);
    // 1 dus = 12 pcs
    $satuanDus = SatuanProduk::factory()->create([
        'produk_id' => $produk->id,
        'nama_satuan' => 'dus',
        'konversi' => 12,
        'harga_jual' => 70_000,
    ]);

    $pelanggan = Pelanggan::factory()->create();
    // Jual 2 dus → omzet 140.000, HPP = 2 * 12 * 5.000 = 120.000, laba = 20.000
    $penjualan = Penjualan::factory()->create([
        'pelanggan_id' => $pelanggan->id,
        'total' => 140_000,
    ]);
    DetailPenjualan::create([
        'penjualan_id' => $penjualan->id,
        'produk_id' => $produk->id,
        'satuan_id' => $satuanDus->id,
        'qty' => 2,
        'harga_satuan' => 70_000,
        'subtotal' => 140_000,
    ]);

    $hasil = Penjualan::query()
        ->join('detail_penjualans', 'detail_penjualans.penjualan_id', '=', 'penjualans.id')
        ->join('satuan_produks', 'satuan_produks.id', '=', 'detail_penjualans.satuan_id')
        ->join('produks', 'produks.id', '=', 'detail_penjualans.produk_id')
        ->join('pelanggans', 'pelanggans.id', '=', 'penjualans.pelanggan_id')
        ->selectRaw('
            penjualans.id,
            penjualans.total as omzet,
            SUM(detail_penjualans.qty * satuan_produks.konversi * produks.harga_beli) as hpp,
            penjualans.total - SUM(detail_penjualans.qty * satuan_produks.konversi * produks.harga_beli) as laba_kotor
        ')
        ->groupBy('penjualans.id', 'penjualans.total')
        ->where('penjualans.id', $penjualan->id)
        ->toBase()
        ->first();

    expect((float) $hasil->omzet)->toBe(140_000.0)
        ->and((float) $hasil->hpp)->toBe(120_000.0)  // 2 dus × 12 pcs × Rp5.000
        ->and((float) $hasil->laba_kotor)->toBe(20_000.0);
});

test('laba kotor dengan multiple produk dihitung per invoice dengan benar', function () {
    $produkA = Produk::factory()->create(['harga_beli' => 10_000]);
    $produkB = Produk::factory()->create(['harga_beli' => 3_000]);
    $satuanA = SatuanProduk::factory()->create(['produk_id' => $produkA->id, 'konversi' => 1, 'harga_jual' => 15_000]);
    $satuanB = SatuanProduk::factory()->create(['produk_id' => $produkB->id, 'konversi' => 24, 'harga_jual' => 80_000]);

    $pelanggan = Pelanggan::factory()->create();
    // Omzet: 5 × 15.000 + 2 × 80.000 = 75.000 + 160.000 = 235.000
    // HPP:   5 × 1 × 10.000 + 2 × 24 × 3.000 = 50.000 + 144.000 = 194.000
    // Laba:  235.000 - 194.000 = 41.000
    $penjualan = Penjualan::factory()->create(['pelanggan_id' => $pelanggan->id, 'total' => 235_000]);
    DetailPenjualan::create([
        'penjualan_id' => $penjualan->id,
        'produk_id' => $produkA->id,
        'satuan_id' => $satuanA->id,
        'qty' => 5,
        'harga_satuan' => 15_000,
        'subtotal' => 75_000,
    ]);
    DetailPenjualan::create([
        'penjualan_id' => $penjualan->id,
        'produk_id' => $produkB->id,
        'satuan_id' => $satuanB->id,
        'qty' => 2,
        'harga_satuan' => 80_000,
        'subtotal' => 160_000,
    ]);

    $hasil = Penjualan::query()
        ->join('detail_penjualans', 'detail_penjualans.penjualan_id', '=', 'penjualans.id')
        ->join('satuan_produks', 'satuan_produks.id', '=', 'detail_penjualans.satuan_id')
        ->join('produks', 'produks.id', '=', 'detail_penjualans.produk_id')
        ->join('pelanggans', 'pelanggans.id', '=', 'penjualans.pelanggan_id')
        ->selectRaw('
            penjualans.id,
            penjualans.total as omzet,
            SUM(detail_penjualans.qty * satuan_produks.konversi * produks.harga_beli) as hpp,
            penjualans.total - SUM(detail_penjualans.qty * satuan_produks.konversi * produks.harga_beli) as laba_kotor
        ')
        ->groupBy('penjualans.id', 'penjualans.total')
        ->where('penjualans.id', $penjualan->id)
        ->toBase()
        ->first();

    expect((float) $hasil->omzet)->toBe(235_000.0)
        ->and((float) $hasil->hpp)->toBe(194_000.0)
        ->and((float) $hasil->laba_kotor)->toBe(41_000.0);
});
