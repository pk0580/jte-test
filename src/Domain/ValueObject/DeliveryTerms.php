<?php

namespace App\Domain\ValueObject;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
class DeliveryTerms
{
    #[ORM\Column(type: 'decimal', precision: 12, scale: 2, nullable: true)]
    private ?string $cost = null;

    #[ORM\Column(type: 'smallint', nullable: true, options: ['unsigned' => true, 'default' => 0])]
    private ?int $type = 0;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $timeMin = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $timeMax = null;

    public function __construct(
        ?string $cost = null,
        ?int $type = 0,
        ?\DateTimeInterface $timeMin = null,
        ?\DateTimeInterface $timeMax = null
    ) {
        $this->cost = $cost;
        $this->type = $type;
        $this->timeMin = $timeMin;
        $this->timeMax = $timeMax;
    }

    public function getCost(): ?string
    {
        return $this->cost;
    }

    public function getType(): ?int
    {
        return $this->type;
    }

    public function getTimeMin(): ?\DateTimeInterface
    {
        return $this->timeMin;
    }

    public function getTimeMax(): ?\DateTimeInterface
    {
        return $this->timeMax;
    }
}
