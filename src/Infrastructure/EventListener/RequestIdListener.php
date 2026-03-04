<?php

namespace App\Infrastructure\EventListener;

use App\Infrastructure\Logging\RequestIdProvider;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class RequestIdListener
{
    public function __construct(
        private readonly RequestIdProvider $requestIdProvider
    ) {}

    #[AsEventListener(event: KernelEvents::REQUEST, priority: 255)]
    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $requestId = $request->headers->get('X-Request-Id') ?? bin2hex(random_bytes(8));

        $this->requestIdProvider->setRequestId($requestId);
        $request->attributes->set('request_id', $requestId);
    }

    #[AsEventListener(event: KernelEvents::RESPONSE, priority: -255)]
    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $response = $event->getResponse();
        $response->headers->set('X-Request-Id', $this->requestIdProvider->getRequestId());
    }
}
