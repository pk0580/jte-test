<?php

namespace App\Infrastructure\Persistence\Doctrine\Listener;

use App\Domain\Contract\HasDomainEventsInterface;
use App\Application\Messenger\Message\InvalidateStatsCacheMessage;
use App\Domain\Dto\Outbox\OrderEventPayloadDto;
use App\Domain\Entity\Order;
use App\Domain\Entity\OutboxEvent;
use App\Domain\Enum\OrderEventType;
use App\Domain\Event\OrderCreatedEvent;
use App\Domain\Event\OrderUpdatedEvent;
use App\Domain\Event\OrderDeletedEvent;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Prometheus\CollectorRegistry;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class DomainEventListener
{
    private bool $needsInvalidation = false;
    private float $onFlushStart = 0.0;

    public function __construct(
        private readonly MessageBusInterface $messageBus,
        private readonly \Symfony\Contracts\Cache\CacheInterface $appCache,
        private readonly CollectorRegistry $collectorRegistry
    ) {}

    public function onFlush(OnFlushEventArgs $args): void
    {
        $this->onFlushStart = microtime(true);
        $em = $args->getObjectManager();
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            $this->processEntityEvents($entity, $uow, $em);
        }

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            $this->processEntityEvents($entity, $uow, $em);
        }

        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            if ($entity instanceof Order) {
                $this->createOutboxEvent(OrderEventType::DELETED, $entity->getId(), $uow, $em);
                $this->invalidateStats();
            }
        }
    }

    private function invalidateStats(): void
    {
        $this->needsInvalidation = true;
    }

    public function postFlush(): void
    {
        if ($this->onFlushStart > 0) {
            $duration = microtime(true) - $this->onFlushStart;
            // Record metrics only if significant or using a more efficient way
            // For now, let's keep it but maybe optimize registry access if it's a known bottleneck
            $this->recordMetrics($duration);
            $this->onFlushStart = 0.0;
        }

        if ($this->needsInvalidation) {
            // Update last update timestamp in cache for ETag
            $timestamp = (string)microtime(true);
            $this->appCache->delete('order_last_update_timestamp');
            $this->appCache->get('order_last_update_timestamp', fn() => $timestamp);

            $this->messageBus->dispatch(new InvalidateStatsCacheMessage());
            $this->needsInvalidation = false;
        }
    }

    private function recordMetrics(float $duration): void
    {
        try {
            $summary = $this->collectorRegistry->getOrRegisterSummary(
                'app',
                'doctrine_flush_duration_seconds',
                'Duration of Doctrine flush process',
                [],
                600,
                [0.5, 0.9, 0.99]
            );
            $summary->observe($duration);
        } catch (\Exception $e) {
            // Prevent monitoring from breaking the main flow
        }
    }

    private function processEntityEvents(object $entity, UnitOfWork $uow, $em): void
    {
        if ($entity instanceof HasDomainEventsInterface) {
            foreach ($entity->pullDomainEvents() as $event) {
                if ($event instanceof OrderCreatedEvent || $event instanceof OrderUpdatedEvent) {
                    $this->createOutboxEvent(OrderEventType::INDEXED, $event->getOrder()->getId(), $uow, $em);
                    $this->invalidateStats();

                    if ($event instanceof OrderCreatedEvent) {
                        $order = $event->getOrder();
                        // Упрощаем: не формируем payload для письма здесь,
                        // переносим это в обработчик Outbox.
                        $this->createOutboxEvent(
                            OrderEventType::EMAIL_NOTIFICATION,
                            $order->getId(),
                            $uow,
                            $em
                        );
                    }
                }
            }
        }
    }

    private function createOutboxEvent(OrderEventType $type, ?int $orderId, UnitOfWork $uow, $em, array $extra = []): void
    {
        if ($orderId === null) {
            return;
        }

        $payloadDto = new OrderEventPayloadDto($orderId, $extra);
        $outboxEvent = new OutboxEvent($type, $payloadDto);

        $em->persist($outboxEvent);
        $uow->computeChangeSet($em->getClassMetadata(OutboxEvent::class), $outboxEvent);
    }
}
