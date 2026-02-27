<?php

namespace App\Application\Dto\Soap;

class CreateOrderSoapRequestDto
{
    public function __construct(
        public string $client_name,
        public string $client_surname,
        public string $email,
        public int $pay_type,
        /** @var SoapOrderArticleDto[] */
        public array $articles = []
    ) {}
}
