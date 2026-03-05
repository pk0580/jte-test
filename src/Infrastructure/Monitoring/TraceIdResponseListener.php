<?php

namespace App\Infrastructure\Monitoring;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::RESPONSE, priority: -255)]
class TraceIdResponseListener
{
    private const string TRACE_HEADER = 'X-Trace-ID';

    public function __construct(
        private TraceIdContext $traceIdContext
    ) {}

    public function __invoke(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $event->getResponse()->headers->set(self::TRACE_HEADER, $this->traceIdContext->getTraceId());
    }
}
