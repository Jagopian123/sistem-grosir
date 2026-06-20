<?php

declare(strict_types=1);

use App\Actions\Stock\StockOpnameAction;
use App\Enums\Role;
use App\Filament\Admin\Resources\StockOpnameResource\Pages\ListStockOpnames;
use App\Filament\Admin\Resources\StockOpnameResource\Pages\ViewStockOpname;
use App\Models\Produk;
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

test('halaman daftar & detail stock opname dirender tanpa error', function () {
    $produk = Produk::factory()->create(['stok' => 100]);

    $opname = app(StockOpnameAction::class)->execute(
        items: [['produk_id' => $produk->id, 'stok_fisik' => 95]],
        tanggal: Carbon::now(),
    );

    Livewire::test(ListStockOpnames::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$opname]);

    Livewire::test(ViewStockOpname::class, ['record' => $opname->getRouteKey()])
        ->assertSuccessful();
});
