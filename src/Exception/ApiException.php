<?php
namespace App\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

class ApiException extends HttpException
{
    private array $errors;

    public function __construct(string $message = "An error occurred", array $errors = [], int $statusCode = 500)
    {
        parent::__construct($statusCode, $message);
        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
