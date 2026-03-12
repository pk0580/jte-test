<?php

declare(strict_types=1);

namespace App\Infrastructure\Prometheus;

use Prometheus\CollectorRegistry;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class HttpRequestSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly CollectorRegistry $collectionRegistry,
        private readonly string $namespace = 'app'
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => [['onKernelResponse', -1024]],
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $response = $event->getResponse();

        $method = (string)$request->getMethod();
        $route = (string)$request->attributes->get('_route', 'unknown');
        $status = (string)$response->getStatusCode();

        // Register and increment requests total
        $counter = $this->collectionRegistry->getOrRegisterCounter(
            $this->namespace,
            'http_requests_total',
            'Total HTTP requests',
            ['method', 'route', 'status']
        );
        $counter->inc([$method, $route, $status]);

        // Register and increment errors total
        if ($response->getStatusCode() >= 400) {
            $errCounter = $this->collectionRegistry->getOrRegisterCounter(
                $this->namespace,
                'http_errors_total',
                'Total HTTP errors',
                ['method', 'route', 'status']
            );
            $errCounter->inc([$method, $route, $status]);
        }

        // Duration metrics
        $startTime = $request->server->get('REQUEST_TIME_FLOAT');
        $duration = $startTime ? microtime(true) - (float)$startTime : 0.0;

        $summary = $this->collectionRegistry->getOrRegisterSummary(
            $this->namespace,
            'http_request_duration_seconds',
            'HTTP request duration in seconds',
            ['method', 'route'],
            600,
            [0.5, 0.9, 0.99]
        );
        $summary->observe($duration, [$method, $route]);
    }
}
