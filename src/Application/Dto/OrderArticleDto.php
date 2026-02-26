<?php

namespace App\Application\Dto;

readonly class OrderArticleDto
{
    public function __construct(
        public ?int   $id,
        public int    $articleId,
        public float  $amount,
        public float  $price,
        public ?float $priceEur,
        public string $currency,
        public string $measure,
    ) {}
}
