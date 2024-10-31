<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
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
     * User registration
     *
     * @OA\Post(
     *     path="/api/register",
     *     tags={"Authentication"},
     *     summary="Register a new user",
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
     *         description="User successfully created",
     *         @OA\JsonContent(
     *             @OA\Property(property="access_token", type="string"),
     *             @OA\Property(property="token_type", type="string", example="bearer"),
     *             @OA\Property(property="expires_in", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Invalid data or user already exists",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string"),
     *             @OA\Property(property="messages", type="array", @OA\Items(type="string"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error creating user",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string"),
     *             @OA\Property(property="details", type="string")
     *         )
     *     )
     * )
     */

    public function register(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:6',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'User already exists or invalid data.',
                'messages' => $e->errors()
            ], 422);
        }

        try {
            $user = User::create([
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'password' => Hash::make($validatedData['password']),
            ]);

            $token = JWTAuth::fromUser($user);

            Log::create([
                'id_user' => $user->id,
                'action' => 'register',
                'date' => now(),
                'id_action' => 1,
            ]);

            return response()->json([
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => JWTAuth::factory()->getTTL() * 60,
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'error' => 'User creation failed. Please try again.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Login and token generation
     *
     * @OA\Post(
     *     path="/api/login",
     *     tags={"Authentication"},
     *     summary="User login",
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
     *         description="Login successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="access_token", type="string"),
     *             @OA\Property(property="token_type", type="string", example="bearer"),
     *             @OA\Property(property="expires_in", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid credentials",
     *         @OA\JsonContent(@OA\Property(property="error", type="string", example="Unauthorized"))
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error generating token",
     *         @OA\JsonContent(@OA\Property(property="error", type="string", example="Could not create token"))
     *     )
     * )
     */

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        try {
            if (!$token = JWTAuth::attempt($credentials)) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
        } catch (JWTException $e) {
            return response()->json(['error' => 'Could not create token'], 500);
        }

        $user = Auth::user();

        Log::create([
            'id_user' => $user->id,
            'action' => 'login',
            'date' => now(),
            'id_action' => 2,
        ]);

        return $this->respondWithToken($token);
    }

    /**
     * Get logged-in user information
     *
     * @OA\Get(
     *     path="/api/me",
     *     tags={"Authentication"},
     *     summary="Get logged-in user information",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="User information",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="email", type="string", format="email")
     *         )
     *     ),
     *     @OA\Response(
     *         response=402,
     *         description="Token expired",
     *         @OA\JsonContent(@OA\Property(property="error", type="string", example="Token expired"))
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Token invalid",
     *         @OA\JsonContent(@OA\Property(property="error", type="string", example="Token invalid"))
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Token absent",
     *         @OA\JsonContent(@OA\Property(property="error", type="string", example="Token absent"))
     *     )
     * )
     */

    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60
        ]);
    }

    public function me()
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            Log::create([
                'id_user' => $user->id,
                'action' => 'get_user_info',
                'date' => now(),
                'id_action' => 3,
            ]);

            return response()->json($user);
        } catch (TokenExpiredException $e) {
            return response()->json(['error' => 'Token expired'], 402);
        } catch (TokenInvalidException $e) {
            return response()->json(['error' => 'Token invalid'], 403);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Token absent'], 404);
        }
    }
}
