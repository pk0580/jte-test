<?php

declare(strict_types=1);

namespace App\Infrastructure\EventListener;

use App\Application\Exception\ValidationException;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Validator\Exception\ValidationFailedException;

#[AsEventListener(event: KernelEvents::EXCEPTION, priority: 10)]
class ExceptionListener
{
    public function onKernelException(ExceptionEvent $event): void
    {
        $request = $event->getRequest();
        $exception = $event->getThrowable();

        // Проверяем, является ли это API-запросом или SOAP
        if (str_starts_with($request->getPathInfo(), '/api/v1/') || str_starts_with($request->getPathInfo(), '/api/v2/') || str_starts_with($request->getPathInfo(), '/api/')) {
            $this->handleApiException($event, $exception);
            return;
        }

        if (str_contains($request->getPathInfo(), '/soap')) {
            return; // SoapServer handle errors itself
        }
    }

    private function handleApiException(ExceptionEvent $event, \Throwable $exception): void
    {
        $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;

        if ($exception instanceof ValidationException) {
            $data = [
                'error' => $exception->getMessage(),
                'violations' => $exception->violations
            ];
            $statusCode = 422;
        } elseif ($exception instanceof ValidationFailedException) {
            validation_failed:
            $data = ['error' => 'Validation failed', 'violations' => []];
            foreach ($exception->getViolations() as $violation) {
                $data['violations'][] = [
                    'field' => $violation->getPropertyPath(),
                    'message' => $violation->getMessage()
                ];
            }
            if ($exception->getViolations()->count() > 0) {
                $data['error'] = $exception->getViolations()[0]->getMessage();
            }
            $statusCode = 422;
        } elseif ($exception instanceof NotFoundHttpException) {
            $prev = $exception->getPrevious();
            if ($prev instanceof ValidationFailedException) {
                $exception = $prev;
                goto validation_failed;
            }
            $message = $exception->getMessage();
            $data = ['error' => $message];
            $statusCode = $exception->getStatusCode();
        } elseif ($exception instanceof BadRequestHttpException) {
            $data = ['error' => $exception->getMessage()];
            $statusCode = 400;
        } elseif ($exception instanceof HttpExceptionInterface) {
            $data = ['error' => $exception->getMessage()];
            $statusCode = $exception->getStatusCode();
        } else {
            $data = ['error' => $exception->getMessage() ?: 'Internal Server Error'];
        }

        $event->setResponse(new JsonResponse($data, $statusCode));
        $event->stopPropagation();
    }
}
