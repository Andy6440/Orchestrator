<?php

namespace App\Service;

use App\Entity\Session;
use App\Exception\ApiException;
use App\Repository\SessionRepository;
use App\Repository\UserRepository;
use App\Validator\CreateUserRequest;
use App\Validator\LoginRequest;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AuthService
{
    private ValidatorInterface $validator;
    private UserRepository $userRepository;
    private SessionRepository $sessionRepository;
    private $serializer;
    public function __construct(ValidatorInterface $validator, UserRepository $userRepository, SessionRepository $sessionRepository,SerializerInterface $serializer)
    {
        $this->validator = $validator;
        $this->userRepository = $userRepository;
        $this->sessionRepository = $sessionRepository;
        $this->serializer = $serializer;
    }

    public function index()
    {
        return [
            'message' => 'Welcome to the API'
        ];
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

    public function logout(Session $session)
    {
        if(!$session) {
            throw new ApiException('Invalid session', [], 401);
        }
        $this->sessionRepository->delete($session);

        return [
            'logout' => true
        ];
    }

    public function register(array $data)
    {
          // validate the request data
        $this->validator->validate($data, new CreateUserRequest());
        $user = $this->userRepository->saveUserWithRole($data);
        if(!$user) {
            throw new ApiException('User could not be created', [], 500);
        }
       
        $userArray = $this->serializer->serialize($user, 'json');
        $userArray = json_decode($userArray, true);
        return [
            'user' => $userArray
        ];
    }
}
