namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class AuthController
{
#[Route('/api/login_check', name: 'api_login_check', methods: ['POST'])]
public function login(AuthenticationUtils $authenticationUtils): JsonResponse
{
// En arrivant ici, la connexion a déjà été validée par Symfony.
// LexikJWTAuthenticationBundle génère automatiquement le token JWT.

// Normalement, si les informations sont valides, tu ne devrais jamais
// atteindre cette partie du code, car l'authentification est gérée par
// le firewall de Symfony.
return new JsonResponse([
'error' => 'Invalid credentials',
], JsonResponse::HTTP_UNAUTHORIZED);
}
}
