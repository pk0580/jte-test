<?php

namespace App\Infrastructure\Search;

use App\Infrastructure\Search\Dto\SearchQueryDto;

class OrderSearchQueryBuilder
{
    public function build(string $query, int $page, int $limit, ?int $lastId = null, ?int $status = null): SearchQueryDto
    {
        $isCursorPagination = $lastId !== null && $lastId > 0;

        if ($isCursorPagination) {
            $offset = 0;
            // Cursor pagination always needs predictable sort
            $sort = ['id' => 'desc'];
        } else {
            // High load optimization: limit max OFFSET to prevent performance degradation
            // If page is too high, we should encourage UI to use cursor pagination
            $maxOffset = 10000;
            $offset = ($page - 1) * $limit;

            if ($offset > $maxOffset) {
                $offset = $maxOffset;
            }

            $sort = [];
        }

        // Apply weights for matching
        $weightedQuery = "@number ^5 | @email ^3 | @client_name ^2 | @client_surname ^2 | @company_name ^1 | $query";
        if (str_contains($query, '|') || str_contains($query, '@') || str_contains($query, '^')) {
            // If user already uses special syntax, don't overwrite it
            $weightedQuery = $query;
        }

        return new SearchQueryDto(
            query: $weightedQuery,
            originalQuery: $query,
            offset: $offset,
            limit: $limit,
            lastId: $lastId,
            sort: $sort,
            status: $status
        );
    }
}
