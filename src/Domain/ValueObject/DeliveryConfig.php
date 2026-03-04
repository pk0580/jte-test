<?php

namespace App\Domain\ValueObject;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
final readonly class DeliveryConfig
{
    public function __construct(
        #[ORM\Column(type: 'date', nullable: true)]
        public ?\DateTimeInterface $deliveryTimeConfirmMin = null,
        #[ORM\Column(type: 'date', nullable: true)]
        public ?\DateTimeInterface $deliveryTimeConfirmMax = null,
        #[ORM\Column(type: 'date', nullable: true)]
        public ?\DateTimeInterface $deliveryTimeFastPayMin = null,
        #[ORM\Column(type: 'date', nullable: true)]
        public ?\DateTimeInterface $deliveryTimeFastPayMax = null,
        #[ORM\Column(type: 'date', nullable: true)]
        public ?\DateTimeInterface $deliveryOldTimeMin = null,
        #[ORM\Column(type: 'date', nullable: true)]
        public ?\DateTimeInterface $deliveryOldTimeMax = null,
        #[ORM\Column(type: 'datetime', nullable: true)]
        public ?\DateTimeInterface $factDate = null,
        #[ORM\Column(type: 'datetime', nullable: true)]
        public ?\DateTimeInterface $sendingDate = null,
        #[ORM\Column(type: 'smallint', nullable: true, options: ['unsigned' => true, 'default' => 0])]
        public ?int $deliveryCalculateType = 0,
        #[ORM\Column(type: 'decimal', precision: 12, scale: 3, nullable: true)]
        public ?string $weightGross = null,
        #[ORM\Column(length: 100, nullable: true)]
        public ?string $carrierName = null,
        #[ORM\Column(length: 255, nullable: true)]
        public ?string $carrierContactData = null,
    ) {
    }
}
