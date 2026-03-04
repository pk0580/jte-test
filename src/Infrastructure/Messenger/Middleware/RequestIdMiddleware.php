<?php

namespace App\Infrastructure\Messenger\Middleware;

use App\Infrastructure\Logging\RequestIdProvider;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\StampInterface;

class RequestIdStamp implements StampInterface
{
    public function __construct(private readonly string $requestId) {}

    public function getRequestId(): string
    {
        return $this->requestId;
    }
}

class RequestIdMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly RequestIdProvider $requestIdProvider
    ) {}

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        // If we are sending a message, add the current request_id to it
        if (null === $envelope->last(RequestIdStamp::class)) {
            $envelope = $envelope->with(new RequestIdStamp($this->requestIdProvider->getRequestId()));
        }

        // If we are handling a message, extract the request_id and set it in the provider
        if ($stamp = $envelope->last(RequestIdStamp::class)) {
            $this->requestIdProvider->setRequestId($stamp->getRequestId());
        }

        return $stack->next()->handle($envelope, $stack);
    }
}
