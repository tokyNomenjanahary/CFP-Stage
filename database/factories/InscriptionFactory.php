<?php

namespace Database\Factories;

use App\Models\Certificat;
use App\Models\Formation;
use App\Models\Inscription;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class InscriptionFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->apprenant(),
            'formation_id' => Formation::factory(),
            'statut' => 'en_cours',
            'date_inscription' => now(),
        ];
    }

    /**
     * S'assure que l'apprenant lié a bien le rôle "apprenant".
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Inscription $inscription) {
            $role = Role::firstOrCreate(['name' => 'apprenant']);
            $inscription->user->roles()->syncWithoutDetaching($role);
        });
    }

    /**
     * État : inscription terminée (avec certificat généré).
     */
    public function terminee(): static
    {
        return $this->state(fn() => ['statut' => 'terminee'])
            ->afterCreating(function (Inscription $inscription) {
                Certificat::create([
                    'inscription_id' => $inscription->id,
                    'date_emission' => now(),
                ]);
            });
    }
}
