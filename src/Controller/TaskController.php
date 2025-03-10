<?php

namespace App\Controller;

use App\Service\ResponseService;
use App\Service\TaskService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class TaskController extends AbstractController
{
    private TaskService $taskService;
    private ResponseService $responseService;

    public function __construct(TaskService $taskService, ResponseService $responseService)
    {
        $this->taskService = $taskService;
        $this->responseService = $responseService;
    }
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $session = $request->attributes->get('session');
        return $this->responseService->withResponseTransaction(
            fn() => $this->taskService->create($data, $session),
            'Registration successful',
            201
        );
    }
}
