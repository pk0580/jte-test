<?php

namespace App\Infrastructure\Monitoring;

class TraceIdContext
{
    private ?string $traceId = null;

    public function getTraceId(): string
    {
        if ($this->traceId === null) {
            $this->traceId = $this->generateTraceId();
        }

        return $this->traceId;
    }

    public function setTraceId(string $traceId): void
    {
        $this->traceId = $traceId;
    }

    private function generateTraceId(): string
    {
        return bin2hex(random_bytes(16)); // 128-bit trace ID
    }
}
