<?php
namespace App\Service;

use App\Exception\ApiException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class ResponseService
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

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
        } catch (ApiException $e) {
            return $this->error($e->getMessage(), $e->getErrors(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return $this->error($e->getMessage(), [], 500);
        }
    }

    public function withResponseTransaction(callable $callback, string $successMessage, int $successStatus = 200): JsonResponse
    {
        $this->entityManager->beginTransaction();
        try {
            $data = $callback();
            $this->entityManager->commit();
            return $this->success($successMessage, $data, $successStatus);
        } catch (ApiException $e) {
            $this->entityManager->rollback();
            return $this->error($e->getMessage(), $e->getErrors(), $e->getStatusCode());
        } catch (\Throwable $e) {
            $this->entityManager->rollback();
            return $this->error( 'An unexpected error occurred', ['error'=>$e->getMessage()], 400);
        }
    }
}
