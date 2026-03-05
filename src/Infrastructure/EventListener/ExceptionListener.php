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

#[AsEventListener(event: KernelEvents::EXCEPTION, priority: 255)]
class ExceptionListener
{
    public function onKernelException(ExceptionEvent $event): void
    {
        $request = $event->getRequest();
        $exception = $event->getThrowable();

        // Проверяем, является ли это API-запросом или SOAP (SOAP обычно обрабатывается самим SoapServer, но мы добавим поддержку)
        if (str_starts_with($request->getPathInfo(), '/api/v1/') && !str_contains($request->getPathInfo(), '/soap')) {
            $this->handleApiException($event, $exception);
        }
    }

    private function handleApiException(ExceptionEvent $event, \Throwable $exception): void
    {
        $response = new JsonResponse();

        if ($exception instanceof ValidationException) {
            $response->setData([
                'error' => $exception->getMessage(),
                'violations' => $exception->getViolations()
            ]);
            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
        } elseif ($exception instanceof NotFoundHttpException && (str_contains($exception->getMessage(), 'request query parameters are invalid') || str_contains($exception->getMessage(), 'App\Application\Dto'))) {
            $response = new JsonResponse(['error' => $exception->getPrevious()?->getMessage() ?: $exception->getMessage()], Response::HTTP_BAD_REQUEST);
            $event->setResponse($response);
            $event->allowCustomResponseCode();
            return;
        } elseif ($exception instanceof HttpExceptionInterface) {
            $response->setData(['error' => $exception->getMessage()]);
            $response->setStatusCode($exception->getStatusCode());
        } else {
            $response->setData(['error' => $exception->getMessage() ?: 'Internal Server Error']);
            $response->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $event->setResponse($response);
    }
}
