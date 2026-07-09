<?php

namespace Database\Factories;

use App\Models\Role;
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
            'titre' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'formateur_id' => User::factory()->formateur(),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (\App\Models\Formation $formation) {
            $role = Role::firstOrCreate(['name' => 'formateur']);
            $formation->formateur->roles()->syncWithoutDetaching($role);
        });
    }
}
