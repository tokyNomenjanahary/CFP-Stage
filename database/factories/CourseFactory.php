<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CourseFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'instructor_id' => User::factory()->instructor(),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Course $course) {
            $role = Role::firstOrCreate(['name' => 'instructor']);
            $course->instructor->roles()->syncWithoutDetaching($role);
        });
    }
}
