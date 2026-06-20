<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Pelanggan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pelanggan>
 */
class PelangganFactory extends Factory
{
    protected $model = Pelanggan::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'nama_toko' => 'Toko '.$this->faker->lastName(),
            'nama_kontak' => $this->faker->optional(0.8)->name(),
            'telepon' => '08'.$this->faker->numerify('##########'),
            'alamat' => $this->faker->address(),
        ];
    }
}
