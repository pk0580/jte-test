<?php

namespace App\Application\Dto;

readonly class OrderDto
{
    public function __construct(
        public ?int   $id,
        public string $hash,
        public string $number,
        public int    $status,
        public string $email,
        public string $clientName,
        public string $clientSurname,
        public string $currency,
        public float  $totalPrice,
        /** @var OrderArticleDto[] */
        public array  $articles,
    ) {}
}
