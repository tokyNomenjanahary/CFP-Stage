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


    // GET /api/cfp/courses/{course}/inscriptions — formateur, propriétaire uniquement
    public function courseInscriptions(Request $request, Formation $course)
    {
        $user = $request->user();

        if ($course->formateur_id !== $user->id) {
            return response()->json([
                'message' => 'Vous ne pouvez consulter que les inscriptions de vos propres formations.',
            ], 403);
        }

        $inscriptions = $course->inscriptions()
            ->with('user:id,name,phone')
            ->with('certificat')
            ->get();

        return response()->json($inscriptions);
    }

    // PUT /api/cfp/registered/{inscription}/status — formateur propriétaire uniquement
    public function updateStatus(Request $request, Inscription $inscription)
    {
        $user = $request->user();

        if ($inscription->formation->formateur_id !== $user->id) {
            return response()->json([
                'message' => 'Vous ne pouvez modifier que les inscriptions de vos propres formations.',
            ], 403);
        }

        $validated = $request->validate([
            'statut' => 'required|in:en_cours,terminee',
        ]);

        $inscription->update(['statut' => $validated['statut']]);

        // Génère le certificat seulement s'il n'existe pas déjà
        if ($validated['statut'] === 'terminee' && ! $inscription->certificat) {
            Certificat::create([
                'inscription_id' => $inscription->id,
                'date_emission' => now(),
            ]);
        }

        return response()->json([
            'message' => 'Statut mis à jour.',
            'inscription' => $inscription->fresh('certificat'),
        ]);
    }
}
