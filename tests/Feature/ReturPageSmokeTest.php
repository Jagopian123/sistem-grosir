<?php

declare(strict_types=1);

use App\Actions\Purchasing\CreatePurchaseReturnAction;
use App\Actions\Purchasing\ReceiveStockAction;
use App\Actions\Sales\CreateSaleAction;
use App\Actions\Sales\CreateSalesReturnAction;
use App\Enums\PaymentMethod;
use App\Enums\Role;
use App\Filament\Admin\Resources\ReturPembelianResource\Pages\ListReturPembelians;
use App\Filament\Admin\Resources\ReturPembelianResource\Pages\ViewReturPembelian;
use App\Filament\Admin\Resources\ReturPenjualanResource\Pages\ListReturPenjualans;
use App\Filament\Admin\Resources\ReturPenjualanResource\Pages\ViewReturPenjualan;
use App\Models\Pelanggan;
use App\Models\Produk;
use App\Models\SatuanProduk;
use App\Models\Supplier;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $owner = User::factory()->create();
    $owner->assignRole(Role::Owner->value);
    actingAs($owner);
});

test('halaman daftar & detail retur penjualan dirender tanpa error', function () {
    $produk = Produk::factory()->create(['stok' => 100]);
    $satuan = SatuanProduk::factory()->create(['produk_id' => $produk->id, 'konversi' => 1, 'harga_jual' => 5_000]);
    $penjualan = app(CreateSaleAction::class)->execute(
        pelanggan: Pelanggan::factory()->create(),
        items: [['satuan_id' => $satuan->id, 'qty' => 5]],
        metode: PaymentMethod::Tunai,
    );
    $retur = app(CreateSalesReturnAction::class)->execute(
        penjualan: $penjualan,
        items: [['satuan_id' => $satuan->id, 'qty' => 2]],
        tanggal: Carbon::now(),
    );

    Livewire::test(ListReturPenjualans::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$retur]);

    Livewire::test(ViewReturPenjualan::class, ['record' => $retur->getRouteKey()])
        ->assertSuccessful();
});

test('halaman daftar & detail retur pembelian dirender tanpa error', function () {
    $produk = Produk::factory()->create(['stok' => 50]);
    $pembelian = app(ReceiveStockAction::class)->execute(
        supplier: Supplier::factory()->create(),
        items: [['produk_id' => $produk->id, 'qty' => 100, 'harga_beli' => 2_800]],
        tanggal: Carbon::now(),
    );
    $retur = app(CreatePurchaseReturnAction::class)->execute(
        pembelian: $pembelian,
        items: [['produk_id' => $produk->id, 'qty' => 30]],
        tanggal: Carbon::now(),
    );

    Livewire::test(ListReturPembelians::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$retur]);

    Livewire::test(ViewReturPembelian::class, ['record' => $retur->getRouteKey()])
        ->assertSuccessful();
});
