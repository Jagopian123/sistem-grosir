<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Produk;
use App\Models\SatuanProduk;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SatuanProduk>
 */
class SatuanProdukFactory extends Factory
{
    protected $model = SatuanProduk::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'produk_id' => Produk::factory(),
            'nama_satuan' => 'pcs',
            'konversi' => 1,
            'harga_jual' => $this->faker->numberBetween(1_000, 60_000),
        ];
    }
}
