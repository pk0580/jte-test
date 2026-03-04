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
        public ?\DateTimeInterface $fullPaymentDate = null
    ) {
    }

    public function withUpdateAt(?\DateTimeInterface $updateAt): self
    {
        return new self(
            $this->createAt,
            $updateAt,
            $this->payDateExecution,
            $this->offsetDate,
            $this->proposedDate,
            $this->shipDate,
            $this->cancelDate,
            $this->fullPaymentDate
        );
    }
}
