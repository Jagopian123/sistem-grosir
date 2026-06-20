<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Filament\Admin\Resources\BatchStokResource\Pages\EditBatchStok;
use App\Filament\Admin\Resources\BatchStokResource\Pages\ListBatchStoks;
use App\Filament\Admin\Widgets\BatchKadaluarsaWidget;
use App\Models\BatchStok;
use App\Models\Produk;
use App\Models\User;
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

test('halaman daftar batch & widget kadaluarsa dirender tanpa error', function () {
    $produk = Produk::factory()->lacakKadaluarsa()->create();
    $batch = BatchStok::factory()->for($produk)->create([
        'tanggal_kadaluarsa' => now()->addDays(5),
    ]);

    Livewire::test(ListBatchStoks::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$batch]);

    Livewire::test(BatchKadaluarsaWidget::class)
        ->assertSuccessful();
});

test('koreksi ED batch lewat halaman edit tersimpan', function () {
    $batch = BatchStok::factory()->create([
        'tanggal_kadaluarsa' => now()->addDays(5),
        'kode_batch' => 'LAMA',
    ]);

    Livewire::test(EditBatchStok::class, ['record' => $batch->getRouteKey()])
        ->fillForm([
            'tanggal_kadaluarsa' => now()->addDays(90)->toDateString(),
            'kode_batch' => 'BARU',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($batch->fresh()->kode_batch)->toBe('BARU');
});
