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

        // 1. Create the user
        $user = User::create([
            'name' => $data['name'],
            'phone' => $data['phone'],
            'password' => $data['password'],
        ]);

        // 2. Attach the role
        $role = Role::where('name', $data['role'])->firstOrFail();
        $user->roles()->attach($role);

        // 3. Generate token with expiration (24h)
        $token = $user->createTokenWithExpiration(
            'auth-token',
            ['*'],
            24
        )->plainTextToken;

        // 4. Retrieve expiration info
        $tokenModel = $user->tokens()->latest()->first();

        return response()->json([
            'success' => true,
            'message' => 'Registration successful',
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
                'phone' => ['Invalid credentials.'],
            ]);
        }

        // Revoke old tokens (optional best practice)
        $user->tokens()->where('name', 'auth-token')->delete();

        // Generate new token
        $token = $user->createTokenWithExpiration(
            'auth-token',
            ['*'],
            24
        )->plainTextToken;

        // Retrieve expiration
        $tokenModel = $user->tokens()->latest()->first();

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
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
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated',
                ], 401);
            }

            $user->currentAccessToken()->delete();

            return response()->json([
                'success' => true,
                'message' => 'Logout successful',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error while logging out',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
