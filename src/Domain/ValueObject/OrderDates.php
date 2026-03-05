<?php

namespace App\Domain\ValueObject;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
final readonly class OrderDates
{
    public function __construct(
        #[ORM\Column(type: 'datetime_immutable', options: ['default' => 'CURRENT_TIMESTAMP'])]
        public \DateTimeImmutable $createAt = new \DateTimeImmutable(),

        #[ORM\Column(type: 'datetime', nullable: true)]
        public ?\DateTimeInterface $updateAt = null,

        #[ORM\Column(type: 'datetime', nullable: true)]
        public ?\DateTimeInterface $payDateExecution = null,

        #[ORM\Column(type: 'datetime', nullable: true)]
        public ?\DateTimeInterface $offsetDate = null,

        #[ORM\Column(type: 'datetime', nullable: true)]
        public ?\DateTimeInterface $proposedDate = null,

        #[ORM\Column(type: 'datetime', nullable: true)]
        public ?\DateTimeInterface $shipDate = null,

        #[ORM\Column(type: 'datetime', nullable: true)]
        public ?\DateTimeInterface $cancelDate = null,

        #[ORM\Column(type: 'datetime', nullable: true)]
        public ?\DateTimeInterface $fullPaymentDate = null,

        #[ORM\Column(type: 'smallint', nullable: true, options: ['unsigned' => true])]
        public ?int $offsetReason = null
    ) {
    }

    public function withUpdateAt(?\DateTimeInterface $updateAt): self
    {
        return new self($this->createAt, $updateAt, $this->payDateExecution, $this->offsetDate, $this->proposedDate, $this->shipDate, $this->cancelDate, $this->fullPaymentDate, $this->offsetReason);
    }

    public function withPayDateExecution(?\DateTimeInterface $payDateExecution): self
    {
        return new self($this->createAt, $this->updateAt, $payDateExecution, $this->offsetDate, $this->proposedDate, $this->shipDate, $this->cancelDate, $this->fullPaymentDate, $this->offsetReason);
    }

    public function withOffsetDate(?\DateTimeInterface $offsetDate): self
    {
        return new self($this->createAt, $this->updateAt, $this->payDateExecution, $offsetDate, $this->proposedDate, $this->shipDate, $this->cancelDate, $this->fullPaymentDate, $this->offsetReason);
    }

    public function withProposedDate(?\DateTimeInterface $proposedDate): self
    {
        return new self($this->createAt, $this->updateAt, $this->payDateExecution, $this->offsetDate, $proposedDate, $this->shipDate, $this->cancelDate, $this->fullPaymentDate, $this->offsetReason);
    }

    public function withShipDate(?\DateTimeInterface $shipDate): self
    {
        return new self($this->createAt, $this->updateAt, $this->payDateExecution, $this->offsetDate, $this->proposedDate, $shipDate, $this->cancelDate, $this->fullPaymentDate, $this->offsetReason);
    }

    public function withCancelDate(?\DateTimeInterface $cancelDate): self
    {
        return new self($this->createAt, $this->updateAt, $this->payDateExecution, $this->offsetDate, $this->proposedDate, $this->shipDate, $cancelDate, $this->fullPaymentDate, $this->offsetReason);
    }

    public function withFullPaymentDate(?\DateTimeInterface $fullPaymentDate): self
    {
        return new self($this->createAt, $this->updateAt, $this->payDateExecution, $this->offsetDate, $this->proposedDate, $this->shipDate, $this->cancelDate, $fullPaymentDate, $this->offsetReason);
    }

    public function withOffsetReason(?int $offsetReason): self
    {
        return new self($this->createAt, $this->updateAt, $this->payDateExecution, $this->offsetDate, $this->proposedDate, $this->shipDate, $this->cancelDate, $this->fullPaymentDate, $offsetReason);
    }
}
