<?php
namespace App\Service;

use App\Validator\LoginRequest;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AuthService
{
    private ValidatorInterface $validator;

    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    public function login(array $data)
    {
        // Validar datos de login
        $this->validator->validate($data, new LoginRequest());

        

        // Retornar datos de usuario autenticado
        return [
            'user' => [
                'token' => 'fake-jwt-token'
            ]
        ];
    }
}
