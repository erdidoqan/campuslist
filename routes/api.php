<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MajorController;
use App\Http\Controllers\Api\MediaController;
use App\Http\Controllers\Api\StateController;
use App\Http\Controllers\Api\UniversityController;
use Illuminate\Support\Facades\Route;

// Public authentication endpoints
Route::post('auth/login', [AuthController::class, 'login']);
Route::post('auth/register', [AuthController::class, 'register']);

// Protected API endpoints
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    // Universities endpoints
    Route::get('universities', [UniversityController::class, 'index']);
    Route::get('universities/{id}', [UniversityController::class, 'show']);
    Route::get('universities/slug/{slug}', [UniversityController::class, 'showBySlug']);

    // Majors endpoints
    Route::get('majors', [MajorController::class, 'index']);
    Route::get('majors/{id}', [MajorController::class, 'show']);

    // Media endpoints
    Route::get('media', [MediaController::class, 'index']);
    Route::get('media/{id}', [MediaController::class, 'show']);
    Route::get('universities/{universityId}/media', [MediaController::class, 'forUniversity']);

    // Location endpoints
    Route::get('countries', [StateController::class, 'countries']);
    Route::get('states', [StateController::class, 'index']);
    Route::get('states/{administrativeArea}/cities', [StateController::class, 'cities']);

    // Auth endpoints (protected)
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('auth/me', [AuthController::class, 'me']);
    Route::post('auth/tokens', [AuthController::class, 'createToken']);
    Route::delete('auth/tokens/{id}', [AuthController::class, 'revokeToken']);
});

