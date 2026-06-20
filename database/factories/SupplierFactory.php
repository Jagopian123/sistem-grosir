<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Supplier>
 */
class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $prefix = $this->faker->randomElement(['CV', 'PT', 'UD', 'Toko']);

        return [
            'nama' => "{$prefix} ".$this->faker->company(),
            'telepon' => '08'.$this->faker->numerify('##########'),
            'alamat' => $this->faker->address(),
        ];
    }
}
