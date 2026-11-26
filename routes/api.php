<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum')

Route::prefix('v1')->group(function () {
    // Route general
    Route::prefix('auth')->group(function () {
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/check-email', [AuthController::class, 'checkEmail']);
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    });

    // Route authentikasi setelah login
    Route::middleware('auth:sanctum')->prefix('auth')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });

    // Route admin
    Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users-create', [UserController::class, 'store']);
        Route::get('/users-get/{id}', [UserController::class, 'show']);
        Route::put('/users-update/{id}', [UserController::class, 'update']);
        Route::get('/users-accounts', [UserController::class, 'indexAccount']);
        Route::get('/users-accounts/{id}', [UserController::class, 'showAccount']);
        Route::post('/users/{id}/create-account', [UserController::class, 'createAccount']);
        Route::put('/users-accounts-update/{id}', [UserController::class, 'updateAccount']);
    });

    // Route fitur
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/change-password', [AuthController::class, 'changePassword']);
    });

    // Teacher API Routes
    require __DIR__.'/teacher.php';

    // Employee Attendance API Routes
    require __DIR__.'/attendance.php';

});
