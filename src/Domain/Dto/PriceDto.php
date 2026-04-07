<?php

declare(strict_types=1);

namespace App\Domain\Dto;

readonly class PriceDto
{
    public function __construct(
        public float $price,
        public string $factory,
        public string $collection,
        public string $article,
        public string $currency = 'EUR',
    ) {
    }
}
