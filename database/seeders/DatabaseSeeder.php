<?php

namespace Database\Seeders;

use App\Models\Formation;
use App\Models\Inscription;
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

        // 3 formateurs avec 2-8 formations chacun
        $formateurs = User::factory()->formateur()->count(3)->create();
        $formateurs->each(function ($formateur) {
            Formation::factory()->count(rand(2, 8))->create([
                'formateur_id' => $formateur->id,
            ]);
        });

        // 10 apprenants, chacun inscrit à 1-3 formations aléatoires
        $apprenants = User::factory()->apprenant()->count(10)->create();
        $formations = Formation::all();

        $apprenants->each(function ($apprenant) use ($formations) {
            $formations->random(rand(1, 3))->each(function ($formation) use ($apprenant) {
                Inscription::factory()->create([
                    'user_id' => $apprenant->id,
                    'formation_id' => $formation->id,
                ]);
            });
        });
    }
}
