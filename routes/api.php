<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum')

Route::prefix('v1')->group(function () {

    // Route general
    Route::prefix('auth')->group(function () {
        Route::post('/login', [UserController::class, 'login']);
    });

    // Route proteksi
    Route::middleware('auth:sanctum')->prefix('auth')->group(function () {
        Route::get('/me', [UserController::class, 'me']);
        Route::post('/logout', [UserController::class, 'logout']);
    });

});
