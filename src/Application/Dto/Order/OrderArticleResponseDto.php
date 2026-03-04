<?php

namespace App\Application\Dto\Order;

class OrderArticleResponseDto
{
    public function __construct(
        public int $id,
        public int $articleId,
        public string $amount,
        public string $price,
        public string $weight
    ) {}
}
