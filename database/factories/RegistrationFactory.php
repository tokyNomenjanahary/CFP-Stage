<?php

namespace Database\Factories;

use App\Models\Certificate;
use App\Models\Course;
use App\Models\Registration;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class RegistrationFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->student(),
            'course_id' => Course::factory(),
            'status' => 'in_progress',
            'registered_at' => now(),
        ];
    }

    /**
     * Ensure the linked user has the student role.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Registration $registration) {
            $role = Role::firstOrCreate(['name' => 'student']);
            $registration->user->roles()->syncWithoutDetaching($role);
        });
    }

    /**
     * State: completed registration with generated certificate.
     */
    public function completed(): static
    {
        return $this->state(fn() => ['status' => 'completed'])
            ->afterCreating(function (Registration $registration) {
                Certificate::create([
                    'registration_id' => $registration->id,
                    'issued_at' => now(),
                ]);
            });
    }
}
