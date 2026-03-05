<?php

namespace App\Domain\ValueObject;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
final readonly class OrderReview
{
    public function __construct(
        #[ORM\Column(type: 'boolean', nullable: true)]
        public ?bool $productReview = null,

        #[ORM\Column(type: 'smallint', nullable: true, options: ['unsigned' => true])]
        public ?int $entranceReview = null
    ) {
    }

    public function withProductReview(?bool $productReview): self
    {
        return new self($productReview, $this->entranceReview);
    }

    public function withEntranceReview(?int $entranceReview): self
    {
        return new self($this->productReview, $entranceReview);
    }
}
