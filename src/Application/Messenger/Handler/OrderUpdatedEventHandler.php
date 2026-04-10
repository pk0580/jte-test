<?php

declare(strict_types=1);

namespace App\Application\Messenger\Handler;

use App\Domain\Enum\OrderEventType;
use App\Domain\Event\OrderUpdatedEvent;
use App\Domain\Service\Cache\CacheInvalidatorInterface;
use App\Domain\Service\Outbox\OutboxServiceInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Обработчик события обновления заказа.
 *
 * Отвечает за выполнение побочных эффектов после обновления заказа:
 * - Генерация события Outbox для переиндексации.
 * - Инвалидация кеша статистики.
 */
#[AsMessageHandler]
readonly class OrderUpdatedEventHandler
{
    public function __construct(
        private OutboxServiceInterface    $outboxService,
        private CacheInvalidatorInterface $cacheInvalidator
    ) {
    }

    public function __invoke(OrderUpdatedEvent $event): void
    {
        $orderId = $event->getOrder()->getId();

        // Transactional Outbox
        $this->outboxService->add(OrderEventType::INDEXED, $orderId);

        // Stats invalidation
        $this->cacheInvalidator->invalidateStats();
    }
}
