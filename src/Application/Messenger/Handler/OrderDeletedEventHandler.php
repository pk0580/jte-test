<?php

declare(strict_types=1);

namespace App\Application\Messenger\Handler;

use App\Domain\Enum\OrderEventType;
use App\Domain\Event\OrderDeletedEvent;
use App\Domain\Service\Cache\CacheInvalidatorInterface;
use App\Domain\Service\Outbox\OutboxServiceInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Обработчик события удаления заказа.
 *
 * Отвечает за выполнение побочных эффектов после удаления заказа:
 * - Генерация события Outbox для удаления из индекса.
 * - Инвалидация кеша статистики.
 */
#[AsMessageHandler]
readonly class OrderDeletedEventHandler
{
    public function __construct(
        private OutboxServiceInterface    $outboxService,
        private CacheInvalidatorInterface $cacheInvalidator
    ) {
    }

    public function __invoke(OrderDeletedEvent $event): void
    {
        $orderId = $event->getOrderId();

        // Transactional Outbox
        $this->outboxService->add(OrderEventType::DELETED, $orderId);

        // Stats invalidation
        $this->cacheInvalidator->invalidateStats();
    }
}
