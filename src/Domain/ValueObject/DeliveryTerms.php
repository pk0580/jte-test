<?php

namespace App\Domain\ValueObject;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
final readonly class DeliveryTerms
{
    public function __construct(
        #[ORM\Column(type: 'decimal', precision: 12, scale: 2, nullable: true)]
        public ?string $cost = null,
        #[ORM\Column(type: 'smallint', nullable: true, options: ['unsigned' => true, 'default' => 0])]
        public ?int $type = 0,
        #[ORM\Column(type: 'date', nullable: true)]
        public ?\DateTimeInterface $timeMin = null,
        #[ORM\Column(type: 'date', nullable: true)]
        public ?\DateTimeInterface $timeMax = null
    ) {
    }
}
