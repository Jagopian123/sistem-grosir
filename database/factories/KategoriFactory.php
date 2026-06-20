<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Kategori;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Kategori>
 */
class KategoriFactory extends Factory
{
    protected $model = Kategori::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        static $names = [
            'Makanan', 'Minuman', 'Sembako', 'Bumbu Dapur',
            'Kebutuhan RT', 'Sabun & Detergen', 'Rokok', 'Snack',
            'Minyak Goreng', 'Bahan Bakar',
        ];

        return [
            'nama' => $this->faker->unique()->randomElement($names),
        ];
    }
}
