<?php

namespace App\Domain\Repository;

interface OrderSearchIndexerRepositoryInterface
{
    /**
     * @return array<int, array>
     */
    public function findForIndexing(int $offset, int $limit): array;

    /**
     * @return iterable<int, array>
     */
    public function getIterableForIndexing(): iterable;
}
