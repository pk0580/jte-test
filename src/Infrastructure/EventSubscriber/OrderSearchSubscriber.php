<?php

namespace App\Infrastructure\EventSubscriber;

use App\Domain\Entity\Order;
use App\Domain\Repository\OrderSearchInterface;
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
    public function __construct(private OrderSearchInterface $orderSearch) {}

    public function postPersist(PostPersistEventArgs $args): void
    {
        $entity = $args->getObject();
        if ($entity instanceof Order) {
            try {
                $this->orderSearch->index($entity);
            } catch (\Throwable) {
                // Ignore errors in subscriber to not break the transaction
            }
        }
    }

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $entity = $args->getObject();
        if ($entity instanceof Order) {
            try {
                $this->orderSearch->index($entity);
            } catch (\Throwable) {
                // Ignore errors in subscriber to not break the transaction
            }
        }
    }

    public function postRemove(PostRemoveEventArgs $args): void
    {
        $entity = $args->getObject();
        if ($entity instanceof Order) {
            try {
                $this->orderSearch->delete($entity->getId());
            } catch (\Throwable) {
                // Ignore errors in subscriber to not break the transaction
            }
        }
    }
}
