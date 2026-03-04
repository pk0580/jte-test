<?php

namespace App\Domain\ValueObject;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
final readonly class DeliveryAddress
{
    public function __construct(
        #[ORM\Column(length: 20, nullable: true)]
        public ?string $index = null,
        #[ORM\Column(type: 'integer', nullable: true, options: ['unsigned' => true])]
        public ?int $countryId = null,
        #[ORM\Column(length: 100, nullable: true)]
        public ?string $region = null,
        #[ORM\Column(length: 200, nullable: true)]
        public ?string $city = null,
        #[ORM\Column(length: 300, nullable: true)]
        public ?string $address = null,
        #[ORM\Column(length: 200, nullable: true)]
        public ?string $building = null,
        #[ORM\Column(length: 30, nullable: true)]
        public ?string $apartmentOffice = null,
        #[ORM\Column(length: 20, nullable: true)]
        public ?string $phoneCode = null,
        #[ORM\Column(length: 30, nullable: true)]
        public ?string $phone = null
    ) {
    }
}
