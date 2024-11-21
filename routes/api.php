<?php

use App\Http\Controllers\LogController;
use App\Http\Controllers\PasswordController;
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
// Route pour le checker d'email
Route::get('/checkEmailWithHunter/{email}', [HackController::class, 'checkEmailWithHunter']);
// Route pour le spammer de mail
Route::post('/spam-email', [HackController::class, 'spamEmail']);
// Route pour le générateur de mdp
Route::get('/generate-password', [PasswordController::class, 'generateSecurePassword']);

Route::post('/verified-password', [PasswordController::class, 'checkCommonPassword']);

Route::get('/logs', [LogController::class, 'getLogs']);

Route::get('/log-action/{id_action}', [LogController::class, 'getLogsByFunctionId']);

Route::get('/log-user/{id_user}', [LogController::class, 'getUserLogs']);

Route::get('/domains/{domain}', [HackController::class, 'getDomains']);

Route::get('/generate-fake-identity/{count}', [HackController::class, 'generateFakeIdentity']);

Route::get('/random-person-image', [HackController::class, 'getRandomPersonImage']);
