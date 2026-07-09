<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Certificat;
use Illuminate\Http\Request;

class CertificatController extends Controller
{
    // GET /api/cfp/my-certificates — apprenant uniquement
    public function myCertificates(Request $request)
    {
        $user = $request->user();

        $certificats = Certificat::whereHas('inscription', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })
            ->with(['inscription.formation:id,titre'])
            ->get();

        return response()->json($certificats);
    }

    // GET /api/cfp/verify/{uuid} — PUBLIC, sans authentification
    public function verify(string $uuid)
    {
        $certificat = Certificat::where('uuid', $uuid)
            ->with(['inscription.user:id,name', 'inscription.formation:id,titre'])
            ->first();

        if (! $certificat) {
            return response()->json([
                'valide' => false,
                'message' => 'Certificat introuvable ou invalide.',
            ], 404);
        }

        return response()->json([
            'valide' => true,
            'certificat' => [
                'uuid' => $certificat->uuid,
                'date_emission' => $certificat->date_emission,
                'apprenant' => $certificat->inscription->user->name,
                'formation' => $certificat->inscription->formation->titre,
            ],
        ]);
    }
}
