<?php

namespace App\Application\UseCase;

use App\Application\Dto\PriceDto;
use App\Domain\Service\PriceParserInterface;

readonly class GetPriceUseCase
{
    public function __construct(
        private PriceParserInterface $priceParser
    ) {}

    public function execute(string $factory, string $collection, string $article): PriceDto
    {
        return $this->priceParser->parse($factory, $collection, $article);
    }
}
