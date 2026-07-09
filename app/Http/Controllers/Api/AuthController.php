<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $data = $request->validated();

        // 1. Création de l'utilisateur
        $user = User::create([
            'name' => $data['name'],
            'phone' => $data['phone'],
            'password' => $data['password'], // hashé automatiquement grâce au cast
        ]);

        // 2. Attribution du rôle
        $role = Role::where('name', $data['role'])->firstOrFail();
        $user->roles()->attach($role);

        // 3. Génération du token avec expiration (24h)
        $token = $user->createTokenWithExpiration(
            'auth-token',
            ['*'],
            24
        )->plainTextToken;

        // 4. Récupération des infos d'expiration
        $tokenModel = $user->tokens()->latest()->first();

        // 5. Réponse
        return response()->json([
            'success' => true,
            'message' => 'Inscription réussie',
            'data' => [
                'user' => $user->load('roles'),
                'token' => $token,
                'expires_at' => $tokenModel->expires_at->toISOString(),
                'token_type' => 'Bearer',
            ]
        ], 201);
    }

    public function login(LoginRequest $request)
    {
        $request->validated();
        $user = User::where('phone', $request->phone)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'phone' => ['Identifiants incorrects.'],
            ]);
        }

        // Révoquer les anciens tokens (optionnel - bonne pratique)
        $user->tokens()->where('name', 'auth-token')->delete();

        // Génération du nouveau token
        $token = $user->createTokenWithExpiration(
            'auth-token',
            ['*'],
            24
        )->plainTextToken;

        // Récupération de l'expiration
        $tokenModel = $user->tokens()->latest()->first();

        return response()->json([
            'success' => true,
            'message' => 'Connexion réussie',
            'data' => [
                'user' => $user->load('roles'),
                'token' => $token,
                'expires_at' => $tokenModel->expires_at->toISOString(),
                'token_type' => 'Bearer'
            ]
        ], 200);
    }

    public function logout(Request $request)
    {
        try {
            // Récupérer l'utilisateur authentifié
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Non authentifié'
                ], 401);
            }

            // Révoquer le token actuel
            $user->currentAccessToken()->delete();


            return response()->json([
                'success' => true,
                'message' => 'Déconnexion réussie'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la déconnexion',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Rafraîchir le token actuel
    public function refreshToken(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Non authentifié'
                ], 401);
            }

            // Révoquer l'ancien token
            $user->currentAccessToken()->delete();

            // Créer un nouveau token
            $token = $user->createTokenWithExpiration(
                'auth-token',
                ['*'],
                24
            )->plainTextToken;

            $tokenModel = $user->tokens()->latest()->first();

            return response()->json([
                'success' => true,
                'message' => 'Token rafraîchi avec succès',
                'data' => [
                    'token' => $token,
                    'expires_at' => $tokenModel->expires_at->toISOString(),
                    'token_type' => 'Bearer'
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du rafraîchissement',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Vérifier la validité du token actuel
    public function checkToken(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Token invalide',
                'is_valid' => false
            ], 401);
        }

        $token = $user->currentAccessToken();

        // Vérifier si le token a expiré
        $isExpired = $token && $token->expires_at && Carbon::now()->greaterThan($token->expires_at);

        if ($isExpired) {
            return response()->json([
                'success' => false,
                'message' => 'Token expiré',
                'is_valid' => false,
                'expired_at' => $token->expires_at->toISOString()
            ], 401);
        }

        return response()->json([
            'success' => true,
            'message' => 'Token valide',
            'is_valid' => true,
            'expires_at' => $token->expires_at->toISOString(),
            'user' => $user->load('roles')
        ], 200);
    }
}
