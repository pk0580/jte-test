<?php

namespace App\Domain\ValueObject;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
final readonly class ManagerInfo
{
    public function __construct(
        #[ORM\Column(length: 100, nullable: true)]
        public ?string $name = null,
        #[ORM\Column(length: 150, nullable: true)]
        public ?string $email = null,
        #[ORM\Column(length: 30, nullable: true)]
        public ?string $phone = null,
    ) {
    }
}
