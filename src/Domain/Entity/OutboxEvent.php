<?php

namespace App\Domain\Entity;

use App\Domain\Enum\OrderEventType;
use Doctrine\ORM\Mapping as ORM;

use App\Domain\Dto\Outbox\OrderEventPayloadDto;

#[ORM\Entity]
#[ORM\Table(name: 'outbox_events')]
#[ORM\Index(columns: ['processed_at', 'attempts'], name: 'idx_outbox_process_lookup')]
#[ORM\Index(columns: ['scheduled_at'], name: 'idx_outbox_scheduled_at')]
#[ORM\Index(columns: ['created_at'], name: 'idx_outbox_created_at')]
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

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $scheduledAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $processedAt = null;

    #[ORM\Column(type: 'integer')]
    private int $attempts = 0;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $lastError = null;

    public function __construct(OrderEventType $eventType, OrderEventPayloadDto $payloadDto)
    {
        $this->eventType = $eventType;
        $this->payload = $payloadDto->toArray();
        $this->createdAt = new \DateTimeImmutable();
        $this->scheduledAt = new \DateTimeImmutable();
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

    public function getScheduledAt(): \DateTimeImmutable
    {
        return $this->scheduledAt;
    }

    public function setScheduledAt(\DateTimeImmutable $scheduledAt): self
    {
        $this->scheduledAt = $scheduledAt;
        return $this;
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

    public function getAttempts(): int
    {
        return $this->attempts;
    }

    public function incrementAttempts(): self
    {
        $this->attempts++;
        return $this;
    }

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    public function setLastError(?string $lastError): self
    {
        $this->lastError = $lastError;
        return $this;
    }
}
