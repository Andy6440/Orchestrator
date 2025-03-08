<?php

namespace App\Controller;

use App\Exception\ApiException;
use App\Validator\LoginRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class AuthController extends AbstractController
{
    public function register(): JsonResponse
    {
        dd('register');
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/AuthController.php',
        ]);
    }
    public function logout(): JsonResponse
    {
        dd('logout');
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/AuthController.php',
        ]);
    }

    public function index(): JsonResponse
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/AuthController.php',
        ]);
    }
    public function login(Request $request, ValidatorInterface $validator): JsonResponse
    {

        $data = json_decode($request->getContent(), true);

       $validator->validate($data, new LoginRequest());

        return new JsonResponse(['message' => 'Login successful'], 200);
    }
}
