<?php

namespace App\Domain\ValueObject;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
final readonly class CustomerInfo
{
    public function __construct(
        #[ORM\Column(length: 255, nullable: true)]
        public ?string $name = null,
        #[ORM\Column(length: 255, nullable: true)]
        public ?string $surname = null,
        #[ORM\Column(length: 150, nullable: true)]
        public ?string $email = null,
        #[ORM\Column(length: 255, nullable: true)]
        public ?string $companyName = null,
        #[ORM\Column(type: 'smallint', nullable: true, options: ['unsigned' => true])]
        public ?int $sex = null
    ) {
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }
}
