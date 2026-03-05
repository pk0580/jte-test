<?php

namespace App\Infrastructure\Monitoring;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::REQUEST, priority: 255)]
class TraceIdListener
{
    private const string TRACE_HEADER = 'X-Trace-ID';

    public function __construct(
        private TraceIdContext $traceIdContext
    ) {}

    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $traceId = (string)$request->headers->get(self::TRACE_HEADER);

        if ($traceId === '') {
            $traceId = $this->traceIdContext->getTraceId();
        } else {
            $this->traceIdContext->setTraceId($traceId);
        }

        // Add to response headers for debugging
        $request->attributes->set('_trace_id', $traceId);
    }
}
