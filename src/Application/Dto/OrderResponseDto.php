<?php

namespace App\Application\Dto;

class OrderResponseDto
{
    /**
     * @param OrderArticleResponseDto[] $articles
     */
    public function __construct(
        public int $id,
        public string $client_name,
        public string $client_surname,
        public string $email,
        public int $pay_type,
        public string $create_date,
        public array $articles
    ) {}
}
