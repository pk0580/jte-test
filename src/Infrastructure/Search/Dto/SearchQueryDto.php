<?php

declare(strict_types=1);

namespace App\Infrastructure\Search\Dto;

readonly class SearchQueryDto
{
    public function __construct(
        public string  $query,
        public string  $originalQuery,
        public int     $offset,
        public int     $limit,
        public ?int    $lastId = null,
        public array   $sort = [],
        public ?int    $status = null
    ) {
    }
}
