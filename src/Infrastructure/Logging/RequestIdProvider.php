<?php

namespace App\Infrastructure\Logging;

class RequestIdProvider
{
    private ?string $requestId = null;

    public function getRequestId(): string
    {
        if ($this->requestId === null) {
            $this->requestId = bin2hex(random_bytes(8));
        }

        return $this->requestId;
    }

    public function setRequestId(string $requestId): void
    {
        $this->requestId = $requestId;
    }
}
