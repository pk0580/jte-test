<?php

declare(strict_types=1);

namespace App\Infrastructure\Service\Outbox;

use App\Domain\Dto\Outbox\OrderEventPayloadDto;
use App\Domain\Entity\OutboxEvent;
use App\Domain\Enum\OrderEventType;
use App\Domain\Service\Outbox\OutboxServiceInterface;
use Doctrine\ORM\EntityManagerInterface;

readonly class OutboxService implements OutboxServiceInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    public function add(OrderEventType $type, int $orderId): void
    {
        // Идемпотентность: проверяем наличие события в текущем Unit of Work или в БД
        $repository = $this->entityManager->getRepository(OutboxEvent::class);

        // 1. Проверяем в БД
        $existing = $repository->findOneBy([
            'eventType' => $type,
            'orderId' => $orderId,
            'processedAt' => null,
        ]);

        if ($existing !== null) {
            return;
        }

        // 2. Проверяем в Unit of Work (Identity Map и запланированные вставки),
        // чтобы избежать дублей при повторных вызовах в рамках одного flush/postFlush цикла.
        $uow = $this->entityManager->getUnitOfWork();
        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            if ($entity instanceof OutboxEvent &&
                $entity->getEventType() === $type &&
                $entity->getPayloadDto()->id === $orderId &&
                $entity->getProcessedAt() === null
            ) {
                return;
            }
        }

        $payloadDto = new OrderEventPayloadDto($orderId);
        $outboxEvent = new OutboxEvent($type, $payloadDto);
        $this->entityManager->persist($outboxEvent);
    }
}
