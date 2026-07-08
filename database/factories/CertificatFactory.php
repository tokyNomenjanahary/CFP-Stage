<?php

namespace Database\Factories;

use App\Models\Inscription;
use Illuminate\Database\Eloquent\Factories\Factory;

class CertificatFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'uuid' => fake()->uuid(),
            'inscription_id' => Inscription::factory(),
            'date_emission' => fake()->dateTime(),
        ];
    }
}
