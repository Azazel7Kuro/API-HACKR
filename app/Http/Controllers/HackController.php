<?php

namespace App\Http\Controllers;

use App\Models\Log;
use App\Models\User;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Tymon\JWTAuth\Facades\JWTAuth;

class HackController extends Controller
{
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
            return response()->json(['message' => 'Email is not valid'], 404);

        } catch (\Exception $e) {
            // Gérer les erreurs (par exemple si l'API ne répond pas)
            return response()->json([
                'error' => 'There was an error verifying the email',
            ], 500);
        }
    }
}
