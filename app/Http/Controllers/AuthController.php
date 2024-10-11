<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Models\User;
use App\Models\Log;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    // Inscription
    public function register(Request $request)
    {
        // Valider les données
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:6',
            ]);
        } catch (ValidationException $e) {
            // Si la validation échoue, retourner une réponse d'erreur
            return response()->json([
                'error' => 'L\'utilisateur existe déjà ou les données sont invalides.',
                'messages' => $e->errors()
            ], 422);
        }

        // Essayer de créer l'utilisateur
        try {
            $user = User::create([
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'password' => Hash::make($validatedData['password']),
            ]);

            // Générer un token JWT pour l'utilisateur
            $token = JWTAuth::fromUser($user);

            // Enregistrer l'action de log
            Log::create([
                'id_user' => $user->id,
                'action' => 'register',
                'date' => now(),
                'id_action' => 1,
            ]);

            // Retourner la réponse avec le token
            return response()->json([
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => JWTAuth::factory()->getTTL() * 60,
            ], 201);

        } catch (\Exception $e) {
            // Gérer les erreurs qui pourraient survenir lors de la création de l'utilisateur
            return response()->json([
                'error' => 'La création de l\'utilisateur a échoué. Veuillez réessayer.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    // Connexion et génération du token
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        try {
            // Tentative d'authentification et génération du token JWT
            if (!$token = JWTAuth::attempt($credentials)) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
        } catch (JWTException $e) {
            return response()->json(['error' => 'Could not create token'], 500);
        }

        // Récupérer l'utilisateur authentifié
        $user = Auth::user();

        // Créer un log pour l'action de connexion
        Log::create([
            'id_user' => $user->id,
            'action' => 'login',
            'date' => now(),
            'id_action' => 2,
        ]);

        // Retourner le token avec la structure de la réponse
        return $this->respondWithToken($token);
    }

    // Renvoie la structure de la réponse avec le token
    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60
        ]);
    }

    // Retourner les informations de l'utilisateur connecté
    public function me()
    {
        try {
            // Obtenir l'utilisateur authentifié grâce au token JWT
            $user = JWTAuth::parseToken()->authenticate();

            // Créer un log pour l'action 'me'
            Log::create([
                'id_user' => $user->id,
                'action' => 'get_user_info',
                'date' => now(),
                'id_action' => 3, // Identifier l'action "me" avec un id spécifique
            ]);

            // Retourner les informations de l'utilisateur
            return response()->json($user);
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return response()->json(['error' => 'Token expired'], 401);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json(['error' => 'Token invalid'], 401);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['error' => 'Token absent'], 401);
        }
    }
}
