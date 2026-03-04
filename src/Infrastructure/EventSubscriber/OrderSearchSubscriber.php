<?php

namespace App\Infrastructure\EventSubscriber;

use App\Domain\Dto\Outbox\OrderEventPayloadDto;
use App\Domain\Entity\Order;
use App\Domain\Entity\OutboxEvent;
use App\Domain\Enum\OrderEventType;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;

#[AsDoctrineListener(event: Events::postPersist, priority: 500, connection: 'default')]
#[AsDoctrineListener(event: Events::postUpdate, priority: 500, connection: 'default')]
#[AsDoctrineListener(event: Events::postRemove, priority: 500, connection: 'default')]
readonly class OrderSearchSubscriber
{
    public function postPersist(PostPersistEventArgs $args): void
    {
        $entity = $args->getObject();
        if ($entity instanceof Order) {
            $em = $args->getObjectManager();
            $payload = new OrderEventPayloadDto($entity->getId());
            $em->persist(new OutboxEvent(OrderEventType::INDEXED, $payload));
        }
    }

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $entity = $args->getObject();
        if ($entity instanceof Order) {
            $em = $args->getObjectManager();
            $payload = new OrderEventPayloadDto($entity->getId());
            $em->persist(new OutboxEvent(OrderEventType::INDEXED, $payload));
        }
    }

    public function postRemove(PostRemoveEventArgs $args): void
    {
        $entity = $args->getObject();
        if ($entity instanceof Order) {
            $em = $args->getObjectManager();
            $payload = new OrderEventPayloadDto($entity->getId());
            $em->persist(new OutboxEvent(OrderEventType::DELETED, $payload));
        }
    }
}
