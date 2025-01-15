<?php

namespace App\Http\Controllers;

use App\Models\Log;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tymon\JWTAuth\Facades\JWTAuth;
use Faker\Factory as Faker;

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

            \App\Models\Log::create([
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
     *     security={{"bearerAuth":{}}},
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

        \App\Models\Log::create([
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
     *     security={{"bearerAuth":{}}},
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
        $user = JWTAuth::parseToken()->authenticate();

        \App\Models\Log::create([
            'id_user' => $user->id,
            'action' => 'Domains',
            'date' => now(),
            'id_action' => 8,
        ]);

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

    /**
     * @OA\Get(
     *     path="/api/generate-fake-identity/{count}",
     *     tags={"Fake Identity"},
     *     summary="Generate a list of fake identities",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="count",
     *         in="path",
     *         required=false,
     *         @OA\Schema(type="integer", default=1, description="Number of identities to generate")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of generated fake identities",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="email", type="string"),
     *                 @OA\Property(property="address", type="string"),
     *                 @OA\Property(property="phone", type="string"),
     *                 @OA\Property(property="company", type="string")
     *             )
     *         )
     *     )
     * )
     */

    public function generateFakeIdentity($count): \Illuminate\Http\JsonResponse
    {
        $user = JWTAuth::parseToken()->authenticate();

        \App\Models\Log::create([
            'id_user' => $user->id,
            'action' => 'FakerId',
            'date' => now(),
            'id_action' => 9,
        ]);

        $faker = Faker::create();

        $identities = [];
        for ($i = 0; $i < $count; $i++) {
            $identities[] = [
                'name' => $faker->name,
                'email' => $faker->email,
                'address' => $faker->address,
                'phone' => $faker->phoneNumber,
                'company' => $faker->company,
            ];
        }

        return response()->json($identities);
    }

    /**
     * Get a random person's image from thispersondoesnotexist.com
     *
     * @OA\Get(
     *     path="/api/random-person-image",
     *     tags={"Faker"},
     *     summary="Get a random AI-generated person's image",
     *     description="Fetches and displays a random AI-generated person's image from thispersondoesnotexist.com",
     *     @OA\Response(
     *         response=200,
     *         description="Image successfully retrieved",
     *         content={
     *             @OA\MediaType(
     *                 mediaType="image/jpeg",
     *                 @OA\Schema(type="string", format="binary")
     *             )
     *         }
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Failed to fetch the image",
     *         @OA\JsonContent(@OA\Property(property="error", type="string", example="Could not fetch image."))
     *     )
     * )
     */

    public function getRandomPersonImage()
    {
        $url = 'https://thispersondoesnotexist.com';

        try {
            // Récupérer l'image depuis l'URL
            $response = Http::get($url);

            if ($response->successful()) {
                // Créer une réponse avec le contenu de l'image
                return new StreamedResponse(function () use ($response) {
                    echo $response->body();
                }, 200, [
                    'Content-Type' => 'image/jpeg',
                    'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
                ]);
            }

            return response()->json(['error' => 'Could not fetch image.'], 500);
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred while fetching the image.'], 500);
        }
    }

    /**
     * Fetch enriched data about a person using SerpApi.
     *
     * @OA\Get(
     *     path="/api/person-info",
     *     tags={"Person"},
     *     summary="Get enriched information about a person using SerpApi",
     *     @OA\Parameter(
     *         name="name",
     *         in="query",
     *         description="Name of the person",
     *         required=true,
     *         @OA\Schema(type="string", example="Elon Musk")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Information retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Elon Musk"),
     *             @OA\Property(property="bio", type="string", example="Elon Musk is a businessman and entrepreneur."),
     *             @OA\Property(property="social_media", type="object",
     *                @OA\Property(property="twitter", type="string", example="https://twitter.com/elonmusk")
     *             ),
     *             @OA\Property(property="wiki_url", type="string", example="https://en.wikipedia.org/wiki/Elon_Musk")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid name provided",
     *         @OA\JsonContent(@OA\Property(property="error", type="string", example="Name is required"))
     *     )
     * )
     */
    public function fetchPersonInfoSerpApi(Request $request): JsonResponse
    {
        $name = $request->query('name');

        if (!$name) {
            return response()->json(['error' => 'Name is required'], 400);
        }

        try {
            $apiKey = env('SERPAPI_API_KEY');
            $url = "https://serpapi.com/search?q=" . urlencode($name) . "&api_key=" . $apiKey . "&engine=google";

            // Make the API call to SerpApi
            $response = Http::get($url);

            if ($response->failed()) {
                return response()->json(['error' => 'Failed to fetch data from SerpApi'], 500);
            }

            // Parse the response from SerpApi
            $data = $response->json();

            if (isset($data['organic_results']) && count($data['organic_results']) > 0) {
                $personInfo = $data['organic_results'][0]; // Taking the first search result

                // Extracting useful information from the first result
                $info = [
                    'name' => $name,
                    'bio' => $personInfo['snippet'] ?? 'No bio available',
                    'social_media' => [
                        'twitter' => $personInfo['social'] ?? 'No social media found'
                    ],
                    'wiki_url' => $personInfo['link'] ?? 'No link available',
                ];

                return response()->json($info, 200);
            } else {
                return response()->json(['error' => 'No relevant results found'], 404);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }
}
