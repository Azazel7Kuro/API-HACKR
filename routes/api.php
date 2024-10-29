<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HackController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('login', [AuthController::class, 'login']);
Route::middleware('auth:api')->group(function () {
    Route::get('/protected', function () {
        return response()->json(['message' => 'You are authenticated']);
    });
});

// Route de Connexion
Route::post('login', [AuthController::class, 'login']);
// Route d'Inscription
Route::post('register', [AuthController::class, 'register']);
// Route Info Utilisateur
Route::middleware('auth:api')->get('me', [AuthController::class, 'me']);

Route::get('/checkEmailWithHunter/{email}', [HackController::class, 'checkEmailWithHunter']);
Route::post('/spam-email', [HackController::class, 'spamEmail']);

