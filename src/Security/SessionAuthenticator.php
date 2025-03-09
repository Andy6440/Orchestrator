<?php
namespace App\Security;

use App\Entity\Session;
use App\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\CustomCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use App\Repository\SessionRepository;
use App\Service\ResponseService;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBag;

class SessionAuthenticator extends AbstractAuthenticator
{
    private RouterInterface $router;
    private SessionRepository $sessionRepository;
    private ResponseService $responseService;


    public function __construct(RouterInterface $router, SessionRepository $sessionRepository, ResponseService $responseService)
    {
        $this->router = $router;
        $this->sessionRepository = $sessionRepository;
        $this->responseService = $responseService;
    }

    public function supports(Request $request): ?bool
    {
        $routeName = $request->attributes->get('_route');

        if (!$routeName) {
            return false;
        }

        $route = $this->router->getRouteCollection()->get($routeName);
        if ($route && $route->getOption('isPublic') === true) {
            return false; // Permitir acceso sin autenticación
        }
        $hasAuthorization = $request->headers->has('Authorization');
        return $hasAuthorization;
    }

    public function authenticate(Request $request): Passport
    {
        $sessionId = $request->headers->get('Authorization');
        if (!$sessionId || !str_starts_with($sessionId, 'Bearer ')) {
            throw new AuthenticationException('Invalid session format');
        }

        $sessionId = substr($sessionId, 7);
        $session = $this->sessionRepository->findOneBy(['sessionId' => $sessionId]);
        if (!$session || $session->getExpiresAt() < new \DateTime()) {
            throw new AuthenticationException('Invalid session');
        }
        $user = $session->getUser();
        if(!$user) {
            throw new AuthenticationException('Invalid user');
        }
        $request->attributes->set('session', $session);
        return new Passport(
            new UserBadge($user->getEmail(), function() use ($session) {
                return $session->getUser();
            }),
            new CustomCredentials(
                fn($credentials, User $user) => $credentials === $sessionId,
                $sessionId
            ),

        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?JsonResponse
    {
        $request->attributes->set('user', $token->getUser());
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): JsonResponse
    {
        return $this->responseService->error($exception->getMessage(), [], 401);
    }

}
