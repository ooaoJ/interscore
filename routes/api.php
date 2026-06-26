<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SchoolController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\InterclassController;
use App\Http\Controllers\Api\ClassroomController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::middleware('role:platform_admin')->group(function () {
       Route::apiResource('schools', SchoolController::class);
       Route::apiResource('users', UserController::class);
    });

    Route::middleware('role:platform_admin,school_manager')->group(function() {
        Route::apiResource('interclasses', InterclassController::class);

        Route::get('/grades', [ClassroomController::class, 'grades']);

        Route::get('/interclasses/{interclass}/classrooms', [ClassroomController::class, 'index']);
        Route::post('/interclasses/{interclass}/classrooms', [ClassroomController::class, 'store']);

        Route::get('/classrooms/{classroom}', [ClassroomController::class, 'show']);
        Route::put('/classrooms/{classroom}', [ClassroomController::class, 'update']);
        Route::delete('/classrooms/{classroom}', [ClassroomController::class, 'destroy']);
    });
});
