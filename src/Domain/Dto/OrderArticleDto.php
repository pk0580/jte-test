<?php

namespace App\Domain\Dto;

readonly class OrderArticleDto
{
    public function __construct(
        public int $articleId,
        public float $amount,
        public float $price,
        public float $weight,
    ) {}
}
