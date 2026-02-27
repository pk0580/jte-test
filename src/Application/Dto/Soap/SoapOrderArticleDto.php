<?php

namespace App\Application\Dto\Soap;

class SoapOrderArticleDto
{
    public function __construct(
        public int $article_id,
        public string $amount,
        public string $price,
        public string $weight
    ) {}
}
