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
            $sort = ['id' => 'desc'];
        } else {
            $offset = ($page - 1) * $limit;
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
            offset: $offset,
            limit: $limit,
            lastId: $lastId,
            sort: $sort,
            status: $status
        );
    }
}
