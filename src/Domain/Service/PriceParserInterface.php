<?php

namespace App\Domain\Service;

use App\Application\Dto\PriceDto;

interface PriceParserInterface
{
    /**
     * @param string $factory
     * @param string $collection
     * @param string $article
     * @return PriceDto
     */
    public function parse(string $factory, string $collection, string $article): PriceDto;
}
