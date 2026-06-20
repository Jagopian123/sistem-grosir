<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Filament\Admin\Resources\PengirimanResource;
use App\Models\Produk;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function userWithRole(string $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

test('Owner (super admin) bisa akses semua: laba, user, log aktivitas, bahkan izin yang tak ditetapkan ke peran mana pun', function () {
    $owner = userWithRole(Role::Owner->value);

    expect($owner->can('View:LaporanLabaKotor'))->toBeTrue()
        ->and($owner->can('ViewAny:User'))->toBeTrue()
        ->and($owner->can('ViewAny:Activity'))->toBeTrue()
        ->and($owner->can('ViewAny:Role'))->toBeTrue()
        // Gate::before super admin: lolos meski izin ini tidak di-assign ke peran apa pun
        ->and($owner->can('ForceDeleteAny:Penjualan'))->toBeTrue();
});

test('Admin kelola master & transaksi, tetapi tidak laba kotor, user, maupun log', function () {
    $admin = userWithRole(Role::Admin->value);

    expect($admin->can('Create:Penjualan'))->toBeTrue()
        ->and($admin->can('Create:Pembelian'))->toBeTrue()
        ->and($admin->can('Update:Produk'))->toBeTrue()
        ->and($admin->can('View:LaporanPenjualan'))->toBeTrue()
        ->and($admin->can('KelolaPengiriman'))->toBeTrue()
        // Yang TIDAK boleh:
        ->and($admin->can('View:LaporanLabaKotor'))->toBeFalse()
        ->and($admin->can('ViewAny:User'))->toBeFalse()
        ->and($admin->can('ViewAny:Activity'))->toBeFalse()
        ->and($admin->can('ViewAny:Role'))->toBeFalse();
});

test('Kasir hanya buat/lihat penjualan + master read-only, tanpa pembelian/kelola kirim/laporan', function () {
    $kasir = userWithRole(Role::Kasir->value);

    expect($kasir->can('Create:Penjualan'))->toBeTrue()
        ->and($kasir->can('View:Produk'))->toBeTrue()
        ->and($kasir->can('View:Pelanggan'))->toBeTrue()
        ->and($kasir->can('LihatPengiriman'))->toBeTrue()
        // Yang TIDAK boleh:
        ->and($kasir->can('Update:Produk'))->toBeFalse()
        ->and($kasir->can('ViewAny:Pembelian'))->toBeFalse()
        ->and($kasir->can('KelolaPengiriman'))->toBeFalse()
        ->and($kasir->can('View:LaporanPenjualan'))->toBeFalse();
});

test('Gudang kelola produk/pembelian & pengiriman, tidak bisa menjual atau lihat laba', function () {
    $gudang = userWithRole(Role::Gudang->value);

    expect($gudang->can('Create:Pembelian'))->toBeTrue()
        ->and($gudang->can('Update:Produk'))->toBeTrue()
        ->and($gudang->can('View:MutasiStok'))->toBeTrue()
        ->and($gudang->can('KelolaPengiriman'))->toBeTrue()
        ->and($gudang->can('View:LaporanStok'))->toBeTrue()
        // Yang TIDAK boleh:
        ->and($gudang->can('Create:Penjualan'))->toBeFalse()
        ->and($gudang->can('View:LaporanLabaKotor'))->toBeFalse()
        ->and($gudang->can('ViewAny:User'))->toBeFalse();
});

test('Sopir hanya antrian pengiriman & update status, tak ada akses lain', function () {
    $sopir = userWithRole(Role::Sopir->value);

    expect($sopir->can('LihatPengiriman'))->toBeTrue()
        ->and($sopir->can('KelolaPengiriman'))->toBeTrue()
        // Yang TIDAK boleh:
        ->and($sopir->can('ViewAny:Penjualan'))->toBeFalse()
        ->and($sopir->can('ViewAny:Produk'))->toBeFalse()
        ->and($sopir->can('ViewAny:Pembelian'))->toBeFalse();
});

test('PengirimanResource hanya bisa diakses peran yang punya izin LihatPengiriman', function () {
    actingAs(userWithRole(Role::Sopir->value));
    expect(PengirimanResource::canAccess())->toBeTrue();

    actingAs(userWithRole(Role::Gudang->value));
    expect(PengirimanResource::canAccess())->toBeTrue();

    actingAs(userWithRole(Role::Kasir->value));
    expect(PengirimanResource::canAccess())->toBeTrue();

    // Pengguna tanpa peran tidak boleh
    actingAs(User::factory()->create());
    expect(PengirimanResource::canAccess())->toBeFalse();
});

test('Pengguna tanpa peran tidak bisa masuk panel admin', function () {
    $user = User::factory()->create();

    expect($user->canAccessPanel(Filament\Facades\Filament::getPanel('admin')))->toBeFalse();

    $user->assignRole(Role::Kasir->value);
    expect($user->fresh()->canAccessPanel(Filament\Facades\Filament::getPanel('admin')))->toBeTrue();
});

test('perubahan harga produk tercatat di activity log', function () {
    $produk = Produk::factory()->create(['harga_beli' => 1000]);

    $produk->update(['harga_beli' => 9999]);

    $activity = Activity::query()
        ->where('subject_type', Produk::class)
        ->where('subject_id', $produk->id)
        ->where('event', 'updated')
        ->latest('id')
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->attribute_changes['attributes']['harga_beli'])->toBe('9999.00')
        ->and($activity->attribute_changes['old']['harga_beli'])->toBe('1000.00');
});

test('password pengguna tidak ikut tercatat di activity log', function () {
    $user = User::factory()->create();

    $user->update(['name' => 'Nama Baru', 'password' => bcrypt('rahasia-baru')]);

    $activity = Activity::query()
        ->where('subject_type', User::class)
        ->where('subject_id', $user->id)
        ->where('event', 'updated')
        ->latest('id')
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->attribute_changes['attributes'])->toHaveKey('name')
        ->and($activity->attribute_changes['attributes'])->not->toHaveKey('password');
});
