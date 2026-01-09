<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\LiveLocationController;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/users', [UserController::class, 'index']); // list users (admin only)

     // NEW (admin only)
    Route::post('/users', [UserController::class, 'store']);
    Route::patch('/users/{user}/toggle-active', [UserController::class, 'toggleActive']);
    Route::patch('/users/{user}/reset-password', [UserController::class, 'resetPassword']);

    Route::get('/reports', [ReportController::class, 'index']);
    Route::post('/reports', [ReportController::class, 'store']);
    Route::get('/reports/{report}', [ReportController::class, 'show']);
    // Route::get('/photos/{photo}', [ReportController::class, 'photo'])->whereNumber('photo');

    Route::post('/live-locations', [LiveLocationController::class, 'upsert']);
    Route::get('/live-locations', [LiveLocationController::class, 'index']);
    



});

Route::get('/ping', function () {
    return response()->json([
        'status' => 'ok',
        'time' => now(),
    ]);
});

