<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Toutes les routes auront le préfixe /api/cfp
Route::prefix('cfp')->group(function () {
    
    // Routes publiques
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    
    // Routes protégées
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/user', function (Request $request) {
            return $request->user()->load('roles');
        });
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});