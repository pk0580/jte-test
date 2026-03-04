<?php

namespace App\Application\Dto\Search;

readonly class SearchResultDto
{
    /**
     * @param mixed[] $items
     */
    public function __construct(
        public array $items,
        public int   $total,
        public int   $page = 1,
        public int   $limit = 10,
        public ?int  $lastId = null,
        public ?int  $status = null,
    ) {}
}
