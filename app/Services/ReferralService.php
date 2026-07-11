<?php

namespace App\Services;

use App\Models\Referral;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ReferralService
{
    /**
     * Traite la récompense de parrainage pour un nouvel utilisateur
     * 
     * @param int $userId L'ID de l'utilisateur qui s'inscrit (filleul)
     * @return bool True si la récompense a été donnée
     */
    public function processReward(int $userId): bool
    {
        $user = User::find($userId);
        // dd($user, "User $userId");
        if (!$user || !$user->referred_by) {
            return false;
        }

        // Vérifier si c'est la première inscription
        // $hasPreviousRegistrations = Registration::where('user_id', $userId)->exists();
        // dd($hasPreviousRegistrations, "Has previous registrations for user $userId");
        // if ($hasPreviousRegistrations) {
        //     return false;
        // }

        // Récupérer la relation de parrainage non récompensée
        $referral = Referral::where('referrer_id', $user->referred_by)
            ->where('referred_id', $userId)
            ->whereNull('reward_triggered_at')
            ->first();

        if (!$referral) {
            return false;
        }

        // Nombre de points à donner 20
        $pointsToGive = 20;

        DB::transaction(function () use ($referral, $userId, $pointsToGive) {
            // 1. Marquer la récompense comme déclenchée

            $referral->update(['reward_triggered_at' => now()]);
        
            // 2. Donner les points au parrain
            $referrer = User::find($referral->referrer_id);
            if ($referrer) {
                $referrer->increment('loyalty_points', $pointsToGive);
            }
        });

        return true;
    }

    /**
     * Récupère le total des points d'un utilisateur
     */
    public function getTotalPoints(int $userId): int
    {
        $user = User::find($userId);
        return $user ? $user->loyalty_points : 0;
    }

    /**
     * Vérifie si un utilisateur a des points suffisants
     */
    public function hasEnoughPoints(int $userId, int $pointsRequired): bool
    {
        return $this->getTotalPoints($userId) >= $pointsRequired;
    }
}
