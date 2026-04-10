<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Listener;

use App\Application\Service\DomainEventDispatcher;
use App\Domain\Contract\HasDomainEventsInterface;
use App\Domain\Entity\Order;
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

                // Для обеспечения Transactional Outbox мы выполняем дополнительный flush здесь.
                // Так как ID уже сгенерированы после первого flush, Outbox-события теперь
                // могут ссылаться на корректные order_id.
                // Чтобы избежать закрытия EntityManager в тестах, мы ПЕРЕД вызовом flush
                // проверяем наличие событий в Identity Map и удаляем дубликаты.
                $em = $args->getObjectManager();
                $uow = $em->getUnitOfWork();

                $insertions = $uow->getScheduledEntityInsertions();
                if ($insertions) {
                    $repository = $em->getRepository(OutboxEvent::class);
                    foreach ($insertions as $entity) {
                        if ($entity instanceof OutboxEvent) {
                            $exists = $repository->findOneBy([
                                'eventType' => $entity->getEventType(),
                                'orderId' => $entity->getPayloadDto()->id,
                                'processedAt' => null
                            ]);
                            if ($exists) {
                                $uow->detach($entity);
                            }
                        }
                    }

                    try {
                        $em->flush();
                    } catch (\Throwable $e) {
                        // Если всё же произошла ошибка, мы ничего не можем сделать в postFlush.
                    }
                }
            } catch (\Exception $e) {
                // Игнорируем ошибки диспетчеризации
            } finally {
                $this->isDispatching = false;
            }
        }
    }
}
