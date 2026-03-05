<?php

namespace App\Domain\ValueObject;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
final readonly class OrderPricing
{
    public function __construct(
        #[ORM\Column(type: 'decimal', precision: 12, scale: 2, options: ['default' => '0.00'])]
        public string $totalAmount = '0.00',

        #[ORM\Column(type: 'decimal', precision: 12, scale: 3, options: ['default' => '0.000'])]
        public string $totalWeight = '0.000',

        #[ORM\Column(type: 'decimal', precision: 12, scale: 2, nullable: true)]
        public ?string $deliveryPriceEuro = null,

        #[ORM\Column(type: 'boolean', nullable: true)]
        public ?bool $specPrice = null,
    ) {
    }

    public function withTotalAmount(string $totalAmount): self
    {
        return new self($totalAmount, $this->totalWeight, $this->deliveryPriceEuro, $this->specPrice);
    }

    public function withTotalWeight(string $totalWeight): self
    {
        return new self($this->totalAmount, $totalWeight, $this->deliveryPriceEuro, $this->specPrice);
    }

    public function withDeliveryPriceEuro(?string $deliveryPriceEuro): self
    {
        return new self($this->totalAmount, $this->totalWeight, $deliveryPriceEuro, $this->specPrice);
    }

    public function withSpecPrice(?bool $specPrice): self
    {
        return new self($this->totalAmount, $this->totalWeight, $this->deliveryPriceEuro, $specPrice);
    }
}
