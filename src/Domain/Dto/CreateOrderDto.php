<?php

namespace App\Domain\Dto;

readonly class CreateOrderDto
{
    /**
     * @param OrderArticleDto[] $articles
     */
    public function __construct(
        public string $clientName,
        public string $clientSurname,
        public string $email,
        public int $payType,
        public array $articles,
    ) {}
}
