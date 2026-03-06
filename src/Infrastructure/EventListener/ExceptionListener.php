<?php

namespace App\Infrastructure\EventListener;

use App\Application\Exception\ValidationException;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

#[AsEventListener(event: KernelEvents::EXCEPTION, priority: 10)]
class ExceptionListener
{
    public function onKernelException(ExceptionEvent $event): void
    {
        $request = $event->getRequest();
        $exception = $event->getThrowable();

        // Проверяем, является ли это API-запросом или SOAP (SOAP обычно обрабатывается самим SoapServer, но мы добавим поддержку)
        if (str_starts_with($request->getPathInfo(), '/api/v1/') && !str_contains($request->getPathInfo(), '/soap')) {
            $this->handleApiException($event, $exception);
            return;
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
            $statusCode = 400;
        } elseif ($exception instanceof NotFoundHttpException && (str_contains($exception->getMessage(), 'request query parameters are invalid') || str_contains($exception->getMessage(), 'App\Application\Dto'))) {
            $data = ['error' => $exception->getPrevious()?->getMessage() ?: $exception->getMessage()];
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
