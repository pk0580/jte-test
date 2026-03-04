<?php

namespace App\Domain\ValueObject;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
class DeliveryAddress
{
    #[ORM\Column(length: 20, nullable: true)]
    private ?string $index = null;

    #[ORM\Column(type: 'integer', nullable: true, options: ['unsigned' => true])]
    private ?int $countryId = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $region = null;

    #[ORM\Column(length: 200, nullable: true)]
    private ?string $city = null;

    #[ORM\Column(length: 300, nullable: true)]
    private ?string $address = null;

    #[ORM\Column(length: 200, nullable: true)]
    private ?string $building = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $apartmentOffice = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $phoneCode = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $phone = null;

    public function __construct(
        ?string $index = null,
        ?int $countryId = null,
        ?string $region = null,
        ?string $city = null,
        ?string $address = null,
        ?string $building = null,
        ?string $apartmentOffice = null,
        ?string $phoneCode = null,
        ?string $phone = null
    ) {
        $this->index = $index;
        $this->countryId = $countryId;
        $this->region = $region;
        $this->city = $city;
        $this->address = $address;
        $this->building = $building;
        $this->apartmentOffice = $apartmentOffice;
        $this->phoneCode = $phoneCode;
        $this->phone = $phone;
    }

    public function getIndex(): ?string
    {
        return $this->index;
    }

    public function getCountryId(): ?int
    {
        return $this->countryId;
    }

    public function getRegion(): ?string
    {
        return $this->region;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function getBuilding(): ?string
    {
        return $this->building;
    }

    public function getApartmentOffice(): ?string
    {
        return $this->apartmentOffice;
    }

    public function getPhoneCode(): ?string
    {
        return $this->phoneCode;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }
}
