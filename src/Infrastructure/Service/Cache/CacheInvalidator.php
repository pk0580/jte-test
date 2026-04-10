<?php

declare(strict_types=1);

namespace App\Infrastructure\Service\Cache;

use App\Application\Messenger\Message\InvalidateStatsCacheMessage;
use App\Domain\Service\Cache\CacheInvalidatorInterface;
use Symfony\Component\Messenger\MessageBusInterface;

readonly class CacheInvalidator implements CacheInvalidatorInterface
{
    public function __construct(
        private MessageBusInterface $messageBus
    ) {
    }

    public function invalidateStats(): void
    {
        // Только отправка сообщения. Сама инвалидация произойдет асинхронно в Handler'е.
        // Это обеспечивает атомарность: OutboxEvent -> Transaction Commit -> Message Bus Dispatch (через Outbox или в конце).
        $this->messageBus->dispatch(new InvalidateStatsCacheMessage());
    }
}
