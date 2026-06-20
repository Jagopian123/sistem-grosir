<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Role as RoleEnum;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Membuat lima peran (Owner, Admin, Kasir, Gudang, Sopir) dan menetapkan izin
 * sesuai tanggung jawab masing-masing (dokumentasi bagian 1.2).
 *
 * Owner adalah super admin (lihat config filament-shield) sehingga otomatis
 * melewati semua pemeriksaan izin; ia tetap disinkronkan dengan seluruh izin
 * agar tampil lengkap di UI manajemen peran.
 */
class RolePermissionSeeder extends Seeder
{
    /** Subset metode izin yang relevan (tanpa soft delete / reorder). */
    private const CRUD = ['ViewAny', 'View', 'Create', 'Update', 'Delete', 'DeleteAny'];

    private const READ = ['ViewAny', 'View'];

    private const CREATE_VIEW = ['ViewAny', 'View', 'Create'];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Pastikan permission untuk semua entity Filament sudah dibuat.
        Artisan::call('shield:generate', [
            '--all' => true,
            '--panel' => 'admin',
            '--option' => 'permissions',
            '--no-interaction' => true,
        ]);

        foreach (RoleEnum::cases() as $role) {
            Role::findOrCreate($role->value, 'web');
        }

        $this->syncRole(RoleEnum::Owner, Permission::pluck('name')->all());
        $this->syncRole(RoleEnum::Admin, $this->adminPermissions());
        $this->syncRole(RoleEnum::Kasir, $this->kasirPermissions());
        $this->syncRole(RoleEnum::Gudang, $this->gudangPermissions());
        $this->syncRole(RoleEnum::Sopir, $this->sopirPermissions());

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * @return list<string>
     */
    private function adminPermissions(): array
    {
        return [
            ...$this->forModels(self::CRUD, ['Kategori', 'Produk', 'Pelanggan', 'Supplier', 'Sopir', 'Pembelian', 'Penjualan']),
            ...$this->forModels(self::CREATE_VIEW, ['ReturPenjualan', 'ReturPembelian', 'StockOpname']),
            ...$this->forModels(self::READ, ['MutasiStok']),
            'LihatPengiriman',
            'KelolaPengiriman',
            'View:LaporanPenjualan',
            'View:LaporanProdukTerlaris',
            'View:LaporanStok',
            'View:RingkasanWidget',
            'View:StokMenipisWidget',
        ];
    }

    /**
     * @return list<string>
     */
    private function kasirPermissions(): array
    {
        return [
            ...$this->forModels(self::CREATE_VIEW, ['Penjualan', 'ReturPenjualan']),
            ...$this->forModels(self::READ, ['Produk', 'Pelanggan']),
            'LihatPengiriman',
            'View:RingkasanWidget',
        ];
    }

    /**
     * @return list<string>
     */
    private function gudangPermissions(): array
    {
        return [
            ...$this->forModels(self::CRUD, ['Produk', 'Pembelian']),
            ...$this->forModels(self::CREATE_VIEW, ['ReturPembelian', 'StockOpname']),
            ...$this->forModels(self::READ, ['Supplier', 'Kategori', 'MutasiStok']),
            'LihatPengiriman',
            'KelolaPengiriman',
            'View:LaporanStok',
            'View:StokMenipisWidget',
        ];
    }

    /**
     * @return list<string>
     */
    private function sopirPermissions(): array
    {
        return [
            'LihatPengiriman',
            'KelolaPengiriman',
        ];
    }

    /**
     * Bangun nama izin "Prefix:Model" untuk tiap kombinasi.
     *
     * @param  list<string>  $prefixes
     * @param  list<string>  $models
     * @return list<string>
     */
    private function forModels(array $prefixes, array $models): array
    {
        $permissions = [];

        foreach ($models as $model) {
            foreach ($prefixes as $prefix) {
                $permissions[] = "{$prefix}:{$model}";
            }
        }

        return $permissions;
    }

    /**
     * Sinkronkan izin ke peran; hanya izin yang benar-benar ada yang ditetapkan.
     *
     * @param  list<string>  $permissions
     */
    private function syncRole(RoleEnum $role, array $permissions): void
    {
        $existing = Permission::whereIn('name', $permissions)->pluck('name')->all();

        Role::findByName($role->value, 'web')->syncPermissions($existing);
    }
}
