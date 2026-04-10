<?php

declare(strict_types=1);

namespace App\Application\Messenger\Handler;

use App\Application\Messenger\Message\InvalidateStatsCacheMessage;
use App\Domain\Enum\OrderEventType;
use App\Domain\Event\OrderCreatedEvent;
use App\Domain\Service\Cache\CacheInvalidatorInterface;
use App\Domain\Service\Metrics\MetricsServiceInterface;
use App\Domain\Service\Outbox\OutboxServiceInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Обработчик события создания заказа.
 *
 * Отвечает за выполнение побочных эффектов после создания заказа:
 * - Генерация событий Outbox (индексация, уведомления).
 * - Инвалидация кеша статистики.
 * - Сбор бизнес-метрик создания заказов.
 */
#[AsMessageHandler]
readonly class OrderCreatedEventHandler
{
    public function __construct(
        private OutboxServiceInterface    $outboxService,
        private CacheInvalidatorInterface $cacheInvalidator,
        private MetricsServiceInterface   $metricsService
    ) {
    }

    public function __invoke(OrderCreatedEvent $event): void
    {
        $orderId = $event->getOrder()->getId();

        // Transactional Outbox: сохраняем события в БД (без немедленного dispatch)
        $this->outboxService->add(OrderEventType::INDEXED, $orderId);
        $this->outboxService->add(OrderEventType::EMAIL_NOTIFICATION, $orderId);

        // Stats invalidation: асинхронно через Messenger
        $this->cacheInvalidator->invalidateStats();

        // Metrics: инкремент в Prometheus с лейблами
        $this->metricsService->incrementOrdersCreated([
            'status' => 'success',
            'source' => 'web', // В реальности можно брать из метаданных заказа
        ]);
    }
}
