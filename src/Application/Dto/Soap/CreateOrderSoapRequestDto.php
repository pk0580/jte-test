<?php

namespace App\Application\Dto\Soap;

use Symfony\Component\Validator\Constraints as Assert;

class CreateOrderSoapRequestDto
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public string $client_name,

        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public string $client_surname,

        #[Assert\NotBlank]
        #[Assert\Email]
        #[Assert\Length(max: 150)]
        public string $email,

        #[Assert\NotBlank]
        #[Assert\Positive]
        public int $pay_type,

        /** @var SoapOrderArticleDto[] */
        #[Assert\NotBlank]
        #[Assert\Valid]
        public array $articles = []
    ) {}
}
