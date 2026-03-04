<?php

namespace App\Domain\Entity;

use App\Domain\Enum\OrderEventType;
use Doctrine\ORM\Mapping as ORM;

use App\Domain\Dto\Outbox\OrderEventPayloadDto;

#[ORM\Entity]
#[ORM\Table(name: 'outbox_events')]
#[ORM\Index(columns: ['processed_at'], name: 'idx_outbox_processed_at')]
class OutboxEvent
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, enumType: OrderEventType::class)]
    private OrderEventType $eventType;

    #[ORM\Column(type: 'json')]
    private array $payload;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $processedAt = null;

    public function __construct(OrderEventType $eventType, OrderEventPayloadDto $payloadDto)
    {
        $this->eventType = $eventType;
        $this->payload = $payloadDto->toArray();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEventType(): OrderEventType
    {
        return $this->eventType;
    }

    public function getPayloadDto(): OrderEventPayloadDto
    {
        return OrderEventPayloadDto::fromArray($this->payload);
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getProcessedAt(): ?\DateTimeImmutable
    {
        return $this->processedAt;
    }

    public function setProcessedAt(?\DateTimeImmutable $processedAt): self
    {
        $this->processedAt = $processedAt;
        return $this;
    }
}
