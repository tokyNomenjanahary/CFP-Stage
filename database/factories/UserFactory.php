<?php

namespace Database\Factories;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'phone' => fake()->unique()->numerify('034#######'),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= 'password', // hashé auto via le cast 'hashed'
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Assign the student role after creation.
     */
    public function student(): static
    {
        return $this->afterCreating(function (User $user) {
            $role = Role::firstOrCreate(['name' => 'student']);
            $user->roles()->syncWithoutDetaching($role);
        });
    }

    /**
     * Assign the instructor role after creation.
     */
    public function instructor(): static
    {
        return $this->afterCreating(function (User $user) {
            $role = Role::firstOrCreate(['name' => 'instructor']);
            $user->roles()->syncWithoutDetaching($role);
        });
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn(array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
