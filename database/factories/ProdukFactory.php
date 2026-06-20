<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Kategori;
use App\Models\Produk;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Produk>
 */
class ProdukFactory extends Factory
{
    protected $model = Produk::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'kategori_id' => Kategori::factory(),
            'nama' => $this->faker->words(3, true),
            'satuan_dasar' => 'pcs',
            'stok' => $this->faker->numberBetween(0, 500),
            'stok_min' => $this->faker->numberBetween(5, 30),
            'harga_beli' => $this->faker->numberBetween(1_000, 50_000),
            'aktif' => true,
        ];
    }

    public function nonaktif(): static
    {
        return $this->state(['aktif' => false]);
    }

    public function stokMenipis(): static
    {
        return $this->state(fn (array $attributes) => [
            'stok' => $attributes['stok_min'] - 1,
        ]);
    }
}
