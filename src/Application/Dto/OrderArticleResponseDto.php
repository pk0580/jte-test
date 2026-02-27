<?php

namespace App\Application\Dto;

class OrderArticleResponseDto
{
    public function __construct(
        public int $id,
        public int $article_id,
        public string $amount,
        public string $price,
        public string $weight
    ) {}
}
