<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Listener;

use App\Application\Service\DomainEventDispatcher;
use App\Domain\Contract\HasDomainEventsInterface;
use App\Domain\Entity\Order;
use App\Domain\Entity\OutboxEvent;
use App\Domain\Event\DomainEventInterface;
use App\Domain\Event\OrderDeletedEvent;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Events;

/**
 * Слушатель жизненного цикла Doctrine для сбора и диспетчеризации доменных событий.
 *
 * Собирает события из сущностей, реализующих HasDomainEventsInterface, во время фазы onFlush
 * и передает их в DomainEventDispatcher после успешного завершения транзакции (postFlush).
 * Это гарантирует, что события будут обработаны только в случае успешного сохранения изменений в БД.
 */
#[AsDoctrineListener(event: Events::onFlush)]
#[AsDoctrineListener(event: Events::postFlush)]
class DomainEventListener
{
    /** @var DomainEventInterface[] */
    private array $collectedEvents = [];

    private bool $isDispatching = false;

    public function __construct(
        private readonly DomainEventDispatcher $dispatcher
    ) {
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        if ($this->isDispatching) {
            return;
        }

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
        if ($this->isDispatching) {
            return;
        }

        if ($this->collectedEvents) {
            $this->isDispatching = true;
            try {
                $eventsToProcess = $this->collectedEvents;
                $this->collectedEvents = [];

                $this->dispatcher->dispatch($eventsToProcess);

                // Для Transactional Outbox делаем дополнительный flush после диспетчеризации:
                // на этом этапе у доменных сущностей уже есть сгенерированные ID.
                $em = $args->getObjectManager();
                $uow = $em->getUnitOfWork();
                $hasOutboxInsertions = false;
                foreach ($uow->getScheduledEntityInsertions() as $entity) {
                    if ($entity instanceof OutboxEvent) {
                        $hasOutboxInsertions = true;
                        break;
                    }
                }

                if ($hasOutboxInsertions && $em->isOpen()) {
                    $em->flush();
                }
            } catch (\Exception $e) {
                // Игнорируем ошибки диспетчеризации
            } finally {
                $this->isDispatching = false;
            }
        }
    }
}
