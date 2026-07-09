<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Certificat;
use App\Models\Formation;
use App\Models\Inscription;
use Illuminate\Http\Request;

class InscriptionController extends Controller
{
    // POST /api/cfp/courses/{course}/register
    public function store(Request $request, Formation $course)
    {
        $user = $request->user();

        if (! $user->hasRole('apprenant')) {
            return response()->json([
                'message' => 'Seul un apprenant peut s\'inscrire à une formation.',
            ], 403);
        }

        // Empêche un formateur de s'inscrire à sa propre formation
        if ($course->formateur_id === $user->id) {
            return response()->json([
                'message' => 'Vous ne pouvez pas vous inscrire à votre propre formation.',
            ], 422);
        }

        // Empêche une double inscription
        $dejaInscrit = Inscription::where('user_id', $user->id)
            ->where('formation_id', $course->id)
            ->exists();

        if ($dejaInscrit) {
            return response()->json([
                'message' => 'Vous êtes déjà inscrit à cette formation.',
            ], 422);
        }

        $inscription = Inscription::create([
            'user_id' => $user->id,
            'formation_id' => $course->id,
            'statut' => 'en_cours',
            'date_inscription' => now(),
        ]);

        return response()->json($inscription, 201);
    }

    // POST /api/cfp/register/{inscription}/finish
    public function finish(Request $request, Inscription $inscription)
    {
        $user = $request->user();

        // Seul le formateur concerné peut valider
        $estFormateur = $inscription->formation->formateur_id === $user->id;
        // $estApprenant = $inscription->user_id === $user->id;

        if (! $estFormateur ) {
            return response()->json([
                'message' => 'Vous n\'êtes pas autorisé à modifier cette inscription.',
            ], 403);
        }

        if ($inscription->statut === 'terminee') {
            return response()->json([
                'message' => 'Cette formation est déjà marquée comme terminée.',
            ], 422);
        }

        $inscription->update(['statut' => 'terminee']);

        // Génère automatiquement le certificat
        $certificat = Certificat::create([
            'inscription_id' => $inscription->id,
            'date_emission' => now(),
        ]);

        return response()->json([
            'message' => 'Formation marquée comme terminée.',
            'inscription' => $inscription,
            'certificat' => $certificat,
        ]);
    }
}
