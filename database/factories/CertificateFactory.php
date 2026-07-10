<?php

namespace Database\Factories;

use App\Models\Certificate;
use App\Models\Registration;
use Illuminate\Database\Eloquent\Factories\Factory;

class CertificateFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'uuid' => fake()->uuid(),
            'registration_id' => Registration::factory(),
            'issued_at' => fake()->dateTime(),
        ];
    }
}
