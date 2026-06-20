<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\HargaTingkat;
use App\Models\SatuanProduk;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HargaTingkat>
 */
class HargaTingkatFactory extends Factory
{
    protected $model = HargaTingkat::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'satuan_id' => SatuanProduk::factory(),
            'min_qty' => $this->faker->numberBetween(5, 50),
            'harga' => $this->faker->numberBetween(1_000, 60_000),
        ];
    }
}
