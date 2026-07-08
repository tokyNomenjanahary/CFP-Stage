<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class FormationFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'titre' => fake()->word(),
            'description' => fake()->text(),
            'formateur_id' => User::factory(),
            'formateur_id_id' => User::factory(),
        ];
    }
}
