<?php
namespace App\Service;

use App\Exception\ApiException;
use App\Repository\UserRepository;
use App\Validator\LoginRequest;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AuthService
{
    private ValidatorInterface $validator;
    private UserRepository $userRepository;
    public function __construct(ValidatorInterface $validator, UserRepository $userRepository)
    {
        $this->validator = $validator;
        $this->userRepository = $userRepository;
    }

    public function login(array $data)
    {
        $this->validator->validate($data, new LoginRequest());

        $user = $this->userRepository->findOneBy(['email' => $data['email']]);
        if (!$user) {
            throw new ApiException('User not found', ['email' => 'User not found'], 404);
        }
        // Verificar que la contraseña es correcta
        if (!password_verify($data['password'], $user->getPassword())) {
            throw new ApiException('Invalid credentials', ['password' => 'Incorrect password'], 401);
        }

        return [
            'email' => $user->getEmail(),
        ];
    }
}
