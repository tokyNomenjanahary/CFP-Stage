<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CertificatController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\InscriptionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Toutes les routes auront le préfixe /api/cfp
Route::prefix('cfp')->group(function () {

    // Routes publiques
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);

    // --- Formations publiques ---
    Route::get('/courses', [CourseController::class, 'index']);
    Route::get('/courses/{course}', [CourseController::class, 'show']);

    // --- Vérification de certificat publique uuid  ---
    Route::get('/verify/{uuid}', [CertificatController::class, 'verify']);


    // Routes protégées
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/user', function (Request $request) {
            return $request->user()->load('roles');
        });
        Route::post('/logout', [AuthController::class, 'logout']);

        // Formations
        Route::get('/my-courses', [CourseController::class, 'myCourses']);
        Route::get('/my-register-courses', [CourseController::class, 'myRegisterCourses']);

        Route::post('/courses', [CourseController::class, 'store']);
        Route::put('/courses/{course}', [CourseController::class, 'update']);
        Route::delete('/courses/{course}', [CourseController::class, 'destroy']);

        // inscriptions formation
        Route::post('/courses/{course}/register', [InscriptionController::class, 'store']);
        Route::post('/register/{inscription}/finish', [InscriptionController::class, 'finish']);
    });
});
