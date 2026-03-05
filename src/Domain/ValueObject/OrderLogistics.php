<?php

namespace App\Domain\ValueObject;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
final readonly class OrderLogistics
{
    public function __construct(
        #[ORM\Column(length: 100, nullable: true)]
        public ?string $trackingNumber = null,

        #[ORM\Column(length: 100, nullable: true)]
        public ?string $carrierName = null,

        #[ORM\Column(length: 255, nullable: true)]
        public ?string $carrierContactData = null,

        #[ORM\Column(type: 'decimal', precision: 12, scale: 3, nullable: true)]
        public ?string $weightGross = null,

        #[ORM\Column(type: 'json', nullable: true)]
        public ?array $warehouseData = null,

        #[ORM\Column(type: 'boolean', options: ['default' => true])]
        public bool $addressEqual = true,

        #[ORM\Column(type: 'bigint', nullable: true, options: ['unsigned' => true])]
        public ?string $addressPayer = null,
    ) {
    }

    public function withTrackingNumber(?string $trackingNumber): self
    {
        return new self($trackingNumber, $this->carrierName, $this->carrierContactData, $this->weightGross, $this->warehouseData, $this->addressEqual, $this->addressPayer);
    }

    public function withCarrierName(?string $carrierName): self
    {
        return new self($this->trackingNumber, $carrierName, $this->carrierContactData, $this->weightGross, $this->warehouseData, $this->addressEqual, $this->addressPayer);
    }

    public function withCarrierContactData(?string $carrierContactData): self
    {
        return new self($this->trackingNumber, $this->carrierName, $carrierContactData, $this->weightGross, $this->warehouseData, $this->addressEqual, $this->addressPayer);
    }

    public function withWeightGross(?string $weightGross): self
    {
        return new self($this->trackingNumber, $this->carrierName, $this->carrierContactData, $weightGross, $this->warehouseData, $this->addressEqual, $this->addressPayer);
    }

    public function withWarehouseData(?array $warehouseData): self
    {
        return new self($this->trackingNumber, $this->carrierName, $this->carrierContactData, $this->weightGross, $warehouseData, $this->addressEqual, $this->addressPayer);
    }

    public function withAddressEqual(bool $addressEqual): self
    {
        return new self($this->trackingNumber, $this->carrierName, $this->carrierContactData, $this->weightGross, $this->warehouseData, $addressEqual, $this->addressPayer);
    }

    public function withAddressPayer(?string $addressPayer): self
    {
        return new self($this->trackingNumber, $this->carrierName, $this->carrierContactData, $this->weightGross, $this->warehouseData, $this->addressEqual, $addressPayer);
    }
}
