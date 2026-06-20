<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Sopir;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sopir>
 */
class SopirFactory extends Factory
{
    protected $model = Sopir::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'nama' => $this->faker->name('male'),
            'telepon' => '08'.$this->faker->numerify('##########'),
        ];
    }
}
