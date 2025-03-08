<?php
namespace App\Service;

use Symfony\Component\HttpFoundation\JsonResponse;

class ResponseService
{
    public function success(string $message, array $data = [], int $status = 200): JsonResponse
    {
        return new JsonResponse([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    public function error(string $message, array $errors = [], int $status = 400): JsonResponse
    {
        return new JsonResponse([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }

    public function withResponse(callable $callback, string $successMessage, int $successStatus = 200): JsonResponse
    {
        try {
            $data = $callback();
            return $this->success($successMessage, $data, $successStatus);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), [], 401);
        }
    }
}
