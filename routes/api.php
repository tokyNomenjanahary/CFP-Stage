<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CertificateController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\ReferralController;
use App\Http\Controllers\Api\RegistrationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// All routes use the /api/cfp prefix
Route::prefix('cfp')->group(function () {

    // Public routes
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);

    // Public courses
    Route::get('/courses', [CourseController::class, 'index']);
    Route::get('/courses/{course}', [CourseController::class, 'show']);

    // Public certificate verification by UUID
    Route::get('/verify/{uuid}', [CertificateController::class, 'verify']);

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/user', function (Request $request) {
            return $request->user()->load('roles');
        });
        Route::post('/logout', [AuthController::class, 'logout']);

        // Courses
        Route::get('/my-courses', [CourseController::class, 'myCourses']);
        Route::get('/my-enrolled-courses', [CourseController::class, 'myEnrolledCourses']);

        Route::post('/courses', [CourseController::class, 'store']);
        Route::put('/courses/{course}', [CourseController::class, 'update']);
        Route::delete('/courses/{course}', [CourseController::class, 'destroy']);

        // Course registrations
        Route::post('/courses/{course}/register', [RegistrationController::class, 'store']);
        Route::get('/courses/{course}/registrations', [RegistrationController::class, 'courseRegistrations']);
        Route::put('/registrations/{registration}/status', [RegistrationController::class, 'updateStatus']);

        // Student certificates
        Route::get('/my-certificates', [CertificateController::class, 'myCertificates']);

        // voir qui j'ai parrainé
        Route::get('/my-referrals', [ReferralController::class, 'index']);
        Route::get('/user/points', [RegistrationController::class, 'myPoints']);
    });
});
