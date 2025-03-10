<?php 
namespace App\EventListener;

use App\Exception\ApiException;
use App\Service\ResponseService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ExceptionListener
{
    private string $env;
    private LoggerInterface $logger;
    private ResponseService  $response;

    public function __construct(string $env, LoggerInterface $logger, ResponseService  $response)
    {
        $this->env = $env;
        $this->logger = $logger;
        $this->response = $response;
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $statusCode = 500;
        $message = 'An unexpected error occurred';
        $errors = [];
        dd($exception);
            // Manejo de excepciones personalizadas
        if ($exception instanceof ApiException) {
            $statusCode = $exception->getStatusCode();
            $message = $exception->getMessage();
            $errors = $exception->getErrors();
        } elseif ($exception instanceof HttpExceptionInterface) {
            $statusCode = $exception->getStatusCode();
            $message = $exception->getMessage();
        }

        // Loggear la excepción en modo producción
        if ($this->env !== 'dev') {
            $this->logger->error($exception->getMessage(), [
                'exception' => $exception,
            ]);
        }

        // // Incluir detalles solo en desarrollo
        // if ($this->env === 'dev') {
        //     $errors['debug'] = [
        //         'trace' => $exception->getTrace(),
        //         'file' => $exception->getFile(),
        //         'line' => $exception->getLine(),
        //     ];
        // }

        $response = $this->response->error($message, $errors, $statusCode);

        $event->setResponse($response);
    }
}
