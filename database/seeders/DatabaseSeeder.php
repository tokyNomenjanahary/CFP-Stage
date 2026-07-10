<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
        ]);

        // 3 instructors with 2-8 courses each
        $instructors = User::factory()->instructor()->count(3)->create();
        $instructors->each(function ($instructor) {
            Course::factory()->count(rand(2, 8))->create([
                'instructor_id' => $instructor->id,
            ]);
        });

        // 10 students, each enrolled in 1-3 random courses
        $students = User::factory()->student()->count(10)->create();
        $courses = Course::all();

        $students->each(function ($student) use ($courses) {
            $courses->random(rand(1, 3))->each(function ($course) use ($student) {
                Registration::factory()->create([
                    'user_id' => $student->id,
                    'course_id' => $course->id,
                ]);
            });
        });
    }
}
