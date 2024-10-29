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

/**
 * @OA\Info(title="API HACK", version="1.0")
 */

class AuthController extends Controller
{

    /**
     * Inscription
     *
     * @OA\Post(
     *     path="/api/register",
     *     tags={"Authentication"},
     *     summary="Inscrire un nouvel utilisateur",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","email","password"},
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", format="email", example="johndoe@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Utilisateur créé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="access_token", type="string"),
     *             @OA\Property(property="token_type", type="string", example="bearer"),
     *             @OA\Property(property="expires_in", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Données invalides ou utilisateur existant",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string"),
     *             @OA\Property(property="messages", type="array", @OA\Items(type="string"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur de création d'utilisateur",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string"),
     *             @OA\Property(property="details", type="string")
     *         )
     *     )
     * )
     */

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

    /**
     * Connexion et génération du token
     *
     * @OA\Post(
     *     path="/api/login",
     *     tags={"Authentication"},
     *     summary="Connexion de l'utilisateur",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *             @OA\Property(property="email", type="string", format="email", example="johndoe@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Connexion réussie",
     *         @OA\JsonContent(
     *             @OA\Property(property="access_token", type="string"),
     *             @OA\Property(property="token_type", type="string", example="bearer"),
     *             @OA\Property(property="expires_in", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Identifiants incorrects",
     *         @OA\JsonContent(@OA\Property(property="error", type="string", example="Unauthorized"))
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur de génération de token",
     *         @OA\JsonContent(@OA\Property(property="error", type="string", example="Could not create token"))
     *     )
     * )
     */

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

    /**
     * Obtenir les informations de l'utilisateur connecté
     *
     * @OA\Get(
     *     path="/api/me",
     *     tags={"Authentication"},
     *     summary="Obtenir les informations de l'utilisateur connecté",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Informations de l'utilisateur",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="email", type="string", format="email")
     *         )
     *     ),
     *     @OA\Response(
     *         response=402,
     *         description="Token expiré",
     *         @OA\JsonContent(@OA\Property(property="error", type="string", example="Token expired"))
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Token invalide",
     *         @OA\JsonContent(@OA\Property(property="error", type="string", example="Token invalid"))
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Token absent",
     *         @OA\JsonContent(@OA\Property(property="error", type="string", example="Token absent"))
     *     )
     * )
     */

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
            return response()->json(['error' => 'Token expired'], 402);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json(['error' => 'Token invalid'], 403);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['error' => 'Token absent'], 404);
        }
    }
    /**
     * Structure de la réponse avec le token
     *
     * @param string $token
     * @return \Illuminate\Http\JsonResponse
     */
}
