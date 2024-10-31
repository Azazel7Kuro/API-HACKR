<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;

class PasswordController extends Controller
{
    /**
     * Generate a secure password.
     *
     * @OA\Get(
     *     path="/api/generate-password",
     *     tags={"Password"},
     *     summary="Generate a secure password",
     *     @OA\Parameter(
     *         name="length",
     *         in="query",
     *         description="Desired password length (default is 12)",
     *         required=false,
     *         @OA\Schema(type="integer", example=12)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Secure password generated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="password", type="string", example="aZ3$w8@kF")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid length provided",
     *         @OA\JsonContent(@OA\Property(property="error", type="string", example="Password length must be at least 8"))
     *     )
     * )
     */
    public function generateSecurePassword(Request $request): JsonResponse
    {
        $user = JWTAuth::parseToken()->authenticate();

        Log::create([
            'id_user' => $user->id,
            'action' => 'generateSecurePassword',
            'date' => now(),
            'id_action' => 6,
        ]);
        $length = $request->query('length', 12);

        if ($length < 8) {
            return response()->json(['error' => 'Password length must be at least 8'], 400);
        }

        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+-=';
        $password = substr(str_shuffle(str_repeat($characters, $length)), 0, $length);

        return response()->json(['password' => $password], 200);
    }

    /**
     * Check if a password is in the list of most common passwords.
     *
     * @OA\Post(
     *     path="/api/check-common-password",
     *     tags={"Password"},
     *     summary="Check if a password is common and insecure",
     *     description="Verifies if the provided password is in a list of the most common passwords, making it insecure.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"password"},
     *             @OA\Property(property="password", type="string", example="password123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Password is not in the list of common passwords",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="This password is not in the list of common passwords.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Password is too common and insecure",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="This password is too common and insecure.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error retrieving the password list",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Unable to retrieve password list.")
     *         )
     *     )
     * )
     */
    public function checkCommonPassword(Request $request): JsonResponse
    {
        $user = JWTAuth::parseToken()->authenticate();

        Log::create([
            'id_user' => $user->id,
            'action' => 'checkcommonpassword',
            'date' => now(),
            'id_action' => 7,
        ]);

        $request->validate([
            'password' => 'required|string',
        ]);

        $password = $request->input('password');

        // URL du fichier de mots de passe communs
        $passwordListUrl = 'https://raw.githubusercontent.com/danielmiessler/SecLists/master/Passwords/Common-Credentials/10k-most-common.txt';

        // Récupérer la liste des mots de passe communs
        $response = Http::get($passwordListUrl);

        // Vérifier la réponse
        if ($response->successful()) {
            // Convertir la liste en tableau
            $commonPasswords = explode("\n", $response->body());

            // Vérifier si le mot de passe est dans la liste
            if (in_array($password, $commonPasswords)) {
                return response()->json([
                    'message' => 'This password is too common and insecure.',
                ], 400);
            }

            return response()->json([
                'message' => 'This password is not in the list of common passwords.',
            ], 200);
        } else {
            return response()->json([
                'error' => 'Unable to retrieve password list.',
            ], 500);
        }
    }
}

