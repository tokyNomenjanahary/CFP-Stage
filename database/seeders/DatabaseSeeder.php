<?php

namespace Database\Seeders;

use App\Models\Formation;
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

        // Formateurs avec leurs formations
        User::factory()->formateur()->count(3)->create()->each(function ($formateur) {
            Formation::factory()->count(rand(2, 10))->create([
                'formateur_id' => $formateur->id,
            ]);
        });

        // Apprenants
        User::factory()->apprenant()->count(10)->create();
    }
}
