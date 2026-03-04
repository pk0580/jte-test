<?php

namespace App\Infrastructure\Persistence\Doctrine\Listener;

use App\Domain\Dto\Outbox\OrderEventPayloadDto;
use App\Domain\Entity\Order;
use App\Domain\Entity\OutboxEvent;
use App\Domain\Enum\OrderEventType;
use App\Domain\Event\OrderCreatedEvent;
use App\Domain\Event\OrderUpdatedEvent;
use App\Domain\Event\OrderDeletedEvent;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\UnitOfWork;

class DomainEventListener
{
    public function onFlush(OnFlushEventArgs $args): void
    {
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
            }
        }
    }

    private function processEntityEvents(object $entity, UnitOfWork $uow, $em): void
    {
        if (method_exists($entity, 'pullDomainEvents')) {
            foreach ($entity->pullDomainEvents() as $event) {
                if ($event instanceof OrderCreatedEvent || $event instanceof OrderUpdatedEvent) {
                    $this->createOutboxEvent(OrderEventType::INDEXED, $event->getOrder()->getId(), $uow, $em);
                }
            }
        }
    }

    private function createOutboxEvent(OrderEventType $type, ?int $orderId, UnitOfWork $uow, $em): void
    {
        if ($orderId === null) {
            return;
        }

        $payloadDto = new OrderEventPayloadDto($orderId);
        $outboxEvent = new OutboxEvent($type, $payloadDto);

        $em->persist($outboxEvent);
        $uow->computeChangeSet($em->getClassMetadata(OutboxEvent::class), $outboxEvent);
    }
}
