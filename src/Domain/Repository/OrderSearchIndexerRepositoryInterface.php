<?php

namespace App\Domain\Repository;

interface OrderSearchIndexerRepositoryInterface
{
    /**
     * @return array<int, array>
     */
    public function findForIndexing(int $offset, int $limit): array;
}
