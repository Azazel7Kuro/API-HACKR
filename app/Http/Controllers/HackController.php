<?php

namespace App\Http\Controllers;

use App\Models\Log;
use App\Models\User;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Mail;
use Tymon\JWTAuth\Facades\JWTAuth;

class HackController extends Controller
{

    /**
     * Vérification si l'email existe via Hunter.io
     *
     * @OA\Get(
     *     path="/api/checkEmailWithHunter/{email}",
     *     tags={"Email Verification"},
     *     summary="Vérifie si l'email existe via Hunter.io",
     *     @OA\Parameter(
     *         name="email",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", format="email")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Email vérifié avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="email", type="string"),
     *             @OA\Property(property="score", type="integer"),
     *             @OA\Property(property="status", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Email non valide",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Email is not valid")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur lors de la vérification de l'email",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="There was an error verifying the email")
     *         )
     *     ),
     * )
     */

    // Vérification si l'email existe via Hunter.io
    public function checkEmailWithHunter($email)
    {
        // URL API Hunter.io avec l'email et la clé API
        $apiUrl = "https://api.hunter.io/v2/email-verifier?email=$email&api_key=b28e3d5396a9e0268b473488cfed9ee61bb8c5ba";

        try {
            // Initialiser un client HTTP
            $client = new Client();
            // Effectuer la requête à l'API de Hunter.io avec l'email passé
            $response = $client->get($apiUrl);

            // Décoder la réponse JSON
            $data = json_decode($response->getBody(), true);

            $user = JWTAuth::parseToken()->authenticate();

            // Créer un log pour la vérification
            Log::create([
                'id_user' => $user->id,
                'action' => 'check_email',
                'date' => now(),
                'id_action' => 4,
                'details' => 'Email checked: ' . $email
            ]);

            // Si l'email est validé par Hunter.io
            if (isset($data['data']['status']) && $data['data']['status'] === 'valid') {
                return response()->json([
                    'email' => $data['data']['email'],   // L'adresse email vérifiée
                    'score' => $data['data']['score'],   // Le score de validation
                    'status' => $data['data']['status']  // Le statut (valid, invalid, etc.)
                ]);
            }


            // Si l'email n'est pas valide
            return response()->json(['message' => 'Email is not valid'], 405);

        } catch (\Exception $e) {
            // Gérer les erreurs (par exemple si l'API ne répond pas)
            return response()->json([
                'error' => 'There was an error verifying the email',
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/spam-email",
     *     tags={"EmailSpam"},
     *     summary="Envoyer un spam par e-mail",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "content", "count"},
     *             @OA\Property(property="email", type="string", format="email", example="example@example.com"),
     *             @OA\Property(property="content", type="string", example="Ceci est un message de spam."),
     *             @OA\Property(property="count", type="integer", example=10)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="E-mails envoyés avec succès",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="E-mails envoyés avec succès"))
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Erreur dans les paramètres fournis",
     *         @OA\JsonContent(@OA\Property(property="error", type="string", example="Invalid parameters"))
     *     )
     * )
     */
    public function spamEmail(Request $request): \Illuminate\Http\JsonResponse
    {
        $validatedData = $request->validate([
            'email' => 'required|email',
            'content' => 'required|string',
            'count' => 'required|integer|min:1',
        ]);

        $email = $validatedData['email'];
        $content = $validatedData['content'];
        $count = $validatedData['count'];

        for ($i = 0; $i < $count; $i++) {
            Mail::raw($content, function ($message) use ($email) {
                $message->to($email)
                    ->subject('it\'s a trap');
            });
        }

        return response()->json(['message' => 'E-mails envoyes avec succes'], 200);
    }
}
