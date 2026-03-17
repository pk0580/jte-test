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
use App\Domain\Event\DomainEventInterface;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Events;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Prometheus\CollectorRegistry;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\Cache\ItemInterface;

#[AsDoctrineListener(event: Events::onFlush)]
#[AsDoctrineListener(event: Events::postFlush)]
class DomainEventListener
{
    private bool $needsInvalidation = false;
    private float $onFlushStart = 0.0;

    /** @var DomainEventInterface[] */
    private array $collectedEvents = [];

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
            $this->collectEntityEvents($entity);
        }

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            $this->collectEntityEvents($entity);
        }

        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            if ($entity instanceof Order) {
                $this->collectedEvents[] = new OrderDeletedEvent($entity->getId());
                $this->invalidateStats();
            }
        }
    }

    private function collectEntityEvents(object $entity): void
    {
        if ($entity instanceof HasDomainEventsInterface) {
            foreach ($entity->pullDomainEvents() as $event) {
                $this->collectedEvents[] = $event;
            }
        }
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        if ($this->collectedEvents) {
            $em = $args->getObjectManager();
            $uow = $em->getUnitOfWork();
            $eventsToProcess = $this->collectedEvents;
            $this->collectedEvents = [];

            foreach ($eventsToProcess as $event) {
                if ($event instanceof OrderCreatedEvent) {
                    $this->createOutboxEvent(OrderEventType::INDEXED, $event->getOrder()->getId(), $em);
                    $this->createOutboxEvent(OrderEventType::EMAIL_NOTIFICATION, $event->getOrder()->getId(), $em);
                    $this->invalidateStats();
                    $this->incrementAppCounter('orders_created_count');
                } elseif ($event instanceof OrderUpdatedEvent) {
                    $this->createOutboxEvent(OrderEventType::INDEXED, $event->getOrder()->getId(), $em);
                    $this->invalidateStats();
                } elseif ($event instanceof OrderDeletedEvent) {
                    $this->createOutboxEvent(OrderEventType::DELETED, $event->getOrderId(), $em);
                    $this->invalidateStats();
                }
            }
            $em->flush();
        }

        if ($this->onFlushStart > 0) {
            $duration = microtime(true) - $this->onFlushStart;
            $this->recordMetrics($duration);
            $this->onFlushStart = 0.0;
        }

        if ($this->needsInvalidation) {
            // Update last update timestamp in cache for ETag
            $timestamp = (string)microtime(true);
            $this->appCache->delete('order_last_update_timestamp');
            $this->appCache->get('order_last_update_timestamp', function (ItemInterface $item) use ($timestamp) {
                $item->set($timestamp);
                return $timestamp;
            });

            // Wait a tiny bit to ensure next request gets a different timestamp if microtime is too fast
            usleep(5000);

            $this->messageBus->dispatch(new InvalidateStatsCacheMessage());
            $this->needsInvalidation = false;
        }
    }

    private function invalidateStats(): void
    {
        $this->needsInvalidation = true;
    }

    private function recordMetrics(float $duration): void
    {
        try {
            $cacheKey = 'doctrine_flush_duration';
            $item = $this->appCache->get($cacheKey, function (ItemInterface $item) {
                return [];
            });
            $durations = is_array($item) ? $item : [];
            $durations[] = $duration;
            if (count($durations) > 10) {
                array_shift($durations);
            }
            // Since $appCache is not always TagAware, just use simple save if possible or re-get/set.
            // Using Symfony Cache Interface:
            $this->appCache->delete($cacheKey);
            $this->appCache->get($cacheKey, function (ItemInterface $item) use ($durations) {
                $item->set($durations);
                return $durations;
            });

            $summary = $this->collectorRegistry->getOrRegisterSummary(
                'app',
                'doctrine_flush_duration_seconds',
                'Duration of Doctrine flush process',
                [],
                600,
                [0.5, 0.9, 0.99]
            );
            $summary->observe($duration, []);
        } catch (\Exception $e) {
            // Prevent monitoring from breaking the main flow
        }
    }

    private function createOutboxEvent(OrderEventType $type, ?int $orderId, $em, array $extra = []): void
    {
        if ($orderId === null) {
            return;
        }

        $payloadDto = new OrderEventPayloadDto($orderId, $extra);
        $outboxEvent = new OutboxEvent($type, $payloadDto);

        $em->persist($outboxEvent);
    }

    private function incrementAppCounter(string $name): void
    {
        try {
            $val = (int)$this->appCache->get($name, function (ItemInterface $item) {
                return 0;
            });
            $this->appCache->delete($name);
            $this->appCache->get($name, function (ItemInterface $item) use ($val) {
                $item->set($val + 1);
                return $val + 1;
            });
        } catch (\Exception) {
        }
    }
}
