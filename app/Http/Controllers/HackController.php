<?php

namespace App\Http\Controllers;

use App\Models\Log;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Mail;
use Tymon\JWTAuth\Facades\JWTAuth;

class HackController extends Controller
{

    /**
     * Check if the email exists using Hunter.io
     *
     * @OA\Get(
     *     path="/api/checkEmailWithHunter/{email}",
     *     tags={"Emails"},
     *     summary="Check if the email exists using Hunter.io",
     *     @OA\Parameter(
     *         name="email",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", format="email")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Email successfully verified",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="email", type="string"),
     *             @OA\Property(property="score", type="integer"),
     *             @OA\Property(property="status", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Email is not valid",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Email is not valid")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error occurred during email verification",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="There was an error verifying the email")
     *         )
     *     ),
     * )
     */

    public function checkEmailWithHunter($email): \Illuminate\Http\JsonResponse
    {
        // API URL with the email and API key for Hunter.io
        $apiUrl = "https://api.hunter.io/v2/email-verifier?email=$email&api_key=b28e3d5396a9e0268b473488cfed9ee61bb8c5ba";

        try {
            $client = new Client();
            $response = $client->get($apiUrl);
            $data = json_decode($response->getBody(), true);
            $user = JWTAuth::parseToken()->authenticate();

            Log::create([
                'id_user' => $user->id,
                'action' => 'check_email',
                'date' => now(),
                'id_action' => 4,
                'details' => 'Email checked: ' . $email
            ]);

            if (isset($data['data']['status']) && $data['data']['status'] === 'valid') {
                return response()->json([
                    'email' => $data['data']['email'],
                    'score' => $data['data']['score'],
                    'status' => $data['data']['status']
                ]);
            }

            return response()->json(['message' => 'Email is not valid'], 405);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'There was an error verifying the email',
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/spam-email",
     *     tags={"Emails"},
     *     summary="Send spam emails",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "content", "count"},
     *             @OA\Property(property="email", type="string", format="email", example="example@example.com"),
     *             @OA\Property(property="content", type="string", example="This is a spam message."),
     *             @OA\Property(property="count", type="integer", example=10)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Emails sent successfully",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="Emails sent successfully"))
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid parameters provided",
     *         @OA\JsonContent(@OA\Property(property="error", type="string", example="Invalid parameters"))
     *     )
     * )
     */
    public function spamEmail(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = JWTAuth::parseToken()->authenticate();

        Log::create([
            'id_user' => $user->id,
            'action' => 'spamEmail',
            'date' => now(),
            'id_action' => 5,
        ]);

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
                    ->subject("It's a trap");
            });
        }

        return response()->json(['message' => 'Emails sent successfully'], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/domains/{domain}",
     *     tags={"Domain"},
     *     summary="Retrieve all domains and subdomains associated with a given domain",
     *     @OA\Parameter(
     *         name="domain",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Domains and subdomains retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="domain", type="string"),
     *             @OA\Property(property="subdomains", type="array", @OA\Items(type="string")),
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Domain not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Domain not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error retrieving domains",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Could not retrieve domains")
     *         )
     *     )
     * )
     */
    public function getDomains(Request $request, $domain): \Illuminate\Http\JsonResponse
    {
        $apiKey = 'XMNTJ39Dkt97iJo9guYx6LDZXec0ZYcy';
        $client = new Client();

        try {
            $response = $client->get("https://api.securitytrails.com/v1/domain/{$domain}/subdomains", [
                'headers' => [
                    'APIKEY' => $apiKey,
                ],
            ]);

            $data = json_decode($response->getBody(), true);
            $subdomains = $data['subdomains'] ?? [];

            return response()->json([
                'domain' => $domain,
                'subdomains' => $subdomains,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Could not retrieve domains',
            ], 500);
        }
    }
}
