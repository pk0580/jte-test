<?php

namespace App\Application\Dto\Order;

class OrderResponseDto
{
    /**
     * @param OrderArticleResponseDto[] $articles
     */
    public function __construct(
        public int $id,
        public string $clientName,
        public string $clientSurname,
        public string $email,
        public int $payType,
        public string $createDate,
        public array $articles
    ) {}
}
