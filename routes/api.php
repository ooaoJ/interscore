<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SchoolController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\InterclassController;
use App\Http\Controllers\Api\ClassroomController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\ModalityController;
use App\Http\Controllers\Api\TeamController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // For platform admins only
    Route::middleware('role:platform_admin')->group(function () {
       Route::apiResource('schools', SchoolController::class);
       Route::apiResource('users', UserController::class);
    });

    // For school managers and platform admins
    Route::middleware('role:platform_admin,school_manager')->group(function() {
        // InterclassController
        Route::apiResource('interclasses', InterclassController::class);

        // ClassroomController
        Route::get('/grades', [ClassroomController::class, 'grades']);
        Route::get('/interclasses/{interclass}/classrooms', [ClassroomController::class, 'index']);
        Route::post('/interclasses/{interclass}/classrooms', [ClassroomController::class, 'store']);
        Route::get('/classrooms/{classroom}', [ClassroomController::class, 'show']);
        Route::put('/classrooms/{classroom}', [ClassroomController::class, 'update']);
        Route::delete('/classrooms/{classroom}', [ClassroomController::class, 'destroy']);

        // StudentController
        Route::get('/classrooms/{classroom}/students', [StudentController::class, 'index']);
        Route::post('/classrooms/{classroom}/students', [StudentController::class, 'store']);
        Route::get('/students/{student}', [StudentController::class, 'show']);
        Route::put('/students/{student}', [StudentController::class, 'update']);
        Route::delete('/students/{student}', [StudentController::class, 'destroy']);

        // ModalityController
        Route::get('/sports', [ModalityController::class, 'sports']);
        Route::get('/competition-categories', [ModalityController::class, 'categories']);
        Route::get('/interclasses/{interclass}/modalities', [ModalityController::class, 'index']);
        Route::post('/interclasses/{interclass}/modalities', [ModalityController::class, 'store']);
        Route::get('/modalities/{modality}', [ModalityController::class, 'show']);
        Route::put('/modalities/{modality}', [ModalityController::class, 'update']);
        Route::delete('/modalities/{modality}', [ModalityController::class, 'destroy']);

        // TeamController
        Route::get('/modalities/{modality}/teams', [TeamController::class, 'index']);
        Route::post('/modalities/{modality}/teams', [TeamController::class, 'store']);
        Route::get('/teams/{team}', [TeamController::class, 'show']);
        Route::put('/teams/{team}', [TeamController::class, 'update']);
        Route::delete('/teams/{team}', [TeamController::class, 'destroy']);

        
    });
});
