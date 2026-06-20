<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\BatchStok;
use App\Models\Produk;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BatchStok>
 */
class BatchStokFactory extends Factory
{
    protected $model = BatchStok::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $qty = $this->faker->numberBetween(10, 200);

        return [
            'produk_id' => Produk::factory()->lacakKadaluarsa(),
            'kode_batch' => 'B-'.$this->faker->bothify('####'),
            'tanggal_kadaluarsa' => $this->faker->dateTimeBetween('+1 month', '+1 year'),
            'qty_masuk' => $qty,
            'qty_sisa' => $qty,
            'sumber' => 'pembelian:'.$this->faker->numberBetween(1, 100),
            'tanggal_masuk' => now(),
        ];
    }
}
