<?php

namespace App\Domain\ValueObject;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
class CustomerInfo
{
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $surname = null;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $companyName = null;

    #[ORM\Column(type: 'smallint', nullable: true, options: ['unsigned' => true])]
    private ?int $sex = null;

    public function __construct(
        ?string $name = null,
        ?string $surname = null,
        ?string $email = null,
        ?string $companyName = null,
        ?int $sex = null
    ) {
        $this->name = $name;
        $this->surname = $surname;
        $this->email = $email;
        $this->companyName = $companyName;
        $this->sex = $sex;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getSurname(): ?string
    {
        return $this->surname;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getCompanyName(): ?string
    {
        return $this->companyName;
    }

    public function getSex(): ?int
    {
        return $this->sex;
    }
}
