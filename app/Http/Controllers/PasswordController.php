<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
        $length = $request->query('length', 12);

        if ($length < 8) {
            return response()->json(['error' => 'Password length must be at least 8'], 400);
        }

        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+-=';
        $password = substr(str_shuffle(str_repeat($characters, $length)), 0, $length);

        return response()->json(['password' => $password], 200);
    }
}

