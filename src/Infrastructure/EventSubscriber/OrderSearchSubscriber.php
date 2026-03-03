<?php

namespace App\Infrastructure\EventSubscriber;

use App\Application\Message\DeleteOrderMessage;
use App\Application\Message\IndexOrderMessage;
use App\Domain\Entity\Order;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsDoctrineListener(event: Events::postPersist, priority: 500, connection: 'default')]
#[AsDoctrineListener(event: Events::postUpdate, priority: 500, connection: 'default')]
#[AsDoctrineListener(event: Events::postRemove, priority: 500, connection: 'default')]
readonly class OrderSearchSubscriber
{
    public function __construct(private MessageBusInterface $messageBus) {}

    public function postPersist(PostPersistEventArgs $args): void
    {
        $entity = $args->getObject();
        if ($entity instanceof Order) {
            $this->messageBus->dispatch(new IndexOrderMessage($entity->getId()));
        }
    }

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $entity = $args->getObject();
        if ($entity instanceof Order) {
            $this->messageBus->dispatch(new IndexOrderMessage($entity->getId()));
        }
    }

    public function postRemove(PostRemoveEventArgs $args): void
    {
        $entity = $args->getObject();
        if ($entity instanceof Order) {
            $this->messageBus->dispatch(new DeleteOrderMessage($entity->getId()));
        }
    }
}
