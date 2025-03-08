<?php

namespace App\Service;

use App\Entity\Session;
use App\Exception\ApiException;
use App\Repository\SessionRepository;
use App\Repository\UserRepository;
use App\Validator\LoginRequest;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AuthService
{
    private ValidatorInterface $validator;
    private UserRepository $userRepository;
    private SessionRepository $sessionRepository;
    
    public function __construct(ValidatorInterface $validator, UserRepository $userRepository, SessionRepository $sessionRepository)
    {
        $this->validator = $validator;
        $this->userRepository = $userRepository;
        $this->sessionRepository = $sessionRepository;
    }

    public function login(array $data)
    {
        // validate the request data
        $this->validator->validate($data, new LoginRequest());

        // search for the user by email
        $user = $this->userRepository->findOneByField('email', $data['email']);
        if (!$user || !password_verify($data['password'], $user->getPassword())) {
            throw new ApiException('Invalid credentials', [], 401);
        }
        // limit the number of active sessions per user (example: maximum 3 sessions)
        $this->sessionRepository->enforceSessionLimit($user);


        $session = new Session($user);
        $this->sessionRepository->save($session);

        return [
           'token' => $session->getSessionId(),
           'expires_at' => $session->getExpiresAt()->format('Y-m-d H:i:s'),
        ];
    }
}
