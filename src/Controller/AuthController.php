<?php

namespace App\Controller;

use App\Exception\ApiException;
use App\Service\AuthService;
use App\Service\ResponseService;
use App\Validator\LoginRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class AuthController extends AbstractController
{
    private AuthService $authService;
    private ResponseService $responseService;

    public function __construct(AuthService $authService, ResponseService $responseService)
    {
        $this->authService = $authService;
        $this->responseService = $responseService;
    }
    public function login(Request $request, ValidatorInterface $validator): JsonResponse
    {

        $data = json_decode($request->getContent(), true);

        return $this->responseService->withResponse(
            fn() => $this->authService->login($data),
            'Login successful',
            200
        );
    }
}
