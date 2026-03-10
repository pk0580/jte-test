<?php

namespace App\Application\UseCase;

use App\Application\Dto\Search\SearchOrderResponseDto;
use App\Application\Dto\Search\SearchResultDto;
use App\Domain\Dto\Search\SearchOrderDto;
use App\Domain\Repository\OrderSearchInterface;

readonly class SearchOrdersUseCase
{
    public function __construct(
        private OrderSearchInterface $orderSearch
    ) {}

    /**
     * @param string $query
     * @param int $page
     * @param int $limit
     * @param int|null $lastId
     * @param int|null $status
     * @return SearchResultDto
     */
    public function execute(
        string $query,
        int $page = 1,
        int $limit = 10,
        ?int $lastId = null,
        ?int $status = null
    ): SearchResultDto
    {
        $searchResult = $this->orderSearch->search($query, $page, $limit, $lastId, $status);

        $items = array_map(function (SearchOrderDto $order) {
            return new SearchOrderResponseDto(
                $order->id,
                $order->number,
                $order->email,
                $order->clientName,
                $order->clientSurname,
                $order->companyName,
                $order->description,
                $order->status,
            );
        }, $searchResult->items);

        return new SearchResultDto($items, $searchResult->total, $page, $limit, $lastId, $status);
    }
}
