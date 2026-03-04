<?php

namespace App\Infrastructure\EventSubscriber;

use App\Domain\Entity\Order;
use App\Domain\Entity\OutboxEvent;
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
            $em->persist(new OutboxEvent('order.indexed', ['id' => $entity->getId()]));
            // No explicit flush here, it will be flushed together with the order
        }
    }

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $entity = $args->getObject();
        if ($entity instanceof Order) {
            $em = $args->getObjectManager();
            $em->persist(new OutboxEvent('order.indexed', ['id' => $entity->getId()]));
            // No explicit flush here
        }
    }

    public function postRemove(PostRemoveEventArgs $args): void
    {
        $entity = $args->getObject();
        if ($entity instanceof Order) {
            $em = $args->getObjectManager();
            $em->persist(new OutboxEvent('order.deleted', ['id' => $entity->getId()]));
            // No explicit flush here
        }
    }
}
