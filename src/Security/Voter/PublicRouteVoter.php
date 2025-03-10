<?php

namespace App\Security\Voter;
namespace App\Security\Voter;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class PublicRouteVoter extends Voter
{
    private RequestStack $requestStack;
    private RouterInterface $router;

    public function __construct(RequestStack $requestStack, RouterInterface $router)
    {
        $this->requestStack = $requestStack;
        $this->router = $router;
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        // Solo manejamos la regla "IS_PUBLIC_ROUTE"
        return $attribute === 'IS_PUBLIC_ROUTE';
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $request = $this->requestStack->getCurrentRequest();

        if (!$request) {
            return false;
        }

        // Obtener el nombre de la ruta actual
        $routeName = $request->attributes->get('_route');

        if (!$routeName) {
            return false;
        }

        // Obtener la configuración de la ruta
        $route = $this->router->getRouteCollection()->get($routeName);

        // Si la opción 'isPublic' es true, permitir acceso sin autenticación
        if ($route && $route->getOption('isPublic') === true) {
            return true;
        }

        
        return false; // Para rutas privadas, seguir la seguridad normal
    }

}
